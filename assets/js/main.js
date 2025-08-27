// Main JavaScript functionality for AI Resume Builder

class ResumeBuilder {
    constructor() {
        this.currentResumeId = null;
        this.currentSection = null;
        this.unsavedChanges = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAutoSave();
        this.loadResumeData();
    }

    setupEventListeners() {
        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.resume-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });

        // Section editing
        document.addEventListener('click', (e) => {
            if (e.target.matches('.edit-section')) {
                this.editSection(e.target.dataset.sectionId);
            }
            if (e.target.matches('.save-section')) {
                this.saveSection(e.target.dataset.sectionId);
            }
            if (e.target.matches('.add-item')) {
                this.addSectionItem(e.target.dataset.sectionType);
            }
            if (e.target.matches('.remove-item')) {
                this.removeSectionItem(e.target);
            }
        });

        // Job description parsing
        document.addEventListener('click', (e) => {
            if (e.target.matches('.parse-jd')) {
                this.parseJobDescription();
            }
            if (e.target.matches('.tailor-resume')) {
                this.tailorResume();
            }
        });

        // Export functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('.export-resume')) {
                this.showExportModal();
            }
        });

        // Modal handling
        document.addEventListener('click', (e) => {
            if (e.target.matches('.modal-close') || e.target.matches('.modal')) {
                this.closeModal();
            }
        });

        // Track unsaved changes
        document.addEventListener('input', (e) => {
            if (e.target.matches('.resume-field')) {
                this.unsavedChanges = true;
                this.updatePreview();
            }
        });

        // Warn before leaving with unsaved changes
        window.addEventListener('beforeunload', (e) => {
            if (this.unsavedChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    setupAutoSave() {
        setInterval(() => {
            if (this.unsavedChanges) {
                this.autoSave();
            }
        }, 30000); // Auto-save every 30 seconds
    }

    loadResumeData() {
        const urlParams = new URLSearchParams(window.location.search);
        const resumeId = urlParams.get('id');
        
        if (resumeId) {
            this.currentResumeId = resumeId;
            this.fetchResumeData(resumeId);
        }
    }

    async fetchResumeData(resumeId) {
        try {
            const response = await fetch(`api/resume.php?action=get&id=${resumeId}`);
            const data = await response.json();
            
            if (data.success) {
                this.populateEditor(data.resume);
                this.updatePreview();
            } else {
                this.showAlert('Failed to load resume data', 'error');
            }
        } catch (error) {
            this.showAlert('Error loading resume', 'error');
        }
    }

    populateEditor(resume) {
        // Populate basic resume info
        const titleField = document.getElementById('resume-title');
        const roleField = document.getElementById('target-role');
        
        if (titleField) titleField.value = resume.title || '';
        if (roleField) roleField.value = resume.target_role || '';

        // Populate sections
        if (resume.sections) {
            resume.sections.forEach(section => {
                this.populateSection(section);
            });
        }

        // Update ATS score
        this.updateATSScore(resume.ats_score || 0);
    }

    populateSection(section) {
        const sectionContainer = document.querySelector(`[data-section-type="${section.type}"]`);
        if (!sectionContainer) return;

        const content = section.content || {};
        
        switch (section.type) {
            case 'summary':
                this.populateSummarySection(sectionContainer, content);
                break;
            case 'experience':
                this.populateExperienceSection(sectionContainer, content);
                break;
            case 'education':
                this.populateEducationSection(sectionContainer, content);
                break;
            case 'skills':
                this.populateSkillsSection(sectionContainer, content);
                break;
            case 'projects':
                this.populateProjectsSection(sectionContainer, content);
                break;
        }
    }

    populateSummarySection(container, content) {
        const textarea = container.querySelector('textarea');
        if (textarea && content.text) {
            textarea.value = content.text;
        }
    }

    populateExperienceSection(container, content) {
        const itemsContainer = container.querySelector('.section-items');
        if (!itemsContainer || !content.items) return;

        itemsContainer.innerHTML = '';
        content.items.forEach(item => {
            const itemElement = this.createExperienceItem(item);
            itemsContainer.appendChild(itemElement);
        });
    }

    populateEducationSection(container, content) {
        const itemsContainer = container.querySelector('.section-items');
        if (!itemsContainer || !content.items) return;

        itemsContainer.innerHTML = '';
        content.items.forEach(item => {
            const itemElement = this.createEducationItem(item);
            itemsContainer.appendChild(itemElement);
        });
    }

    populateSkillsSection(container, content) {
        const categoriesContainer = container.querySelector('.skills-categories');
        if (!categoriesContainer || !content.categories) return;

        categoriesContainer.innerHTML = '';
        content.categories.forEach(category => {
            const categoryElement = this.createSkillCategory(category);
            categoriesContainer.appendChild(categoryElement);
        });
    }

    populateProjectsSection(container, content) {
        const itemsContainer = container.querySelector('.section-items');
        if (!itemsContainer || !content.items) return;

        itemsContainer.innerHTML = '';
        content.items.forEach(item => {
            const itemElement = this.createProjectItem(item);
            itemsContainer.appendChild(itemElement);
        });
    }

    createExperienceItem(item = {}) {
        const div = document.createElement('div');
        div.className = 'section-item';
        div.innerHTML = `
            <div class="form-group">
                <label>Job Title</label>
                <input type="text" class="form-control resume-field" value="${item.title || ''}" data-field="title">
            </div>
            <div class="form-group">
                <label>Company</label>
                <input type="text" class="form-control resume-field" value="${item.company || ''}" data-field="company">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="text" class="form-control resume-field" value="${item.start_date || ''}" data-field="start_date" placeholder="e.g., Jan 2020">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="text" class="form-control resume-field" value="${item.end_date || ''}" data-field="end_date" placeholder="e.g., Present">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control resume-field" rows="4" data-field="description" placeholder="• Achieved X by implementing Y, resulting in Z% improvement">${item.description || ''}</textarea>
            </div>
            <button type="button" class="btn btn-danger remove-item">Remove</button>
        `;
        return div;
    }

    createEducationItem(item = {}) {
        const div = document.createElement('div');
        div.className = 'section-item';
        div.innerHTML = `
            <div class="form-group">
                <label>Degree</label>
                <input type="text" class="form-control resume-field" value="${item.degree || ''}" data-field="degree">
            </div>
            <div class="form-group">
                <label>School</label>
                <input type="text" class="form-control resume-field" value="${item.school || ''}" data-field="school">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label>Graduation Year</label>
                    <input type="text" class="form-control resume-field" value="${item.year || ''}" data-field="year">
                </div>
                <div class="form-group">
                    <label>GPA (Optional)</label>
                    <input type="text" class="form-control resume-field" value="${item.gpa || ''}" data-field="gpa">
                </div>
            </div>
            <button type="button" class="btn btn-danger remove-item">Remove</button>
        `;
        return div;
    }

    createSkillCategory(category = {}) {
        const div = document.createElement('div');
        div.className = 'skill-category';
        div.innerHTML = `
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" class="form-control resume-field" value="${category.name || ''}" data-field="name">
            </div>
            <div class="form-group">
                <label>Skills (comma-separated)</label>
                <input type="text" class="form-control resume-field" value="${(category.skills || []).join(', ')}" data-field="skills" placeholder="JavaScript, React, Node.js">
            </div>
            <button type="button" class="btn btn-danger remove-item">Remove Category</button>
        `;
        return div;
    }

    createProjectItem(item = {}) {
        const div = document.createElement('div');
        div.className = 'section-item';
        div.innerHTML = `
            <div class="form-group">
                <label>Project Name</label>
                <input type="text" class="form-control resume-field" value="${item.name || ''}" data-field="name">
            </div>
            <div class="form-group">
                <label>Technologies</label>
                <input type="text" class="form-control resume-field" value="${item.technologies || ''}" data-field="technologies" placeholder="React, Node.js, MongoDB">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control resume-field" rows="3" data-field="description" placeholder="Brief description of the project and your role">${item.description || ''}</textarea>
            </div>
            <div class="form-group">
                <label>Link (Optional)</label>
                <input type="url" class="form-control resume-field" value="${item.link || ''}" data-field="link" placeholder="https://github.com/username/project">
            </div>
            <button type="button" class="btn btn-danger remove-item">Remove</button>
        `;
        return div;
    }

    addSectionItem(sectionType) {
        const container = document.querySelector(`[data-section-type="${sectionType}"] .section-items`);
        if (!container) return;

        let newItem;
        switch (sectionType) {
            case 'experience':
                newItem = this.createExperienceItem();
                break;
            case 'education':
                newItem = this.createEducationItem();
                break;
            case 'projects':
                newItem = this.createProjectItem();
                break;
        }

        if (newItem) {
            container.appendChild(newItem);
            this.unsavedChanges = true;
        }
    }

    removeSectionItem(button) {
        const item = button.closest('.section-item') || button.closest('.skill-category');
        if (item && confirm('Are you sure you want to remove this item?')) {
            item.remove();
            this.unsavedChanges = true;
            this.updatePreview();
        }
    }

    async parseJobDescription() {
        const textarea = document.getElementById('job-description');
        const parseBtn = document.querySelector('.parse-jd');
        
        if (!textarea || !textarea.value.trim()) {
            this.showAlert('Please enter a job description', 'error');
            return;
        }

        const originalText = parseBtn.textContent;
        parseBtn.innerHTML = '<span class="loading"></span> Parsing...';
        parseBtn.disabled = true;

        try {
            const response = await fetch('api/jd-parser.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    job_description: textarea.value,
                    resume_id: this.currentResumeId
                })
            });

            const data = await response.json();

            if (data.success) {
                this.displayKeywords(data.keywords);
                this.showAlert('Job description parsed successfully!', 'success');
            } else {
                this.showAlert('Failed to parse job description', 'error');
            }
        } catch (error) {
            this.showAlert('Error parsing job description', 'error');
        } finally {
            parseBtn.textContent = originalText;
            parseBtn.disabled = false;
        }
    }

    displayKeywords(keywords) {
        const container = document.querySelector('.keywords-list');
        if (!container) return;

        container.innerHTML = '';
        keywords.forEach(keyword => {
            const span = document.createElement('span');
            span.className = `keyword-tag ${keyword.missing ? 'missing' : ''}`;
            span.textContent = keyword.text;
            span.title = keyword.missing ? 'Missing from your resume' : 'Found in your resume';
            container.appendChild(span);
        });
    }

    async tailorResume() {
        const tailorBtn = document.querySelector('.tailor-resume');
        const jobDescription = document.getElementById('job-description')?.value;

        if (!jobDescription || !jobDescription.trim()) {
            this.showAlert('Please parse a job description first', 'error');
            return;
        }

        const originalText = tailorBtn.textContent;
        tailorBtn.innerHTML = '<span class="loading"></span> Tailoring...';
        tailorBtn.disabled = true;

        try {
            const response = await fetch('api/ai-tailor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resume_id: this.currentResumeId,
                    job_description: jobDescription
                })
            });

            const data = await response.json();

            if (data.success) {
                this.populateEditor(data.tailored_resume);
                this.updatePreview();
                this.showAlert('Resume tailored successfully!', 'success');
            } else {
                this.showAlert('Failed to tailor resume', 'error');
            }
        } catch (error) {
            this.showAlert('Error tailoring resume', 'error');
        } finally {
            tailorBtn.textContent = originalText;
            tailorBtn.disabled = false;
        }
    }

    async autoSave() {
        if (!this.currentResumeId) return;

        try {
            const resumeData = this.collectResumeData();
            const response = await fetch('api/resume.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update',
                    resume_id: this.currentResumeId,
                    data: resumeData
                })
            });

            const data = await response.json();
            if (data.success) {
                this.unsavedChanges = false;
                this.showAlert('Auto-saved', 'info', 2000);
            }
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }

    collectResumeData() {
        const data = {};
        
        // Basic info
        const titleField = document.getElementById('resume-title');
        const roleField = document.getElementById('target-role');
        
        if (titleField) data.title = titleField.value;
        if (roleField) data.target_role = roleField.value;

        // Sections
        data.sections = {};
        document.querySelectorAll('[data-section-type]').forEach(section => {
            const sectionType = section.dataset.sectionType;
            data.sections[sectionType] = this.collectSectionData(section, sectionType);
        });

        return data;
    }

    collectSectionData(container, sectionType) {
        switch (sectionType) {
            case 'summary':
                const textarea = container.querySelector('textarea');
                return { text: textarea ? textarea.value : '' };
                
            case 'experience':
            case 'education':
            case 'projects':
                const items = [];
                container.querySelectorAll('.section-item').forEach(item => {
                    const itemData = {};
                    item.querySelectorAll('.resume-field').forEach(field => {
                        itemData[field.dataset.field] = field.value;
                    });
                    items.push(itemData);
                });
                return { items };
                
            case 'skills':
                const categories = [];
                container.querySelectorAll('.skill-category').forEach(category => {
                    const categoryData = {};
                    category.querySelectorAll('.resume-field').forEach(field => {
                        if (field.dataset.field === 'skills') {
                            categoryData.skills = field.value.split(',').map(s => s.trim()).filter(s => s);
                        } else {
                            categoryData[field.dataset.field] = field.value;
                        }
                    });
                    categories.push(categoryData);
                });
                return { categories };
                
            default:
                return {};
        }
    }

    updatePreview() {
        // Update the resume preview in real-time
        const previewContainer = document.querySelector('.resume-preview');
        if (!previewContainer) return;

        const resumeData = this.collectResumeData();
        this.renderPreview(previewContainer, resumeData);
        
        // Update ATS score
        this.calculateATSScore(resumeData);
    }

    renderPreview(container, data) {
        let html = '<div class="resume-content">';
        
        // Header
        html += `
            <div class="resume-header">
                <h1>${data.title || 'Your Name'}</h1>
                ${data.target_role ? `<h2>${data.target_role}</h2>` : ''}
            </div>
        `;

        // Sections
        Object.entries(data.sections || {}).forEach(([type, content]) => {
            html += this.renderSectionPreview(type, content);
        });

        html += '</div>';
        container.innerHTML = html;
    }

    renderSectionPreview(type, content) {
        const titles = {
            summary: 'Professional Summary',
            experience: 'Work Experience',
            education: 'Education',
            skills: 'Skills',
            projects: 'Projects'
        };

        let html = `<div class="preview-section">
            <h3 class="section-title">${titles[type] || type}</h3>`;

        switch (type) {
            case 'summary':
                html += `<p>${content.text || ''}</p>`;
                break;
                
            case 'experience':
                (content.items || []).forEach(item => {
                    html += `
                        <div class="experience-item">
                            <h4>${item.title || ''}</h4>
                            <div class="company-date">
                                <span>${item.company || ''}</span>
                                <span>${item.start_date || ''} - ${item.end_date || ''}</span>
                            </div>
                            <div class="description">${(item.description || '').replace(/\n/g, '<br>')}</div>
                        </div>
                    `;
                });
                break;
                
            case 'education':
                (content.items || []).forEach(item => {
                    html += `
                        <div class="education-item">
                            <h4>${item.degree || ''}</h4>
                            <div>${item.school || ''} - ${item.year || ''}</div>
                            ${item.gpa ? `<div>GPA: ${item.gpa}</div>` : ''}
                        </div>
                    `;
                });
                break;
                
            case 'skills':
                (content.categories || []).forEach(category => {
                    html += `
                        <div class="skills-category">
                            <strong>${category.name || ''}:</strong>
                            <span>${(category.skills || []).join(', ')}</span>
                        </div>
                    `;
                });
                break;
                
            case 'projects':
                (content.items || []).forEach(item => {
                    html += `
                        <div class="project-item">
                            <h4>${item.name || ''}</h4>
                            <div class="technologies">${item.technologies || ''}</div>
                            <div class="description">${item.description || ''}</div>
                            ${item.link ? `<div><a href="${item.link}" target="_blank">${item.link}</a></div>` : ''}
                        </div>
                    `;
                });
                break;
        }

        html += '</div>';
        return html;
    }

    async calculateATSScore(resumeData) {
        try {
            const response = await fetch('api/ats-scorer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resume_id: this.currentResumeId,
                    resume_data: resumeData
                })
            });

            const data = await response.json();
            if (data.success) {
                this.updateATSScore(data.score, data.breakdown);
            }
        } catch (error) {
            console.error('Failed to calculate ATS score:', error);
        }
    }

    updateATSScore(score, breakdown = {}) {
        const scoreElement = document.querySelector('.ats-score h3');
        const breakdownElement = document.querySelector('.score-breakdown');
        
        if (scoreElement) {
            scoreElement.textContent = `${score}%`;
        }
        
        if (breakdownElement && breakdown) {
            breakdownElement.innerHTML = `
                <div>Keywords: ${breakdown.keywords || 0}%</div>
                <div>Skills: ${breakdown.skills || 0}%</div>
                <div>Format: ${breakdown.format || 0}%</div>
                <div>Content: ${breakdown.content || 0}%</div>
            `;
        }
    }

    showExportModal() {
        const modal = document.getElementById('export-modal');
        if (modal) {
            modal.classList.add('active');
        }
    }

    closeModal() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
    }

    async exportResume(format) {
        if (!this.currentResumeId) return;

        try {
            const response = await fetch('api/export.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resume_id: this.currentResumeId,
                    format: format
                })
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `resume.${format}`;
                a.click();
                window.URL.revokeObjectURL(url);
                
                this.closeModal();
                this.showAlert(`Resume exported as ${format.toUpperCase()}`, 'success');
            } else {
                this.showAlert('Export failed', 'error');
            }
        } catch (error) {
            this.showAlert('Export error', 'error');
        }
    }

    showAlert(message, type = 'info', duration = 5000) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.style.position = 'fixed';
        alert.style.top = '20px';
        alert.style.right = '20px';
        alert.style.zIndex = '9999';
        alert.style.minWidth = '300px';

        document.body.appendChild(alert);

        setTimeout(() => {
            alert.remove();
        }, duration);
    }
}

// Initialize the resume builder when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.resumeBuilder = new ResumeBuilder();
});

// Utility functions
function addSkillCategory() {
    const container = document.querySelector('[data-section-type="skills"] .skills-categories');
    if (container) {
        const newCategory = window.resumeBuilder.createSkillCategory();
        container.appendChild(newCategory);
        window.resumeBuilder.unsavedChanges = true;
    }
}

function exportResume(format) {
    window.resumeBuilder.exportResume(format);
}