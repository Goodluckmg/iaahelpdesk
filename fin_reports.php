<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Financial Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            <a href="fin_invoices.php" class="nav-item" data-view="invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></a>
            <a href="fin_students.php" class="nav-item" data-view="students"><i class="fas fa-user-graduate"></i><span class="nav-label">Student Accounts</span></a>
            <a href="fin_reports.php" class="nav-item active" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Financial Reports</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row" id="statsRow">
            <div class="stat-card"><div class="stat-number" id="totalRevenue">TSh 0</div><div>Total Revenue</div></div>
            <div class="stat-card"><div class="stat-number" id="collectionRate">0%</div><div>Collection Rate</div></div>
            <div class="stat-card"><div class="stat-number" id="pendingAmount">TSh 0</div><div>Pending Collection</div></div>
            <div class="stat-card"><div class="stat-number" id="totalStudents">0</div><div>Students</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Monthly Revenue Trend</strong></div>
            <canvas id="revenueTrendChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>🥧 Revenue by Category</strong></div>
            <canvas id="categoryPieChart" width="400" height="200" style="max-height: 250px; width: 100%;"></canvas>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📋 Top Outstanding Students</strong></div>
            <div id="topOutstandingList"></div>
        </div>
    </main>
</div>

<script src="finance.js"></script>
<script>
    let payments = [];
    let students = [];
    let revenueTrendChart = null;
    let categoryPieChart = null;

    function loadData() {
        payments = loadPayments();
        students = loadStudentAccounts();
        updateStats();
        renderRevenueTrendChart();
        renderCategoryPieChart();
        renderTopOutstanding();
    }

    function updateStats() {
        const stats = getPaymentStats(payments);
        const totalRevenue = stats.totalAmount;
        const collectionRate = stats.total === 0 ? 0 : Math.round((stats.completed / stats.total) * 100);
        const totalArrears = students.reduce((sum, s) => sum + s.balance, 0);
        
        document.getElementById('totalRevenue').innerText = 'TSh ' + totalRevenue.toLocaleString();
        document.getElementById('collectionRate').innerText = collectionRate + '%';
        document.getElementById('pendingAmount').innerText = 'TSh ' + totalArrears.toLocaleString();
        document.getElementById('totalStudents').innerText = students.length;
    }

    function renderRevenueTrendChart() {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const revenue = [12500000, 15800000, 14200000, 18900000, 21200000, 18500000];
        
        const ctx = document.getElementById('revenueTrendChart').getContext('2d');
        if (revenueTrendChart) revenueTrendChart.destroy();
        
        revenueTrendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue (TSh)',
                    data: revenue,
                    backgroundColor: '#f39c12',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } } }
        });
    }

    function renderCategoryPieChart() {
        const categories = {
            'Tuition Fee': 0,
            'Library Fee': 0,
            'Sports Fee': 0,
            'ID Card Fee': 0,
            'Other': 0
        };
        
        payments.forEach(p => {
            if (p.purpose.includes('Tuition')) categories['Tuition Fee'] += p.amount;
            else if (p.purpose.includes('Library')) categories['Library Fee'] += p.amount;
            else if (p.purpose.includes('Sports')) categories['Sports Fee'] += p.amount;
            else if (p.purpose.includes('ID Card')) categories['ID Card Fee'] += p.amount;
            else categories['Other'] += p.amount;
        });
        
        const ctx = document.getElementById('categoryPieChart').getContext('2d');
        if (categoryPieChart) categoryPieChart.destroy();
        
        categoryPieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(categories),
                datasets: [{
                    data: Object.values(categories),
                    backgroundColor: ['#f39c12', '#e67e22', '#d35400', '#f1c40f', '#95a5a6'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    function renderTopOutstanding() {
        const topStudents = [...students].sort((a, b) => b.balance - a.balance).slice(0, 5);
        
        const container = document.getElementById('topOutstandingList');
        
        if (topStudents.length === 0) {
            container.innerHTML = '<p>No outstanding students</p>';
            return;
        }
        
        container.innerHTML = `
            <table>
                <thead><tr><th>Student Name</th><th>Reg No</th><th>Balance</th></tr></thead>
                <tbody>
                    ${topStudents.map(s => `
                        <tr>
                            <td>${s.name}</td>
                            <td>${s.regNo}</td>
                            <td class="status-overdue" style="font-weight:bold;">TSh ${s.balance.toLocaleString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    }

    loadData();
</script>
</body>
</html>