<?php
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';
$class = new ReflectionClass(\App\Controllers\Admin\ReportsController::class);
$method = $class->getMethod('normalizeDateRange');
foreach ([
    ['2024-09-23', true, '2024-09-23', false],
    ['2025-03-23', true, '2025-03-23', false],
    ['2024-08-23', true, '2024-09-23', true],
    ['2023-09-23', false, '2023-09-23', false],
] as [$from, $limited, $expected, $notice]) {
    $controller = $class->newInstanceWithoutConstructor();
    $range = $method->invoke($controller, $from, '2026-09-23', $limited, 'Forecast');
    $notices = $class->getProperty('filterNotices')->getValue($controller);
    if ($range !== [$expected, '2026-09-23'] || (count($notices) > 0) !== $notice) throw new RuntimeException('Unexpected date normalization');
    if ($notice && !str_contains($notices[0], '24 months')) throw new RuntimeException('Outdated notice');
}
echo "Passed server checks: 18 and 24 months accepted; 25 months limited; normal reports unchanged.\n";
