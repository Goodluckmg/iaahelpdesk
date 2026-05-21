<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | Student Accounts</title>
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
            <a href="fin_invoices.php" class="nav-item" data-view="invoices"><i class="fas fa-file-invoice"></i><span class="nav-label">Invoices</span></a>
            <a href="fin_students.php" class="nav-item active" data-view="students"><i class="fas fa-user-graduate"></i><span class="nav-label">Student Accounts</span></a>
            <a href="fin_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Student Fee Accounts</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number" id="totalStudents">0</div><div>Total Students</div></div>
            <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-number" id="clearStudents">0</div><div>Fee Clear</div></div>
            <div class="stat-card"><i class="fas fa-exclamation-triangle"></i><div class="stat-number" id="partialStudents">0</div><div>Partial Payment</div></div>
            <div class="stat-card"><i class="fas fa-coins"></i><div class="stat-number" id="totalArrears">TSh 0</div><div>Total Arrears</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>👨‍🎓 Student Fee Accounts</strong>
                <div>
                    <input type="text" id="searchStudent" placeholder="Search by name or reg no..." style="width:200px;">
                    <button class="btn-primary" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>
            <div id="studentsList"></div>
        </div>
    </main>
</div>

<!-- MODAL FOR VIEWING STUDENT DETAILS -->
<div id="studentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Student Fee Details</h3><span class="close-modal">&times;</span></div>
        <div id="studentDetails"></div>
        <button class="btn-primary" id="closeStudentModal" style="margin-top:15px;">Close</button>
    </div>
</div>

<script src="finance.js"></script>
<script>
    let students = [];
    let payments = [];

    function loadData() {
        students = loadStudentAccounts();
        payments = loadPayments();
        updateStats();
        renderStudents();
    }

    function updateStats() {
        const clear = students.filter(s => s.status === 'Clear').length;
        const partial = students.filter(s => s.status === 'Partial').length;
        const totalArrears = students.reduce((sum, s) => sum + s.balance, 0);
        
        document.getElementById('totalStudents').innerText = students.length;
        document.getElementById('clearStudents').innerText = clear;
        document.getElementById('partialStudents').innerText = partial;
        document.getElementById('totalArrears').innerText = 'TSh ' + totalArrears.toLocaleString();
    }

    function renderStudents() {
        const searchTerm = document.getElementById('searchStudent').value.toLowerCase();
        let filtered = students.filter(s => 
            s.name.toLowerCase().includes(searchTerm) || 
            s.regNo.toLowerCase().includes(searchTerm)
        );
        
        const container = document.getElementById('studentsList');
        
        if (filtered.length === 0) {
            container.innerHTML = '<div class="widget-card" style="text-align:center;">No students found</div>';
            return;
        }
        
        container.innerHTML = `
            <table>
                <thead>
                    <tr><th>Reg No</th><th>Student Name</th><th>Program</th><th>Total Fees</th><th>Paid</th><th>Balance</th><th>Status</th><th>Action</th>
                </thead>
                <tbody>
                    ${filtered.map(s => `
                        <tr>
                            <td>${s.regNo}侧
                            <td>${s.name}侧
                            <td>${s.program}侧
                            <td>TSh ${s.totalFees.toLocaleString()}侧
                            <td>TSh ${s.paidFees.toLocaleString()}侧
                            <td>TSh ${s.balance.toLocaleString()}侧
                            <td><span class="status-badge ${s.status === 'Clear' ? 'status-paid' : 'status-pending'}">${s.status === 'Clear' ? '✓ Fee Clear' : 'Partial Payment'}</span>侧
                            <td><button class="btn-primary view-student" data-reg="${s.regNo}" style="padding:4px 12px;"><i class="fas fa-eye"></i> View</button>侧
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
        
        document.querySelectorAll('.view-student').forEach(btn => {
            btn.addEventListener('click', () => {
                const regNo = btn.dataset.reg;
                viewStudentDetails(regNo);
            });
        });
    }

    function viewStudentDetails(regNo) {
        const student = students.find(s => s.regNo === regNo);
        const studentPayments = payments.filter(p => p.studentReg === regNo);
        
        document.getElementById('studentDetails').innerHTML = `
            <div class="form-group"><strong>Registration Number:</strong> ${student.regNo}</div>
            <div class="form-group"><strong>Student Name:</strong> ${student.name}</div>
            <div class="form-group"><strong>Program:</strong> ${student.program}</div>
            <hr>
            <div class="form-group"><strong>Total Fees:</strong> TSh ${student.totalFees.toLocaleString()}</div>
            <div class="form-group"><strong>Total Paid:</strong> TSh ${student.paidFees.toLocaleString()}</div>
            <div class="form-group"><strong>Outstanding Balance:</strong> TSh ${student.balance.toLocaleString()}</div>
            <hr>
            <strong>Payment History:</strong>
            ${studentPayments.length === 0 ? '<p>No payment records found</p>' : 
                studentPayments.map(p => `<div class="payment-item"><small>${p.date} - TSh ${p.amount.toLocaleString()} - ${p.purpose} - ${p.status}</small></div>`).join('')}
        `;
        document.getElementById('studentModal').style.display = 'flex';
    }

    document.getElementById('closeStudentModal').addEventListener('click', () => {
        document.getElementById('studentModal').style.display = 'none';
    });
    
    document.querySelectorAll('.close-modal').forEach(el => {
        el.addEventListener('click', () => document.getElementById('studentModal').style.display = 'none');
    });

    document.getElementById('refreshBtn').addEventListener('click', loadData);
    document.getElementById('searchStudent').addEventListener('keyup', renderStudents);

    loadData();
</script>
</body>
</html>