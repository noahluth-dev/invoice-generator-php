<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../db.php");

// Display status messages
$message = '';
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'deleted':
            $message = '<div class="alert alert-success">Invoice deleted successfully!</div>';
            break;
        case 'created':
            $message = '<div class="alert alert-success">Invoice created successfully!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">Invoice updated successfully!</div>';
            break;
        case 'notfound':
            $message = '<div class="alert alert-danger">Invoice not found.</div>';
            break;
        case 'error':
            $message = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
        case 'invalid':
            $message = '<div class="alert alert-danger">Invalid invoice ID.</div>';
            break;
        default:
            break;
    }
}

$user_id = $_SESSION['user_id'];

// --- SEARCH, FILTER, PAGINATION ---

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Pagination variables
$limit = 10; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build the WHERE clause dynamically
$where_conditions = ["i.user_id = ?"];
$params = [$user_id];
$types = "i";

// Search by invoice number or client name
if (!empty($search)) {
    $where_conditions[] = "(i.invoice_number LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Filter by status
if (!empty($status_filter)) {
    $where_conditions[] = "i.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Filter by date range
if (!empty($date_from)) {
    $where_conditions[] = "i.invoice_date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $where_conditions[] = "i.invoice_date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM invoices i
              LEFT JOIN clients c ON i.client_id = c.id
              WHERE $where_clause";

$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch invoices with client name
$sql = "SELECT 
            i.id,
            i.invoice_number,
            i.invoice_date,
            i.due_date,
            i.subtotal,
            i.tax_rate,
            i.tax_amount,
            i.total,
            i.status,
            i.created_at,
            c.name AS client_name
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE $where_clause
        ORDER BY i.invoice_date DESC
        LIMIT ? OFFSET ?";

// Add limit and offset to parameters
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../global.css">
    <title>Invoices</title>
    <link rel="stylesheet" href="invoices.css">
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Invoices</h3>
                <a href="create/create_invoice.php" class="btn-create">+ Create Invoice</a>
            </header>

            <?php echo $message; ?>

            <!-- Search & Filters -->
            <div class="filters-section">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" 
                           placeholder="Invoice # or Client..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeyup="if(event.keyCode===13) applyFilters()">
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="draft" <?php echo ($status_filter == 'draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="sent" <?php echo ($status_filter == 'sent') ? 'selected' : ''; ?>>Sent</option>
                        <option value="viewed" <?php echo ($status_filter == 'viewed') ? 'selected' : ''; ?>>Viewed</option>
                        <option value="paid" <?php echo ($status_filter == 'paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo ($status_filter == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                        <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="date_from">Date From</label>
                    <input type="date" name="date_from" id="date_from" 
                           value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="filter-group">
                    <label for="date_to">Date To</label>
                    <input type="date" name="date_to" id="date_to" 
                           value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                <div class="filter-group" style="flex-direction:row; gap:8px; margin-top: 2rem;">
                    <button class="btn-filter" onclick="applyFilters()">Apply Filters</button>
                    <a href="invoices.php" class="btn-clear">Clear</a>
                </div>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                Showing <?php echo min($total_rows, $offset + 1); ?> - 
                <?php echo min($offset + $limit, $total_rows); ?> of 
                <?php echo $total_rows; ?> invoices
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['invoice_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['client_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['invoice_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                    <td><strong>$<?php echo number_format($row['total'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="view/view_invoice.php?id=<?php echo $row['id']; ?>" class="btn-view">View</a>
                                            <a href="edit/edit_invoice.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                            <a href="delete/delete_invoice.php?id=<?php echo $row['id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No invoices found matching your criteria.</p>
                        <a href="/projects/invoice-generator-php/pages/invoice/create/create_invoice.php" class="btn-create" style="display:inline-block; margin-top:10px;">Create Invoice</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">‹ Prev</a>
                    <?php else: ?>
                        <span class="disabled">‹ Prev</span>
                    <?php endif; ?>

                    <?php 
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    if ($start_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                        <?php if ($start_page > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next ›</a>
                    <?php else: ?>
                        <span class="disabled">Next ›</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Apply filters (reload page with filter parameters)
        function applyFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status').value;
            const date_from = document.getElementById('date_from').value;
            const date_to = document.getElementById('date_to').value;
            
            let params = new URLSearchParams();
            if (search) params.append('search', search);
            if (status) params.append('status', status);
            if (date_from) params.append('date_from', date_from);
            if (date_to) params.append('date_to', date_to);
            params.append('page', 1);
            
            window.location.href = 'invoices.php?' + params.toString();
        }

        // Auto-submit when pressing Enter in search field
        document.getElementById('search').addEventListener('keyup', function(e) {
            if (e.keyCode === 13) {
                applyFilters();
            }
        });
    </script>
</body>
</html>