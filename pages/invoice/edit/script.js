// Auto-calculate row totals and overall summary
function calculateTotals() {
  let subtotal = 0;
  const rows = document.querySelectorAll(".item-row");
  rows.forEach((row) => {
    const qty = parseFloat(row.querySelector(".qty").value) || 0;
    const price = parseFloat(row.querySelector(".price").value) || 0;
    const total = qty * price;
    row.querySelector(".row-total").textContent = total.toFixed(2);
    subtotal += total;
  });

  const taxRate = parseFloat(document.getElementById("tax_rate").value) || 0;
  const taxAmount = subtotal * (taxRate / 100);
  const grandTotal = subtotal + taxAmount;

  document.getElementById("subtotal").textContent = subtotal.toFixed(2);
  document.getElementById("tax-amount").textContent = taxAmount.toFixed(2);
  document.getElementById("grand-total").textContent = grandTotal.toFixed(2);
}

// Add a new row
function addRow() {
  const tbody = document.getElementById("items-body");
  const firstRow = tbody.querySelector(".item-row");
  const newRow = firstRow.cloneNode(true);
  // Clear input values
  newRow.querySelectorAll("input").forEach((input) => {
    if (input.type === "number") {
      input.value = input.classList.contains("qty") ? 1 : 0.0;
    } else {
      input.value = "";
    }
  });
  newRow.querySelector(".row-total").textContent = "0.00";
  // Show remove button for all rows
  const removeBtn = newRow.querySelector(".btn-remove");
  removeBtn.style.display = "inline-block";
  tbody.appendChild(newRow);
  // Attach event listeners to new inputs
  attachEvents(newRow);
  calculateTotals();
}

// Remove a row
function removeRow(btn) {
  const row = btn.closest(".item-row");
  const tbody = document.getElementById("items-body");
  if (tbody.querySelectorAll(".item-row").length > 1) {
    row.remove();
    calculateTotals();
  } else {
    alert("You need at least one line item.");
  }
}

// Attach events to inputs in a row
function attachEvents(row) {
  row.querySelectorAll(".qty, .price").forEach((input) => {
    input.addEventListener("input", calculateTotals);
  });
}

// Attach events to all existing rows
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".item-row").forEach((row) => {
    attachEvents(row);
  });
  calculateTotals();
});

// Auto-update due date when invoice date changes
document.getElementById("invoice_date").addEventListener("change", function () {
  const date = new Date(this.value);
  if (!isNaN(date)) {
    date.setDate(date.getDate() + 15);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    document.getElementById("due_date").value = `${year}-${month}-${day}`;
  }
});
