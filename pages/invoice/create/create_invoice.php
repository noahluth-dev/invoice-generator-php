<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include("../../../db.php");

$user_id = $_SESSION['user_id'];

// Fetch clients for dropdown
$client_query = "SELECT id, name FROM clients WHERE user_id = ? ORDER BY name";
$client_stmt = $conn->prepare($client_query);
$client_stmt->bind_param("i", $user_id);
$client_stmt->execute();
$clients = $client_stmt->get_result();

// Generate next invoice number
function getNextInvoiceNumber($conn, $user_id) {
    $year = date('Y');
    $month = date('m');
    $prefix = "INV-{$year}{$month}-";
    $query = "SELECT MAX(invoice_number) as last FROM invoices WHERE user_id = ? AND invoice_number LIKE ?";
    $stmt = $conn->prepare($query);
    $like = $prefix . '%';
    $stmt->bind_param("is", $user_id, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if ($row['last']) {
        $last_number = intval(substr($row['last'], -3));
        $next = str_pad($last_number + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $next = '001';
    }
    return $prefix . $next;
}

$invoice_number = getNextInvoiceNumber($conn, $user_id);
$today = date('Y-m-d');
$default_due = date('Y-m-d', strtotime('+15 days'));

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $invoice_date = $_POST['invoice_date'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    $tax_rate = floatval($_POST['tax_rate'] ?? 0);
    $notes = $_POST['notes'] ?? '';
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

        // Insert invoice header
        $conn->begin_transaction();
        try {
    // Insert invoice header
    $status = 'draft';
    $insert_invoice = "INSERT INTO invoices 
        (user_id, client_id, invoice_number, invoice_date, due_date, 
         subtotal, tax_rate, tax_amount, total, status, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_invoice);
    $stmt->bind_param(
        "iisssddddss",
        $user_id,
        $client_id,
        $invoice_number,
        $invoice_date,
        $due_date,
        $subtotal,
        $tax_rate,
        $tax_amount,
        $grand_total,
        $status,
        $notes
    );
    $stmt->execute();
    $invoice_id = $conn->insert_id;
    $stmt->close();

    // Insert line items
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
    $item_stmt->close();

    $conn->commit();
    $success = true;
    header("Location: /projects/invoice-generator-php/pages/invoice/invoices.php?msg=created");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $errors[] = "Failed to create invoice: " . $e->getMessage();
}
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../global.css">
    <link rel="stylesheet" href="../edit/edit_invoice.css">
    <title>Create Invoice</title>
    <style>

        .actions-row {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}
.btn-cancel {
  background: #64748b;
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: .5rem;
  cursor: pointer;
  font-weight: 600;
  font-size: 1.4rem;
  text-decoration: none;
  display: flex;
  align-items: center;
}
    </style>
</head>
<body>
    <div class="main-wrapper">
        <?php include '../../../components/navbar.php'; ?>
        <div class="content">
            <header class="page-header">
                <h3>Create New Invoice</h3>
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
                                    <?php echo (isset($_POST['client_id']) && $_POST['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Invoice Date *</label>
                        <input type="date" name="invoice_date" id="invoice_date" 
                               value="<?php echo $_POST['invoice_date'] ?? $today; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="date" name="due_date" id="due_date" 
                               value="<?php echo $_POST['due_date'] ?? $default_due; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Invoice Number</label>
                        <input type="text" value="<?php echo $invoice_number; ?>" disabled>
                        <input type="hidden" name="invoice_number" value="<?php echo $invoice_number; ?>">
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
                        <!-- Initial row (will be cloned) -->
                        <tr class="item-row">
                            <td><input type="text" name="description[]" placeholder="Item description" required></td>
                            <td><input type="number" name="quantity[]" class="qty" value="1" min="1" step="1" required></td>
                            <td><input type="number" name="unit_price[]" class="price" value="0.00" min="0" step="0.01" required></td>
                            <td><span class="row-total">0.00</span></td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this)" style="display:none;">✕</button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="addRow()">+ Add Item</button>

                <!-- Tax and Notes -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="tax_rate">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" 
                               value="<?php echo $_POST['tax_rate'] ?? 0; ?>" 
                               min="0" max="100" step="0.01" onchange="calculateTotals()">
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea name="notes" id="notes" rows="2"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Summary -->
                <div class="summary">
                    <p>Subtotal: $<span id="subtotal">0.00</span></p>
                    <p>Tax: $<span id="tax-amount">0.00</span></p>
                    <p class="grand-total">Grand Total: $<span id="grand-total">0.00</span></p>
                </div>
                <div class="actions-row">
                    <button type="submit" class="btn-submit">Create Invoice</button>
                    <a href="/projects/invoice-generator-php/pages/invoice/invoices.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script src="script.js">
        
    </script>
</body>
</html>