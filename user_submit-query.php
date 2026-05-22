<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAA Student Helpdesk | Submit Query</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles for document upload */
        .document-upload-section {
            background: #f8fafc;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px dashed #cbd5e1;
        }
        .document-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .document-header i {
            font-size: 20px;
            color: #2c7da0;
        }
        .document-header h4 {
            font-size: 0.9rem;
            color: #0a2b38;
            margin: 0;
        }
        .document-note {
            font-size: 0.7rem;
            color: #7f8c8d;
            margin-bottom: 12px;
            font-style: italic;
        }
        .file-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .document-file-label {
            background: #2c7da0;
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .document-file-label:hover {
            background: #1f5a70;
            transform: translateY(-2px);
        }
        .selected-file-name {
            font-size: 0.7rem;
            color: #2c7da0;
            flex: 1;
            word-break: break-all;
        }
        .remove-file-btn {
            background: #c0392b;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.65rem;
            cursor: pointer;
            transition: 0.2s;
        }
        .remove-file-btn:hover {
            background: #a93226;
        }
        .optional-badge {
            background: #e0f0f5;
            color: #2c7da0;
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }
        .document-preview {
            margin-top: 10px;
            padding: 8px;
            background: #e8f0f5;
            border-radius: 12px;
            font-size: 0.7rem;
            display: none;
        }
        .document-preview.show {
            display: block;
        }
        .document-preview a {
            color: #2c7da0;
            text-decoration: none;
        }
        .document-preview a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="app-container">
    <aside class="sidebar">
        <div class="profile-area">
            <div class="avatar"><i class="fas fa-user-graduate"></i></div>
            <div class="welcome-text">Welcome,</div>
            <div class="student-name" id="userName">Goodluck</div>
            <div class="student-id"><i class="fas fa-id-card"></i> <span id="studentId">BCS-01-0131-2023</span></div>
        </div>
        <div class="nav-menu">
            <a href="user_index.php" class="nav-item"><i class="fas fa-chart-pie"></i><span class="nav-label">Dashboard</span></a>
            <a href="user_submit-query.php" class="nav-item active"><i class="fas fa-plus-circle"></i><span class="nav-label">Submit Query</span></a>
            <a href="user_my-queries.php" class="nav-item"><i class="fas fa-ticket-alt"></i><span class="nav-label">My Queries</span></a>
            <a href="user_knowledge-base.php" class="nav-item"><i class="fas fa-graduation-cap"></i><span class="nav-label">Knowledge Base</span></a>
            <a href="user_feedback.php" class="nav-item"><i class="fas fa-star"></i><span class="nav-label">Feedback</span></a>
            <a href="user_edit-photo.php" class="nav-item"><i class="fas fa-camera"></i><span class="nav-label">Edit Photo</span></a>
            <a href="user_startup.php" class="nav-item"><i class="fas fa-rocket"></i><span class="nav-label">Startup Hub</span></a>
            <a href="user_settings.php" class="nav-item"><i class="fas fa-cog"></i><span class="nav-label">Settings</span></a>
            <div class="logout-item"><a href="login.html" class="nav-item" id="logoutBtn"><i class="fas fa-sign-out-alt"></i><span class="nav-label">Logout</span></a></div>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Submit New Query</h1>
            <div class="date-badge"><i class="far fa-calendar-alt"></i> <span id="currentDate"></span></div>
        </div>

        <div class="widget-card">
            <div class="flex-between"><strong>📝 Submit a new query – IAA Helpdesk</strong></div>
            <form id="queryForm">
                <div class="form-group">
                    <label>Query Title *</label>
                    <input type="text" id="qTitle" placeholder="e.g., Missing examination CSC 101, Fee payment not reflected" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="qCategory">
                        <option>Examination issues</option>
                        <option>Fee-related query</option>
                        <option>Portal login problem</option>
                        <option>Course registration error</option>
                        <option>Academic documents</option>
                        <option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select id="qDept">
                        <option>Examination & Records</option>
                        <option>Finance Office</option>
                        <option>ICT Support</option>
                        <option>Academic Registry</option>
                        <option>Dean of Students</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select id="qPriority">
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                        <option>Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea rows="4" id="qDesc" placeholder="Provide full details..."></textarea>
                </div>

                <!-- DOCUMENT UPLOAD SECTION (OPTIONAL) -->
                <div class="document-upload-section">
                    <div class="document-header">
                        <i class="fas fa-paperclip"></i>
                        <h4>Supporting Document <span class="optional-badge">Optional</span></h4>
                    </div>
                    <div class="document-note">
                        <i class="fas fa-info-circle"></i> You can upload a supporting document (e.g., payment receipt, letter, screenshot). Maximum size 2MB. This is optional.
                    </div>
                    <div class="file-input-group">
                        <label class="document-file-label" id="documentFileLabel">
                            <i class="fas fa-upload"></i> Choose File
                        </label>
                        <input type="file" id="documentFile" accept="image/jpeg,image/png,image/jpg,application/pdf" style="display: none;">
                        <span class="selected-file-name" id="selectedFileName">No file chosen</span>
                        <button type="button" class="remove-file-btn" id="removeFileBtn" style="display: none;">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    <div id="documentPreview" class="document-preview">
                        <i class="fas fa-file"></i> <span id="previewFileName"></span>
                        <a href="#" id="previewLink" target="_blank">View document</a>
                    </div>
                </div>

                <div style="display:flex; gap:12px; justify-content:end;">
                    <a href="user_index.php" class="btn-primary" style="background:#7f8c8d;">Cancel</a>
                    <button type="submit" class="btn-primary">Submit Query</button>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="js/data.js"></script>
<script>
    function setCurrentDate() {
        const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        const dateElement = document.getElementById('currentDate');
        if (dateElement) dateElement.innerText = new Date().toLocaleDateString('en-US', options);
    }
    
    loadFromLocalStorage();
    initDemoData();
    setCurrentDate();
    
    let loggedUser = JSON.parse(sessionStorage.getItem('loggedInUser') || '{}');
    document.getElementById('userName').innerText = loggedUser.name || appData.currentUser.name;
    document.getElementById('studentId').innerText = loggedUser.regNo || appData.currentUser.studentId;

    // ========== DOCUMENT UPLOAD FUNCTIONALITY ==========
    const documentFileInput = document.getElementById('documentFile');
    const documentFileLabel = document.getElementById('documentFileLabel');
    const selectedFileName = document.getElementById('selectedFileName');
    const removeFileBtn = document.getElementById('removeFileBtn');
    const documentPreview = document.getElementById('documentPreview');
    const previewFileName = document.getElementById('previewFileName');
    const previewLink = document.getElementById('previewLink');
    
    let selectedDocumentData = null;
    let selectedDocumentName = null;
    let selectedDocumentType = null;
    
    // Handle file selection
    if (documentFileLabel) {
        documentFileLabel.addEventListener('click', () => {
            documentFileInput.click();
        });
    }
    
    if (documentFileInput) {
        documentFileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file format! Please upload JPEG, PNG, JPG or PDF file only.');
                this.value = '';
                return;
            }
            
            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('File size too large! Maximum 2MB allowed.');
                this.value = '';
                return;
            }
            
            selectedDocumentName = file.name;
            selectedDocumentType = file.type;
            selectedFileName.textContent = file.name;
            removeFileBtn.style.display = 'inline-flex';
            
            // Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                selectedDocumentData = e.target.result;
                previewFileName.textContent = file.name;
                
                if (file.type.startsWith('image/')) {
                    previewLink.innerHTML = '<i class="fas fa-image"></i> View image';
                    previewLink.href = selectedDocumentData;
                } else {
                    previewLink.innerHTML = '<i class="fas fa-file-pdf"></i> View PDF';
                    previewLink.href = selectedDocumentData;
                }
                documentPreview.classList.add('show');
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Remove file
    if (removeFileBtn) {
        removeFileBtn.addEventListener('click', () => {
            selectedDocumentData = null;
            selectedDocumentName = null;
            selectedDocumentType = null;
            documentFileInput.value = '';
            selectedFileName.textContent = 'No file chosen';
            removeFileBtn.style.display = 'none';
            documentPreview.classList.remove('show');
        });
    }
    
    // Modified addTicket function to include document
    function addTicketWithDocument(title, category, department, priority, description, documentData, documentName, documentType) {
        const newId = appData.nextId++;
        const newTicket = {
            id: newId,
            title: title,
            category: category,
            department: department,
            priority: priority,
            description: description,
            status: 'Open',
            date: formatDateTime(),
            rating: null,
            hasDocument: documentData ? true : false,
            documentData: documentData || null,
            documentName: documentName || null,
            documentType: documentType || null
        };
        appData.tickets.push(newTicket);
        saveToLocalStorage();
        return newTicket;
    }

    // Submit form with document
    document.getElementById('queryForm').addEventListener('submit', (e) => {
        e.preventDefault();
        const title = document.getElementById('qTitle').value;
        const category = document.getElementById('qCategory').value;
        const department = document.getElementById('qDept').value;
        const priority = document.getElementById('qPriority').value;
        const description = document.getElementById('qDesc').value;
        
        if (!title || !description) {
            showMessage('Please fill title and description');
            return;
        }
        
        // Add ticket with optional document
        addTicketWithDocument(
            title, category, department, priority, description,
            selectedDocumentData, selectedDocumentName, selectedDocumentType
        );
        
        let successMessage = '✅ Query submitted successfully!';
        if (selectedDocumentData) {
            successMessage += ' Your document has been attached.';
        }
        showMessage(successMessage);
        
        // Reset form
        document.getElementById('qTitle').value = '';
        document.getElementById('qDesc').value = '';
        selectedDocumentData = null;
        selectedDocumentName = null;
        documentFileInput.value = '';
        selectedFileName.textContent = 'No file chosen';
        removeFileBtn.style.display = 'none';
        documentPreview.classList.remove('show');
        
        window.location.href = 'user_my-queries.php';
    });

    document.getElementById('logoutBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        sessionStorage.clear();
        localStorage.clear();
        window.location.href = 'login.html';
    });
</script>
</body>
</html>