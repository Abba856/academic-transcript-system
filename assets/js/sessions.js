/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Academic Sessions & Semester Management
 */

document.addEventListener('DOMContentLoaded', () => {
  if (document.getElementById('sessions-table-tbody')) {
    initSessionsPage();
  }
  if (document.getElementById('semesters-table-tbody')) {
    initSemestersPage();
  }
});

// ============================================================================
// Sessions Management
// ============================================================================
async function initSessionsPage() {
  const addSessionForm = document.getElementById('add-session-form');
  const editSessionForm = document.getElementById('edit-session-form');

  if (addSessionForm) {
    addSessionForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(addSessionForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.sessions.create({
          name: data.name,
          startDate: data.startDate,
          endDate: data.endDate,
          status: data.status || "Upcoming"
        });
        showToast(`Academic session ${data.name} created!`, 'success');
        closeModal('add-session-modal');
        addSessionForm.reset();
        loadSessionsTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  if (editSessionForm) {
    editSessionForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('edit-session-id').value;
      const formData = new FormData(editSessionForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.sessions.update(id, data);
        showToast("Session updated successfully", 'success');
        closeModal('edit-session-modal');
        loadSessionsTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  loadSessionsTable();
}

async function loadSessionsTable() {
  const tbody = document.getElementById('sessions-table-tbody');
  if (!tbody) return;

  const sessions = await api.sessions.getAll();
  tbody.innerHTML = sessions.map(sess => `
    <tr class="hover:bg-slate-50 transition border-b border-slate-100 ${sess.isActive ? 'bg-blue-50/40' : ''}">
      <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
        ${sess.name}
        ${sess.isActive ? '<span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">Current Active</span>' : ''}
      </td>
      <td class="py-3.5 px-4 text-xs text-slate-600">${sess.startDate || 'N/A'}</td>
      <td class="py-3.5 px-4 text-xs text-slate-600">${sess.endDate || 'N/A'}</td>
      <td class="py-3.5 px-4">${UIHelpers.getStatusBadge(sess.status)}</td>
      <td class="py-3.5 px-4 text-right">
        <div class="flex items-center justify-end space-x-2">
          ${!sess.isActive ? `
            <button type="button" onclick="setActiveSession('${sess.id}', '${sess.name}')" class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
              Set as Active
            </button>
          ` : `
            <span class="px-2.5 py-1 text-xs font-bold text-emerald-600 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Active
            </span>
          `}
          <button type="button" onclick="openEditSessionModal('${sess.id}')" class="p-1.5 text-slate-500 hover:text-blue-900 hover:bg-slate-100 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function openEditSessionModal(id) {
  const sessions = await api.sessions.getAll();
  const sess = sessions.find(s => s.id === id);
  if (!sess) return;

  document.getElementById('edit-session-id').value = sess.id;
  document.getElementById('edit-session-name').value = sess.name;
  document.getElementById('edit-session-start').value = sess.startDate || '';
  document.getElementById('edit-session-end').value = sess.endDate || '';
  document.getElementById('edit-session-status').value = sess.status;

  openModal('edit-session-modal');
}

function setActiveSession(id, name) {
  confirmAction({
    title: "Change Active Academic Session",
    message: `Set session ${name} as the institution's official active session? All new registrations and default transcript filters will point to this session.`,
    confirmText: "Activate Session",
    confirmClass: "bg-emerald-600 hover:bg-emerald-700 text-white",
    onConfirm: async () => {
      await api.sessions.setActive(id);
      showToast(`Active session changed to ${name}`, 'success');
      loadSessionsTable();
      AppLayout.loadActiveSessionInfo();
    }
  });
}

// ============================================================================
// Semester Management
// ============================================================================
async function initSemestersPage() {
  const addSemesterForm = document.getElementById('add-semester-form');
  const editSemesterForm = document.getElementById('edit-semester-form');

  if (addSemesterForm) {
    addSemesterForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(addSemesterForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.semesters.create(data);
        showToast("New semester added successfully", 'success');
        closeModal('add-semester-modal');
        addSemesterForm.reset();
        loadSemestersTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  if (editSemesterForm) {
    editSemesterForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = document.getElementById('edit-semester-id').value;
      const formData = new FormData(editSemesterForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.semesters.update(id, data);
        showToast("Semester details updated", 'success');
        closeModal('edit-semester-modal');
        loadSemestersTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  loadSemestersTable();
}

async function loadSemestersTable() {
  const tbody = document.getElementById('semesters-table-tbody');
  if (!tbody) return;

  const semesters = await api.semesters.getAll();
  const sessions = await api.sessions.getAll();
  const sessionMap = Object.fromEntries(sessions.map(s => [s.id, s.name]));

  tbody.innerHTML = semesters.map(sem => `
    <tr class="hover:bg-slate-50 transition border-b border-slate-100 ${sem.isActive ? 'bg-blue-50/40' : ''}">
      <td class="py-3.5 px-4 font-bold text-slate-900 text-sm">
        ${sem.name}
        ${sem.isActive ? '<span class="ml-2 px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase tracking-wider">Active Semester</span>' : ''}
      </td>
      <td class="py-3.5 px-4 font-mono text-xs text-slate-600">${sem.code || 'SEM'}</td>
      <td class="py-3.5 px-4 text-xs font-semibold text-blue-900">${sessionMap[sem.sessionId] || '2024/2025'}</td>
      <td class="py-3.5 px-4">${UIHelpers.getStatusBadge(sem.status)}</td>
      <td class="py-3.5 px-4 text-right">
        <div class="flex items-center justify-end space-x-2">
          ${!sem.isActive ? `
            <button type="button" onclick="setActiveSemester('${sem.id}', '${sem.name}')" class="px-2.5 py-1 text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg border border-emerald-200 transition">
              Set as Active
            </button>
          ` : `
            <span class="px-2.5 py-1 text-xs font-bold text-emerald-600 flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              Active
            </span>
          `}
          <button type="button" onclick="openEditSemesterModal('${sem.id}')" class="p-1.5 text-slate-500 hover:text-blue-900 hover:bg-slate-100 rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
        </div>
      </td>
    </tr>
  `).join('');
}

async function openEditSemesterModal(id) {
  const semesters = await api.semesters.getAll();
  const sem = semesters.find(s => s.id === id);
  if (!sem) return;

  document.getElementById('edit-semester-id').value = sem.id;
  document.getElementById('edit-semester-name').value = sem.name;
  document.getElementById('edit-semester-code').value = sem.code || '';
  document.getElementById('edit-semester-status').value = sem.status;

  openModal('edit-semester-modal');
}

function setActiveSemester(id, name) {
  confirmAction({
    title: "Set Active Semester",
    message: `Set ${name} as current active semester?`,
    confirmText: "Activate",
    confirmClass: "bg-emerald-600 hover:bg-emerald-700 text-white",
    onConfirm: async () => {
      await api.semesters.setActive(id);
      showToast(`Active semester switched to ${name}`, 'success');
      loadSemestersTable();
      AppLayout.loadActiveSessionInfo();
    }
  });
}

window.setActiveSession = setActiveSession;
window.openEditSessionModal = openEditSessionModal;
window.setActiveSemester = setActiveSemester;
window.openEditSemesterModal = openEditSemesterModal;
