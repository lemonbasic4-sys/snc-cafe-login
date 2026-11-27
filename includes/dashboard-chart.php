<?php
$series = $dashboardData['series'];
$maximum = max(array_map(static fn (array $point): float => (float) $point['amount'], $series));
$step = $maximum >= 1000 ? 1000 : ($maximum >= 100 ? 100 : 10);
$axisMaximum = max($step, ceil($maximum / $step) * $step);
$slot = 1110 / count($series);
$labelEvery = max(1, (int) ceil(count($series) / 7));
?>
<div class="snc-dashboard-chart">
  <svg viewBox="0 0 1200 240" role="img" aria-labelledby="snc-revenue-title snc-revenue-description">
    <title id="snc-revenue-title">Paid sales <?= snc_dashboard_escape($rangeLabel) ?></title>
    <desc id="snc-revenue-description">Sales after discounts, excluding unpaid and voided orders. A data table follows the chart.</desc>
    <?php foreach ([0, 0.5, 1] as $fraction): $y = 190 - 165 * $fraction; $tick = $axisMaximum * $fraction; ?>
    <line class="snc-dashboard-chart__grid" x1="70" y1="<?= $y ?>" x2="1180" y2="<?= $y ?>" />
    <text class="snc-dashboard-chart__axis" x="60" y="<?= $y + 4 ?>" text-anchor="end">₱<?= $tick >= 1000 ? number_format($tick / 1000, 1) . 'k' : number_format($tick, $tick < 10 ? 1 : 0) ?></text>
    <?php endforeach; ?>
    <?php foreach ($series as $index => $point):
        $height = (float) $point['amount'] / $axisMaximum * 165;
        $x = 70 + $index * $slot + $slot * 0.15;
        $y = 190 - $height;
        $description = $point['label'] . ': ' . snc_dashboard_money($point['amount']); ?>
    <rect class="snc-dashboard-chart__bar" x="<?= round($x, 2) ?>" y="<?= round($y, 2) ?>" width="<?= round($slot * 0.7, 2) ?>" height="<?= round($height, 2) ?>" rx="3" tabindex="0" role="img" aria-label="<?= snc_dashboard_escape($description) ?>"><title><?= snc_dashboard_escape($description) ?></title></rect>
    <?php if (($index % $labelEvery === 0 && $index <= count($series) - 1 - (int) ceil($labelEvery / 2)) || $index === count($series) - 1): ?>
    <text class="snc-dashboard-chart__axis" x="<?= round(70 + ($index + 0.5) * $slot, 2) ?>" y="215" text-anchor="middle"><?= snc_dashboard_escape($point['label']) ?></text>
    <?php endif; endforeach; ?>
  </svg>
</div>
<details class="snc-dashboard-chart-data"><summary>View chart data</summary><div class="snc-table-wrap"><table class="snc-table"><thead><tr><th><?= $dashboardData['hourly'] ? 'Hour' : 'Date' ?></th><th>Paid Sales</th></tr></thead><tbody><?php foreach ($series as $point): ?><tr><td><?= snc_dashboard_escape($point['timestamp']) ?></td><td><?= snc_dashboard_money($point['amount']) ?></td></tr><?php endforeach; ?></tbody></table></div></details>
