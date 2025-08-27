# AI-Powered Resume Builder (ATS Optimized)

A comprehensive web-based resume builder application that helps users create professional, ATS-optimized resumes with AI assistance. Built with HTML, CSS, JavaScript, PHP, and MySQL.

## 🚀 Features

### Core Features
- **AI-Assisted Resume Creation** - Smart content suggestions and optimization
- **ATS Scoring Engine** - Real-time compatibility scoring with detailed recommendations
- **Job Description Parser** - Extract keywords and requirements from job postings
- **Multiple Export Formats** - PDF, DOCX, and JSON export options
- **Resume Version Management** - Track and manage different resume versions
- **Professional Templates** - 6 ATS-optimized templates for different industries

### Advanced Features
- **Real-time Preview** - Live preview while editing
- **Keyword Optimization** - AI-powered keyword matching and suggestions
- **Achievement Quantifier** - AI adds metrics and measurable impact
- **Role Translator** - Rewrite experience for different industries
- **Collaboration Tools** - Share resumes for feedback and comments
- **Mobile-First Design** - Fully responsive across all devices

## 🛠 Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Styling**: Custom CSS with Flexbox/Grid
- **Icons**: Font Awesome 6
- **Fonts**: Inter (Google Fonts)

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Modern web browser

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd resume-builder
```

### 2. Database Setup
```sql
-- Create database
CREATE DATABASE resume_builder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import the schema (tables will be created automatically on first run)
```

### 3. Configure Database Connection
Update the database configuration in `config/database.php`:
```php
define('DB_HOST', 'your-host');
define('DB_USERNAME', 'your-username');
define('DB_PASSWORD', 'your-password');
define('DB_NAME', 'your-database');
```

### 4. Web Server Configuration
Point your web server document root to the project directory.

#### Apache (.htaccess included)
```apache
DocumentRoot /path/to/resume-builder
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/resume-builder;
    index index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### 5. Set Permissions
```bash
chmod 755 /path/to/resume-builder
chmod 644 /path/to/resume-builder/config/database.php
```

## 🚀 Usage

### Getting Started
1. Visit your application URL
2. Register for a new account or login
3. Access the dashboard to manage resumes
4. Create your first resume using the resume builder

### Creating a Resume
1. Go to **Dashboard** → **Create New Resume**
2. Choose a template or start from scratch
3. Fill in your personal information
4. Add sections (Experience, Education, Skills, etc.)
5. Use AI tools to optimize content
6. Preview and export your resume

### Using AI Features
- **Parse Job Description**: Paste a job posting to extract keywords
- **ATS Scoring**: Get real-time compatibility scores
- **AI Optimization**: Enhance content with AI suggestions
- **Keyword Matching**: Ensure your resume matches job requirements

## 📁 Project Structure

```
resume-builder/
├── api/                    # Backend API endpoints
│   ├── calculate-ats-score.php
│   ├── dashboard-data.php
│   ├── delete-resume.php
│   ├── export-resume.php
│   ├── parse-job-description.php
│   └── save-resume.php
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   │   ├── main.css
│   │   ├── dashboard.css
│   │   ├── resume-builder.css
│   │   └── templates.css
│   └── js/                # JavaScript files
│       ├── main.js
│       ├── dashboard.js
│       ├── resume-builder.js
│       └── templates.js
├── auth/                  # Authentication pages
│   ├── login.php
│   ├── logout.php
│   └── register.php
├── config/                # Configuration files
│   └── database.php
├── dashboard.php          # User dashboard
├── index.php             # Landing page
├── resume-builder.php    # Resume editor
├── templates.php         # Template gallery
└── README.md
```

## 🎯 Key Components

### Database Schema
- **users**: User accounts and authentication
- **resumes**: Resume metadata and settings
- **sections**: Resume sections and content
- **job_descriptions**: Parsed job postings and keywords

### API Endpoints
- `POST /api/save-resume.php` - Save resume data
- `POST /api/parse-job-description.php` - Parse job descriptions
- `POST /api/calculate-ats-score.php` - Calculate ATS scores
- `GET /api/export-resume.php` - Export resumes
- `POST /api/delete-resume.php` - Delete resumes
- `GET /api/dashboard-data.php` - Dashboard statistics

### Resume Builder Features
- **Section Management**: Add/remove/reorder resume sections
- **Real-time Preview**: Live preview with template rendering
- **Auto-save**: Automatic saving every 30 seconds
- **Template Selection**: Choose from 6 professional templates
- **Export Options**: PDF, DOCX, JSON formats

## 🎨 Templates

### Available Templates
1. **Professional** - Clean, traditional design for corporate roles
2. **Modern** - Two-column layout for creative professionals
3. **Minimal** - Simple, content-focused design
4. **Executive** - Sophisticated layout for senior positions
5. **Technical** - Skills-focused design for developers
6. **Creative** - Eye-catching design for creative industries

All templates are:
- ✅ ATS-compatible
- ✅ Mobile-responsive
- ✅ Print-friendly
- ✅ Customizable

## 📊 ATS Scoring Algorithm

The ATS scoring system evaluates resumes based on:

- **Contact Information (15%)** - Complete contact details
- **Professional Summary (10%)** - Quality and relevance
- **Work Experience (25%)** - Detailed experience with metrics
- **Skills Section (20%)** - Relevant technical and soft skills
- **Education (10%)** - Educational background
- **Formatting (10%)** - ATS-friendly structure
- **Keywords (10%)** - Job-relevant keyword density

## 🔮 Future Enhancements

### Planned Features
- [ ] Advanced AI integrations (GPT-4, Claude)
- [ ] LinkedIn integration for profile import
- [ ] Resume analytics and success tracking
- [ ] Bulk resume management for recruiters
- [ ] Advanced collaboration features
- [ ] Mobile app development
- [ ] Integration with job boards
- [ ] A/B testing for resume variations

### Technical Improvements
- [ ] Implement proper PDF generation library
- [ ] Add Redis caching for performance
- [ ] Implement rate limiting
- [ ] Add comprehensive test suite
- [ ] Docker containerization
- [ ] CI/CD pipeline setup

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email support@resumeai.com or create an issue in the repository.

## 🙏 Acknowledgments

- Font Awesome for icons
- Google Fonts for typography
- Open source community for inspiration
- Beta testers and early users for feedback

---

**Built with ❤️ for job seekers worldwide**