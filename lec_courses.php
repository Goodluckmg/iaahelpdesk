<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Helpdesk | My Courses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/lecturers.css">
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-chalkboard-user"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="user-name" id="lecturerName">Dr. Sarah Lecturer</div>
            <div class="user-role">📚 Lecturer</div>
            <div class="user-id" id="lecturerId">STAFF/2024/001</div>
        </div>
        <div class="nav-menu">
            <a href="lecturers.php" class="nav-item" data-view="dashboard"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="lec_pending.php" class="nav-item" data-view="pending"><i class="fas fa-clock"></i><span class="nav-label">Pending Requests</span></a>
            <a href="lec_resolved.php" class="nav-item" data-view="resolved"><i class="fas fa-check-circle"></i><span class="nav-label">Resolved</span></a>
            <a href="lec_courses.php" class="nav-item active" data-view="courses"><i class="fas fa-book"></i><span class="nav-label">My Courses</span></a>
            <a href="lec_reports.php" class="nav-item" data-view="reports"><i class="fas fa-chart-line"></i><span class="nav-label">Reports</span></a>
            <div class="logout-item"><a href="../login.html" class="nav-item"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">My Courses</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="stats-row">
            <div class="stat-card"><i class="fas fa-book"></i><div class="stat-number" id="totalCourses">0</div><div>Total Courses</div></div>
            <div class="stat-card"><i class="fas fa-users"></i><div class="stat-number" id="totalStudents">0</div><div>Total Students</div></div>
        </div>

        <div class="widget-card">
            <div class="flex-between">
                <strong>📚 Courses I Teach</strong>
                <button class="btn-primary" id="addCourseBtn"><i class="fas fa-plus"></i> Add New Course</button>
            </div>
            <table id="coursesTable">
                <thead>
                    <tr><th>Code</th><th>Course Name</th><th>Students Enrolled</th><th>Semester</th><th>Actions</th>
                </thead>
                <tbody id="coursesBody"></tbody>
            </table>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📊 Course Statistics</strong></div>
            <canvas id="coursesChart" width="400" height="200" style="max-height: 200px; width: 100%;"></canvas>
        </div>
    </main>
</div>

<!-- MODAL FOR ADD/EDIT COURSE -->
<div id="courseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Course</h3>
            <span class="close-modal">&times;</span>
        </div>
        <form id="courseForm">
            <div class="form-group"><label>Course Code</label><input type="text" id="courseCode" required placeholder="e.g., CSC 201"></div>
            <div class="form-group"><label>Course Name</label><input type="text" id="courseName" required placeholder="e.g., Data Structures"></div>
            <div class="form-group"><label>Students Enrolled</label><input type="number" id="courseStudents" value="0"></div>
            <div class="form-group"><label>Semester</label><select id="courseSemester"><option>Semester I</option><option>Semester II</option></select></div>
            <div style="display:flex; gap:10px;"><button type="button" class="btn-primary" id="cancelModalBtn" style="background:#7f8c8d;">Cancel</button><button type="submit" class="btn-primary">Save Course</button></div>
        </form>
    </div>
</div>

<script src="lecturer.js"></script>
<script>
    let courses = [];
    let currentEditId = null;
    let coursesChart = null;

    function loadData() {
        courses = loadCourses();
        updateStats();
        renderCourses();
        renderChart();
    }

    function updateStats() {
        document.getElementById('totalCourses').innerText = courses.length;
        const totalStudents = courses.reduce((sum, c) => sum + (c.students || 0), 0);
        document.getElementById('totalStudents').innerText = totalStudents;
    }

    function renderCourses() {
        const tbody = document.getElementById('coursesBody');
        if (courses.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No courses added yet. Click "Add Course" to start. </td></tr>';
            return;
        }
        tbody.innerHTML = courses.map(c => `
            <tr>
                <td>${c.code}侧
                <td>${c.name}侧
                <td>${c.students || 0}侧
                <td>${c.semester || 'Semester II'}侧
                <td><button class="btn-warning edit-course" data-id="${c.id}" style="padding:4px 10px;"><i class="fas fa-edit"></i></button> <button class="btn-danger delete-course" data-id="${c.id}" style="padding:4px 10px;"><i class="fas fa-trash"></i></button>侧
            </tr>
        `).join('');
        
        document.querySelectorAll('.edit-course').forEach(btn => {
            btn.addEventListener('click', () => editCourse(parseInt(btn.dataset.id)));
        });
        document.querySelectorAll('.delete-course').forEach(btn => {
            btn.addEventListener('click', () => deleteCourse(parseInt(btn.dataset.id)));
        });
    }

    function renderChart() {
        const ctx = document.getElementById('coursesChart').getContext('2d');
        if (coursesChart) coursesChart.destroy();
        
        const courseNames = courses.map(c => c.code);
        const studentCounts = courses.map(c => c.students || 0);
        
        coursesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: courseNames,
                datasets: [{
                    label: 'Number of Students',
                    data: studentCounts,
                    backgroundColor: '#27ae60',
                    borderRadius: 8
                }]
            },
            options: { responsive: true, maintainAspectRatio: true }
        });
    }

    function addCourse(courseData) {
        const newId = courses.length > 0 ? Math.max(...courses.map(c => c.id)) + 1 : 1;
        const newCourse = { id: newId, ...courseData };
        courses.push(newCourse);
        saveCourses(courses);
        loadData();
        alert('Course added successfully!');
    }

    function editCourse(id) {
        const course = courses.find(c => c.id === id);
        if (course) {
            currentEditId = id;
            document.getElementById('modalTitle').innerText = 'Edit Course';
            document.getElementById('courseCode').value = course.code;
            document.getElementById('courseName').value = course.name;
            document.getElementById('courseStudents').value = course.students || 0;
            document.getElementById('courseSemester').value = course.semester || 'Semester II';
            document.getElementById('courseModal').style.display = 'flex';
        }
    }

    function updateCourse(id, courseData) {
        const index = courses.findIndex(c => c.id === id);
        if (index !== -1) {
            courses[index] = { ...courses[index], ...courseData };
            saveCourses(courses);
            loadData();
            alert('Course updated successfully!');
        }
    }

    function deleteCourse(id) {
        if (confirm('Are you sure you want to delete this course?')) {
            courses = courses.filter(c => c.id !== id);
            saveCourses(courses);
            loadData();
            alert('Course deleted successfully!');
        }
    }

    // Modal handlers
    document.getElementById('addCourseBtn').addEventListener('click', () => {
        currentEditId = null;
        document.getElementById('modalTitle').innerText = 'Add New Course';
        document.getElementById('courseForm').reset();
        document.getElementById('courseModal').style.display = 'flex';
    });
    
    document.querySelectorAll('.close-modal, #cancelModalBtn').forEach(el => {
        el.addEventListener('click', () => document.getElementById('courseModal').style.display = 'none');
    });
    
    document.getElementById('courseForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const courseData = {
            code: document.getElementById('courseCode').value,
            name: document.getElementById('courseName').value,
            students: parseInt(document.getElementById('courseStudents').value) || 0,
            semester: document.getElementById('courseSemester').value
        };
        
        if (currentEditId) {
            updateCourse(currentEditId, courseData);
        } else {
            addCourse(courseData);
        }
        document.getElementById('courseModal').style.display = 'none';
    });
    
    loadData();
</script>
</body>
</html>