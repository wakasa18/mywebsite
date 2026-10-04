<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
session()->set(['role'=>'admin','branch_id'=>null,'full_name'=>'Preview Administrator']);
$branches=[['id'=>1,'branch_name'=>'Main Branch','status'=>'active'],['id'=>2,'branch_name'=>'Patag Branch','status'=>'active']];
$product=['id'=>44,'product_name'=>'Adhesive Bandage 20s','sku'=>'BAND20-039','category_name'=>'First Aid','manufacturer'=>'MediShield','supplier_name'=>'WellLife Medical Trading','unit'=>'box','branch_id'=>1,'branch_name'=>'Main Branch','branch_product_id'=>1,'branch_stock'=>35,'branch_price'=>55,'branch_cost_price'=>38,'branch_reorder_level'=>10,'branch_status'=>'active','branch_expiration_date'=>'2028-06-12','status'=>'active','deleted_at'=>'2026-10-03 10:00:00'];
$second=array_replace($product,['branch_id'=>2,'branch_name'=>'Patag Branch','branch_product_id'=>2,'branch_stock'=>46]);
foreach (['list','trash','empty','legacy'] as $name) {
    $pager=new \CodeIgniter\Pager\Pager(new \Config\Pager(),service('renderer'));
    $pager->store('default',1,15,2);
    $data=['products'=>$name==='empty'?[]:[$product,$second], 'branches'=>$branches,'branchId'=>'','keyword'=>'','pager'=>$pager,'categories'=>[],'categoryId'=>'','status'=>'','stockFilter'=>'','legacyProducts'=>[]];
    if($name==='legacy')$data['legacyProducts']=[array_replace($product,['id'=>55,'product_name'=>'Older shared deletion'])];
    $html=view($name==='list'?'products/index':'products/trash',$data,['saveData'=>false]);
    file_put_contents(__DIR__.'/branch-trash-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered product list and branch Trash states.\n";
