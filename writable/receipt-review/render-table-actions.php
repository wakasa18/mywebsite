<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Preview Staff']);
$product = ['id'=>1,'product_id'=>1,'product_name'=>'Paracetamol 500mg Tablet','sku'=>'PARA-500','category_name'=>'Pain relief','manufacturer'=>'Sample manufacturer','supplier_name'=>'Sample supplier','unit'=>'box','branch_id'=>1,'branch_name'=>'Main Branch','price'=>25,'cost_price'=>10,'stock'=>8,'reorder_level'=>10,'status'=>'active','expiration_date'=>'2028-01-01','deleted_at'=>'2026-09-27 12:00:00'];
$branch = ['id'=>1,'branch_name'=>'Main Branch','branch_code'=>'MAIN','address'=>'Santa Maria, Bulacan','contact_number'=>'09123456789','status'=>'active','created_at'=>'2026-09-01 09:00:00'];
$user = ['id'=>1,'full_name'=>'Maria Santos','username'=>'maria','role'=>'cashier','branch_name'=>'Main Branch','status'=>'active','created_at'=>'2026-09-01 09:00:00'];
$sale = ['id'=>1,'invoice_no'=>'INV-20260928-001','branch_name'=>'Main Branch','full_name'=>'Maria Santos','final_total'=>125,'payment_method'=>'cash','status'=>'completed','sale_date'=>'2026-09-28 09:00:00'];
$fixtures = [
 'users'=>['admin/users/index',['users'=>[$user,array_replace($user,['id'=>2,'full_name'=>'Carlo Reyes','status'=>'inactive'])],'branches'=>[$branch],'totalUsers'=>2,'activeUsers'=>1,'inactiveUsers'=>1,'adminUsers'=>0,'cashierUsers'=>2]],
 'history'=>['cashier/sales/history',['sales'=>[$sale,array_replace($sale,['id'=>2,'status'=>'refunded'])],'branches'=>[$branch]]],
 'products'=>['products/index',['products'=>[$product],'categories'=>[],'branches'=>[$branch],'suppliers'=>[],'keyword'=>'','branchId'=>'','categoryId'=>'']],
 'trash'=>['products/trash',['products'=>[$product],'keyword'=>'']],
 'category-trash'=>['categories/trash',['categories'=>[['id'=>1,'category_name'=>'Pain relief','deleted_at'=>'2026-09-28 09:00:00']]]],
 'supplier-trash'=>['admin/suppliers/trash',['suppliers'=>[['id'=>1,'supplier_name'=>'Sample supplier','contact_person'=>'Jo Santos','contact_number'=>'09123456789','email'=>'supplier@example.test','status'=>'active','deleted_at'=>'2026-09-28 09:00:00']]]],
 'discount-trash'=>['admin/discounts/trash',['discounts'=>[['id'=>1,'discount_name'=>'Customer discount','discount_type'=>'percentage','discount_value'=>20,'description'=>'Kept for past receipts','applies_to'=>'all','usage_count'=>5,'deleted_at'=>'2026-09-28 09:00:00']]]],
 'categories'=>['categories/index',['categories'=>[['id'=>1,'category_name'=>'Pain relief','sample_products'=>['Paracetamol'],'product_count'=>1,'created_at'=>'2026-09-01']],'keyword'=>'']],
 'suppliers'=>['admin/suppliers/index',['suppliers'=>[['id'=>1,'supplier_name'=>'Sample supplier','contact_person'=>'Jo Santos','contact_number'=>'09123456789','email'=>'supplier@example.test','address'=>'Santa Maria, Bulacan','product_count'=>3,'status'=>'active']],'keyword'=>'']],
 'branches'=>['admin/branches/index',['branches'=>[$branch,array_replace($branch,['id'=>2,'branch_name'=>'Closed Branch','branch_code'=>'CLOSED','status'=>'inactive'])],'totalBranches'=>2,'activeBranches'=>1,'inactiveBranches'=>1]],
];
foreach ($fixtures as $name => [$view, $data]) {
    $pager = new \CodeIgniter\Pager\Pager(new \Config\Pager(), service('renderer'));
    $pager->store('default', 1, 10, 2);
    $data['pager'] = $pager;
    $data += ['filterBranch'=>'','filterPaymentMethod'=>'','filterStatus'=>'','status'=>'','stockFilter'=>'','keyword'=>''];
    try { $html = view($view, $data, ['saveData'=>false]); }
    catch (Throwable $error) { while (ob_get_level()) ob_end_clean(); fwrite(STDERR, $name . ': ' . $error->getMessage() . "\n"); exit(1); }
    file_put_contents(__DIR__ . '/table-actions-' . $name . '.html', preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo 'Rendered ' . count($fixtures) . " actual table views.\n";
session()->set(['role'=>'cashier','branch_id'=>1,'branch_name'=>'Main Branch']);
$data = $fixtures['products'][1];
$data['pager'] = null;
$data['products'] = [$product,array_replace($product,['id'=>2,'stock'=>0,'product_name'=>'Out of stock product'])];
$data += ['filterBranch'=>'1','filterPaymentMethod'=>'','filterStatus'=>'','status'=>'','stockFilter'=>'','keyword'=>''];
$html = view('products/index',$data,['saveData'=>false]);
file_put_contents(__DIR__.'/table-actions-cashier-stock.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
echo "Rendered cashier add/remove stock fixture.\n";
