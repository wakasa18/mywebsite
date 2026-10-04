<?php

namespace App\Commands;

use App\Libraries\ReorderForecastService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Generates today's reorder forecast snapshot for every low-stock product,
 * across all branches and writes a daily product-level snapshot to
 * forecasting_data. Explicit Reports-page updates write separate manual
 * snapshots; ordinary report reads do not write to the snapshot table.
 *
 * Run manually:
 *   php spark forecast:reorder
 *
 * Schedule it (recommended) so history builds up reliably even on days
 * nobody opens the Reports page — add a line like this to the server's
 * crontab (adjust the path):
 *
 *   0 1 * * * cd /path/to/pharxmacoo && php spark forecast:reorder >> writable/logs/forecast_cron.log 2>&1
 *
 * That runs it once a day at 1:00 AM. Running it more than once on the
 * same calendar day is harmless because same-day product rows are skipped.
 */
class GenerateReorderForecast extends BaseCommand
{
    protected $group = 'Forecast';
    protected $name = 'forecast:reorder';
    protected $description = 'Generates today\'s reorder forecast snapshot for all low-stock products and stores it in forecasting_data.';
    protected $usage = 'forecast:reorder [options]';
    protected $options = [
        '--days'  => 'How many days of sales history to fit the trend against. Default: 60.',
        '--alpha' => 'Holt\'s level smoothing constant (0–1). Default: 0.3.',
        '--beta'  => 'Holt\'s trend smoothing constant (0–1). Default: 0.2.',
    ];

    public function run(array $params)
    {
        $days = (int) (CLI::getOption('days') ?? 60);
        $alpha = (float) (CLI::getOption('alpha') ?? 0.3);
        $beta = (float) (CLI::getOption('beta') ?? 0.2);

        $days = max(14, $days);
        $alpha = max(0.01, min(1.0, $alpha));
        $beta = max(0.0, min(1.0, $beta));

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        CLI::write("Generating reorder forecast — history window {$dateFrom} to {$dateTo}, alpha={$alpha}, beta={$beta}", 'yellow');

        $service = new ReorderForecastService();
        $results = $service->generate($dateFrom, $dateTo, '', $alpha, $beta, true, null);

        if (empty($results)) {
            CLI::write('No low-stock products found across any branch — nothing to forecast.', 'green');

            return;
        }

        $counts = ['critical' => 0, 'warning' => 0, 'watch' => 0, 'unknown' => 0];
        foreach ($results as $row) {
            $counts[$row['urgency']]++;
        }

        CLI::write('');
        CLI::write(count($results) . ' low-stock product(s) forecasted:', 'green');
        CLI::write('  Critical (≤7 days left):  ' . $counts['critical'], 'red');
        CLI::write('  Warning (≤14 days left):  ' . $counts['warning'], 'yellow');
        CLI::write('  Watch (>14 days left):    ' . $counts['watch']);
        CLI::write('  No recent sales:          ' . $counts['unknown']);
        CLI::write('');
        CLI::write('Snapshot written to forecasting_data (same-day duplicate product rows were skipped).');
    }
}
