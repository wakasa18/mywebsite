const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const entries = [
  ['products/create', 'wide'], ['products/edit', 'wide'],
  ['categories/create', 'compact'], ['categories/edit', 'compact'],
  ['admin/users/create', 'standard'], ['admin/users/edit', 'standard'], ['admin/users/password', 'standard'],
  ['admin/branches/create', 'standard'], ['admin/branches/edit', 'standard'],
  ['admin/suppliers/create', 'standard'], ['admin/suppliers/edit', 'standard'],
  ['admin/discounts/create', 'wide'], ['admin/discounts/edit', 'wide'],
  ['admin/branch_inventory/edit', 'standard'], ['admin/stock_transfer/create', 'standard'],
  ['admin/sale_correction/edit', 'wide'],
];
const actionLabels = {
  'categories/create': 'Save Category', 'categories/edit': 'Save Changes',
  'admin/users/create': 'Save User', 'admin/users/edit': 'Save Changes', 'admin/users/password': 'Update Password',
  'admin/branches/create': 'Save Branch', 'admin/branches/edit': 'Save Changes',
  'admin/suppliers/create': 'Save Supplier', 'admin/suppliers/edit': 'Save Changes',
};
const backupDir = path.join(__dirname, 'editor-source');
fs.mkdirSync(backupDir, {recursive: true});
for (const [view, size] of entries) {
  const target = path.join(root, 'app/Views', view + '.php');
  let source = fs.readFileSync(target, 'utf8');
  if (source.includes('class="editor-page')) throw Error('Already updated: ' + view);
  fs.writeFileSync(path.join(backupDir, view.replaceAll('/', '-') + '.txt'), source);
  const section = "<?= $this->section('content') ?>";
  const end = "<?= $this->endSection() ?>";
  if (!source.includes(section) || !source.includes(end)) throw Error('Missing view boundaries: ' + view);
  source = source.replace(section, section + '\n\n<div class="editor-page editor-page--' + size + '">');
  source = source.replace(end, '</div>\n\n' + end);
  let labelIndex = 0;
  source = source.replace(/<label([^>]*)>((?:(?!<\/label>)[\s\S])*?)<\/label>(\s*)<(input|select|textarea)\b((?:<\?[\s\S]*?\?>|[^>])*)>/g,
    (whole, labelAttributes, labelText, space, tag, attributes) => {
      const plainAttributes = attributes.replace(/<\?[\s\S]*?\?>/g, '');
      if (/type="(?:hidden|checkbox|radio)"/.test(plainAttributes)) return whole;
      const existingFor = labelAttributes.match(/\bfor="([^"]+)"/);
      const existingId = plainAttributes.match(/\bid="([^"]+)"/);
      const id = existingId?.[1] || existingFor?.[1] || 'editor-field-' + (++labelIndex);
      if (!existingId) attributes = ' id="' + id + '"' + attributes;
      if (!existingFor) labelAttributes += ' for="' + id + '"';
      if (/\brequired\b/.test(plainAttributes) && !labelText.includes('*')) {
        labelText += ' <span class="required-mark" aria-hidden="true">*</span>';
      }
      return '<label' + labelAttributes + '>' + labelText + '</label>' + space + '<' + tag + attributes + '>';
    });
  if (!view.includes('discounts/') && !view.includes('sale_correction/')) {
    source = source.replace('<?= csrf_field() ?>', '<?= csrf_field() ?>\n        <p class="editor-form-note">Fields marked <span class="required-mark">*</span> are required.</p>');
  }
  if (actionLabels[view]) {
    const actions = /<div(?: class="row")? style="(?:gap:10px;|margin-top:20px;display:flex;gap:10px;)">\s*<button type="submit"[\s\S]*?<\/button>\s*<a [\s\S]*?>Cancel<\/a>\s*<\/div>/g;
    let replacements = 0;
    const cancel = view === 'admin/users/password' ? 'admin/users' : view.slice(0, view.lastIndexOf('/'));
    source = source.replace(actions, () => {
      replacements++;
      return "<?= view('partials/editor_actions', ['cancelUrl' => site_url('" + cancel + "'), 'submitLabel' => '" + actionLabels[view] + "']) ?>";
    });
    if (replacements !== 1) throw Error('Unexpected action group: ' + view + ': ' + replacements);
  }
  fs.writeFileSync(target, source);
  console.log('Updated ' + view);
}
