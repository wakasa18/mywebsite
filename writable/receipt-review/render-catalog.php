<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url', 'form']);
session()->set(['role'=>'cashier','branch_id'=>2,'branch_name'=>'New Branch','full_name'=>'Branch Cashier']);
$product = ['id'=>1,'product_name'=>'Paracetamol 500mg Tablet','sku'=>'PARA-500','category_name'=>'Pain relief','manufacturer'=>'Sample manufacturer','supplier_name'=>'Sample supplier','unit'=>'box','price'=>25,'cost_price'=>10,'reorder_level'=>10];
$products = [];
for ($i=1;$i<=12;$i++) $products[] = array_replace($product,['id'=>$i,'product_name'=>$i===2?'A product with a longer name and descriptive packaging information':$product['product_name']]);
foreach (['list','selected','empty','search-empty'] as $name) {
    $pager = new \CodeIgniter\Pager\Pager(new \Config\Pager(),service('renderer'));
    $pager->store('default',1,12,30);
    $html = view('products/catalog',[
        'branch'=>['id'=>2,'branch_name'=>'New Branch'], 'keyword'=>$name==='search-empty'?'Vitamins & supplements':'',
        'selected'=>$name==='selected'?$product:null, 'products'=>$name==='list'?$products:[],
        'pager'=>$pager,
    ],['saveData'=>false]);
    file_put_contents(__DIR__.'/catalog-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered catalog list, setup form, and both empty states.\n";
