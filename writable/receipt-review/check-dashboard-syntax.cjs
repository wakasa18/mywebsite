const fs = require('fs'), vm = require('vm');
const html = fs.readFileSync('writable/receipt-review/dashboard-full.html', 'utf8');
let count = 0;
for (const match of html.matchAll(/<script(?![^>]*src=)[^>]*>([\s\S]*?)<\/script>/g)) {
    new vm.Script(match[1]);
    count++;
}
console.log(count + ' rendered scripts passed syntax checks');