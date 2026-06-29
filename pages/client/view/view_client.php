<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");

$user_id = $_SESSION['user_id'];
$client_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($client_id <= 0) {
    header("Location: ../clients.php");
    exit();
}

$sql = "SELECT * FROM clients WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $client_id, $user_id);
$stmt->execute();
$client = $stmt->get_result()->fetch_assoc();
if (!$client) {
    header("Location: ../clients.php?msg=notfound");
    exit();
}

// Get invoices for this client
$inv_sql = "SELECT id, invoice_number, invoice_date, total, status FROM invoices WHERE client_id = ? AND user_id = ? ORDER BY invoice_date DESC";
$inv_stmt = $conn->prepare($inv_sql);
$inv_stmt->bind_param("ii", $client_id, $user_id);
$inv_stmt->execute();
$invoices = $inv_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../global.css">
    <title>View Client</title>
    <style>
        .client-detail-card {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        .client-detail-card .client-name {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 10px 0;
        }
        .client-detail-card .client-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        @media (max-width: 600px) {
            .client-detail-card .client-meta {
                grid-template-columns: 1fr;
            }
        }
        .client-detail-card .meta-item {
            display: flex;
            flex-direction: column;
        }
        .client-detail-card .meta-item .label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .client-detail-card .meta-item .value {
            font-size: 1rem;
            color: #0f172a;
            margin-top: 3px;
            word-break: break-word;
        }
        .client-invoices {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
        }
        .client-invoices h3 {
            margin-top: 0;
            color: #0f172a;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .client-invoices table {
            width: 100%;
            border-collapse: collapse;
        }
        .client-invoices th {
            text-align: left;
            padding: 10px 8px;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
        }
        .client-invoices td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
        }
        .client-invoices tr:hover td {
            background: #f8fafc;
        }
        .client-invoices .status-badge {
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-draft { background: #e2e8f0; color: #475569; }
        .status-sent { background: #fef3c7; color: #92400e; }
        .status-viewed { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f1f5f9; color: #64748b; }
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn-back {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-back:hover {
            background: #cbd5e1;
        }
        .btn-edit {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-edit:hover {
            background: #d97706;
        }
        .btn-delete {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-delete:hover {
            background: #dc2626;
        }
        .empty-state {
            text-align: center;
            padding: 30px 0;
            color: #94a3b8;
        }
        .empty-state a {
            color: #3b82f6;
            text-decoration: none;
        }
        .empty-state a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
             <header class="page-header">
                <h3>Client details</h3>
            </header>
            <div style="margin:1rem;">
            <div class="client-detail-card">
                <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                <div class="client-meta">
                    <div class="meta-item">
                        <span class="label">Email</span>
                        <span class="value"><?php echo htmlspecialchars($client['email'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="label">Phone</span>
                        <span class="value"><?php echo htmlspecialchars($client['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="label">GSTIN / VAT</span>
                        <span class="value"><?php echo htmlspecialchars($client['gstin'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <span class="label">Address</span>
                        <span class="value">
                            <?php 
                            $address_parts = array_filter([$client['address'], $client['city'], $client['state'], $client['zip_code'], $client['country']]);
                            echo htmlspecialchars(implode(', ', $address_parts)) ?: 'N/A';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="client-invoices">
                <h3>📄 Invoices for this Client</h3>
                <?php if ($invoices->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($inv = $invoices->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($inv['invoice_number']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($inv['invoice_date'])); ?></td>
                                    <td>$<?php echo number_format($inv['total'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $inv['status']; ?>">
                                            <?php echo ucfirst($inv['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="/projects/invoice-generator-php/pages/invoice/view/view_invoice.php?id=<?php echo $inv['id']; ?>" class="btn-view">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <p>No invoices found for this client.</p>
                        <a href="../invoice/create/create_invoice.php?client_id=<?php echo $client_id; ?>">Create Invoice for this Client</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="btn-group">
                <a href="/projects/invoice-generator-php/pages/client/client.php" class="btn-back">← Back to Clients</a>
                <a href="/projects/invoice-generator-php/pages/client/edit/edit_client.php?id=<?php echo $client_id; ?>" class="btn-edit">✏️ Edit Client</a>
                <a href="/projects/invoice-generator-php/pages/client/delete/delete_client.php?id=<?php echo $client_id; ?>" 
                   class="btn-delete" 
                   onclick="return confirm('Are you sure you want to delete this client?')">🗑️ Delete Client</a>
            </div>
            </div>
        </div>
    </div>
</body>
</html>