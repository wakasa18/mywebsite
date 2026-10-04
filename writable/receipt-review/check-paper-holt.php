<?php
// Read-only mathematical checks against the paper's worked example.
require dirname(__DIR__, 2) . '/system/Test/bootstrap.php';

$cases = [
    'paper worked example' => [[850, 900, 1000], 965.0, 53.0, 1018.0],
    'constant demand' => [[100, 100, 100, 100], 100.0, 0.0, 100.0],
    'linear demand' => [[100, 110, 120, 130], 130.0, 10.0, 140.0],
];
$count = 0;
foreach ([App\Controllers\Admin\ReportsController::class, App\Libraries\ReorderForecastService::class] as $className) {
    $class = new ReflectionClass($className);
    $instance = $class->newInstanceWithoutConstructor();
    $method = $class->getMethod('holtSmooth');
    foreach ($cases as $label => [$series, $expectedLevel, $expectedTrend, $expectedNext]) {
        $result = $method->invoke($instance, $series, 0.3, 0.2);
        foreach ([$result['level'] - $expectedLevel, $result['trend'] - $expectedTrend, $result['level'] + $result['trend'] - $expectedNext] as $difference) {
            if (abs($difference) > 0.000001) {
                throw new RuntimeException("{$className}: {$label} failed");
            }
        }
        ++$count;
    }
}
echo "Passed {$count} Holt checks (both implementations): paper example = 1018; constant and linear series match.\n";
