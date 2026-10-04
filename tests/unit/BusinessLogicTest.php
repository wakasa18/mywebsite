<?php

use App\Libraries\AccountSession;
use App\Libraries\CashMovementReport;
use App\Libraries\DiscountAllocator;
use App\Libraries\ReorderForecastService;
use App\Libraries\ReturnCondition;
use App\Libraries\SaleRevision;
use CodeIgniter\Test\CIUnitTestCase;

class_alias(\CodeIgniter\Database\SQLite3\Result::class, 'LogicTestResult');
class_alias(\CodeIgniter\Database\SQLite3\Builder::class, 'LogicTestBuilder');

/** SQLite executes the real controller queries; locking hooks simulate a prior writer.
 * These tests do not claim to verify MySQL's concurrent lock scheduling.
 */
final class LogicTestConnection extends \CodeIgniter\Database\SQLite3\Connection
{
    public $beforeSaleLock = null;
    public $beforeInventoryLock = null;
    public array $locks = [];

    public function query(string $sql, $binds = null, bool $setEscapeFlags = true, string $queryClass = '')
    {
        $sql = str_replace("CONCAT(LEFT(t.grp, 4), '-W', LPAD(RIGHT(t.grp, 2), 2, '0'))", "SUBSTR(t.grp, 1, 4) || '-W' || SUBSTR(t.grp, 5, 2)", $sql);
        if (str_contains($sql, 'FOR UPDATE')) {
            $this->locks[] = $sql;
            if (str_contains($sql, 'FROM branch_products WHERE') && $this->beforeInventoryLock) {
                $callback = $this->beforeInventoryLock;
                $this->beforeInventoryLock = null;
                $callback($this);
            }
            if (str_contains($sql, 'FROM sales WHERE') && $this->beforeSaleLock) {
                $callback = $this->beforeSaleLock;
                $this->beforeSaleLock = null;
                $callback($this);
            }
            $sql = str_replace(' FOR UPDATE', '', $sql);
        }
        return parent::query($sql, $binds, $setEscapeFlags, $queryClass);
    }
}

final class BusinessLogicTest extends CIUnitTestCase
{
    private array $oldConnections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new LogicTestConnection([
            'database' => ':memory:', 'DBDriver' => 'SQLite3', 'DBPrefix' => '',
            'DBDebug' => true, 'foreignKeys' => true, 'busyTimeout' => 1000,
        ]);
        $this->db->initialize();
        $this->db->connID->createFunction('GREATEST', static fn ($a, $b) => max($a, $b), 2);
        $this->db->connID->createFunction('YEARWEEK', static fn ($date, $mode) => (int) date('oW', strtotime($date)), 2);
        $this->db->connID->createFunction('DATE_FORMAT', static fn ($date, $format) => date(str_replace(['%Y', '%m', '%d'], ['Y', 'm', 'd'], $format), strtotime($date)), 2);
        $property = new ReflectionProperty(\CodeIgniter\Database\Config::class, 'instances');
        $this->oldConnections = $property->getValue();
        $property->setValue(null, ['tests' => $this->db]);
        foreach ([
            'users' => 'id INTEGER PRIMARY KEY, full_name TEXT, username TEXT, password TEXT, role TEXT, branch_id INTEGER, status TEXT, session_version TEXT, updated_at TEXT',
            'branches' => 'id INTEGER PRIMARY KEY, branch_name TEXT, address TEXT, contact_number TEXT, status TEXT',
            'products' => 'id INTEGER PRIMARY KEY, product_name TEXT, sku TEXT, category_id INTEGER, unit TEXT, stock INTEGER, cost_price NUMERIC, price NUMERIC, reorder_level INTEGER, manufacturer TEXT, supplier_id INTEGER, status TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT',
            'branch_products' => 'id INTEGER PRIMARY KEY, product_id INTEGER, branch_id INTEGER, stock INTEGER, reorder_level INTEGER, price NUMERIC, cost_price NUMERIC, expiration_date TEXT, status TEXT, updated_at TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0',
            'sales' => 'id INTEGER PRIMARY KEY, invoice_no TEXT, user_id INTEGER, branch_id INTEGER, discount_id INTEGER, total_amount NUMERIC, discount_amount NUMERIC, final_total NUMERIC, amount_paid NUMERIC, change_amount NUMERIC, payment_method TEXT, reference_no TEXT, status TEXT, notes TEXT, sale_date TEXT, updated_at TEXT',
            'discounts' => 'id INTEGER PRIMARY KEY, discount_name TEXT, discount_type TEXT, discount_value NUMERIC, applies_to TEXT, category_id INTEGER, product_id INTEGER, minimum_purchase NUMERIC, max_discount_amount NUMERIC, status TEXT, start_date TEXT, end_date TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT',
            'sale_items' => 'id INTEGER PRIMARY KEY, sale_id INTEGER, product_id INTEGER, product_name_snapshot TEXT, quantity INTEGER, price NUMERIC, cost_price_at_sale NUMERIC, subtotal NUMERIC, discount_applied NUMERIC, profit NUMERIC',
            'refund_items' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, sale_id INTEGER, sale_item_id INTEGER, product_id INTEGER, product_name_snapshot TEXT, quantity_refunded INTEGER, price_at_sale NUMERIC, refund_subtotal NUMERIC, refunded_by INTEGER, reason TEXT, return_condition TEXT DEFAULT "resellable", refund_method TEXT, refund_event_id TEXT, created_at TEXT',
            'stock_logs' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, branch_id INTEGER, user_id INTEGER, action_type TEXT, quantity INTEGER, previous_stock INTEGER, new_stock INTEGER, remarks TEXT, supplier_id INTEGER, manufacturer TEXT, created_at TEXT',
            'activity_logs' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, activity TEXT, log_time TEXT',
            'forecasting_data' => 'id INTEGER PRIMARY KEY AUTOINCREMENT, product_id INTEGER, forecast_month TEXT, predicted_quantity INTEGER, predicted_revenue NUMERIC, method_used TEXT, generated_at TEXT',
        ] as $table => $fields) {
            $this->db->query('CREATE TABLE ' . $table . ' (' . $fields . ')');
        }
        $this->db->table('users')->insert(['id' => 1, 'full_name' => 'Test Staff', 'username' => 'staff', 'password' => 'hashed', 'role' => 'admin', 'branch_id' => 1, 'status' => 'active']);
        $this->db->table('branches')->insert(['id' => 1, 'branch_name' => 'Branch A', 'status' => 'active']);
        $this->db->table('products')->insert(['id' => 1, 'product_name' => 'Test Item', 'sku' => 'TEST', 'stock' => 10, 'status' => 'active']);
        $this->db->table('branch_products')->insert(['id' => 1, 'product_id' => 1, 'branch_id' => 1, 'stock' => 10, 'reorder_level' => 100, 'price' => 10, 'cost_price' => 4, 'status' => 'active']);
        $this->db->table('sales')->insert(['id' => 1, 'invoice_no' => 'TEST-1', 'user_id' => 1, 'branch_id' => 1, 'total_amount' => 50, 'discount_amount' => 0, 'final_total' => 50, 'amount_paid' => 100, 'change_amount' => 50, 'payment_method' => 'cash', 'status' => 'completed', 'sale_date' => '2026-09-14 10:00:00']);
        $this->db->table('sale_items')->insert(['id' => 1, 'sale_id' => 1, 'product_id' => 1, 'product_name_snapshot' => 'Test Item', 'quantity' => 5, 'price' => 10, 'cost_price_at_sale' => 4, 'subtotal' => 50, 'discount_applied' => 0, 'profit' => 30]);
        session()->set(['isLoggedIn' => true, 'user_id' => 1, 'role' => 'admin', 'branch_id' => 1, 'account_fingerprint' => AccountSession::fingerprint($this->row('users'))]);
    }

    protected function tearDown(): void
    {
        (new ReflectionProperty(\CodeIgniter\Database\Config::class, 'instances'))->setValue(null, $this->oldConnections);
        $this->db->close();
        parent::tearDown();
    }

    private function row(string $table): array
    {
        return $this->db->table($table)->where('id', 1)->get()->getRowArray();
    }

    private function controller(string $class, array $post)
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(new \Config\App(), new \CodeIgniter\HTTP\SiteURI(new \Config\App()), null, new \CodeIgniter\HTTP\UserAgent());
        $request->setMethod('POST');
        $request->setGlobal('post', $post);
        $request->setGlobal('request', $post);
        service('validation')->reset();
        \Config\Services::injectMock('request', $request);
        $controller = new $class();
        $controller->initController($request, service('response'), service('logger'));
        return $controller;
    }

    private function prepareBranchTrash(): \App\Libraries\BranchProductTrash
    {
        $this->db->query('CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, updated_at TEXT)');
        $this->db->query('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, supplier_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        $this->db->table('branches')->insert(['id'=>2,'branch_name'=>'Branch B','status'=>'active']);
        $this->db->table('products')->where('id',1)->update(['unit'=>'box','stock'=>17]);
        $this->db->table('branch_products')->insert(['id'=>2,'product_id'=>1,'branch_id'=>2,'stock'=>7,'price'=>15,'cost_price'=>6,'reorder_level'=>2,'expiration_date'=>'2099-01-01','status'=>'active']);
        return new \App\Libraries\BranchProductTrash($this->db);
    }

    private function retainedModels(): array
    {
        $this->db->query('CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        $this->db->query('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, supplier_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        $this->db->table('categories')->insert(['id'=>1,'category_name'=>'Test Category']);
        $this->db->table('suppliers')->insert(['id'=>1,'supplier_name'=>'Test Supplier']);
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Test Discount','status'=>'active','applies_to'=>'all']);
        return [
            'products'=>new \App\Models\ProductModel($this->db),
            'branch_products'=>new \App\Models\BranchProductModel($this->db),
            'categories'=>new \App\Models\CategoryModel($this->db),
            'suppliers'=>new \App\Models\SupplierModel($this->db),
            'discounts'=>new \App\Models\DiscountModel($this->db),
        ];
    }

    public function testTrashAndRestoreRetainEveryDeletableRecordAndReferences(): void
    {
        $sale=$this->row('sales'); $item=$this->row('sale_items');
        foreach ($this->retainedModels() as $table=>$model) {
            $before=$this->row($table);
            $this->assertTrue($model->delete(1),$table);
            $deleted=$this->row($table);
            $this->assertSame(1,(int)$deleted['is_deleted'],$table);
            $this->assertNotEmpty($deleted['deleted_at'],$table);
            $this->assertNull($model->find(1),$table);
            $this->assertSame(0,$model->countAllResults(),$table);
            $this->assertSame(1,(int)$model->onlyDeleted()->first()['id'],$table);
            $this->assertSame(1,(int)$model->withDeleted()->find(1)['id'],$table);
            $this->assertTrue($model->restoreRecord(1),$table);
            $after=$this->row($table);
            $this->assertSame(0,(int)$after['is_deleted'],$table);
            $this->assertNull($after['deleted_at'],$table);
            $this->assertNotNull($model->find(1),$table);
            foreach (['deleted_at','is_deleted','updated_at'] as $field) {
                unset($before[$field],$deleted[$field],$after[$field]);
            }
            $this->assertSame($before,$deleted,$table . ' data changed in Trash');
            $this->assertSame($before,$after,$table . ' data changed on Restore');
        }
        $this->assertSame($sale,$this->row('sales'));
        $this->assertSame($item,$this->row('sale_items'));
    }

    public function testModelsRejectPermanentDeletionAndUnscopedTrash(): void
    {
        foreach ($this->retainedModels() as $table=>$model) {
            foreach ([static fn()=> $model->delete(1,true), static fn()=> $model->purgeDeleted(), static fn()=> $model->delete()] as $operation) {
                try { $operation(); $this->fail($table . ' allowed permanent or unscoped deletion'); }
                catch (\LogicException|\CodeIgniter\Database\Exceptions\DatabaseException $error) { $this->assertNotEmpty($error->getMessage()); }
                $this->assertSame(1,$this->db->table($table)->countAllResults());
                $this->assertSame(0,(int)$this->row($table)['is_deleted']);
            }
            $model->delete(1);
            try { $model->purgeDeleted(); $this->fail('Trash purged'); }
            catch (\LogicException $error) { $this->assertStringContainsString('disabled',$error->getMessage()); }
            $this->assertNotEmpty($this->row($table)['deleted_at']);
        }
    }

    public function testFlagOnlyAndLegacyTrashAreExcludedAndPaginateCorrectly(): void
    {
        $this->db->table('products')->insertBatch([
            ['id'=>2,'product_name'=>'Flag only','is_deleted'=>1,'deleted_at'=>null],
            ['id'=>3,'product_name'=>'Legacy timestamp','is_deleted'=>0,'deleted_at'=>'2026-09-01'],
            ['id'=>4,'product_name'=>'Current','is_deleted'=>0,'deleted_at'=>null],
        ]);
        $model=new \App\Models\ProductModel($this->db);
        $this->assertEquals([1,4],$model->orderBy('id')->findColumn('id'));
        $this->assertNull($model->find(2));
        $this->assertNull($model->find(3));
        $this->assertNull($model->where('id',2)->first());
        $this->assertEquals([1],array_column($model->orderBy('id')->paginate(1,'retained',1),'id'));
        $this->assertSame(2,$model->pager->getTotal('retained'));
        $this->assertEquals([4],array_column($model->orderBy('id')->paginate(1,'retained',2),'id'));
        $this->assertEquals([2,3],array_column($model->onlyDeleted()->orderBy('id')->paginate(10,'trash',1),'id'));
        $this->assertSame(2,$model->pager->getTotal('trash'));
        $this->assertSame(4,$model->withDeleted()->countAllResults());
        $this->assertSame(2,$model->countAllResults());
        $model->restoreRecord(2); $model->restoreRecord(3);
        $this->assertSame(4,$model->countAllResults());
    }

    public function testPermanentDeleteEndpointsRequireTrashAndAdministrator(): void
    {
        $models=$this->retainedModels();
        foreach ([\App\Controllers\Products::class,\App\Controllers\Categories::class,\App\Controllers\Admin\Suppliers::class,\App\Controllers\Admin\Discounts::class] as $class) {
            $response=$this->controller($class,[])->forceDelete(1);
            $this->assertSame(302,$response->getStatusCode());
            $this->assertNotEmpty(session()->getFlashdata('error'));
            session()->set('role','cashier');
            $this->assertSame(403,$this->controller($class,[])->forceDelete(1)->getStatusCode());
            session()->set('role','admin');
        }
        foreach ($models as $table=>$model) $this->assertNotNull($model->find(1),$table);
        $this->assertSame(0,$this->db->table('activity_logs')->countAllResults());
    }

    public function testRestoreControllersClearBothDeletionMarkers(): void
    {
        $models=$this->retainedModels();
        foreach (['categories'=>\App\Controllers\Categories::class,'suppliers'=>\App\Controllers\Admin\Suppliers::class,'discounts'=>\App\Controllers\Admin\Discounts::class] as $table=>$class) {
            $models[$table]->delete(1);
            $this->controller($class,[])->restore(1);
            $this->assertSame(0,(int)$this->row($table)['is_deleted'],$table);
            $this->assertNull($this->row($table)['deleted_at'],$table);
            $this->assertNotNull($models[$table]->find(1),$table);
        }
        $models['products']->delete(1);
        $this->controller(\App\Controllers\Products::class,['legacy_restore'=>'1'])->restore(1);
        $this->assertSame(0,(int)$this->row('products')['is_deleted']);
        $this->assertNull($this->row('products')['deleted_at']);
    }

    public function testPermanentDeletionHidesRecordsFromTrashButRetainsAllReferences(): void
    {
        $sale=$this->row('sales'); $item=$this->row('sale_items');
        $service=new \App\Libraries\RetainedRecordDeletion($this->db);
        foreach ($this->retainedModels() as $table=>$model) {
            if ($table==='branch_products') continue;
            $model->delete(1);
            $before=$this->row($table);
            $this->assertTrue($service->hide($table,1,'admin',1),$table);
            $hidden=$this->row($table);
            $this->assertSame(1,(int)$hidden['is_deleted']);
            $this->assertSame(1,(int)$hidden['is_permanently_deleted']);
            $this->assertNotEmpty($hidden['permanently_deleted_at']);
            $this->assertNull($model->find(1));
            $this->assertNull($model->onlyDeleted()->find(1));
            $this->assertSame(0,$model->onlyDeleted()->countAllResults());
            $this->assertNotNull($model->withDeleted()->find(1));
            $this->assertFalse($model->restoreRecord(1));
            $this->assertFalse($service->hide($table,1,'admin',1));
            foreach (['permanently_deleted_at','is_permanently_deleted','updated_at'] as $field) unset($before[$field],$hidden[$field]);
            $this->assertSame($before,$hidden,$table . ' data was changed');
        }
        $this->assertSame(4,$this->db->table('activity_logs')->countAllResults());
        $this->assertSame($sale,$this->row('sales'));
        $this->assertSame($item,$this->row('sale_items'));
    }

    public function testPermanentDeleteControllersRetainUsedRecordsAndRejectRestoration(): void
    {
        $models=$this->retainedModels();
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1]);
        $this->db->table('products')->where('id',1)->update(['supplier_id'=>1,'category_id'=>1]);
        foreach (['categories'=>\App\Controllers\Categories::class,'suppliers'=>\App\Controllers\Admin\Suppliers::class,'discounts'=>\App\Controllers\Admin\Discounts::class] as $table=>$class) {
            $models[$table]->delete(1);
            $this->controller($class,[])->forceDelete(1);
            $this->assertStringContainsString('retained',session()->getFlashdata('success'));
            $when=$this->row($table)['permanently_deleted_at'];
            $this->assertNotEmpty($when,$table);
            $this->controller($class,[])->restore(1);
            $this->assertSame($when,$this->row($table)['permanently_deleted_at'],$table);
            $this->assertSame(1,(int)$this->row($table)['is_deleted']);
            $html=$this->controller($class,[])->trash();
            $this->assertStringNotContainsString('/restore/1',$html);
        }
        $this->assertSame(1,(int)$this->row('sales')['discount_id']);
        $this->assertSame(1,(int)$this->row('products')['supplier_id']);
        $this->assertSame(1,(int)$this->row('products')['category_id']);
    }

    public function testPermanentDeletionRollsBackIfAuditCannotBeWritten(): void
    {
        $models=$this->retainedModels();
        $models['suppliers']->delete(1);
        $before=$this->row('suppliers');
        $this->db->query("CREATE TRIGGER reject_hide_audit BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'audit failure'); END");
        try { (new \App\Libraries\RetainedRecordDeletion($this->db))->hide('suppliers',1,'admin',1); $this->fail('Audit failure was ignored'); }
        catch (\Throwable $error) { $this->assertNotEmpty($error->getMessage()); }
        $this->assertSame($before,$this->row('suppliers'));
        $this->assertNotNull($models['suppliers']->onlyDeleted()->find(1));
    }

    public function testPermanentBranchDeletionRetainsStockAndOtherBranches(): void
    {
        $service=$this->prepareBranchTrash();
        $service->change(1,1,true,'admin',1);
        $before=$this->row('branch_products');
        $master=$this->row('products');
        $other=$this->db->table('branch_products')->where('id',2)->get()->getRowArray();
        $controller=$this->controller(\App\Controllers\Products::class,['branch_id'=>1]);
        $controller->forceDelete(1);
        $hidden=$this->row('branch_products');
        $this->assertNotEmpty($hidden['permanently_deleted_at']);
        $this->assertSame(1,(int)$hidden['is_permanently_deleted']);
        $this->assertSame(1,(int)$hidden['is_deleted']);
        foreach (['permanently_deleted_at','is_permanently_deleted','updated_at'] as $field) unset($before[$field],$hidden[$field]);
        $this->assertSame($before,$hidden);
        $this->assertSame($master,$this->row('products'));
        $this->assertSame($other,$this->db->table('branch_products')->where('id',2)->get()->getRowArray());
        $this->assertFalse($service->permanentlyDelete(1,1,'admin',1));
        $this->assertSame(2,$this->db->table('activity_logs')->countAllResults());
        $this->assertSame(2,$this->db->table('stock_logs')->countAllResults());
        $this->controller(\App\Controllers\Products::class,['branch_id'=>1])->restore(1);
        $this->assertStringContainsString('cannot be restored',session()->getFlashdata('error'));
        $html=$this->controller(\App\Controllers\Products::class,[])->trash();
        $this->assertStringNotContainsString('name="branch_id" value="1"',$html);
        $this->assertStringNotContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
        session()->set('branch_id',2);
        $this->assertStringContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
    }

    public function testPermanentBranchDeletionRequiresTrashAndDoesNotGuessBranch(): void
    {
        $service=$this->prepareBranchTrash();
        foreach ([['branch_id'=>1],[],['branch_id'=>0],['branch_id'=>999],['branch_id'=>[1]]] as $input) {
            $this->controller(\App\Controllers\Products::class,$input)->forceDelete(1);
            $this->assertNull($this->row('branch_products')['permanently_deleted_at']);
            $this->assertNull($this->row('products')['permanently_deleted_at']);
        }
        $service->change(1,1,true,'admin',1);
        session()->set('role','cashier');
        $this->assertSame(403,$this->controller(\App\Controllers\Products::class,['branch_id'=>1])->forceDelete(1)->getStatusCode());
        $this->assertNull($this->row('branch_products')['permanently_deleted_at']);
    }

    public function testPermanentBranchDeletionRollsBackOnAuditFailure(): void
    {
        $service=$this->prepareBranchTrash();
        $service->change(1,1,true,'admin',1);
        $before=$this->row('branch_products');
        $this->db->query("CREATE TRIGGER reject_permanent_branch_audit BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'audit failure'); END");
        try { $service->permanentlyDelete(1,1,'admin',1); $this->fail('Audit failure was ignored'); }
        catch (\Throwable $error) { $this->assertNotEmpty($error->getMessage()); }
        $this->assertSame($before,$this->row('branch_products'));
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
    }

    public function testLegacyPermanentDeletionRequiresExplicitSharedAction(): void
    {
        $this->prepareBranchTrash();
        $this->db->table('products')->where('id',1)->update(['is_deleted'=>1,'deleted_at'=>'2026-09-01']);
        $this->controller(\App\Controllers\Products::class,[])->forceDelete(1);
        $this->assertNull($this->row('products')['permanently_deleted_at']);
        $this->controller(\App\Controllers\Products::class,['legacy_delete'=>'1'])->forceDelete(1);
        $this->assertNotEmpty($this->row('products')['permanently_deleted_at']);
        $this->controller(\App\Controllers\Products::class,['legacy_restore'=>'1'])->restore(1);
        $this->assertSame(1,(int)$this->row('products')['is_deleted']);
        $this->assertNull($this->row('branch_products')['permanently_deleted_at']);
        $this->assertStringNotContainsString('Restore Shared Product',$this->controller(\App\Controllers\Products::class,[])->trash());
    }

    public function testPermanentDeleteMigrationIsAdditiveAndRepeatable(): void
    {
        $models=$this->retainedModels();
        foreach ($models as $table=>$model) $this->db->query('ALTER TABLE ' . $table . ' DROP COLUMN permanently_deleted_at');
        $this->db->resetDataCache();
        $before=$this->row('branch_products');
        require_once APPPATH . 'Database/Migrations/2026_10_03_000008_add_permanent_delete_markers.php';
        $migration=new \App\Database\Migrations\AddPermanentDeleteMarkers(new \CodeIgniter\Database\SQLite3\Forge($this->db));
        $migration->up(); $migration->up();
        foreach ($models as $table=>$model) {
            $this->assertTrue($this->db->fieldExists('permanently_deleted_at',$table));
            $this->assertNull($this->row($table)['permanently_deleted_at']);
        }
        $this->assertEquals($before+['permanently_deleted_at'=>null],$this->row('branch_products'));
        $this->db->table('products')->where('id',1)->update(['is_deleted'=>1,'is_permanently_deleted'=>1,'deleted_at'=>'2026-09-01','permanently_deleted_at'=>'2026-10-03']);
        $migration->up();
        $this->assertSame('2026-10-03',$this->row('products')['permanently_deleted_at']);
        $this->assertNull($models['products']->onlyDeleted()->find(1));
    }

    public function testResettingPermanentFlagAllowsRestoreWithoutErasingTimestamp(): void
    {
        $models=$this->retainedModels();
        $service=new \App\Libraries\RetainedRecordDeletion($this->db);
        foreach ($models as $table=>$model) {
            if ($table==='branch_products') continue;
            $model->delete(1);
            $service->hide($table,1,'admin',1);
            $when=$this->row($table)['permanently_deleted_at'];
            $this->db->table($table)->where('id',1)->update(['is_permanently_deleted'=>0]);
            $this->assertNotNull($model->onlyDeleted()->find(1),$table);
            $this->assertTrue($model->restoreRecord(1),$table);
            $this->assertNotNull($model->find(1),$table);
            $this->assertSame($when,$this->row($table)['permanently_deleted_at'],$table);
            $this->assertSame(0,(int)$this->row($table)['is_permanently_deleted']);
            $this->assertSame(0,(int)$this->row($table)['is_deleted']);
            $model->delete(1);
            $this->assertTrue($service->hide($table,1,'admin',1),$table . ' old timestamp blocked a new deletion');
            $this->assertSame(1,(int)$this->row($table)['is_permanently_deleted']);
        }
        $this->assertSame(8,$this->db->table('activity_logs')->countAllResults());
    }

    public function testBranchFlagResetReturnsOnlySelectedAssignmentToTrashAndPos(): void
    {
        $service=$this->prepareBranchTrash();
        $service->change(1,1,true,'admin',1);
        $service->permanentlyDelete(1,1,'admin',1);
        $when=$this->row('branch_products')['permanently_deleted_at'];
        $other=$this->db->table('branch_products')->where('id',2)->get()->getRowArray();
        $this->db->table('branch_products')->where('id',1)->update(['is_permanently_deleted'=>0]);
        $this->assertStringContainsString('name="branch_id" value="1"',$this->controller(\App\Controllers\Products::class,[])->trash());
        $this->controller(\App\Controllers\Products::class,['branch_id'=>1])->restore(1);
        $this->assertSame(0,(int)$this->row('branch_products')['is_deleted']);
        $this->assertSame($when,$this->row('branch_products')['permanently_deleted_at']);
        $this->assertStringContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
        $this->assertSame($other,$this->db->table('branch_products')->where('id',2)->get()->getRowArray());
        $exchange=new \App\Libraries\ProductExchange($this->db);
        $this->assertNotEmpty($exchange->quote(1,$this->exchangeInput(),1,'admin')['replacements']);
        $expiry=new \App\Libraries\ExpiryStockResolution($this->db);
        $row=$expiry->inventory(1,'admin',0);
        $this->assertStringContainsString('?action=replace',$this->controller(\App\Controllers\ExpiryStockController::class,[])->show(1));
        $this->assertTrue($expiry->resolve(1,'remove',['outcome'=>'disposed','quantity'=>1,'reason'=>'Recovered stock action','confirmed'=>'1'],'admin',0,1,str_repeat('c',48),$expiry::revision($row)));
        $this->assertSame(9,(int)$this->row('branch_products')['stock']);
    }

    public function testPermanentFlagAloneHidesRowsWhenTimestampsAreMissing(): void
    {
        foreach ($this->retainedModels() as $table=>$model) {
            $this->db->table($table)->where('id',1)->update(['is_permanently_deleted'=>1]);
            $this->assertNull($model->find(1),$table);
            $this->assertNull($model->onlyDeleted()->find(1),$table);
            $this->assertSame(0,$model->countAllResults(),$table);
            $this->assertFalse($model->restoreRecord(1),$table);
            $this->assertNotNull($model->withDeleted()->find(1),$table);
        }
        $this->assertStringNotContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
    }

    public function testHistoricalPermanentDatesDoNotHideRecoveredInventory(): void
    {
        $this->prepareBranchTrash();
        foreach (['products','branch_products'] as $table) $this->db->table($table)->where('id',1)->update(['permanently_deleted_at'=>'2026-09-01']);
        $this->assertStringContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
        $reports=$this->controller(\App\Controllers\Admin\ReportsController::class,[]);
        $this->assertCount(2,(new ReflectionMethod($reports,'expiryProductsBuilder'))->invoke($reports,'','all',date('Y-m-d'),date('Y-m-d',strtotime('+30 days')))->get()->getResultArray());
        $dashboard=$this->controller(\App\Controllers\Dashboard::class,[]);
        $this->assertSame(2,(new ReflectionMethod($dashboard,'activeInventoryBuilder'))->invoke($dashboard)->countAllResults());
        $token=str_repeat('b',32);
        session()->set(['cart'=>[1=>['product_id'=>1,'quantity'=>1]],'checkout_token'=>$token]);
        $this->controller(\App\Controllers\Cashier\SalesController::class,['_checkout_token'=>$token,'items'=>[1=>1],'payment_method'=>'cash','amount_paid'=>'10'])->checkout();
        $this->assertSame(2,$this->db->table('sales')->countAllResults());
        $this->assertSame(9,(int)$this->row('branch_products')['stock']);
    }

    public function testPermanentFlagMigrationBackfillsOnceAndPreservesRecovery(): void
    {
        $models=$this->retainedModels();
        foreach ($models as $table=>$model) $this->db->query('ALTER TABLE ' . $table . ' DROP COLUMN is_permanently_deleted');
        foreach (['products','suppliers'] as $table) $this->db->table($table)->where('id',1)->update(['is_deleted'=>1,'deleted_at'=>'2026-09-01','permanently_deleted_at'=>'2026-10-03']);
        $this->db->resetDataCache();
        $before=$this->row('branch_products');
        require_once APPPATH . 'Database/Migrations/2026_10_03_000009_add_permanent_delete_flags.php';
        $migration=new \App\Database\Migrations\AddPermanentDeleteFlags(new \CodeIgniter\Database\SQLite3\Forge($this->db));
        $migration->up();
        foreach ($models as $table=>$model) {
            $this->assertTrue($this->db->fieldExists('is_permanently_deleted',$table));
            $this->assertSame(in_array($table,['products','suppliers'],true) ? 1 : 0,(int)$this->row($table)['is_permanently_deleted']);
        }
        $this->assertEquals($before+['is_permanently_deleted'=>0],$this->row('branch_products'));
        $this->db->table('products')->where('id',1)->update(['is_permanently_deleted'=>0]);
        $migration->up();
        $this->assertSame(0,(int)$this->row('products')['is_permanently_deleted']);
        $this->assertSame('2026-10-03',$this->row('products')['permanently_deleted_at']);
        $this->assertNotNull($models['products']->onlyDeleted()->find(1));
        $this->assertSame(1,(int)$this->row('suppliers')['is_permanently_deleted']);
    }

    public function testSoftDeleteMigrationBackfillsWithoutLosingFlagsOrData(): void
    {
        $models=$this->retainedModels();
        $beforeSale=$this->row('sales'); $beforeInventory=$this->row('branch_products');
        foreach ($models as $table=>$model) {
            $this->db->query('ALTER TABLE ' . $table . ' DROP COLUMN is_deleted');
            if ($table !== 'branch_products') $this->db->table($table)->where('id',1)->update(['deleted_at'=>'2026-09-28 09:00:00']);
        }
        $this->db->query('ALTER TABLE branch_products DROP COLUMN deleted_at');
        $this->db->resetDataCache();
        require_once APPPATH . 'Database/Migrations/2026_10_03_000007_add_soft_delete_flags.php';
        $migration=new \App\Database\Migrations\AddSoftDeleteFlags(new \CodeIgniter\Database\SQLite3\Forge($this->db));
        $migration->up();
        foreach ($models as $table=>$model) {
            $this->assertTrue($this->db->fieldExists('is_deleted',$table));
            $this->assertSame($table==='branch_products' ? 0 : 1,(int)$this->row($table)['is_deleted']);
        }
        $this->db->table('branch_products')->where('id',1)->update(['is_deleted'=>1]);
        $migration->up();
        $this->assertSame(1,(int)$this->row('branch_products')['is_deleted']);
        $this->assertNull($this->row('branch_products')['deleted_at']);
        $after=$this->row('branch_products'); $beforeInventory['is_deleted']=1;
        $this->assertEquals($beforeInventory,$after);
        $this->assertSame($beforeSale,$this->row('sales'));
    }

    public function testFlagOnlyBranchTrashIsIsolatedAndCannotBeSoldOrChanged(): void
    {
        $service=$this->prepareBranchTrash();
        $this->db->table('branch_products')->where('id',1)->update(['is_deleted'=>1]);
        $this->assertStringNotContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
        session()->set('branch_id',2);
        $this->assertStringContainsString('Test Item',$this->controller(\App\Controllers\Cashier\SalesController::class,[])->searchProducts());
        $reports=$this->controller(\App\Controllers\Admin\ReportsController::class,[]);
        $this->assertCount(1,(new ReflectionMethod($reports,'expiryProductsBuilder'))->invoke($reports,'','all',date('Y-m-d'),date('Y-m-d',strtotime('+30 days')))->get()->getResultArray());
        $dashboard=$this->controller(\App\Controllers\Dashboard::class,[]);
        $this->assertSame(1,(new ReflectionMethod($dashboard,'activeInventoryBuilder'))->invoke($dashboard)->countAllResults());
        $exchange=new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput();
        $this->db->table('branch_products')->where('id',100)->update(['is_deleted'=>1]);
        try { $exchange->quote(1,$input,1,'admin'); $this->fail('Flagged replacement accepted'); }
        catch (InvalidArgumentException $error) { $this->assertStringContainsString('unavailable',$error->getMessage()); }
        $expiry=new \App\Libraries\ExpiryStockResolution($this->db);
        $row=$expiry->inventory(1,'admin',0);
        try { $expiry->resolve(1,'remove',['outcome'=>'disposed','quantity'=>1,'reason'=>'Test removal','confirmed'=>'1'],'admin',0,1,str_repeat('a',48),$expiry::revision($row)); $this->fail('Flagged stock changed'); }
        catch (InvalidArgumentException $error) { $this->assertStringContainsString('active',$error->getMessage()); }
        $service->change(1,1,false,'admin',1);
        $this->assertSame(0,(int)$this->row('branch_products')['is_deleted']);
        $this->assertNull($this->row('branch_products')['deleted_at']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame(7,(int)$this->db->table('branch_products')->where('id',2)->get()->getRowArray()['stock']);
    }

    public function testFlagOnlyTrashIsRecheckedAtCheckoutLock(): void
    {
        $this->prepareBranchTrash();
        $token=str_repeat('a',32);
        session()->set(['cart'=>[1=>['product_id'=>1,'quantity'=>1]],'checkout_token'=>$token]);
        $this->db->beforeInventoryLock=static function($db){$db->table('branch_products')->where('id',1)->update(['is_deleted'=>1]);};
        $this->controller(\App\Controllers\Cashier\SalesController::class,['_checkout_token'=>$token,'items'=>[1=>1],'payment_method'=>'cash','amount_paid'=>'10'])->checkout();
        $this->assertSame(1,$this->db->table('sales')->countAllResults());
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertNotEmpty(session('cart'));
    }

    public function testBranchTrashAndRestoreKeepOtherBranchesAndSharedDetails(): void
    {
        $service = $this->prepareBranchTrash();
        $master = $this->row('products');
        $other = $this->db->table('branch_products')->where('id',2)->get()->getRowArray();
        $this->db->table('branch_products')->where('id',1)->update(['status'=>'inactive','expiration_date'=>'2099-02-01']);
        $this->assertTrue($service->change(1,1,true,'admin',1));
        $this->assertFalse($service->change(1,1,true,'admin',1));
        $this->assertNotEmpty($this->row('branch_products')['deleted_at']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame('inactive',$this->row('branch_products')['status']);
        $this->assertSame($master,$this->row('products'));
        $this->assertSame($other,$this->db->table('branch_products')->where('id',2)->get()->getRowArray());
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
        $this->assertTrue($service->change(1,2,true,'admin',1));
        $this->assertTrue($service->change(1,1,false,'admin',1));
        $this->assertFalse($service->change(1,1,false,'admin',1));
        $this->assertNull($this->row('branch_products')['deleted_at']);
        $this->assertSame('inactive',$this->row('branch_products')['status']);
        $this->assertSame('2099-02-01',$this->row('branch_products')['expiration_date']);
        $this->assertNotEmpty($this->db->table('branch_products')->where('id',2)->get()->getRowArray()['deleted_at']);
        $logs=$this->db->table('stock_logs')->orderBy('id')->get()->getResultArray();
        $this->assertCount(3,$logs);
        $this->assertEquals([0,0,0],array_column($logs,'quantity'));
        $this->assertEquals([10,7,10],array_column($logs,'previous_stock'));
        $this->assertEquals([10,7,10],array_column($logs,'new_stock'));
        $this->assertSame(3,$this->db->table('activity_logs')->countAllResults());
    }

    public function testBranchTrashControllerRequiresBranchAndAdministrator(): void
    {
        $this->prepareBranchTrash();
        foreach ([[],['branch_id'=>0],['branch_id'=>999],['branch_id'=>[1]]] as $input) {
            $this->controller(\App\Controllers\Products::class,$input)->delete(1);
            $this->assertNull($this->row('branch_products')['deleted_at']);
            $this->assertNull($this->row('products')['deleted_at']);
        }
        session()->set('role','cashier');
        $this->assertSame(403,$this->controller(\App\Controllers\Products::class,['branch_id'=>1])->delete(1)->getStatusCode());
        $this->assertSame(403,$this->controller(\App\Controllers\Products::class,['branch_id'=>1])->restore(1)->getStatusCode());
        session()->set('role','admin');
        $this->controller(\App\Controllers\Products::class,['branch_id'=>1])->delete(1);
        $this->controller(\App\Controllers\Products::class,[])->restore(1);
        $this->assertNotEmpty($this->row('branch_products')['deleted_at']);
        $this->controller(\App\Controllers\Products::class,[])->forceDelete(1);
        $this->assertNotNull($this->row('products'));
        $this->controller(\App\Controllers\Products::class,['branch_id'=>1])->restore(1);
        $this->assertNull($this->row('branch_products')['deleted_at']);
        $this->assertSame(2,$this->db->table('activity_logs')->countAllResults());
    }

    public function testBranchTrashRollbackPreservesInventoryIfAuditFails(): void
    {
        $service=$this->prepareBranchTrash();
        $this->db->query("CREATE TRIGGER reject_trash_audit BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'audit failure'); END");
        try {$service->change(1,1,true,'admin',1);$this->fail('Audit failure ignored');}
        catch (\Throwable $error) {$this->assertNotEmpty($error->getMessage());}
        $this->assertNull($this->row('branch_products')['deleted_at']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(17,(int)$this->row('products')['stock']);
    }

    public function testBranchTrashListsOnlySelectedBranchAndKeepsLegacyRestoreExplicit(): void
    {
        $service=$this->prepareBranchTrash();
        $service->change(1,1,true,'admin',1);
        $controller=$this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',[]);
        $html=$controller->index();
        $this->assertStringNotContainsString('<input type="hidden" name="branch_id" value="1">',$html);
        $this->assertStringContainsString('<input type="hidden" name="branch_id" value="2">',$html);
        $html=$this->controller(\App\Controllers\Products::class,[])->trash();
        $this->assertStringContainsString('Branch A',$html);
        $this->assertStringContainsString('<input type="hidden" name="branch_id" value="1">',$html);
        $this->assertStringNotContainsString('Delete Forever',$html);
        $controller=$this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',['branch_id'=>2]);
        $html=$controller->trash();
        $this->assertStringNotContainsString('name="branch_id" value="1"',$html);
        $this->db->table('products')->insert(['id'=>2,'product_name'=>'Legacy deletion','sku'=>'OLD','status'=>'inactive','deleted_at'=>'2026-01-01']);
        $controller=$this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',[]);
        $this->assertStringContainsString('Restore Shared Product',$controller->trash());
        $this->controller(\App\Controllers\Products::class,[])->restore(2);
        $this->assertNotEmpty($this->db->table('products')->where('id',2)->get()->getRowArray()['deleted_at']);
        $this->controller(\App\Controllers\Products::class,['legacy_restore'=>'1'])->restore(2);
        $this->assertNull($this->db->table('products')->where('id',2)->get()->getRowArray()['deleted_at']);
        $this->assertNotEmpty($this->row('branch_products')['deleted_at']);
    }

    public function testBranchTrashIsExcludedFromPosExpiryDashboardAndReplacementSelection(): void
    {
        $service=$this->prepareBranchTrash();
        $service->change(1,1,true,'admin',1);
        $controller=$this->controller(\App\Controllers\Cashier\SalesController::class,[]);
        service('request')->setGlobal('get',[]);
        $this->assertStringNotContainsString('Test Item',$controller->searchProducts());
        session()->set('branch_id',2);
        $controller=$this->controller(\App\Controllers\Cashier\SalesController::class,[]);
        service('request')->setGlobal('get',[]);
        $this->assertStringContainsString('Test Item',$controller->searchProducts());
        $reports=$this->controller(\App\Controllers\Admin\ReportsController::class,[]);
        $builder=new ReflectionMethod($reports,'expiryProductsBuilder');
        $this->assertCount(1,$builder->invoke($reports,'','all',date('Y-m-d'),date('Y-m-d',strtotime('+30 days')))->get()->getResultArray());
        $dashboard=$this->controller(\App\Controllers\Dashboard::class,[]);
        $builder=new ReflectionMethod($dashboard,'activeInventoryBuilder');
        $this->assertSame(1,$builder->invoke($dashboard)->countAllResults());
        $exchange=new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput();
        $this->db->table('branch_products')->where('id',100)->update(['deleted_at'=>date('Y-m-d H:i:s')]);
        try {$exchange->quote(1,$input,1,'admin');$this->fail('Trashed replacement accepted');}
        catch (InvalidArgumentException $error) {$this->assertStringContainsString('unavailable',$error->getMessage());}
        $expiry=new \App\Libraries\ExpiryStockResolution($this->db);
        $row=$expiry->inventory(1,'admin',0);
        try {$expiry->resolve(1,'remove',['outcome'=>'disposed','quantity'=>1,'reason'=>'Test removal','confirmed'=>'1'],'admin',0,1,str_repeat('a',48),$expiry::revision($row));$this->fail('Trashed stock changed');}
        catch (InvalidArgumentException $error) {$this->assertStringContainsString('active',$error->getMessage());}
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
    }

    public function testBranchTrashIsRecheckedAtCheckoutLock(): void
    {
        $this->prepareBranchTrash();
        $token=str_repeat('a',32);
        session()->set(['cart'=>[1=>['product_id'=>1,'quantity'=>1]],'checkout_token'=>$token]);
        $this->db->beforeInventoryLock=static function($db){$db->table('branch_products')->where('id',1)->update(['deleted_at'=>'2026-10-03 10:00:00']);};
        $this->controller(\App\Controllers\Cashier\SalesController::class,['_checkout_token'=>$token,'items'=>[1=>1],'payment_method'=>'cash','amount_paid'=>'10'])->checkout();
        $this->assertSame(1,$this->db->table('sales')->countAllResults());
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertNotEmpty(session('cart'));
    }

    public function testBranchTrashMigrationCanRunTwiceWithoutChangingInventory(): void
    {
        $this->db->query('ALTER TABLE branch_products DROP COLUMN deleted_at');
        $this->db->resetDataCache();
        $before=$this->row('branch_products') + ['deleted_at'=>null];
        require_once APPPATH . 'Database/Migrations/2026_10_03_000006_add_branch_product_trash.php';
        $migration=new \App\Database\Migrations\AddBranchProductTrash(new \CodeIgniter\Database\SQLite3\Forge($this->db));
        $migration->up();
        $migration->up();
        $this->assertTrue($this->db->fieldExists('deleted_at','branch_products'));
        $this->assertSame($before,$this->row('branch_products'));
        $service=new \App\Libraries\BranchProductTrash($this->db);
        $service->change(1,1,true,'admin',1);
        $when=$this->row('branch_products')['deleted_at'];
        $migration->up();
        $this->assertSame($when,$this->row('branch_products')['deleted_at']);
    }

    private function catalogInput(array $replace = []): array
    {
        return array_replace(['product_id'=>'1','stock'=>'6','reorder_level'=>'3','price'=>'12.50','cost_price'=>'5.00','expiration_date'=>'2099-02-01'], $replace);
    }

    private function prepareCatalogBranch(): void
    {
        $this->db->query('ALTER TABLE branch_products ADD COLUMN created_at TEXT');
        $this->db->table('branches')->insert(['id'=>2,'branch_name'=>'New Branch','status'=>'active']);
        session()->set(['role'=>'cashier','branch_id'=>2]);
    }

    public function testCatalogAssignmentReusesMasterAndKeepsBranchesSeparate(): void
    {
        $this->prepareCatalogBranch();
        $master = $this->row('products');
        $original = $this->row('branch_products');
        $input = $this->catalogInput(['branch_id'=>1,'product_name'=>'Forged shared name','status'=>'inactive']);
        $this->controller(\App\Controllers\Products::class,$input)->addExisting();
        $assigned = $this->db->table('branch_products')->where('branch_id',2)->get()->getRowArray();
        $this->assertNotNull($assigned);
        $this->assertSame(6,(int)$assigned['stock']);
        $this->assertSame(12.5,(float)$assigned['price']);
        $this->assertSame('2099-02-01',$assigned['expiration_date']);
        $this->assertSame($original,$this->row('branch_products'));
        $this->assertSame($master['product_name'],$this->row('products')['product_name']);
        $this->assertSame(16,(int)$this->row('products')['stock']);
        $this->assertSame(1,$this->db->table('products')->countAllResults());
        $log = $this->row('stock_logs');
        $this->assertSame(2,(int)$log['branch_id']);
        $this->assertSame([0,6,6],array_map('intval',[$log['previous_stock'],$log['new_stock'],$log['quantity']]));
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
        $this->controller(\App\Controllers\Products::class,$input)->addExisting();
        $this->assertSame(2,$this->db->table('branch_products')->countAllResults());
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
        $this->assertStringContainsString('already assigned',session()->getFlashdata('error'));
    }

    public function testCatalogAssignmentAcceptsNoSkuAndZeroStock(): void
    {
        $this->prepareCatalogBranch();
        $this->db->table('products')->where('id',1)->update(['sku'=>null]);
        $this->controller(\App\Controllers\Products::class,$this->catalogInput(['stock'=>'0','expiration_date'=>'']))->addExisting();
        $assigned = $this->db->table('branch_products')->where('branch_id',2)->get()->getRowArray();
        $this->assertSame(0,(int)$assigned['stock']);
        $this->assertNull($assigned['expiration_date']);
        $this->assertSame(10,(int)$this->row('products')['stock']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
    }

    public function testCatalogAssignmentRejectsInvalidInputAndUnavailableRecords(): void
    {
        $this->prepareCatalogBranch();
        foreach ([['stock'=>'-1'],['stock'=>'1.5'],['stock'=>'1000001'],['price'=>'1e3'],['price'=>'1.001'],['price'=>'999999999'],['price'=>'4'],['expiration_date'=>'2020-01-01'],['expiration_date'=>'2099-02-30'],['product_id'=>'999']] as $change) {
            $this->controller(\App\Controllers\Products::class,$this->catalogInput($change))->addExisting();
            $this->assertSame(1,$this->db->table('branch_products')->countAllResults(),json_encode($change));
        }
        foreach ([['status'=>'inactive'],['status'=>'active','deleted_at'=>'2026-01-01']] as $change) {
            $this->db->table('products')->where('id',1)->update($change);
            $this->controller(\App\Controllers\Products::class,$this->catalogInput())->addExisting();
            $this->assertSame(1,$this->db->table('branch_products')->countAllResults());
        }
        $this->db->table('products')->where('id',1)->update(['status'=>'active','deleted_at'=>null]);
        $this->db->table('branches')->where('id',2)->update(['status'=>'inactive']);
        $this->controller(\App\Controllers\Products::class,$this->catalogInput())->addExisting();
        $this->assertSame(1,$this->db->table('branch_products')->countAllResults());
        session()->set('branch_id',0);
        $this->controller(\App\Controllers\Products::class,$this->catalogInput())->addExisting();
        $this->assertSame(1,$this->db->table('branch_products')->countAllResults());
        session()->set('role','admin');
        $this->assertSame(403,$this->controller(\App\Controllers\Products::class,$this->catalogInput())->addExisting()->getStatusCode());
    }

    public function testCatalogAssignmentRollsBackWhenAuditFails(): void
    {
        $this->prepareCatalogBranch();
        $this->db->query("CREATE TRIGGER reject_catalog_log BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'simulated audit failure'); END");
        $this->controller(\App\Controllers\Products::class,$this->catalogInput())->addExisting();
        $this->assertSame(1,$this->db->table('branch_products')->countAllResults());
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(10,(int)$this->row('products')['stock']);
    }

    public function testCatalogBrowseSearchAndSelectionExcludeAssignedInactiveAndDeletedProducts(): void
    {
        $this->prepareCatalogBranch();
        $this->db->query('CREATE TABLE categories (id INTEGER PRIMARY KEY, category_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        $this->db->query('CREATE TABLE suppliers (id INTEGER PRIMARY KEY, supplier_name TEXT, deleted_at TEXT, is_deleted INTEGER NOT NULL DEFAULT 0, permanently_deleted_at TEXT, is_permanently_deleted INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        foreach ([['id'=>2,'product_name'=>'Hidden inactive','status'=>'inactive'],['id'=>3,'product_name'=>'Hidden deleted','status'=>'active','deleted_at'=>'2026-01-01'],['id'=>4,'product_name'=>'Already assigned','status'=>'active'],['id'=>5,'product_name'=>'Another catalog item','sku'=>'FIND-ME','status'=>'active']] as $product) {
            $this->db->table('products')->insert($product + ['unit'=>'box']);
        }
        $this->db->table('branch_products')->insert(['product_id'=>4,'branch_id'=>2,'stock'=>0,'status'=>'inactive']);
        $controller = $this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',[]);
        $html = $controller->catalog();
        $this->assertStringContainsString('Test Item',$html);
        $this->assertStringContainsString('Another catalog item',$html);
        foreach (['Hidden inactive','Hidden deleted','Already assigned'] as $name) {
            $this->assertStringNotContainsString($name,$html);
        }
        $controller = $this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',['keyword'=>'FIND-ME']);
        $html = $controller->catalog();
        $this->assertStringContainsString('Another catalog item',$html);
        $this->assertStringNotContainsString('Test Item',$html);
        $controller = $this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',['product_id'=>4]);
        $this->assertSame(302,$controller->catalog()->getStatusCode());
        $controller = $this->controller(\App\Controllers\Products::class,[]);
        service('request')->setGlobal('get',['product_id'=>1]);
        $html = $controller->catalog();
        $this->assertStringContainsString('Add to My Branch',$html);
        $this->assertStringContainsString('name="stock" type="number" min="0" max="1000000" step="1" value="0"',$html);
    }

    private function seedExchangeReplacement(int $id = 100, float $price = 10): void
    {
        if ($this->db->table('products')->where('id', $id)->countAllResults()) return;
        $this->db->table('products')->insert(['id'=>$id,'product_name'=>'Replacement '.$id,'sku'=>'REPLACE-'.$id,'category_id'=>1,'stock'=>10,'status'=>'active']);
        $this->db->table('branch_products')->insert(['id'=>$id,'product_id'=>$id,'branch_id'=>1,'stock'=>10,'price'=>$price,'cost_price'=>4,'status'=>'active']);
    }

    private function exchangeInput(array $replace = []): array
    {
        $this->seedExchangeReplacement();
        return array_replace(['returns' => [1 => 2], 'conditions' => [1 => 'resellable'], 'replacements' => [100 => 3], 'reason' => 'Customer requested a different product', 'settlement_method' => 'cash', 'reference' => '', 'discount_id' => ''], $replace);
    }

    private function expiryService(): \App\Libraries\ExpiryStockResolution
    {
        $this->db->table('branch_products')->where('id',1)->update(['expiration_date'=>'2020-01-01']);
        return new \App\Libraries\ExpiryStockResolution($this->db);
    }

    public function testExpiryReplacementPreservesOldExpiryAndRecordsBothMovementsOnce(): void
    {
        $service = $this->expiryService();
        $this->db->table('branches')->insert(['id'=>2,'branch_name'=>'Other branch','status'=>'active']);
        $this->db->table('branch_products')->insert(['id'=>2,'branch_id'=>2,'product_id'=>1,'stock'=>7,'status'=>'active','expiration_date'=>'2030-01-01']);
        $row = $service->inventory(1,'cashier',1);
        $input = ['outcome'=>'supplier_return','new_quantity'=>20,'new_expiry'=>'2099-01-01','reason'=>'Supplier replacement delivery','confirmed'=>'1'];
        $revision = $service::revision($row);
        $this->assertTrue($service->resolve(1,'replace',$input,'cashier',1,1,str_repeat('a',48),$revision));
        $this->assertFalse($service->resolve(1,'replace',$input,'cashier',1,1,str_repeat('a',48),$revision));
        $this->assertSame(20,(int)$this->row('branch_products')['stock']);
        $this->assertSame('2099-01-01',$this->row('branch_products')['expiration_date']);
        $this->assertSame(27,(int)$this->row('products')['stock']);
        $logs = $this->db->table('stock_logs')->orderBy('id')->get()->getResultArray();
        $this->assertCount(2,$logs);
        $this->assertSame(['stock_out','stock_in'],array_column($logs,'action_type'));
        $this->assertEquals([10,20],array_column($logs,'quantity'));
        $this->assertEquals([10,0],array_column($logs,'previous_stock'));
        $this->assertEquals([0,20],array_column($logs,'new_stock'));
        $this->assertStringContainsString('2020-01-01',$logs[0]['remarks']);
        $this->assertStringContainsString('Returned to supplier',$logs[0]['remarks']);
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
        $other = $this->db->table('branch_products')->where('id',2)->get()->getRowArray();
        $this->assertSame(7,(int)$other['stock']);
        $this->assertSame('2030-01-01',$other['expiration_date']);
    }

    public function testExpiryDisposalCanBePartialAndKeepsExpiry(): void
    {
        $service = $this->expiryService();
        $row = $service->inventory(1,'cashier',1);
        $service->resolve(1,'remove',['outcome'=>'disposed','quantity'=>3,'reason'=>'Expired units removed','confirmed'=>'1'],'cashier',1,1,str_repeat('b',48),$service::revision($row));
        $this->assertSame(7,(int)$this->row('branch_products')['stock']);
        $this->assertSame(7,(int)$this->row('products')['stock']);
        $this->assertSame('2020-01-01',$this->row('branch_products')['expiration_date']);
        $this->assertStringContainsString('Disposed',$this->row('stock_logs')['remarks']);
        $this->assertSame('Test Staff',$service->history($row)[0]['full_name']);
    }

    public function testExpiryDeactivationIsAdminOnlyAndBranchSpecific(): void
    {
        $service = $this->expiryService();
        $this->db->table('branch_products')->insert(['id'=>2,'branch_id'=>2,'product_id'=>1,'stock'=>7,'status'=>'active']);
        $row = $service->inventory(1,'admin',0);
        $input = ['reason'=>'Stop sales pending inspection','confirmed'=>'1'];
        try { $service->resolve(1,'deactivate',$input,'cashier',1,1,str_repeat('c',48),$service::revision($row)); $this->fail('Cashier deactivated inventory'); }
        catch (DomainException $e) { $this->assertStringContainsString('administrator',$e->getMessage()); }
        $this->assertSame('active',$this->row('branch_products')['status']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $service->resolve(1,'deactivate',$input,'admin',0,1,str_repeat('c',48),$service::revision($row));
        $this->assertSame('inactive',$this->row('branch_products')['status']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame('active',$this->row('products')['status']);
        $this->assertNull($this->row('products')['deleted_at']);
        $this->assertSame('active',$this->db->table('branch_products')->where('id',2)->get()->getRowArray()['status']);
        $this->assertSame(0,(int)$this->row('stock_logs')['quantity']);
        $this->assertCount(1,$service->history($service->inventory(1,'admin',0)));
    }

    public function testExpiryRejectsOtherBranchesInvalidCountsDatesAndUnconfirmedRequests(): void
    {
        $service = $this->expiryService();
        try { $service->inventory(1,'cashier',2); $this->fail('Cross-branch read allowed'); }
        catch (DomainException $e) { $this->assertStringContainsString('branch',$e->getMessage()); }
        $revision = $service::revision($service->inventory(1,'cashier',1));
        $base = ['outcome'=>'disposed','quantity'=>1,'new_quantity'=>20,'new_expiry'=>'2099-01-01','reason'=>'Expiry stock action','confirmed'=>'1'];
        foreach ([['remove',['quantity'=>11]],['remove',['quantity'=>'1.5']],['remove',['quantity'=>-1]],['replace',['new_quantity'=>0]],['replace',['new_expiry'=>'2000-01-01']],['replace',['new_expiry'=>'2099-02-30']],['replace',['new_expiry'=>date('Y-m-d')]],['replace',['outcome'=>'other']],['remove',['confirmed'=>'']],['deactivate',['reason'=>'']]] as [$action,$change]) {
            try { $service->resolve(1,$action,array_replace($base,$change),'admin',1,1,str_repeat('d',48),$revision); $this->fail('Invalid resolution accepted'); }
            catch (InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
    }

    public function testExpiryRejectsStaleStockAndRollsBackFailedLog(): void
    {
        $service = $this->expiryService();
        $input = ['outcome'=>'disposed','quantity'=>3,'reason'=>'Expired stock removal','confirmed'=>'1'];
        $revision = $service::revision($service->inventory(1,'cashier',1));
        $this->db->beforeInventoryLock = static function ($db) { $db->table('branch_products')->where('id',1)->update(['stock'=>9]); };
        try { $service->resolve(1,'remove',$input,'cashier',1,1,str_repeat('e',48),$revision); $this->fail('Stale stock accepted'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('changed',$e->getMessage()); }
        $this->db->query("CREATE TRIGGER reject_expiry_log BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'simulated write failure'); END");
        try { $service->resolve(1,'remove',$input,'cashier',1,1,str_repeat('e',48),$revision); $this->fail('Failed log ignored'); }
        catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(10,(int)$this->row('products')['stock']);
    }

    public function testExpiryControllerDeniesCashierDeactivationEvenWithForgedPost(): void
    {
        $this->expiryService();
        session()->set('role','cashier');
        $response = $this->controller(\App\Controllers\ExpiryStockController::class,['resolution_action'=>'deactivate','reason'=>'Forged request','confirmed'=>'1'])->save(1);
        $this->assertSame(403,$response->getStatusCode());
        $this->assertSame('active',$this->row('branch_products')['status']);
        $this->assertSame(0,$this->db->table('activity_logs')->countAllResults());
        $response = $this->controller(\App\Controllers\ExpiryStockController::class,['resolution_action'=>'remove','resolution_token'=>'invalid'])->save(1);
        $this->assertStringContainsString('action=remove',$response->getHeaderLine('Location'));
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
    }

    public function testExpiryDeactivationIsRecheckedDuringCheckout(): void
    {
        $token = str_repeat('a',32);
        session()->set(['cart'=>[1=>['product_id'=>1,'quantity'=>1]],'checkout_token'=>$token]);
        $this->db->beforeInventoryLock = static function ($db) { $db->table('branch_products')->where('id',1)->update(['status'=>'inactive']); };
        $this->controller(\App\Controllers\Cashier\SalesController::class,['_checkout_token'=>$token,'items'=>[1=>1],'payment_method'=>'cash','amount_paid'=>'10'])->checkout();
        $this->assertSame(1,$this->db->table('sales')->countAllResults());
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertStringContainsString('deactivated',session()->getFlashdata('error'));
    }

    public function testExpiryControllerSavesValidRemovalAndHistorySurvivesTrash(): void
    {
        helper(['url','form']);
        $service = $this->expiryService();
        $row = $service->inventory(1,'admin',0);
        $token = str_repeat('f',48);
        session()->set(['expiry_action_1_remove'=>['token'=>$token,'revision'=>$service::revision($row)],'branch_id'=>null]);
        $post = ['resolution_action'=>'remove','resolution_token'=>$token,'outcome'=>'supplier_return','quantity'=>10,'reason'=>'Return expired delivery','confirmed'=>'1'];
        $response = $this->controller(\App\Controllers\ExpiryStockController::class,$post)->save(1);
        $this->assertStringContainsString('/expiry-report/item/1',$response->getHeaderLine('Location'));
        $this->assertSame(0,(int)$this->row('branch_products')['stock']);
        $this->controller(\App\Controllers\ExpiryStockController::class,$post)->save(1);
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
        $this->db->table('products')->where('id',1)->update(['deleted_at'=>'2026-10-01 10:00:00','status'=>'inactive']);
        $html = $this->controller(\App\Controllers\ExpiryStockController::class,[])->show(1);
        $this->assertStringContainsString('Return expired delivery',$html);
        $this->assertStringNotContainsString('?action=replace',$html);
        $this->assertStringNotContainsString('[ER:',$html);
    }

    public function testExpiryLongReasonsFitExistingLogAndInactiveInventoryCannotChange(): void
    {
        $service = $this->expiryService();
        $row = $service->inventory(1,'admin',0);
        $input = ['outcome'=>'supplier_return','new_quantity'=>1000000,'new_expiry'=>'2099-12-31','reason'=>str_repeat('x',100),'confirmed'=>'1'];
        $service->resolve(1,'replace',$input,'admin',0,1,str_repeat('1',48),$service::revision($row));
        foreach ($service->history($row) as $log) $this->assertLessThanOrEqual(255,mb_strlen($log['remarks']));
        $this->db->table('branch_products')->where('id',1)->update(['status'=>'inactive']);
        $row = $service->inventory(1,'admin',0);
        try { $service->resolve(1,'replace',$input,'admin',0,1,str_repeat('2',48),$service::revision($row)); $this->fail('Inactive stock changed'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('active',$e->getMessage()); }
        $this->assertCount(2,$service->history($row));
    }

    public function testExchangeCommitsLinkedRecordsAndRepeatedSubmitDoesNotDuplicate(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput();
        $quote = $service->quote(1, $input, 1, 'cashier');
        $this->assertSame(1000, $quote['difference']);
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
        $id = $service->complete(1, $input, 1, 'cashier', 1, str_repeat('a', 48), $quote['revision']);
        $this->assertSame($id, $service->complete(1, $input, 1, 'cashier', 1, str_repeat('a', 48), $quote['revision']));
        $this->assertSame(2, $this->db->table('sales')->countAllResults());
        $this->assertSame(1, $this->db->table('refund_items')->countAllResults());
        $this->assertSame(12, (int) $this->row('branch_products')['stock']);
        $this->assertSame(12, (int) $this->row('products')['stock']);
        $this->assertSame(7, (int) $this->db->table('branch_products')->where('id',100)->get()->getRowArray()['stock']);
        $this->assertSame(7, (int) $this->db->table('products')->where('id',100)->get()->getRowArray()['stock']);
        $this->assertSame('partially_refunded', $this->row('sales')['status']);
        $newSale = $this->db->table('sales')->where('id', $id)->get()->getRowArray();
        $link = \App\Libraries\ExchangeRecord::forSale($newSale, $this->db);
        $this->assertEquals(20, $link['credit']);
        $this->assertEquals(10, $link['due']);
        $this->assertSame('TEST-1', $link['original_invoice']);
        $movement = (new CashMovementReport($this->db))->generate(date('Y-m-d'), date('Y-m-d'));
        $this->assertEquals(10, $movement['totals']['receipts']);
        $this->assertEquals(0, $movement['totals']['refunds']);
        $this->assertEquals(10, $movement['totals']['cash_net']);
    }

    private function seedThreeProductExchangeDiscount(string $type = 'percentage', ?float $cap = null): array
    {
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Original order discount','discount_type'=>$type,'discount_value'=>$type === 'fixed' ? 53 : 20,'applies_to'=>'all','minimum_purchase'=>200,'max_discount_amount'=>$cap,'status'=>'active']);
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1,'total_amount'=>265,'discount_amount'=>53,'final_total'=>212,'amount_paid'=>212,'change_amount'=>0]);
        $this->db->table('sale_items')->where('id',1)->update(['quantity'=>1,'price'=>100,'subtotal'=>100,'discount_applied'=>20]);
        foreach ([2=>110,3=>55] as $id=>$price) {
            $this->db->table('products')->insert(['id'=>$id,'product_name'=>'Product '.$id,'sku'=>'EX-'.$id,'category_id'=>1,'stock'=>10,'status'=>'active']);
            $this->db->table('branch_products')->insert(['id'=>$id,'product_id'=>$id,'branch_id'=>1,'stock'=>10,'price'=>$price,'cost_price'=>10,'status'=>'active']);
            $this->db->table('sale_items')->insert(['id'=>$id,'sale_id'=>1,'product_id'=>$id,'product_name_snapshot'=>'Product '.$id,'quantity'=>1,'price'=>$price,'cost_price_at_sale'=>10,'subtotal'=>$price,'discount_applied'=>$price * 0.2,'profit'=>0]);
        }
        $this->seedExchangeReplacement(100,55);
        $this->seedExchangeReplacement(101,55);
        $this->seedExchangeReplacement(102,100);
        return $this->exchangeInput(['returns'=>[3=>1],'conditions'=>[3=>'quarantined'],'replacements'=>[100=>1],'discount_id'=>'1']);
    }

    public function testExchangeOriginalMinimumIncludesKeptProductsAndRepeatedExchange(): void
    {
        $input = $this->seedThreeProductExchangeDiscount();
        $service = new \App\Libraries\ProductExchange($this->db);
        $q = $service->quote(1,$input,1,'cashier');
        $this->assertSame(21000,$q['discountContext']['eligible_cents']);
        $this->assertSame(1100,$q['discount']);
        $this->assertSame(4400,$q['total']);
        $this->assertSame(0,$q['difference']);
        $id = $service->complete(1,$input,1,'cashier',1,str_repeat('7',48),$q['revision']);
        $this->assertSame(20.0,(float)$this->row('sale_items')['discount_applied']);
        $this->assertSame(53.0,(float)$this->row('sales')['discount_amount']);
        $replacement = $this->db->table('sale_items')->where('sale_id',$id)->get()->getRowArray();
        $input['returns'] = [$replacement['id']=>1];
        $input['conditions'] = [$replacement['id']=>'quarantined'];
        $input['replacements'] = [101=>1];
        $next = $service->quote($id,$input,1,'cashier');
        $this->assertSame(1100,$next['discount']);
        $this->assertSame(0,$next['difference']);
    }

    public function testExchangeFixedDiscountUsesRemainingAllowanceIncludingSiblingReplacements(): void
    {
        $input = $this->seedThreeProductExchangeDiscount('fixed');
        $service = new \App\Libraries\ProductExchange($this->db);
        $q = $service->quote(1,$input,1,'cashier');
        $this->assertSame(1100,$q['discount']);
        $service->complete(1,$input,1,'cashier',1,str_repeat('8',48),$q['revision']);
        // Exchange another original item: the earlier replacement still consumes PHP 11.
        $second = $this->exchangeInput(['returns'=>[1=>1],'conditions'=>[1=>'quarantined'],'replacements'=>[102=>1],'discount_id'=>'1']);
        $q = $service->quote(1,$second,1,'cashier');
        $this->assertSame(3300,$q['discountContext']['discount_cents']);
        $this->assertSame(2000,$q['discount']);
        $this->assertSame(0,$q['difference']);
    }

    public function testExchangePercentageCapIsSharedWithUnchangedItems(): void
    {
        $input = $this->seedThreeProductExchangeDiscount('percentage',53);
        $this->db->table('branch_products')->where('id',100)->update(['price'=>100]);
        $q = (new \App\Libraries\ProductExchange($this->db))->quote(1,$input,1,'cashier');
        $this->assertSame(1100,$q['discount']);
        $this->assertSame(4500,$q['difference']);
    }

    public function testExchangeMinimumAndScopeFailuresExplainTheirDifferentReasons(): void
    {
        $input = $this->seedThreeProductExchangeDiscount();
        $service = new \App\Libraries\ProductExchange($this->db);
        $this->db->table('discounts')->where('id',1)->update(['minimum_purchase'=>300]);
        try { $service->quote(1,$input,1,'cashier'); $this->fail('Minimum bypassed'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('300.00',$e->getMessage()); $this->assertStringContainsString('265.00',$e->getMessage()); }
        $this->db->table('discounts')->where('id',1)->update(['minimum_purchase'=>0,'applies_to'=>'product','product_id'=>1]);
        try { $service->quote(1,$input,1,'cashier'); $this->fail('Wrong product discounted'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('product or category restrictions',$e->getMessage()); }
        $this->db->table('discounts')->insert(['id'=>2,'discount_name'=>'Different discount','discount_type'=>'percentage','discount_value'=>20,'applies_to'=>'all','minimum_purchase'=>200,'status'=>'active']);
        $input['discount_id'] = '2';
        try { $service->quote(1,$input,1,'cashier'); $this->fail('Unrelated discount used kept merchandise'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('55.00',$e->getMessage()); }
    }

    public function testExchangeOriginalDiscountPreselectsButPreservesExplicitNoDiscount(): void
    {
        helper(['url','form']);
        $this->seedThreeProductExchangeDiscount();
        session()->set('branch_id',null);
        session()->remove('exchange_1');
        $this->controller(\App\Controllers\Cashier\ExchangeController::class,[])->form(1);
        $this->assertSame('1',session('exchange_1')['input']['discount_id']);
        session()->set('exchange_1',['input'=>['discount_id'=>'']]);
        $this->controller(\App\Controllers\Cashier\ExchangeController::class,[])->form(1);
        $this->assertSame('',session('exchange_1')['input']['discount_id']);
    }

    public function testExchangeCheaperReplacementPaysOnlyDifferenceAndQuarantineNeverRestocks(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput(['returns' => [1 => 5], 'conditions' => [1 => 'quarantined'], 'replacements' => [100 => 1], 'settlement_method' => 'gcash', 'reference' => 'TEST-REFERENCE']);
        $q = $service->quote(1, $input, 1, 'cashier');
        $this->assertSame(-4000, $q['difference']);
        $id = $service->complete(1, $input, 1, 'cashier', 1, str_repeat('b', 48), $q['revision']);
        $this->assertSame('refunded', $this->row('sales')['status']);
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
        $this->assertSame(9, (int) $this->db->table('branch_products')->where('id',100)->get()->getRowArray()['stock']);
        $this->assertSame($id, $service->complete(1, $input, 1, 'cashier', 1, str_repeat('b', 48), $q['revision']));
        $movement = (new CashMovementReport($this->db))->generate(date('Y-m-d'), date('Y-m-d'));
        $this->assertEquals(0, $movement['totals']['receipts']);
        $this->assertEquals(40, $movement['totals']['refunds']);
        $this->assertEquals(-40, $movement['totals']['net']);
        $this->assertEquals(0, $movement['totals']['cash_net']);
        session()->set('refund_token_' . $id, 'return-replacement');
        $this->controller(\App\Controllers\Cashier\SalesController::class, ['_refund_token'=>'return-replacement','reason'=>'Return replacement','refund_method'=>'cash','refund'=>[2=>1],'return_condition'=>[2=>'damaged']])->refundPartial($id);
        $movement = (new CashMovementReport($this->db))->generate(date('Y-m-d'), date('Y-m-d'));
        $this->assertEquals(50, $movement['totals']['refunds']);
        $this->assertEquals(-10, $movement['totals']['cash_net']);
    }

    public function testExchangeEvenSwapHasNoCashMovement(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput(['replacements' => [100 => 2]]);
        $q = $service->quote(1, $input, 1, 'cashier');
        $service->complete(1, $input, 1, 'cashier', 1, str_repeat('c', 48), $q['revision']);
        $movement = (new CashMovementReport($this->db))->generate(date('Y-m-d'), date('Y-m-d'));
        $this->assertEquals(0, $movement['totals']['receipts']);
        $this->assertEquals(0, $movement['totals']['refunds']);
        $this->assertSame(12, (int) $this->row('branch_products')['stock']);
        $this->assertSame(8, (int) $this->db->table('branch_products')->where('id',100)->get()->getRowArray()['stock']);
    }

    public function testExchangeKeepsDiscountedReturnCentavosAndAppliesReplacementDiscount(): void
    {
        $this->db->table('sale_items')->where('id', 1)->update(['quantity'=>3,'subtotal'=>10,'discount_applied'=>0.01]);
        $this->db->table('refund_items')->insert(['sale_id'=>1,'sale_item_id'=>1,'quantity_refunded'=>1,'refund_subtotal'=>3.33]);
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Half off','discount_type'=>'percentage','discount_value'=>50,'applies_to'=>'all','minimum_purchase'=>0,'status'=>'active']);
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput(['discount_id'=>'1','replacements'=>[100=>1]]);
        $q = $service->quote(1, $input, 1, 'cashier');
        $this->assertSame(666, $q['credit']);
        $this->assertSame(500, $q['total']);
        $this->assertSame(-166, $q['difference']);
        $service->complete(1, $input, 1, 'cashier', 1, str_repeat('d', 48), $q['revision']);
        $sum = $this->db->table('refund_items')->selectSum('refund_subtotal','amount')->get()->getRowArray();
        $this->assertEquals(9.99, $sum['amount']);
        $this->assertSame('refunded', $this->row('sales')['status']);
    }

    public function testExchangeRejectsStalePricesAndRollsBackAllWritesOnFailure(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput();
        $q = $service->quote(1, $input, 1, 'cashier');
        $this->db->table('branch_products')->where('id',100)->update(['price'=>11]);
        try { $service->complete(1, $input, 1, 'cashier', 1, str_repeat('e',48), $q['revision']); $this->fail('Stale price accepted'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('changed', $e->getMessage()); }
        $this->assertSame(1, $this->db->table('sales')->countAllResults());
        $q = $service->quote(1, $input, 1, 'cashier');
        $this->db->query("CREATE TRIGGER reject_exchange_log BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'simulated write failure'); END");
        try { $service->complete(1, $input, 1, 'cashier', 1, str_repeat('e',48), $q['revision']); $this->fail('Write failure ignored'); }
        catch (\RuntimeException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->assertSame(1, $this->db->table('sales')->countAllResults());
        $this->assertSame(0, $this->db->table('refund_items')->countAllResults());
        $this->assertSame(0, $this->db->table('stock_logs')->countAllResults());
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
        $this->assertSame('completed', $this->row('sales')['status']);
    }

    public function testExchangeRejectsInvalidQuantitiesProductsAndSettlement(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        foreach ([['returns'=>[1=>6]], ['returns'=>[999=>1]], ['returns'=>[1=>'1.5']], ['returns'=>[]], ['replacements'=>[100=>11]], ['replacements'=>[999=>1]], ['replacements'=>[]], ['conditions'=>[1=>'bogus']], ['settlement_method'=>'gcash'], ['discount_id'=>999]] as $override) {
            try { $service->quote(1, $this->exchangeInput($override), 1, 'cashier'); $this->fail('Invalid input accepted'); }
            catch (InvalidArgumentException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertSame(0, $this->db->table('refund_items')->countAllResults());
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
    }

    public function testExchangeRejectsOtherBranchesInactiveAndExpiredInventory(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        try { $service->quote(1, $this->exchangeInput(), 2, 'cashier'); $this->fail('Cross-branch access allowed'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('branch', $e->getMessage()); }
        $this->db->table('branches')->where('id',1)->update(['status'=>'inactive']);
        try { $service->quote(1, $this->exchangeInput(), 1, 'admin'); $this->fail('Inactive branch allowed'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('active', $e->getMessage()); }
        $this->db->table('branches')->where('id',1)->update(['status'=>'active']);
        $this->db->table('branch_products')->where('id',1)->update(['expiration_date'=>'2000-01-01']);
        try { $service->quote(1, $this->exchangeInput(), 1, 'cashier'); $this->fail('Expired stock restocked'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('Expired', $e->getMessage()); }
        $this->db->table('branch_products')->where('id',100)->update(['expiration_date'=>'2000-01-01']);
        try { $service->quote(1, $this->exchangeInput(['conditions'=>[1=>'quarantined']]), 1, 'cashier'); $this->fail('Expired replacement sold'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('expired', $e->getMessage()); }
    }

    public function testExchangeRechecksReturnsAfterLockAndRequiresReviewToken(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $q = $service->quote(1, $this->exchangeInput(), 1, 'cashier');
        $this->db->beforeSaleLock = static function ($db) { $db->table('refund_items')->insert(['sale_id'=>1,'sale_item_id'=>1,'quantity_refunded'=>4,'refund_subtotal'=>40]); };
        try { $service->complete(1, $this->exchangeInput(), 1, 'cashier', 1, str_repeat('f',48), $q['revision']); $this->fail('Concurrent return ignored'); }
        catch (InvalidArgumentException $e) { $this->assertStringContainsString('quantity', $e->getMessage()); }
        $this->controller(\App\Controllers\Cashier\ExchangeController::class, ['exchange_token'=>'invalid'])->complete(1);
        $this->assertSame(1, $this->db->table('sales')->countAllResults());
    }

    public function testExchangeFormExcludesEveryOriginalProductIncludingKeptAndPreviouslyReturnedItems(): void
    {
        helper(['url','form']);
        $this->seedThreeProductExchangeDiscount();
        $this->db->table('refund_items')->insert(['sale_id'=>1,'sale_item_id'=>2,'quantity_refunded'=>1,'refund_subtotal'=>88]);
        $this->db->table('sales')->where('id',1)->update(['status'=>'partially_refunded']);
        foreach (['admin','cashier'] as $role) {
            session()->set(['role'=>$role,'branch_id'=>1,'exchange_1'=>['input'=>['returns'=>[1=>0,3=>1],'replacements'=>[2=>1,100=>1]]]]);
            $html = $this->controller(\App\Controllers\Cashier\ExchangeController::class,[])->form(1);
            $this->assertStringContainsString('name="returns[1]"',$html);
            $this->assertStringContainsString('name="returns[3]"',$html);
            $this->assertStringNotContainsString('name="returns[2]"',$html);
            foreach ([1,2,3] as $id) $this->assertStringNotContainsString('name="replacements['.$id.']"',$html);
            foreach ([100,101,102] as $id) $this->assertStringContainsString('name="replacements['.$id.']"',$html);
        }
    }

    public function testExchangeFormExplainsWhenNoDifferentProductsAreAvailable(): void
    {
        helper(['url','form']);
        session()->remove('exchange_1');
        $html = $this->controller(\App\Controllers\Cashier\ExchangeController::class,[])->form(1);
        $this->assertStringContainsString('No different replacement products are available in this branch.',$html);
        $this->assertStringContainsString('name="returns[1]"',$html);
        $this->assertStringNotContainsString('name="replacements[',$html);
    }

    public function testExchangeReviewRejectsOriginalProductsEvenIfNotSelectedForReturn(): void
    {
        $input = $this->seedThreeProductExchangeDiscount();
        $this->db->table('refund_items')->insert(['sale_id'=>1,'sale_item_id'=>2,'quantity_refunded'=>1,'refund_subtotal'=>88]);
        $this->db->table('sales')->where('id',1)->update(['status'=>'partially_refunded']);
        $service = new \App\Libraries\ProductExchange($this->db);
        foreach (['admin','cashier'] as $role) foreach ([[1=>1],[2=>1],[3=>1],[100=>1,1=>1]] as $replacements) {
            $input['replacements'] = $replacements;
            try { $service->quote(1,$input,1,$role); $this->fail('An original product was accepted as a replacement'); }
            catch (InvalidArgumentException $error) { $this->assertStringContainsString('different replacement product',$error->getMessage()); }
        }
        $this->assertSame(1,$this->db->table('sales')->countAllResults());
        $this->assertSame(1,$this->db->table('refund_items')->countAllResults());
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
    }

    public function testExchangeCompleteRechecksDifferentProductPolicyAndLeavesRecordsUnchanged(): void
    {
        $service = new \App\Libraries\ProductExchange($this->db);
        $input = $this->exchangeInput();
        $tables = ['sales','sale_items','products','branch_products','refund_items','stock_logs','activity_logs'];
        $before = [];
        foreach ($tables as $table) $before[$table] = $this->db->table($table)->orderBy('id')->get()->getResultArray();
        foreach (['admin','cashier'] as $role) {
            $quote = $service->quote(1,$input,1,$role);
            $forged = array_replace($input,['replacements'=>[100=>1,1=>1]]);
            try { $service->complete(1,$forged,1,$role,1,str_repeat('a',48),$quote['revision']); $this->fail('Original replacement bypassed final validation'); }
            catch (InvalidArgumentException $error) { $this->assertStringContainsString('different replacement product',$error->getMessage()); }
            foreach ($tables as $table) $this->assertSame($before[$table],$this->db->table($table)->orderBy('id')->get()->getResultArray(),$table.' changed after rejected exchange');
        }
    }

    public function testExchangeControllerReviewsDifferentProductThenCommitsAndRendersBothReceipts(): void
    {
        helper(['url', 'form']);
        $this->db->table('products')->insert(['id'=>2,'product_name'=>'Replacement Tablet','sku'=>'NEW','category_id'=>1,'stock'=>6,'status'=>'active']);
        $this->db->table('branch_products')->insert(['id'=>2,'product_id'=>2,'branch_id'=>1,'stock'=>6,'price'=>12,'cost_price'=>5,'status'=>'active']);
        $token = str_repeat('a',48);
        $input = $this->exchangeInput(['replacements'=>[2=>2], 'exchange_token'=>$token]);
        session()->set(['exchange_1'=>['token'=>$token], 'branch_id'=>null, 'cart'=>['preserve'=>'existing cart']]);
        $html = $this->controller(\App\Controllers\Cashier\ExchangeController::class, $input)->review(1);
        $this->assertStringContainsString('Review your exchange', $html);
        $this->assertStringContainsString('Replacement Tablet', $html);
        $this->assertStringContainsString('Collect from customer', $html);
        $this->assertSame(1, $this->db->table('sales')->countAllResults());
        $response = $this->controller(\App\Controllers\Cashier\ExchangeController::class, ['exchange_token'=>$token])->complete(1);
        $this->assertStringContainsString('cashier/sales/receipt/2', $response->getHeaderLine('Location'));
        $this->assertSame(12, (int) $this->row('branch_products')['stock']);
        $newStock = $this->db->table('branch_products')->where('id',2)->get()->getRowArray();
        $this->assertSame(4, (int) $newStock['stock']);
        $this->assertSame(['preserve'=>'existing cart'], session('cart'));
        $html = $this->controller(\App\Controllers\Cashier\SalesController::class, [])->receipt(2);
        $this->assertStringContainsString('Credit applied', $html);
        $this->assertStringContainsString('Additional payment', $html);
        $this->assertStringContainsString('₱4.00', $html);
        $html = $this->controller(\App\Controllers\Cashier\SalesController::class, [])->refundSlip(1);
        $this->assertStringContainsString('Replacement receipt', $html);
        $this->assertStringContainsString('Credit applied to replacement', $html);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class, [])->edit(2);
        $this->assertStringContainsString('exchange links', session()->getFlashdata('error'));
    }

    public function testAccountChangesRevokeAnExistingSession(): void
    {
        $this->assertTrue(AccountSession::check());
        session()->set('cart', ['old-branch-item']);
        (new \App\Models\UserModel())->update(1, ['status' => 'inactive']);
        $this->assertFalse(AccountSession::check());
        $this->assertNull(session('cart'));
        // Reactivation cannot revive a pre-deactivation fingerprint.
        (new \App\Models\UserModel())->update(1, ['status' => 'active']);
        $this->assertNotEmpty($this->row('users')['session_version']);
    }

    public function testLoginPageRedirectsAuthenticatedUsersToTheirAllowedHome(): void
    {
        $auth = $this->controller(\App\Controllers\Auth::class, []);
        service('request')->setMethod('GET');
        session()->set('cart', ['keep-current-sale']);
        $response = $auth->login();
        $this->assertSame(site_url('dashboard'), $response->getHeaderLine('Location'));
        $this->assertSame(['keep-current-sale'], session('cart'));

        $this->db->table('users')->where('id', 1)->update(['role' => 'cashier']);
        session()->set('account_fingerprint', AccountSession::fingerprint($this->row('users')));
        // Validation refreshes the role from the account, not a stale session value.
        $response = $auth->login();
        $this->assertSame(site_url('cashier/sales'), $response->getHeaderLine('Location'));
        $this->assertSame('cashier', session('role'));
        $this->assertSame(0, $this->db->table('activity_logs')->countAllResults());
    }

    public function testLoginPageRendersForGuestsAndRevokedSessions(): void
    {
        helper(['url', 'form']);
        $auth = $this->controller(\App\Controllers\Auth::class, []);
        service('request')->setMethod('GET');
        session()->remove(array_keys(session()->get()));
        $this->assertStringContainsString('id="loginForm"', $auth->login());

        foreach ([['status' => 'inactive'], ['status' => 'active', 'password' => 'changed-hash']] as $change) {
            session()->set([
                'isLoggedIn' => true, 'user_id' => 1, 'role' => 'admin', 'cart' => ['stale-item'],
                'account_fingerprint' => AccountSession::fingerprint($this->row('users')),
            ]);
            $this->db->table('users')->where('id', 1)->update($change);
            $this->assertStringContainsString('id="loginForm"', $auth->login());
            $this->assertNull(session('isLoggedIn'));
            $this->assertNull(session('cart'));
        }
    }

    public function testPasswordRoleAndBranchChangesRevokeSessions(): void
    {
        foreach ([['password' => 'new-hash'], ['role' => 'cashier'], ['branch_id' => 2]] as $change) {
            session()->set(['isLoggedIn' => true, 'user_id' => 1, 'account_fingerprint' => AccountSession::fingerprint($this->row('users'))]);
            (new \App\Models\UserModel())->update(1, $change);
            $this->assertFalse(AccountSession::check());
        }
    }

    public function testStaleStockCountIsRejectedAndDeliveryAddsToCurrentStock(): void
    {
        $post = ['product_id' => 1, 'branch_id' => 1, 'old_stock' => 12, 'stock' => 17, 'stock_mode' => 'count', 'remarks' => 'Physical count'];
        $this->controller(\App\Controllers\Products::class, $post)->updateStock();
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
        $this->assertSame(0, $this->db->table('stock_logs')->countAllResults());
        $post['stock_mode'] = 'receive';
        $post['stock'] = 5;
        $this->controller(\App\Controllers\Products::class, $post)->updateStock();
        $this->assertSame(15, (int) $this->row('branch_products')['stock']);
        $this->assertSame(5, (int) $this->row('stock_logs')['quantity']);
    }

    private function cashierStockPost(string $mode, int $qty, int $oldStock = 10): array
    {
        session()->set(['role'=>'cashier','branch_id'=>1,'stock_adjustment_1_1'=>str_repeat('9',48)]);
        return ['product_id'=>1,'branch_id'=>1,'old_stock'=>$oldStock,'stock'=>$qty,'stock_mode'=>$mode,'remarks'=>'Inventory check','stock_token'=>str_repeat('9',48)];
    }

    public function testCashierAddsAndRemovesUnitsWithAccurateLogsAndNoDuplicateSubmit(): void
    {
        $post = $this->cashierStockPost('add',5);
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(15,(int)$this->row('branch_products')['stock']);
        $this->assertSame(15,(int)$this->row('products')['stock']);
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(15,(int)$this->row('branch_products')['stock']);
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
        $post = $this->cashierStockPost('remove',4,15);
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(11,(int)$this->row('branch_products')['stock']);
        $logs = $this->db->table('stock_logs')->orderBy('id')->get()->getResultArray();
        $this->assertSame(['stock_in','stock_out'],array_column($logs,'action_type'));
        $this->assertEquals([5,4],array_column($logs,'quantity'));
        $this->assertEquals([10,15],array_column($logs,'previous_stock'));
        $this->assertEquals([15,11],array_column($logs,'new_stock'));
        $this->assertSame(2,$this->db->table('activity_logs')->countAllResults());
    }

    public function testCashierStockRejectsTooManyUnitsStaleCountsAndOtherBranches(): void
    {
        foreach ([['remove',11,10],['add',1,9],['remove',1,9],['add',0,10],['remove',-1,10]] as [$mode,$qty,$old]) {
            $this->controller(\App\Controllers\Products::class,$this->cashierStockPost($mode,$qty,$old))->updateStock();
            $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        }
        $post = $this->cashierStockPost('remove',2);
        $post['branch_id'] = 2;
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertStringContainsString('assigned branch',session()->getFlashdata('error'));
        $post = $this->cashierStockPost('remove',10);
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(0,(int)$this->row('branch_products')['stock']);
    }

    public function testCashierStockRequiresReasonAndTokenAndChecksLockedCount(): void
    {
        $post = $this->cashierStockPost('add',2);
        $post['remarks'] = '   ';
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $post = $this->cashierStockPost('add',2);
        $post['stock_token'] = 'forged';
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $post = $this->cashierStockPost('remove',2);
        $this->db->beforeInventoryLock = static function ($db) { $db->table('branch_products')->where('id',1)->update(['stock'=>9]); };
        $this->controller(\App\Controllers\Products::class,$post)->updateStock();
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->assertStringContainsString('Stock changed',session()->getFlashdata('error'));
    }

    private function correctionPost(array $replace = []): array
    {
        $sale = $this->row('sales');
        $items = $this->db->table('sale_items')->where('sale_id',1)->orderBy('id')->get()->getResultArray();
        $pricing = new \App\Libraries\SaleCorrectionPricing($this->db);
        $context = $pricing->context($sale,$items);
        $post = array_replace(['items'=>[1=>3],'payment_method'=>'cash','correction_reason'=>'Correct quantity'], $replace);
        try { $difference = $pricing->calculate($sale,$items,$post['items'],$context)['difference']; }
        catch (InvalidArgumentException $error) { $difference = 0; }
        return $post + ['sale_revision'=>SaleRevision::fingerprint($sale,$items), 'discount_revision'=>$context['revision'], 'settlement_confirmed'=>'1', 'settlement_difference'=>(string)$difference];
    }

    public function testCorrectionUsesLockedItemsAndRejectsCompetingRevision(): void
    {
        $post = $this->correctionPost();
        // Model another administrator completing a correction before our lock.
        $this->db->beforeSaleLock = static function ($db): void {
            $db->table('sale_items')->where('id', 1)->update(['quantity' => 4, 'subtotal' => 40]);
        };
        $this->controller(\App\Controllers\Admin\SaleCorrection::class, $post)->update(1);
        $this->assertSame(10, (int) $this->row('branch_products')['stock']);
        $this->assertStringContainsString('FROM sales', $this->db->locks[0]);
        $this->assertStringContainsString('FROM sale_items', $this->db->locks[1]);
        $this->assertSame(0, $this->db->table('stock_logs')->countAllResults());
    }

    public function testValidCorrectionAdjustsStockOnce(): void
    {
        $post = $this->correctionPost();
        $this->controller(\App\Controllers\Admin\SaleCorrection::class, $post)->update(1);
        $this->assertSame(12, (int) $this->row('branch_products')['stock']);
        $this->assertSame(3, (int) $this->row('sale_items')['quantity']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class, $post)->update(1);
        $this->assertSame(12, (int) $this->row('branch_products')['stock']);
    }

    private function setDiscountedSale(string $type, float $value, float $minimum = 0, ?float $maximum = null): void
    {
        $rule = \App\Libraries\DiscountPolicy::fromDiscount(['discount_type'=>$type,'discount_value'=>$value,'minimum_purchase'=>$minimum,'max_discount_amount'=>$maximum]);
        $amount = \App\Libraries\DiscountPolicy::amount(50, $rule);
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1,'discount_amount'=>$amount,'final_total'=>50-$amount,'change_amount'=>50+$amount]);
        $this->db->table('sale_items')->where('id',1)->update(['discount_applied'=>$amount,'profit'=>30-$amount]);
    }

    public function testDiscountedQuantityChangesWithoutRecordedRuleAreRejected(): void
    {
        $this->setDiscountedSale('fixed',20,40);
        $post = $this->correctionPost();
        $post['items'][1] = 8;
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(20.0,(float)$this->row('sales')['discount_amount']);
        $this->assertSame(30.0,(float)$this->row('sales')['final_total']);
        $post = $this->correctionPost();
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(20.0,(float)$this->row('sales')['discount_amount']);
        $this->assertSame(30.0,(float)$this->row('sales')['final_total']);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
    }

    public function testPromotionChangesCannotAlterHistoricalDiscounts(): void
    {
        $this->setDiscountedSale('percentage',20,0,10);
        $this->db->table('discounts')->insert(['id'=>1,'discount_type'=>'percentage','discount_value'=>100,'max_discount_amount'=>null,'status'=>'inactive']);
        $post = $this->correctionPost();
        $post['items'][1] = 10;
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(10.0,(float)$this->row('sales')['discount_amount']);
        $this->assertSame(40.0,(float)$this->row('sales')['final_total']);
        $this->assertSame(20.0,(float)$this->row('sale_items')['profit']);
    }

    public function testDiscountedSaleAllowsNotesAndPaymentCorrections(): void
    {
        $this->setDiscountedSale('fixed',20);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$this->correctionPost())->update(1);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $post = $this->correctionPost();
        $post['items'][1] = 5;
        $post['notes'] = 'Corrected note';
        $post['payment_method'] = 'gcash';
        $post['reference_no'] = 'TEST-GCASH';
        $itemsBefore = $this->row('sale_items');
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame('Corrected note',$this->row('sales')['notes']);
        $this->assertSame(20.0,(float)$this->row('sales')['discount_amount']);
        $this->assertSame('gcash',$this->row('sales')['payment_method']);
        $this->assertSame('TEST-GCASH',$this->row('sales')['reference_no']);
        $this->assertSame($itemsBefore,$this->row('sale_items'));
    }

    private function linkCorrectionDiscount(string $type = 'percentage', float $value = 20, float $minimum = 0, ?float $maximum = null): void
    {
        $this->setDiscountedSale($type,$value,$minimum,$maximum);
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Recorded discount','discount_type'=>$type,'discount_value'=>$value,'applies_to'=>'all','minimum_purchase'=>$minimum,'max_discount_amount'=>$maximum,'status'=>'active']);
    }

    public function testDiscountedCorrectionRepricesOriginalPricesAndCollectsExactAdditionalPayment(): void
    {
        helper(['url','form']);
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Order discount','discount_type'=>'percentage','discount_value'=>20,'applies_to'=>'all','minimum_purchase'=>200,'status'=>'active']);
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1,'total_amount'=>220,'discount_amount'=>44,'final_total'=>176,'amount_paid'=>176,'change_amount'=>0]);
        $this->db->table('sale_items')->where('id',1)->update(['quantity'=>1,'price'=>100,'cost_price_at_sale'=>40,'subtotal'=>100,'discount_applied'=>20,'profit'=>40]);
        $this->seedExchangeReplacement(2,300);
        $this->db->table('sale_items')->insert(['id'=>2,'sale_id'=>1,'product_id'=>2,'product_name_snapshot'=>'Second product','quantity'=>1,'price'=>120,'cost_price_at_sale'=>50,'subtotal'=>120,'discount_applied'=>24,'profit'=>46]);
        $post = $this->correctionPost(['items'=>[1=>2,2=>1]]);
        $this->assertSame('8000',$post['settlement_difference']);
        $response = $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(302,$response->getStatusCode());
        $sale = $this->row('sales');
        $this->assertEquals(320,$sale['total_amount']);
        $this->assertEquals(64,$sale['discount_amount']);
        $this->assertEquals(256,$sale['final_total']);
        $this->assertEquals(256,$sale['amount_paid']);
        $this->assertEquals(0,$sale['change_amount']);
        $this->assertEquals(40,$this->row('sale_items')['discount_applied']);
        $this->assertEquals(80,$this->row('sale_items')['profit']);
        $this->assertSame(9,(int)$this->row('branch_products')['stock']);
        $this->assertSame(10,(int)$this->db->table('branch_products')->where('id',2)->get()->getRowArray()['stock']);
        $this->assertStringContainsString('Additional payment collected: PHP 80.00',$this->row('activity_logs')['activity']);
        $this->assertEquals(256,(new CashMovementReport($this->db))->generate('2026-09-14','2026-09-14')['totals']['receipts']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(1,$this->db->table('stock_logs')->countAllResults());
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
    }

    public function testDiscountedCorrectionPaysBackDifferenceAndPreservesPreviouslyReturnedChange(): void
    {
        $this->linkCorrectionDiscount();
        $post = $this->correctionPost();
        $this->assertSame('-1600',$post['settlement_difference']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(30,$this->row('sales')['total_amount']);
        $this->assertEquals(6,$this->row('sales')['discount_amount']);
        $this->assertEquals(24,$this->row('sales')['final_total']);
        $this->assertEquals(84,$this->row('sales')['amount_paid']);
        $this->assertEquals(60,$this->row('sales')['change_amount']);
        $this->assertEquals(12,$this->row('sale_items')['profit']);
        $this->assertSame(12,(int)$this->row('branch_products')['stock']);
        $this->assertStringContainsString('Paid back to customer: PHP 16.00',$this->row('activity_logs')['activity']);
    }

    public function testCorrectionRechecksFixedDiscountMinimumAndPercentageCap(): void
    {
        $this->linkCorrectionDiscount('fixed',20,40);
        $post = $this->correctionPost();
        $this->assertSame('0',$post['settlement_difference']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(0,$this->row('sales')['discount_amount']);
        $this->assertEquals(30,$this->row('sales')['final_total']);
        $this->assertEquals(18,$this->row('sale_items')['profit']);
        $post = $this->correctionPost(['items'=>[1=>8]]);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(20,$this->row('sales')['discount_amount']);
        $this->assertEquals(60,$this->row('sales')['final_total']);
        // Start a compatible capped percentage sale on the corrected quantities.
        $this->db->table('discounts')->where('id',1)->update(['discount_type'=>'percentage','discount_value'=>50,'minimum_purchase'=>0,'max_discount_amount'=>20]);
        $post = $this->correctionPost(['items'=>[1=>10]]);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(20,$this->row('sales')['discount_amount']);
        $this->assertEquals(80,$this->row('sales')['final_total']);
    }

    public function testCorrectionAllocatesDiscountCentavosAndRecalculatesOnlyEligibleItems(): void
    {
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Product discount','discount_type'=>'percentage','discount_value'=>50,'applies_to'=>'product','product_id'=>1,'minimum_purchase'=>0,'status'=>'active']);
        $this->db->table('sale_items')->where('id',1)->update(['quantity'=>1,'price'=>1.01,'cost_price_at_sale'=>0.10,'subtotal'=>1.01,'discount_applied'=>0.51,'profit'=>0.40]);
        $this->seedExchangeReplacement(2,1.01);
        $this->db->table('sale_items')->insert(['id'=>2,'sale_id'=>1,'product_id'=>2,'product_name_snapshot'=>'Not eligible','quantity'=>1,'price'=>1.01,'cost_price_at_sale'=>0.10,'subtotal'=>1.01,'discount_applied'=>0,'profit'=>0.91]);
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1,'total_amount'=>2.02,'discount_amount'=>0.51,'final_total'=>1.51,'amount_paid'=>1.51,'change_amount'=>0]);
        $post = $this->correctionPost(['items'=>[1=>2,2=>3]]);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(5.05,$this->row('sales')['total_amount']);
        $this->assertEquals(1.01,$this->row('sales')['discount_amount']);
        $this->assertEquals(4.04,$this->row('sales')['final_total']);
        $this->assertEquals(0.81,$this->row('sale_items')['profit']);
        $this->assertEquals(0,$this->db->table('sale_items')->where('id',2)->get()->getRowArray()['discount_applied']);
        $this->assertEquals(2.73,$this->db->table('sale_items')->where('id',2)->get()->getRowArray()['profit']);
    }

    public function testCorrectionRequiresExactSettlementAndNonCashReferenceWithoutUsingOriginalChange(): void
    {
        $this->linkCorrectionDiscount();
        foreach ([['settlement_confirmed'=>'0'],['settlement_difference'=>'0'],['payment_method'=>'gcash','reference_no'=>'']] as $override) {
            $post = $this->correctionPost(['items'=>[1=>8]]);
            $html = $this->controller(\App\Controllers\Admin\SaleCorrection::class,array_replace($post,$override))->update(1);
            $this->assertIsString($html);
            $this->assertEquals(40,$this->row('sales')['final_total']);
            $this->assertSame(10,(int)$this->row('branch_products')['stock']);
            $this->assertSame(0,$this->db->table('activity_logs')->countAllResults());
        }
        $post = $this->correctionPost(['items'=>[1=>8],'payment_method'=>'gcash','reference_no'=>'CORRECTION-REF']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertEquals(64,$this->row('sales')['final_total']);
        $this->assertEquals(124,$this->row('sales')['amount_paid']);
        $this->assertEquals(60,$this->row('sales')['change_amount']);
        $this->assertStringContainsString('CORRECTION-REF',$this->row('activity_logs')['activity']);
    }

    public function testCorrectionRejectsStaleDiscountAndStillPreservesMetadataOnlyEdits(): void
    {
        $this->linkCorrectionDiscount();
        $post = $this->correctionPost();
        $this->db->table('discounts')->where('id',1)->update(['minimum_purchase'=>40]);
        $html = $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertStringContainsString('discount changed while this page was open',$html);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->db->table('discounts')->where('id',1)->update(['discount_value'=>99]);
        $before = $this->row('sale_items');
        $post = $this->correctionPost(['items'=>[1=>5],'notes'=>'Metadata only']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame($before,$this->row('sale_items'));
        $this->assertEquals(10,$this->row('sales')['discount_amount']);
        $this->assertSame('Metadata only',$this->row('sales')['notes']);
    }

    public function testCorrectionCanUseRetainedExpiredDiscountAndRollsBackIfAuditCannotBeSaved(): void
    {
        $this->linkCorrectionDiscount();
        $this->db->table('discounts')->where('id',1)->update(['status'=>'inactive','end_date'=>'2020-01-01','is_deleted'=>1,'is_permanently_deleted'=>1]);
        $post = $this->correctionPost();
        $this->db->query("CREATE TRIGGER reject_correction_audit BEFORE INSERT ON activity_logs BEGIN SELECT RAISE(ABORT, 'simulated audit failure'); END");
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->assertEquals(40,$this->row('sales')['final_total']);
        $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
        $this->db->query('DROP TRIGGER reject_correction_audit');
        // A later HTTP request starts with a fresh transaction status.
        $this->db->resetTransStatus();
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(3,(int)$this->row('sale_items')['quantity']);
        $this->assertSame(1,$this->db->table('activity_logs')->countAllResults());
    }

    public function testCorrectionRespectsCategoryEligibilityAndAllocatesExactCentavosAcrossLines(): void
    {
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Category discount','discount_type'=>'percentage','discount_value'=>50,'applies_to'=>'category','category_id'=>1,'minimum_purchase'=>0,'status'=>'active']);
        $this->db->table('products')->where('id',1)->update(['category_id'=>1]);
        $this->db->table('sale_items')->where('id',1)->update(['quantity'=>1,'price'=>1.01,'cost_price_at_sale'=>0.10,'subtotal'=>1.01,'discount_applied'=>0.51,'profit'=>0.40]);
        foreach ([2=>0.51,3=>0.50] as $id=>$discount) {
            $this->seedExchangeReplacement($id,1.01);
            $this->db->table('sale_items')->insert(['id'=>$id,'sale_id'=>1,'product_id'=>$id,'product_name_snapshot'=>'Product '.$id,'quantity'=>1,'price'=>1.01,'cost_price_at_sale'=>0.10,'subtotal'=>1.01,'discount_applied'=>$discount,'profit'=>0.91-$discount]);
        }
        $this->seedExchangeReplacement(4,5);
        $this->db->table('products')->where('id',4)->update(['category_id'=>2]);
        $this->db->table('sale_items')->insert(['id'=>4,'sale_id'=>1,'product_id'=>4,'product_name_snapshot'=>'Other category','quantity'=>1,'price'=>5,'cost_price_at_sale'=>1,'subtotal'=>5,'discount_applied'=>0,'profit'=>4]);
        $this->db->table('sales')->where('id',1)->update(['discount_id'=>1,'total_amount'=>8.03,'discount_amount'=>1.52,'final_total'=>6.51,'amount_paid'=>6.51,'change_amount'=>0]);
        $post = $this->correctionPost(['items'=>[1=>1,2=>2,3=>1,4=>3]]);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $items = $this->db->table('sale_items')->orderBy('id')->get()->getResultArray();
        $this->assertEquals([0.51,1.01,0.50,0],array_column($items,'discount_applied'));
        $this->assertEquals(2.02,$this->row('sales')['discount_amount']);
        $this->assertEquals(17.02,$this->row('sales')['final_total']);
        $this->assertEquals(12,$items[3]['profit']);
    }

    public function testDiscountedCorrectionsRejectInvalidStockAndPermissionsWithoutChangingRows(): void
    {
        $this->linkCorrectionDiscount();
        foreach ([['items'=>[1=>0]],['items'=>[1=>16]],['items'=>[1=>'1.5']],['items'=>[1=>-1]],['items'=>[1=>100001]]] as $override) {
            $post = $this->correctionPost();
            $this->controller(\App\Controllers\Admin\SaleCorrection::class,array_replace($post,$override))->update(1);
            $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
            $this->assertSame(10,(int)$this->row('branch_products')['stock']);
        }
        $post = $this->correctionPost(['items'=>[1=>8]]);
        $this->db->beforeInventoryLock = static function ($db) { $db->table('branch_products')->where('id',1)->update(['stock'=>1]); };
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->assertSame(0,$this->db->table('activity_logs')->countAllResults());
        session()->set('role','cashier');
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$this->correctionPost())->update(1);
        $this->assertStringContainsString('administrators',session()->getFlashdata('error'));
        $this->assertSame(0,$this->db->table('stock_logs')->countAllResults());
    }

    public function testCorrectionRefusesToRebillInconsistentTenderButAllowsNotes(): void
    {
        $this->linkCorrectionDiscount();
        $this->db->table('sales')->where('id',1)->update(['change_amount'=>0]);
        $post = $this->correctionPost();
        $html = $this->controller(\App\Controllers\Admin\SaleCorrection::class,$post)->update(1);
        $this->assertStringContainsString('recorded payment and change do not match',$html);
        $this->assertSame(5,(int)$this->row('sale_items')['quantity']);
        $this->controller(\App\Controllers\Admin\SaleCorrection::class,$this->correctionPost(['items'=>[1=>5],'notes'=>'Review old payment']))->update(1);
        $this->assertSame('Review old payment',$this->row('sales')['notes']);
        $this->assertEquals(40,$this->row('sales')['final_total']);
    }

    public function testCheckoutWorksWithoutNewColumnsAndRoundsBeforeSubtracting(): void
    {
        $this->db->table('discounts')->insert(['id'=>1,'discount_name'=>'Half off','discount_type'=>'percentage','discount_value'=>50,'applies_to'=>'all','minimum_purchase'=>0,'status'=>'active']);
        $this->db->table('branch_products')->where('id',1)->update(['price'=>1.01]);
        $token = str_repeat('a',32);
        session()->set(['cart'=>[1=>['product_id'=>1,'quantity'=>1]],'checkout_token'=>$token]);
        $this->controller(\App\Controllers\Cashier\SalesController::class,['_checkout_token'=>$token,'items'=>[1=>1],'discount_id'=>1,'payment_method'=>'cash','amount_paid'=>'0.50'])->checkout();
        $sale = $this->db->table('sales')->where('id',2)->get()->getRowArray();
        $this->assertNotNull($sale);
        $this->assertSame(0.51,(float)$sale['discount_amount']);
        $this->assertSame(0.50,(float)$sale['final_total']);
        $this->assertSame(1,(int)$sale['discount_id']);
        $this->assertFalse($this->db->fieldExists('discount_snapshot','sales'));
    }

    public function testRefundConditionsControlStockAndKeepPayoutRecords(): void
    {
        foreach (['quarantined', 'damaged', 'expired', 'resellable'] as $condition) {
            session()->set('refund_token_1', 'test-token');
            $post = ['_refund_token' => 'test-token', 'reason' => 'Customer return', 'refund_method' => 'gcash', 'refund' => [1 => 1], 'return_condition' => [1 => $condition]];
            $this->controller(\App\Controllers\Cashier\SalesController::class, $post)->refundPartial(1);
        }
        $this->assertSame(11, (int) $this->row('branch_products')['stock']);
        $rows = $this->db->table('refund_items')->get()->getResultArray();
        $this->assertCount(4, $rows);
        $this->assertSame(40.0, (float) array_sum(array_column($rows, 'refund_subtotal')));
        $this->assertCount(4, array_unique(array_column($rows, 'refund_event_id')));
        $this->assertSame(['gcash'], array_values(array_unique(array_column($rows, 'refund_method'))));
    }

    public function testExpiredInventoryCannotBeRestocked(): void
    {
        $this->assertSame(0, ReturnCondition::restockQuantity('expired', 2, '2026-09-01', '2026-09-16'));
        $this->expectException(InvalidArgumentException::class);
        ReturnCondition::restockQuantity('resellable', 2, '2026-09-01', '2026-09-16');
    }

    public function testDiscountsPreserveExactCentavosAcrossManyItems(): void
    {
        $this->assertSame(0.02, array_sum(DiscountAllocator::allocate([10, 10, 10, 10], 0.02)));
        for ($n = 1; $n <= 100; $n++) {
            $items = array_fill(0, $n, 0.03);
            $discount = min($n * 0.03, 0.07);
            $result = DiscountAllocator::allocate($items, $discount);
            $this->assertSame((int) round($discount * 100), (int) round(array_sum($result) * 100));
            foreach ($result as $value) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(0.03, $value);
            }
        }
    }

    public function testForecastRanksAllCandidatesAndSnapshotsAreNotDisplayLimited(): void
    {
        for ($id = 2; $id <= 70; $id++) {
            $this->db->table('products')->insert(['id' => $id, 'product_name' => 'Item ' . $id, 'sku' => 'ITEM' . $id, 'status' => 'active']);
            $this->db->table('branch_products')->insert(['id' => $id, 'product_id' => $id, 'branch_id' => 1, 'stock' => 1, 'reorder_level' => 100, 'price' => 10, 'cost_price' => 4, 'status' => 'active']);
        }
        $service = new ReorderForecastService();
        $rows = $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true, 1);
        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['product_id']);
        $this->assertSame(70, $this->db->table('forecasting_data')->countAllResults());
        $this->assertCount(70, $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, false, null));
    }

    public function testForecastPreviewDoesNotWriteSavedRecords(): void
    {
        $service = new ReorderForecastService();
        $withSales = $service->generate('2026-09-14', '2026-09-14', '1', 0.3, 0.2);
        $withoutSales = $service->generate('2026-09-13', '2026-09-13', '1', 0.3, 0.2);
        $this->assertGreaterThan(0, $withSales[0]['suggested_qty']);
        $this->assertSame(0, $withoutSales[0]['suggested_qty']);
        $this->assertSame(0, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testForecastSnapshotsPersistAcrossBranchesAndSkipSameDayDuplicates(): void
    {
        $this->db->table('branches')->insert(['id' => 2, 'branch_name' => 'Branch Two', 'status' => 'active']);
        $this->db->table('branch_products')->insert(['id' => 2, 'product_id' => 1, 'branch_id' => 2, 'stock' => 1, 'reorder_level' => 20, 'price' => 20, 'status' => 'active']);
        $this->db->table('sales')->insert(['id' => 2, 'branch_id' => 2, 'status' => 'completed', 'sale_date' => '2026-09-14 10:00:00']);
        $this->db->table('sale_items')->insert(['id' => 2, 'sale_id' => 2, 'product_id' => 1, 'quantity' => 7]);
        $service = new ReorderForecastService();
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true, null);
        $saved = $this->db->table('forecasting_data')->get()->getResultArray();
        $this->assertCount(1, $saved);
        $days = (int) date('t');
        $this->assertSame(12 * $days, (int) $saved[0]['predicted_quantity']);
        $this->assertSame((5 * 10 + 7 * 20) * $days, (int) $saved[0]['predicted_revenue']);
        $this->assertSame(date('Y-m-01'), $saved[0]['forecast_month']);

        // A later same-day run must not overwrite the first historical snapshot.
        $this->db->table('sale_items')->where('id', 2)->update(['quantity' => 20]);
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true, null);
        $this->assertSame($saved, $this->db->table('forecasting_data')->get()->getResultArray());

        $this->db->table('forecasting_data')->where('id', $saved[0]['id'])->update(['generated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true, null);
        $this->assertSame(2, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testManualForecastRefreshesMatchingRowsAndKeepsScheduledHistory(): void
    {
        $service = new ReorderForecastService();
        $this->assertSame(1, $service->saveManualSnapshot('2026-09-14', '2026-09-14', '1', 0.3, 0.2, 'daily'));
        $first = $this->row('forecasting_data');
        $this->assertSame(5 * (int) date('t'), (int) $first['predicted_quantity']);
        $this->assertSame(50 * (int) date('t'), (int) $first['predicted_revenue']);
        $this->db->table('sale_items')->where('id', 1)->update(['quantity' => 7]);
        $service->saveManualSnapshot('2026-09-14', '2026-09-14', '1', 0.3, 0.2, 'daily');
        $this->assertSame(1, $this->db->table('forecasting_data')->countAllResults());
        $this->assertSame(7 * (int) date('t'), (int) $this->row('forecasting_data')['predicted_quantity']);
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true);
        $this->assertSame(2, $this->db->table('forecasting_data')->countAllResults());
        $scheduled = $this->db->table('forecasting_data')->where('id', 2)->get()->getRowArray();
        $service->saveManualSnapshot('2026-09-13', '2026-09-13', '1', 0.3, 0.2, 'daily');
        $this->assertSame(3, $this->db->table('forecasting_data')->countAllResults());
        $service->saveManualSnapshot('2026-09-14', '2026-09-14', '', 0.3, 0.2, 'weekly');
        $this->assertSame(4, $this->db->table('forecasting_data')->countAllResults());
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true);
        $this->assertSame(4, $this->db->table('forecasting_data')->countAllResults());
        $this->assertSame($scheduled, $this->db->table('forecasting_data')->where('id', 2)->get()->getRowArray());
        $this->db->table('forecasting_data')->where('id', 1)->update(['generated_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
        $service->saveManualSnapshot('2026-09-14', '2026-09-14', '1', 0.3, 0.2, 'daily');
        $this->assertSame(5, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testManualForecastAggregatesBranchesAndSavesAllProducts(): void
    {
        $this->db->table('branches')->insert(['id' => 2, 'branch_name' => 'Branch Two', 'status' => 'active']);
        $this->db->table('branch_products')->insert(['id' => 2, 'product_id' => 1, 'branch_id' => 2, 'stock' => 1, 'reorder_level' => 20, 'price' => 20, 'status' => 'active']);
        $this->db->table('sales')->insert(['id' => 2, 'branch_id' => 2, 'status' => 'completed', 'sale_date' => '2026-09-14 10:00:00']);
        $this->db->table('sale_items')->insert(['id' => 2, 'sale_id' => 2, 'product_id' => 1, 'quantity' => 7]);
        for ($id = 2; $id <= 65; $id++) {
            $this->db->table('products')->insert(['id' => $id, 'product_name' => 'Item ' . $id, 'status' => 'active']);
            $this->db->table('branch_products')->insert(['id' => $id + 1, 'product_id' => $id, 'branch_id' => 1, 'stock' => 1, 'reorder_level' => 10, 'price' => 10, 'status' => 'active']);
        }
        $service = new ReorderForecastService();
        $this->assertSame(65, $service->saveManualSnapshot('2026-09-14', '2026-09-14', '', 0.3, 0.2, 'daily'));
        $this->assertSame(12 * (int) date('t'), (int) $this->row('forecasting_data')['predicted_quantity']);
        $this->assertSame(190 * (int) date('t'), (int) $this->row('forecasting_data')['predicted_revenue']);
        $this->assertSame(1, $service->saveManualSnapshot('2026-09-14', '2026-09-14', '2', 0.3, 0.2, 'daily'));
        $last = $this->db->table('forecasting_data')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertSame(7 * (int) date('t'), (int) $last['predicted_quantity']);
    }

    public function testManualForecastRollsBackPartialWrites(): void
    {
        $this->db->table('products')->insert(['id' => 2, 'product_name' => 'Fail Item', 'status' => 'active']);
        $this->db->table('branch_products')->insert(['id' => 2, 'product_id' => 2, 'branch_id' => 1, 'stock' => 1, 'reorder_level' => 10, 'price' => 10, 'status' => 'active']);
        $this->db->query("CREATE TRIGGER fail_snapshot BEFORE INSERT ON forecasting_data WHEN NEW.product_id = 2 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        try {
            (new ReorderForecastService())->saveManualSnapshot('2026-09-14', '2026-09-14', '', 0.3, 0.2, 'daily');
            $this->fail('Write failure must propagate');
        } catch (\RuntimeException $exception) {
            $this->assertSame(0, $this->db->table('forecasting_data')->countAllResults());
        }
    }

    public function testManualForecastActionSavesThenRedirectsAndRejectsBadFilters(): void
    {
        $post = ['branch_id' => '1', 'report_type' => 'daily', 'report_date_from' => '2026-09-01', 'report_date_to' => '2026-09-14', 'forecast_date_from' => '2026-09-14', 'forecast_date_to' => '2026-09-14', 'forecast_type' => 'weekly'];
        $response = $this->controller(\App\Controllers\Admin\ReportsController::class, $post)->updateForecast();
        $this->assertSame(303, $response->getStatusCode());
        $this->assertStringContainsString('forecast_type=weekly', $response->getHeaderLine('Location'));
        $this->assertStringNotContainsString('update-forecast', $response->getHeaderLine('Location'));
        $this->assertSame(8, $this->db->table('forecasting_data')->countAllResults());
        $this->assertStringContainsString('Saved 7 sales forecast periods and 1', session()->getFlashdata('forecast_success'));
        foreach (['branch_id' => '999', 'forecast_date_from' => ['invalid'], 'forecast_type' => 'invalid'] as $key => $value) {
            $this->controller(\App\Controllers\Admin\ReportsController::class, array_replace($post, [$key => $value]))->updateForecast();
            $this->assertStringContainsString('not saved', session()->getFlashdata('forecast_error'));
            $this->assertSame(8, $this->db->table('forecasting_data')->countAllResults());
        }
        $this->db->table('branch_products')->update(['stock' => 1000]);
        $this->controller(\App\Controllers\Admin\ReportsController::class, $post)->updateForecast();
        $this->assertStringContainsString('7 sales forecast periods and 0', session()->getFlashdata('forecast_success'));
        $this->assertSame(8, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testSalesForecastSnapshotsMatchChartForEveryIntervalAndRefreshWithoutDuplicates(): void
    {
        $post = ['branch_id' => '1', 'report_type' => 'daily', 'report_date_from' => '2026-09-01', 'report_date_to' => '2026-09-14', 'forecast_date_from' => '2026-09-01', 'forecast_date_to' => '2026-09-14'];
        foreach (['daily', 'weekly', 'monthly'] as $interval) {
            $controller = $this->controller(\App\Controllers\Admin\ReportsController::class, $post + ['forecast_type' => $interval]);
            $controller->updateForecast();
            $this->assertNotNull(session()->getFlashdata('forecast_success'));
            $history = (new ReflectionMethod($controller, 'buildPeriodHistory'))->invoke($controller, '2026-09-01', '2026-09-14', '1', $interval);
            $chart = (new ReflectionMethod($controller, 'computeHoltForecast'))->invoke($controller, $history, $interval);
            $method = \App\Libraries\SalesForecastSnapshot::basis('2026-09-01', '2026-09-14', '1', $interval);
            $rows = $this->db->table('forecasting_data')->where('method_used', $method)->orderBy('forecast_month')->get()->getResultArray();
            $this->assertCount(7, $rows);
            foreach ($rows as $i => $row) {
                $this->assertNull($row['product_id']);
                $this->assertNull($row['predicted_quantity']);
                $this->assertEquals($chart['futureForecast'][$i]['forecast_value'], $row['predicted_revenue']);
                $label = match ($interval) {
                    'weekly' => date('o-\WW', strtotime($row['forecast_month'])),
                    'monthly' => substr($row['forecast_month'], 0, 7),
                    default => $row['forecast_month'],
                };
                $this->assertSame($chart['futureForecast'][$i]['forecast_date'], $label);
            }
            $ids = array_column($rows, 'id');
            $controller->updateForecast();
            $this->assertSame($ids, array_column($this->db->table('forecasting_data')->where('method_used', $method)->orderBy('forecast_month')->get()->getResultArray(), 'id'));
        }
        $this->assertSame(24, $this->db->table('forecasting_data')->countAllResults());
        $this->db->table('sales')->where('id', 1)->update(['total_amount' => 80, 'final_total' => 80]);
        $this->controller(\App\Controllers\Admin\ReportsController::class, $post + ['forecast_type' => 'monthly'])->updateForecast();
        $refreshed = $this->db->table('forecasting_data')->where('method_used', \App\Libraries\SalesForecastSnapshot::basis('2026-09-01', '2026-09-14', '1', 'monthly'))->orderBy('forecast_month')->get()->getResultArray();
        $this->assertCount(7, $refreshed);
        $this->assertEquals(80, $refreshed[0]['predicted_revenue']);
        $this->assertSame('2026-10-01', $refreshed[0]['forecast_month']);
        $this->assertSame(24, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testSalesForecastSaveRollsBackIfProductSaveFails(): void
    {
        $this->db->query("CREATE TRIGGER fail_product_snapshot BEFORE INSERT ON forecasting_data WHEN NEW.product_id IS NOT NULL BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $post = ['branch_id' => '1', 'report_date_from' => '2026-09-14', 'report_date_to' => '2026-09-14', 'forecast_date_from' => '2026-09-14', 'forecast_date_to' => '2026-09-14', 'forecast_type' => 'daily'];
        $this->controller(\App\Controllers\Admin\ReportsController::class, $post)->updateForecast();
        $this->assertStringContainsString('could not be saved', session()->getFlashdata('forecast_error'));
        $this->assertSame(0, $this->db->table('forecasting_data')->countAllResults());
    }

    public function testSalesForecastTargetWeekHandlesIsoYearBoundary(): void
    {
        $this->db->transBegin();
        (new \App\Libraries\SalesForecastSnapshot())->save($this->db, [['forecast_date' => '2027-W01', 'forecast_value' => 123.45]], '2026-12-01', '2026-12-31', '', 'weekly');
        $this->db->transCommit();
        $this->assertSame('2027-01-04', $this->row('forecasting_data')['forecast_month']);
        $this->assertEquals(123.45, $this->row('forecasting_data')['predicted_revenue']);
    }

    public function testBranchAvailabilityOnlyShowsSellableOtherBranchStockAndPreservesCart(): void
    {
        helper(['url', 'form']);
        session()->set(['role' => 'cashier', 'cart' => ['sentinel' => 3]]);
        $this->db->table('branches')->insertBatch([
            ['id' => 2, 'branch_name' => 'Referral Branch', 'address' => '42 Referral Road', 'contact_number' => '09120000000', 'status' => 'active'],
            ['id' => 3, 'branch_name' => 'Closed Branch', 'address' => 'Hidden address', 'contact_number' => '', 'status' => 'inactive'],
        ]);
        $this->db->table('branch_products')->insertBatch([
            ['id' => 2, 'product_id' => 1, 'branch_id' => 2, 'stock' => 6, 'status' => 'active'],
            ['id' => 3, 'product_id' => 1, 'branch_id' => 3, 'stock' => 6, 'status' => 'active'],
        ]);
        $controller = $this->controller(\App\Controllers\Cashier\SalesController::class, []);
        service('request')->setGlobal('get', ['q' => 'TEST']);
        $html = $controller->branchAvailability();
        $this->assertStringContainsString('Referral Branch', $html);
        $this->assertStringContainsString('42 Referral Road', $html);
        $this->assertStringContainsString('09120000000', $html);
        $this->assertStringNotContainsString('Closed Branch', $html);
        $this->assertStringNotContainsString('<h3>Branch A</h3>', $html);
        $this->assertSame(['sentinel' => 3], session('cart'));
        $this->assertSame(1, session('branch_id'));
        foreach ([['stock' => 0], ['stock' => 6, 'expiration_date' => '2000-01-01'], ['expiration_date' => null, 'status' => 'inactive']] as $change) {
            $this->db->table('branch_products')->where('id', 2)->update($change);
            $this->assertStringContainsString('No available stock', $controller->branchAvailability());
        }
        $this->db->table('branch_products')->where('id', 2)->update(['status' => 'active']);
        $this->db->table('products')->where('id', 1)->update(['deleted_at' => '2026-01-01']);
        $this->assertStringContainsString('No available stock', $controller->branchAvailability());
        service('request')->setGlobal('get', ['q' => ['malformed']]);
        $this->assertStringContainsString('Search for a product', $controller->branchAvailability());
        session()->set('branch_id', null);
        $this->assertSame(403, $controller->branchAvailability()->getStatusCode());
        session()->set(['branch_id' => 1, 'isLoggedIn' => false]);
        $this->assertSame(403, $controller->branchAvailability()->getStatusCode());
    }

    public function testAuthenticationFailuresAndThrottleAreAuditedWithoutSecrets(): void
    {
        $cache = new \CodeIgniter\Test\Mock\MockCache(new \Config\Cache());
        \Config\Services::injectMock('cache', $cache);
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->controller(\App\Controllers\Auth::class, ['username' => 'does-not-exist', 'password' => 'secret-never-log'])->attemptLogin();
        }
        $rows = $this->db->table('activity_logs')->orderBy('id')->get()->getResultArray();
        $this->assertCount(5, $rows);
        $this->assertNull($rows[0]['user_id']);
        $this->assertStringContainsString('rate limit reached', $rows[4]['activity']);
        $this->assertStringNotContainsString('secret-never-log', json_encode($rows));
        $this->assertStringNotContainsString('does-not-exist', json_encode($rows));
        $filterResponse = (new \App\Filters\LoginThrottleFilter())->before(service('request'));
        $this->assertSame(302, $filterResponse->getStatusCode());
        $this->assertSame(6, $this->db->table('activity_logs')->countAllResults());
        $last = $this->db->table('activity_logs')->orderBy('id', 'DESC')->get()->getRowArray();
        $this->assertStringContainsString('Login blocked', $last['activity']);
    }

    public function testAuthenticationSuccessInactiveAndLogoutHaveAccountAuditContext(): void
    {
        \Config\Services::injectMock('cache', new \CodeIgniter\Test\Mock\MockCache(new \Config\Cache()));
        $this->db->table('users')->where('id', 1)->update(['password' => password_hash('test-password', PASSWORD_DEFAULT), 'status' => 'inactive']);
        $credentials = ['username' => 'staff', 'password' => 'test-password'];
        $this->controller(\App\Controllers\Auth::class, $credentials)->attemptLogin();
        $this->assertStringContainsString('inactive account', $this->row('activity_logs')['activity']);
        $this->assertSame(1, (int) $this->row('activity_logs')['user_id']);
        $this->db->table('users')->where('id', 1)->update(['status' => 'active']);
        $controller = $this->controller(\App\Controllers\Auth::class, $credentials);
        $this->assertStringContainsString('/dashboard', $controller->attemptLogin()->getHeaderLine('Location'));
        $this->assertTrue(session('isLoggedIn'));
        $controller->logout();
        $rows = $this->db->table('activity_logs')->orderBy('id')->get()->getResultArray();
        $this->assertCount(3, $rows);
        $this->assertStringContainsString('Login succeeded', $rows[1]['activity']);
        $this->assertStringContainsString('Logout succeeded', $rows[2]['activity']);
        $this->assertSame(1, (int) $rows[2]['user_id']);
        $this->assertStringNotContainsString('test-password', json_encode($rows));
        $this->assertNotEmpty($rows[2]['log_time']);
    }

    public function testSavedForecastLogReturnsLatestRecordsAndProductNames(): void
    {
        $service = new ReorderForecastService();
        $service->generate('2026-09-14', '2026-09-14', '', 0.3, 0.2, true, null);
        $controller = (new ReflectionClass(\App\Controllers\Admin\ReportsController::class))->newInstanceWithoutConstructor();
        (new ReflectionProperty($controller, 'db'))->setValue($controller, $this->db);
        $read = new ReflectionMethod($controller, 'getForecastingDataLog');
        $rows = $read->invoke($controller);
        $this->assertCount(1, $rows);
        $this->assertSame('Test Item', $rows[0]['product_name']);
        $this->assertGreaterThan(0, (int) $rows[0]['predicted_quantity']);
        $template = $rows[0];
        unset($template['id'], $template['product_name'], $template['sku']);
        $template['product_id'] = null;
        for ($i = 0; $i < 101; $i++) $this->db->table('forecasting_data')->insert($template);
        $rows = $read->invoke($controller);
        $this->assertCount(100, $rows);
        $this->assertSame(102, (int) $rows[0]['id']);
        $this->assertNull($rows[0]['product_name']);
    }

    public function testRefundPayoutDateAndMethodAreIndependentOfOriginalSale(): void
    {
        $this->db->table('sales')->where('id', 1)->update(['status' => 'refunded']);
        $this->db->table('refund_items')->insert(['sale_id' => 1, 'sale_item_id' => 1, 'quantity_refunded' => 5, 'refund_subtotal' => 50, 'return_condition' => 'damaged', 'refund_method' => 'gcash', 'created_at' => '2026-09-16 12:00:00']);
        $service = new CashMovementReport($this->db);
        $originalDay = $service->generate('2026-09-14', '2026-09-14');
        $refundDay = $service->generate('2026-09-16', '2026-09-16');
        $this->assertSame(50.0, $originalDay['totals']['receipts']);
        $this->assertSame(0.0, $originalDay['totals']['refunds']);
        $this->assertSame(-50.0, $refundDay['totals']['net']);
        $this->assertSame(0.0, $refundDay['totals']['cash_net']);
        $this->assertSame('gcash', $refundDay['rows'][0]['method']);
        $this->assertSame([], $service->generate('2026-09-16', '2026-09-16', '2')['rows']);
    }

    public function testLegacyRefundMethodRemainsUnknown(): void
    {
        $this->db->table('refund_items')->insert(['sale_id' => 1, 'refund_subtotal' => 10, 'created_at' => '2026-09-16 12:00:00']);
        $result = (new CashMovementReport($this->db))->generate('2026-09-16', '2026-09-16');
        $this->assertSame(10.0, $result['totals']['unknown_refunds']);
        $this->assertSame(0.0, $result['totals']['cash_net']);
    }

    public function testNonResellableFullRefundRetainsInventoryCost(): void
    {
        $this->db->table('sales')->where('id', 1)->update(['status' => 'refunded']);
        $this->db->table('refund_items')->insert(['sale_id' => 1, 'sale_item_id' => 1, 'quantity_refunded' => 5, 'refund_subtotal' => 50, 'return_condition' => 'damaged']);
        $controller = new \App\Controllers\Admin\ReportsController();
        $method = new ReflectionMethod($controller, 'refundedCogsProfitJoinSql');
        $subquery = $method->invoke($controller);
        $row = $this->db->query('SELECT si.cost_price_at_sale * si.quantity - rf.refunded_cogs AS cogs, si.profit - rf.refunded_profit AS profit FROM sale_items si JOIN ' . $subquery . ' ON rf.sale_item_id = si.id')->getRowArray();
        $this->assertSame(20.0, (float) $row['cogs']);
        $this->assertSame(-20.0, (float) $row['profit']);
    }

    public function testMigrationPreservesExistingRowsAndLegacyReturnBehavior(): void
    {
        $this->db->table('refund_items')->insert(['sale_id' => 1, 'sale_item_id' => 1, 'quantity_refunded' => 1, 'refund_subtotal' => 10]);
        foreach (['return_condition', 'refund_method', 'refund_event_id'] as $column) {
            $this->db->query('ALTER TABLE refund_items DROP COLUMN ' . $column);
        }
        $this->db->query('ALTER TABLE users DROP COLUMN session_version');
        // Refresh field metadata after changing the isolated schema.
        $this->db->resetDataCache();
        require_once APPPATH . 'Database/Migrations/2026_09_16_000005_add_logic_safeguards.php';
        $migration = new \App\Database\Migrations\AddLogicSafeguards(new \CodeIgniter\Database\SQLite3\Forge($this->db));
        $migration->up();
        $this->assertSame('resellable', $this->row('refund_items')['return_condition']);
        $this->assertNull($this->row('refund_items')['refund_method']);
        $this->assertSame(10.0, (float) $this->row('refund_items')['refund_subtotal']);
        $this->assertSame(1, $this->db->table('users')->countAllResults());
    }

    public function testUpdatedRefundAndReportViewsRender(): void
    {
        helper(['url', 'form']);
        session()->set(['role' => 'cashier', 'branch_id' => null]);
        $sale = $this->row('sales') + ['full_name' => 'Test Staff', 'branch_name' => 'Branch A'];
        $item = $this->row('sale_items') + ['already_refunded' => 0, 'refundable_qty' => 5, 'refund_unit_price' => 10];
        $html = view('cashier/sales/refund_partial', ['sale' => $sale, 'items' => [$item], 'refundHistory' => [], 'refundToken' => 'test']);
        $this->assertStringContainsString('name="refund_method"', $html);
        $this->assertStringContainsString('name="return_condition[1]"', $html);
        $html = view('admin/reports/cash_movements', ['cashMovements' => (new CashMovementReport($this->db))->generate('2026-09-14', '2026-09-16')]);
        $this->assertStringContainsString('September 14, 2026', $html);
        $this->assertStringContainsString('CASH', $html);
    }

    public function testRefundSlipsSeparateEventsInTheSameSecond(): void
    {
        helper(['url', 'form']);
        foreach (['event-one' => 'First returned item', 'event-two' => 'Second returned item'] as $event => $name) {
            $this->db->table('refund_items')->insert([
                'sale_id' => 1, 'sale_item_id' => 1, 'product_name_snapshot' => $name,
                'quantity_refunded' => 1, 'price_at_sale' => 10, 'refund_subtotal' => 10,
                'refunded_by' => 1, 'reason' => 'Test return', 'return_condition' => 'quarantined',
                'refund_method' => 'cash', 'refund_event_id' => $event, 'created_at' => '2026-09-16 12:00:00',
            ]);
        }
        $controller = $this->controller(\App\Controllers\Cashier\SalesController::class, []);
        service('request')->setGlobal('get', ['event' => 'event-one']);
        $html = $controller->refundSlip(1);
        $this->assertStringContainsString('First returned item', $html);
        $this->assertStringNotContainsString('Second returned item', $html);
        $controller = $this->controller(\App\Controllers\Cashier\SalesController::class, []);
        service('request')->setGlobal('get', []);
        $html = $controller->refundSlip(1);
        $this->assertStringContainsString('Second returned item', $html);
        $this->assertStringNotContainsString('First returned item', $html);
    }
}
