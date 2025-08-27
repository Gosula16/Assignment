// Templates Page JavaScript
class TemplateManager {
    constructor() {
        this.currentTemplate = null;
        this.filteredTemplates = [];
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.initializeFilters();
        this.loadTemplates();
    }
    
    bindEvents() {
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.handleCategoryFilter(e.target);
            });
        });
        
        // Filter selects
        document.getElementById('industry-filter').addEventListener('change', () => {
            this.applyFilters();
        });
        
        document.getElementById('level-filter').addEventListener('change', () => {
            this.applyFilters();
        });
        
        // Template actions
        document.querySelectorAll('.use-template').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const template = e.target.dataset.template;
                this.useTemplate(template);
            });
        });
        
        document.querySelectorAll('.preview-template').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const template = e.target.dataset.template;
                this.previewTemplate(template);
            });
        });
        
        // Modal events
        document.getElementById('close-preview').addEventListener('click', () => {
            this.closePreviewModal();
        });
        
        document.getElementById('use-previewed-template').addEventListener('click', () => {
            if (this.currentTemplate) {
                this.useTemplate(this.currentTemplate);
            }
        });
        
        // Close modal on backdrop click
        document.getElementById('template-preview-modal').addEventListener('click', (e) => {
            if (e.target.id === 'template-preview-modal') {
                this.closePreviewModal();
            }
        });
        
        // Close modal with escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closePreviewModal();
            }
        });
    }
    
    initializeFilters() {
        // Set up initial filter state
        this.applyFilters();
    }
    
    loadTemplates() {
        // Templates are already loaded in the HTML
        // This method could be used to load templates dynamically via API
        this.filteredTemplates = Array.from(document.querySelectorAll('.template-card'));
        this.updateTemplateCount();
    }
    
    handleCategoryFilter(button) {
        // Remove active class from all category buttons
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Add active class to clicked button
        button.classList.add('active');
        
        // Apply filters
        this.applyFilters();
    }
    
    applyFilters() {
        const activeCategory = document.querySelector('.filter-btn.active').dataset.category;
        const selectedIndustry = document.getElementById('industry-filter').value;
        const selectedLevel = document.getElementById('level-filter').value;
        
        const templates = document.querySelectorAll('.template-card');
        let visibleCount = 0;
        
        templates.forEach(template => {
            let show = true;
            
            // Category filter
            if (activeCategory !== 'all') {
                const templateCategory = template.dataset.category;
                if (templateCategory !== activeCategory) {
                    show = false;
                }
            }
            
            // Industry filter
            if (show && selectedIndustry !== 'all') {
                const templateIndustries = template.dataset.industry.split(' ');
                if (!templateIndustries.includes(selectedIndustry) && !templateIndustries.includes('all')) {
                    show = false;
                }
            }
            
            // Level filter
            if (show && selectedLevel !== 'all') {
                const templateLevels = template.dataset.level.split(' ');
                if (!templateLevels.includes(selectedLevel) && !templateLevels.includes('all')) {
                    show = false;
                }
            }
            
            // Show/hide template with animation
            if (show) {
                template.style.display = 'block';
                setTimeout(() => {
                    template.style.opacity = '1';
                    template.style.transform = 'translateY(0)';
                }, 10);
                visibleCount++;
            } else {
                template.style.opacity = '0';
                template.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    template.style.display = 'none';
                }, 300);
            }
        });
        
        this.updateTemplateCount(visibleCount);
        this.showNoResultsMessage(visibleCount === 0);
    }
    
    updateTemplateCount(count = null) {
        if (count === null) {
            count = document.querySelectorAll('.template-card:not([style*="display: none"])').length;
        }
        
        // Update count in header if element exists
        const countElement = document.getElementById('template-count');
        if (countElement) {
            countElement.textContent = count;
        }
    }
    
    showNoResultsMessage(show) {
        let noResultsElement = document.getElementById('no-results-message');
        
        if (show && !noResultsElement) {
            // Create no results message
            noResultsElement = document.createElement('div');
            noResultsElement.id = 'no-results-message';
            noResultsElement.className = 'no-results';
            noResultsElement.innerHTML = `
                <div class="no-results-content">
                    <i class="fas fa-search"></i>
                    <h3>No templates found</h3>
                    <p>Try adjusting your filters to see more templates</p>
                    <button class="btn btn-outline" onclick="templateManager.clearFilters()">
                        Clear Filters
                    </button>
                </div>
            `;
            
            const templatesGrid = document.getElementById('templates-grid');
            templatesGrid.appendChild(noResultsElement);
        } else if (!show && noResultsElement) {
            noResultsElement.remove();
        }
    }
    
    clearFilters() {
        // Reset all filters
        document.querySelector('.filter-btn[data-category="all"]').click();
        document.getElementById('industry-filter').value = 'all';
        document.getElementById('level-filter').value = 'all';
        this.applyFilters();
    }
    
    useTemplate(templateName) {
        // Check if user is logged in
        if (!this.isUserLoggedIn()) {
            this.showLoginPrompt();
            return;
        }
        
        // Redirect to resume builder with template parameter
        window.location.href = `resume-builder.php?template=${templateName}`;
    }
    
    previewTemplate(templateName) {
        this.currentTemplate = templateName;
        
        // Generate full preview
        const previewContent = this.generateTemplatePreview(templateName);
        document.getElementById('template-preview-content').innerHTML = previewContent;
        
        // Update modal title
        const modalTitle = document.querySelector('#template-preview-modal h3');
        modalTitle.textContent = `${this.capitalizeFirst(templateName)} Template Preview`;
        
        // Update use button
        const useButton = document.getElementById('use-previewed-template');
        useButton.dataset.template = templateName;
        
        // Show modal
        document.getElementById('template-preview-modal').classList.add('active');
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
    
    generateTemplatePreview(templateName) {
        // Generate a larger, more detailed preview of the template
        const templates = {
            professional: this.generateProfessionalPreview(),
            modern: this.generateModernPreview(),
            minimal: this.generateMinimalPreview(),
            executive: this.generateExecutivePreview(),
            technical: this.generateTechnicalPreview(),
            creative: this.generateCreativePreview()
        };
        
        return templates[templateName] || templates.professional;
    }
    
    generateProfessionalPreview() {
        return `
            <div class="template-preview-large template-professional-large">
                <div class="template-header">
                    <div class="template-name">JOHN SMITH</div>
                    <div class="template-title">Senior Software Engineer</div>
                    <div class="template-contact">john.smith@email.com | (555) 123-4567 | LinkedIn: /in/johnsmith | New York, NY</div>
                </div>
                
                <div class="template-sections">
                    <div class="template-section">
                        <div class="section-title">PROFESSIONAL SUMMARY</div>
                        <div class="section-content">
                            <p>Experienced Senior Software Engineer with 8+ years of expertise in full-stack development, team leadership, and system architecture. Proven track record of delivering scalable solutions that improved system performance by 40% and reduced operational costs by $200K annually. Skilled in modern technologies including React, Node.js, and cloud platforms.</p>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">PROFESSIONAL EXPERIENCE</div>
                        <div class="section-content">
                            <div class="exp-item">
                                <div class="exp-header">
                                    <div class="exp-title">Senior Software Engineer - Tech Innovations Inc.</div>
                                    <div class="exp-date">2020 - Present</div>
                                </div>
                                <div class="exp-location">San Francisco, CA</div>
                                <ul class="exp-bullets">
                                    <li>Led development of microservices architecture serving 1M+ daily active users</li>
                                    <li>Mentored team of 5 junior developers and improved code quality by 35%</li>
                                    <li>Implemented CI/CD pipeline reducing deployment time from 2 hours to 15 minutes</li>
                                </ul>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-header">
                                    <div class="exp-title">Software Engineer - Digital Solutions LLC</div>
                                    <div class="exp-date">2018 - 2020</div>
                                </div>
                                <div class="exp-location">New York, NY</div>
                                <ul class="exp-bullets">
                                    <li>Developed responsive web applications using React and Redux</li>
                                    <li>Optimized database queries resulting in 50% faster page load times</li>
                                    <li>Collaborated with UX team to improve user engagement by 25%</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">TECHNICAL SKILLS</div>
                        <div class="section-content">
                            <div class="skills-grid">
                                <div class="skill-category">
                                    <strong>Languages:</strong> JavaScript, TypeScript, Python, Java, SQL
                                </div>
                                <div class="skill-category">
                                    <strong>Frameworks:</strong> React, Node.js, Express, Django, Spring Boot
                                </div>
                                <div class="skill-category">
                                    <strong>Tools:</strong> Git, Docker, Kubernetes, Jenkins, AWS, MongoDB
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">EDUCATION</div>
                        <div class="section-content">
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-title">Bachelor of Science in Computer Science</div>
                                    <div class="edu-date">2018</div>
                                </div>
                                <div class="edu-school">University of Technology, Boston, MA</div>
                                <div class="edu-details">GPA: 3.8/4.0, Magna Cum Laude</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateModernPreview() {
        return `
            <div class="template-preview-large template-modern-large">
                <div class="template-layout">
                    <div class="template-sidebar">
                        <div class="sidebar-header">
                            <div class="template-name">JANE DOE</div>
                            <div class="template-title">UX/UI Designer</div>
                        </div>
                        
                        <div class="sidebar-section">
                            <div class="section-title">CONTACT</div>
                            <div class="contact-info">
                                <div class="contact-item">
                                    <i class="fas fa-envelope"></i>
                                    jane.doe@email.com
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-phone"></i>
                                    (555) 987-6543
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Los Angeles, CA
                                </div>
                                <div class="contact-item">
                                    <i class="fab fa-linkedin"></i>
                                    /in/janedoe
                                </div>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <div class="section-title">SKILLS</div>
                            <div class="skills-list">
                                <div class="skill-item">
                                    <span class="skill-name">UI/UX Design</span>
                                    <div class="skill-bar">
                                        <div class="skill-progress" style="width: 95%"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <span class="skill-name">Figma</span>
                                    <div class="skill-bar">
                                        <div class="skill-progress" style="width: 90%"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <span class="skill-name">Adobe Creative Suite</span>
                                    <div class="skill-bar">
                                        <div class="skill-progress" style="width: 85%"></div>
                                    </div>
                                </div>
                                <div class="skill-item">
                                    <span class="skill-name">Prototyping</span>
                                    <div class="skill-bar">
                                        <div class="skill-progress" style="width: 88%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="sidebar-section">
                            <div class="section-title">LANGUAGES</div>
                            <div class="languages-list">
                                <div class="language-item">English (Native)</div>
                                <div class="language-item">Spanish (Fluent)</div>
                                <div class="language-item">French (Intermediate)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-main">
                        <div class="template-section">
                            <div class="section-title">PROFILE</div>
                            <div class="section-content">
                                <p>Creative UX/UI Designer with 6+ years of experience crafting intuitive digital experiences. Specialized in user-centered design methodology, resulting in 40% increase in user engagement and 25% reduction in bounce rates. Passionate about creating accessible and inclusive design solutions.</p>
                            </div>
                        </div>
                        
                        <div class="template-section">
                            <div class="section-title">EXPERIENCE</div>
                            <div class="section-content">
                                <div class="exp-item">
                                    <div class="exp-header">
                                        <div class="exp-title">Senior UX Designer</div>
                                        <div class="exp-date">2021 - Present</div>
                                    </div>
                                    <div class="exp-company">Creative Digital Agency</div>
                                    <ul class="exp-bullets">
                                        <li>Led design system implementation across 15+ product teams</li>
                                        <li>Conducted user research and usability testing for 50+ features</li>
                                        <li>Increased user satisfaction scores by 35% through iterative design improvements</li>
                                    </ul>
                                </div>
                                
                                <div class="exp-item">
                                    <div class="exp-header">
                                        <div class="exp-title">UX Designer</div>
                                        <div class="exp-date">2019 - 2021</div>
                                    </div>
                                    <div class="exp-company">Tech Startup Inc.</div>
                                    <ul class="exp-bullets">
                                        <li>Designed mobile-first responsive interfaces for iOS and Android apps</li>
                                        <li>Collaborated with product managers to define user stories and requirements</li>
                                        <li>Created wireframes, prototypes, and high-fidelity mockups</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-section">
                            <div class="section-title">EDUCATION</div>
                            <div class="section-content">
                                <div class="edu-item">
                                    <div class="edu-header">
                                        <div class="edu-title">Bachelor of Fine Arts in Graphic Design</div>
                                        <div class="edu-date">2019</div>
                                    </div>
                                    <div class="edu-school">Art Institute of California, Los Angeles</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateMinimalPreview() {
        return `
            <div class="template-preview-large template-minimal-large">
                <div class="template-header">
                    <div class="template-name">ALEX JOHNSON</div>
                    <div class="template-contact">alex.johnson@email.com • (555) 456-7890 • linkedin.com/in/alexjohnson • Chicago, IL</div>
                </div>
                
                <div class="template-sections">
                    <div class="template-section">
                        <div class="section-title">SUMMARY</div>
                        <div class="section-content">
                            <p>Results-driven Project Manager with 7+ years of experience leading cross-functional teams and delivering complex projects on time and under budget. Expert in Agile methodologies, stakeholder management, and process optimization. Proven track record of increasing team productivity by 30% and reducing project delivery time by 25%.</p>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">EXPERIENCE</div>
                        <div class="section-content">
                            <div class="exp-item">
                                <div class="exp-header">
                                    <div class="exp-left">
                                        <div class="exp-title">Senior Project Manager</div>
                                        <div class="exp-company">Global Consulting Group</div>
                                    </div>
                                    <div class="exp-date">2020 - Present</div>
                                </div>
                                <ul class="exp-bullets">
                                    <li>Managed portfolio of 12+ concurrent projects with budgets totaling $2.5M</li>
                                    <li>Implemented Agile practices across organization, improving delivery speed by 40%</li>
                                    <li>Led digital transformation initiatives for Fortune 500 clients</li>
                                    <li>Mentored junior project managers and established PMO best practices</li>
                                </ul>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-header">
                                    <div class="exp-left">
                                        <div class="exp-title">Project Manager</div>
                                        <div class="exp-company">Tech Solutions Inc.</div>
                                    </div>
                                    <div class="exp-date">2018 - 2020</div>
                                </div>
                                <ul class="exp-bullets">
                                    <li>Delivered 15+ software development projects using Scrum methodology</li>
                                    <li>Coordinated with stakeholders across 5 different time zones</li>
                                    <li>Reduced project risks through proactive risk management strategies</li>
                                </ul>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-header">
                                    <div class="exp-left">
                                        <div class="exp-title">Business Analyst</div>
                                        <div class="exp-company">Financial Services Corp</div>
                                    </div>
                                    <div class="exp-date">2016 - 2018</div>
                                </div>
                                <ul class="exp-bullets">
                                    <li>Analyzed business requirements and translated them into technical specifications</li>
                                    <li>Facilitated stakeholder meetings and gathered project requirements</li>
                                    <li>Created process documentation and workflow diagrams</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">SKILLS</div>
                        <div class="section-content">
                            <p><strong>Project Management:</strong> Agile, Scrum, Kanban, Waterfall, Risk Management, Stakeholder Management</p>
                            <p><strong>Tools:</strong> Jira, Confluence, Microsoft Project, Slack, Trello, Asana</p>
                            <p><strong>Certifications:</strong> PMP, Certified Scrum Master (CSM), SAFe Agilist</p>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">EDUCATION</div>
                        <div class="section-content">
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Master of Business Administration (MBA)</div>
                                        <div class="edu-school">Northwestern Kellogg School of Management</div>
                                    </div>
                                    <div class="edu-date">2016</div>
                                </div>
                            </div>
                            
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Bachelor of Science in Information Systems</div>
                                        <div class="edu-school">University of Illinois at Chicago</div>
                                    </div>
                                    <div class="edu-date">2014</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateExecutivePreview() {
        return `
            <div class="template-preview-large template-executive-large">
                <div class="template-header">
                    <div class="header-left">
                        <div class="template-name">MICHAEL BROWN</div>
                        <div class="template-title">Chief Technology Officer</div>
                    </div>
                    <div class="header-right">
                        <div class="contact-line">michael.brown@email.com</div>
                        <div class="contact-line">(555) 321-9876</div>
                        <div class="contact-line">New York, NY 10001</div>
                        <div class="contact-line">linkedin.com/in/michaelbrown</div>
                    </div>
                </div>
                
                <div class="template-sections">
                    <div class="template-section">
                        <div class="section-title">EXECUTIVE SUMMARY</div>
                        <div class="section-content">
                            <p>Visionary technology leader with 15+ years of experience driving digital transformation and innovation at Fortune 500 companies. Proven track record of scaling engineering organizations from 50 to 500+ employees while maintaining 99.9% system uptime. Expert in cloud architecture, AI/ML implementation, and building high-performance teams that deliver $100M+ in annual revenue growth.</p>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">PROFESSIONAL EXPERIENCE</div>
                        <div class="section-content">
                            <div class="exp-item">
                                <div class="exp-title">Chief Technology Officer</div>
                                <div class="exp-meta">Global FinTech Solutions • 2020 - Present • New York, NY</div>
                                <ul class="exp-bullets">
                                    <li>Led digital transformation initiative resulting in $50M cost savings and 40% operational efficiency improvement</li>
                                    <li>Built and scaled engineering organization from 120 to 400+ professionals across 8 global offices</li>
                                    <li>Architected cloud-native platform processing 10M+ transactions daily with 99.99% uptime</li>
                                    <li>Implemented AI-driven fraud detection system reducing losses by $25M annually</li>
                                    <li>Established technical vision and roadmap for $2B revenue organization</li>
                                </ul>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-title">Vice President of Engineering</div>
                                <div class="exp-meta">Enterprise Software Corp • 2017 - 2020 • San Francisco, CA</div>
                                <ul class="exp-bullets">
                                    <li>Managed $15M engineering budget and 150+ person engineering team</li>
                                    <li>Delivered platform modernization project 6 months ahead of schedule</li>
                                    <li>Reduced infrastructure costs by 35% through cloud migration and optimization</li>
                                    <li>Established DevOps practices reducing deployment time from weeks to hours</li>
                                </ul>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-title">Director of Software Engineering</div>
                                <div class="exp-meta">Technology Innovations Inc • 2014 - 2017 • Boston, MA</div>
                                <ul class="exp-bullets">
                                    <li>Led development of award-winning SaaS platform serving 1M+ users</li>
                                    <li>Grew engineering team from 25 to 80 professionals while maintaining code quality</li>
                                    <li>Implemented microservices architecture improving system scalability by 300%</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">CORE COMPETENCIES</div>
                        <div class="section-content">
                            <div class="competencies-grid">
                                <div class="competency-column">
                                    <strong>Leadership & Strategy</strong>
                                    <ul>
                                        <li>Digital Transformation</li>
                                        <li>Technology Roadmapping</li>
                                        <li>Team Building & Scaling</li>
                                        <li>Budget Management</li>
                                    </ul>
                                </div>
                                <div class="competency-column">
                                    <strong>Technical Expertise</strong>
                                    <ul>
                                        <li>Cloud Architecture (AWS, Azure)</li>
                                        <li>Microservices & APIs</li>
                                        <li>AI/ML Implementation</li>
                                        <li>DevOps & CI/CD</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">EDUCATION & CERTIFICATIONS</div>
                        <div class="section-content">
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Master of Science in Computer Science</div>
                                        <div class="edu-school">Stanford University</div>
                                    </div>
                                    <div class="edu-date">2009</div>
                                </div>
                            </div>
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Bachelor of Engineering in Software Engineering</div>
                                        <div class="edu-school">Massachusetts Institute of Technology</div>
                                    </div>
                                    <div class="edu-date">2007</div>
                                </div>
                            </div>
                            <p><strong>Certifications:</strong> AWS Solutions Architect Professional, Certified Kubernetes Administrator (CKA)</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateTechnicalPreview() {
        return `
            <div class="template-preview-large template-technical-large">
                <div class="template-header">
                    <div class="template-name">SARAH WILSON</div>
                    <div class="template-title">Full Stack Developer</div>
                    <div class="template-contact">sarah.wilson@email.com • (555) 789-0123 • github.com/sarahwilson • portfolio.sarahwilson.dev</div>
                </div>
                
                <div class="template-layout">
                    <div class="left-column">
                        <div class="template-section">
                            <div class="section-title">TECHNICAL SKILLS</div>
                            <div class="section-content">
                                <div class="skill-category">
                                    <div class="skill-cat-title">Programming Languages</div>
                                    <div class="skill-items">JavaScript (ES6+), TypeScript, Python, Java, Go, SQL</div>
                                </div>
                                
                                <div class="skill-category">
                                    <div class="skill-cat-title">Frontend Technologies</div>
                                    <div class="skill-items">React, Vue.js, Angular, HTML5, CSS3, Sass, Tailwind CSS, Webpack</div>
                                </div>
                                
                                <div class="skill-category">
                                    <div class="skill-cat-title">Backend Technologies</div>
                                    <div class="skill-items">Node.js, Express, Django, Spring Boot, Flask, GraphQL, REST APIs</div>
                                </div>
                                
                                <div class="skill-category">
                                    <div class="skill-cat-title">Databases</div>
                                    <div class="skill-items">PostgreSQL, MongoDB, Redis, MySQL, Elasticsearch</div>
                                </div>
                                
                                <div class="skill-category">
                                    <div class="skill-cat-title">DevOps & Tools</div>
                                    <div class="skill-items">Docker, Kubernetes, AWS, Git, Jenkins, Terraform, Nginx</div>
                                </div>
                                
                                <div class="skill-category">
                                    <div class="skill-cat-title">Testing</div>
                                    <div class="skill-items">Jest, Cypress, PyTest, JUnit, Test-Driven Development</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-section">
                            <div class="section-title">CERTIFICATIONS</div>
                            <div class="section-content">
                                <ul class="cert-list">
                                    <li>AWS Certified Developer - Associate</li>
                                    <li>Google Cloud Professional Developer</li>
                                    <li>Certified Kubernetes Application Developer</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="right-column">
                        <div class="template-section">
                            <div class="section-title">PROFESSIONAL EXPERIENCE</div>
                            <div class="section-content">
                                <div class="exp-item">
                                    <div class="exp-header">
                                        <div class="exp-title">Senior Full Stack Developer</div>
                                        <div class="exp-date">2022 - Present</div>
                                    </div>
                                    <div class="exp-company">TechStart Innovations</div>
                                    <ul class="exp-bullets">
                                        <li>Architected and developed microservices-based e-commerce platform handling 100K+ daily users</li>
                                        <li>Improved application performance by 60% through code optimization and caching strategies</li>
                                        <li>Led migration from monolith to microservices, reducing deployment time by 80%</li>
                                        <li>Mentored 3 junior developers and established coding standards and best practices</li>
                                    </ul>
                                </div>
                                
                                <div class="exp-item">
                                    <div class="exp-header">
                                        <div class="exp-title">Full Stack Developer</div>
                                        <div class="exp-date">2020 - 2022</div>
                                    </div>
                                    <div class="exp-company">Digital Solutions Agency</div>
                                    <ul class="exp-bullets">
                                        <li>Developed responsive web applications using React, Node.js, and PostgreSQL</li>
                                        <li>Implemented automated testing pipeline reducing bugs in production by 45%</li>
                                        <li>Built RESTful APIs and GraphQL endpoints for mobile and web clients</li>
                                        <li>Collaborated with UX/UI designers to create pixel-perfect user interfaces</li>
                                    </ul>
                                </div>
                                
                                <div class="exp-item">
                                    <div class="exp-header">
                                        <div class="exp-title">Software Developer</div>
                                        <div class="exp-date">2019 - 2020</div>
                                    </div>
                                    <div class="exp-company">FinTech Solutions</div>
                                    <ul class="exp-bullets">
                                        <li>Developed secure payment processing system handling $10M+ monthly transactions</li>
                                        <li>Implemented real-time fraud detection algorithms reducing false positives by 30%</li>
                                        <li>Created automated deployment pipeline using Docker and Jenkins</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-section">
                            <div class="section-title">KEY PROJECTS</div>
                            <div class="section-content">
                                <div class="project-item">
                                    <div class="project-title">E-Commerce Platform Redesign</div>
                                    <div class="project-tech">React, Node.js, MongoDB, AWS</div>
                                    <p>Led complete redesign of e-commerce platform, resulting in 35% increase in conversion rate and 50% improvement in page load times.</p>
                                </div>
                                
                                <div class="project-item">
                                    <div class="project-title">Real-time Analytics Dashboard</div>
                                    <div class="project-tech">Vue.js, Python, PostgreSQL, WebSockets</div>
                                    <p>Built real-time analytics dashboard processing 1M+ events per day with sub-second latency.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="template-section">
                            <div class="section-title">EDUCATION</div>
                            <div class="section-content">
                                <div class="edu-item">
                                    <div class="edu-header">
                                        <div class="edu-title">Bachelor of Science in Computer Science</div>
                                        <div class="edu-date">2019</div>
                                    </div>
                                    <div class="edu-school">University of California, Berkeley</div>
                                    <div class="edu-details">GPA: 3.9/4.0, Summa Cum Laude</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    generateCreativePreview() {
        return `
            <div class="template-preview-large template-creative-large">
                <div class="template-header">
                    <div class="header-design">
                        <div class="template-name">EMMA DAVIS</div>
                        <div class="template-title">Creative Marketing Manager</div>
                        <div class="header-line"></div>
                        <div class="template-contact">emma.davis@email.com • (555) 654-3210 • Los Angeles, CA • behance.net/emmadavis</div>
                    </div>
                </div>
                
                <div class="template-sections">
                    <div class="template-section">
                        <div class="section-title">CREATIVE PROFILE</div>
                        <div class="section-content">
                            <p>Innovative Marketing Manager with 8+ years of experience creating compelling brand narratives and driving customer engagement through creative campaigns. Expertise in digital marketing, content strategy, and brand development. Proven track record of increasing brand awareness by 150% and generating $5M+ in revenue through creative marketing initiatives.</p>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">PROFESSIONAL EXPERIENCE</div>
                        <div class="section-content">
                            <div class="exp-item">
                                <div class="exp-badge">2021-2024</div>
                                <div class="exp-content">
                                    <div class="exp-title">Senior Marketing Manager</div>
                                    <div class="exp-company">Creative Brands Agency</div>
                                    <ul class="exp-bullets">
                                        <li>Developed integrated marketing campaigns for 25+ luxury brands, generating $8M in revenue</li>
                                        <li>Led rebranding initiative that increased client's market share by 40%</li>
                                        <li>Managed creative team of 12 designers, copywriters, and strategists</li>
                                        <li>Launched viral social media campaign reaching 10M+ users and earning industry recognition</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-badge">2019-2021</div>
                                <div class="exp-content">
                                    <div class="exp-title">Marketing Manager</div>
                                    <div class="exp-company">Fashion Forward Inc.</div>
                                    <ul class="exp-bullets">
                                        <li>Created content strategy increasing social media engagement by 200%</li>
                                        <li>Coordinated influencer partnerships resulting in 50% increase in brand awareness</li>
                                        <li>Managed $2M annual marketing budget across digital and traditional channels</li>
                                        <li>Launched email marketing campaigns with 35% open rate (industry avg: 20%)</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="exp-item">
                                <div class="exp-badge">2017-2019</div>
                                <div class="exp-content">
                                    <div class="exp-title">Digital Marketing Specialist</div>
                                    <div class="exp-company">StartUp Media</div>
                                    <ul class="exp-bullets">
                                        <li>Developed SEO strategy increasing organic traffic by 300%</li>
                                        <li>Created and managed PPC campaigns with 15% conversion rate</li>
                                        <li>Designed landing pages that improved conversion rates by 45%</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">CORE COMPETENCIES</div>
                        <div class="section-content">
                            <div class="skills-creative">
                                <div class="skill-group">
                                    <strong>Strategy & Planning:</strong> Brand Development, Campaign Strategy, Market Research, Competitive Analysis
                                </div>
                                <div class="skill-group">
                                    <strong>Digital Marketing:</strong> Social Media Marketing, SEO/SEM, Email Marketing, Content Marketing, PPC
                                </div>
                                <div class="skill-group">
                                    <strong>Creative Tools:</strong> Adobe Creative Suite, Figma, Canva, Sketch, InVision
                                </div>
                                <div class="skill-group">
                                    <strong>Analytics:</strong> Google Analytics, Facebook Analytics, HubSpot, Salesforce, Tableau
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">ACHIEVEMENTS & RECOGNITION</div>
                        <div class="section-content">
                            <ul class="achievements-list">
                                <li><strong>Marketing Campaign of the Year</strong> - American Marketing Association (2023)</li>
                                <li><strong>Digital Innovation Award</strong> - Los Angeles Business Journal (2022)</li>
                                <li><strong>Rising Star in Marketing</strong> - Marketing Excellence Awards (2021)</li>
                                <li><strong>Best Social Media Campaign</strong> - Social Media Marketing Awards (2020)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="template-section">
                        <div class="section-title">EDUCATION</div>
                        <div class="section-content">
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Master of Business Administration - Marketing</div>
                                        <div class="edu-school">UCLA Anderson School of Management</div>
                                    </div>
                                    <div class="edu-date">2017</div>
                                </div>
                            </div>
                            
                            <div class="edu-item">
                                <div class="edu-header">
                                    <div class="edu-left">
                                        <div class="edu-title">Bachelor of Arts in Communications</div>
                                        <div class="edu-school">University of Southern California</div>
                                    </div>
                                    <div class="edu-date">2015</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    closePreviewModal() {
        document.getElementById('template-preview-modal').classList.remove('active');
        document.body.style.overflow = '';
        this.currentTemplate = null;
    }
    
    isUserLoggedIn() {
        // Check if user is logged in (this would be set by PHP)
        return document.querySelector('.user-menu') !== null;
    }
    
    showLoginPrompt() {
        if (confirm('Please log in to use this template. Would you like to go to the login page?')) {
            window.location.href = 'auth/login.php?redirect=' + encodeURIComponent('templates.php');
        }
    }
    
    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}

// Initialize template manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.templateManager = new TemplateManager();
});

// Add CSS for large previews
const previewStyles = `
<style>
.template-preview-large {
    width: 600px;
    min-height: 800px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 40px;
    font-size: 12px;
    line-height: 1.4;
    color: #000;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.template-preview-large .template-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #000;
}

.template-preview-large .template-name {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 8px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.template-preview-large .template-title {
    font-size: 16px;
    color: #666;
    margin-bottom: 12px;
}

.template-preview-large .template-contact {
    font-size: 11px;
    color: #666;
}

.template-preview-large .section-title {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 12px;
    padding-bottom: 4px;
    border-bottom: 1px solid #ccc;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.template-preview-large .template-section {
    margin-bottom: 25px;
}

.template-preview-large .exp-item {
    margin-bottom: 20px;
}

.template-preview-large .exp-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 4px;
}

.template-preview-large .exp-title {
    font-weight: bold;
    font-size: 13px;
}

.template-preview-large .exp-company {
    font-style: italic;
    color: #666;
    margin-bottom: 8px;
}

.template-preview-large .exp-date {
    font-size: 11px;
    color: #666;
}

.template-preview-large .exp-bullets {
    margin-left: 20px;
    margin-bottom: 0;
}

.template-preview-large .exp-bullets li {
    margin-bottom: 4px;
    font-size: 11px;
    line-height: 1.4;
}

.template-preview-large .skills-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.template-preview-large .skill-category {
    margin-bottom: 8px;
    font-size: 11px;
}

.template-preview-large .edu-item {
    margin-bottom: 15px;
}

.template-preview-large .edu-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 4px;
}

.template-preview-large .edu-title {
    font-weight: bold;
    font-size: 12px;
}

.template-preview-large .edu-school {
    font-style: italic;
    color: #666;
    font-size: 11px;
}

.template-preview-large .edu-date {
    font-size: 11px;
    color: #666;
}

/* Modern Template Large */
.template-modern-large {
    display: flex;
    padding: 0;
}

.template-modern-large .template-sidebar {
    width: 35%;
    background: #2563eb;
    color: white;
    padding: 30px 20px;
}

.template-modern-large .template-main {
    width: 65%;
    padding: 30px;
}

.template-modern-large .template-name {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 8px;
}

.template-modern-large .template-title {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 20px;
}

.template-modern-large .sidebar-section {
    margin-bottom: 25px;
}

.template-modern-large .section-title {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.template-modern-large .contact-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 10px;
}

.template-modern-large .skill-item {
    margin-bottom: 12px;
}

.template-modern-large .skill-name {
    display: block;
    font-size: 10px;
    margin-bottom: 4px;
}

.template-modern-large .skill-bar {
    height: 4px;
    background: rgba(255,255,255,0.3);
    border-radius: 2px;
    overflow: hidden;
}

.template-modern-large .skill-progress {
    height: 100%;
    background: #fbbf24;
    border-radius: 2px;
}

/* Executive Template Large */
.template-executive-large .template-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #000;
}

.template-executive-large .header-right {
    text-align: right;
}

.template-executive-large .contact-line {
    font-size: 11px;
    color: #666;
    margin-bottom: 3px;
}

.template-executive-large .exp-meta {
    font-style: italic;
    color: #666;
    margin-bottom: 8px;
    font-size: 11px;
}

.template-executive-large .competencies-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.template-executive-large .competency-column ul {
    margin-left: 20px;
    margin-top: 8px;
}

.template-executive-large .competency-column li {
    margin-bottom: 4px;
    font-size: 11px;
}

/* Technical Template Large */
.template-technical-large .template-layout {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.template-technical-large .left-column {
    width: 40%;
}

.template-technical-large .right-column {
    width: 60%;
}

.template-technical-large .skill-category {
    margin-bottom: 15px;
}

.template-technical-large .skill-cat-title {
    font-weight: bold;
    font-size: 11px;
    margin-bottom: 6px;
    color: #2563eb;
}

.template-technical-large .skill-items {
    font-size: 10px;
    line-height: 1.4;
    color: #666;
}

.template-technical-large .cert-list {
    margin-left: 20px;
}

.template-technical-large .cert-list li {
    margin-bottom: 6px;
    font-size: 11px;
}

.template-technical-large .project-item {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 6px;
}

.template-technical-large .project-title {
    font-weight: bold;
    font-size: 12px;
    margin-bottom: 4px;
}

.template-technical-large .project-tech {
    font-size: 10px;
    color: #2563eb;
    margin-bottom: 8px;
}

.template-technical-large .project-item p {
    font-size: 10px;
    line-height: 1.4;
    color: #666;
}

/* Creative Template Large */
.template-creative-large .header-design {
    position: relative;
}

.template-creative-large .template-name {
    font-size: 28px;
    font-weight: bold;
    color: #7c3aed;
    margin-bottom: 8px;
}

.template-creative-large .template-title {
    font-size: 16px;
    color: #666;
    margin-bottom: 15px;
}

.template-creative-large .header-line {
    height: 4px;
    background: linear-gradient(to right, #7c3aed, #a855f7);
    width: 120px;
    margin-bottom: 15px;
}

.template-creative-large .section-title {
    font-size: 14px;
    font-weight: bold;
    color: #7c3aed;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.template-creative-large .exp-item {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
}

.template-creative-large .exp-badge {
    background: #7c3aed;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: bold;
    height: fit-content;
    min-width: 80px;
    text-align: center;
}

.template-creative-large .exp-content {
    flex: 1;
}

.template-creative-large .exp-title {
    font-size: 13px;
    font-weight: bold;
    margin-bottom: 4px;
}

.template-creative-large .exp-company {
    font-size: 11px;
    color: #666;
    margin-bottom: 8px;
    font-style: italic;
}

.template-creative-large .skills-creative {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}

.template-creative-large .skill-group {
    font-size: 11px;
    line-height: 1.4;
}

.template-creative-large .achievements-list {
    margin-left: 20px;
}

.template-creative-large .achievements-list li {
    margin-bottom: 8px;
    font-size: 11px;
    line-height: 1.4;
}

/* No results styles */
.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.no-results-content i {
    font-size: 48px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.no-results-content h3 {
    font-size: 24px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 12px;
}

.no-results-content p {
    color: #6b7280;
    margin-bottom: 24px;
    font-size: 16px;
}
</style>
`;

// Inject styles
document.head.insertAdjacentHTML('beforeend', previewStyles);