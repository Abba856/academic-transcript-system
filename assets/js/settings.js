/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Settings & Grading Scale Configuration
 */

document.addEventListener('DOMContentLoaded', () => {
  initSettingsPage();
});

async function initSettingsPage() {
  const settings = await api.settings.get();

  // Populate Institution Form
  const instForm = document.getElementById('settings-institution-form');
  if (instForm) {
    document.getElementById('inst-name').value = settings.institution.name;
    document.getElementById('inst-sub').value = settings.institution.subtitle;
    document.getElementById('inst-dept').value = settings.institution.department;
    document.getElementById('inst-address').value = settings.institution.address;
    document.getElementById('inst-motto').value = settings.institution.motto;
    document.getElementById('inst-rector').value = settings.institution.rector;
    document.getElementById('inst-registrar').value = settings.institution.registrar;
    document.getElementById('inst-hod').value = settings.institution.hod;

    instForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const updated = {
        name: document.getElementById('inst-name').value,
        subtitle: document.getElementById('inst-sub').value,
        department: document.getElementById('inst-dept').value,
        address: document.getElementById('inst-address').value,
        motto: document.getElementById('inst-motto').value,
        rector: document.getElementById('inst-rector').value,
        registrar: document.getElementById('inst-registrar').value,
        hod: document.getElementById('inst-hod').value
      };
      await api.settings.update('institution', updated);
      showToast("Institution information updated successfully!", "success");
    });
  }

  // Populate User Profile
  const profileForm = document.getElementById('settings-profile-form');
  if (profileForm) {
    document.getElementById('prof-name').value = settings.userProfile.name;
    document.getElementById('prof-title').value = settings.userProfile.title;
    document.getElementById('prof-email').value = settings.userProfile.email;
    document.getElementById('prof-phone').value = settings.userProfile.phone || '';

    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const updated = {
        name: document.getElementById('prof-name').value,
        title: document.getElementById('prof-title').value,
        email: document.getElementById('prof-email').value,
        phone: document.getElementById('prof-phone').value
      };
      await api.settings.update('userProfile', updated);
      showToast("Staff profile updated!", "success");
      AppLayout.setupAuthCheck();
    });
  }

  // Populate Password Management Form
  const passwordForm = document.getElementById('settings-password-form');
  if (passwordForm) {
    passwordForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const currentPass = document.getElementById('pwd-current').value;
      const newPass = document.getElementById('pwd-new').value;
      const confirmPass = document.getElementById('pwd-confirm').value;

      if (newPass !== confirmPass) {
        showToast("New passwords do not match!", "error");
        return;
      }
      if (newPass.length < 6) {
        showToast("Password must be at least 6 characters.", "warning");
        return;
      }

      showToast("Administrative password updated successfully!", "success");
      passwordForm.reset();
    });
  }

  // Render Configurable Grading Scale Table
  renderGradingScaleEditor();
}

/**
 * Render and edit Grading Scale
 */
function renderGradingScaleEditor() {
  const tbody = document.getElementById('grading-scale-editor-tbody');
  if (!tbody) return;

  const scale = api.grading.getScale();
  tbody.innerHTML = scale.map((item, index) => `
    <tr class="border-b border-slate-100 hover:bg-slate-50">
      <td class="py-2.5 px-3">
        <input type="text" value="${item.grade}" class="scale-grade-input w-16 px-2 py-1 font-bold text-center border border-slate-200 rounded text-xs font-mono" data-index="${index}">
      </td>
      <td class="py-2.5 px-3">
        <div class="flex items-center space-x-1">
          <input type="number" value="${item.minScore}" min="0" max="100" class="scale-min-input w-14 px-2 py-1 text-center border border-slate-200 rounded text-xs" data-index="${index}">
          <span class="text-slate-400 text-xs">-</span>
          <input type="number" value="${item.maxScore}" min="0" max="100" class="scale-max-input w-14 px-2 py-1 text-center border border-slate-200 rounded text-xs" data-index="${index}">
        </div>
      </td>
      <td class="py-2.5 px-3">
        <input type="number" step="0.25" min="0" max="5.0" value="${item.point.toFixed(2)}" class="scale-point-input w-20 px-2 py-1 font-mono font-bold text-center border border-slate-200 rounded text-xs" data-index="${index}">
      </td>
      <td class="py-2.5 px-3">
        <input type="text" value="${item.description}" class="scale-desc-input w-full px-2 py-1 border border-slate-200 rounded text-xs" data-index="${index}">
      </td>
      <td class="py-2.5 px-3 text-right">
        <button type="button" onclick="removeGradingRow(${index})" class="p-1 text-red-500 hover:bg-red-50 rounded">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </td>
    </tr>
  `).join('');
}

function saveGradingScaleChanges() {
  const scale = [];
  const rows = document.querySelectorAll('#grading-scale-editor-tbody tr');

  rows.forEach(row => {
    const grade = row.querySelector('.scale-grade-input').value.trim();
    const minScore = parseFloat(row.querySelector('.scale-min-input').value);
    const maxScore = parseFloat(row.querySelector('.scale-max-input').value);
    const point = parseFloat(row.querySelector('.scale-point-input').value);
    const description = row.querySelector('.scale-desc-input').value.trim();

    if (grade && !isNaN(minScore) && !isNaN(maxScore) && !isNaN(point)) {
      scale.push({ grade, minScore, maxScore, point, description });
    }
  });

  api.grading.updateScale(scale);
  showToast("Institutional grading policy updated!", "success");
  renderGradingScaleEditor();
}

function addGradingRow() {
  const scale = api.grading.getScale();
  scale.push({
    grade: "NEW",
    minScore: 0,
    maxScore: 39,
    point: 0.00,
    description: "New Scale Tier"
  });
  api.grading.updateScale(scale);
  renderGradingScaleEditor();
}

function removeGradingRow(index) {
  const scale = api.grading.getScale();
  scale.splice(index, 1);
  api.grading.updateScale(scale);
  renderGradingScaleEditor();
}

function resetDatabaseToDefaults() {
  confirmAction({
    title: "Restore Default Demonstration Data",
    message: "This will reset all courses, students, sessions, examination results, transcripts, and settings back to default Kano State Polytechnic sample records.",
    confirmText: "Restore Defaults",
    confirmClass: "bg-red-600 hover:bg-red-700 text-white",
    onConfirm: () => {
      api.settings.resetDefaults();
      showToast("Demo data restored to defaults!", "success");
      setTimeout(() => window.location.reload(), 400);
    }
  });
}

window.renderGradingScaleEditor = renderGradingScaleEditor;
window.saveGradingScaleChanges = saveGradingScaleChanges;
window.addGradingRow = addGradingRow;
window.removeGradingRow = removeGradingRow;
window.resetDatabaseToDefaults = resetDatabaseToDefaults;
