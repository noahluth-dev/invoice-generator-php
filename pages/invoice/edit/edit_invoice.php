<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");

$user_id = $_SESSION['user_id'];
$invoice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($invoice_id <= 0) {
    header("Location: invoices.php");
    exit();
}

// Fetch invoice details
$sql = "SELECT * FROM invoices WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $invoice_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: invoices.php");
    exit();
}

$invoice = $result->fetch_assoc();

// Fetch existing line items
$item_sql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
$item_stmt = $conn->prepare($item_sql);
$item_stmt->bind_param("i", $invoice_id);
$item_stmt->execute();
$existing_items = $item_stmt->get_result();

// Fetch clients for dropdown
$client_query = "SELECT id, name FROM clients WHERE user_id = ? ORDER BY name";
$client_stmt = $conn->prepare($client_query);
$client_stmt->bind_param("i", $user_id);
$client_stmt->execute();
$clients = $client_stmt->get_result();

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $invoice_date = $_POST['invoice_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $tax_rate = floatval($_POST['tax_rate'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    $status = $_POST['status'] ?? 'draft';
    $descriptions = $_POST['description'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $unit_prices = $_POST['unit_price'] ?? [];

    // Validate
    if (empty($client_id)) {
        $errors[] = "Please select a client.";
    }
    if (empty($invoice_date)) {
        $errors[] = "Invoice date is required.";
    }
    if (empty($due_date)) {
        $errors[] = "Due date is required.";
    }
    if (empty($descriptions) || count(array_filter($descriptions)) === 0) {
        $errors[] = "At least one line item is required.";
    }

    if (empty($errors)) {
        // Calculate totals
        $subtotal = 0;
        $items = [];
        foreach ($descriptions as $index => $desc) {
            if (empty(trim($desc))) continue;
            $qty = floatval($quantities[$index] ?? 0);
            $price = floatval($unit_prices[$index] ?? 0);
            $total = $qty * $price;
            $subtotal += $total;
            $items[] = [
                'description' => trim($desc),
                'quantity' => $qty,
                'unit_price' => $price,
                'total' => $total
            ];
        }

        $tax_amount = $subtotal * ($tax_rate / 100);
        $grand_total = $subtotal + $tax_amount;

        // Update invoice in transaction
        $conn->begin_transaction();
        try {
            // Update invoice header
            $update_invoice = "UPDATE invoices SET 
                client_id = ?,
                invoice_date = ?,
                due_date = ?,
                subtotal = ?,
                tax_rate = ?,
                tax_amount = ?,
                total = ?,
                status = ?,
                notes = ?,
                updated_at = NOW()
                WHERE id = ? AND user_id = ?";
            
            $stmt = $conn->prepare($update_invoice);
            $stmt->bind_param(
                "issddddssii",
                $client_id,
                $invoice_date,
                $due_date,
                $subtotal,
                $tax_rate,
                $tax_amount,
                $grand_total,
                $status,
                $notes,
                $invoice_id,
                $user_id
            );
            $stmt->execute();

            // Delete existing items
            $delete_items = "DELETE FROM invoice_items WHERE invoice_id = ?";
            $del_stmt = $conn->prepare($delete_items);
            $del_stmt->bind_param("i", $invoice_id);
            $del_stmt->execute();

            // Insert updated items
            $insert_item = "INSERT INTO invoice_items 
                (invoice_id, description, quantity, unit_price, total) 
                VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $conn->prepare($insert_item);
            foreach ($items as $item) {
                $item_stmt->bind_param(
                    "isidd",
                    $invoice_id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total']
                );
                $item_stmt->execute();
            }

            $conn->commit();
            $success = true;
            header("Location: view_invoice.php?id=" . $invoice_id . "&msg=updated");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to update invoice: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <link rel="stylesheet" href="../../../global.css">
    <link rel="stylesheet" href="edit_invoice.css">
 
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Edit Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?></h3>
            </header>
            <div class="form-container">
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="invoice-form">
                <!-- Client and Dates -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_id">Client *</label>
                        <select name="client_id" id="client_id" required>
                            <option value="">-- Select Client --</option>
                            <?php while ($client = $clients->fetch_assoc()): ?>
                                <option value="<?php echo $client['id']; ?>" 
                                    <?php echo ($client['id'] == $invoice['client_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date *</label>
                        <input type="date" name="invoice_date" id="invoice_date" 
                               value="<?php echo $invoice['invoice_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="date" name="due_date" id="due_date" 
                               value="<?php echo $invoice['due_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Invoice Number</label>
                        <input type="text" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" disabled>
                    </div>
                </div>

                <!-- Line Items -->
                <h3>Line Items</h3>
                <table class="items-table" id="items-table">
                    <thead>
                        <tr>
                            <th style="width:50%;">Description</th>
                            <th style="width:15%;">Quantity</th>
                            <th style="width:20%;">Unit Price</th>
                            <th style="width:15%;">Total</th>
                            <th style="width:5%;"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        <?php 
                        $item_index = 0;
                        if ($existing_items->num_rows > 0): 
                            while ($item = $existing_items->fetch_assoc()): 
                        ?>
                            <tr class="item-row">
                                <td><input type="text" name="description[]" placeholder="Item description" 
                                           value="<?php echo htmlspecialchars($item['description']); ?>" required></td>
                                <td><input type="number" name="quantity[]" class="qty" 
                                           value="<?php echo $item['quantity']; ?>" min="1" step="1" required></td>
                                <td><input type="number" name="unit_price[]" class="price" 
                                           value="<?php echo $item['unit_price']; ?>" min="0" step="0.01" required></td>
                                <td><span class="row-total"><?php echo number_format($item['total'], 2); ?></span></td>
                                <td>
                                    <button type="button" class="btn-remove" 
                                            onclick="removeRow(this)" 
                                            <?php echo ($existing_items->num_rows <= 1) ? 'style="display:none;"' : ''; ?>>
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        <?php 
                            $item_index++;
                            endwhile; 
                        else: 
                        ?>
                            <tr class="item-row">
                                <td><input type="text" name="description[]" placeholder="Item description" required></td>
                                <td><input type="number" name="quantity[]" class="qty" value="1" min="1" step="1" required></td>
                                <td><input type="number" name="unit_price[]" class="price" value="0.00" min="0" step="0.01" required></td>
                                <td><span class="row-total">0.00</span></td>
                                <td><button type="button" class="btn-remove" onclick="removeRow(this)" style="display:none;">✕</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addRow()">+ Add Item</button>

                <!-- Status, Tax, and Notes -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="draft" <?php echo ($invoice['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="sent" <?php echo ($invoice['status'] == 'sent') ? 'selected' : ''; ?>>Sent</option>
                            <option value="viewed" <?php echo ($invoice['status'] == 'viewed') ? 'selected' : ''; ?>>Viewed</option>
                            <option value="paid" <?php echo ($invoice['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                            <option value="overdue" <?php echo ($invoice['status'] == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                            <option value="cancelled" <?php echo ($invoice['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tax_rate">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" 
                               value="<?php echo $invoice['tax_rate']; ?>" 
                               min="0" max="100" step="0.01" onchange="calculateTotals()">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="2"><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary">
                    <p>Subtotal: $<span id="subtotal"><?php echo number_format($invoice['subtotal'], 2); ?></span></p>
                    <p>Tax: $<span id="tax-amount"><?php echo number_format($invoice['tax_amount'], 2); ?></span></p>
                    <p class="grand-total">Grand Total: $<span id="grand-total"><?php echo number_format($invoice['total'], 2); ?></span></p>
                </div>

                <!-- Actions -->
                <div class="actions-row">
                    <button type="submit" class="btn-submit">Update Invoice</button>
                    <a href="/projects/invoice-generator-php/pages/invoice/view/view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn-cancel">Cancel</a>
                    <a href="delete/delete_invoice.php?id=<?php echo $invoice_id; ?>" 
                       class="btn-cancel" style="background:#ef4444;"
                       onclick="return confirm('Are you sure you want to delete this invoice?')">Delete</a>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script src="./script.js"></script>
</body>
</html>