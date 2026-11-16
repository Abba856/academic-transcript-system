/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * API Abstraction Layer & Mock Data Engine (Prepared for PHP/MySQL backend)
 */

const API_CONFIG = {
  USE_MOCK: false, // Connected to native PHP 8.1+ & MariaDB/MySQL backend!
  BASE_URL: (function() {
    return window.location.pathname.includes('/pages/') ? '../api' : 'api';
  })(),
  STORAGE_KEY: 'kano_poly_transcript_data_v1',
  AUTH_KEY: 'kano_poly_auth_session'
};

// Initial Seed Data representing Kano State Polytechnic Computer Science Department
const INITIAL_DATABASE = {
  institution: {
    name: "KANO STATE POLYTECHNIC",
    subtitle: "School of Technology",
    department: "Department of Computer Science",
    address: "Matan Fada Road, PMB 3401, Kano State, Nigeria",
    motto: "Skill for Self Reliance",
    rector: "Prof. Kabir Bello Dungurawa",
    registrar: "Mukhtar Ibrahim Bello",
    hod: "Dr. Aminu Abdullahi",
    dean: "Engr. Bashir Garba",
    contactEmail: "info@kanopoly.edu.ng",
    contactPhone: "+234 803 123 4567",
    website: "www.kanopoly.edu.ng"
  },
  
  gradingScale: [
    { grade: "A",  minScore: 75, maxScore: 100, point: 4.00, description: "Distinction / Excellent" },
    { grade: "AB", minScore: 70, maxScore: 74,  point: 3.50, description: "Very Good" },
    { grade: "B",  minScore: 65, maxScore: 69,  point: 3.25, description: "Good" },
    { grade: "BC", minScore: 60, maxScore: 64,  point: 3.00, description: "Fairly Good" },
    { grade: "C",  minScore: 55, maxScore: 59,  point: 2.75, description: "Average" },
    { grade: "CD", minScore: 50, maxScore: 54,  point: 2.50, description: "Below Average" },
    { grade: "D",  minScore: 45, maxScore: 49,  point: 2.25, description: "Weak Pass" },
    { grade: "E",  minScore: 40, maxScore: 44,  point: 2.00, description: "Pass" },
    { grade: "F",  minScore: 0,  maxScore: 39,  point: 0.00, description: "Fail" }
  ],

  academicSessions: [
    { id: "sess_1", name: "2022/2023", startDate: "2022-10-01", endDate: "2023-07-31", status: "Closed", isActive: false },
    { id: "sess_2", name: "2023/2024", startDate: "2023-10-01", endDate: "2024-08-15", status: "Closed", isActive: false },
    { id: "sess_3", name: "2024/2025", startDate: "2024-10-01", endDate: "2025-08-30", status: "Active", isActive: true },
    { id: "sess_4", name: "2025/2026", startDate: "2025-10-01", endDate: "2026-08-30", status: "Upcoming", isActive: false }
  ],

  semesters: [
    { id: "sem_1", name: "First Semester", code: "SEM-1", sessionId: "sess_3", status: "Closed", isActive: false },
    { id: "sem_2", name: "Second Semester", code: "SEM-2", sessionId: "sess_3", status: "Active", isActive: true }
  ],

  courses: [
    // ND I - First Semester
    { id: "crs_1", code: "COM 111", title: "Introduction to Computing", unit: 3, level: "ND I", semester: "First Semester", description: "Fundamentals of hardware, software, information systems and computing." },
    { id: "crs_2", code: "COM 112", title: "Introduction to Digital Electronics", unit: 3, level: "ND I", semester: "First Semester", description: "Boolean logic, logic gates, combinational and sequential circuit design." },
    { id: "crs_3", code: "COM 113", title: "Computer Packages I", unit: 2, level: "ND I", semester: "First Semester", description: "Hands-on word processors, spreadsheet packages and data presentation." },
    { id: "crs_4", code: "MTH 111", title: "Logic & Linear Algebra", unit: 3, level: "ND I", semester: "First Semester", description: "Set theory, matrix algebra, systems of linear equations and proposition." },
    { id: "crs_5", code: "GNS 101", title: "Use of English I", unit: 2, level: "ND I", semester: "First Semester", description: "Grammar, comprehension, writing skills and phonetics." },
    
    // ND I - Second Semester
    { id: "crs_6", code: "COM 121", title: "Programming with C++", unit: 3, level: "ND I", semester: "Second Semester", description: "Structured programming, pointers, syntax, functions and memory." },
    { id: "crs_7", code: "COM 122", title: "Introduction to Internet & Web Tech", unit: 3, level: "ND I", semester: "Second Semester", description: "HTML5, CSS, client-side web markup and internet architecture." },
    { id: "crs_8", code: "COM 123", title: "Computer Architecture", unit: 2, level: "ND I", semester: "Second Semester", description: "Instruction sets, memory hierarchy, bus systems and microarchitecture." },
    { id: "crs_9", code: "COM 124", title: "Data Structures & Algorithms", unit: 3, level: "ND I", semester: "Second Semester", description: "Arrays, stacks, queues, linked lists, binary trees and sorting." },
    { id: "crs_10", code: "GNS 102", title: "Communication in English II", unit: 2, level: "ND I", semester: "Second Semester", description: "Technical reports, oral presentations and correspondence." },

    // ND II - First Semester
    { id: "crs_11", code: "COM 211", title: "Object-Oriented Programming (Java)", unit: 3, level: "ND II", semester: "First Semester", description: "Encapsulation, inheritance, polymorphism, exception handling." },
    { id: "crs_12", code: "COM 212", title: "Database Design & Management I", unit: 3, level: "ND II", semester: "First Semester", description: "Relational models, SQL, normalization, ER diagrams and SQLite." },
    { id: "crs_13", code: "COM 213", title: "Operating Systems Principles", unit: 2, level: "ND II", semester: "First Semester", description: "Processes, threads, CPU scheduling, synchronization, file systems." },
    { id: "crs_14", code: "COM 214", title: "Systems Analysis & Design", unit: 3, level: "ND II", semester: "First Semester", description: "SDLC methodologies, flowcharts, DFD, testing and deployment." },

    // ND II - Second Semester
    { id: "crs_15", code: "COM 221", title: "Web Application Development", unit: 3, level: "ND II", semester: "Second Semester", description: "Server-side scripting with PHP, sessions, REST and web services." },
    { id: "crs_16", code: "COM 222", title: "Computer Networks & Security", unit: 3, level: "ND II", semester: "Second Semester", description: "OSI model, TCP/IP, IP addressing, routing, firewall and basic crypto." },
    { id: "crs_17", code: "COM 229", title: "ND Final Year Research Project", unit: 4, level: "ND II", semester: "Second Semester", description: "Supervised technical software implementation and defense." },

    // HND I & II Courses
    { id: "crs_18", code: "COM 311", title: "Operating Systems II (Unix/Linux)", unit: 3, level: "HND I", semester: "First Semester", description: "Shell scripting, kernel modules, Linux administration and POSIX." },
    { id: "crs_19", code: "COM 312", title: "Advanced Database Systems", unit: 3, level: "HND I", semester: "First Semester", description: "Query optimization, transactions, ACID, indexing, distributed DBs." },
    { id: "crs_20", code: "COM 411", title: "Software Engineering Methodologies", unit: 3, level: "HND II", semester: "First Semester", description: "Agile, Scrum, CI/CD, software architecture, metrics and QA." },
    { id: "crs_21", code: "COM 429", title: "HND Capstone Project", unit: 6, level: "HND II", semester: "Second Semester", description: "Advanced full-stack capstone system design, defense and dissertation." }
  ],

  students: [
    {
      id: "std_1",
      matricNumber: "KPT/CST/ND/22/0101",
      firstName: "Ibrahim",
      middleName: "Sani",
      lastName: "Danjuma",
      gender: "Male",
      dob: "2003-05-14",
      email: "ibrahim.danjuma@student.kanopoly.edu.ng",
      phone: "+234 803 234 5678",
      department: "Computer Science",
      programme: "National Diploma in Computer Science",
      admissionSession: "2022/2023",
      currentLevel: "ND II",
      status: "Active",
      avatar: ""
    },
    {
      id: "std_2",
      matricNumber: "KPT/CST/ND/22/0102",
      firstName: "Fatima",
      middleName: "Zahra",
      lastName: "Abubakar",
      gender: "Female",
      dob: "2004-02-18",
      email: "fatima.abubakar@student.kanopoly.edu.ng",
      phone: "+234 814 345 6789",
      department: "Computer Science",
      programme: "National Diploma in Computer Science",
      admissionSession: "2022/2023",
      currentLevel: "ND II",
      status: "Graduated",
      avatar: ""
    },
    {
      id: "std_3",
      matricNumber: "KPT/CST/ND/23/0204",
      firstName: "Usman",
      middleName: "Garba",
      lastName: "Bello",
      gender: "Male",
      dob: "2003-11-09",
      email: "usman.bello@student.kanopoly.edu.ng",
      phone: "+234 802 987 6543",
      department: "Computer Science",
      programme: "National Diploma in Computer Science",
      admissionSession: "2023/2024",
      currentLevel: "ND I",
      status: "Active",
      avatar: ""
    },
    {
      id: "std_4",
      matricNumber: "KPT/CST/HND/23/0015",
      firstName: "Amina",
      middleName: "Mukhtar",
      lastName: "Kano",
      gender: "Female",
      dob: "2001-08-22",
      email: "amina.kano@student.kanopoly.edu.ng",
      phone: "+234 818 765 4321",
      department: "Computer Science",
      programme: "Higher National Diploma in Computer Science",
      admissionSession: "2023/2024",
      currentLevel: "HND II",
      status: "Active",
      avatar: ""
    },
    {
      id: "std_5",
      matricNumber: "KPT/CST/ND/23/0319",
      firstName: "Aliyu",
      middleName: "Mustapha",
      lastName: "Shehu",
      gender: "Male",
      dob: "2004-09-30",
      email: "aliyu.shehu@student.kanopoly.edu.ng",
      phone: "+234 703 112 2334",
      department: "Computer Science",
      programme: "National Diploma in Computer Science",
      admissionSession: "2023/2024",
      currentLevel: "ND I",
      status: "Active",
      avatar: ""
    }
  ],

  results: [
    // Results for Fatima Abubakar (std_2) - Completed ND with high distinction
    // ND I - First Semester
    { id: "res_1", studentId: "std_2", courseId: "crs_1", courseCode: "COM 111", courseTitle: "Introduction to Computing", creditUnit: 3, score: 82, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_2", studentId: "std_2", courseId: "crs_2", courseCode: "COM 112", courseTitle: "Introduction to Digital Electronics", creditUnit: 3, score: 76, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_3", studentId: "std_2", courseId: "crs_3", courseCode: "COM 113", courseTitle: "Computer Packages I", creditUnit: 2, score: 88, grade: "A", gradePoint: 4.00, qualityPoint: 8.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_4", studentId: "std_2", courseId: "crs_4", courseCode: "MTH 111", courseTitle: "Logic & Linear Algebra", creditUnit: 3, score: 71, grade: "AB", gradePoint: 3.50, qualityPoint: 10.50, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_5", studentId: "std_2", courseId: "crs_5", courseCode: "GNS 101", courseTitle: "Use of English I", creditUnit: 2, score: 78, grade: "A", gradePoint: 4.00, qualityPoint: 8.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    
    // ND I - Second Semester
    { id: "res_6", studentId: "std_2", courseId: "crs_6", courseCode: "COM 121", courseTitle: "Programming with C++", creditUnit: 3, score: 85, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Approved" },
    { id: "res_7", studentId: "std_2", courseId: "crs_7", courseCode: "COM 122", courseTitle: "Introduction to Internet & Web Tech", creditUnit: 3, score: 90, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Approved" },
    { id: "res_8", studentId: "std_2", courseId: "crs_8", courseCode: "COM 123", courseTitle: "Computer Architecture", creditUnit: 2, score: 68, grade: "B", gradePoint: 3.25, qualityPoint: 6.50, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Approved" },
    { id: "res_9", studentId: "std_2", courseId: "crs_9", courseCode: "COM 124", title: "Data Structures & Algorithms", creditUnit: 3, score: 77, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Approved" },
    { id: "res_10", studentId: "std_2", courseId: "crs_10", courseCode: "GNS 102", courseTitle: "Communication in English II", creditUnit: 2, score: 74, grade: "AB", gradePoint: 3.50, qualityPoint: 7.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Approved" },

    // ND II - First Semester
    { id: "res_11", studentId: "std_2", courseId: "crs_11", courseCode: "COM 211", courseTitle: "Object-Oriented Programming (Java)", creditUnit: 3, score: 86, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "First Semester", level: "ND II", status: "Approved" },
    { id: "res_12", studentId: "std_2", courseId: "crs_12", courseCode: "COM 212", courseTitle: "Database Design & Management I", creditUnit: 3, score: 80, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "First Semester", level: "ND II", status: "Approved" },
    { id: "res_13", studentId: "std_2", courseId: "crs_13", courseCode: "COM 213", courseTitle: "Operating Systems Principles", creditUnit: 2, score: 72, grade: "AB", gradePoint: 3.50, qualityPoint: 7.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "First Semester", level: "ND II", status: "Approved" },
    { id: "res_14", studentId: "std_2", courseId: "crs_14", courseCode: "COM 214", courseTitle: "Systems Analysis & Design", creditUnit: 3, score: 79, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "First Semester", level: "ND II", status: "Approved" },

    // ND II - Second Semester
    { id: "res_15", studentId: "std_2", courseId: "crs_15", courseCode: "COM 221", courseTitle: "Web Application Development", creditUnit: 3, score: 92, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "Second Semester", level: "ND II", status: "Approved" },
    { id: "res_16", studentId: "std_2", courseId: "crs_16", courseCode: "COM 222", courseTitle: "Computer Networks & Security", creditUnit: 3, score: 75, grade: "A", gradePoint: 4.00, qualityPoint: 12.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "Second Semester", level: "ND II", status: "Approved" },
    { id: "res_17", studentId: "std_2", courseId: "crs_17", courseCode: "COM 229", courseTitle: "ND Final Year Research Project", creditUnit: 4, score: 88, grade: "A", gradePoint: 4.00, qualityPoint: 16.00, sessionId: "sess_2", sessionName: "2023/2024", semester: "Second Semester", level: "ND II", status: "Approved" },

    // Results for Ibrahim Danjuma (std_1)
    { id: "res_18", studentId: "std_1", courseId: "crs_1", courseCode: "COM 111", courseTitle: "Introduction to Computing", creditUnit: 3, score: 68, grade: "B", gradePoint: 3.25, qualityPoint: 9.75, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_19", studentId: "std_1", courseId: "crs_2", courseCode: "COM 112", courseTitle: "Introduction to Digital Electronics", creditUnit: 3, score: 62, grade: "BC", gradePoint: 3.00, qualityPoint: 9.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_20", studentId: "std_1", courseId: "crs_3", courseCode: "COM 113", courseTitle: "Computer Packages I", creditUnit: 2, score: 72, grade: "AB", gradePoint: 3.50, qualityPoint: 7.00, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_21", studentId: "std_1", courseId: "crs_4", courseCode: "MTH 111", courseTitle: "Logic & Linear Algebra", creditUnit: 3, score: 58, grade: "C", gradePoint: 2.75, qualityPoint: 8.25, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_22", studentId: "std_1", courseId: "crs_5", courseCode: "GNS 101", courseTitle: "Use of English I", creditUnit: 2, score: 65, grade: "B", gradePoint: 3.25, qualityPoint: 6.50, sessionId: "sess_1", sessionName: "2022/2023", semester: "First Semester", level: "ND I", status: "Approved" },
    { id: "res_23", studentId: "std_1", courseId: "crs_6", courseCode: "COM 121", courseTitle: "Programming with C++", creditUnit: 3, score: 70, grade: "AB", gradePoint: 3.50, qualityPoint: 10.50, sessionId: "sess_1", sessionName: "2022/2023", semester: "Second Semester", level: "ND I", status: "Draft" }
  ],

  transcripts: [
    {
      id: "tr_1",
      referenceNumber: "KSP/CST/TR/2024/00842",
      studentId: "std_2",
      studentName: "Fatima Zahra Abubakar",
      matricNumber: "KPT/CST/ND/22/0102",
      programme: "National Diploma in Computer Science",
      department: "Department of Computer Science",
      admissionYear: "2022",
      graduationYear: "2024",
      issueDate: "2024-09-12",
      cgpa: 3.84,
      totalCreditUnits: 41,
      totalQualityPoints: 157.50,
      classification: "Distinction",
      status: "Valid",
      remarks: "Awarded National Diploma with Distinction.",
      generatedBy: "Mal. Mukhtar I. Bello (Academic Secretary)"
    },
    {
      id: "tr_2",
      referenceNumber: "KSP/CST/TR/2024/00915",
      studentId: "std_1",
      studentName: "Ibrahim Sani Danjuma",
      matricNumber: "KPT/CST/ND/22/0101",
      programme: "National Diploma in Computer Science",
      department: "Department of Computer Science",
      admissionYear: "2022",
      graduationYear: "In Progress",
      issueDate: "2024-10-05",
      cgpa: 3.12,
      totalCreditUnits: 13,
      totalQualityPoints: 40.50,
      classification: "Upper Credit",
      status: "Valid",
      remarks: "Provisional statement of academic results for First Year.",
      generatedBy: "Exam Officer (Computer Science)"
    }
  ],

  verificationLogs: [
    {
      id: "vlog_1",
      referenceNumber: "KSP/CST/TR/2024/00842",
      studentName: "Fatima Zahra Abubakar",
      verifiedAt: "2024-11-01 10:45:12",
      ipAddress: "197.210.45.12",
      userAgent: "Mozilla/5.0 (Windows NT 10.0; Win64; x64)",
      status: "VERIFIED"
    },
    {
      id: "vlog_2",
      referenceNumber: "KSP/CST/TR/2024/00842",
      studentName: "Fatima Zahra Abubakar",
      verifiedAt: "2024-11-10 14:22:01",
      ipAddress: "105.112.98.74",
      userAgent: "Mobile Safari (iPhone)",
      status: "VERIFIED"
    }
  ],

  userProfile: {
    username: "admin",
    name: "Dr. Aminu Abdullahi",
    title: "Head of Department (Computer Science)",
    email: "admin@kanopoly.edu.ng",
    role: "Departmental Administrator",
    department: "Computer Science",
    phone: "+234 803 555 0192",
    lastLogin: "2024-11-15 08:30:00"
  }
};

// Storage helper functions
class LocalStorageDB {
  static getDB() {
    const data = localStorage.getItem(API_CONFIG.STORAGE_KEY);
    if (!data) {
      this.saveDB(INITIAL_DATABASE);
      return JSON.parse(JSON.stringify(INITIAL_DATABASE));
    }
    try {
      return JSON.parse(data);
    } catch (e) {
      console.error("Failed to parse database, resetting", e);
      this.saveDB(INITIAL_DATABASE);
      return JSON.parse(JSON.stringify(INITIAL_DATABASE));
    }
  }

  static saveDB(data) {
    localStorage.setItem(API_CONFIG.STORAGE_KEY, JSON.stringify(data));
  }

  static reset() {
    localStorage.removeItem(API_CONFIG.STORAGE_KEY);
    return this.getDB();
  }
}

// Global API service exposing endpoints matching future PHP REST controllers
const api = {
  // Generic HTTP fetch wrapper for PHP backend
  async request(endpoint, options = {}) {
    const base = window.location.pathname.includes('/pages/') ? '../api' : 'api';
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint.slice(1) : endpoint;
    const url = `${base}/${cleanEndpoint}`;

    const defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    };

    const sessionStr = localStorage.getItem(API_CONFIG.AUTH_KEY);
    if (sessionStr) {
      try {
        const session = JSON.parse(sessionStr);
        if (session && session.token) {
          defaultHeaders['Authorization'] = `Bearer ${session.token}`;
        } else {
          defaultHeaders['Authorization'] = `Bearer ${sessionStr}`;
        }
      } catch (e) {
        defaultHeaders['Authorization'] = `Bearer ${sessionStr}`;
      }
    }

    const res = await fetch(url, {
      ...options,
      headers: { ...defaultHeaders, ...options.headers }
    });

    if (!res.ok) {
      const errorData = await res.json().catch(() => ({}));
      throw new Error(errorData.message || (errorData.errors ? Object.values(errorData.errors).flat().join(', ') : `HTTP Error ${res.status}`));
    }

    const json = await res.json();
    if (json && json.success !== undefined && json.data !== undefined) {
      if (typeof json.data === 'object' && json.data !== null && !Array.isArray(json.data)) {
        if (json.data.success === undefined) json.data.success = json.success;
        if (!json.data.message && json.message) json.data.message = json.message;
      }
      return json.data;
    }
    return json;
  },

  // ================= AUTHENTICATION =================
  auth: {
    async login(emailOrUsername, password, remember = false) {
      if (!API_CONFIG.USE_MOCK) {
        const res = await api.request('auth/login.php', {
          method: 'POST',
          body: JSON.stringify({ emailOrUsername, password, remember })
        });
        const user = res.user || (res.data && res.data.user) || res;
        const token = res.token || (res.data && res.data.token) || "session_token";
        const session = {
          token: token,
          user: user,
          expiresAt: Date.now() + (remember ? 30 * 86400000 : 86400000)
        };
        localStorage.setItem(API_CONFIG.AUTH_KEY, JSON.stringify(session));
        return { success: true, user: session.user };
      }

      await new Promise(r => setTimeout(r, 200));
      const db = LocalStorageDB.getDB();
      // Demo authentication rules: Accept 'admin' / 'admin123' or 'admin@kanopoly.edu.ng' / 'admin123'
      const isValid = (emailOrUsername.toLowerCase() === 'admin' || emailOrUsername.toLowerCase() === 'admin@kanopoly.edu.ng') && password === 'admin123';

      if (!isValid) {
        throw new Error("Invalid username/email or password. For demo, use admin@kanopoly.edu.ng and admin123");
      }

      const session = {
        token: "demo_jwt_token_" + Date.now(),
        user: db.userProfile,
        expiresAt: Date.now() + (remember ? 30 * 86400000 : 86400000)
      };
      localStorage.setItem(API_CONFIG.AUTH_KEY, JSON.stringify(session));
      return { success: true, user: session.user };
    },

    getCurrentUser() {
      const sessionStr = localStorage.getItem(API_CONFIG.AUTH_KEY);
      if (!sessionStr) return null;
      try {
        const session = JSON.parse(sessionStr);
        if (session.expiresAt && Date.now() > session.expiresAt) {
          localStorage.removeItem(API_CONFIG.AUTH_KEY);
          return null;
        }
        return session.user;
      } catch (e) {
        return null;
      }
    },

    logout() {
      localStorage.removeItem(API_CONFIG.AUTH_KEY);
      return Promise.resolve({ success: true });
    }
  },

  // ================= STUDENTS =================
  students: {
    async getAll(filters = {}) {
      if (!API_CONFIG.USE_MOCK) {
        const query = new URLSearchParams(filters).toString();
        return api.request(`students/index.php?${query}`);
      }
      const db = LocalStorageDB.getDB();
      let list = [...db.students];

      if (filters.search) {
        const q = filters.search.toLowerCase().trim();
        list = list.filter(s =>
          `${s.firstName} ${s.middleName || ''} ${s.lastName}`.toLowerCase().includes(q) ||
          s.matricNumber.toLowerCase().includes(q) ||
          s.email.toLowerCase().includes(q)
        );
      }
      if (filters.level && filters.level !== 'All') {
        list = list.filter(s => s.currentLevel === filters.level);
      }
      if (filters.session && filters.session !== 'All') {
        list = list.filter(s => s.admissionSession === filters.session);
      }
      if (filters.programme && filters.programme !== 'All') {
        list = list.filter(s => s.programme === filters.programme);
      }
      return list;
    },

    async getById(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`students/show.php?id=${id}`);
      const db = LocalStorageDB.getDB();
      const student = db.students.find(s => s.id === id || s.matricNumber === id);
      if (!student) throw new Error("Student not found");
      return student;
    },

    async create(studentData) {
      if (!API_CONFIG.USE_MOCK) {
        return api.request('students/create.php', { method: 'POST', body: JSON.stringify(studentData) });
      }
      const db = LocalStorageDB.getDB();
      const exists = db.students.some(s => s.matricNumber.toLowerCase() === studentData.matricNumber.toLowerCase());
      if (exists) throw new Error(`Student with matric number ${studentData.matricNumber} already exists.`);

      const newStudent = {
        ...studentData,
        id: "std_" + Date.now(),
        status: studentData.status || "Active",
        createdAt: new Date().toISOString()
      };
      db.students.unshift(newStudent);
      LocalStorageDB.saveDB(db);
      return newStudent;
    },

    async update(id, studentData) {
      if (!API_CONFIG.USE_MOCK) {
        return api.request(`students/update.php?id=${id}`, { method: 'PUT', body: JSON.stringify(studentData) });
      }
      const db = LocalStorageDB.getDB();
      const idx = db.students.findIndex(s => s.id === id);
      if (idx === -1) throw new Error("Student not found");

      db.students[idx] = { ...db.students[idx], ...studentData, updatedAt: new Date().toISOString() };
      LocalStorageDB.saveDB(db);
      return db.students[idx];
    },

    async delete(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`students/delete.php?id=${id}`, { method: 'DELETE' });
      const db = LocalStorageDB.getDB();
      db.students = db.students.filter(s => s.id !== id);
      // Remove orphaned results and transcripts
      db.results = db.results.filter(r => r.studentId !== id);
      LocalStorageDB.saveDB(db);
      return { success: true };
    }
  },

  // ================= COURSES =================
  courses: {
    async getAll(filters = {}) {
      if (!API_CONFIG.USE_MOCK) return api.request('courses/index.php');
      const db = LocalStorageDB.getDB();
      let list = [...db.courses];

      if (filters.search) {
        const q = filters.search.toLowerCase().trim();
        list = list.filter(c => c.code.toLowerCase().includes(q) || c.title.toLowerCase().includes(q));
      }
      if (filters.level && filters.level !== 'All') {
        list = list.filter(c => c.level === filters.level);
      }
      if (filters.semester && filters.semester !== 'All') {
        list = list.filter(c => c.semester === filters.semester);
      }
      return list;
    },

    async getById(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`courses/show.php?id=${id}`);
      const db = LocalStorageDB.getDB();
      const course = db.courses.find(c => c.id === id || c.code === id);
      if (!course) throw new Error("Course not found");
      return course;
    },

    async create(courseData) {
      if (!API_CONFIG.USE_MOCK) return api.request('courses/create.php', { method: 'POST', body: JSON.stringify(courseData) });
      const db = LocalStorageDB.getDB();
      const exists = db.courses.some(c => c.code.toLowerCase() === courseData.code.toLowerCase());
      if (exists) throw new Error(`Course with code ${courseData.code} already exists.`);

      const newCourse = {
        ...courseData,
        id: "crs_" + Date.now(),
        unit: parseInt(courseData.unit, 10) || 2
      };
      db.courses.push(newCourse);
      LocalStorageDB.saveDB(db);
      return newCourse;
    },

    async update(id, courseData) {
      if (!API_CONFIG.USE_MOCK) return api.request(`courses/update.php?id=${id}`, { method: 'PUT', body: JSON.stringify(courseData) });
      const db = LocalStorageDB.getDB();
      const idx = db.courses.findIndex(c => c.id === id);
      if (idx === -1) throw new Error("Course not found");

      db.courses[idx] = { ...db.courses[idx], ...courseData, unit: parseInt(courseData.unit, 10) || db.courses[idx].unit };
      LocalStorageDB.saveDB(db);
      return db.courses[idx];
    },

    async delete(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`courses/delete.php?id=${id}`, { method: 'DELETE' });
      const db = LocalStorageDB.getDB();
      db.courses = db.courses.filter(c => c.id !== id);
      LocalStorageDB.saveDB(db);
      return { success: true };
    }
  },

  // ================= SESSIONS =================
  sessions: {
    async getAll() {
      if (!API_CONFIG.USE_MOCK) return api.request('sessions/index.php');
      const db = LocalStorageDB.getDB();
      return db.academicSessions;
    },

    async create(data) {
      if (!API_CONFIG.USE_MOCK) return api.request('sessions/create.php', { method: 'POST', body: JSON.stringify(data) });
      const db = LocalStorageDB.getDB();
      const newSession = {
        ...data,
        id: "sess_" + Date.now(),
        isActive: false
      };
      db.academicSessions.unshift(newSession);
      LocalStorageDB.saveDB(db);
      return newSession;
    },

    async update(id, data) {
      if (!API_CONFIG.USE_MOCK) return api.request(`sessions/update.php?id=${id}`, { method: 'PUT', body: JSON.stringify(data) });
      const db = LocalStorageDB.getDB();
      const idx = db.academicSessions.findIndex(s => s.id === id);
      if (idx === -1) throw new Error("Session not found");
      db.academicSessions[idx] = { ...db.academicSessions[idx], ...data };
      LocalStorageDB.saveDB(db);
      return db.academicSessions[idx];
    },

    async setActive(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`sessions/set_active.php?id=${id}`, { method: 'POST' });
      const db = LocalStorageDB.getDB();
      db.academicSessions.forEach(s => {
        s.isActive = (s.id === id);
        if (s.id === id) s.status = "Active";
      });
      LocalStorageDB.saveDB(db);
      return { success: true };
    }
  },

  // ================= SEMESTERS =================
  semesters: {
    async getAll() {
      if (!API_CONFIG.USE_MOCK) return api.request('semesters/index.php');
      const db = LocalStorageDB.getDB();
      return db.semesters;
    },

    async create(data) {
      if (!API_CONFIG.USE_MOCK) return api.request('semesters/create.php', { method: 'POST', body: JSON.stringify(data) });
      const db = LocalStorageDB.getDB();
      const newSem = { ...data, id: "sem_" + Date.now(), isActive: false };
      db.semesters.push(newSem);
      LocalStorageDB.saveDB(db);
      return newSem;
    },

    async update(id, data) {
      if (!API_CONFIG.USE_MOCK) return api.request(`semesters/update.php?id=${id}`, { method: 'PUT', body: JSON.stringify(data) });
      const db = LocalStorageDB.getDB();
      const idx = db.semesters.findIndex(s => s.id === id);
      if (idx === -1) throw new Error("Semester not found");
      db.semesters[idx] = { ...db.semesters[idx], ...data };
      LocalStorageDB.saveDB(db);
      return db.semesters[idx];
    },

    async setActive(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`semesters/set_active.php?id=${id}`, { method: 'POST' });
      const db = LocalStorageDB.getDB();
      db.semesters.forEach(s => {
        s.isActive = (s.id === id);
        if (s.id === id) s.status = "Active";
      });
      LocalStorageDB.saveDB(db);
      return { success: true };
    }
  },

  // ================= RESULTS =================
  results: {
    async getAll(filters = {}) {
      if (!API_CONFIG.USE_MOCK) return api.request('results/index.php');
      const db = LocalStorageDB.getDB();
      let list = [...db.results];

      if (filters.studentId) list = list.filter(r => r.studentId === filters.studentId);
      if (filters.courseCode) list = list.filter(r => r.courseCode === filters.courseCode);
      if (filters.level && filters.level !== 'All') list = list.filter(r => r.level === filters.level);
      if (filters.semester && filters.semester !== 'All') list = list.filter(r => r.semester === filters.semester);
      if (filters.sessionName && filters.sessionName !== 'All') list = list.filter(r => r.sessionName === filters.sessionName);
      if (filters.status && filters.status !== 'All') list = list.filter(r => r.status === filters.status);

      return list;
    },

    async save(resultData) {
      if (!API_CONFIG.USE_MOCK) return api.request('results/save.php', { method: 'POST', body: JSON.stringify(resultData) });
      const db = LocalStorageDB.getDB();
      
      // Calculate grade & quality points using active grading scale
      const { grade, gradePoint } = api.grading.calculateGrade(resultData.score);
      const creditUnit = parseInt(resultData.creditUnit, 10) || 3;
      const qualityPoint = parseFloat((creditUnit * gradePoint).toFixed(2));

      // Check if student already has a result for this course in the same session/semester
      const existingIdx = db.results.findIndex(r =>
        r.studentId === resultData.studentId &&
        r.courseCode === resultData.courseCode &&
        r.semester === resultData.semester
      );

      const record = {
        ...resultData,
        creditUnit,
        grade,
        gradePoint,
        qualityPoint,
        status: resultData.status || "Draft",
        updatedAt: new Date().toISOString()
      };

      if (existingIdx >= 0) {
        db.results[existingIdx] = { ...db.results[existingIdx], ...record };
      } else {
        record.id = "res_" + Date.now();
        db.results.push(record);
      }

      LocalStorageDB.saveDB(db);
      return record;
    },

    async batchSave(resultsArray) {
      if (!API_CONFIG.USE_MOCK) return api.request('results/batch_save.php', { method: 'POST', body: JSON.stringify(resultsArray) });
      for (const res of resultsArray) {
        await api.results.save(res);
      }
      return { success: true, count: resultsArray.length };
    },

    async approve(ids) {
      if (!API_CONFIG.USE_MOCK) return api.request('results/approve.php', { method: 'POST', body: JSON.stringify({ ids }) });
      const db = LocalStorageDB.getDB();
      db.results.forEach(r => {
        if (ids.includes(r.id)) r.status = "Approved";
      });
      LocalStorageDB.saveDB(db);
      return { success: true };
    },

    async delete(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`results/delete.php?id=${id}`, { method: 'DELETE' });
      const db = LocalStorageDB.getDB();
      db.results = db.results.filter(r => r.id !== id);
      LocalStorageDB.saveDB(db);
      return { success: true };
    }
  },

  // ================= TRANSCRIPTS =================
  transcripts: {
    async getAll(filters = {}) {
      if (!API_CONFIG.USE_MOCK) return api.request('transcripts/index.php');
      const db = LocalStorageDB.getDB();
      let list = [...db.transcripts];
      if (filters.search) {
        const q = filters.search.toLowerCase();
        list = list.filter(t => t.referenceNumber.toLowerCase().includes(q) || t.studentName.toLowerCase().includes(q) || t.matricNumber.toLowerCase().includes(q));
      }
      if (filters.status && filters.status !== 'All') {
        list = list.filter(t => t.status === filters.status);
      }
      return list;
    },

    async getById(id) {
      if (!API_CONFIG.USE_MOCK) return api.request(`transcripts/show.php?id=${id}`);
      const db = LocalStorageDB.getDB();
      const tr = db.transcripts.find(t => t.id === id || t.referenceNumber === id);
      if (!tr) throw new Error("Transcript record not found");
      return tr;
    },

    async generate(studentId, options = {}) {
      if (!API_CONFIG.USE_MOCK) return api.request('transcripts/generate.php', { method: 'POST', body: JSON.stringify({ studentId, options }) });
      const db = LocalStorageDB.getDB();
      const student = db.students.find(s => s.id === studentId);
      if (!student) throw new Error("Student not found");

      // Calculate GPA & CGPA for the student
      const studentResults = db.results.filter(r => r.studentId === student.id);
      if (studentResults.length === 0) {
        throw new Error("Cannot generate transcript: No examination results recorded for this student.");
      }

      let totalUnits = 0;
      let totalQualityPoints = 0;
      studentResults.forEach(r => {
        totalUnits += r.creditUnit;
        totalQualityPoints += r.qualityPoint;
      });

      const cgpa = totalUnits > 0 ? parseFloat((totalQualityPoints / totalUnits).toFixed(2)) : 0;
      const classification = api.grading.getClassification(cgpa);

      // Generate unique reference number: KSP/CST/TR/{YEAR}/{SERIAL}
      const year = new Date().getFullYear();
      const randomNum = Math.floor(1000 + Math.random() * 9000);
      const referenceNumber = `KSP/CST/TR/${year}/${randomNum}`;

      const newTranscript = {
        id: "tr_" + Date.now(),
        referenceNumber,
        studentId: student.id,
        studentName: `${student.firstName} ${student.middleName ? student.middleName + ' ' : ''}${student.lastName}`,
        matricNumber: student.matricNumber,
        programme: student.programme,
        department: "Department of Computer Science",
        admissionYear: student.admissionSession ? student.admissionSession.split('/')[0] : "2022",
        graduationYear: student.status === "Graduated" ? year.toString() : "In Progress",
        issueDate: new Date().toISOString().split('T')[0],
        cgpa,
        totalCreditUnits: totalUnits,
        totalQualityPoints: parseFloat(totalQualityPoints.toFixed(2)),
        classification,
        status: "Valid",
        remarks: options.remarks || `Official Academic Transcript issued upon completion of coursework. Performance: ${classification}.`,
        generatedBy: db.userProfile.name + " (" + db.userProfile.title + ")"
      };

      db.transcripts.unshift(newTranscript);
      LocalStorageDB.saveDB(db);
      return newTranscript;
    },

    async revoke(id, reason = "Revoked by administration") {
      if (!API_CONFIG.USE_MOCK) return api.request(`transcripts/revoke.php?id=${id}`, { method: 'POST', body: JSON.stringify({ reason }) });
      const db = LocalStorageDB.getDB();
      const tr = db.transcripts.find(t => t.id === id || t.referenceNumber === id);
      if (!tr) throw new Error("Transcript not found");
      tr.status = "Revoked";
      tr.revocationReason = reason;
      tr.revokedAt = new Date().toISOString();
      LocalStorageDB.saveDB(db);
      return tr;
    }
  },

  // ================= QR VERIFICATION =================
  verification: {
    async verify(referenceNumber) {
      if (!API_CONFIG.USE_MOCK) return api.request(`verification/verify.php?ref=${encodeURIComponent(referenceNumber)}`);
      const db = LocalStorageDB.getDB();
      const cleanRef = (referenceNumber || '').trim().toUpperCase();
      const transcript = db.transcripts.find(t => t.referenceNumber.toUpperCase() === cleanRef);

      const log = {
        id: "vlog_" + Date.now(),
        referenceNumber: cleanRef,
        studentName: transcript ? transcript.studentName : "UNKNOWN",
        verifiedAt: new Date().toISOString().replace('T', ' ').substring(0, 19),
        ipAddress: "127.0.0.1",
        userAgent: navigator.userAgent.substring(0, 40),
        status: !transcript ? "NOT_FOUND" : (transcript.status === "Revoked" ? "REVOKED" : "VERIFIED")
      };

      db.verificationLogs.unshift(log);
      LocalStorageDB.saveDB(db);

      if (!transcript) {
        return {
          verified: false,
          status: "NOT_FOUND",
          message: "No authentic transcript found matching this reference number. Please check the reference or contact the Department."
        };
      }

      if (transcript.status === "Revoked") {
        return {
          verified: false,
          status: "REVOKED",
          message: "This transcript was revoked by Kano State Polytechnic administration and is NO LONGER VALID.",
          revocationReason: transcript.revocationReason || "Administrative invalidation",
          transcript
        };
      }

      return {
        verified: true,
        status: "VERIFIED",
        message: "Transcript record confirmed genuine and authentic.",
        transcript: {
          referenceNumber: transcript.referenceNumber,
          studentName: transcript.studentName,
          matricNumber: transcript.matricNumber,
          department: transcript.department,
          programme: transcript.programme,
          cgpa: transcript.cgpa,
          classification: transcript.classification,
          issueDate: transcript.issueDate,
          generatedBy: transcript.generatedBy,
          institution: db.institution.name
        }
      };
    },

    async getLogs() {
      if (!API_CONFIG.USE_MOCK) return api.request('verification/logs.php');
      const db = LocalStorageDB.getDB();
      return db.verificationLogs;
    }
  },

  // ================= GRADING SYSTEM & UTILITIES =================
  grading: {
    getScale() {
      const db = LocalStorageDB.getDB();
      return db.gradingScale;
    },

    updateScale(newScale) {
      const db = LocalStorageDB.getDB();
      db.gradingScale = newScale;
      LocalStorageDB.saveDB(db);
      return db.gradingScale;
    },

    calculateGrade(score) {
      const num = parseFloat(score);
      if (isNaN(num)) return { grade: "F", gradePoint: 0.00, description: "Invalid" };
      const scale = api.grading.getScale();
      for (const item of scale) {
        if (num >= item.minScore && num <= item.maxScore) {
          return { grade: item.grade, gradePoint: item.point, description: item.description };
        }
      }
      return { grade: "F", gradePoint: 0.00, description: "Fail" };
    },

    getClassification(cgpa) {
      const val = parseFloat(cgpa);
      if (val >= 3.50) return "Distinction";
      if (val >= 3.00) return "Upper Credit";
      if (val >= 2.50) return "Lower Credit";
      if (val >= 2.00) return "Pass";
      return "Fail / Unsatisfactory";
    }
  },

  // ================= SETTINGS =================
  settings: {
    async get() {
      const db = LocalStorageDB.getDB();
      return {
        institution: db.institution,
        gradingScale: db.gradingScale,
        userProfile: db.userProfile
      };
    },

    async update(section, data) {
      const db = LocalStorageDB.getDB();
      if (db[section]) {
        db[section] = { ...db[section], ...data };
        LocalStorageDB.saveDB(db);
      }
      return db[section];
    },

    resetDefaults() {
      return LocalStorageDB.reset();
    }
  }
};

window.api = api;
