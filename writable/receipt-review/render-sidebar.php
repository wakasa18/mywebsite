<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
$layoutBusinessName = 'Pharxmaco';
$layoutBusinessType = 'Drugstore';
$fullName = 'Maria Santos';
$branchName = 'Santa Maria Main Branch';
$initials = 'MS';
foreach (['admin', 'cashier'] as $role) {
    session()->set(compact('role', 'fullName') + ['full_name' => $fullName, 'branch_name' => $branchName, 'branch_id' => null]);
    $displayRole = $role === 'admin' ? 'Administrator' : 'Cashier';
    $currentUri = $role === 'admin' ? '/admin/reports' : '/cashier/sales/history';
    $data = compact('role', 'fullName', 'branchName', 'displayRole', 'initials', 'currentUri', 'layoutBusinessName', 'layoutBusinessType');
    $sidebar = view('layouts/sidebar', $data, ['saveData' => false]);
    $dom = new DOMDocument();
    @$dom->loadHTML($sidebar);
    $xpath = new DOMXPath($dom);
    if ($xpath->query('//*[@aria-current="page"]')->length !== 1) throw new RuntimeException('Expected exactly one active page');
    if ($role === 'cashier' && str_contains($sidebar, '>Users</span>')) throw new RuntimeException('Admin navigation leaked');
    $html = view('layouts/staff', [], ['saveData' => false]);
    $html = preg_replace('~<aside class="sidebar"[\s\S]*?</aside>~', $sidebar, $html, 1);
    $html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
    file_put_contents(__DIR__ . '/sidebar-' . $role . '.html', $html);
}
echo "Rendered sidebar fixtures with role visibility and active-state checks.\n";
