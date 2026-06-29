<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../db.php");

$user_id = $_SESSION['user_id'];

// --- DASHBOARD STATS ---

// 1. Total Invoices
$total_invoices_sql = "SELECT COUNT(*) as count FROM invoices WHERE user_id = ?";
$stmt = $conn->prepare($total_invoices_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_invoices = $stmt->get_result()->fetch_assoc()['count'];

// 2. Total Clients
$total_clients_sql = "SELECT COUNT(*) as count FROM clients WHERE user_id = ?";
$stmt = $conn->prepare($total_clients_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_clients = $stmt->get_result()->fetch_assoc()['count'];

// 3. Total Revenue (sum of all paid invoices)
$total_revenue_sql = "SELECT SUM(total) as revenue FROM invoices WHERE user_id = ? AND status = 'paid'";
$stmt = $conn->prepare($total_revenue_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['revenue'] ?? 0;

// 4. Pending Payments (unpaid invoices)
$pending_sql = "SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND status NOT IN ('paid', 'cancelled')";
$stmt = $conn->prepare($pending_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_count = $stmt->get_result()->fetch_assoc()['count'];

// 5. Overdue Invoices
$overdue_sql = "SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND status = 'overdue'";
$stmt = $conn->prepare($overdue_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$overdue_count = $stmt->get_result()->fetch_assoc()['count'];

// 6. Recent Invoices (last 5)
$recent_sql = "SELECT 
                    i.id,
                    i.invoice_number,
                    i.invoice_date,
                    i.total,
                    i.status,
                    c.name AS client_name
                FROM invoices i
                LEFT JOIN clients c ON i.client_id = c.id
                WHERE i.user_id = ?
                ORDER BY i.created_at DESC
                LIMIT 5";
$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_invoices = $stmt->get_result();

// 7. Monthly Revenue Chart Data (last 6 months)
$chart_sql = "SELECT 
                    DATE_FORMAT(invoice_date, '%b') as month,
                    YEAR(invoice_date) as year,
                    SUM(total) as revenue
                FROM invoices
                WHERE user_id = ? 
                    AND status = 'paid'
                    AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                GROUP BY YEAR(invoice_date), MONTH(invoice_date)
                ORDER BY YEAR(invoice_date), MONTH(invoice_date)";
$stmt = $conn->prepare($chart_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$chart_data = $stmt->get_result();

// Prepare chart data for JavaScript
$months = [];
$revenues = [];
while ($row = $chart_data->fetch_assoc()) {
    $months[] = $row['month'] . ' ' . $row['year'];
    $revenues[] = $row['revenue'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../global.css">
    <link rel="stylesheet" href="dashboard.css">
    <title>Dashboard</title>

</head>
<body>
    <div class="main-wrapper">
        <?php include '../components/navbar.php'; ?>
        <div class="content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>! 👋</h1>
                <p style="color:var(--ghost-white);font-size:1.2rem;">Here's an overview of your invoice activity.</p>
            </div>

            <!-- Stats Grid -->
            <div class="dashboard-grid">
                <div class="stat-card stat-invoices">
                    <span class="stat-icon">📄</span>
                    <div class="stat-label">Total Invoices</div>
                    <div class="stat-number"><?php echo number_format($total_invoices); ?></div>
                </div>
                <div class="stat-card stat-clients">
                    <span class="stat-icon">👤</span>
                    <div class="stat-label">Total Clients</div>
                    <div class="stat-number"><?php echo number_format($total_clients); ?></div>
                </div>
                <div class="stat-card stat-revenue">
                    <span class="stat-icon">💰</span>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                </div>
                <div class="stat-card stat-pending">
                    <span class="stat-icon">⏳</span>
                    <div class="stat-label">Pending Payments</div>
                    <div class="stat-number"><?php echo number_format($pending_count); ?></div>
                </div>
                <div class="stat-card stat-overdue">
                    <span class="stat-icon">🔴</span>
                    <div class="stat-label">Overdue</div>
                    <div class="stat-number"><?php echo number_format($overdue_count); ?></div>
                </div>
            </div>

            <!-- Row: Recent Invoices + Quick Actions -->
            <div class="dashboard-row">
                <!-- Recent Invoices -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3>📋 Recent Invoices</h3>
                        <a href="invoice/invoices.php">View All →</a>
                    </div>
                    <?php if ($recent_invoices->num_rows > 0): ?>
                        <table class="recent-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($invoice = $recent_invoices->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <a href="invoice/view/view_invoice.php?id=<?php echo $invoice['id']; ?>" 
                                               style="color:#3b82f6; text-decoration:none; font-weight:600;">
                                                <?php echo htmlspecialchars($invoice['invoice_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></td>
                                        <td>$<?php echo number_format($invoice['total'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                                <?php echo ucfirst($invoice['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="color:#94a3b8; text-align:center; padding:20px 0;">
                            No invoices yet. <a href="invoice/create_invoice.php" style="color:#3b82f6;">Create one now!</a>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="dashboard-card">
                    <h3>⚡ Quick Actions</h3>
                    <div class="quick-actions">
                        <a href="invoice/create_invoice.php" class="btn-primary">+ Create New Invoice</a>
                        <a href="client/add_client.php" class="btn-success">+ Add New Client</a>
                        <a href="client/clients.php" class="btn-secondary">👤 Manage Clients</a>
                        <a href="invoice/invoices.php" class="btn-secondary">📄 View All Invoices</a>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="dashboard-card" style="margin-top:20px;">
                <h3>📊 Monthly Revenue (Last 6 Months)</h3>
                <div class="chart-container">
                    <?php if (!empty($months)): ?>
                        <div class="chart-bars" id="chart-bars">
                            <?php 
                            $max_revenue = max($revenues) > 0 ? max($revenues) : 1;
                            foreach ($months as $index => $month): 
                                $height_percent = ($revenues[$index] / $max_revenue) * 100;
                            ?>
                                <div class="chart-bar-wrapper">
                                    <div class="chart-bar-value">$<?php echo number_format($revenues[$index], 0); ?></div>
                                    <div class="chart-bar" style="height: <?php echo max($height_percent, 4); ?>%;"></div>
                                    <div class="chart-bar-label"><?php echo htmlspecialchars($month); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-chart-data">
                            <p>No revenue data available yet.</p>
                            <p style="font-size:0.85rem; margin-top:5px;">Start creating invoices to see your earnings!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>