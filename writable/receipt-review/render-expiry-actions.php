<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
helper(['url','form']);
$row = ['id'=>1,'product_id'=>3,'branch_id'=>1,'product_name'=>'Ascorbic Acid + Zinc 500mg / 10mg Film-coated Tablet','sku'=>'VIT-C-ZINC-500','unit'=>'box','stock'=>35,'expiration_date'=>'2026-03-23','status'=>'active','product_status'=>'active','deleted_at'=>null,'branch_status'=>'active','branch_name'=>'Main Branch','manufacturer'=>'Sample manufacturer','supplier_name'=>'Sample supplier'];
$history = [['id'=>1,'quantity'=>35,'previous_stock'=>35,'new_stock'=>0,'remarks'=>'Expiry resolution: Replaced old stock: Returned to supplier; expiry 2026-03-23. Supplier delivery REPL-101 [ER:'.str_repeat('a',32).']','created_at'=>'2026-10-01 10:00:00','full_name'=>'Preview Staff'],['id'=>2,'quantity'=>40,'previous_stock'=>0,'new_stock'=>40,'remarks'=>'Expiry resolution: Replacement received; expiry 2026-03-23 -> 2027-12-31. Supplier delivery REPL-101 [ER:'.str_repeat('a',32).']','created_at'=>'2026-10-01 10:00:00','full_name'=>'Preview Staff']];
foreach (['replace','remove','deactivate','history','cashier-history','inactive-history','admin-list','cashier-list'] as $name) {
    $cashier = str_starts_with($name,'cashier');
    session()->set(['role'=>$cashier?'cashier':'admin','branch_id'=>null,'full_name'=>'Preview Staff']);
    if (str_ends_with($name,'list')) {
        $view = 'admin/reports/expiry_report';
        $data = ['products'=>[$row,array_replace($row,['id'=>2,'product_name'=>'Paracetamol 500mg','stock'=>0,'expiration_date'=>null])],'branches'=>[['id'=>1,'branch_name'=>'Main Branch']],'today'=>date('Y-m-d'),'nearDate'=>date('Y-m-d',strtotime('+30 days')),'isCashier'=>$cashier,'filterBranch'=>$cashier?'1':'','filterStatus'=>'all'];
    } else {
        $view = 'admin/reports/expiry_action';
        $data = ['row'=>$name==='inactive-history'?array_replace($row,['status'=>'inactive']):$row,'action'=>str_contains($name,'history')?'history':$name,'input'=>[],'error'=>null,'history'=>$history,'token'=>str_repeat('b',48)];
    }
    $html = view($view,$data,['saveData'=>false]);
    file_put_contents(__DIR__.'/expiry-action-'.$name.'.html',preg_replace('~https?://[^"\s]+/assets/~','/assets/',$html));
}
echo "Rendered eight expiry action fixtures.\n";
