// ===================== SilidTrack Dashboard JS (student + admin) =====================
// Week-strip attendance calendar, "View All" modal, live date label.
(function () {
  const BASE = window.APP_BASE || '';
  const strip = document.getElementById('wkStrip');

  // ---- helpers ----
  const pad = (n) => String(n).padStart(2, '0');
  const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
  const mondayOf = (d) => addDays(d, -((d.getDay() + 6) % 7));
  const DOW = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const longDate = (d) => d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
  const STATUS = {
    Present: { cls: 'present', msg: 'You are marked present.', icon: 'M20 6 9 17l-5-5' },
    Late:    { cls: 'late',    msg: 'You were marked late.',   icon: 'M12 7v5l3 2' },
    Absent:  { cls: 'absent',  msg: 'You are marked absent.',  icon: 'M18 6 6 18M6 6l12 12' },
    Excused: { cls: 'excused', msg: 'You are excused for this day.', icon: 'M20 6 9 17l-5-5' },
  };

  // ---- live date + greeting (follows the viewer's own clock) ----
  const todayLabel = document.getElementById('todayLabel');
  function refreshToday() { if (todayLabel) todayLabel.textContent = longDate(new Date()); }
  refreshToday();
  setInterval(refreshToday, 60000);

  // ---- "View All announcements" modal (student) ----
  document.querySelectorAll('[data-open-modal]').forEach((btn) => {
    btn.addEventListener('click', () => document.getElementById(btn.dataset.openModal)?.classList.add('open'));
  });
  document.querySelectorAll('[data-close-modal]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('.modal-overlay')?.classList.remove('open'));
  });
  document.querySelectorAll('.d-modal').forEach((box) => {
    const overlay = box.closest('.modal-overlay');
    overlay?.addEventListener('click', (e) => { if (e.target === overlay) overlay.classList.remove('open'); });
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach((m) => m.classList.remove('open'));
  });

  if (!strip) return;

  // ---- calendar ----
  const role = document.querySelector('.d-cal')?.dataset.role || 'student';
  const monthEl = document.getElementById('calMonth');
  const detail = document.getElementById('dayDetail');
  const today = new Date();
  let weekStart = mondayOf(today);
  let selected = ymd(today);
  let data = {};           // date -> record(s), merged across loaded weeks
  let totalStudents = 0;

  async function loadWeek() {
    const from = ymd(weekStart), to = ymd(addDays(weekStart, 6));
    try {
      const res = await fetch(`${BASE}/api/calendar.php?from=${from}&to=${to}`, { credentials: 'same-origin' });
      const json = await res.json();
      if (json.ok) {
        // clear this week's stale entries, then merge fresh ones
        for (let i = 0; i < 7; i++) delete data[ymd(addDays(weekStart, i))];
        Object.assign(data, json.days);
        totalStudents = json.total_students || totalStudents;
      }
    } catch (e) { /* offline: render with what we have */ }
    render();
  }

  function dotClass(key) {
    const r = data[key];
    if (!r) return 'none';
    if (role === 'student') return (STATUS[r.status] || {}).cls || 'none';
    if (r.present + r.late > 0) return r.late > r.present ? 'late' : 'present';
    return r.absent > 0 ? 'absent' : (r.excused > 0 ? 'excused' : 'none');
  }

  function render() {
    const [sy, sm, sd] = selected.split('-').map(Number);
    monthEl.textContent = new Date(sy, sm - 1, sd).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

    strip.innerHTML = DOW.map((name, i) => {
      const d = addDays(weekStart, i), key = ymd(d);
      const isSel = key === selected, isToday = key === ymd(today);
      return `<button type="button" role="tab" aria-selected="${isSel}" class="d-day${isSel ? ' selected' : ''}${isToday ? ' is-today' : ''}" data-date="${key}">
        <span class="dow">${name}</span><span class="num">${d.getDate()}</span><span class="dot ${dotClass(key)}"></span></button>`;
    }).join('');
    renderDetail();
  }

  function renderDetail() {
    const [y, m, dd] = selected.split('-').map(Number);
    const d = new Date(y, m - 1, dd);
    const r = data[selected];
    const future = selected > ymd(today);
    let html;

    if (role === 'student') {
      if (r && STATUS[r.status]) {
        const s = STATUS[r.status];
        const time = r.scan_time ? ` Scanned at ${new Date('1970-01-01T' + r.scan_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}.` : '';
        html = `<span class="d-day-ico ${s.cls}"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="${s.icon}"/></svg></span>
          <div><strong>${longDate(d)}</strong><span>${s.msg}${time}</span></div><span class="badge badge-${s.cls}">${r.status}</span>`;
      } else {
        html = `<span class="d-day-ico none"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6h.01"/></svg></span>
          <div><strong>${longDate(d)}</strong><span>${future ? 'Upcoming day — nothing recorded yet.' : 'No attendance record for this day.'}</span></div>`;
      }
    } else if (r) {
      const att = r.present + r.late;
      html = `<span class="d-day-ico present"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20v-1a4.5 4.5 0 0 1 4.5-4.5h3A4.5 4.5 0 0 1 15 19v1"/></svg></span>
        <div><strong>${longDate(d)}</strong><span>${att} of ${totalStudents} students attended.</span></div>
        <span class="d-chips"><span class="badge badge-present">${r.present} Present</span><span class="badge badge-late">${r.late} Late</span><span class="badge badge-absent">${r.absent} Absent</span></span>`;
    } else {
      html = `<span class="d-day-ico none"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 7.6h.01"/></svg></span>
        <div><strong>${longDate(d)}</strong><span>${future ? 'Upcoming day — nothing recorded yet.' : 'No attendance was recorded on this day.'}</span></div>`;
    }
    detail.innerHTML = html;
  }

  strip.addEventListener('click', (e) => {
    const btn = e.target.closest('.d-day');
    if (!btn) return;
    selected = btn.dataset.date;
    render();
  });
  const shift = (days) => {
    weekStart = addDays(weekStart, days);
    selected = ymd(addDays(weekStart, Math.min((mondayOf(today).getTime() === weekStart.getTime()) ? (today.getDay() + 6) % 7 : 0, 6)));
    render();          // instant paint with cached data
    loadWeek();
  };
  document.getElementById('calPrev')?.addEventListener('click', () => shift(-7));
  document.getElementById('calNext')?.addEventListener('click', () => shift(7));

  render();
  loadWeek();
  setInterval(loadWeek, 30000);   // pick up new RFID scans

  // ---- admin: keep the "% in today" header figure live ----
  const rate = document.getElementById('attRate');
  if (rate && role === 'admin') {
    setInterval(async () => {
      try {
        const j = await (await fetch(BASE + '/api/dashboard_stats.php')).json();
        if (j.ok && j.total > 0) rate.textContent = Math.round(((j.present + j.late) / j.total) * 100) + '%';
      } catch (e) { /* silent */ }
    }, 8000);
  }
})();
