# AI-Powered Resume Builder

A comprehensive, ATS-optimized resume builder built with HTML, CSS, JavaScript, and PHP. This application helps job seekers create professional resumes that pass Applicant Tracking Systems and land more interviews.

## 🚀 Features

### Core Features
- **AI-Assisted Resume Creation**: Intelligent content suggestions and optimization
- **ATS-Optimized Templates**: Professional templates designed to pass ATS systems
- **Job Description Parsing**: Extract keywords and requirements from job postings
- **Real-time ATS Scoring**: Get instant feedback on resume optimization
- **AI-Powered Tailoring**: Automatically customize resumes for specific job descriptions
- **Multi-format Export**: PDF, DOCX, and JSON export options
- **Resume Version Management**: Track and manage multiple resume versions

### Advanced Features
- **Keyword Analysis**: Identify missing keywords from job descriptions
- **Achievement Quantifier**: AI suggestions for measurable impact statements
- **Section-based Editor**: Organized editing for different resume sections
- **Auto-save Functionality**: Never lose your work
- **Responsive Design**: Works on desktop, tablet, and mobile devices

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+ with JSON support
- **Styling**: Custom CSS with Flexbox/Grid
- **Architecture**: MVC pattern with RESTful APIs

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd ai-resume-builder
```

### 2. Database Setup
Update the database configuration in `config/database.php`:

```php
private $host = "127.0.0.1:3306";
private $username = "u562257438_devopsplay";
private $password = "Devopsplay@#83";
private $database = "u562257438_devopsplay";
```

### 3. Initialize Database
Run the initialization script to create tables:
```bash
php init.php
```

### 4. Web Server Configuration
Ensure your web server points to the project root directory and has mod_rewrite enabled.

### 5. Permissions
Set appropriate permissions for the project directory:
```bash
chmod -R 755 /path/to/project
```

## 🗂️ Project Structure

```
ai-resume-builder/
├── api/                    # API endpoints
│   ├── ai-tailor.php      # AI resume tailoring
│   ├── ats-scorer.php     # ATS scoring system
│   ├── export.php         # Resume export functionality
│   ├── jd-parser.php      # Job description parsing
│   └── resume.php         # Resume CRUD operations
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── main.js        # Main JavaScript functionality
├── classes/               # PHP classes
│   └── ResumeManager.php  # Resume management logic
├── config/                # Configuration files
│   └── database.php       # Database configuration
├── includes/              # PHP includes
│   └── auth.php          # Authentication logic
├── dashboard.php          # User dashboard
├── editor.php            # Resume editor interface
├── index.php             # Landing page
├── login.php             # User login
├── logout.php            # User logout
├── preview.php           # Resume preview
├── register.php          # User registration
├── init.php              # Database initialization
├── .htaccess             # Apache configuration
└── README.md             # This file
```

## 🎯 Core Components

### Authentication System (`includes/auth.php`)
- User registration and login
- Session management
- Password hashing with PHP's password_hash()
- Authorization checks

### Resume Management (`classes/ResumeManager.php`)
- CRUD operations for resumes
- Section management (Summary, Experience, Education, Skills, Projects)
- User statistics and analytics
- Resume version tracking

### Job Description Parser (`api/jd-parser.php`)
- Keyword extraction from job descriptions
- Technical skill identification
- Action verb recognition
- Industry-specific term detection

### ATS Scoring System (`api/ats-scorer.php`)
- Multi-factor scoring algorithm:
  - Keywords matching (40%)
  - Skills coverage (30%)
  - Format compliance (20%)
  - Content quality (10%)
- Personalized recommendations
- Score breakdown and analytics

### AI Tailoring Engine (`api/ai-tailor.php`)
- Resume customization based on job descriptions
- Keyword integration
- Content enhancement
- Skills optimization

## 🎨 User Interface

### Responsive Design
- Mobile-first approach
- Flexbox and CSS Grid layouts
- Optimized for all screen sizes
- Print-friendly resume previews

### Key Pages
1. **Landing Page** (`index.php`): Marketing page with features and benefits
2. **Dashboard** (`dashboard.php`): Resume management and statistics
3. **Editor** (`editor.php`): Full-featured resume editing interface
4. **Preview** (`preview.php`): Print-ready resume preview

## 🔐 Security Features

- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- CSRF protection for forms
- Secure password hashing
- Session security measures
- Input validation and sanitization

## 📊 Database Schema

### Users Table
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Resumes Table
```sql
CREATE TABLE resumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(191) DEFAULT 'My Resume',
    target_role VARCHAR(191),
    language VARCHAR(10) DEFAULT 'en',
    ats_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### Sections Table
```sql
CREATE TABLE sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    type ENUM('summary','experience','education','skills','projects') NOT NULL,
    position INT DEFAULT 0,
    content TEXT,
    FOREIGN KEY (resume_id) REFERENCES resumes(id) ON DELETE CASCADE
);
```

### Job Descriptions Table
```sql
CREATE TABLE job_descriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resume_id INT,
    raw_text TEXT,
    extracted_keywords JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🚀 API Endpoints

### Resume Management
- `GET /api/resume.php?action=get&id={id}` - Get resume by ID
- `GET /api/resume.php?action=list` - List user resumes
- `POST /api/resume.php` - Create/update/delete resume

### Job Description Processing
- `POST /api/jd-parser.php` - Parse job description and extract keywords

### ATS Scoring
- `POST /api/ats-scorer.php` - Calculate ATS compatibility score

### AI Tailoring
- `POST /api/ai-tailor.php` - Tailor resume for specific job description

### Export
- `POST /api/export.php` - Export resume in various formats

## 🎯 ATS Optimization Rules

### Format Compliance
- No tables, columns, or complex layouts
- Standard fonts (Arial, Calibri, Times New Roman)
- Standard section headers
- No headers/footers for critical information
- Clean, simple formatting

### Content Guidelines
- Action verbs at the beginning of bullet points
- Quantified achievements with numbers and percentages
- Relevant keywords from job descriptions
- Proper section organization
- Appropriate content length (300-800 words)

## 🔄 Workflow

1. **User Registration/Login**: Secure account creation and authentication
2. **Resume Creation**: Start with templates or create from scratch
3. **Job Description Analysis**: Paste job description to extract keywords
4. **AI Tailoring**: Automatically optimize resume for the specific role
5. **ATS Scoring**: Get real-time feedback on ATS compatibility
6. **Preview & Export**: Review final resume and export in desired format

## 🎨 Customization

### Adding New Resume Sections
1. Update the `sections` table enum in the database
2. Add section handling in `ResumeManager.php`
3. Create section templates in `main.js`
4. Add rendering logic in the preview system

### Extending ATS Scoring
1. Modify the scoring algorithm in `ats-scorer.php`
2. Add new criteria and weights
3. Update the UI to display new metrics

### Adding Export Formats
1. Extend the `ResumeExporter` class in `export.php`
2. Add format-specific rendering methods
3. Update the export modal UI

## 🐛 Troubleshooting

### Common Issues

**Database Connection Error**
- Verify database credentials in `config/database.php`
- Ensure MySQL server is running
- Check database permissions

**PDF Export Not Working**
- Install PHP extensions: `php-gd`, `php-xml`
- For production, consider using libraries like TCPDF or mPDF

**Session Issues**
- Check PHP session configuration
- Verify write permissions for session storage
- Ensure cookies are enabled

## 📈 Performance Optimization

### Frontend
- CSS and JS minification
- Image optimization
- Lazy loading for large content
- Browser caching with appropriate headers

### Backend
- Database query optimization
- Prepared statement caching
- Session optimization
- API response caching

### Database
- Proper indexing on frequently queried columns
- JSON column optimization for keywords
- Regular database maintenance

## 🚀 Deployment

### Production Checklist
1. Update database credentials
2. Enable HTTPS
3. Set proper file permissions
4. Configure error logging
5. Enable production optimizations
6. Set up regular backups
7. Configure monitoring

### Environment Variables
Create a `.env` file for production:
```
DB_HOST=your_host
DB_USER=your_username
DB_PASS=your_password
DB_NAME=your_database
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🙏 Acknowledgments

- Built following ATS best practices from leading recruitment platforms
- Inspired by modern resume builders and career development tools
- Designed with accessibility and usability in mind

## 📞 Support

For support and questions:
- Create an issue in the repository
- Check the documentation
- Review the troubleshooting section

---

**Built with ❤️ for job seekers worldwide**