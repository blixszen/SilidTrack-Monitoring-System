// ===================== SilidTrack Admin JS =====================

const BASE = window.APP_BASE || '';
const STATUS_CLASS = {
  Present: 'badge-present',
  Late: 'badge-late',
  Absent: 'badge-absent',
  Excused: 'badge-excused',
};

function phNow() {
  return new Date(); // the computer's own local date and time
}

// ---- Live local time (12h) ----
function tickClock() {
  const el = document.getElementById('phTime');
  if (!el) return;
  const now = phNow();
  const ampm = now.getHours() >= 12 ? 'PM' : 'AM';
  const h = String(now.getHours() % 12 || 12).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  el.textContent = `${h} : ${m} ${ampm}`;
}
setInterval(tickClock, 1000);
tickClock();

// ---- Greeting follows the computer's local time ----
function updateGreeting() {
  const el = document.getElementById('greeting');
  if (!el) return;
  const h = new Date().getHours();
  el.textContent = h < 12 ? 'Good Morning' : (h < 18 ? 'Good Afternoon' : 'Good Evening');
}
updateGreeting();
setInterval(updateGreeting, 60000);

// ---- ESP32 RFID Status Check ----
async function checkDeviceStatus() {
  const badge = document.getElementById('rfidStatus');
  if (!badge) return;
  try {
    const res = await fetch(BASE + '/api/device_status.php');
    const data = await res.json();
    if (data.ok) {
      const isOnline = data.online;
      badge.dataset.online = isOnline;
      badge.innerHTML = `<span class="pulse${isOnline ? '' : ' offline'}"></span>${data.message}`;
      badge.classList.toggle('online', isOnline);
      badge.classList.toggle('offline', !isOnline);
    }
  } catch (err) {
    // Silent fail - keep previous state
  }
}
setInterval(checkDeviceStatus, 10000);
checkDeviceStatus();

// ---- Dashboard Stats Live Update ----
async function updateDashboardStats() {
  if (!document.getElementById('statTotal')) return;
  try {
    const res = await fetch(BASE + '/api/dashboard_stats.php');
    const data = await res.json();
    if (data.ok) {
      document.getElementById('statTotal').textContent = data.total;
      document.getElementById('statPresent').textContent = data.present;
      const lateEl = document.getElementById('statLate');
      const absentEl = document.getElementById('statAbsent');
      lateEl.textContent = data.late < 10 ? data.late : data.late;
      absentEl.textContent = data.absent < 10 ? data.absent : data.absent;
    }
  } catch (err) {
    // Silent fail
  }
}
setInterval(updateDashboardStats, 8000);

// ---- Unpaid Balance Actions ----
async function markUnpaidPaid(studentPk, feeId, amount) {
  if (!confirm('Mark this fee as paid for this student?')) return;
  try {
    const fd = new FormData();
    fd.append('student_pk', studentPk);
    fd.append('fee_id', feeId);
    fd.append('amount', amount);
    const res = await fetch(BASE + '/api/unpaid_actions.php?action=mark_paid', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      location.reload();
    } else {
      alert(data.error || 'Failed to mark as paid.');
    }
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

async function waiveUnpaid(studentPk, feeId) {
  if (!confirm('Waive this fee for this student? This cannot be undone.')) return;
  try {
    const fd = new FormData();
    fd.append('student_pk', studentPk);
    fd.append('fee_id', feeId);
    const res = await fetch(BASE + '/api/unpaid_actions.php?action=waive', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      location.reload();
    } else {
      alert(data.error || 'Failed to waive fee.');
    }
  } catch (err) {
    alert('Error: ' + err.message);
  }
}

// ---- Month calendar: active month label, today in a blue rounded box ----
function renderCalendar() {
  const grid = document.getElementById('calStrip');
  const monthEl = document.getElementById('calMonth');
  if (!grid) return;
  const now = phNow();
  if (monthEl) monthEl.textContent = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

  // Monday-first week that contains today
  const offset = (now.getDay() + 6) % 7;
  const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  grid.innerHTML = names.map((name, i) => {
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate() - offset + i);
    return `<div class="wk-day${i === offset ? ' today' : ''}"><span class="dow">${name}</span><span class="num">${d.getDate()}</span></div>`;
  }).join('');
}
renderCalendar();
setInterval(renderCalendar, 60000); // roll over at midnight

// ---- Modal helpers ----
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach((overlay) => {
  overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('open'); });
});
document.addEventListener('keydown', (e) => {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.modal-overlay.open, .status-menu.open')
    .forEach((el) => el.classList.remove('open'));
});

async function postJSON(url, body) {
  const res = await fetch(BASE + url, { method: 'POST', body });
  return res.json();
}

// ---- Student table search ----
function filterStudents() {
  const input = document.getElementById('searchStudent');
  if (!input) return;
  const q = input.value.trim().toLowerCase();
  document.querySelectorAll('.student-row').forEach((row) => {
    row.style.display = !q || row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ---- Interactive status badges ----
document.addEventListener('click', (e) => {
  const openMenus = document.querySelectorAll('.status-menu.open');
  openMenus.forEach((menu) => {
    if (!menu.contains(e.target) && !menu.previousElementSibling?.contains(e.target)) {
      menu.classList.remove('open');
    }
  });
});

function toggleStatusMenu(trigger) {
  const menu = trigger.nextElementSibling;
  if (!menu) return;
  const isOpen = menu.classList.contains('open');
  document.querySelectorAll('.status-menu.open').forEach((m) => m.classList.remove('open'));
  if (!isOpen) menu.classList.add('open');
}

async function applyStatus(option) {
  const trigger = option.closest('.status-cell').querySelector('.status-trigger');
  const status = option.dataset.status;
  const fd = new FormData();
  fd.append('attendance_id', trigger.dataset.attendanceId || '');
  fd.append('student_id', trigger.dataset.studentId || '');
  fd.append('scan_date', trigger.dataset.date || '');
  fd.append('status', status);
  const result = await postJSON('/api/attendance.php?action=set_status', fd);
  if (!result.ok) return alert(result.error || 'Failed to update status.');
  option.closest('.status-menu').classList.remove('open');
  trigger.className = 'badge ' + (STATUS_CLASS[status] || 'badge-unset') + ' status-trigger';
  trigger.textContent = status;
  trigger.dataset.attendanceId = result.attendance_id || trigger.dataset.attendanceId;
  trigger.closest('tr').dataset.status = status;
}

// ---- Add New Student ----
document.getElementById('addStudentForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/students.php?action=create', new FormData(e.target));
  if (data.ok) location.reload();
  else alert(data.error || 'Failed to create student.');
});

// ---- Override modal (Set Status button) ----
function openOverride(button) {
  const cell = button.closest('td').parentElement.querySelector('.status-cell');
  const trigger = cell?.querySelector('.status-trigger');
  if (!trigger) return;
  const row = trigger.closest('tr');
  document.getElementById('overrideAttendanceId').value = trigger.dataset.attendanceId || '';
  document.getElementById('overrideStudentId').value = trigger.dataset.studentId || '';
  document.getElementById('overrideDate').value = trigger.dataset.date || '';
  document.getElementById('overrideStudentName').textContent =
    row.querySelector('.student-name')?.textContent.trim() || '';
  const currentStatus = trigger.textContent.trim();
  document.getElementById('overrideStatusSelect').value =
    STATUS_CLASS[currentStatus] ? currentStatus : 'Present';
  openModal('overrideModal');
}
document.getElementById('overrideForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/attendance.php?action=set_status', new FormData(e.target));
  if (!data.ok) return alert(data.error || 'Failed to update status.');
  closeModal('overrideModal');
  location.reload();
});

// ---- Reset student password ----
function openPasswordReset(studentId) {
  document.getElementById('passwordStudentId').value = studentId;
  openModal('passwordModal');
}
document.getElementById('passwordForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/students.php?action=reset_password', new FormData(e.target));
  if (data.ok) { closeModal('passwordModal'); alert('Password updated.'); }
  else alert(data.error || 'Failed.');
});

// ---- Announcements ----
document.getElementById('announcementForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/announcements.php?action=create', new FormData(e.target));
  if (data.ok) location.reload(); else alert(data.error || 'Failed to publish.');
});
function editAnnouncement(data) {
  document.getElementById('editAnnouncementId').value = data.id;
  document.getElementById('editAnnouncementTitle').value = data.title;
  document.getElementById('editAnnouncementMessage').value = data.message;
  openModal('editAnnouncementModal');
}
document.getElementById('editAnnouncementForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/announcements.php?action=update', new FormData(e.target));
  if (data.ok) location.reload(); else alert(data.error || 'Failed to update.');
});
async function deleteAnnouncement(id) {
  if (!confirm('Delete this announcement?')) return;
  const data = await postJSON(`/api/announcements.php?action=delete&id=${encodeURIComponent(id)}`);
  if (data.ok) location.reload();
}

// ---- Fees ----
document.getElementById('addFeeForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/fees.php?action=create', new FormData(e.target));
  if (data.ok) location.reload(); else alert(data.error || 'Failed to publish fee.');
});
function editFee(data) {
  document.getElementById('editFeeId').value = data.id;
  document.getElementById('editFeeTitle').value = data.title;
  document.getElementById('editFeeAmount').value = data.amount;
  document.getElementById('editFeeDue').value = data.due_date;
  openModal('editFeeModal');
}
document.getElementById('editFeeForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = await postJSON('/api/fees.php?action=update', new FormData(e.target));
  if (data.ok) location.reload(); else alert(data.error || 'Failed to update fee.');
});
async function deleteFee(id) {
  if (!confirm('Delete this fee?')) return;
  const data = await postJSON(`/api/fees.php?action=delete&id=${encodeURIComponent(id)}`);
  if (data.ok) location.reload();
}
async function savePaymongoAccount() {
  const data = await postJSON('/api/fees.php?action=save_paymongo', new FormData(document.getElementById('paymongoAccountForm')));
  alert(data.ok ? 'PayMongo settings saved.' : (data.error || 'Failed.'));
}

// ---- Live attendance polling (ESP32 scans) ----
setInterval(async () => {
  if (!document.getElementById('attendanceTable')) return;
  try {
    const res = await fetch(BASE + '/api/attendance.php?action=latest');
    await res.json();
  } catch (err) { /* silent */ }
}, 8000);

// ---- ?status=Late|Present|Absent|Excused (linked from the dashboard cards) ----
(function () {
  const wanted = new URLSearchParams(location.search).get('status');
  if (!wanted || !STATUS_CLASS[wanted]) return;
  const rows = document.querySelectorAll('.student-row');
  if (!rows.length) return;
  rows.forEach((row) => { if (row.dataset.status !== wanted) row.style.display = 'none'; });
})();
