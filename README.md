# Web-Based Academic Transcript Generating System with QR Verification

**Institution:** Kano State Polytechnic  
**School:** School of Technology  
**Department:** Department of Computer Science  

---

## 1. Project Overview

The **Web-Based Academic Transcript Generating System with QR Verification** is an enterprise-grade academic administration platform engineered specifically for the Computer Science Department of Kano State Polytechnic.

The system manages student academic records, curriculum courses, semester examination results, calculates real-time GPA and CGPA using dynamic NBTE grading scales, generates tamper-evident official academic transcripts in PDF format, and features a public cryptographic QR verification portal to detect and prevent credentials forgery.

---

## 2. Technology Stack & Strict Architectural Restrictions

### Frontend (Zero Frameworks)
- **HTML5:** Semantic, accessible layout.
- **Tailwind CSS:** Modern institutional dashboard design with dark-blue primary branding (`#0f172a`, `#1e3a8a`, `#0284c7`).
- **Vanilla JavaScript (ES6+):** Pure fetch API communication with backend REST endpoints; zero external JavaScript frameworks (no React, Vue, Angular, Bootstrap, or jQuery).
- **Inline SVG Icons & Vectors:** High-resolution responsive icons without icon library bloat.

### Backend (Native Core PHP 8.1+)
- **PHP 8.1+ / 8.5+:** Modular Native Core PHP (No Laravel, Symfony, CodeIgniter, or external MVC frameworks).
- **PHP Data Objects (PDO):** Prepared SQL statements, parameterized queries, and strict transaction management.
- **Normalized MySQL 8.0+ / MariaDB 10.5+:** 19 relational tables with foreign keys and cascade rules.
- **Document Generation:** Native vector PDF 1.4 A4 transcript rendering engine with Dompdf fallback.
- **QR Code Generation:** Standards-compliant vector SVG QR generator with zero third-party dependencies.

---

## 3. Directory Layout

```
academic-transcript-system/
├── api/                               # REST API Endpoints & Dispatchers
│   ├── auth/                          # Login, Logout, Current User
│   ├── students/                      # Student Directory & Biodata CRUD
│   ├── courses/                       # Department Courses Catalog CRUD
│   ├── sessions/                      # Academic Sessions Management
│   ├── semesters/                     # Semester Management
│   ├── results/                       # Score Entry, Batch Upload, Approval
│   ├── transcripts/                   # Generation, Preview, Revocation
│   ├── verification/                  # Public QR Verification & Logs
│   ├── settings/                      # Institution & Grading Scale Settings
│   ├── dashboard/                     # Metrics, Stats, Grade Distributions
│   └── index.php                      # Master REST Route Front-Controller
├── assets/
│   ├── css/style.css                  # Custom styling
│   ├── images/                        # SVG Emblems, Logos, Seals, Signatures
│   └── js/
│       ├── api.js                     # Central Fetch API Client Layer (USE_MOCK: false)
│       ├── app.js                     # Shared Layout, Sidebar, Dropdown, Modals
│       ├── auth.js                    # Staff Login & Session Protection
│       ├── dashboard.js               # Dashboard Metrics & Feed
│       ├── students.js                # Student Management Interface
│       ├── courses.js                 # Course Catalog Interface
│       ├── sessions.js                # Academic Sessions
│       ├── results.js                 # Result Entry & Grading
│       ├── calculations.js            # Frontend Math & Projection Utilities
│       ├── transcripts.js             # Transcript Generation & Batch Review
│       ├── verification.js            # QR Verification & Security Auditor
│       ├── settings.js                # Institutional Configuration
│       └── gpa-cgpa.js                # GPA/CGPA Analysis & Audit
├── config/
│   ├── app.php                        # System Paths, Timezone, App Config
│   ├── database.php                   # PDO Credentials & Socket Configuration
│   └── security.php                   # JWT Secrets, Password Policy, Rate Limits
├── controllers/                       # Business Logic Layer
│   ├── AuthController.php
│   ├── CourseController.php
│   ├── DashboardController.php
│   ├── ResultController.php
│   ├── SemesterController.php
│   ├── SessionController.php
│   ├── SettingController.php
│   ├── StudentController.php
│   ├── TranscriptController.php
│   └── VerificationController.php
├── database/
│   ├── schema.sql                     # Full Relational DDL (19 Normalized Tables)
│   └── seed.sql                       # Seed Data (Kano Poly CS Department)
├── helpers/
│   ├── Database.php                   # Resilient PDO Singleton
│   ├── Response.php                   # Standard JSON HTTP Envelope
│   ├── Security.php                   # Cryptographic Token, Hash, & Brute-Force Rate Limiting
│   └── Validator.php                  # Input Sanitization & Validation Engine
├── middleware/
│   ├── AuthMiddleware.php             # Bearer JWT / Session Verifier
│   ├── CSRFMiddleware.php             # Anti-CSRF Token Checker
│   └── PermissionMiddleware.php       # Role-Based Access Control (RBAC)
├── models/                            # Database Entity Operations
│   ├── AcademicSession.php
│   ├── AuditLog.php
│   ├── Course.php
│   ├── GradingScale.php
│   ├── Result.php
│   ├── Semester.php
│   ├── Setting.php
│   ├── Student.php
│   ├── Transcript.php
│   ├── User.php
│   └── Verification.php
├── pages/                             # Administrative Dashboard Pages
│   ├── academic-sessions.html
│   ├── courses.html
│   ├── dashboard.html
│   ├── gpa-cgpa.html
│   ├── qr-verification.html
│   ├── results.html
│   ├── semesters.html
│   ├── settings.html
│   ├── student-profile.html
│   ├── students.html
│   ├── transcript-preview.html
│   └── transcripts.html
├── services/                          # Specialized Service Layer
│   ├── AuditLogService.php            # Audit Trail Logging
│   ├── CGPAService.php                # Cumulative Grade Point Calculation
│   ├── GPAService.php                 # Semester Grade Point Calculation
│   ├── PDFService.php                 # Native A4 Official PDF Engine
│   ├── QRCodeService.php              # Vector SVG QR Code Engine
│   └── TranscriptService.php          # Immutable Snapshot & Reference Generator
├── storage/                           # Persistent Storage
│   ├── qrcodes/                       # Generated SVG QR Codes
│   ├── transcripts/                   # Generated A4 PDF Documents
│   └── uploads/                       # Student Avatars and Files
├── tests/
│   ├── BackendTest.php                # Comprehensive 43-Assertion Backend Test Suite
│   └── HttpEndpointTest.php           # 14-Assertion HTTP REST Dispatcher Test Suite
├── bootstrap.php                      # Autoloader & Exception Handler
├── composer.json                      # Dependency Manifest & PSR-4 Definition
├── index.html                         # System Splash / Portal Landing Page
├── index.php                          # Root Redirect to Dashboard / Login
├── login.html                         # Staff Authentication Page
├── verify.html                        # Public QR / Reference Verification Portal
└── README.md                          # Documentation
```

---

## 4. Database Schema

The database consists of 19 normalized relational tables:
1. `roles`: Super Admin, Department Admin, Result Officer, Transcript Officer.
2. `permissions`: Granular permission keys (`students.create`, `transcripts.generate`, etc.).
3. `role_permissions`: Many-to-many role to permission matrix.
4. `users`: Administrative staff credentials with bcrypt password hashing.
5. `departments`: Academic department records.
6. `academic_sessions`: Sessions (e.g. `2024/2025`, `2023/2024`).
7. `semesters`: First and Second semesters linked to sessions.
8. `grading_system`: NBTE 4.00 standard scale (A, AB, B, BC, C, CD, D, E, F).
9. `courses`: Course catalog with credit units and academic levels (ND I, ND II, HND I, HND II).
10. `students`: Student biodata, unique matriculation numbers, levels, and status.
11. `student_courses`: Student semester course registration.
12. `results`: Continuous Assessment (CA 0-40), Exam (0-60), Total, Grade Point, and Quality Points.
13. `transcripts`: Generated transcripts with official reference numbers and CGPA.
14. `transcript_details`: Immutable historical snapshot of all courses at issuance time.
15. `transcript_verifications`: Cryptographic token hashes (`SHA-256`), preview hashes, and validity status.
16. `verification_logs`: Audit log of every public verification attempt (IP, user agent, timestamp).
17. `audit_logs`: Detailed activity log of administrative operations.
18. `system_settings`: Key-value registry for institutional configuration.
19. `login_attempts`: Brute-force rate limiting registry.

---

## 5. Grading System & Mathematical Formulas

The system implements the **NBTE (National Board for Technical Education)** 4.00 Grading Scale:

| Score Range | Letter Grade | Grade Point (GP) | Classification / Remark |
| :--- | :---: | :---: | :--- |
| **75.00% – 100.00%** | **A** | **4.00** | Distinction |
| **70.00% – 74.99%** | **AB** | **3.50** | Very Good |
| **65.00% – 69.99%** | **B** | **3.25** | Good |
| **60.00% – 64.99%** | **BC** | **3.00** | Upper Credit |
| **55.00% – 59.99%** | **C** | **2.75** | Lower Credit |
| **50.00% – 54.99%** | **CD** | **2.50** | Fair |
| **45.00% – 49.99%** | **D** | **2.25** | Pass |
| **40.00% – 44.99%** | **E** | **2.00** | Bare Pass |
| **0.00% – 39.99%** | **F** | **0.00** | Fail |

### Formulas
- **Quality Point (QP):**
  $$\text{Quality Point} = \text{Credit Unit} \times \text{Grade Point}$$

- **Semester Grade Point Average (GPA):**
  $$\text{GPA} = \frac{\sum (\text{Credit Unit} \times \text{Grade Point})}{\sum \text{Credit Unit}}$$

- **Cumulative Grade Point Average (CGPA):**
  $$\text{CGPA} = \frac{\sum \text{Total Quality Points (All Semesters)}}{\sum \text{Total Credit Units (All Semesters)}}$$

- **Class of Diploma:**
  - **Distinction:** $\text{CGPA} \ge 3.50$
  - **Upper Credit:** $3.00 \le \text{CGPA} < 3.50$
  - **Lower Credit:** $2.50 \le \text{CGPA} < 3.00$
  - **Pass:** $2.00 \le \text{CGPA} < 2.50$
  - **Fail / Unsatisfactory:** $\text{CGPA} < 2.00$

All calculations are enforced strictly on the PHP backend; client-side scores are never trusted.

---

## 6. Cryptographic QR Verification Architecture

1. **Generation:**
   - At generation, an unpredictable 256-bit cryptographic random token is minted:
     $$\text{Token} = \text{bin2hex}(\text{random\_bytes}(32))$$
   - The token's SHA-256 hash is recorded in `transcript_verifications`.
   - The transcript reference format: `KSP/CST/TR/{YEAR}/{SERIAL}` (e.g., `KSP/CST/TR/2025/0001`).
   - A vector SVG QR code is compiled, pointing to `http://<domain>/verify.html?token=<token>`.
2. **Verification (`verify.html` / `GET /api/verification/verify.php`):**
   - The portal accepts the QR token OR the transcript reference number.
   - The SHA-256 hash of the input is queried against `transcript_verifications`.
   - Returns instantaneous verification status: `VERIFIED`, `REVOKED`, or `NOT_FOUND`.
   - Every verification attempt is timestamped and logged with visitor IP address and User-Agent.

---

## 7. Default Demo Accounts

All accounts are pre-seeded with password: `admin123`

| Username | Email | Role | Department |
| :--- | :--- | :--- | :--- |
| `admin` | `admin@kanopoly.edu.ng` | Super Admin | Administration |
| `dept_admin` | `cs.hod@kanopoly.edu.ng` | Department Admin | Computer Science |
| `result_officer` | `results.cs@kanopoly.edu.ng` | Result Officer | Computer Science |
| `transcript_officer` | `transcripts.cs@kanopoly.edu.ng` | Transcript Officer | Computer Science |

---

## 8. Installation & Setup

### Prerequisites
- PHP 8.1+ with extensions: `pdo`, `pdo_mysql`, `gd`, `openssl`, `mbstring`, `json`.
- MySQL 8.0+ or MariaDB 10.5+.
- Apache / Nginx or PHP built-in web server.

### Setup Instructions
1. **Clone or Copy Application:**
   ```bash
   cd /var/www/html/onlinecourse/academic-transcript-system
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   # Update database credentials in .env or config/database.php if necessary
   ```

3. **Import Database:**
   ```bash
   mysql -u root -p onlinecourse < database/schema.sql
   mysql -u root -p onlinecourse < database/seed.sql
   ```

4. **Launch Local Server:**
   ```bash
   php -S 0.0.0.0:8000
   ```

5. **Access Application:**
   - Dashboard: `http://localhost:8000/login.html`
   - Public Verification: `http://localhost:8000/verify.html`

---

## 9. Automated Test Suites

The application includes two automated test suites:

### Run Backend Unit Tests (43 Assertions)
```bash
php tests/BackendTest.php
```

### Run HTTP REST API Dispatcher Tests (14 Assertions)
```bash
php tests/HttpEndpointTest.php
```

Both test suites execute real mathematical calculations, PDO database queries, JWT validation, and PDF/QR generation.
