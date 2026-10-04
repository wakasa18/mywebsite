<?php

use App\Libraries\DiscountPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class DiscountPolicyTest extends CIUnitTestCase
{
    public function testCentavoCalculationsMatchSharedBrowserCases(): void
    {
        foreach (json_decode(file_get_contents(TESTPATH . 'fixtures/discount-cases.json'), true) as $case) {
            $this->assertSame((float) $case['expected'], DiscountPolicy::amount($case['subtotal'], $case['rule']));
        }
    }

    public function testImportedItemDiscountsAlsoLockQuantities(): void
    {
        $this->assertTrue(DiscountPolicy::hasDiscount(['discount_id'=>null,'discount_amount'=>0],[['discount_applied'=>1]]));
        $this->assertTrue(DiscountPolicy::hasDiscount(['discount_id'=>1,'discount_amount'=>0],[]));
        $this->assertFalse(DiscountPolicy::hasDiscount(['discount_id'=>null,'discount_amount'=>0],[['discount_applied'=>0]]));
    }

    public function testAdminRejectsSubCentAndNonDecimalValues(): void
    {
        $reflection = new ReflectionClass(\App\Controllers\Admin\Discounts::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('businessRuleError');
        $input = ['discount_type'=>'percentage','discount_value'=>'10','minimum_purchase'=>'0','max_discount_amount'=>'','applies_to'=>'all'];
        foreach (['discount_value','minimum_purchase','max_discount_amount'] as $field) {
            foreach (['0.001','1e2','-1','100000000.00'] as $value) {
                $error = $method->invoke($controller, array_replace($input, [$field=>$value]));
                $this->assertSame($field, $error[0]);
            }
        }
        foreach (['discount_value','max_discount_amount'] as $field) {
            $this->assertSame($field, $method->invoke($controller, array_replace($input, [$field=>'0']))[0]);
        }
        $this->assertNull($method->invoke($controller, array_replace($input,['discount_value'=>'0.01','max_discount_amount'=>'0.01'])));
    }

    public function testCheckoutRulesRejectInvalidValues(): void
    {
        $discount = ['discount_type'=>'fixed','discount_value'=>'20','minimum_purchase'=>'50','max_discount_amount'=>null];
        $this->assertSame(20.0,DiscountPolicy::amount(100,DiscountPolicy::fromDiscount($discount)));
        $this->assertNull(DiscountPolicy::fromDiscount(array_replace($discount,['max_discount_amount'=>0])));
        $this->assertNull(DiscountPolicy::fromDiscount(array_replace($discount,['discount_type'=>'percentage','discount_value'=>101])));
    }
}
