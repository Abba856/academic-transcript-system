/**
 * Web-Based Academic Transcript Generating System with QR Verification
 * Kano State Polytechnic - Department of Computer Science
 * Dashboard Analytics & Dynamic Feed Controller
 */

document.addEventListener('DOMContentLoaded', async () => {
  await loadDashboardMetrics();
  await loadRecentStudents();
  await loadRecentTranscripts();
  await loadActivityLog();
  renderGradeDistributionChart();
});

/**
 * Fetch and populate primary statistical summary cards
 */
async function loadDashboardMetrics() {
  try {
    const students = await api.students.getAll();
    const courses = await api.courses.getAll();
    const sessions = await api.sessions.getAll();
    const results = await api.results.getAll();
    const transcripts = await api.transcripts.getAll();

    document.getElementById('metric-total-students').textContent = students.length;
    document.getElementById('metric-total-courses').textContent = courses.length;
    document.getElementById('metric-total-sessions').textContent = sessions.length;
    document.getElementById('metric-total-results').textContent = results.length;
    document.getElementById('metric-total-transcripts').textContent = transcripts.length;
  } catch (err) {
    console.error("Failed to load dashboard metrics", err);
  }
}

/**
 * Load and render recent student registrations
 */
async function loadRecentStudents() {
  const container = document.getElementById('recent-students-tbody');
  if (!container) return;

  try {
    const students = await api.students.getAll();
    const recent = students.slice(0, 5);

    if (recent.length === 0) {
      container.innerHTML = `<tr><td colspan="5" class="py-6 text-center text-slate-500 text-sm">No registered students found.</td></tr>`;
      return;
    }

    container.innerHTML = recent.map(std => `
      <tr class="hover:bg-slate-50 transition border-b border-slate-100">
        <td class="py-3 px-4 font-mono text-xs font-semibold text-blue-900">${std.matricNumber}</td>
        <td class="py-3 px-4">
          <div class="font-medium text-slate-800">${std.firstName} ${std.lastName}</div>
          <div class="text-xs text-slate-400">${std.email}</div>
        </td>
        <td class="py-3 px-4 text-xs text-slate-600">${std.currentLevel}</td>
        <td class="py-3 px-4">${UIHelpers.getStatusBadge(std.status)}</td>
        <td class="py-3 px-4 text-right">
          <a href="student-profile.html?id=${std.id}" class="text-blue-700 hover:text-blue-900 text-xs font-semibold hover:underline">
            View &rarr;
          </a>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error("Failed to render recent students", err);
  }
}

/**
 * Load and render recently generated transcripts
 */
async function loadRecentTranscripts() {
  const container = document.getElementById('recent-transcripts-tbody');
  if (!container) return;

  try {
    const transcripts = await api.transcripts.getAll();
    const recent = transcripts.slice(0, 5);

    if (recent.length === 0) {
      container.innerHTML = `<tr><td colspan="6" class="py-6 text-center text-slate-500 text-sm">No transcripts generated yet.</td></tr>`;
      return;
    }

    container.innerHTML = recent.map(tr => `
      <tr class="hover:bg-slate-50 transition border-b border-slate-100">
        <td class="py-3 px-4 font-mono text-xs font-bold text-slate-900">${tr.referenceNumber}</td>
        <td class="py-3 px-4 font-medium text-slate-800 text-sm">${tr.studentName}</td>
        <td class="py-3 px-4 font-semibold text-xs text-blue-900">${tr.cgpa.toFixed(2)}</td>
        <td class="py-3 px-4 text-xs text-slate-600">${tr.classification}</td>
        <td class="py-3 px-4">${UIHelpers.getStatusBadge(tr.status)}</td>
        <td class="py-3 px-4 text-right">
          <a href="transcript-preview.html?id=${tr.id}" class="px-2.5 py-1 text-xs font-semibold text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-lg transition inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Preview
          </a>
        </td>
      </tr>
    `).join('');
  } catch (err) {
    console.error("Failed to render recent transcripts", err);
  }
}

/**
 * Load Activity feed
 */
async function loadActivityLog() {
  const container = document.getElementById('recent-activities-list');
  if (!container) return;

  const activities = [
    { title: "Transcript Generated", desc: "Official transcript KSP/CST/TR/2024/00842 issued for Fatima Zahra Abubakar.", time: "10 mins ago", icon: "doc" },
    { title: "Results Approved", desc: "15 results approved for COM 221 (Web Application Development).", time: "1 hour ago", icon: "check" },
    { title: "QR Verification Performed", desc: "Public verification check logged from IP 197.210.45.12.", time: "3 hours ago", icon: "qr" },
    { title: "New Student Enrolled", desc: "Aliyu Mustapha Shehu registered for ND Computer Science.", time: "Yesterday", icon: "user" }
  ];

  container.innerHTML = activities.map(act => `
    <li class="flex items-start space-x-3 text-sm py-2 border-b border-slate-50 last:border-0">
      <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-900 flex items-center justify-center shrink-0 mt-0.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="flex-1">
        <p class="font-semibold text-slate-800 text-xs">${act.title}</p>
        <p class="text-xs text-slate-500">${act.desc}</p>
        <span class="text-[10px] text-slate-400">${act.time}</span>
      </div>
    </li>
  `).join('');
}

/**
 * Render pure HTML5 Canvas bar chart for Result Processing Grade Distribution
 */
async function renderGradeDistributionChart() {
  const canvas = document.getElementById('grade-distribution-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const results = await api.results.getAll();
  const counts = { 'A': 0, 'AB': 0, 'B': 0, 'BC': 0, 'C': 0, 'CD': 0, 'D': 0, 'E': 0, 'F': 0 };

  results.forEach(r => {
    if (counts[r.grade] !== undefined) counts[r.grade]++;
  });

  const labels = Object.keys(counts);
  const values = Object.values(counts);
  const maxValue = Math.max(...values, 5);

  const width = canvas.width = canvas.parentElement.clientWidth || 450;
  const height = canvas.height = 200;
  const padding = 30;
  const chartWidth = width - padding * 2;
  const chartHeight = height - padding * 2;

  ctx.clearRect(0, 0, width, height);

  // Background Gridlines
  ctx.strokeStyle = '#f1f5f9';
  ctx.lineWidth = 1;
  for (let i = 0; i <= 4; i++) {
    const y = padding + (chartHeight / 4) * i;
    ctx.beginPath();
    ctx.moveTo(padding, y);
    ctx.lineTo(width - padding, y);
    ctx.stroke();

    // Y Axis label
    ctx.fillStyle = '#94a3b8';
    ctx.font = '10px Inter, sans-serif';
    ctx.textAlign = 'right';
    const labelVal = Math.round(maxValue * (1 - i / 4));
    ctx.fillText(labelVal, padding - 6, y + 3);
  }

  // Draw Bars
  const barWidth = chartWidth / labels.length * 0.6;
  const gap = chartWidth / labels.length;

  labels.forEach((label, idx) => {
    const val = values[idx];
    const barHeight = (val / maxValue) * chartHeight;
    const x = padding + idx * gap + (gap - barWidth) / 2;
    const y = height - padding - barHeight;

    // Gradient bar
    const grad = ctx.createLinearGradient(0, y, 0, height - padding);
    if (['A', 'AB'].includes(label)) {
      grad.addColorStop(0, '#059669');
      grad.addColorStop(1, '#10b981');
    } else if (['B', 'BC'].includes(label)) {
      grad.addColorStop(0, '#1d4ed8');
      grad.addColorStop(1, '#3b82f6');
    } else if (['C', 'CD'].includes(label)) {
      grad.addColorStop(0, '#d97706');
      grad.addColorStop(1, '#f59e0b');
    } else {
      grad.addColorStop(0, '#dc2626');
      grad.addColorStop(1, '#ef4444');
    }

    ctx.fillStyle = grad;
    ctx.beginPath();
    ctx.roundRect ? ctx.roundRect(x, y, barWidth, barHeight, [4, 4, 0, 0]) : ctx.rect(x, y, barWidth, barHeight);
    ctx.fill();

    // Value above bar
    if (val > 0) {
      ctx.fillStyle = '#1e293b';
      ctx.font = 'bold 10px Inter, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(val, x + barWidth / 2, y - 4);
    }

    // X Axis Label
    ctx.fillStyle = '#64748b';
    ctx.font = '600 11px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText(label, x + barWidth / 2, height - padding + 16);
  });
}

window.addEventListener('resize', () => {
  renderGradeDistributionChart();
});
