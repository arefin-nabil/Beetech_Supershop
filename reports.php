<?php
// reports.php
require_once 'config.php';
require_once 'includes/db_connect.php';

$current_page = 'reports';
require_once 'includes/header.php';

// Parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$report_type = $_GET['report_type'] ?? 'daily'; // daily, monthly

// Validate dates
if ($start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Prepare Query
$where_sql = "DATE(created_at) BETWEEN :start AND :end";
$group_sql = "";
$date_format_sql = "";
$label_format = "";

if ($report_type === 'monthly') {
    $group_sql = "GROUP BY YEAR(created_at), MONTH(created_at)";
    $date_format_sql = "DATE_FORMAT(created_at, '%Y-%m')"; // For sorting/grouping
    $label_format = "M Y"; // PHP format
} else {
    // Daily
    $group_sql = "GROUP BY DATE(created_at)";
    $date_format_sql = "DATE(created_at)";
    $label_format = "d M Y";
}

$sql = "SELECT 
            $date_format_sql as report_date, 
            COUNT(*) as total_sales, 
            SUM(total_amount) as total_revenue,
            SUM(final_discount_amount) as total_discount,
            SUM(total_amount - (SELECT SUM(subtotal - (unit_buy_price * quantity)) FROM sale_items WHERE sale_id = sales.id)) as estimated_cost, -- Complex approximation, better to sum items directly usually, but let's stick to simple aggregates or subquery
            (SELECT SUM((unit_sell_price - unit_buy_price) * quantity) FROM sale_items WHERE sale_id IN (SELECT id FROM sales as s2 WHERE $date_format_sql = report_date )) as total_profit -- This is tricky in single query with group by
        FROM sales 
        WHERE $where_sql 
        $group_sql 
        ORDER BY report_date DESC";

// Simplified Query for Profit. 
// A better approach is to join sale_items
$sql = "SELECT 
            DATE_FORMAT(s.created_at, '" . ($report_type == 'monthly' ? '%Y-%m-01' : '%Y-%m-%d') . "') as date_group,
            COUNT(DISTINCT s.id) as total_invoices,
            SUM(si.unit_sell_price * si.quantity) as revenue,
            SUM((si.unit_sell_price - si.unit_buy_price) * si.quantity) as gross_profit
        FROM sales s
        JOIN sale_items si ON s.id = si.sale_id
        WHERE DATE(s.created_at) BETWEEN :start AND :end
        GROUP BY date_group
        ORDER BY date_group DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['start' => $start_date, 'end' => $end_date]);
$reports = $stmt->fetchAll();

// Calculate Totals for Footer
$grand_total_revenue = 0;
$grand_total_profit = 0;
$grand_total_invoices = 0;

foreach ($reports as $r) {
    $grand_total_revenue += $r['revenue'];
    $grand_total_profit += $r['gross_profit'];
    $grand_total_invoices += $r['total_invoices'];
}

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Sales Reports</h2>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card glass-panel border-0">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-secondary">Report Type</label>
                        <select name="report_type" class="form-select">
                            <option value="daily" <?php echo $report_type == 'daily' ? 'selected' : ''; ?>>Daily</option>
                            <option value="monthly" <?php echo $report_type == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i> Generate Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white border-0 h-100 shadow-sm">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Revenue</div>
                <div class="fs-3 fw-bold"><?php echo format_money($grand_total_revenue); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white border-0 h-100 shadow-sm">
            <div class="card-body">
                <div class="small text-white-50 text-uppercase fw-bold">Total Profit (100%)</div>
                <div class="fs-3 fw-bold"><?php echo format_money($grand_total_profit); ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card bg-info text-dark border-0 h-100 shadow-sm">
            <div class="card-body">
                <div class="small text-muted text-uppercase fw-bold">Total Invoices</div>
                <div class="fs-3 fw-bold"><?php echo number_format($grand_total_invoices); ?></div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="col-12">
        <div class="card glass-panel border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Date / Period</th>
                                <th class="text-center">Invoices</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Gross Profit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($reports)): ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No data found for selected period</td></tr>
                            <?php else: ?>
                                <?php foreach($reports as $row): ?>
                                <tr>
                                    <td class="fw-bold text-primary">
                                        <?php echo date($label_format, strtotime($row['date_group'])); ?>
                                    </td>
                                    <td class="text-center"><?php echo $row['total_invoices']; ?></td>
                                    <td class="text-end fw-bold"><?php echo format_money($row['revenue']); ?></td>
                                    <td class="text-end text-success"><?php echo format_money($row['gross_profit']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if(!empty($reports)): ?>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td>TOTAL</td>
                                <td class="text-center"><?php echo number_format($grand_total_invoices); ?></td>
                                <td class="text-end"><?php echo format_money($grand_total_revenue); ?></td>
                                <td class="text-end text-success"><?php echo format_money($grand_total_profit); ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
