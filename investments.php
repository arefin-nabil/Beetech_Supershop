<?php
// investments.php
require_once 'config.php';
require_once 'includes/db_connect.php';

$current_page = 'investments';
require_once 'includes/header.php';

// Only Admin can access
require_admin();

$success_msg = '';
$error_msg = '';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $investor_name = clean_input($_POST['investor_name']);
        $amount = (float)$_POST['amount'];
        $date = clean_input($_POST['invest_date']);
        $purpose = clean_input($_POST['purpose']);

        if (!empty($investor_name) && $amount > 0 && !empty($date)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO investments (investor_name, amount, invest_date, purpose, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$investor_name, $amount, $date, $purpose]);
                set_flash_message('success', 'Investment added successfully!');
                header("Location: investments.php");
                exit;
            } catch (PDOException $e) {
                $error_msg = "Error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please fill all required fields.";
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM investments WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Investment deleted successfully!');
            header("Location: investments.php");
            exit;
        } catch (PDOException $e) {
             $error_msg = "Error: " . $e->getMessage();
        }
    }
}


// Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$investor_search = $_GET['investor_search'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build Query
$where = "WHERE 1=1";
$params = [];

if ($from_date) {
    $where .= " AND invest_date >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where .= " AND invest_date <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($investor_search) {
    $where .= " AND investor_name LIKE :investor_search";
    $params[':investor_search'] = "%$investor_search%";
}
if ($search) {
    $where .= " AND purpose LIKE :search";
    $params[':search'] = "%$search%";
}

// Fetch Investments
$sql = "SELECT * FROM investments $where ORDER BY invest_date DESC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$investments = $stmt->fetchAll();

// Count Total Rows (for Pagination)
$count_sql = "SELECT COUNT(*) FROM investments $where";
$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $val) {
    $count_stmt->bindValue($key, $val);
}
$count_stmt->execute();
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Count Total Amount (Filtered)
$sum_sql = "SELECT SUM(amount) FROM investments $where";
$sum_stmt = $pdo->prepare($sum_sql);
foreach ($params as $key => $val) {
    $sum_stmt->bindValue($key, $val);
}
$sum_stmt->execute();
$total_filtered_amount = $sum_stmt->fetchColumn() ?: 0;

// Total All Time Amount
$total_all_sql = "SELECT SUM(amount) FROM investments";
$total_all_stmt = $pdo->query($total_all_sql);
$total_all_amount = $total_all_stmt->fetchColumn() ?: 0;

?>

<style>
    @media print {
        @page { size: A4; margin: 1cm; }
        body * { visibility: hidden; }
        .printable-content, .printable-content * { visibility: visible; }
        .printable-content { position: absolute; left: 0; top: 0; width: 100%; }
        .no-print { display: none !important; }
        /* Improve table print readability */
        table { width: 100% !important; border-collapse: collapse; }
        th, td { border: 1px solid #ddd !important; padding: 8px; color: black !important; }
        .card { border: none !important; box-shadow: none !important; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold text-primary">Investment Management</h2>
        <p class="text-muted small mb-0">Track all capital investments</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary" onclick="window.print()">
            <i class="fas fa-print me-2"></i> Print Report
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInvestmentModal">
            <i class="fas fa-plus me-2"></i> Add Investment
        </button>
    </div>
</div>

<?php 
if ($error_msg) echo "<div class='alert alert-danger no-print'>$error_msg</div>";
display_flash_message(); 
?>

<!-- Summary Cards -->
<div class="row mb-4 no-print">
    <div class="col-md-4">
        <div class="card bg-primary text-white border-0 shadow-sm">
            <div class="card-body">
                <div class="small opacity-75">Total Capital Invested</div>
                <h3 class="fw-bold mb-0">৳<?php echo format_money($total_all_amount); ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Filter Panel -->
<div class="card glass-panel border-0 mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Investor Name</label>
                <input type="text" name="investor_search" class="form-control" placeholder="Search Investor..." value="<?php echo htmlspecialchars($investor_search); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Purpose Search</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search Purpose..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="col-12 text-end">
                <a href="investments.php" class="btn btn-sm btn-link text-secondary">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<div class="card glass-panel border-0 printable-content">
    <div class="card-body">
        
        <!-- Print Header (Hidden on Screen) -->
        <div class="d-none d-print-block text-center mb-4">
            <h2>Investment Report</h2>
            <p>Generated on: <?php echo date('d M Y h:i A'); ?></p>
            <?php if($investor_search): ?>
                <p>Investor: <?php echo htmlspecialchars($investor_search); ?></p>
            <?php endif; ?>
        </div>

        <?php if(!empty($from_date) || !empty($to_date) || !empty($investor_search) || !empty($search)): ?>
            <div class="alert alert-info py-2 d-flex justify-content-between align-items-center no-print">
                <span><i class="fas fa-info-circle me-2"></i> Filtered Total Amount: <span class="fw-bold"><?php echo format_money($total_filtered_amount); ?></span></span>
            </div>
            <!-- Visible on print only if filtered -->
            <div class="d-none d-print-block border-bottom mb-3 pb-2 fw-bold">
                Filtered Total: <?php echo format_money($total_filtered_amount); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Investor Name</th>
                        <th>Purpose / Detail</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end no-print">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($investments)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No investments found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach($investments as $inv): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($inv['invest_date'])); ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($inv['investor_name']); ?></td>
                            <td class="text-secondary small"><?php echo htmlspecialchars($inv['purpose']); ?></td>
                            <td class="fw-bold text-end">৳<?php echo number_format($inv['amount'], 2); ?></td>
                            <td class="text-end no-print">
                                <form method="POST" onsubmit="return confirm('Delete this investment record?');" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $inv['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <!-- Print Total Row -->
                        <tr class="d-none d-print-table-row fw-bold bg-light">
                            <td colspan="3" class="text-end">TOTAL</td>
                            <td class="text-end">৳<?php echo number_format($total_filtered_amount > 0 ? $total_filtered_amount : $total_all_amount, 2); ?></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-4 no-print">
            <ul class="pagination justify-content-center">
                <?php 
                    // Build query string for pagination
                    $qs = $_GET;
                    unset($qs['page']);
                    $query_string = http_build_query($qs);
                    if ($query_string) $query_string = '&' . $query_string;
                ?>
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i . $query_string; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<!-- Add Investment Modal -->
<div class="modal fade" id="addInvestmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Investment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Investor Name</label>
                        <input type="text" name="investor_name" class="form-control" required list="investorList">
                        <datalist id="investorList">
                            <!-- Helper to show existing investors -->
                            <?php 
                            $invs_stmt = $pdo->query("SELECT DISTINCT investor_name FROM investments ORDER BY investor_name ASC");
                            while($r = $invs_stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<option value='" . htmlspecialchars($r['investor_name']) . "'>";
                            }
                            ?>
                        </datalist>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (BDT)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="invest_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose / Detail</label>
                        <textarea name="purpose" class="form-control" rows="2" placeholder="e.g. Shop Decoration, New Stock..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Investment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
