const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const context = {window:{}};
vm.createContext(context);
vm.runInContext(fs.readFileSync('public/assets/js/discount-math.js','utf8'), context);
const math = context.window.PharxmacoDiscount;
const cases = JSON.parse(fs.readFileSync('tests/fixtures/discount-cases.json','utf8'));
for (const {subtotal,rule,expected} of cases) assert.equal(math.calculate(subtotal,rule),expected);
// Execute the actual checkout eligibility function, not a copy of its logic.
const source = fs.readFileSync('app/Views/cashier/sales/index.php','utf8');
const calc = source.slice(source.indexOf('    function calcDiscount('),source.indexOf('    function refreshAll()'));
const attrs = {'data-type':'percentage','data-value':'10','data-minimum':'59.70','data-max':'','data-applies-to':'all'};
Object.assign(context, {
 discountSelect:{selectedIndex:0,options:[{value:'1',getAttribute:n=>attrs[n]||''}]},
 discountWarning:{textContent:'',classList:{remove(){},add(){}}},fmt:n=>n.toFixed(2)
});
vm.runInContext('let currentDiscountValid = true;'+calc,context);
assert.equal(vm.runInContext('calcDiscount([{subtotal:19.90*3,productId:1,categoryId:1}],59.70)',context),5.97);
assert.equal(vm.runInContext('currentDiscountValid',context),true);
assert.equal(context.discountWarning.textContent,'');
attrs['data-applies-to']='category';attrs['data-category-id']='2';
assert.equal(vm.runInContext('calcDiscount([{subtotal:100,productId:1,categoryId:1}],100)',context),0);
assert.equal(vm.runInContext('currentDiscountValid',context),false);
attrs['data-applies-to']='all';attrs['data-value']='50';attrs['data-minimum']='0';
const half = vm.runInContext('calcDiscount([{subtotal:1.01,productId:1,categoryId:1}],1.01)',context);
assert.equal(half,0.51);
assert.equal((math.cents(1.01)-math.cents(half))/100,0.50);
assert.equal(vm.runInContext('currentDiscountValid',context),true);
console.log('Passed shared centavo cases and actual POS eligibility/rounding regression checks.');
