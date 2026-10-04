<?php
// A small shared icon vocabulary for navigation, inventory and checkout.
$iconPaths = [
    'print' => '<path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6zM17 12h.01"/>',
    'cart' => '<path d="M3 3h2l2.5 12h11L21 7H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'chart' => '<path d="M4 4v16h16M8 15v-4m5 4V7m5 8v-6"/>',
    'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'building' => '<path d="M4 21V3h12v18M2 21h20M16 9h4v12M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/>',
    'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M16 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
    'box' => '<path d="m12 3 9 5-9 5-9-5 9-5Zm-9 5v10l9 5 9-5V8M12 13v10M7.5 5.5l9 5"/>',
    'layers' => '<path d="m12 3 10 5-10 5L2 8l10-5ZM2 12l10 5 10-5M2 16l10 5 10-5"/>',
    'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3ZM9 7h6M9 11h6M9 15h3"/>',
    'wallet' => '<path d="M20 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h15V8H5a3 3 0 0 1 0-6M20 12h-5v5h5M16 14.5h.01"/>',
    'alert' => '<path d="m12 3 10 18H2L12 3ZM12 9v5M12 17h.01"/>',
    'phone' => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M10 5h4M11 18h2"/>',
    'card' => '<rect x="2" y="4" width="20" height="16" rx="3"/><path d="M2 9h20M6 15h4"/>',
    'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>',
    'calculator' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 10h1m3 0h1m3 0h.01M8 14h1m3 0h1m3 0h.01M8 18h1m3 0h1m3 0h.01"/>',
    'tag' => '<path d="m3 3 8 0 10 10-8 8L3 11V3Z"/><circle cx="7.5" cy="7.5" r="1"/>',
];
?>
<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $iconPaths[$name ?? ''] ?? $iconPaths['box'] ?></svg>
