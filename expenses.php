<?php
// expenses.php
require_once 'config.php';
require_once 'includes/db_connect.php';

$current_page = 'expenses';
require_once 'includes/header.php';

// Only Admin can access
require_admin();

$success_msg = '';
$error_msg = '';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = clean_input($_POST['title']);
        $amount = (float)$_POST['amount'];
        $date = clean_input($_POST['expense_date']);
        $desc = clean_input($_POST['description']);

        if (!empty($title) && $amount > 0 && !empty($date)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO expenses (title, amount, expense_date, description, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $amount, $date, $desc]);
                set_flash_message('success', 'Expense added successfully!');
                header("Location: expenses.php");
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
            $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            set_flash_message('success', 'Expense deleted successfully!');
            header("Location: expenses.php");
            exit;
        } catch (PDOException $e) {
             $error_msg = "Error: " . $e->getMessage();
        }
    }
}


// Filters
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build Query
$where = "WHERE 1=1";
$params = [];

if ($from_date) {
    $where .= " AND expense_date >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $where .= " AND expense_date <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($category) {
    $where .= " AND title = :category";
    $params[':category'] = $category;
}
if ($search) {
    $where .= " AND description LIKE :search";
    $params[':search'] = "%$search%";
}

// Fetch Expenses
$sql = "SELECT * FROM expenses $where ORDER BY expense_date DESC, id DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$expenses = $stmt->fetchAll();

// Count Total Rows (for Pagination)
$count_sql = "SELECT COUNT(*) FROM expenses $where";
$count_stmt = $pdo->prepare($count_sql);
foreach ($params as $key => $val) {
    $count_stmt->bindValue($key, $val);
}
$count_stmt->execute();
$total_rows = $count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Count Total Amount (Filtered)
$sum_sql = "SELECT SUM(amount) FROM expenses $where";
$sum_stmt = $pdo->prepare($sum_sql);
foreach ($params as $key => $val) {
    $sum_stmt->bindValue($key, $val);
}
$sum_stmt->execute();
$total_filtered_amount = $sum_stmt->fetchColumn() ?: 0;

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary">Expenses Management</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="fas fa-plus me-2"></i> Add Expense
        </button>
    </div>
</div>

<?php 
if ($error_msg) echo "<div class='alert alert-danger'>$error_msg</div>";
display_flash_message(); 
?>

<!-- Filter Panel -->
<div class="card glass-panel border-0 mb-4">
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
                <label class="form-label small fw-bold">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="Product Purchase" <?php if($category=='Product Purchase') echo 'selected'; ?>>Product Purchase</option>
                    <option value="Utility Bills" <?php if($category=='Utility Bills') echo 'selected'; ?>>Utility Bills</option>
                    <option value="Rent" <?php if($category=='Rent') echo 'selected'; ?>>Rent</option>
                    <option value="Maintenance" <?php if($category=='Maintenance') echo 'selected'; ?>>Maintenance</option>
                    <option value="Salary" <?php if($category=='Salary') echo 'selected'; ?>>Salary</option>
                    <option value="Other" <?php if($category=='Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Description Search</label>
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <div class="col-12 text-end">
                <a href="expenses.php" class="btn btn-sm btn-link text-secondary">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<div class="card glass-panel border-0">
    <div class="card-body">
        
        <?php if(!empty($from_date) || !empty($to_date) || !empty($category) || !empty($search)): ?>
            <div class="alert alert-info py-2 d-flex justify-content-between align-items-center">
                <span><i class="fas fa-info-circle me-2"></i> Filtered Total Amount: <span class="fw-bold"><?php echo format_money($total_filtered_amount); ?></span></span>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expenses)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No expenses found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach($expenses as $ex): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($ex['expense_date'])); ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($ex['title']); ?></td>
                            <td class="text-secondary small"><?php echo htmlspecialchars($ex['description']); ?></td>
                            <td class="fw-bold text-danger"><?php echo format_money($ex['amount']); ?></td>
                            <td class="text-end">
                                <form method="POST" onsubmit="return confirm('Delete this expense?');" class="d-inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $ex['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-4">
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

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <select name="title" class="form-select" required> <!-- Providing common options + editable if needed, but select is safer for standardization -->
                            <option value="Product Purchase">Product Purchase</option>
                            <option value="Utility Bills">Utility Bills</option>
                            <option value="Rent">Rent</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Salary">Salary</option>
                            <option value="Other">Other</option>
                        </select>
                         <!-- Could add custom input if 'Other' selected, but keeping simple for now -->
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (BDT)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
