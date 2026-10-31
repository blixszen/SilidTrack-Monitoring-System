// ===================== SilidTrack Student JS =====================

function tickClock() {
  const el = document.getElementById('phTime');
  if (!el) return;
  const now = new Date();
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

function renderCalendar() {
  const grid = document.getElementById('calStrip');
  const monthEl = document.getElementById('calMonth');
  if (!grid) return;
  const now = new Date();
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

// ---- Student Dashboard Stats Live Update ----
async function updateStudentStats() {
  if (!document.getElementById('statTotal')) return;
  try {
    const res = await fetch(window.APP_BASE + '/api/student_stats.php');
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
setInterval(updateStudentStats, 8000);

// ---- Profile dropdown: Change Password ----
document.getElementById('changePasswordLink')?.addEventListener('click', async (e) => {
  e.preventDefault();
  const current = prompt('Current password:');
  if (current === null) return;
  const next = prompt('New password:');
  if (!next) return;
  const fd = new FormData();
  fd.append('current_password', current);
  fd.append('new_password', next);
  const res = await fetch(window.APP_BASE + '/api/change_password.php', { method: 'POST', body: fd });
  const data = await res.json();
  alert(data.ok ? 'Password changed.' : (data.error || 'Failed to change password.'));
});

document.getElementById('editPhotoLink')?.addEventListener('click', (e) => {
  e.preventDefault();
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/*';
  input.onchange = async () => {
    if (!input.files.length) return;
    const fd = new FormData();
    fd.append('photo', input.files[0]);
    const res = await fetch(window.APP_BASE + '/api/update_photo.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) location.reload(); else alert(data.error || 'Failed to update photo.');
  };
  input.click();
});
