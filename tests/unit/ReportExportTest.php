<?php

use App\Controllers\Admin\ReportsController;
use App\Models\ReportExportModel;
use CodeIgniter\Test\CIUnitTestCase;

final class ReportExportTest extends CIUnitTestCase
{
    private $exportDb;
    private ReportExportModel $exports;
    private array $data = [
        'branchId' => '', 'reportDateFrom' => '2026-09-01', 'reportDateTo' => '2026-09-21',
        'reportType' => 'daily', 'forecastDateFrom' => '2026-08-01',
        'forecastDateTo' => '2026-09-21', 'forecastType' => 'weekly',
        'salesSummary' => ['gross_sales' => 100, 'net_sales' => 90, 'total_discount' => 10, 'total_transactions' => 1],
        'profitSummary' => ['cogs' => 40, 'gross_profit' => 50],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportDb = \Config\Database::connect([
            'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '', 'DBDebug' => true,
        ], false);
        $this->exportDb->query('CREATE TABLE report_exports (id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER, branch_id INTEGER, export_type TEXT, date_from TEXT, date_to TEXT,
            gross_sales NUMERIC, net_sales NUMERIC, total_discount NUMERIC, cogs NUMERIC,
            gross_profit NUMERIC, total_transactions INTEGER, created_at TEXT)');
        $this->exports = new ReportExportModel($this->exportDb);
        session()->set('user_id', 1);
        session()->remove('report_export_receipts');
    }

    protected function tearDown(): void
    {
        session()->remove('report_export_receipts');
        $this->exportDb->close();
        parent::tearDown();
    }

    private function controller($token = null, string $method = 'GET', array $headers = []): ReportsController
    {
        $request = new \CodeIgniter\HTTP\IncomingRequest(new \Config\App(), new \CodeIgniter\HTTP\SiteURI(new \Config\App()), null, new \CodeIgniter\HTTP\UserAgent());
        $request->setMethod($method);
        $request->setGlobal('get', $token === null ? [] : ['export_request' => $token]);
        foreach ($headers as $key => $value) {
            $request->setHeader($key, $value);
        }
        $controller = (new ReflectionClass(ReportsController::class))->newInstanceWithoutConstructor();
        $controller->initController($request, service('response'), service('logger'));
        (new ReflectionProperty($controller, 'reportExportModel'))->setValue($controller, $this->exports);
        return $controller;
    }

    private function record(ReportsController $controller, string $type = 'excel', ?array $data = null): void
    {
        (new ReflectionMethod($controller, 'recordReportExport'))->invoke($controller, $type, $data ?? $this->data);
    }

    public function testRepeatedRequestRecordsOnceButAnotherClickRecordsAgain(): void
    {
        $token = str_repeat('a', 32);
        $this->record($this->controller($token));
        $this->record($this->controller($token));
        $this->assertCount(1, $this->exports->findAll());
        $this->record($this->controller(str_repeat('b', 32)));
        $rows = $this->exports->findAll();
        $this->assertCount(2, $rows);
        $this->assertEquals(100, $rows[0]['gross_sales']);
        $this->assertEquals(50, $rows[0]['gross_profit']);
        $this->assertNull($rows[0]['branch_id']);
    }

    public function testDifferentFormatFiltersAndUserAreNotSuppressed(): void
    {
        $token = str_repeat('a', 32);
        $this->record($this->controller($token));
        $this->record($this->controller($token), 'pdf');
        $this->record($this->controller($token), 'excel', array_replace($this->data, ['branchId' => '2']));
        $this->record($this->controller($token), 'excel', array_replace($this->data, ['forecastType' => 'monthly']));
        session()->set('user_id', 2);
        $this->record($this->controller($token));
        $this->assertCount(5, $this->exports->findAll());
    }

    public function testLegacyLinksHaveOnlyAShortDuplicateGuard(): void
    {
        $this->record($this->controller());
        $this->record($this->controller());
        $this->assertCount(1, $this->exports->findAll());
        $receipts = session('report_export_receipts');
        session()->set('report_export_receipts', array_map(static fn () => time() - 6, $receipts));
        $this->record($this->controller());
        $this->assertCount(2, $this->exports->findAll());
        // Array input cannot cause a type error or bypass the legacy guard.
        $this->record($this->controller(['invalid']));
        $this->assertCount(2, $this->exports->findAll());
    }

    public function testHeadAndPrefetchDoNotBuildOrRecordExports(): void
    {
        foreach (['exportExcel', 'exportPdf'] as $action) {
            $this->assertSame(204, $this->controller(null, 'HEAD')->$action()->getStatusCode());
            $this->assertSame(204, $this->controller(null, 'GET', ['Sec-Purpose' => 'prefetch;prerender'])->$action()->getStatusCode());
            $this->assertSame(204, $this->controller(null, 'GET', ['Purpose' => 'prefetch'])->$action()->getStatusCode());
        }
        $this->assertCount(0, $this->exports->findAll());
    }

    public function testFailedInsertDoesNotConsumeRequest(): void
    {
        $controller = $this->controller(str_repeat('c', 32));
        $failedModel = $this->createMock(ReportExportModel::class);
        $failedModel->method('insert')->willReturn(false);
        (new ReflectionProperty($controller, 'reportExportModel'))->setValue($controller, $failedModel);
        try {
            $this->record($controller);
            $this->fail('Expected insert failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Unable to record', $exception->getMessage());
        }
        $this->record($this->controller(str_repeat('c', 32)));
        $this->assertCount(1, $this->exports->findAll());
    }
}
