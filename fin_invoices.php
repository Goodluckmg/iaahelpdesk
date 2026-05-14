<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Invoice Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/finance.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-coins"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="financeName">Mr. James Peter</div>
            <div class="user-role">💰 Finance Office</div>
            <div class="user-id" id="financeId">FIN/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="finance.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="fin_payments.php" class="nav-item" data-view="payments"><i class="fas fa-credit-card"></i><span class="nav-label">Payment Requests</span></a>
            <a href="fin_invoices.php" class="nav-item active" data-view="invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></a>
            <a href="fin_students.php" class="nav-item" data-view="students"><i class="fas fa-user-graduate"></i><span class="nav-label">Student Accounts</span></a>
            <a href="fin_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Invoice Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-file-invoice"></i><div class="stat-number" id="totalInvoices">0</div><div>Total Invoices</div></div>
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="unpaidInvoices">0</div><div>Unpaid</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="paidInvoices">0</div><div>Paid</div></div>
            <div class="stat-card"><i class="fas fa-coins"></i><div class="stat-number" id="outstandingAmount">TSh 0</div><div>Outstanding</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📄 Invoice List</strong>
                <button class="btn-primary" id="generateInvoiceBtn"><i class="fas fa-plus"></i> Generate New Invoice</button>
            </div>
            <div id="invoicesList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR GENERATING INVOICE -->
<div id="invoiceModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Generate New Invoice</h3><span class="close-modal">&times;</span></div>
        <form id="invoiceForm">
            <div class="form-group"><label>Student Registration Number</label><input type="text" id="invoiceStudentReg" required></div>
            <div class="form-group"><label>Student Name</label><input type="text" id="invoiceStudentName" required></div>
            <div class="form-group"><label>Amount (TSh)</label><input type="number" id="invoiceAmount" required></div>
            <div class="form-group"><label>Description</label><textarea id="invoiceDesc" rows="2" required></textarea></div>
            <div class="form-group"><label>Due Date</label><input type="date" id="invoiceDueDate" required></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Generate Invoice</button></div>
        </form>
    </div>
</div>

<script src="finance.js"></script>
<script>
    let invoices = [];
    let students = [];

    function loadData() {
        invoices = loadInvoices();
        students = loadStudentAccounts();
        updateStats();
        renderInvoices();
    }

    function updateStats() {
        const unpaid = invoices.filter(i => i.status === 'Unpaid').length;
        const paid = invoices.filter(i => i.status === 'Paid').length;
        const outstanding = invoices.filter(i => i.status === 'Unpaid').reduce((sum, i) => sum + i.amount, 0);
        
        document.getElementById('totalInvoices').innerText = invoices.length;
        document.getElementById('unpaidInvoices').innerText = unpaid;
        document.getElementById('paidInvoices').innerText = paid;
        document.getElementById('outstandingAmount').innerText = 'TSh ' + outstanding.toLocaleString();
    }

    function renderInvoices() {
        const container = document.getElementById('invoicesList');
        
        if (invoices.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No invoices generated yet.</div>';
            return;
        }
        
        container.innerHTML = invoices.map(i => `
            <div class="payment-item">
                <div class="flex-between">
                    <div class="payment-title">INV-${i.id} - ${i.studentName} (${i.studentReg})</div>
                    <span class="status-badge ${i.status === 'Paid' ? 'status-paid' : 'status-pending'}">${i.status}</span>
                </div>
                <div class="payment-meta">
                    <i class="fas fa-money-bill"></i> Amount: TSh ${i.amount.toLocaleString()} | 
                    <i class="fas fa-tag"></i> ${i.description} | 
                    <i class="fas fa-calendar"></i> Date: ${i.date} |
                    <i class="fas fa-hourglass-half"></i> Due: ${i.dueDate}
                </div>
                <div style="margin-top: 10px;">
                    <button class="btn-primary print-invoice" data-id="${i.id}" style="padding:4px 12px;"><i class="fas fa-print"></i> Print</button>
                    ${i.status === 'Unpaid' ? `<button class="btn-success mark-paid" data-id="${i.id}" style="padding:4px 12px;"><i class="fas fa-check"></i> Mark as Paid</button>` : ''}
                </div>
            </div>
        `).join('');
        
        document.querySelectorAll('.mark-paid').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id);
                markInvoicePaid(id);
            });
        });
        
        document.querySelectorAll('.print-invoice').forEach(btn => {
            btn.addEventListener('click', () => {
                alert('Print functionality would open invoice PDF');
            });
        });
    }

    function generateInvoice(invoiceData) {
        const newId = invoices.length > 0 ? Math.max(...invoices.map(i => i.id)) + 1 : 5001;
        const newInvoice = { id: newId, ...invoiceData, status: 'Unpaid', date: new Date().toLocaleDateString('en-US') };
        invoices.push(newInvoice);
        saveInvoices(invoices);
        loadData();
        alert(`Invoice INV-${newId} generated successfully!`);
    }

    function markInvoicePaid(invoiceId) {
        const invoice = invoices.find(i => i.id === invoiceId);
        if (invoice) {
            invoice.status = 'Paid';
            saveInvoices(invoices);
            loadData();
            alert(`Invoice INV-${invoiceId} marked as paid!`);
        }
    }

    document.getElementById('generateInvoiceBtn').addEventListener('click', () => {
        document.getElementById('invoiceForm').reset();
        document.getElementById('invoiceModal').style.display = 'flex';
    });

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('invoiceModal').style.display = 'none');
    });

    document.getElementById('invoiceForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const invoiceData = {
            studentReg: document.getElementById('invoiceStudentReg').value,
            studentName: document.getElementById('invoiceStudentName').value,
            amount: parseFloat(document.getElementById('invoiceAmount').value),
            description: document.getElementById('invoiceDesc').value,
            dueDate: document.getElementById('invoiceDueDate').value
        };
        
        if (!invoiceData.studentReg || !invoiceData.studentName || !invoiceData.amount) {
            alert('Please fill all required fields');
            return;
        }
        
        generateInvoice(invoiceData);
        document.getElementById('invoiceModal').style.display = 'none';
    });

    loadData();
</script>
</body>
</html>