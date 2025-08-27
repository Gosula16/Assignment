// Dashboard JavaScript functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeDashboard();
});

function initializeDashboard() {
    // Sidebar navigation
    initializeSidebarNavigation();
    
    // Resume card actions
    initializeResumeActions();
    
    // Quick actions
    initializeQuickActions();
    
    // Mobile sidebar toggle
    initializeMobileSidebar();
    
    // Load dashboard data
    loadDashboardData();
}

function initializeSidebarNavigation() {
    const sidebarLinks = document.querySelectorAll('.sidebar-link');
    
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Remove active class from all links
            sidebarLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Handle section navigation
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                e.preventDefault();
                scrollToSection(href.substring(1));
            }
        });
    });
}

function initializeResumeActions() {
    // Edit resume buttons
    document.querySelectorAll('.resume-card .btn-icon').forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const action = this.querySelector('i').classList.contains('fa-edit') ? 'edit' :
                          this.querySelector('i').classList.contains('fa-download') ? 'download' :
                          this.querySelector('i').classList.contains('fa-trash') ? 'delete' : null;
            
            const resumeCard = this.closest('.resume-card');
            const resumeId = resumeCard.dataset.resumeId;
            
            handleResumeAction(action, resumeId, resumeCard);
        });
    });
    
    // Resume card click to edit
    document.querySelectorAll('.resume-card').forEach(card => {
        card.addEventListener('click', function() {
            const editButton = this.querySelector('a[href*="resume-builder.php"]');
            if (editButton) {
                window.location.href = editButton.href;
            }
        });
    });
}

function handleResumeAction(action, resumeId, card) {
    switch (action) {
        case 'edit':
            window.location.href = `resume-builder.php?id=${resumeId}`;
            break;
            
        case 'download':
            downloadResume(resumeId);
            break;
            
        case 'delete':
            if (confirm('Are you sure you want to delete this resume? This action cannot be undone.')) {
                deleteResume(resumeId, card);
            }
            break;
    }
}

async function downloadResume(resumeId) {
    try {
        showLoading('Preparing download...');
        
        const response = await fetch(`api/export-resume.php?id=${resumeId}&format=pdf`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error('Download failed');
        }
        
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `resume_${resumeId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
        
        hideLoading();
        showAlert('Resume downloaded successfully!', 'success');
        
    } catch (error) {
        hideLoading();
        showAlert('Failed to download resume. Please try again.', 'error');
        console.error('Download error:', error);
    }
}

async function deleteResume(resumeId, card) {
    try {
        showLoading('Deleting resume...');
        
        const response = await fetch(`api/delete-resume.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ resumeId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Animate card removal
            card.style.transition = 'all 0.3s ease';
            card.style.transform = 'scale(0)';
            card.style.opacity = '0';
            
            setTimeout(() => {
                card.remove();
                updateStatsAfterDelete();
            }, 300);
            
            showAlert('Resume deleted successfully!', 'success');
        } else {
            throw new Error(result.message || 'Delete failed');
        }
        
        hideLoading();
        
    } catch (error) {
        hideLoading();
        showAlert('Failed to delete resume. Please try again.', 'error');
        console.error('Delete error:', error);
    }
}

function initializeQuickActions() {
    document.querySelectorAll('.action-card').forEach(card => {
        card.addEventListener('click', function(e) {
            // Add click animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

function initializeMobileSidebar() {
    // Create mobile menu toggle if it doesn't exist
    if (window.innerWidth <= 768 && !document.querySelector('.mobile-sidebar-toggle')) {
        const toggle = document.createElement('button');
        toggle.className = 'mobile-sidebar-toggle';
        toggle.innerHTML = '<i class="fas fa-bars"></i>';
        toggle.style.cssText = `
            position: fixed;
            top: 80px;
            left: 20px;
            z-index: 1001;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        
        document.body.appendChild(toggle);
        
        const sidebar = document.querySelector('.sidebar');
        
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
}

async function loadDashboardData() {
    try {
        // Load recent activity, analytics, etc.
        const response = await fetch('api/dashboard-data.php');
        const data = await response.json();
        
        if (data.success) {
            updateDashboardStats(data.stats);
            updateRecentActivity(data.activity);
        }
        
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}

function updateDashboardStats(stats) {
    if (!stats) return;
    
    // Update stat cards with animated counting
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"] .stat-content h3`);
        if (element) {
            animateNumber(element, parseInt(element.textContent) || 0, stats[key]);
        }
    });
}

function updateRecentActivity(activity) {
    if (!activity || !activity.length) return;
    
    // Update recent activity section if it exists
    const activityContainer = document.querySelector('.recent-activity');
    if (activityContainer) {
        activityContainer.innerHTML = activity.map(item => `
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-${item.icon}"></i>
                </div>
                <div class="activity-content">
                    <p>${item.message}</p>
                    <span class="activity-time">${formatTimeAgo(item.timestamp)}</span>
                </div>
            </div>
        `).join('');
    }
}

function updateStatsAfterDelete() {
    // Update resume count
    const resumeCountElement = document.querySelector('.stat-card:first-child .stat-content h3');
    if (resumeCountElement) {
        const currentCount = parseInt(resumeCountElement.textContent);
        resumeCountElement.textContent = Math.max(0, currentCount - 1);
    }
    
    // Check if we need to show empty state
    const remainingCards = document.querySelectorAll('.resume-card');
    if (remainingCards.length === 0) {
        const resumesGrid = document.querySelector('.resumes-grid');
        resumesGrid.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>No resumes yet</h3>
                <p>Create your first AI-powered resume to get started</p>
                <a href="resume-builder.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>
                    Create Resume
                </a>
            </div>
        `;
    }
}

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId) || 
                   document.querySelector(`[data-section="${sectionId}"]`);
    
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

function animateNumber(element, start, end, duration = 1000) {
    const range = end - start;
    const increment = range / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.round(current);
    }, 16);
}

function formatTimeAgo(timestamp) {
    const now = new Date();
    const time = new Date(timestamp);
    const diff = now - time;
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
}

function showLoading(message = 'Loading...') {
    const loader = document.createElement('div');
    loader.id = 'dashboard-loader';
    loader.innerHTML = `
        <div class="loader-overlay">
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <p>${message}</p>
            </div>
        </div>
    `;
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    `;
    
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('dashboard-loader');
    if (loader) {
        loader.remove();
    }
}

function showAlert(message, type = 'info') {
    // Use the global alert function from main.js
    if (window.ResumeBuilder && window.ResumeBuilder.showAlert) {
        window.ResumeBuilder.showAlert(message, type);
    } else {
        alert(message); // Fallback
    }
}