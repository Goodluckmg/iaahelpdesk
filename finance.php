<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Finance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="css/finance.css">
</head>
<body>
<div class="app-container">
    <!-- SIDEBAR -->
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
            <a href="fin_invoices.php" class="nav-item" data-view="invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></a>
            <a href="fin_students.php" class="nav-item" data-view="students"><i class="fas fa-user-graduate"></i><span class="nav-label">Student Accounts</span></a>
            <a href="fin_edit-photo.php" class="nav-item"> <i class="fas fa-camera"></i> <span class="nav-label">Edit Photo</span></a>
            <a href="fin_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Finance Dashboard</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-row" id="statsRow">
            <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-number" id="pendingCount">0</div><div>Pending Payments</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="completedCount">0</div><div>Completed</div></div>
            <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="stat-number" id="overdueCount">0</div><div>Overdue</div></div>
            <div class="stat-card"><i class="fas fa-coins"></i><div class="stat-number" id="totalAmount">TSh 0</div><div>Total Revenue</div></div>
            <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-number" id="collectionRate">0%</div><div>Collection Rate</div></div>
        </div>

        <!-- RECENT PAYMENTS -->
        <div class="widget-card">
            <div class="flex-between">
                <strong>💰 Recent Payment Requests</strong>
                <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
            </div>
            <table id="paymentsTable">
                <thead>
                    <tr><th>ID</th><th>Student Name</th><th>Reg No</th><th>Amount</th><th>Purpose</th><th>Status</th><th>Action</th>
                </thead>
                <tbody id="paymentsBody"></tbody>
            </table>
        </div>

        <!-- REVENUE OVERVIEW -->
        <div class="widget-card">
            <div class="flex-between"><strong>📊 Revenue Overview</strong></div>
            <canvas id="revenueChart" width="400" height="200" style="max-height: 200px; width: 100%;"></canvas>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div class="widget-card">
            <div class="flex-between"><strong>📢 Finance Announcements</strong></div>
            <p>💰 Fee payment deadline: July 15th, 2024<br>📄 New invoices generated for Semester II<br>🏦 Bank details updated for online payments</p>
        </div>
    </main>
</div>

<!-- MODAL FOR APPROVING PAYMENT -->
<div id="approveModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Approve Payment</h3><span class="close-modal">&times;</span></div>
        <form id="approveForm">
            <div class="form-group"><label>Payment ID: <span id="paymentIdDisplay"></span></label></div>
            <div class="form-group"><label>Transaction Reference</label><input type="text" id="transactionRef" placeholder="e.g., TRX-001"></div>
            <div class="form-group"><label>Payment Date</label><input type="date" id="paymentDate"></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Approve Payment</button></div>
        </form>
    </div>
</div>

<script src="finance.js"></script>
<script>
    let payments = [];
    let revenueChart = null;

    function loadData() {
        payments = loadPayments();
        updateStats();
        renderRecentPayments();
        renderRevenueChart();
    }

    function updateStats() {
        const stats = getPaymentStats(payments);
        const collectionRate = stats.total === 0 ? 0 : Math.round((stats.completed / stats.total) * 100);
        
        document.getElementById('pendingCount').innerText = stats.pending;
        document.getElementById('completedCount').innerText = stats.completed;
        document.getElementById('overdueCount').innerText = stats.overdue;
        document.getElementById('totalAmount').innerText = 'TSh ' + stats.totalAmount.toLocaleString();
        document.getElementById('collectionRate').innerText = collectionRate + '%';
    }

    function renderRecentPayments() {
        const recent = [...payments].reverse().slice(0, 10);
        const tbody = document.getElementById('paymentsBody');
        
        if (recent.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7">No payments found</tr>';
            return;
        }
        
        tbody.innerHTML = recent.map(p => `
            <tr>
                <td>#${p.id}侧
                <td>${p.studentName}侧
                <td>${p.studentReg}侧
                <td>TSh ${p.amount.toLocaleString()}侧
                <td>${p.purpose.substring(0, 30)}${p.purpose.length > 30 ? '...' : ''}侧
                <td><span class="status-badge ${p.status === 'Completed' ? 'status-paid' : p.status === 'Overdue' ? 'status-overdue' : 'status-pending'}">${p.status}</span>侧
                <td>${p.status === 'Pending' ? `<button class="btn-primary approve-payment" data-id="${p.id}" style="padding:4px 12px;"><i class="fas fa-check"></i> Approve</button>` : '<span class="status-badge status-paid">✓ Processed</span>'}侧
            </tr>
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

    function renderRevenueChart() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const revenue = [12500000, 15800000, 14200000, 18900000, 21200000, 18500000];
        
        const ctx = document.getElementById('revenueChart').getContext('2d');
        if (revenueChart) revenueChart.destroy();
        
        revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (TSh)',
                    data: revenue,
                    borderColor: '#f39c12',
                    backgroundColor: 'rgba(243,156,18,0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    let currentPaymentId = null;
    
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('approveModal').style.display = 'none');
    });

    document.getElementById('approveForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const transactionRef = document.getElementById('transactionRef').value;
        const paymentDate = document.getElementById('paymentDate').value;
        
        if (!transactionRef) {
            alert('Please enter transaction reference');
            return;
        }
        
        approvePayment(currentPaymentId, transactionRef, paymentDate);
        document.getElementById('approveModal').style.display = 'none';
        document.getElementById('transactionRef').value = '';
        document.getElementById('paymentDate').value = '';
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    
    loadData();
</script>
</body>
</html>