<?php
declare(strict_types=1);
require_once __DIR__ . '/sales-report.php';

function snc_dashboard_report(PDO $pdo, string $start, string $end, bool $hourlyForSingleDay = true, int $productLimit = 5): array
{
    $filters = ['from' => $start, 'to' => $end, 'status' => 'paid', 'method' => '', 'search' => ''];
    [$from, $where, $params] = snc_sales_scope($filters);
    $hourly = $hourlyForSingleDay && $start === $end;
    $productLimitSql = $productLimit > 0 ? ' LIMIT '.min($productLimit,1000) : '';
    $format = $hourly ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $totals = snc_sales_report($pdo, $filters)['totals'];
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(COALESCE(p.paid_at, o.created_at), '{$format}') AS bucket,
            SUM(o.total_amount) AS paid_sales {$from}{$where} GROUP BY bucket ORDER BY bucket");
        $stmt->execute($params);
        $buckets = [];
        foreach ($stmt->fetchAll() as $row) $buckets[$row['bucket']] = $row['paid_sales'];
        $zone = new DateTimeZone('Asia/Singapore');
        $cursor = new DateTimeImmutable($start, $zone);
        $last = new DateTimeImmutable($end . ' 23:59:59', $zone);
        $series = [];
        while ($cursor <= $last) {
            $key = $cursor->format($hourly ? 'Y-m-d H:00:00' : 'Y-m-d');
            $series[] = ['label' => $cursor->format($hourly ? 'g A' : 'M j'), 'timestamp' => $key, 'amount' => $buckets[$key] ?? '0.00'];
            $cursor = $cursor->modify($hourly ? '+1 hour' : '+1 day');
        }
        // Allocate each paid order's total in integer cents, distributing leftover cents
        // by largest fractional remainder. This preserves the exact paid total per order.
        $stmt = $pdo->prepare("WITH item_lines AS (
            SELECT oi.id, oi.order_id, oi.menu_item_id, oi.item_name, oi.quantity,
                   CAST(oi.line_total * 100 AS DECIMAL(20,0)) AS line_cents,
                   CAST(o.total_amount * 100 AS DECIMAL(20,0)) AS total_cents,
                   SUM(oi.line_total * 100) OVER (PARTITION BY oi.order_id) AS base_cents
            {$from} JOIN order_items oi ON oi.order_id = o.id {$where}
        ), shares AS (
            SELECT *, COALESCE(FLOOR(total_cents * line_cents / NULLIF(base_cents, 0)),0) AS allocation,
                      COALESCE(MOD(total_cents * line_cents, NULLIF(base_cents, 0)),0) AS remainder_cents
            FROM item_lines WHERE base_cents > 0 OR total_cents = 0
        ), ranked AS (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY order_id ORDER BY remainder_cents DESC, id ASC) AS remainder_rank,
                      total_cents - SUM(allocation) OVER (PARTITION BY order_id) AS cents_left
            FROM shares
        )
        SELECT menu_item_id, item_name, SUM(quantity) AS quantity,
               SUM(allocation + CASE WHEN remainder_rank <= cents_left THEN 1 ELSE 0 END) AS net_cents
        FROM ranked GROUP BY menu_item_id, item_name
        ORDER BY net_cents DESC, quantity DESC, item_name ASC, menu_item_id ASC{$productLimitSql}");
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        if ($ownsTransaction) $pdo->commit();
        return ['totals' => $totals, 'series' => $series, 'products' => $products, 'hourly' => $hourly];
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
