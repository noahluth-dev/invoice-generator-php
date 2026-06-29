<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");
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
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    header("Location: invoices.php");
    exit();
}

// Fetch invoice details with client info
$sql = "SELECT 
            i.*,
            c.name AS client_name,
            c.email AS client_email,
            c.phone AS client_phone,
            c.address AS client_address,
            c.gstin AS client_gstin
        FROM invoices i
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.id = ? AND i.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: invoices.php");
    exit();
}

$invoice = $result->fetch_assoc();

// Fetch line items
$item_sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $invoice_id);
$item_stmt->execute();
$items = $item_stmt->get_result();

// Get user business info from settings (optional)
$settings_sql = "SELECT * FROM company_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_sql);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings = $settings_stmt->get_result()->fetch_assoc();

// Calculate status badge class
function getStatusClass($status) {
    $classes = [
        'draft' => 'status-draft',
        'sent' => 'status-sent',
        'viewed' => 'status-viewed',
        'paid' => 'status-paid',
        'overdue' => 'status-overdue',
        'cancelled' => 'status-cancelled'
    ];
    return $classes[$status] ?? 'status-draft';
}

// Handle status update (mark as paid)
if (isset($_GET['action']) && $_GET['action'] === 'mark_paid') {
    $update_sql = "UPDATE invoices SET status = 'paid', updated_at = NOW() WHERE id = ? AND user_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $invoice_id, $user_id);
    $update_stmt->execute();
    header("Location: view_invoice.php?id=" . $invoice_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link rel="stylesheet" href="../../../global.css">
    <link rel="stylesheet" href="view_invoice.css">

</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
            <?php echo $message; ?>
            <div class="invoice-wrapper" id="invoice-content">
                <!-- Invoice Header -->
                <div class="invoice-header">
                    <div class="invoice-title">
                        <h1>INVOICE</h1>
                        <div class="invoice-number">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
                        <span class="status-badge <?php echo getStatusClass($invoice['status']); ?>">
                            <?php echo ucfirst($invoice['status']); ?>
                        </span>
                    </div>
                    <div class="company-info">
                        <h2><?php echo htmlspecialchars($settings['business_name'] ?? 'Your Business'); ?></h2>
                        <?php if (!empty($settings['business_address'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($settings['business_address'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['business_phone'])): ?>
                            <p>Phone: <?php echo htmlspecialchars($settings['business_phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['business_email'])): ?>
                            <p>Email: <?php echo htmlspecialchars($settings['business_email']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['business_gstin'])): ?>
                            <p>GSTIN: <?php echo htmlspecialchars($settings['business_gstin']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="invoice-details">
                    <div class="client-info">
                        <h3>Bill To</h3>
                        <p class="client-name"><?php echo htmlspecialchars($invoice['client_name']); ?></p>
                        <?php if (!empty($invoice['client_address'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($invoice['client_address'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($invoice['client_email'])): ?>
                            <p>Email: <?php echo htmlspecialchars($invoice['client_email']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($invoice['client_phone'])): ?>
                            <p>Phone: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($invoice['client_gstin'])): ?>
                            <p>GSTIN: <?php echo htmlspecialchars($invoice['client_gstin']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="invoice-meta">
                        <h3>Invoice Details</h3>
                        <p><strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($invoice['invoice_date'])); ?></p>
                        <p><strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?></p>
                        <?php if ($invoice['status'] === 'paid' && !empty($invoice['amount_paid'])): ?>
                            <p><strong>Amount Paid:</strong> $<?php echo number_format($invoice['amount_paid'], 2); ?></p>
                        <?php endif; ?>
                        <p><strong>Status:</strong> <?php echo ucfirst($invoice['status']); ?></p>
                    </div>
                </div>

                <!-- Line Items -->
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width:50%;">Description</th>
                            <th style="width:15%;" class="text-right">Quantity</th>
                            <th style="width:20%;" class="text-right">Unit Price</th>
                            <th style="width:15%;" class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while ($item = $items->fetch_assoc()): 
                            $subtotal += $item['total'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td class="text-right"><?php echo number_format($item['quantity'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right">$<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" class="text-right"><strong>Subtotal</strong></td>
                            <td class="text-right"><strong>$<?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-right">Tax (<?php echo number_format($invoice['tax_rate'], 2); ?>%)</td>
                            <td class="text-right">$<?php echo number_format($invoice['tax_amount'], 2); ?></td>
                        </tr>
                        <?php if (!empty($invoice['discount_amount']) && $invoice['discount_amount'] > 0): ?>
                            <tr>
                                <td colspan="3" class="text-right">Discount</td>
                                <td class="text-right">-$<?php echo number_format($invoice['discount_amount'], 2); ?></td>
                            </tr>
                        <?php endif; ?>
                        <tr class="grand-total">
                            <td colspan="3" class="text-right"><strong>Grand Total</strong></td>
                            <td class="text-right"><strong>$<?php echo number_format($invoice['total'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Notes -->
                <?php if (!empty($invoice['notes'])): ?>
                    <div class="invoice-footer">
                        <div class="invoice-notes">
                            <h4>Notes</h4>
                            <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Actions -->
            <div class="invoice-actions no-print">
                <a href="/projects/invoice-generator-php/pages/invoice/invoices.php" class="btn-back">← Back to Invoices</a>
                
                <!-- Print -->
                <button onclick="window.print()" class="btn-print">🖨️ Print</button>
                
                <!-- PDF (coming soon) -->
                <a href="pdf_invoice.php?id=<?php echo $invoice_id; ?>" class="btn-pdf">📄 PDF</a>
                
                <!-- Edit -->
                <a href="/projects/invoice-generator-php/pages/invoice/edit/edit_invoice.php?id=<?php echo $invoice_id; ?>" class="btn-edit">✏️ Edit</a>
                
                <!-- Delete -->
                <a href="/projects/invoice-generator-php/pages/invoice/delete/delete_invoice.php?id=<?php echo $invoice_id; ?>" 
                   class="btn-delete" 
                   onclick="return confirm('Are you sure you want to delete this invoice?')">🗑️ Delete</a>
                
                <!-- Mark as Paid (only if not already paid) -->
                <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
                    <a href="view_invoice.php?id=<?php echo $invoice_id; ?>&action=mark_paid" 
                       class="btn-paid" 
                       onclick="return confirm('Mark this invoice as paid?')">✅ Mark as Paid</a>
                <?php endif; ?>
            </div>
            </div>

            
        </div>
    </div>
</body>
</html>