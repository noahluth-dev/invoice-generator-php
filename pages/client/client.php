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
            $message = '<div class="alert alert-success">Client deleted successfully!</div>';
            break;
        case 'created':
            $message = '<div class="alert alert-success">Client created successfully!</div>';
            break;
        case 'updated':
            $message = '<div class="alert alert-success">Client updated successfully!</div>';
            break;
        case 'notfound':
            $message = '<div class="alert alert-danger">Client not found.</div>';
            break;
        case 'error':
            $message = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
            break;
        default:
            break;
    }
}

$user_id = $_SESSION['user_id'];

// --- SEARCH & PAGINATION ---

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination variables
$limit = 10; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$where_conditions = ["user_id = ?"];
$params = [$user_id];
$types = "i";

// Search by name, email, phone, or address
if (!empty($search)) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ssss";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM clients WHERE $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch clients
$sql = "SELECT 
            id,
            name,
            email,
            phone,
            address,
            city,
            state,
            created_at
        FROM clients
        WHERE $where_clause
        ORDER BY name ASC
        LIMIT ? OFFSET ?";

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
    <title>Clients</title>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Clients</h3>
                <a href="/projects/invoice-generator-php/pages/client/create/create_client.php" class="btn-create">+ Add Client</a>
            </header>

            <?php echo $message; ?>

            <!-- Search Bar -->
            <div class="filters-section">
                <div class="filter-group">
                    <label for="search">Search Clients</label>
                    <input type="text" name="search" id="search" 
                           placeholder="Name, Email, Phone, or Address..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           onkeyup="if(event.keyCode===13) applyFilters()">
                </div>
                <div class="filter-group" style="flex-direction:row; gap:8px; margin-top: 2rem;">
                    <button class="btn-filter" onclick="applyFilters()">Search</button>
                    <a href="client.php" class="btn-clear">Clear</a>
                </div>
            </div>

            <!-- Results Info -->
            <div class="results-info">
                Showing <?php echo min($total_rows, $offset + 1); ?> - 
                <?php echo min($offset + $limit, $total_rows); ?> of 
                <?php echo $total_rows; ?> clients
            </div>

            <!-- Table -->
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                                    <td class="client-address">
                                        <?php 
                                        $address_parts = array_filter([$row['address'], $row['city'], $row['state']]);
                                        echo htmlspecialchars(implode(', ', $address_parts));
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="view/view_client.php?id=<?php echo $row['id']; ?>" class="btn-view">View</a>
                                            <a href="edit/edit_client.php?id=<?php echo $row['id']; ?>" class="btn-edit">Edit</a>
                                            <a href="delete/delete_client.php?id=<?php echo $row['id']; ?>" 
                                               class="btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this client?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No clients found matching your criteria.</p>
                        <a href="create/create_client.php" class="btn-create" style="display:inline-block; margin-top:10px;">Add Client</a>
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
        function applyFilters() {
            const search = document.getElementById('search').value.trim();
            let params = new URLSearchParams();
            if (search) {
                params.append('search', search);
            }
            params.append('page', 1);
            window.location.href = 'client.php?' + params.toString();
        }
        document.getElementById('search').addEventListener('keyup', function(e) {
            if (e.keyCode === 13) {
                applyFilters();
            }
        });
    </script>
</body>
</html>