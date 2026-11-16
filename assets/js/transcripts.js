/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Transcript Issuance, Document Rendering & QR Encoding Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('transcripts-table-tbody')) {
    initTranscriptsPage();
  }
  if (document.getElementById('transcript-official-document')) {
    initTranscriptPreviewPage();
  }
});

// ============================================================================
// Transcripts Management List Page
// ============================================================================
let transcriptSearchQuery = '';

async function initTranscriptsPage() {
  const searchInput = document.getElementById('transcript-search-input');
  const studentSelect = document.getElementById('gen-transcript-student');
  const genModal = document.getElementById('generate-transcript-modal');
  const genForm = document.getElementById('generate-transcript-form');

  // Populate student select in generate modal
  const students = await api.students.getAll();
  if (studentSelect) {
    studentSelect.innerHTML = `<option value="" disabled selected>Select Student to Generate Transcript...</option>` +
      students.map(s => `<option value="${s.id}">${s.matricNumber} - ${s.firstName} ${s.lastName} (${s.programme})</option>`).join('');

    studentSelect.addEventListener('change', async (e) => {
      const studentId = e.target.value;
      const results = await api.results.getAll({ studentId });
      const previewCard = document.getElementById('gen-student-preview-card');
      if (previewCard) {
        if (results.length === 0) {
          previewCard.innerHTML = `
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
              Warning: No examination results recorded for this student. Enter results first.
            </div>
          `;
          document.getElementById('confirm-generate-btn').disabled = true;
        } else {
          const calc = AcademicCalculations.calculateCumulativeCGPA(results);
          previewCard.innerHTML = `
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs space-y-1">
              <div class="font-bold text-blue-900">${results.length} Course Results Available</div>
              <div class="text-slate-700">Cumulative CGPA: <span class="font-bold text-slate-900">${calc.cgpa}</span> &bull; Classification: <span class="font-bold text-emerald-700">${calc.classification}</span></div>
              <div class="text-slate-500">Total Credit Units Earned: ${calc.cumulativeCreditUnits} | Total QP: ${calc.cumulativeQualityPoints.toFixed(2)}</div>
            </div>
          `;
          document.getElementById('confirm-generate-btn').disabled = false;
        }
      }
    });
  }

  // Check if studentId was passed in query param to auto-open modal
  const urlParams = new URLSearchParams(window.location.search);
  const autoStdId = urlParams.get('studentId');
  if (autoStdId && studentSelect) {
    studentSelect.value = autoStdId;
    studentSelect.dispatchEvent(new Event('change'));
    openModal('generate-transcript-modal');
  }

  // Handle Generate Submission
  if (genForm) {
    genForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const studentId = studentSelect.value;
      const remarks = document.getElementById('gen-remarks').value;

      try {
        const transcript = await api.transcripts.generate(studentId, { remarks });
        showToast(`Official Transcript ${transcript.referenceNumber} generated!`, "success");
        closeModal('generate-transcript-modal');
        loadTranscriptsTable();
        // Prompt to preview immediately
        setTimeout(() => {
          openTranscriptPreviewModal(transcript.id);
        }, 300);
      } catch (err) {
        showToast(err.message, "error");
      }
    });
  }

  // Search input
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      transcriptSearchQuery = e.target.value;
      loadTranscriptsTable();
    });
  }

  loadTranscriptsTable();
}

/**
 * Load Transcripts Records Table
 */
async function loadTranscriptsTable() {
  const tbody = document.getElementById('transcripts-table-tbody');
  const countBadge = document.getElementById('transcripts-count-badge');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-slate-400">Loading transcript records...</td></tr>`;

  try {
    const list = await api.transcripts.getAll({ search: transcriptSearchQuery });
    if (countBadge) countBadge.textContent = `${list.length} Issued`;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="py-12 text-center text-slate-500">
            <p class="font-medium">No official transcripts found.</p>
            <p class="text-xs text-slate-400 mt-1">Click '+ Generate Official Transcript' to create one.</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = list.map(tr => `
      <tr class="hover:bg-slate-50 transition border-b border-slate-100 ${tr.status === 'Revoked' ? 'bg-red-50/30' : ''}">
        <td class="py-3.5 px-4 font-mono text-xs font-bold text-slate-900">${tr.referenceNumber}</td>
        <td class="py-3.5 px-4 font-mono text-xs text-blue-900 font-semibold">${tr.matricNumber}</td>
        <td class="py-3.5 px-4 font-medium text-slate-900 text-sm">${tr.studentName}</td>
        <td class="py-3.5 px-4 text-xs font-mono font-bold text-slate-900">${tr.cgpa.toFixed(2)}</td>
        <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">${tr.classification}</td>
        <td class="py-3.5 px-4 text-xs text-slate-500">${tr.issueDate}</td>
        <td class="py-3.5 px-4">${UIHelpers.getStatusBadge(tr.status)}</td>
        <td class="py-3.5 px-4 text-right">
          <div class="flex items-center justify-end space-x-2">
            <button type="button" onclick="openTranscriptPreviewModal('${tr.id}')" title="Quick Preview" class="p-1.5 text-blue-900 hover:bg-blue-50 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
            <a href="transcript-preview.html?id=${tr.id}" title="Print / Download Official Transcript" class="p-1.5 text-slate-700 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </a>
            ${tr.status === 'Valid' ? `
              <button type="button" onclick="revokeTranscript('${tr.id}', '${tr.referenceNumber}')" title="Revoke Transcript" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              </button>
            ` : ''}
          </div>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-red-500">${err.message}</td></tr>`;
  }
}

/**
 * Revoke Transcript Record
 */
function revokeTranscript(id, ref) {
  const reason = prompt(`Please enter administrative reason for REVOKING transcript ${ref}:`, "Disciplinary committee determination / clerical correction");
  if (!reason) return;

  confirmAction({
    title: "Confirm Transcript Revocation",
    message: `Are you certain you wish to revoke ${ref}? Anyone scanning the verification QR code will immediately be notified that this transcript is INVALID & REVOKED.`,
    confirmText: "Revoke Transcript",
    confirmClass: "bg-red-600 hover:bg-red-700 text-white",
    onConfirm: async () => {
      try {
        await api.transcripts.revoke(id, reason);
        showToast(`Transcript ${ref} marked as REVOKED`, "warning");
        loadTranscriptsTable();
      } catch (err) {
        showToast(err.message, "error");
      }
    }
  });
}

/**
 * Open Quick Preview Modal inside dashboard
 */
async function openTranscriptPreviewModal(transcriptId) {
  try {
    const tr = await api.transcripts.getById(transcriptId);
    const modalContent = document.getElementById('preview-modal-body');
    const fullPageLink = document.getElementById('preview-fullpage-link');

    if (fullPageLink) {
      fullPageLink.href = `transcript-preview.html?id=${tr.id}`;
    }

    if (modalContent) {
      modalContent.innerHTML = `
        <div class="border border-slate-200 rounded-xl p-6 bg-white space-y-4">
          <div class="flex items-center justify-between border-b pb-4">
            <div>
              <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">KANO STATE POLYTECHNIC</span>
              <h3 class="text-base font-bold text-slate-900">Official Transcript Verification Record</h3>
            </div>
            <div id="modal-qr-canvas-container" class="shrink-0"></div>
          </div>
          <div class="grid grid-cols-2 gap-4 text-xs">
            <div><span class="text-slate-400">Reference:</span> <span class="font-mono font-bold text-slate-800">${tr.referenceNumber}</span></div>
            <div><span class="text-slate-400">Issue Date:</span> <span class="font-semibold text-slate-800">${tr.issueDate}</span></div>
            <div><span class="text-slate-400">Student Name:</span> <span class="font-semibold text-slate-800">${tr.studentName}</span></div>
            <div><span class="text-slate-400">Matric Number:</span> <span class="font-mono font-semibold text-blue-900">${tr.matricNumber}</span></div>
            <div><span class="text-slate-400">Programme:</span> <span class="font-semibold text-slate-800">${tr.programme}</span></div>
            <div><span class="text-slate-400">Final CGPA:</span> <span class="font-bold text-blue-900">${tr.cgpa.toFixed(2)} (${tr.classification})</span></div>
          </div>
          <div class="bg-slate-50 p-3 rounded-lg text-xs text-slate-600">
            <strong>Remarks:</strong> ${tr.remarks}
          </div>
          <div class="flex justify-between items-center pt-2">
            <div>Status: ${UIHelpers.getStatusBadge(tr.status)}</div>
            <a href="transcript-preview.html?id=${tr.id}" class="px-4 py-2 bg-blue-900 text-white rounded-lg text-xs font-bold hover:bg-blue-800 transition">
              Open Full Printable Official Document &rarr;
            </a>
          </div>
        </div>
      `;

      // Render QR code in modal
      setTimeout(() => {
        TranscriptVerificationService.renderQR('modal-qr-canvas-container', tr.referenceNumber, { size: 90 });
      }, 50);
    }

    openModal('quick-transcript-modal');
  } catch (err) {
    showToast(err.message, "error");
  }
}

// ============================================================================
// Official Printable A4 Transcript Page Controller (transcript-preview.html)
// ============================================================================
async function initTranscriptPreviewPage() {
  const urlParams = new URLSearchParams(window.location.search);
  let trId = urlParams.get('id');

  const transcripts = await api.transcripts.getAll();
  if (!trId && transcripts.length > 0) {
    trId = transcripts[0].id;
  }

  if (!trId) {
    showToast("No transcript specified", "error");
    return;
  }

  const tr = await api.transcripts.getById(trId);
  const student = await api.students.getById(tr.studentId);
  const settings = await api.settings.get();
  const allResults = await api.results.getAll({ studentId: tr.studentId });
  const progression = AcademicCalculations.getStudentAcademicProgression(allResults);

  // Populate Header & Document Metadata
  document.getElementById('doc-institution-name').textContent = settings.institution.name;
  document.getElementById('doc-institution-sub').textContent = settings.institution.subtitle;
  document.getElementById('doc-department-name').textContent = settings.institution.department;
  document.getElementById('doc-motto').textContent = `"${settings.institution.motto}"`;
  document.getElementById('doc-reference-no').textContent = tr.referenceNumber;
  document.getElementById('doc-issue-date').textContent = tr.issueDate;

  // Student Details
  document.getElementById('doc-student-name').textContent = tr.studentName;
  document.getElementById('doc-matric-no').textContent = tr.matricNumber;
  document.getElementById('doc-programme').textContent = tr.programme;
  document.getElementById('doc-department').textContent = tr.department;
  document.getElementById('doc-admission-year').textContent = tr.admissionYear;
  document.getElementById('doc-graduation-year').textContent = tr.graduationYear;

  // Render Semester Tables
  const tablesContainer = document.getElementById('doc-semester-tables-container');
  if (tablesContainer) {
    tablesContainer.innerHTML = progression.semesters.map(sem => `
      <div class="mb-5 print-avoid-break">
        <div class="bg-slate-100 border border-slate-300 px-3 py-1 text-xs font-bold flex justify-between items-center">
          <span>${sem.sessionName.toUpperCase()} &bull; ${sem.semester.toUpperCase()} (${sem.level})</span>
          <span>Semester GPA: ${sem.semesterSummary.gpa}</span>
        </div>
        <table class="w-full text-[11px] border-collapse border border-slate-300">
          <thead>
            <tr class="bg-slate-50 text-[10px] font-bold text-slate-700 uppercase">
              <th class="border border-slate-300 py-1 px-2 text-left w-20">Course Code</th>
              <th class="border border-slate-300 py-1 px-2 text-left">Course Title</th>
              <th class="border border-slate-300 py-1 px-2 text-center w-12">Credit</th>
              <th class="border border-slate-300 py-1 px-2 text-center w-12">Score</th>
              <th class="border border-slate-300 py-1 px-2 text-center w-12">Grade</th>
              <th class="border border-slate-300 py-1 px-2 text-center w-16">Grade Point</th>
              <th class="border border-slate-300 py-1 px-2 text-right w-16">Quality Point</th>
            </tr>
          </thead>
          <tbody>
            ${sem.results.map(r => `
              <tr class="border-b border-slate-200">
                <td class="border-x border-slate-300 py-1 px-2 font-mono font-semibold">${r.courseCode}</td>
                <td class="border-x border-slate-300 py-1 px-2">${r.courseTitle || r.title || 'Course'}</td>
                <td class="border-x border-slate-300 py-1 px-2 text-center font-semibold">${r.creditUnit}</td>
                <td class="border-x border-slate-300 py-1 px-2 text-center">${r.score}</td>
                <td class="border-x border-slate-300 py-1 px-2 text-center font-bold">${r.grade}</td>
                <td class="border-x border-slate-300 py-1 px-2 text-center font-mono">${r.gradePoint.toFixed(2)}</td>
                <td class="border-x border-slate-300 py-1 px-2 text-right font-mono font-semibold">${r.qualityPoint.toFixed(2)}</td>
              </tr>
            `).join('')}
          </tbody>
          <tfoot>
            <tr class="bg-slate-50 text-[10px] font-bold border-t border-slate-300">
              <td colspan="2" class="py-1 px-2 text-right">Semester Total:</td>
              <td class="py-1 px-2 text-center">${sem.semesterSummary.totalCreditUnits}</td>
              <td colspan="3" class="py-1 px-2 text-right">TQP:</td>
              <td class="py-1 px-2 text-right">${sem.semesterSummary.totalQualityPoints.toFixed(2)}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    `).join('');
  }

  // Cumulative Summary Card
  document.getElementById('doc-total-credits').textContent = progression.overall.cumulativeCreditUnits;
  document.getElementById('doc-total-qp').textContent = progression.overall.cumulativeQualityPoints.toFixed(2);
  document.getElementById('doc-final-cgpa').textContent = tr.cgpa.toFixed(2);
  document.getElementById('doc-classification').textContent = tr.classification;
  document.getElementById('doc-remarks').textContent = tr.remarks;

  // Render Configurable Grading Scale Table
  const scaleTbody = document.getElementById('doc-grading-scale-tbody');
  if (scaleTbody) {
    scaleTbody.innerHTML = settings.gradingScale.map(item => `
      <tr class="border-b border-slate-200 text-[9.5px]">
        <td class="py-0.5 px-1 font-bold text-center">${item.grade}</td>
        <td class="py-0.5 px-1 text-center font-mono">${item.minScore}-${item.maxScore}</td>
        <td class="py-0.5 px-1 text-center font-mono">${item.point.toFixed(2)}</td>
        <td class="py-0.5 px-1">${item.description}</td>
      </tr>
    `).join('');
  }

  // Render Verification QR Code (Pure vanilla JS into SVG or Canvas)
  TranscriptVerificationService.renderQR('doc-qr-code-container', tr.referenceNumber, { size: 130 });
  const qrRefLink = document.getElementById('doc-qr-verification-url');
  if (qrRefLink) {
    const vUrl = TranscriptVerificationService.getVerificationURL(tr.referenceNumber);
    qrRefLink.textContent = vUrl;
    qrRefLink.href = vUrl;
  }

  // Document Signatures
  document.getElementById('doc-registrar-name').textContent = settings.institution.registrar;
  document.getElementById('doc-hod-name').textContent = settings.institution.hod;

  // If Revoked, show watermark notice
  if (tr.status === 'Revoked') {
    const wm = document.getElementById('doc-watermark');
    if (wm) {
      wm.textContent = "REVOKED - INVALID";
      wm.style.color = "#dc2626";
      wm.style.opacity = "0.15";
    }
  }
}

// Print Handler
function triggerTranscriptPrint() {
  window.print();
}

// Download PDF trigger
function downloadTranscriptPDF() {
  showToast("Preparing official printable A4 document. Please select 'Save as PDF' in the print dialog.", "info", 5000);
  setTimeout(() => {
    window.print();
  }, 400);
}

window.openTranscriptPreviewModal = openTranscriptPreviewModal;
window.revokeTranscript = revokeTranscript;
window.triggerTranscriptPrint = triggerTranscriptPrint;
window.downloadTranscriptPDF = downloadTranscriptPDF;
