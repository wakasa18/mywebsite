const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const { webcrypto } = require('node:crypto');
const listeners = {}, attrs = {}, timers = [];
const link = {
    href: 'https://example.com/admin/reports/export-excel?branch_id=2&report_type=weekly',
    addEventListener: (type, fn) => { listeners[type] = fn; },
    setAttribute: (key, value) => { attrs[key] = value; },
    removeAttribute: key => { delete attrs[key]; },
};
const context = {
    document: { querySelectorAll: () => [link] }, URL, Uint8Array,
    window: { crypto: webcrypto, location: { href: 'https://example.com/admin/reports' }, setTimeout: fn => timers.push(fn) },
};
vm.runInNewContext(fs.readFileSync('public/assets/js/report-exports.js', 'utf8'), context);
const click = (type = 'click', button = 0) => {
    const event = { type, button, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
    listeners[type](event);
    return event;
};
assert.equal(click().defaultPrevented, false);
const first = new URL(link.href);
assert.match(first.searchParams.get('export_request'), /^[a-f0-9]{32}$/);
assert.equal(first.searchParams.get('branch_id'), '2');
assert.equal(first.searchParams.get('report_type'), 'weekly');
assert.equal(click().defaultPrevented, true);
assert.equal(link.href, first.href);
assert.equal(attrs['aria-disabled'], 'true');
timers.shift()();
assert.equal(attrs['aria-disabled'], undefined);
assert.equal(click().defaultPrevented, false);
assert.notEqual(new URL(link.href).searchParams.get('export_request'), first.searchParams.get('export_request'));
timers.shift()();
assert.equal(click('auxclick', 1).defaultPrevented, false);
assert.equal(click('auxclick', 1).defaultPrevented, true);
timers.shift()();
context.window.crypto = undefined;
assert.equal(click().defaultPrevented, false);
assert.equal(new URL(link.href).searchParams.has('export_request'), false);
console.log('Export links: double-click guard, fresh request IDs, filters, middle-click and fallback passed.');
