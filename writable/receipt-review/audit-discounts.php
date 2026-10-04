<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
set_exception_handler(static function(Throwable $e): void { fwrite(STDERR,$e->getMessage()); exit(1); });
$reflection = new ReflectionClass(\App\Controllers\Admin\Discounts::class);
$controller = $reflection->newInstanceWithoutConstructor();
$input = ['discount_name'=>'Audit only', 'discount_type'=>'percentage','discount_value'=>'50','description'=>'','applies_to'=>'all','status'=>'active','start_date'=>'','end_date'=>'','minimum_purchase'=>'','max_discount_amount'=>'0.001','category_id'=>'','product_id'=>''];
$validation = service('validation');
$validation->setRules($reflection->getMethod('rules')->invoke($controller,'all'));
$accepted = $validation->run($input);
$businessError = $reflection->getMethod('businessRuleError')->invoke($controller,$input);
$payload = $reflection->getMethod('payload')->invoke($controller,$input);
if (!$accepted || $businessError !== null || $payload['max_discount_amount'] != 0) throw new RuntimeException('Cap scenario not reproduced.');
$correctionSource = file_get_contents(APPPATH . 'Controllers/Admin/SaleCorrection.php');
$start = strpos($correctionSource, '            $grossSubtotal =');
$end = strpos($correctionSource, '            $branchProduct = null;', $start);
if ($start === false || $end === false) throw new RuntimeException('Correction calculation block not found.');
$calculation = substr($correctionSource, $start, $end - $start);
$correct = static function(int $newQty) use ($calculation): float {
    $item = ['price'=>100,'discount_applied'=>20,'cost_price_at_sale'=>50];
    $oldQty = 2;
    eval($calculation);
    return $newItemDiscount;
};
if ($correct(4) !== 40.0 || $correct(1) !== 10.0) throw new RuntimeException('Correction scenario not reproduced.');
echo json_encode([
 'correction'=>['original_qty'=>2,'original_discount'=>20,'discount_when_qty_doubles'=>$correct(4),'discount_when_qty_halves'=>$correct(1)],
 'server_rounding'=>['subtotal'=>1.01,'discount'=>round(1.01*0.50,2),'final_total'=>round(1.01-round(1.01*0.50,2),2)],
 'server_minimum'=>['eligible_subtotal'=>round(19.90*3,2),'qualifies'=>round(19.90*3,2)>=59.70],
 'cap_validation'=>['accepted'=>$accepted,'submitted_cap'=>'0.001','stored_cap'=>$payload['max_discount_amount'],'checkout_applies_cap'=>$payload['max_discount_amount']>0],
],JSON_PRETTY_PRINT),"\n";
