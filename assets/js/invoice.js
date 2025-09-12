


// Auto update footer year
document.getElementById("footerYear").textContent = new Date().getFullYear();

// Sample invoice data (replace with backend fetch via PHP)
const invoices = [
  { id: 1, date: '2025-09-01', plan: '1 Year Membership', amount: 1200, status: 'Paid' },
  { id: 2, date: '2024-09-01', plan: '1 Year Membership', amount: 1200, status: 'Paid' },
];

// Render invoices
const invoiceList = document.getElementById("invoiceList");

function renderInvoices() {
  invoiceList.innerHTML = ''; // Clear previous content
  if (invoices.length === 0) {
    invoiceList.innerHTML = '<p>No invoices available.</p>';
    return;
  }

  invoices.forEach(inv => {
    const item = document.createElement('div');
    item.classList.add('invoice-item');
    item.innerHTML = `
      <p><strong>Invoice #${inv.id}</strong></p>
      <p>${inv.date}</p>
      <p>${inv.plan}</p>
      <p>₱${inv.amount}</p>
      <p>Status: ${inv.status}</p>
    `;
    invoiceList.appendChild(item);
  });
}

renderInvoices();
