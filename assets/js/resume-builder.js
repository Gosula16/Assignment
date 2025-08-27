// Resume Builder JavaScript
class ResumeBuilder {
    constructor() {
        this.resumeData = window.resumeData || {};
        this.currentSection = null;
        this.isDirty = false;
        this.autoSaveInterval = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeExistingData();
        this.startAutoSave();
        this.updatePreview();
    }
    
    bindEvents() {
        // Sidebar section buttons
        document.querySelectorAll('.section-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const section = e.currentTarget.dataset.section;
                this.showSection(section);
            });
        });
        
        // Resume settings
        document.getElementById('resume-title').addEventListener('input', (e) => {
            this.resumeData.title = e.target.value;
            this.markDirty();
        });
        
        document.getElementById('target-role').addEventListener('input', (e) => {
            this.resumeData.targetRole = e.target.value;
            this.markDirty();
        });
        
        // Action buttons
        document.getElementById('save-resume').addEventListener('click', () => {
            this.saveResume();
        });
        
        document.getElementById('preview-resume').addEventListener('click', () => {
            this.togglePreview();
        });
        
        document.getElementById('close-preview').addEventListener('click', () => {
            this.closePreview();
        });
        
        // AI Tools
        document.getElementById('ai-optimize').addEventListener('click', () => {
            this.optimizeWithAI();
        });
        
        document.getElementById('parse-jd').addEventListener('click', () => {
            this.openJDParser();
        });
        
        document.getElementById('ats-score').addEventListener('click', () => {
            this.calculateATSScore();
        });
        
        // Export buttons
        document.getElementById('export-pdf').addEventListener('click', () => {
            this.exportResume('pdf');
        });
        
        document.getElementById('export-docx').addEventListener('click', () => {
            this.exportResume('docx');
        });
        
        document.getElementById('export-json').addEventListener('click', () => {
            this.exportResume('json');
        });
        
        // Add section button
        document.getElementById('add-section-btn').addEventListener('click', () => {
            this.openSectionModal();
        });
        
        // Modal events
        this.bindModalEvents();
        
        // Personal info events
        this.bindPersonalInfoEvents();
        
        // Auto-save on input
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('form-input')) {
                this.markDirty();
            }
        });
    }
    
    bindModalEvents() {
        // JD Parser Modal
        document.getElementById('parse-jd-btn').addEventListener('click', () => {
            this.parseJobDescription();
        });
        
        document.getElementById('cancel-jd-parse').addEventListener('click', () => {
            this.closeModal('jd-parser-modal');
        });
        
        // Section Template Modal
        document.querySelectorAll('.section-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                this.addSection(type);
                this.closeModal('section-template-modal');
            });
        });
        
        // Close modals
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                this.closeModal(modal.id);
            });
        });
        
        // Close on backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal.id);
                }
            });
        });
    }
    
    bindPersonalInfoEvents() {
        const personalFields = ['full-name', 'professional-title', 'email', 'phone', 'location', 'linkedin'];
        
        personalFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('input', (e) => {
                    if (!this.resumeData.personalInfo) {
                        this.resumeData.personalInfo = {};
                    }
                    this.resumeData.personalInfo[fieldId.replace('-', '')] = e.target.value;
                    this.markDirty();
                    this.updatePreview();
                });
            }
        });
    }
    
    initializeExistingData() {
        if (this.resumeData.id) {
            // Populate form fields with existing data
            document.getElementById('resume-title').value = this.resumeData.title || '';
            document.getElementById('target-role').value = this.resumeData.targetRole || '';
            
            // Load sections
            if (this.resumeData.sections) {
                Object.keys(this.resumeData.sections).forEach(sectionType => {
                    this.resumeData.sections[sectionType].forEach(section => {
                        this.renderSection(sectionType, section);
                    });
                });
            }
            
            // Update ATS score display
            document.getElementById('current-ats-score').textContent = this.resumeData.atsScore || 0;
        }
    }
    
    showSection(sectionType) {
        // Update active state
        document.querySelectorAll('.section-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-section="${sectionType}"]`).classList.add('active');
        
        // Scroll to section or create if doesn't exist
        const sectionElement = document.getElementById(`${sectionType}-section`);
        if (sectionElement) {
            sectionElement.scrollIntoView({ behavior: 'smooth' });
        } else {
            this.addSection(sectionType);
        }
    }
    
    addSection(type) {
        const sectionData = this.createSectionData(type);
        this.renderSection(type, sectionData);
        this.markDirty();
        
        // Scroll to new section
        setTimeout(() => {
            const newSection = document.querySelector(`[data-section-type="${type}"]:last-of-type`);
            if (newSection) {
                newSection.scrollIntoView({ behavior: 'smooth' });
            }
        }, 100);
    }
    
    createSectionData(type) {
        const templates = {
            summary: {
                type: 'summary',
                content: '',
                position: this.getNextPosition(type)
            },
            experience: {
                type: 'experience',
                jobTitle: '',
                company: '',
                location: '',
                startDate: '',
                endDate: '',
                current: false,
                description: '',
                position: this.getNextPosition(type)
            },
            education: {
                type: 'education',
                degree: '',
                school: '',
                location: '',
                startDate: '',
                endDate: '',
                gpa: '',
                description: '',
                position: this.getNextPosition(type)
            },
            skills: {
                type: 'skills',
                category: '',
                skills: [],
                position: this.getNextPosition(type)
            },
            projects: {
                type: 'projects',
                title: '',
                description: '',
                technologies: '',
                link: '',
                startDate: '',
                endDate: '',
                position: this.getNextPosition(type)
            }
        };
        
        return templates[type] || {};
    }
    
    getNextPosition(type) {
        if (!this.resumeData.sections) {
            this.resumeData.sections = {};
        }
        if (!this.resumeData.sections[type]) {
            this.resumeData.sections[type] = [];
        }
        return this.resumeData.sections[type].length;
    }
    
    renderSection(type, data) {
        const container = document.getElementById('dynamic-sections');
        const sectionElement = this.createSectionElement(type, data);
        container.appendChild(sectionElement);
        
        // Store in data
        if (!this.resumeData.sections) {
            this.resumeData.sections = {};
        }
        if (!this.resumeData.sections[type]) {
            this.resumeData.sections[type] = [];
        }
        this.resumeData.sections[type].push(data);
        
        this.updatePreview();
    }
    
    createSectionElement(type, data) {
        const section = document.createElement('section');
        section.className = 'editor-section dynamic-section';
        section.dataset.sectionType = type;
        section.dataset.sectionId = data.id || Date.now();
        
        const templates = {
            summary: this.createSummarySection(data),
            experience: this.createExperienceSection(data),
            education: this.createEducationSection(data),
            skills: this.createSkillsSection(data),
            projects: this.createProjectsSection(data)
        };
        
        section.innerHTML = templates[type] || '';
        
        // Bind events for this section
        this.bindSectionEvents(section, type);
        
        return section;
    }
    
    createSummarySection(data) {
        return `
            <div class="section-header">
                <h3>Professional Summary</h3>
                <div class="section-handle">
                    <button class="handle-btn ai-enhance" title="AI Enhance">
                        <i class="fas fa-magic"></i>
                    </button>
                    <button class="handle-btn delete" title="Delete Section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                <div class="form-group">
                    <label class="form-label">Professional Summary</label>
                    <textarea class="form-input large summary-content" placeholder="Write a compelling professional summary that highlights your key achievements and skills...">${data.content || ''}</textarea>
                </div>
                <div class="ai-suggestions" style="margin-top: 16px;">
                    <button class="btn btn-outline btn-sm ai-rewrite">
                        <i class="fas fa-magic"></i>
                        AI Rewrite
                    </button>
                    <button class="btn btn-outline btn-sm ai-improve">
                        <i class="fas fa-plus"></i>
                        AI Improve
                    </button>
                </div>
            </div>
        `;
    }
    
    createExperienceSection(data) {
        return `
            <div class="section-header">
                <h3>Work Experience</h3>
                <div class="section-handle">
                    <button class="handle-btn add-item" title="Add Experience">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="handle-btn delete" title="Delete Section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                <div class="experience-item">
                    <div class="item-header">
                        <span class="item-title">Experience Entry</span>
                        <div class="item-actions">
                            <button class="handle-btn ai-enhance" title="AI Enhance">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="handle-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Job Title</label>
                            <input type="text" class="form-input job-title" value="${data.jobTitle || ''}" placeholder="Senior Software Engineer">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-input company" value="${data.company || ''}" placeholder="Tech Company Inc.">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-input location" value="${data.location || ''}" placeholder="New York, NY">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Employment Period</label>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="month" class="form-input start-date" value="${data.startDate || ''}" style="flex: 1;">
                                <span>to</span>
                                <input type="month" class="form-input end-date" value="${data.endDate || ''}" style="flex: 1;" ${data.current ? 'disabled' : ''}>
                                <label style="display: flex; align-items: center; gap: 4px; font-size: 12px;">
                                    <input type="checkbox" class="current-job" ${data.current ? 'checked' : ''}> Current
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description & Achievements</label>
                        <textarea class="form-input large description" placeholder="• Developed and maintained web applications using React and Node.js&#10;• Led a team of 5 developers and improved code quality by 40%&#10;• Implemented CI/CD pipeline reducing deployment time by 60%">${data.description || ''}</textarea>
                    </div>
                    <div class="ai-suggestions" style="margin-top: 16px;">
                        <button class="btn btn-outline btn-sm ai-quantify">
                            <i class="fas fa-chart-line"></i>
                            Add Metrics
                        </button>
                        <button class="btn btn-outline btn-sm ai-rewrite">
                            <i class="fas fa-magic"></i>
                            AI Rewrite
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    createEducationSection(data) {
        return `
            <div class="section-header">
                <h3>Education</h3>
                <div class="section-handle">
                    <button class="handle-btn add-item" title="Add Education">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="handle-btn delete" title="Delete Section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                <div class="education-item">
                    <div class="item-header">
                        <span class="item-title">Education Entry</span>
                        <div class="item-actions">
                            <button class="handle-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Degree</label>
                            <input type="text" class="form-input degree" value="${data.degree || ''}" placeholder="Bachelor of Science in Computer Science">
                        </div>
                        <div class="form-group">
                            <label class="form-label">School</label>
                            <input type="text" class="form-input school" value="${data.school || ''}" placeholder="University of Technology">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" class="form-input location" value="${data.location || ''}" placeholder="Boston, MA">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Graduation Year</label>
                            <input type="month" class="form-input graduation-date" value="${data.endDate || ''}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">GPA (Optional)</label>
                            <input type="text" class="form-input gpa" value="${data.gpa || ''}" placeholder="3.8/4.0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Honors/Activities</label>
                            <input type="text" class="form-input honors" value="${data.honors || ''}" placeholder="Magna Cum Laude, Dean's List">
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    createSkillsSection(data) {
        const skillsHtml = (data.skills || []).map(skill => 
            `<span class="skill-tag">${skill} <i class="fas fa-times remove-skill"></i></span>`
        ).join('');
        
        return `
            <div class="section-header">
                <h3>Skills</h3>
                <div class="section-handle">
                    <button class="handle-btn delete" title="Delete Section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                <div class="form-group">
                    <label class="form-label">Category (Optional)</label>
                    <input type="text" class="form-input skill-category" value="${data.category || ''}" placeholder="e.g., Programming Languages, Technical Skills">
                </div>
                <div class="skills-container">
                    ${skillsHtml}
                </div>
                <div class="skill-input-container">
                    <input type="text" class="form-input skill-input" placeholder="Add a skill and press Enter">
                    <button class="btn btn-outline add-skill-btn">Add</button>
                </div>
                <div class="ai-suggestions" style="margin-top: 16px;">
                    <button class="btn btn-outline btn-sm ai-suggest-skills">
                        <i class="fas fa-lightbulb"></i>
                        AI Suggest Skills
                    </button>
                </div>
            </div>
        `;
    }
    
    createProjectsSection(data) {
        return `
            <div class="section-header">
                <h3>Projects</h3>
                <div class="section-handle">
                    <button class="handle-btn add-item" title="Add Project">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button class="handle-btn delete" title="Delete Section">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-content">
                <div class="project-item">
                    <div class="item-header">
                        <span class="item-title">Project Entry</span>
                        <div class="item-actions">
                            <button class="handle-btn ai-enhance" title="AI Enhance">
                                <i class="fas fa-magic"></i>
                            </button>
                            <button class="handle-btn delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Project Title</label>
                            <input type="text" class="form-input project-title" value="${data.title || ''}" placeholder="E-commerce Platform">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Project Link (Optional)</label>
                            <input type="url" class="form-input project-link" value="${data.link || ''}" placeholder="https://github.com/username/project">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Technologies Used</label>
                        <input type="text" class="form-input technologies" value="${data.technologies || ''}" placeholder="React, Node.js, MongoDB, AWS">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project Description</label>
                        <textarea class="form-input large project-description" placeholder="Describe the project, your role, and key achievements...">${data.description || ''}</textarea>
                    </div>
                    <div class="ai-suggestions" style="margin-top: 16px;">
                        <button class="btn btn-outline btn-sm ai-rewrite">
                            <i class="fas fa-magic"></i>
                            AI Rewrite
                        </button>
                    </div>
                </div>
            </div>
        `;
    }
    
    bindSectionEvents(section, type) {
        // Delete section
        const deleteBtn = section.querySelector('.section-handle .delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                this.deleteSection(section);
            });
        }
        
        // Add item buttons
        const addItemBtn = section.querySelector('.add-item');
        if (addItemBtn) {
            addItemBtn.addEventListener('click', () => {
                this.addItemToSection(section, type);
            });
        }
        
        // Skills specific events
        if (type === 'skills') {
            this.bindSkillsEvents(section);
        }
        
        // Current job checkbox
        const currentJobCheckbox = section.querySelector('.current-job');
        if (currentJobCheckbox) {
            currentJobCheckbox.addEventListener('change', (e) => {
                const endDateInput = section.querySelector('.end-date');
                endDateInput.disabled = e.target.checked;
                if (e.target.checked) {
                    endDateInput.value = '';
                }
            });
        }
        
        // AI enhancement buttons
        section.querySelectorAll('.ai-rewrite, .ai-improve, .ai-quantify, .ai-suggest-skills').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = e.target.closest('button').className.match(/ai-(\w+)/)[1];
                this.handleAIAction(action, section);
            });
        });
        
        // Auto-save on input
        section.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', () => {
                this.updateSectionData(section, type);
                this.markDirty();
                this.updatePreview();
            });
        });
    }
    
    bindSkillsEvents(section) {
        const skillInput = section.querySelector('.skill-input');
        const addSkillBtn = section.querySelector('.add-skill-btn');
        const skillsContainer = section.querySelector('.skills-container');
        
        const addSkill = () => {
            const skill = skillInput.value.trim();
            if (skill) {
                const skillTag = document.createElement('span');
                skillTag.className = 'skill-tag';
                skillTag.innerHTML = `${skill} <i class="fas fa-times remove-skill"></i>`;
                
                skillTag.querySelector('.remove-skill').addEventListener('click', () => {
                    skillTag.remove();
                    this.updateSectionData(section, 'skills');
                    this.markDirty();
                    this.updatePreview();
                });
                
                skillsContainer.appendChild(skillTag);
                skillInput.value = '';
                
                this.updateSectionData(section, 'skills');
                this.markDirty();
                this.updatePreview();
            }
        };
        
        skillInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                addSkill();
            }
        });
        
        addSkillBtn.addEventListener('click', addSkill);
        
        // Existing skill remove buttons
        section.querySelectorAll('.remove-skill').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.skill-tag').remove();
                this.updateSectionData(section, 'skills');
                this.markDirty();
                this.updatePreview();
            });
        });
    }
    
    updateSectionData(section, type) {
        const sectionId = section.dataset.sectionId;
        const sectionIndex = this.findSectionIndex(type, sectionId);
        
        if (sectionIndex === -1) return;
        
        const data = this.resumeData.sections[type][sectionIndex];
        
        // Update data based on section type
        switch (type) {
            case 'summary':
                data.content = section.querySelector('.summary-content').value;
                break;
                
            case 'experience':
                data.jobTitle = section.querySelector('.job-title').value;
                data.company = section.querySelector('.company').value;
                data.location = section.querySelector('.location').value;
                data.startDate = section.querySelector('.start-date').value;
                data.endDate = section.querySelector('.end-date').value;
                data.current = section.querySelector('.current-job').checked;
                data.description = section.querySelector('.description').value;
                break;
                
            case 'education':
                data.degree = section.querySelector('.degree').value;
                data.school = section.querySelector('.school').value;
                data.location = section.querySelector('.location').value;
                data.endDate = section.querySelector('.graduation-date').value;
                data.gpa = section.querySelector('.gpa').value;
                data.honors = section.querySelector('.honors').value;
                break;
                
            case 'skills':
                data.category = section.querySelector('.skill-category').value;
                data.skills = Array.from(section.querySelectorAll('.skill-tag')).map(tag => 
                    tag.textContent.trim().replace('×', '').trim()
                );
                break;
                
            case 'projects':
                data.title = section.querySelector('.project-title').value;
                data.link = section.querySelector('.project-link').value;
                data.technologies = section.querySelector('.technologies').value;
                data.description = section.querySelector('.project-description').value;
                break;
        }
    }
    
    findSectionIndex(type, sectionId) {
        if (!this.resumeData.sections || !this.resumeData.sections[type]) {
            return -1;
        }
        
        return this.resumeData.sections[type].findIndex(section => 
            section.id === sectionId || section.id === parseInt(sectionId)
        );
    }
    
    deleteSection(section) {
        if (confirm('Are you sure you want to delete this section?')) {
            const type = section.dataset.sectionType;
            const sectionId = section.dataset.sectionId;
            const index = this.findSectionIndex(type, sectionId);
            
            if (index !== -1) {
                this.resumeData.sections[type].splice(index, 1);
            }
            
            section.remove();
            this.markDirty();
            this.updatePreview();
        }
    }
    
    addItemToSection(section, type) {
        const sectionContent = section.querySelector('.section-content');
        const newItemData = this.createSectionData(type);
        
        // Create new item element based on type
        let newItemHtml = '';
        switch (type) {
            case 'experience':
                newItemHtml = section.querySelector('.experience-item').outerHTML;
                break;
            case 'education':
                newItemHtml = section.querySelector('.education-item').outerHTML;
                break;
            case 'projects':
                newItemHtml = section.querySelector('.project-item').outerHTML;
                break;
        }
        
        if (newItemHtml) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newItemHtml;
            const newItem = tempDiv.firstElementChild;
            
            // Clear the form values
            newItem.querySelectorAll('.form-input').forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
            });
            
            sectionContent.appendChild(newItem);
            
            // Bind events for the new item
            this.bindItemEvents(newItem, section, type);
            
            // Add to data
            this.resumeData.sections[type].push(newItemData);
            this.markDirty();
        }
    }
    
    bindItemEvents(item, section, type) {
        // Delete item button
        const deleteBtn = item.querySelector('.item-actions .delete');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                if (confirm('Are you sure you want to delete this item?')) {
                    item.remove();
                    this.updateSectionData(section, type);
                    this.markDirty();
                    this.updatePreview();
                }
            });
        }
        
        // Input events
        item.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('input', () => {
                this.updateSectionData(section, type);
                this.markDirty();
                this.updatePreview();
            });
        });
        
        // Current job checkbox for experience
        if (type === 'experience') {
            const currentCheckbox = item.querySelector('.current-job');
            const endDateInput = item.querySelector('.end-date');
            
            if (currentCheckbox && endDateInput) {
                currentCheckbox.addEventListener('change', (e) => {
                    endDateInput.disabled = e.target.checked;
                    if (e.target.checked) {
                        endDateInput.value = '';
                    }
                    this.updateSectionData(section, type);
                    this.markDirty();
                });
            }
        }
    }
    
    handleAIAction(action, section) {
        // Show loading state
        const button = section.querySelector(`.ai-${action}`);
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        button.disabled = true;
        
        // Simulate AI processing (replace with actual AI API calls)
        setTimeout(() => {
            switch (action) {
                case 'rewrite':
                    this.aiRewriteContent(section);
                    break;
                case 'improve':
                    this.aiImproveContent(section);
                    break;
                case 'quantify':
                    this.aiQuantifyAchievements(section);
                    break;
                case 'suggest-skills':
                    this.aiSuggestSkills(section);
                    break;
            }
            
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
    }
    
    aiRewriteContent(section) {
        // Placeholder for AI rewrite functionality
        const textarea = section.querySelector('textarea');
        if (textarea && textarea.value) {
            // This would call your AI API
            console.log('AI Rewrite requested for:', textarea.value);
            window.ResumeBuilder.showAlert('AI rewrite feature coming soon!', 'info');
        }
    }
    
    aiImproveContent(section) {
        // Placeholder for AI improve functionality
        console.log('AI Improve requested');
        window.ResumeBuilder.showAlert('AI improve feature coming soon!', 'info');
    }
    
    aiQuantifyAchievements(section) {
        // Placeholder for AI quantify functionality
        console.log('AI Quantify requested');
        window.ResumeBuilder.showAlert('AI quantify feature coming soon!', 'info');
    }
    
    aiSuggestSkills(section) {
        // Placeholder for AI skill suggestions
        console.log('AI Suggest Skills requested');
        window.ResumeBuilder.showAlert('AI skill suggestions coming soon!', 'info');
    }
    
    togglePreview() {
        const previewPanel = document.getElementById('preview-panel');
        const isOpen = previewPanel.classList.contains('open');
        
        if (isOpen) {
            this.closePreview();
        } else {
            this.openPreview();
        }
    }
    
    openPreview() {
        const previewPanel = document.getElementById('preview-panel');
        previewPanel.classList.add('open');
        this.updatePreview();
        
        // Update button text
        document.getElementById('preview-resume').innerHTML = '<i class="fas fa-eye-slash"></i> Hide Preview';
    }
    
    closePreview() {
        const previewPanel = document.getElementById('preview-panel');
        previewPanel.classList.remove('open');
        
        // Update button text
        document.getElementById('preview-resume').innerHTML = '<i class="fas fa-eye"></i> Preview';
    }
    
    updatePreview() {
        const previewContent = document.getElementById('preview-content');
        const resumeHtml = this.generateResumeHTML();
        
        previewContent.innerHTML = resumeHtml;
    }
    
    generateResumeHTML() {
        const personalInfo = this.resumeData.personalInfo || {};
        const sections = this.resumeData.sections || {};
        
        let html = '<div class="resume-preview">';
        
        // Header
        html += '<div class="preview-header-section">';
        if (personalInfo.fullname) {
            html += `<div class="preview-name">${personalInfo.fullname}</div>`;
        }
        if (personalInfo.professionaltitle) {
            html += `<div class="preview-title">${personalInfo.professionaltitle}</div>`;
        }
        
        // Contact info
        const contactParts = [];
        if (personalInfo.email) contactParts.push(personalInfo.email);
        if (personalInfo.phone) contactParts.push(personalInfo.phone);
        if (personalInfo.location) contactParts.push(personalInfo.location);
        if (personalInfo.linkedin) contactParts.push(personalInfo.linkedin);
        
        if (contactParts.length > 0) {
            html += `<div class="preview-contact">${contactParts.join(' | ')}</div>`;
        }
        html += '</div>';
        
        // Sections
        Object.keys(sections).forEach(sectionType => {
            if (sections[sectionType].length > 0) {
                html += this.generateSectionHTML(sectionType, sections[sectionType]);
            }
        });
        
        html += '</div>';
        return html;
    }
    
    generateSectionHTML(type, sectionData) {
        const sectionTitles = {
            summary: 'Professional Summary',
            experience: 'Professional Experience',
            education: 'Education',
            skills: 'Skills',
            projects: 'Projects'
        };
        
        let html = `<div class="preview-section">`;
        html += `<div class="preview-section-title">${sectionTitles[type]}</div>`;
        
        sectionData.forEach(item => {
            switch (type) {
                case 'summary':
                    if (item.content) {
                        html += `<div class="preview-item">
                            <div class="preview-item-description">${item.content.replace(/\n/g, '<br>')}</div>
                        </div>`;
                    }
                    break;
                    
                case 'experience':
                    if (item.jobTitle || item.company) {
                        html += `<div class="preview-item">
                            <div class="preview-item-header">
                                <div class="preview-item-title">${item.jobTitle} ${item.company ? '- ' + item.company : ''}</div>
                                <div class="preview-item-date">${this.formatDateRange(item.startDate, item.endDate, item.current)}</div>
                            </div>
                            ${item.location ? `<div class="preview-item-subtitle">${item.location}</div>` : ''}
                            ${item.description ? `<div class="preview-item-description">${item.description.replace(/\n/g, '<br>')}</div>` : ''}
                        </div>`;
                    }
                    break;
                    
                case 'education':
                    if (item.degree || item.school) {
                        html += `<div class="preview-item">
                            <div class="preview-item-header">
                                <div class="preview-item-title">${item.degree}</div>
                                <div class="preview-item-date">${this.formatDate(item.endDate)}</div>
                            </div>
                            ${item.school ? `<div class="preview-item-subtitle">${item.school}${item.location ? ', ' + item.location : ''}</div>` : ''}
                            ${item.gpa ? `<div class="preview-item-description">GPA: ${item.gpa}</div>` : ''}
                            ${item.honors ? `<div class="preview-item-description">${item.honors}</div>` : ''}
                        </div>`;
                    }
                    break;
                    
                case 'skills':
                    if (item.skills && item.skills.length > 0) {
                        html += `<div class="preview-item">
                            ${item.category ? `<div class="preview-item-title">${item.category}</div>` : ''}
                            <div class="preview-item-description">${item.skills.join(', ')}</div>
                        </div>`;
                    }
                    break;
                    
                case 'projects':
                    if (item.title) {
                        html += `<div class="preview-item">
                            <div class="preview-item-header">
                                <div class="preview-item-title">${item.title}</div>
                                <div class="preview-item-date">${this.formatDateRange(item.startDate, item.endDate)}</div>
                            </div>
                            ${item.technologies ? `<div class="preview-item-subtitle">${item.technologies}</div>` : ''}
                            ${item.description ? `<div class="preview-item-description">${item.description.replace(/\n/g, '<br>')}</div>` : ''}
                            ${item.link ? `<div class="preview-item-description"><a href="${item.link}" target="_blank">${item.link}</a></div>` : ''}
                        </div>`;
                    }
                    break;
            }
        });
        
        html += '</div>';
        return html;
    }
    
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short' });
    }
    
    formatDateRange(startDate, endDate, current = false) {
        const start = this.formatDate(startDate);
        const end = current ? 'Present' : this.formatDate(endDate);
        
        if (!start && !end) return '';
        if (!start) return end;
        if (!end) return start;
        
        return `${start} - ${end}`;
    }
    
    openJDParser() {
        this.openModal('jd-parser-modal');
    }
    
    async parseJobDescription() {
        const jobDescriptionText = document.getElementById('job-description-text').value.trim();
        
        if (!jobDescriptionText) {
            window.ResumeBuilder.showAlert('Please enter a job description to parse.', 'error');
            return;
        }
        
        try {
            // Show loading
            const parseBtn = document.getElementById('parse-jd-btn');
            const originalText = parseBtn.innerHTML;
            parseBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Parsing...';
            parseBtn.disabled = true;
            
            // Call API to parse job description
            const response = await fetch('api/parse-job-description.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    jobDescription: jobDescriptionText,
                    resumeId: this.resumeData.id
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Apply suggestions to resume
                this.applyJDSuggestions(result.data);
                window.ResumeBuilder.showAlert('Job description parsed successfully! Resume optimized.', 'success');
                this.closeModal('jd-parser-modal');
            } else {
                throw new Error(result.message || 'Failed to parse job description');
            }
            
            // Restore button
            parseBtn.innerHTML = originalText;
            parseBtn.disabled = false;
            
        } catch (error) {
            console.error('JD parsing error:', error);
            window.ResumeBuilder.showAlert('Failed to parse job description. Please try again.', 'error');
            
            // Restore button
            const parseBtn = document.getElementById('parse-jd-btn');
            parseBtn.innerHTML = '<i class="fas fa-search"></i> Parse & Optimize';
            parseBtn.disabled = false;
        }
    }
    
    applyJDSuggestions(suggestions) {
        // Apply AI suggestions to the resume
        // This would update sections based on parsed keywords and requirements
        console.log('Applying JD suggestions:', suggestions);
        
        // Update ATS score if provided
        if (suggestions.atsScore) {
            document.getElementById('current-ats-score').textContent = suggestions.atsScore;
            this.resumeData.atsScore = suggestions.atsScore;
        }
        
        this.markDirty();
        this.updatePreview();
    }
    
    async calculateATSScore() {
        try {
            const button = document.getElementById('ats-score');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Calculating...';
            button.disabled = true;
            
            const response = await fetch('api/calculate-ats-score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resumeData: this.resumeData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                document.getElementById('current-ats-score').textContent = result.score;
                this.resumeData.atsScore = result.score;
                window.ResumeBuilder.showAlert(`ATS Score calculated: ${result.score}%`, 'success');
            } else {
                throw new Error(result.message || 'Failed to calculate ATS score');
            }
            
            button.innerHTML = originalText;
            button.disabled = false;
            
        } catch (error) {
            console.error('ATS score calculation error:', error);
            window.ResumeBuilder.showAlert('Failed to calculate ATS score. Please try again.', 'error');
            
            const button = document.getElementById('ats-score');
            button.innerHTML = '<i class="fas fa-chart-line"></i> ATS Score';
            button.disabled = false;
        }
    }
    
    async optimizeWithAI() {
        try {
            const button = document.getElementById('ai-optimize');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Optimizing...';
            button.disabled = true;
            
            const response = await fetch('api/optimize-resume.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    resumeData: this.resumeData
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Apply optimizations
                this.applyOptimizations(result.optimizations);
                window.ResumeBuilder.showAlert('Resume optimized with AI suggestions!', 'success');
            } else {
                throw new Error(result.message || 'Failed to optimize resume');
            }
            
            button.innerHTML = originalText;
            button.disabled = false;
            
        } catch (error) {
            console.error('AI optimization error:', error);
            window.ResumeBuilder.showAlert('AI optimization feature coming soon!', 'info');
            
            const button = document.getElementById('ai-optimize');
            button.innerHTML = '<i class="fas fa-magic"></i> AI Optimize';
            button.disabled = false;
        }
    }
    
    applyOptimizations(optimizations) {
        // Apply AI optimizations to the resume
        console.log('Applying optimizations:', optimizations);
        this.markDirty();
        this.updatePreview();
    }
    
    async exportResume(format) {
        try {
            const button = document.getElementById(`export-${format}`);
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Save resume first
            await this.saveResume(false);
            
            const response = await fetch(`api/export-resume.php?id=${this.resumeData.id}&format=${format}`, {
                method: 'GET'
            });
            
            if (!response.ok) {
                throw new Error('Export failed');
            }
            
            // Download the file
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `resume.${format}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            window.ResumeBuilder.showAlert(`Resume exported as ${format.toUpperCase()}!`, 'success');
            
            button.innerHTML = originalText;
            button.disabled = false;
            
        } catch (error) {
            console.error('Export error:', error);
            window.ResumeBuilder.showAlert('Export feature coming soon!', 'info');
            
            const button = document.getElementById(`export-${format}`);
            button.innerHTML = button.querySelector('i').outerHTML + ' ' + format.toUpperCase();
            button.disabled = false;
        }
    }
    
    async saveResume(showAlert = true) {
        try {
            const response = await fetch('api/save-resume.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(this.resumeData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (!this.resumeData.id) {
                    this.resumeData.id = result.resumeId;
                    // Update URL to include resume ID
                    window.history.replaceState({}, '', `resume-builder.php?id=${result.resumeId}`);
                }
                
                this.isDirty = false;
                
                if (showAlert) {
                    window.ResumeBuilder.showAlert('Resume saved successfully!', 'success');
                }
                
                return true;
            } else {
                throw new Error(result.message || 'Failed to save resume');
            }
            
        } catch (error) {
            console.error('Save error:', error);
            if (showAlert) {
                window.ResumeBuilder.showAlert('Failed to save resume. Please try again.', 'error');
            }
            return false;
        }
    }
    
    openSectionModal() {
        this.openModal('section-template-modal');
    }
    
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('active');
        
        // Focus first input
        const firstInput = modal.querySelector('input, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        
        // Clear form if it's the JD parser modal
        if (modalId === 'jd-parser-modal') {
            document.getElementById('job-description-text').value = '';
        }
    }
    
    markDirty() {
        this.isDirty = true;
        
        // Update save button state
        const saveBtn = document.getElementById('save-resume');
        if (!saveBtn.classList.contains('btn-primary')) {
            saveBtn.classList.remove('btn-outline');
            saveBtn.classList.add('btn-primary');
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    }
    
    startAutoSave() {
        // Auto-save every 30 seconds if there are changes
        this.autoSaveInterval = setInterval(() => {
            if (this.isDirty) {
                this.saveResume(false);
            }
        }, 30000);
    }
    
    // Cleanup
    destroy() {
        if (this.autoSaveInterval) {
            clearInterval(this.autoSaveInterval);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.resumeBuilder = new ResumeBuilder();
    
    // Warn user about unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (window.resumeBuilder && window.resumeBuilder.isDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
});