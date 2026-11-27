<?php
declare(strict_types=1);

/** One row per order. The earliest payment supplies the reporting date/method;
 *  sales use order totals, never tendered cash or joined line-item amounts. */
function snc_sales_scope(array $filters): array
{
    $conditions = [];
    $params = [];
    if ($filters['from'] !== '') { $conditions[] = 'COALESCE(p.paid_at, o.created_at) >= ?'; $params[] = $filters['from']; }
    if ($filters['to'] !== '') { $conditions[] = 'COALESCE(p.paid_at, o.created_at) < DATE_ADD(?, INTERVAL 1 DAY)'; $params[] = $filters['to']; }
    if ($filters['method'] !== '') { $conditions[] = 'p.method = ?'; $params[] = $filters['method']; }
    if ($filters['status'] !== '') { $conditions[] = 'o.payment_status = ?'; $params[] = $filters['status']; }
    if ($filters['search'] !== '') {
        $conditions[] = '(LOCATE(?, o.order_number) > 0 OR LOCATE(?, o.table_or_note) > 0 OR LOCATE(?, u.full_name) > 0)';
        array_push($params, $filters['search'], $filters['search'], $filters['search']);
    }
    $from = ' FROM orders o
              LEFT JOIN payments p ON p.id = (SELECT MIN(fp.id) FROM payments fp WHERE fp.order_id = o.id)
              LEFT JOIN users u ON u.id = o.cashier_id';
    $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
    return [$from, $where, $params];
}

function snc_sales_report(PDO $pdo, array $filters, int $page = 1): array
{
    [$from, $where, $params] = snc_sales_scope($filters);
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) AS matched_orders,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END), 0) AS paid_sales,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.discount_amount ELSE 0 END), 0) AS discounts,
            COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_orders,
            COALESCE(ROUND(AVG(CASE WHEN o.payment_status = 'paid' THEN o.total_amount END), 2), 0) AS average_paid_order
            {$from}{$where}");
        $stmt->execute($params);
        $totals = $stmt->fetch();
        $pages = max(1, (int) ceil((int) $totals['matched_orders'] / 25));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * 25;
        $stmt = $pdo->prepare("SELECT o.id, o.order_number, o.order_type, o.table_or_note,
            o.total_amount, o.payment_status, o.kitchen_status, o.created_at,
            p.method AS payment_method, p.paid_at, COALESCE(p.paid_at, o.created_at) AS transaction_at,
            u.full_name AS cashier_name {$from}{$where}
            ORDER BY transaction_at DESC, o.id DESC LIMIT 25 OFFSET {$offset}");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();
        if ($ownsTransaction) $pdo->commit();
        return ['totals' => $totals, 'orders' => $orders, 'pages' => $pages, 'page' => $page];
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
