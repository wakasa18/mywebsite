<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Maria Santos']);
service('superglobals')->setGetArray(['keyword'=>'Vitamin & Zinc','status'=>'active']);
foreach (['first'=>[1,195],'middle'=>[6,195],'last'=>[20,195],'single'=>[1,5],'empty'=>[1,0],'large'=>[123456,2000000]] as $name => [$page,$total]) {
    $pager = new \CodeIgniter\Pager\Pager(new \Config\Pager(), service('renderer'));
    $pager->store('default',$page,10,$total)->setPath('/categories');
    $rows = $total ? [['id'=>10,'category_name'=>'Vitamins and supplements','sample_products'=>['Vitamin C + Zinc'],'product_count'=>5,'created_at'=>'2026-09-24 10:00:00']] : [];
    $html = view('categories/index', ['categories'=>$rows,'keyword'=>'Vitamin & Zinc','pager'=>$pager], ['saveData'=>false]);
    $html = preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html);
    file_put_contents(__DIR__.'/pagination-'.$name.'.html',$html);
}
echo "Rendered six pagination states in the actual categories page.\n";
