<?php
/** @var \CodeIgniter\Pager\PagerRenderer $pager */
$total = max(0, (int) $pager->getTotal());
$pageCount = max(0, $pager->getPageCount());
$current = max(1, $pager->getCurrentPageNumber());
// Get adjacent-page URLs before restricting the numbered link window.
$previous = $pager->hasPreviousPage() ? $pager->getPreviousPage() : null;
$next = $pager->hasNextPage() ? $pager->getNextPage() : null;
$pager->setSurroundCount(2);
$simple = $simplePagination ?? false;
$start = $total > 0 ? max(1, (int) $pager->getPerPageStart()) : 0;
$end = min($total, (int) $pager->getPerPageEnd());
$icons = [
    'first' => '<path d="M6 5v14m12-14-7 7 7 7"/>',
    'previous' => '<path d="m15 5-7 7 7 7"/>',
    'next' => '<path d="m9 5 7 7-7 7"/>',
    'last' => '<path d="M18 5v14M6 5l7 7-7 7"/>',
];
$controls = [
    'first' => ['First page', $previous ? $pager->getFirst() : null, ''],
    'previous' => ['Previous page', $previous, 'Previous'],
    'next' => ['Next page', $next, 'Next'],
    'last' => ['Last page', $next ? $pager->getLast() : null, ''],
];
?>
<nav class="app-pagination" aria-label="Results pagination">
    <div class="pg-summary">
        <span class="pg-results"><?php if ($total > 0): ?>Showing <strong><?= number_format($start) ?>–<?= number_format($end) ?></strong> of <strong><?= number_format($total) ?></strong><?php else: ?>No results<?php endif; ?></span>
        <?php if ($total > 0): ?><span class="pg-position">Page <?= number_format($current) ?> of <?= number_format(max(1, $pageCount)) ?></span><?php endif; ?>
    </div>
    <?php if ($pageCount > 1): ?>
    <div class="pg-controls">
        <?php foreach ($controls as $direction => [$label, $url, $text]): ?>
            <?php if ($simple && in_array($direction, ['first', 'last'], true)) continue; ?>
            <?php if ($direction === 'next' && !$simple): ?>
                <ul class="pg-pages" aria-label="Page numbers">
                    <?php foreach ($pager->links() as $link): ?>
                        <li><?php if ($link['active']): ?><span class="pg-control pg-current" aria-current="page" aria-label="Page <?= (int) $link['title'] ?>, current page"><?= number_format($link['title']) ?></span><?php else: ?><a class="pg-control" href="<?= esc($link['uri'], 'attr') ?>" aria-label="Page <?= (int) $link['title'] ?>"><?= number_format($link['title']) ?></a><?php endif; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php $tag = $url ? 'a' : 'span'; ?>
            <<?= $tag ?> class="pg-control pg-<?= esc($direction) ?><?= $url ? '' : ' pg-disabled' ?>" <?= $url ? 'href="' . esc($url, 'attr') . '"' : 'role="link" aria-disabled="true"' ?> aria-label="<?= esc($label) ?>" title="<?= esc($label) ?>"<?= $url && in_array($direction, ['previous', 'next'], true) ? ' rel="' . ($direction === 'previous' ? 'prev' : 'next') . '"' : '' ?>>
                <?php if ($direction === 'next'): ?><span><?= esc($text) ?></span><?php endif; ?>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$direction] ?></svg>
                <?php if ($direction === 'previous'): ?><span><?= esc($text) ?></span><?php endif; ?>
            </<?= $tag ?>>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</nav>
