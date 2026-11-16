/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Student Management & Profile Handler
 */

document.addEventListener('DOMContentLoaded', () => {
  // If on students list page
  if (document.getElementById('students-table-tbody')) {
    initStudentsListPage();
  }

  // If on student profile page
  if (document.getElementById('student-profile-root')) {
    initStudentProfilePage();
  }
});

// ============================================================================
// Students Directory List Page
// ============================================================================
let currentStudentFilters = {
  search: '',
  level: 'All',
  session: 'All',
  page: 1,
  pageSize: 8
};

async function initStudentsListPage() {
  const searchInput = document.getElementById('student-search-input');
  const levelFilter = document.getElementById('student-level-filter');
  const sessionFilter = document.getElementById('student-session-filter');
  const addStudentForm = document.getElementById('add-student-form');
  const editStudentForm = document.getElementById('edit-student-form');

  // Search input with debounce
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        currentStudentFilters.search = e.target.value;
        currentStudentFilters.page = 1;
        loadStudentsTable();
      }, 250);
    });
  }

  // Filter change handlers
  if (levelFilter) {
    levelFilter.addEventListener('change', (e) => {
      currentStudentFilters.level = e.target.value;
      currentStudentFilters.page = 1;
      loadStudentsTable();
    });
  }

  if (sessionFilter) {
    // Populate sessions
    const sessions = await api.sessions.getAll();
    sessionFilter.innerHTML = `<option value="All">All Admission Sessions</option>` +
      sessions.map(s => `<option value="${s.name}">${s.name}</option>`).join('');

    sessionFilter.addEventListener('change', (e) => {
      currentStudentFilters.session = e.target.value;
      currentStudentFilters.page = 1;
      loadStudentsTable();
    });
  }

  // Form submission: Add Student
  if (addStudentForm) {
    addStudentForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(addStudentForm);
      const studentData = Object.fromEntries(formData.entries());

      try {
        await api.students.create(studentData);
        showToast(`Student ${studentData.firstName} ${studentData.lastName} registered successfully!`, 'success');
        closeModal('add-student-modal');
        addStudentForm.reset();
        loadStudentsTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  // Form submission: Edit Student
  if (editStudentForm) {
    editStudentForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const studentId = document.getElementById('edit-student-id').value;
      const formData = new FormData(editStudentForm);
      const studentData = Object.fromEntries(formData.entries());

      try {
        await api.students.update(studentId, studentData);
        showToast("Student records updated successfully!", "success");
        closeModal('edit-student-modal');
        loadStudentsTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  loadStudentsTable();
}

/**
 * Render paginated students table
 */
async function loadStudentsTable() {
  const tbody = document.getElementById('students-table-tbody');
  const countBadge = document.getElementById('students-total-count');
  const paginationControls = document.getElementById('students-pagination-controls');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="7" class="py-8 text-center text-slate-400">Loading student directory...</td></tr>`;

  try {
    const allMatching = await api.students.getAll(currentStudentFilters);

    if (countBadge) countBadge.textContent = `${allMatching.length} Student${allMatching.length === 1 ? '' : 's'}`;

    if (allMatching.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="py-12 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <p class="text-slate-600 font-medium">No students found matching your criteria</p>
            <p class="text-xs text-slate-400 mt-1">Try clearing your search query or level filters</p>
          </td>
        </tr>
      `;
      if (paginationControls) paginationControls.innerHTML = '';
      return;
    }

    // Pagination slice
    const totalPages = Math.ceil(allMatching.length / currentStudentFilters.pageSize);
    const startIdx = (currentStudentFilters.page - 1) * currentStudentFilters.pageSize;
    const pageItems = allMatching.slice(startIdx, startIdx + currentStudentFilters.pageSize);

    tbody.innerHTML = pageItems.map(std => `
      <tr class="hover:bg-slate-50 transition border-b border-slate-100">
        <td class="py-3.5 px-4 font-mono text-xs font-bold text-blue-900">${std.matricNumber}</td>
        <td class="py-3.5 px-4">
          <div class="flex items-center space-x-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center font-bold text-xs uppercase shrink-0">
              ${std.firstName[0]}${std.lastName[0]}
            </div>
            <div>
              <div class="font-semibold text-slate-900 text-sm">${std.firstName} ${std.middleName ? std.middleName + ' ' : ''}${std.lastName}</div>
              <div class="text-xs text-slate-400">${std.email}</div>
            </div>
          </div>
        </td>
        <td class="py-3.5 px-4 text-xs font-medium text-slate-700">${std.programme}</td>
        <td class="py-3.5 px-4 text-xs font-semibold text-slate-600">${std.currentLevel}</td>
        <td class="py-3.5 px-4 text-xs text-slate-500">${std.admissionSession}</td>
        <td class="py-3.5 px-4">${UIHelpers.getStatusBadge(std.status)}</td>
        <td class="py-3.5 px-4 text-right">
          <div class="flex items-center justify-end space-x-2">
            <a href="student-profile.html?id=${std.id}" title="View Student Academic Profile" class="p-1.5 text-slate-600 hover:text-blue-900 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>
            <button type="button" onclick="openEditStudentModal('${std.id}')" title="Edit Student Record" class="p-1.5 text-slate-600 hover:text-amber-700 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button type="button" onclick="deleteStudentRecord('${std.id}', '${std.firstName} ${std.lastName}')" title="Delete Student" class="p-1.5 text-slate-600 hover:text-red-700 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `).join('');

    // Render pagination controls
    if (paginationControls) {
      paginationControls.innerHTML = `
        <div class="text-xs text-slate-500">
          Showing <span class="font-semibold text-slate-800">${startIdx + 1}</span> to <span class="font-semibold text-slate-800">${Math.min(startIdx + currentStudentFilters.pageSize, allMatching.length)}</span> of <span class="font-semibold text-slate-800">${allMatching.length}</span> students
        </div>
        <div class="flex items-center space-x-1">
          <button type="button" class="px-3 py-1.5 rounded-lg border text-xs font-semibold ${currentStudentFilters.page === 1 ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-50'}" ${currentStudentFilters.page === 1 ? 'disabled' : ''} onclick="changeStudentPage(${currentStudentFilters.page - 1})">Previous</button>
          ${Array.from({ length: totalPages }, (_, i) => i + 1).map(p => `
            <button type="button" class="w-8 h-8 rounded-lg text-xs font-semibold ${p === currentStudentFilters.page ? 'bg-blue-900 text-white' : 'text-slate-700 hover:bg-slate-100'}" onclick="changeStudentPage(${p})">${p}</button>
          `).join('')}
          <button type="button" class="px-3 py-1.5 rounded-lg border text-xs font-semibold ${currentStudentFilters.page === totalPages ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-300 text-slate-700 hover:bg-slate-50'}" ${currentStudentFilters.page === totalPages ? 'disabled' : ''} onclick="changeStudentPage(${currentStudentFilters.page + 1})">Next</button>
        </div>
      `;
    }
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="7" class="py-6 text-center text-red-500">Error loading students: ${err.message}</td></tr>`;
  }
}

function changeStudentPage(newPage) {
  currentStudentFilters.page = newPage;
  loadStudentsTable();
}

/**
 * Open and populate Edit Student Modal
 */
async function openEditStudentModal(studentId) {
  try {
    const student = await api.students.getById(studentId);
    document.getElementById('edit-student-id').value = student.id;
    document.getElementById('edit-matricNumber').value = student.matricNumber;
    document.getElementById('edit-firstName').value = student.firstName;
    document.getElementById('edit-middleName').value = student.middleName || '';
    document.getElementById('edit-lastName').value = student.lastName;
    document.getElementById('edit-gender').value = student.gender || 'Male';
    document.getElementById('edit-dob').value = student.dob || '';
    document.getElementById('edit-email').value = student.email;
    document.getElementById('edit-phone').value = student.phone || '';
    document.getElementById('edit-department').value = student.department || 'Computer Science';
    document.getElementById('edit-programme').value = student.programme;
    document.getElementById('edit-admissionSession').value = student.admissionSession;
    document.getElementById('edit-currentLevel').value = student.currentLevel;
    document.getElementById('edit-status').value = student.status || 'Active';

    openModal('edit-student-modal');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

/**
 * Delete student record with confirmation prompt
 */
function deleteStudentRecord(id, name) {
  confirmAction({
    title: "Delete Student Record",
    message: `Are you sure you want to permanently delete records for ${name}? All associated course registrations, examination scores, and generated transcripts will be removed.`,
    confirmText: "Delete Permanently",
    confirmClass: "bg-red-600 hover:bg-red-700 text-white",
    onConfirm: async () => {
      try {
        await api.students.delete(id);
        showToast(`Student ${name} deleted successfully`, 'success');
        loadStudentsTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    }
  });
}

// ============================================================================
// Student Profile Page Detailed View
// ============================================================================
async function initStudentProfilePage() {
  const urlParams = new URLSearchParams(window.location.search);
  let studentId = urlParams.get('id');

  const students = await api.students.getAll();
  if (!studentId && students.length > 0) {
    studentId = students[0].id;
  }

  if (!studentId) {
    showToast("No student found in system", "error");
    return;
  }

  const student = students.find(s => s.id === studentId || s.matricNumber === studentId);
  if (!student) {
    document.getElementById('student-profile-root').innerHTML = `
      <div class="p-8 text-center bg-white rounded-2xl shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-800">Student Profile Not Found</h3>
        <p class="text-sm text-slate-500 mt-2">The requested student record does not exist or was deleted.</p>
        <a href="students.html" class="mt-4 inline-block px-4 py-2 bg-blue-900 text-white text-sm font-semibold rounded-lg">&larr; Return to Student Directory</a>
      </div>
    `;
    return;
  }

  // Populate student basic info card
  document.getElementById('profile-name').textContent = `${student.firstName} ${student.middleName ? student.middleName + ' ' : ''}${student.lastName}`;
  document.getElementById('profile-matric').textContent = student.matricNumber;
  document.getElementById('profile-programme').textContent = student.programme;
  document.getElementById('profile-level-badge').textContent = student.currentLevel;
  document.getElementById('profile-status-badge').innerHTML = UIHelpers.getStatusBadge(student.status);

  document.getElementById('profile-email').textContent = student.email;
  document.getElementById('profile-phone').textContent = student.phone || 'N/A';
  document.getElementById('profile-gender').textContent = student.gender;
  document.getElementById('profile-dob').textContent = student.dob || 'N/A';
  document.getElementById('profile-admissionSession').textContent = student.admissionSession;
  document.getElementById('profile-department').textContent = student.department;

  // Load results and compute GPA/CGPA
  const allResults = await api.results.getAll({ studentId: student.id });
  const progression = AcademicCalculations.getStudentAcademicProgression(allResults);

  // Top metric badges
  document.getElementById('profile-cgpa').textContent = progression.overall.cgpa;
  document.getElementById('profile-classification').textContent = progression.overall.classification;
  document.getElementById('profile-total-credits').textContent = progression.overall.cumulativeCreditUnits;
  document.getElementById('profile-total-qp').textContent = progression.overall.cumulativeQualityPoints.toFixed(2);

  // Link quick "Generate Transcript" button
  const genBtn = document.getElementById('profile-generate-transcript-btn');
  if (genBtn) {
    genBtn.onclick = () => {
      window.location.href = `transcripts.html?studentId=${student.id}`;
    };
  }

  // Render Semester Tabs & Tables
  renderStudentResultsTab(progression.semesters);
  renderStudentCoursesTab(allResults);
  renderStudentTranscriptsTab(student.id);
}

function renderStudentResultsTab(semesters) {
  const container = document.getElementById('profile-results-container');
  if (!container) return;

  if (semesters.length === 0) {
    container.innerHTML = `
      <div class="p-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
        <p class="text-sm text-slate-500">No examination scores entered for this student yet.</p>
        <a href="results.html" class="mt-2 inline-block text-xs font-semibold text-blue-900 hover:underline">+ Enter Results in Result Management</a>
      </div>
    `;
    return;
  }

  container.innerHTML = semesters.map(sem => `
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
      <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h4 class="font-bold text-slate-900 text-sm">${sem.sessionName} &bull; ${sem.semester}</h4>
          <span class="text-xs text-slate-500 font-medium">Academic Level: ${sem.level}</span>
        </div>
        <div class="flex items-center gap-3">
          <span class="px-2.5 py-1 bg-blue-100 text-blue-900 font-bold rounded-lg text-xs">
            Semester GPA: ${sem.semesterSummary.gpa}
          </span>
          <span class="px-2.5 py-1 bg-slate-100 text-slate-800 font-bold rounded-lg text-xs">
            Cumulative CGPA: ${sem.runningCumulative.cgpa}
          </span>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="bg-slate-50/50 text-[11px] font-bold uppercase text-slate-500 border-b border-slate-100">
              <th class="py-2.5 px-4">Course Code</th>
              <th class="py-2.5 px-4">Course Title</th>
              <th class="py-2.5 px-4 text-center">Unit</th>
              <th class="py-2.5 px-4 text-center">Score</th>
              <th class="py-2.5 px-4 text-center">Grade</th>
              <th class="py-2.5 px-4 text-center">Grade Point</th>
              <th class="py-2.5 px-4 text-right">Quality Point</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            ${sem.results.map(r => `
              <tr class="hover:bg-slate-50/60">
                <td class="py-2.5 px-4 font-mono font-semibold text-xs text-blue-900">${r.courseCode}</td>
                <td class="py-2.5 px-4 font-medium text-slate-800">${r.courseTitle || r.title || 'Course'}</td>
                <td class="py-2.5 px-4 text-center font-semibold text-slate-700">${r.creditUnit}</td>
                <td class="py-2.5 px-4 text-center font-bold text-slate-900">${r.score}</td>
                <td class="py-2.5 px-4 text-center">${UIHelpers.getGradeBadge(r.grade)}</td>
                <td class="py-2.5 px-4 text-center font-mono text-slate-600">${r.gradePoint.toFixed(2)}</td>
                <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-800">${r.qualityPoint.toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot class="bg-slate-50 font-semibold text-xs text-slate-700 border-t border-slate-200">
            <tr>
              <td colspan="2" class="py-2.5 px-4 text-right">Semester Total:</td>
              <td class="py-2.5 px-4 text-center font-bold text-blue-900">${sem.semesterSummary.totalCreditUnits} Units</td>
              <td colspan="3" class="py-2.5 px-4 text-right">Total Quality Points (TQP):</td>
              <td class="py-2.5 px-4 text-right font-bold text-blue-900">${sem.semesterSummary.totalQualityPoints.toFixed(2)}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  `).join('');
}

function renderStudentCoursesTab(results) {
  const container = document.getElementById('profile-courses-tbody');
  if (!container) return;

  if (results.length === 0) {
    container.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-slate-400">No registered courses found.</td></tr>`;
    return;
  }

  container.innerHTML = results.map(r => `
    <tr class="hover:bg-slate-50 border-b border-slate-100">
      <td class="py-2.5 px-4 font-mono text-xs font-bold text-blue-900">${r.courseCode}</td>
      <td class="py-2.5 px-4 text-sm font-medium text-slate-800">${r.courseTitle || r.title || 'Course'}</td>
      <td class="py-2.5 px-4 text-center text-xs font-semibold text-slate-700">${r.creditUnit}</td>
      <td class="py-2.5 px-4 text-xs text-slate-600">${r.sessionName} &bull; ${r.semester}</td>
      <td class="py-2.5 px-4 text-right">${UIHelpers.getStatusBadge(r.status || 'Approved')}</td>
    </tr>
  `).join('');
}

async function renderStudentTranscriptsTab(studentId) {
  const container = document.getElementById('profile-transcripts-tbody');
  if (!container) return;

  const transcripts = await api.transcripts.getAll();
  const studentTranscripts = transcripts.filter(t => t.studentId === studentId);

  if (studentTranscripts.length === 0) {
    container.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-400">No official transcripts issued for this student yet.</td></tr>`;
    return;
  }

  container.innerHTML = studentTranscripts.map(tr => `
    <tr class="hover:bg-slate-50 border-b border-slate-100">
      <td class="py-3 px-4 font-mono text-xs font-bold text-slate-900">${tr.referenceNumber}</td>
      <td class="py-3 px-4 text-xs text-slate-600">${tr.issueDate}</td>
      <td class="py-3 px-4 text-xs font-bold text-blue-900">${tr.cgpa.toFixed(2)}</td>
      <td class="py-3 px-4 text-xs font-semibold text-slate-700">${tr.classification}</td>
      <td class="py-3 px-4">${UIHelpers.getStatusBadge(tr.status)}</td>
      <td class="py-3 px-4 text-right">
        <a href="transcript-preview.html?id=${tr.id}" class="px-3 py-1 bg-blue-900 text-white rounded-lg text-xs font-semibold hover:bg-blue-800 transition">
          Official View &rarr;
        </a>
      </td>
    </tr>
  `).join('');
}

window.openEditStudentModal = openEditStudentModal;
window.deleteStudentRecord = deleteStudentRecord;
window.changeStudentPage = changeStudentPage;
