<?php
require '../config/db.php';
include '../includes/header.php';

// Session handling
session_start();
if (!isset($_SESSION['user_id']))
    header("Location: login.php");
$user_id = $_SESSION['user_id'];


// Handle Add
if (isset($_POST['add_expense'])) {
    $amount = $_POST['amount'];
    $note = $_POST['note'];
    $date = $_POST['date'];
    $category_id = $_POST['category_id'];

    $stmt = $pdo->prepare("INSERT INTO main.expense (user_id, category_id, amount, note, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $category_id, $amount, $note, $date]);
    header("Location: index.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM main.expense WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    header("Location: index.php");
    exit;
}

// Handle Edit (fetch and update)
$edit_expense = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM main.expense WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
    $edit_expense = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (isset($_POST['update_expense'])) {
    $id = $_POST['expense_id'];
    $amount = $_POST['amount'];
    $note = $_POST['note'];
    $date = $_POST['date'];
    $category_id = $_POST['category_id'];

    $stmt = $pdo->prepare("UPDATE main.expense SET category_id=?, amount=?, note=?, date=? WHERE id=? AND user_id=?");
    $stmt->execute([$category_id, $amount, $note, $date, $id, $user_id]);
    header("Location: index.php");
    exit;
}

// Fetch categories for dropdown
$cat_stmt = $pdo->prepare("SELECT id, name FROM main.categories WHERE user_id = ?");
$cat_stmt->execute([$user_id]);
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-4">
    <h2 class="mb-4 text-center">Expense Manager</h2>

    <!-- Graph and Summary data -->
    <div class="row mb-4">
        <?php
        $period = $_GET['period'] ?? 'month';
        $periods = ['day', 'week', 'month', 'year'];

        $user_id = $_SESSION['user_id'];

        // Determine date range
        switch ($period) {
            case 'day':
                $start_date = date('Y-m-d 00:00:00');
                $end_date = date('Y-m-d 23:59:59');
                $label = "Today";
                break;
            case 'week':
                $start_date = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $end_date = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                $label = "This Week";
                break;
            case 'year':
                $start_date = date('Y-01-01 00:00:00');
                $end_date = date('Y-12-31 23:59:59');
                $label = "This Year";
                break;
            case 'month':
            default:
                $start_date = date('Y-m-01 00:00:00');
                $end_date = date('Y-m-t 23:59:59');
                $label = "This Month";
                break;
        }

        // ====== TOTALS ======
        $queries = [
            'today' => "SELECT COALESCE(SUM(amount), 0) FROM main.expense WHERE user_id=? AND DATE(date)=CURRENT_DATE",
            'week' => "SELECT COALESCE(SUM(amount), 0) FROM main.expense WHERE user_id=? AND DATE_PART('week', date)=DATE_PART('week', CURRENT_DATE) AND DATE_PART('year', date)=DATE_PART('year', CURRENT_DATE)",
            'month' => "SELECT COALESCE(SUM(amount), 0) FROM main.expense WHERE user_id=? AND DATE_PART('month', date)=DATE_PART('month', CURRENT_DATE) AND DATE_PART('year', date)=DATE_PART('year', CURRENT_DATE)",
            'year' => "SELECT COALESCE(SUM(amount), 0) FROM main.expense WHERE user_id=? AND DATE_PART('year', date)=DATE_PART('year', CURRENT_DATE)"
        ];

        $totals = [];
        foreach ($queries as $key => $sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
            $totals[$key] = (float) $stmt->fetchColumn();
        }

        if ($period === 'week') {
            // daily chart
            $chart_sql = "
SELECT TO_CHAR(date, 'Dy') AS label, SUM(amount) AS total
FROM main.expense
WHERE user_id=? AND date BETWEEN ? AND ?
GROUP BY TO_CHAR(date, 'Dy')
ORDER BY MIN(date);
";
        } elseif ($period === 'month') {
            // date chart
            $chart_sql = "
            SELECT TO_CHAR(date, 'DD') AS label, SUM(amount) AS total
FROM main.expense
WHERE user_id=? AND date BETWEEN ? AND ?
GROUP BY TO_CHAR(date, 'DD')
ORDER BY TO_CHAR(date, 'DD')::int;
";
        } else {
            // monthly chart
            $chart_sql = "
            SELECT TO_CHAR(date, 'Mon') AS label, SUM(amount) AS total
FROM main.expense
WHERE user_id=? AND date BETWEEN ? AND ?
GROUP BY TO_CHAR(date, 'Mon')
ORDER BY MIN(date);
";
        }

        $stmt = $pdo->prepare($chart_sql);
        $stmt->execute([$user_id, $start_date, $end_date]);
        $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = array_column($chart_data, 'label');
        $values = array_map('floatval', array_column($chart_data, 'total'));
        ?>

        <!-- Graph -->
        <div class="col-md-8">
            <div class="card shadow-sm border rounded-3 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Expense Trends (<?= $label ?>)</h5>
                    <div>
                        <?php
                        $currIndex = array_search($period, $periods);
                        $prevPeriod = $periods[max(0, $currIndex - 1)];
                        $nextPeriod = $periods[min(count($periods) - 1, $currIndex + 1)];
                        ?>
                        <a href="?period=<?= $prevPeriod ?>" class="btn btn-sm btn-outline-secondary">&lt;</a>
                        <a href="?period=<?= $nextPeriod ?>" class="btn btn-sm btn-outline-secondary">&gt;</a>
                    </div>
                </div>
                <canvas id="expensesChart" height="100"></canvas>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-4">
            <div class="card shadow-sm border rounded-3 p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Summary</h5>
                    <div>
                        <a href="?period=<?= $prevPeriod ?>" class="btn btn-sm btn-outline-secondary">&lt;</a>
                        <a href="?period=<?= $nextPeriod ?>" class="btn btn-sm btn-outline-secondary">&gt;</a>
                    </div>
                </div>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Today
                        <span class="badge bg-primary">₹<?= number_format($totals['today'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        This Week
                        <span class="badge bg-success">₹<?= number_format($totals['week'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        This Month
                        <span class="badge bg-warning text-dark">₹<?= number_format($totals['month'], 2) ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        This Year
                        <span class="badge bg-info text-dark">₹<?= number_format($totals['year'], 2) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('expensesChart').getContext('2d');
        const expensesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Expenses (₹)',
                    data: <?= json_encode($values) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>



    <!-- Add / Edit Form -->
    <div class="card mb-4">
        <div class="card-header bg-dark text-light">
            <?= $edit_expense ? 'Edit Expense' : 'Add New Expense' ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php if ($edit_expense): ?>
                    <input type="hidden" name="expense_id" value="<?= $edit_expense['id'] ?>">
                <?php endif; ?>

                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" name="category_id" required>
                        <option value="">Select</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $edit_expense && $edit_expense['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" class="form-control" name="amount"
                        value="<?= $edit_expense['amount'] ?? '' ?>" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Note</label>
                    <input type="text" class="form-control" name="note"
                        value="<?= htmlspecialchars($edit_expense['note'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date"
                        value="<?= $edit_expense['date'] ?? date('Y-m-d') ?>" required>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <?php if ($edit_expense): ?>
                        <button type="submit" name="update_expense" class="btn btn-success w-100">Update</button>
                    <?php else: ?>
                        <button type="submit" name="add_expense" class="btn btn-primary w-100">Add</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Compute start and end for queries based on filters for table based view -->
    <?php
    // Mode (day/week/month/year)
    $mode = $_GET['mode'] ?? 'day';
    $selected_category = $_GET['category_id'] ?? 'all';

    // Selected date
    $current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $current = new DateTime($current_date);

    // Compute start and end based on mode
    switch ($mode) {
        case 'week':
            $start = clone $current;
            $start->modify('monday this week');
            $end = clone $start;
            $end->modify('+6 days');
            $label = "Week of " . $start->format('d M') . " - " . $end->format('d M Y');
            break;

        case 'month':
            $start = new DateTime($current->format('Y-m-01'));
            $end = clone $start;
            $end->modify('last day of this month');
            $label = $start->format('F Y');
            break;

        case 'year':
            $start = new DateTime($current->format('Y-01-01'));
            $end = new DateTime($current->format('Y-12-31'));
            $label = $current->format('Y');
            break;

        case 'all':
            $label = "All Expenses";
            $start = new DateTime('2000-01-01'); // or earliest possible date
            $end = new DateTime();   // ensures we fetch everything
            break;

        default: // day
            $start = $current;
            $end = $current;
            $label = $current->format('d M Y');
    }

    $query = "
    SELECT e.id, e.amount, e.note, e.date, c.name AS category
    FROM main.expense e
    LEFT JOIN main.categories c ON e.category_id = c.id
    WHERE e.user_id = ?
    AND e.date BETWEEN ? AND ?
";
    // Build query
    $params = [$user_id, $start->format('Y-m-d'), $end->format('Y-m-d')];

    // Add category condition if selected
    if ($selected_category !== 'all') {
        $query .= " AND e.category_id = ?";
        $params[] = $selected_category;
    }

    $query .= " ORDER BY e.date DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compute previous and next dates
    $prev = clone $current;
    $next = clone $current;

    switch ($mode) {
        case 'week':
            $prev->modify('-1 week');
            $next->modify('+1 week');
            break;
        case 'month':
            $prev->modify('-1 month');
            $next->modify('+1 month');
            break;
        case 'year':
            $prev->modify('-1 year');
            $next->modify('+1 year');
            break;
        default:  //day
            $prev->modify('-1 day');
            $next->modify('+1 day');

    }
    ?>

    <!-- Expense List -->
    <div class="card">
        <div class="card-header bg-dark text-white">Your Expenses</div>
        <div class="d-flex justify-content-between align-items-center my-3 p-2">
            <div>
                <?php if ($mode !== 'all'): ?>
                    <a href="?mode=<?= $mode ?>&date=<?= $prev->format('Y-m-d') ?>"
                        class="btn btn-outline-secondary">&lt;</a>
                    <strong class="mx-2"><?= $label ?></strong>
                    <a href="?mode=<?= $mode ?>&date=<?= $next->format('Y-m-d') ?>"
                        class="btn btn-outline-secondary">&gt;</a>
                <?php else: ?>
                    <button class="btn btn-outline-secondary" disabled>&lt;</button>
                    <strong class="mx-2"><?= $label ?></strong>
                    <button class="btn btn-outline-secondary" disabled>&gt;</button>
                <?php endif; ?>
            </div>


            <div class="d-flex align-items-center gap-2 p-2">

                <!-- Category Filter Dropdown -->
                <form method="GET" class="d-flex align-items-center">
                    <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($current->format('Y-m-d')) ?>">
                    <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $selected_category === 'all' ? 'selected' : '' ?>>All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $selected_category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <a href="?mode=day"
                    class="btn btn-sm <?= $mode == 'day' ? 'btn-primary' : 'btn-outline-primary' ?>">Day</a>
                <a href="?mode=week"
                    class="btn btn-sm <?= $mode == 'week' ? 'btn-primary' : 'btn-outline-primary' ?>">Week</a>
                <a href="?mode=month"
                    class="btn btn-sm <?= $mode == 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">Month</a>
                <a href="?mode=year"
                    class="btn btn-sm <?= $mode == 'year' ? 'btn-primary' : 'btn-outline-primary' ?>">Year</a>
                <a href="?mode=all"
                    class="btn btn-sm <?= $mode == 'all' ? 'btn-secondary' : 'btn-outline-secondary' ?>">All</a>

            </div>
        </div>

        <div class="card-body  border border-secondary rounded-3 p-3 mx-3 mb-3">
            <?php if (count($expenses) === 0): ?>
                <p class="text-muted">No expenses yet. Start by adding one above.</p>
            <?php else: ?>
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $exp): ?>
                            <tr>
                                <td><?= htmlspecialchars($exp['date']) ?></td>
                                <td><?= htmlspecialchars($exp['category'] ?? 'Uncategorized') ?></td>
                                <td>₹<?= number_format($exp['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($exp['note']) ?></td>
                                <td>
                                    <a href="?edit=<?= $exp['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="?delete=<?= $exp['id'] ?>" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Delete this expense?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>




<?php include '../includes/footer.php'; ?>