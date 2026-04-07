# 🗳️ College Voting System – Setup & Run Guide

## ✅ Prerequisites
- **XAMPP** (PHP 7.4+ / 8.x) installed at `C:\xampp\`
- Apache & MySQL running in XAMPP Control Panel
- Browser (Chrome/Firefox/Edge)

---

## 🚀 Step 1: Place Files
1. This project folder is already in:  
   `f:\project 2026\collage voting system\`
2. Copy it to XAMPP's `htdocs`:  
   ```
   C:\xampp\htdocs\collage voting system\
   ```
   OR create a symlink from XAMPP htdocs.

**Quick copy command (run in PowerShell as Admin):**
```powershell
Copy-Item "f:\project 2026\collage voting system" "C:\xampp\htdocs\collage voting system" -Recurse
```

---

## 📦 Step 2: Import Database
1. Open **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** → Enter database name: `college_voting_system` → Click **Create**
3. Click the new database → Go to **Import** tab
4. Click **Choose File** → Select:
   ```
   collage voting system\database\voting_system.sql
   ```
5. Click **Go** → Wait for success message

---

## ⚙️ Step 3: Configuration
The config file is already set up at `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'college_voting_system');
define('DB_USER', 'root');
define('DB_PASS', '');  // blank for XAMPP default
define('BASE_URL', 'http://localhost/collage%20voting%20system');
```

> No changes needed for default XAMPP setup!

---

## 🌐 Step 4: Open in Browser
Visit: **[http://localhost/collage%20voting%20system/](http://localhost/collage%20voting%20system/)**

---

## 🔐 Default Login Credentials

| Role | Email | Password |
|------|-------|---------|
| **Super Admin** | admin@college.edu | admin123 |
| **HOD** | Register via HOD portal | your choice |
| **Student** | Register → Admin approves | your choice |
| **Teacher** | Register → Admin approves | your choice |

> Admin account is seeded by the SQL file.

---

## 📁 Project Structure

```
collage voting system/
├── index.php              ← Home page (public)
├── login.php              ← Unified login (all roles)
├── register.php           ← Student/Teacher registration
├── forgot_password.php    ← OTP-based password reset
├── logout.php             ← Logout handler
│
├── admin/                 ← Admin Panel
│   ├── index.php          ← Dashboard (stats, charts)
│   ├── elections.php      ← Manage elections
│   ├── candidates.php     ← Manage candidates
│   ├── results.php        ← View results
│   ├── departments.php    ← Manage departments
│   ├── students.php       ← Approve students
│   ├── teachers.php       ← Approve teachers
│   ├── announcements.php  ← Post notices
│   ├── audit_logs.php     ← Security logs
│   └── backup.php         ← Database backup
│
├── student/               ← Student Panel
│   ├── index.php          ← Dashboard
│   ├── elections.php      ← Browse elections
│   ├── vote.php           ← Cast vote (OTP secured)
│   ├── results.php        ← View published results
│   ├── profile.php        ← Update profile
│   └── certificate.php    ← Participation certificate
│
├── teacher/               ← Teacher Panel
│   ├── index.php          ← Dashboard
│   ├── elections.php      ← Teacher elections
│   ├── vote.php           ← Cast vote
│   ├── results.php        ← View results
│   └── profile.php        ← Update profile
│
├── hod/                   ← HOD Panel
│   ├── index.php          ← Dashboard + candidate approval
│   └── ...
│
├── api/
│   └── results.php        ← Live vote count JSON API
│
├── includes/
│   ├── config.php         ← DB config, constants
│   ├── functions.php      ← Helper functions
│   ├── auth.php           ← Login logic, role guards
│   ├── otp.php            ← OTP generation/verify
│   ├── header.php         ← Public header
│   ├── footer.php         ← Public footer
│   ├── dashboard_header.php ← Admin/User dashboard header
│   └── dashboard_footer.php ← Dashboard footer
│
├── assets/
│   ├── css/style.css      ← Full glassmorphism UI system
│   ├── js/main.js         ← Core JS (dark mode, OTP, countdown)
│   └── js/charts.js       ← Chart.js wrapper
│
├── uploads/
│   ├── profiles/          ← User profile photos
│   ├── candidates/        ← Candidate photos & symbols
│   └── election_banners/  ← Election banner images
│
└── database/
    └── voting_system.sql  ← Full DB schema + seed data
```

---

## 🔑 Key Features

### Security
- 🔐 OTP-based 2-factor authentication for login, voting, and registration
- 🛡️ Math CAPTCHA on login and register forms
- 🔒 Password hashing using bcrypt
- 🔍 CSRF protection on all forms
- ⚠️ Suspicious IP detection (5+ failed logins)
- 📱 Device fingerprinting to prevent duplicate votes

### Elections
- ✅ Create elections: Student, CR, Teacher, HOD, Cultural, Sports, General
- ⏱️ Automatic status updates (upcoming → active → completed)
- ❄️ Admin can freeze/unfreeze elections
- 📊 Real-time vote count via `/api/results.php`

### Results
- 🏆 Winner card with crown animation
- 📊 Leaderboard with progress bars
- 🥧 Pie/doughnut chart (Chart.js)
- 🔄 Auto-refresh every 5 seconds for live elections

### Dashboards
- 📈 Admin: stats, charts, recent activity, pending approvals
- 🎓 Student: elections, vote status, countdown timers, certificate
- 👩‍🏫 Teacher: teacher-specific elections
- 🏛️ HOD: department elections, turnout %, candidate approval

---

## 🎨 UI Design
- **Theme**: Glassmorphism with dark mode default
- **Colors**: Purple/Cyan gradient palette
- **Font**: Inter (Google Fonts)
- **Charts**: Chart.js (CDN)
- **Icons**: Font Awesome 6 (CDN)
- **Dark/Light toggle** in top navigation

---

## 🧪 Demo Mode (OTP)
Since this is a college demo project without email server:
- OTP is displayed **on-screen** in a colored banner
- In production, replace with PHPMailer or SMTP in `includes/otp.php`

---

## 📖 Demo Workflow
1. Open homepage → See active elections
2. Login as Admin → Create election, add candidates
3. Register as Student → Admin approves
4. Student logs in → Votes in election (OTP shown on screen)
5. Admin publishes results → Students view leaderboard
6. Student downloads participation certificate

---

## ⚡ Troubleshooting

| Problem | Solution |
|---------|----------|
| Database connection error | Ensure XAMPP MySQL is running |
| Page not found | Check files are in `C:\xampp\htdocs\collage voting system\` |
| OTP not showing | Look for the blue "DEMO MODE" OTP box |
| Images not loading | Check `uploads/` folder permissions |
| Login fails | Make sure DB was imported and `admin@college.edu`/`admin123` credentials |

---

**Built with 💙 for ABC College of Engineering | Final Year Project 2026**
