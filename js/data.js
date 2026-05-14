// ========== SHARED DATA & FUNCTIONS FOR ALL PAGES ==========

let appData = {
    currentUser: {
        name: 'Goodluck',
        studentId: 'IAA/2024/0789',
        email: 'goodluck@iaa.ac.tz',
        phone: '+255 712 345 678'
    },
    tickets: [],
    nextId: 1001
};

function formatDateTime() {
    let d = new Date();
    return d.toLocaleDateString('en-GB') + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatDateOnly() {
    return new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function showMessage(msg, type = 'info') {
    alert(msg);
}

function saveToLocalStorage() {
    localStorage.setItem('iaa_helpdesk_user', JSON.stringify(appData.currentUser));
    localStorage.setItem('iaa_helpdesk_tickets', JSON.stringify(appData.tickets));
    localStorage.setItem('iaa_helpdesk_nextId', appData.nextId);
}

function loadFromLocalStorage() {
    let savedUser = localStorage.getItem('iaa_helpdesk_user');
    let savedTickets = localStorage.getItem('iaa_helpdesk_tickets');
    let savedNextId = localStorage.getItem('iaa_helpdesk_nextId');
    
    if (savedUser) appData.currentUser = JSON.parse(savedUser);
    if (savedTickets) appData.tickets = JSON.parse(savedTickets);
    if (savedNextId) appData.nextId = parseInt(savedNextId);
}

function addTicket(title, category, department, priority, description) {
    let newTicket = {
        id: appData.nextId++,
        title: title,
        category: category,
        department: department,
        priority: priority,
        description: description,
        status: 'Open',
        date: formatDateTime(),
        rating: null
    };
    appData.tickets.push(newTicket);
    saveToLocalStorage();
    return newTicket;
}

function updateTicketStatus(ticketId, newStatus) {
    let ticket = appData.tickets.find(t => t.id === ticketId);
    if (ticket) {
        ticket.status = newStatus;
        saveToLocalStorage();
        return true;
    }
    return false;
}

function addRating(ticketId, rating) {
    let ticket = appData.tickets.find(t => t.id === ticketId);
    if (ticket) {
        ticket.rating = rating;
        saveToLocalStorage();
        return true;
    }
    return false;
}

function initDemoData() {
    if (appData.tickets.length === 0) {
        addTicket('Missing marks for CSC 201', 'Missing Marks', 'Examination & Records', 'High', 'My result shows absent but I attended all exams.');
        addTicket('Portal login error - 2FA not working', 'Portal Login', 'ICT Support', 'Urgent', 'Unable to access e-learning after password reset.');
        addTicket('Fee payment not reflected', 'Fee-related', 'Finance Office', 'Medium', 'Paid tuition but system still shows balance.');
        appData.tickets[0].status = 'In Progress';
        saveToLocalStorage();
    }
}

function getStats() {
    return {
        open: appData.tickets.filter(t => t.status === 'Open').length,
        inProgress: appData.tickets.filter(t => t.status === 'In Progress').length,
        resolved: appData.tickets.filter(t => t.status === 'Resolved').length,
        total: appData.tickets.length
    };
}

function getRecentTickets(limit = 5) {
    return [...appData.tickets].reverse().slice(0, limit);
}

function getAllTickets() {
    return [...appData.tickets].reverse();
}

function getResolvedTickets() {
    return appData.tickets.filter(t => t.status === 'Resolved');
}

// Set current date on any page
function setCurrentDate() {
    let dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.innerText = formatDateOnly();
    }
}

// Set active sidebar link based on current page
function setActiveSidebar(currentPage) {
    let links = document.querySelectorAll('.nav-item');
    links.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') && link.getAttribute('href').includes(currentPage)) {
            link.classList.add('active');
        }
    });
}