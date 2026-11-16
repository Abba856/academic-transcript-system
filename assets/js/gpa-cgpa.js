/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * GPA & CGPA Interactive Inspector
 */

document.addEventListener('DOMContentLoaded', () => {
  initGpaCgpaPage();
});

async function initGpaCgpaPage() {
  const studentSelect = document.getElementById('gpa-student-select');
  const students = await api.students.getAll();

  if (studentSelect) {
    studentSelect.innerHTML = `<option value="" disabled selected>Select Student to View Calculations...</option>` +
      students.map(s => `<option value="${s.id}">${s.matricNumber} - ${s.firstName} ${s.lastName} (${s.programme})</option>`).join('');

    studentSelect.addEventListener('change', (e) => {
      loadStudentGpaDetails(e.target.value);
    });

    // Auto-select first student
    if (students.length > 0) {
      studentSelect.value = students[0].id;
      loadStudentGpaDetails(students[0].id);
    }
  }
}

async function loadStudentGpaDetails(studentId) {
  const student = await api.students.getById(studentId);
  const results = await api.results.getAll({ studentId });
  const progression = AcademicCalculations.getStudentAcademicProgression(results);

  // Student Bio Header
  document.getElementById('calc-student-name').textContent = `${student.firstName} ${student.middleName ? student.middleName + ' ' : ''}${student.lastName}`;
  document.getElementById('calc-matric-number').textContent = student.matricNumber;
  document.getElementById('calc-programme').textContent = student.programme;
  document.getElementById('calc-level').textContent = student.currentLevel;

  // Cumulative Cards
  document.getElementById('calc-cum-credits').textContent = progression.overall.cumulativeCreditUnits;
  document.getElementById('calc-cum-qp').textContent = progression.overall.cumulativeQualityPoints.toFixed(2);
  document.getElementById('calc-cum-cgpa').textContent = progression.overall.cgpa;
  document.getElementById('calc-classification').textContent = progression.overall.classification;

  // Render Semester Breakdowns
  const container = document.getElementById('calc-semesters-container');
  if (!container) return;

  if (progression.semesters.length === 0) {
    container.innerHTML = `
      <div class="p-8 text-center bg-white rounded-2xl border border-slate-200">
        <p class="text-sm text-slate-500">No examination scores recorded for this student yet.</p>
        <a href="results.html" class="mt-2 inline-block text-xs font-semibold text-blue-900 hover:underline">+ Record Examination Scores</a>
      </div>
    `;
    return;
  }

  container.innerHTML = progression.semesters.map((sem, idx) => `
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
      <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
        <div>
          <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">Semester ${idx + 1}</span>
          <h3 class="text-base font-bold text-slate-900">${sem.sessionName} &bull; ${sem.semester}</h3>
          <span class="text-xs text-slate-500">Level: ${sem.level}</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="px-4 py-2 bg-blue-900 text-white rounded-xl text-center shadow-sm">
            <div class="text-[10px] font-semibold uppercase text-blue-200">Semester GPA</div>
            <div class="text-lg font-bold font-mono">${sem.semesterSummary.gpa}</div>
          </div>
          <div class="px-4 py-2 bg-slate-900 text-white rounded-xl text-center shadow-sm">
            <div class="text-[10px] font-semibold uppercase text-slate-300">Running CGPA</div>
            <div class="text-lg font-bold font-mono">${sem.runningCumulative.cgpa}</div>
          </div>
        </div>
      </div>

      <!-- Course Results Table -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="bg-slate-50/60 text-[11px] font-bold uppercase text-slate-500 border-b border-slate-200">
              <th class="py-3 px-4">Course Code</th>
              <th class="py-3 px-4">Course Title</th>
              <th class="py-3 px-4 text-center">Credit Unit (CU)</th>
              <th class="py-3 px-4 text-center">Score</th>
              <th class="py-3 px-4 text-center">Grade</th>
              <th class="py-3 px-4 text-center">Grade Point (GP)</th>
              <th class="py-3 px-4 text-right">Quality Points (QP = CU &times; GP)</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            ${sem.results.map(r => `
              <tr class="hover:bg-slate-50/50">
                <td class="py-3 px-4 font-mono font-bold text-xs text-blue-900">${r.courseCode}</td>
                <td class="py-3 px-4 font-medium text-slate-800 text-sm">${r.courseTitle || r.title || 'Course'}</td>
                <td class="py-3 px-4 text-center font-bold text-slate-700">${r.creditUnit}</td>
                <td class="py-3 px-4 text-center font-bold text-slate-900">${r.score}</td>
                <td class="py-3 px-4 text-center">${UIHelpers.getGradeBadge(r.grade)}</td>
                <td class="py-3 px-4 text-center font-mono text-xs text-slate-600">${r.gradePoint.toFixed(2)}</td>
                <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">${r.qualityPoint.toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot class="bg-slate-50 font-semibold text-xs text-slate-700 border-t border-slate-200">
            <tr>
              <td colspan="2" class="py-3 px-4 text-right font-bold text-slate-900">Semester Quality Point Totals:</td>
              <td class="py-3 px-4 text-center font-bold text-blue-900">${sem.semesterSummary.totalCreditUnits} Units</td>
              <td colspan="3" class="py-3 px-4 text-right font-bold text-slate-900">Total Quality Points (TQP):</td>
              <td class="py-3 px-4 text-right font-mono font-bold text-blue-900 text-sm">${sem.semesterSummary.totalQualityPoints.toFixed(2)}</td>
            </tr>
            <tr class="bg-blue-50/60 text-blue-950 font-bold border-t border-blue-100">
              <td colspan="7" class="py-2.5 px-4 text-center">
                <span>Calculation: </span>
                <span class="font-mono">GPA = Total Quality Points (${sem.semesterSummary.totalQualityPoints.toFixed(2)}) &divide; Total Credit Units (${sem.semesterSummary.totalCreditUnits}) = <span class="text-blue-900 underline text-sm">${sem.semesterSummary.gpa}</span></span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  `).join('');
}

window.loadStudentGpaDetails = loadStudentGpaDetails;
