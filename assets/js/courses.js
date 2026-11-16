/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Course Management Controller
 */

document.addEventListener('DOMContentLoaded', () => {
  initCoursesPage();
});

let currentCourseFilters = {
  search: '',
  level: 'All',
  semester: 'All'
};

async function initCoursesPage() {
  const searchInput = document.getElementById('course-search-input');
  const levelFilter = document.getElementById('course-level-filter');
  const semesterFilter = document.getElementById('course-semester-filter');
  const addCourseForm = document.getElementById('add-course-form');
  const editCourseForm = document.getElementById('edit-course-form');

  if (searchInput) {
    let debounce;
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounce);
      debounce = setTimeout(() => {
        currentCourseFilters.search = e.target.value;
        loadCoursesTable();
      }, 250);
    });
  }

  if (levelFilter) {
    levelFilter.addEventListener('change', (e) => {
      currentCourseFilters.level = e.target.value;
      loadCoursesTable();
    });
  }

  if (semesterFilter) {
    semesterFilter.addEventListener('change', (e) => {
      currentCourseFilters.semester = e.target.value;
      loadCoursesTable();
    });
  }

  if (addCourseForm) {
    addCourseForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(addCourseForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.courses.create(data);
        showToast(`Course ${data.code} added successfully!`, 'success');
        closeModal('add-course-modal');
        addCourseForm.reset();
        loadCoursesTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  if (editCourseForm) {
    editCourseForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const courseId = document.getElementById('edit-course-id').value;
      const formData = new FormData(editCourseForm);
      const data = Object.fromEntries(formData.entries());

      try {
        await api.courses.update(courseId, data);
        showToast("Course details updated successfully!", "success");
        closeModal('edit-course-modal');
        loadCoursesTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    });
  }

  loadCoursesTable();
}

async function loadCoursesTable() {
  const tbody = document.getElementById('courses-table-tbody');
  const countBadge = document.getElementById('courses-total-count');
  if (!tbody) return;

  tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-400">Loading courses...</td></tr>`;

  try {
    const list = await api.courses.getAll(currentCourseFilters);
    if (countBadge) countBadge.textContent = `${list.length} Courses`;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="py-10 text-center text-slate-500">
            <p class="font-medium">No courses match your filter criteria.</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = list.map(c => `
      <tr class="hover:bg-slate-50 transition border-b border-slate-100">
        <td class="py-3.5 px-4 font-mono font-bold text-sm text-blue-900">${c.code}</td>
        <td class="py-3.5 px-4">
          <div class="font-semibold text-slate-900 text-sm">${c.title}</div>
          <div class="text-xs text-slate-400 line-clamp-1">${c.description || 'Departmental computer science syllabus.'}</div>
        </td>
        <td class="py-3.5 px-4 text-center">
          <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-50 text-blue-900 font-bold text-xs">
            ${c.unit}
          </span>
        </td>
        <td class="py-3.5 px-4 text-xs font-semibold text-slate-700">${c.level}</td>
        <td class="py-3.5 px-4 text-xs font-medium text-slate-600">${c.semester}</td>
        <td class="py-3.5 px-4 text-right">
          <div class="flex items-center justify-end space-x-2">
            <button type="button" onclick="openEditCourseModal('${c.id}')" title="Edit Course" class="p-1.5 text-slate-600 hover:text-amber-700 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button type="button" onclick="deleteCourseRecord('${c.id}', '${c.code}')" title="Delete Course" class="p-1.5 text-slate-600 hover:text-red-700 hover:bg-slate-100 rounded-lg transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-red-500">${err.message}</td></tr>`;
  }
}

async function openEditCourseModal(courseId) {
  try {
    const course = await api.courses.getById(courseId);
    document.getElementById('edit-course-id').value = course.id;
    document.getElementById('edit-course-code').value = course.code;
    document.getElementById('edit-course-title').value = course.title;
    document.getElementById('edit-course-unit').value = course.unit;
    document.getElementById('edit-course-level').value = course.level;
    document.getElementById('edit-course-semester').value = course.semester;
    document.getElementById('edit-course-desc').value = course.description || '';

    openModal('edit-course-modal');
  } catch (err) {
    showToast(err.message, 'error');
  }
}

function deleteCourseRecord(id, code) {
  confirmAction({
    title: "Delete Course",
    message: `Are you sure you want to delete course ${code}? This will remove it from future registrations.`,
    confirmText: "Delete Course",
    confirmClass: "bg-red-600 hover:bg-red-700 text-white",
    onConfirm: async () => {
      try {
        await api.courses.delete(id);
        showToast(`Course ${code} deleted`, 'success');
        loadCoursesTable();
      } catch (err) {
        showToast(err.message, 'error');
      }
    }
  });
}

window.openEditCourseModal = openEditCourseModal;
window.deleteCourseRecord = deleteCourseRecord;
