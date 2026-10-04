const fs = require('fs');
function edit(path, fn) {
  const original = fs.readFileSync(path, 'utf8');
  const result = fn(original.replace(/\r\n/g, '\n'));
  if (result === original.replace(/\r\n/g, '\n')) throw new Error('No change: ' + path);
  fs.writeFileSync(path, result);
}
function replace(s, a, b) {
  if (!s.includes(a)) throw new Error('Missing replacement: ' + a.slice(0, 90));
  return s.replace(a, b);
}
edit('app/Controllers/Admin/SaleCorrection.php', s => {
  const start = s.indexOf('        $this->db->transStart();');
  const end = s.indexOf('        foreach ($plans as $plan)', start);
  let lock = s.slice(start, end);
  lock = lock.replace('SELECT id, status FROM sales', 'SELECT * FROM sales');
  lock += `        $sale = array_replace($sale, $lockedSale);
        $currentItems = $this->db->query(
            'SELECT * FROM sale_items WHERE sale_id = ? ORDER BY id FOR UPDATE', [$saleId]
        )->getResultArray();
        if (!hash_equals(\\App\\Libraries\\SaleRevision::fingerprint($sale, $currentItems), (string) $this->request->getPost('sale_revision'))) {
            $this->db->transRollback();
            return redirect()->to(site_url('admin/sale-correction/' . $saleId))
                ->with('error', 'This sale was changed by another request. Review the latest values before saving again.');
        }
        $rejectCorrection = function (array $sale, $validation, string $message) {
            $this->db->transRollback();
            return $this->renderCorrection($sale, $validation, $message);
        };

`;
  s = s.slice(0, start) + s.slice(end);
  const calcStart = s.indexOf("        $currentItems = $this->saleItemModel->where('sale_id', $saleId)->findAll();");
  const calcEnd = s.indexOf('        foreach ($plans as $plan)', calcStart);
  let calc = s.slice(calcStart, calcEnd).replace("        $currentItems = $this->saleItemModel->where('sale_id', $saleId)->findAll();\n", '');
  calc = calc.replaceAll('return $this->renderCorrection(', 'return $rejectCorrection(');
  return s.slice(0, calcStart) + lock + calc + s.slice(calcEnd);
});
edit('app/Views/admin/sale_correction/edit.php', s => replace(s, '<?= csrf_field() ?>', `<?= csrf_field() ?>
        <input type="hidden" name="sale_revision" value="<?= esc(\\App\\Libraries\\SaleRevision::fingerprint($sale, $items)) ?>">`));
edit('app/Controllers/Cashier/SalesController.php', s => {
  const start = s.indexOf('            // Allocate the discount only');
  const end = s.indexOf('\n        }\n\n        if ($amountPaid', start);
  if (start < 0 || end < 0) throw new Error('Discount boundaries missing');
  return s.slice(0, start) + `            $eligibleSubtotals = [];
            foreach ($eligibleItemIds as $eligibleProductId) {
                $eligibleSubtotals[$eligibleProductId] = (float) $cart[$eligibleProductId]['subtotal'];
            }
            foreach (\\App\\Libraries\\DiscountAllocator::allocate($eligibleSubtotals, $discountAmount) as $id => $allocated) {
                $itemDiscounts[$id] = $allocated;
            }` + s.slice(end);
});
edit('app/Libraries/ReorderForecastService.php', s => {
  s = replace(s, '        int $limit = 60', '        ?int $limit = 60');
  s = replace(s, '            ->limit(max(1, min($limit, 250)))\n', '');
  s = replace(s, '        return $results;', '        return $limit === null ? $results : array_slice($results, 0, max(1, min($limit, 250)));');
  return s;
});
edit('app/Commands/GenerateReorderForecast.php', s => replace(s,
  "$service->generate($dateFrom, $dateTo, '', $alpha, $beta, true)",
  "$service->generate($dateFrom, $dateTo, '', $alpha, $beta, true, null)"));
