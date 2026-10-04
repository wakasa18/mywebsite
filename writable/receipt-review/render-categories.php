<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role' => 'cashier', 'branch_id' => null, 'full_name' => 'UI Review']);
$categories = [
    ['id' => 10, 'category_name' => 'Allergy and Respiratory', 'sample_products' => ['Diphenhydramine 25mg Capsule', 'Fexofenadine 120mg Tablet', 'Hydrocortisone Cream 1%'], 'product_count' => 5, 'created_at' => '2026-09-20 12:30:00'],
    ['id' => 11, 'category_name' => 'Long category name ' . str_repeat('LongCategory', 6), 'sample_products' => [str_repeat('LongProductName', 10), 'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet'], 'product_count' => 2, 'created_at' => '2026-09-20 12:30:00'],
    ['id' => 12, 'category_name' => 'Unassigned category', 'sample_products' => [], 'product_count' => 0, 'created_at' => null],
];
foreach (['categories' => $categories, 'categories-empty' => []] as $name => $rows) {
    $html = view('categories/index', ['categories' => $rows, 'keyword' => ''], ['saveData' => false]);
    $html = preg_replace('~https?://[^"\s]+/assets/~', '/assets/', $html);
    preg_match_all('#<style[^>]*>(.*?)</style>#si', $html, $styles);
    foreach ($styles[1] as $css) (new \Sabberworm\CSS\Parser($css, \Sabberworm\CSS\Settings::create()->withLenientParsing(false)))->parse();
    file_put_contents(__DIR__ . '/' . $name . '.html', $html);
}
echo "Rendered populated and empty category pages; styles parsed.\n";
