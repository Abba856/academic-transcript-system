/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Result Management & Live Grade Engine
 */

document.addEventListener('DOMContentLoaded', () => {
  initResultsPage();
});

let currentResultFilters = {
  studentId: '',
  sessionName: 'All',
  semester: 'All',
  level: 'All',
  status: 'All'
};

async function initResultsPage() {
  const studentFilter = document.getElementById('result-student-filter');
  const sessionFilter = document.getElementById('result-session-filter');
  const semesterFilter = document.getElementById('result-semester-filter');
  const levelFilter = document.getElementById('result-level-filter');
  const statusFilter = document.getElementById('result-status-filter');
  const addResultForm = document.getElementById('add-result-form');
  const scoreInput = document.getElementById('entry-score');

  // Populate Student Dropdown in Filters & Entry Form
  const students = await api.students.getAll();
  const studentOptions = `<option value="">All Students</option>` +
    students.map(s => `<option value="${s.id}">${s.matricNumber} - ${s.firstName} ${s.lastName}</option>`).join('');

  if (studentFilter) {
    studentFilter.innerHTML = studentOptions;
    studentFilter.addEventListener('change', (e) => {
      currentResultFilters.studentId = e.target.value;
      loadResultsTable();
    });
  }

  const formStudentSelect = document.getElementById('entry-student');
  if (formStudentSelect) {
    formStudentSelect.innerHTML = `<option value="" disabled selected>Select Registered Student...</option>` +
      students.map(s => `<option value="${s.id}" data-matric="${s.matricNumber}" data-name="${s.firstName} ${s.lastName}" data-level="${s.currentLevel}">${s.matricNumber} - ${s.firstName} ${s.lastName} (${s.currentLevel})</option>`).join('');

    formStudentSelect.addEventListener('change', (e) => {
      const selected = formStudentSelect.selectedOptions[0];
      if (selected && document.getElementById('entry-level')) {
        document.getElementById('entry-level').value = selected.dataset.level || 'ND I';
      }
    });
  }

  // Populate Courses Dropdown in Entry Form
  const courses = await api.courses.getAll();
  const formCourseSelect = document.getElementById('entry-course');
  if (formCourseSelect) {
    formCourseSelect.innerHTML = `<option value="" disabled selected>Select Departmental Course...</option>` +
      courses.map(c => `<option value="${c.id}" data-code="${c.code}" data-title="${c.title}" data-unit="${c.unit}" data-level="${c.level}" data-sem="${c.semester}">${c.code} - ${c.title} (${c.unit} Units)</option>`).join('');

    formCourseSelect.addEventListener('change', () => {
      const selected = formCourseSelect.selectedOptions[0];
      if (selected) {
        if (document.getElementById('entry-credit-unit')) {
          document.getElementById('entry-credit-unit').value = selected.dataset.unit;
        }
        if (document.getElementById('entry-semester')) {
          document.getElementById('entry-semester').value = selected.dataset.sem;
        }
      }
      calculatePreviewGrade();
    });
  }

  // Populate Sessions in Filters & Entry Form
  const sessions = await api.sessions.getAll();
  if (sessionFilter) {
    sessionFilter.innerHTML = `<option value="All">All Sessions</option>` +
      sessions.map(s => `<option value="${s.name}">${s.name}</option>`).join('');
    sessionFilter.addEventListener('change', (e) => {
      currentResultFilters.sessionName = e.target.value;
      loadResultsTable();
    });
  }

  const formSessionSelect = document.getElementById('entry-session');
  if (formSessionSelect) {
    formSessionSelect.innerHTML = sessions.map(s => `<option value="${s.name}" ${s.isActive ? 'selected' : ''}>${s.name}</option>`).join('');
  }

  if (semesterFilter) {
    semesterFilter.addEventListener('change', (e) => {
      currentResultFilters.semester = e.target.value;
      loadResultsTable();
    });
  }

  if (levelFilter) {
    levelFilter.addEventListener('change', (e) => {
      currentResultFilters.level = e.target.value;
      loadResultsTable();
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      currentResultFilters.status = e.target.value;
      loadResultsTable();
    });
  }

  // Real-time calculation on score input
  if (scoreInput) {
    scoreInput.addEventListener('input', calculatePreviewGrade);
  }

  // Handle Result Form Submission
  if (addResultForm) {
    addResultForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const studentSelect = document.getElementById('entry-student');
      const courseSelect = document.getElementById('entry-course');
      const score = parseFloat(document.getElementById('entry-score').value);
      const session = document.getElementById('entry-session').value;
      const semester = document.getElementById('entry-semester').value;
      const level = document.getElementById('entry-level').value;
      const status = document.getElementById('entry-status').value;

      if (!studentSelect.value || !courseSelect.value) {
        showToast("Please select student and course", "warning");
        return;
      }
      if (isNaN(score) || score < 0 || score > 100) {
        showToast("Score must be between 0 and 100", "error");
        return;
      }

      const optStd = studentSelect.selectedOptions[0];
      const optCrs = courseSelect.selectedOptions[0];

      const record = {
        studentId: studentSelect.value,
        courseId: courseSelect.value,
        courseCode: optCrs.dataset.code,
        courseTitle: optCrs.dataset.title,
        creditUnit: parseInt(optCrs.dataset.unit, 10) || 3,
        score,
        sessionName: session,
        semester,
        level,
        status
      };

      try {
        await api.results.save(record);
        showToast(`Result recorded for ${optStd.dataset.matric}!`, "success");
        closeModal('add-result-modal');
        addResultForm.reset();
        loadResultsTable();
      } catch (err) {
        showToast(err.message, "error");
      }
    });
  }

  loadResultsTable();
}

/**
 * Live preview calculation when typing score
 */
function calculatePreviewGrade() {
  const scoreInput = document.getElementById('entry-score');
  const gradeBadge = document.getElementById('preview-grade-badge');
  const gpDisplay = document.getElementById('preview-gp');
  const qpDisplay = document.getElementById('preview-qp');
  const unitInput = document.getElementById('entry-credit-unit');

  if (!scoreInput || !gradeBadge) return;

  const score = parseFloat(scoreInput.value);
  const unit = parseFloat(unitInput ? unitInput.value : 3) || 3;

  if (isNaN(score) || score < 0 || score > 100) {
    gradeBadge.innerHTML = `<span class="text-slate-400 font-mono text-xs">--</span>`;
    if (gpDisplay) gpDisplay.textContent = '0.00';
    if (qpDisplay) qpDisplay.textContent = '0.00';
    return;
  }

  const { grade, gradePoint, description } = api.grading.calculateGrade(score);
  const qp = (unit * gradePoint).toFixed(2);

  gradeBadge.innerHTML = UIHelpers.getGradeBadge(grade) + `<span class="text-[11px] text-slate-500 ml-1">(${description})</span>`;
  if (gpDisplay) gpDisplay.textContent = gradePoint.toFixed(2);
  if (qpDisplay) qpDisplay.textContent = qp;
}

/**
 * Render Results Table
 */
async function loadResultsTable() {
  const tbody = document.getElementById('results-table-tbody');
  const countBadge = document.getElementById('results-count-badge');
  const batchApproveBtn = document.getElementById('batch-approve-btn');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="10" class="py-6 text-center text-slate-400">Loading examination records...</td></tr>`;

  try {
    const list = await api.results.getAll(currentResultFilters);
    const students = await api.students.getAll();
    const studentMap = Object.fromEntries(students.map(s => [s.id, s]));

    if (countBadge) countBadge.textContent = `${list.length} Records`;

    const draftIds = list.filter(r => r.status === 'Draft').map(r => r.id);
    if (batchApproveBtn) {
      batchApproveBtn.style.display = draftIds.length > 0 ? 'inline-flex' : 'none';
      batchApproveBtn.innerHTML = `
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Approve All Drafts (${draftIds.length})
      `;
      batchApproveBtn.onclick = () => approveBatchResults(draftIds);
    }

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="10" class="py-12 text-center text-slate-500">
            <p class="font-medium">No results found for the selected criteria.</p>
            <p class="text-xs text-slate-400 mt-1">Use the '+ Record Examination Score' button to add scores.</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = list.map(r => {
      const std = studentMap[r.studentId] || { matricNumber: 'N/A', firstName: 'Student', lastName: '' };
      const studentName = `${std.firstName} ${std.lastName}`;

      return `
        <tr class="hover:bg-slate-50 transition border-b border-slate-100 ${r.status === 'Draft' ? 'bg-amber-50/20' : ''}">
          <td class="py-3 px-4 font-mono text-xs font-bold text-blue-900">${std.matricNumber}</td>
          <td class="py-3 px-4 font-medium text-slate-900 text-sm">${studentName}</td>
          <td class="py-3 px-4 font-mono text-xs font-semibold text-slate-700">${r.courseCode}</td>
          <td class="py-3 px-4 text-xs text-slate-600 max-w-xs truncate">${r.courseTitle || 'Course Title'}</td>
          <td class="py-3 px-4 text-center text-xs font-bold text-slate-700">${r.creditUnit}</td>
          <td class="py-3 px-4 text-center font-bold text-sm text-slate-900">${r.score}</td>
          <td class="py-3 px-4 text-center">${UIHelpers.getGradeBadge(r.grade)}</td>
          <td class="py-3 px-4 text-center font-mono text-xs text-slate-600">${r.gradePoint.toFixed(2)}</td>
          <td class="py-3 px-4 text-center">${UIHelpers.getStatusBadge(r.status)}</td>
          <td class="py-3 px-4 text-right">
            <div class="flex items-center justify-end space-x-1.5">
              ${r.status === 'Draft' ? `
                <button type="button" onclick="approveSingleResult('${r.id}')" title="Approve Result" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </button>
              ` : ''}
              <button type="button" onclick="openEditScoreModal('${r.id}', ${r.score})" title="Modify Score" class="p-1.5 text-slate-600 hover:text-blue-900 hover:bg-slate-100 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button type="button" onclick="deleteResultRecord('${r.id}')" title="Delete Score" class="p-1.5 text-slate-600 hover:text-red-700 hover:bg-slate-100 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="10" class="py-6 text-center text-red-500">${err.message}</td></tr>`;
  }
}

async function approveSingleResult(id) {
  await api.results.approve([id]);
  showToast("Examination result approved", "success");
  loadResultsTable();
}

function approveBatchResults(ids) {
  confirmAction({
    title: "Approve Examination Results",
    message: `Approve ${ids.length} draft result records? Once approved, they are locked into official transcript calculations.`,
    confirmText: "Approve All",
    confirmClass: "bg-emerald-600 hover:bg-emerald-700 text-white",
    onConfirm: async () => {
      await api.results.approve(ids);
      showToast(`${ids.length} results approved successfully!`, "success");
      loadResultsTable();
    }
  });
}

function openEditScoreModal(id, currentScore) {
  const newScore = prompt("Enter modified examination score (0 - 100):", currentScore);
  if (newScore === null) return;
  const num = parseFloat(newScore);
  if (isNaN(num) || num < 0 || num > 100) {
    showToast("Invalid score. Must be between 0 and 100.", "error");
    return;
  }

  api.results.getAll().then(list => {
    const res = list.find(r => r.id === id);
    if (!res) return;
    res.score = num;
    api.results.save(res).then(() => {
      showToast("Score updated and grade recalculated", "success");
      loadResultsTable();
    });
  });
}

function deleteResultRecord(id) {
  confirmAction({
    title: "Delete Result Entry",
    message: "Are you sure you want to remove this examination score?",
    confirmText: "Delete",
    confirmClass: "bg-red-600 hover:bg-red-700 text-white",
    onConfirm: async () => {
      await api.results.delete(id);
      showToast("Result removed", "success");
      loadResultsTable();
    }
  });
}

// Class-wide Batch Entry Modal
async function openBatchScoreModal() {
  const modal = document.getElementById('batch-score-modal');
  if (!modal) return;

  const courses = await api.courses.getAll();
  const selectCourse = document.getElementById('batch-course-select');
  if (selectCourse) {
    selectCourse.innerHTML = courses.map(c => `<option value="${c.id}" data-level="${c.level}" data-sem="${c.semester}">${c.code} - ${c.title} (${c.level})</option>`).join('');
  }

  loadBatchStudentsList();
  openModal('batch-score-modal');
}

async function loadBatchStudentsList() {
  const selectCourse = document.getElementById('batch-course-select');
  const tbody = document.getElementById('batch-students-tbody');
  if (!selectCourse || !tbody) return;

  const courseId = selectCourse.value;
  const course = await api.courses.getById(courseId);
  const students = await api.students.getAll({ level: course.level });

  if (students.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-slate-400">No students found at level ${course.level}.</td></tr>`;
    return;
  }

  tbody.innerHTML = students.map((std, i) => `
    <tr class="border-b border-slate-100">
      <td class="py-2.5 px-3 font-mono text-xs font-semibold text-blue-900">${std.matricNumber}</td>
      <td class="py-2.5 px-3 text-sm font-medium text-slate-800">${std.firstName} ${std.lastName}</td>
      <td class="py-2.5 px-3">
        <input type="number" min="0" max="100" placeholder="Score (0-100)" data-stdid="${std.id}" class="batch-score-field w-28 px-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-900">
      </td>
      <td class="py-2.5 px-3 text-xs text-slate-500 batch-grade-cell font-mono font-bold">-</td>
    </tr>
  `).join('');

  // Auto calculate when typing in batch
  tbody.querySelectorAll('.batch-score-field').forEach(input => {
    input.addEventListener('input', (e) => {
      const cell = e.target.parentElement.nextElementSibling;
      const val = parseFloat(e.target.value);
      if (!isNaN(val) && val >= 0 && val <= 100) {
        const { grade, point } = api.grading.calculateGrade(val);
        cell.innerHTML = `${grade} <span class="text-slate-400">(${point.toFixed(2)})</span>`;
      } else {
        cell.textContent = '-';
      }
    });
  });
}

async function saveBatchResults() {
  const selectCourse = document.getElementById('batch-course-select');
  const course = await api.courses.getById(selectCourse.value);
  const session = document.getElementById('batch-session-select').value;
  const semester = course.semester;

  const fields = document.querySelectorAll('.batch-score-field');
  const resultsToSave = [];

  fields.forEach(f => {
    const score = parseFloat(f.value);
    if (!isNaN(score) && score >= 0 && score <= 100) {
      resultsToSave.push({
        studentId: f.dataset.stdid,
        courseId: course.id,
        courseCode: course.code,
        courseTitle: course.title,
        creditUnit: course.unit,
        score,
        sessionName: session,
        semester,
        level: course.level,
        status: "Approved"
      });
    }
  });

  if (resultsToSave.length === 0) {
    showToast("No valid scores entered", "warning");
    return;
  }

  await api.results.batchSave(resultsToSave);
  showToast(`${resultsToSave.length} examination scores saved successfully!`, "success");
  closeModal('batch-score-modal');
  loadResultsTable();
}

window.approveSingleResult = approveSingleResult;
window.approveBatchResults = approveBatchResults;
window.openEditScoreModal = openEditScoreModal;
window.deleteResultRecord = deleteResultRecord;
window.openBatchScoreModal = openBatchScoreModal;
window.loadBatchStudentsList = loadBatchStudentsList;
window.saveBatchResults = saveBatchResults;
