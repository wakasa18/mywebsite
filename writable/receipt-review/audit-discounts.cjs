const fs = require('fs');
const vm = require('vm');
const assert = require('assert/strict');
const source = fs.readFileSync('app/Views/cashier/sales/index.php','utf8');
const calc = source.slice(source.indexOf('    function calcDiscount('), source.indexOf('    function refreshAll()'));
function preview(subtotal, value, minimum=0) {
 const attrs={'data-type':'percentage','data-value':String(value),'data-minimum':String(minimum),'data-max':'0','data-applies-to':'all'};
 const warning={textContent:'',classList:{remove(){},add(){}}};
 const context={discountSelect:{selectedIndex:0,options:[{value:'1',getAttribute:n=>attrs[n]||''}]},discountWarning:warning,fmt:n=>n.toFixed(2)};
 vm.createContext(context);
 vm.runInContext(calc+';globalThis.result=calcDiscount([{subtotal:'+subtotal+',productId:1,categoryId:1}],'+subtotal+');',context);
 return {rawDiscount:context.result,displayedDiscount:context.result.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}),displayedTotal:(subtotal-context.result).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}),warning:warning.textContent};
}
const rounding=preview(1.01,50);
assert.equal(rounding.displayedDiscount,'0.51');
assert.equal(rounding.displayedTotal,'0.51');
const minimum=preview(19.90*3,10,59.70);
assert.equal(minimum.rawDiscount,0);
assert.ok(minimum.warning.includes('qualify'));
console.log(JSON.stringify({rounding,minimum},null,2));
