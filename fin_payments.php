<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Payment Requests</title>
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
            <a href="fin_payments.php" class="nav-item active" data-view="payments"><i class="fas fa-credit-card"></i><span class="nav-label">Payment Requests</span></a>
            <a href="fin_invoices.php" class="nav-item" data-view="invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></a>
            <a href="fin_students.php" class="nav-item" data-view="students"><i class="fas fa-user-graduate"></i><span class="nav-label">Student Accounts</span></a>
            <a href="fin_reports.html" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Payment Requests Management</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="pendingCount">0</div><div>Pending</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="completedCount">0</div><div>Completed</div></div>
            <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="stat-number" id="overdueCount">0</div><div>Overdue</div></div>
            <div class="stat-card"><i class="fas fa-coins"></i><div class="stat-number" id="pendingAmount">TSh 0</div><div>Pending Amount</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>💰 All Payment Requests</strong>
                <div>
                    <select id="filterStatus" style="width:140px; margin-right:10px;">
                        <option value="all">All Payments</option>
                        <option value="Pending">Pending</option>
                        <option value="Completed">Completed</option>
                        <option value="Overdue">Overdue</option>
                    </select>
                    <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>
            <div id="allPaymentsList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR APPROVING PAYMENT -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Approve Payment</h3><span class="close-modal">&times;</span></div>
        <form id="approveForm">
            <div class="form-group"><label>Payment ID: <span id="paymentIdDisplay"></span></label></div>
            <div class="form-group"><label>Transaction Reference</label><input type="text" id="transactionRef" required></div>
            <div class="form-group"><label>Payment Date</label><input type="date" id="paymentDate" required></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Approve Payment</button></div>
        </form>
    </div>
</div>

<script src="finance.js"></script>
<script>
    let payments = [];
    let currentPaymentId = null;

    function loadData() {
        payments = loadPayments();
        updateStats();
        renderAllPayments(document.getElementById('filterStatus').value);
    }

    function updateStats() {
        const stats = getPaymentStats(payments);
        document.getElementById('pendingCount').innerText = stats.pending;
        document.getElementById('completedCount').innerText = stats.completed;
        document.getElementById('overdueCount').innerText = stats.overdue;
        document.getElementById('pendingAmount').innerText = 'TSh ' + stats.pendingAmount.toLocaleString();
    }

    function renderAllPayments(filter) {
        let filtered = filter === 'all' ? payments : payments.filter(p => p.status === filter);
        const container = document.getElementById('allPaymentsList');
        
        if (filtered.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No payment requests found</div>';
            return;
        }
        
        container.innerHTML = filtered.map(p => `
            <div class="payment-item">
                <div class="flex-between">
                    <div class="payment-title">#${p.id} - ${p.studentName} (${p.studentReg})</div>
                    <span class="status-badge ${p.status === 'Completed' ? 'status-paid' : p.status === 'Overdue' ? 'status-overdue' : 'status-pending'}">${p.status}</span>
                </div>
                <div class="payment-meta">
                    <i class="fas fa-money-bill"></i> Amount: TSh ${p.amount.toLocaleString()} | 
                    <i class="fas fa-tag"></i> ${p.purpose} | 
                    <i class="fas fa-calendar"></i> Requested: ${p.date} |
                    <i class="fas fa-hourglass-half"></i> Due: ${p.dueDate}
                </div>
                ${p.status === 'Pending' ? `<button class="btn-primary approve-payment" data-id="${p.id}" style="margin-top:10px;"><i class="fas fa-check"></i> Approve Payment</button>` : p.status === 'Completed' ? `<div><small><i class="fas fa-check-circle"></i> Approved - Ref: ${p.transactionRef || 'N/A'} on ${p.paymentDate || p.date}</small></div>` : ''}
            </div>
        `).join('');
        
        document.querySelectorAll('.approve-payment').forEach(btn => {
            btn.addEventListener('click', () => {
                currentPaymentId = parseInt(btn.dataset.id);
                document.getElementById('paymentIdDisplay').innerText = currentPaymentId;
                document.getElementById('approveModal').style.display = 'flex';
            });
        });
    }

    function approvePayment(paymentId, transactionRef, paymentDate) {
        const payment = payments.find(p => p.id === paymentId);
        if (payment) {
            payment.status = 'Completed';
            payment.transactionRef = transactionRef;
            payment.paymentDate = paymentDate;
            savePayments(payments);
            loadData();
            alert(`✅ Payment #${paymentId} approved successfully!`);
        }
    }

    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('approveModal').style.display = 'none');
    });

    document.getElementById('approveForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const transactionRef = document.getElementById('transactionRef').value;
        const paymentDate = document.getElementById('paymentDate').value;
        
        if (!transactionRef || !paymentDate) {
            alert('Please fill all fields');
            return;
        }
        
        approvePayment(currentPaymentId, transactionRef, paymentDate);
        document.getElementById('approveModal').style.display = 'none';
        document.getElementById('transactionRef').value = '';
        document.getElementById('paymentDate').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    document.getElementById('filterStatus').addEventListener('change', () => renderAllPayments(document.getElementById('filterStatus').value));

    loadData();
</script>
</body>
</html>