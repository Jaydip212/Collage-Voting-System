// ============================================================
// COLLEGE VOTING SYSTEM – MAIN JS
// assets/js/main.js
// ============================================================

// ── DARK MODE ─────────────────────────────────────────────
const themeToggle = document.getElementById('themeToggle');
const savedTheme  = localStorage.getItem('cvs-theme') || 'dark';

document.documentElement.setAttribute('data-theme', savedTheme);
if (themeToggle) {
  themeToggle.classList.toggle('active', savedTheme === 'light');
  themeToggle.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('cvs-theme', next);
    themeToggle.classList.toggle('active', next === 'light');
  });
}

// ── MOBILE SIDEBAR TOGGLE ─────────────────────────────────
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');

if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('d-none');
  });
}
if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.add('d-none');
  });
}

// ── COUNTDOWN TIMERS ──────────────────────────────────────
function startCountdown(element, endDatetime) {
  const endTime = new Date(endDatetime).getTime();
  const tick = () => {
    const now = Date.now();
    const diff = endTime - now;
    if (diff <= 0) {
      element.innerHTML = '<span class="badge badge-danger">Ended</span>';
      return;
    }
    const days    = Math.floor(diff / 86400000);
    const hours   = Math.floor((diff % 86400000) / 3600000);
    const minutes = Math.floor((diff % 3600000) / 60000);
    const seconds = Math.floor((diff % 60000) / 1000);

    element.innerHTML = `
      <div class="countdown-grid">
        <div class="countdown-item"><div class="countdown-val">${String(days).padStart(2,'0')}</div><div class="countdown-unit">Days</div></div>
        <div class="countdown-item"><div class="countdown-val">${String(hours).padStart(2,'0')}</div><div class="countdown-unit">Hrs</div></div>
        <div class="countdown-item"><div class="countdown-val">${String(minutes).padStart(2,'0')}</div><div class="countdown-unit">Min</div></div>
        <div class="countdown-item"><div class="countdown-val">${String(seconds).padStart(2,'0')}</div><div class="countdown-unit">Sec</div></div>
      </div>`;
  };
  tick();
  setInterval(tick, 1000);
}

document.querySelectorAll('[data-countdown]').forEach(el => {
  startCountdown(el, el.getAttribute('data-countdown'));
});

// ── OTP AUTO-TAB ─────────────────────────────────────────
document.querySelectorAll('.otp-input').forEach((input, i, inputs) => {
  input.addEventListener('input', e => {
    if (e.target.value.length === 1 && i < inputs.length - 1) {
      inputs[i + 1].focus();
    }
    // Combine OTP into hidden field
    const otpVal = Array.from(inputs).map(inp => inp.value).join('');
    const hidden = document.getElementById('otpHidden');
    if (hidden) hidden.value = otpVal;
  });
  input.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !e.target.value && i > 0) {
      inputs[i - 1].focus();
    }
  });
  input.addEventListener('paste', e => {
    e.preventDefault();
    const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
    [...text].forEach((ch, idx) => { if (inputs[idx]) inputs[idx].value = ch; });
    if (inputs[Math.min(text.length, inputs.length-1)]) inputs[Math.min(text.length, inputs.length-1)].focus();
    const otpVal = Array.from(inputs).map(inp => inp.value).join('');
    const hidden = document.getElementById('otpHidden');
    if (hidden) hidden.value = otpVal;
  });
});

// ── CANDIDATE SELECTION ───────────────────────────────────
function setupCandidateSelection() {
  const cards    = document.querySelectorAll('.candidate-card[data-id]');
  const voteBtn  = document.getElementById('voteSubmitBtn');
  const hiddenId = document.getElementById('selectedCandidate');

  cards.forEach(card => {
    card.addEventListener('click', () => {
      cards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      if (hiddenId) hiddenId.value = card.getAttribute('data-id');
      if (voteBtn)  voteBtn.disabled = false;
    });
  });
}
setupCandidateSelection();

// ── MODALS ────────────────────────────────────────────────
function openModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'flex';
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.style.display = 'none';
}

document.querySelectorAll('[data-modal-open]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.getAttribute('data-modal-open')));
});

document.querySelectorAll('[data-modal-close]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.getAttribute('data-modal-close')));
});

document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.style.display = 'none';
  });
});

// ── AUTO-DISMISS ALERTS ───────────────────────────────────
document.querySelectorAll('.alert:not(.alert-demo)').forEach(alert => {
  setTimeout(() => {
    alert.style.transition = 'opacity 0.5s ease';
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 500);
  }, 5000);
});

// ── CONFIRM ACTIONS ───────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  });
});

// ── PROGRESS BAR ANIMATION ────────────────────────────────
function animateProgressBars() {
  document.querySelectorAll('.progress-bar-fill[data-pct]').forEach(bar => {
    const pct = bar.getAttribute('data-pct');
    requestAnimationFrame(() => {
      bar.style.width = '0%';
      setTimeout(() => { bar.style.width = pct + '%'; }, 100);
    });
  });
}
animateProgressBars();

// ── NUMBER COUNTER ANIMATION ──────────────────────────────
function animateCounters() {
  document.querySelectorAll('[data-counter]').forEach(el => {
    const target = parseInt(el.getAttribute('data-counter'));
    let current = 0;
    const step = Math.ceil(target / 60);
    const timer = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = current.toLocaleString('en-IN');
      if (current >= target) clearInterval(timer);
    }, 16);
  });
}

const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      animateCounters();
      counterObserver.disconnect();
    }
  });
});

const statsGrid = document.querySelector('.stats-grid');
if (statsGrid) counterObserver.observe(statsGrid);

// ── REAL-TIME VOTE REFRESH (Results page) ─────────────────
function startResultRefresh(electionId) {
  const container = document.getElementById('liveResults');
  if (!container) return;

  const refresh = () => {
    fetch(`/api/results.php?election_id=${electionId}`)
      .then(r => r.json())
      .then(data => {
        if (data && data.candidates) {
          renderLeaderboard(data.candidates, container);
        }
      })
      .catch(() => {});
  };

  refresh();
  setInterval(refresh, 5000);
}

function renderLeaderboard(candidates, container) {
  const total = candidates.reduce((sum, c) => sum + c.total_votes, 0);
  container.innerHTML = candidates.map((c, idx) => {
    const pct = total > 0 ? Math.round((c.total_votes / total) * 100) : 0;
    const rankClass = idx === 0 ? 'rank-1' : idx === 1 ? 'rank-2' : idx === 2 ? 'rank-3' : 'rank-n';
    return `
      <div class="leaderboard-item">
        <div class="leaderboard-rank ${rankClass}">${idx+1}</div>
        <img class="leaderboard-photo" src="${c.photo}" alt="${c.name}" onerror="this.src='/assets/images/default-avatar.png'">
        <div class="leaderboard-info">
          <div class="leaderboard-name">${c.name}</div>
          <div class="leaderboard-dept">${c.dept || ''}</div>
          <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:${pct}%"></div></div>
        </div>
        <div class="leaderboard-votes">
          <div class="leaderboard-count">${c.total_votes}</div>
          <div class="leaderboard-label">${pct}% votes</div>
        </div>
      </div>`;
  }).join('');
}

// ── NOTIFICATIONS DROPDOWN ────────────────────────────────
const notifBtn = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');
if (notifBtn && notifDropdown) {
  notifBtn.addEventListener('click', e => {
    e.stopPropagation();
    notifDropdown.classList.toggle('d-none');
  });
  document.addEventListener('click', () => notifDropdown.classList.add('d-none'));
}

// ── TABLE SEARCH ──────────────────────────────────────────
const tableSearch = document.getElementById('tableSearch');
if (tableSearch) {
  tableSearch.addEventListener('input', () => {
    const query = tableSearch.value.toLowerCase();
    document.querySelectorAll('.searchable-row').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
  });
}

// ── MULTI-LANGUAGE (i18n) ────────────────────────────────
const i18n = {
  en: {
    vote: 'Cast Your Vote',
    result: 'Results',
    register: 'Register',
    login: 'Login',
    logout: 'Logout',
    elections: 'Elections',
    dashboard: 'Dashboard',
    welcome: 'Welcome',
    vote_success: 'Vote submitted successfully!',
    vote_confirm: 'Are you sure you want to vote for this candidate?',
    total_votes: 'Total Votes',
    active_elections: 'Active Elections',
  },
  mr: {
    vote: 'आपला मत द्या',
    result: 'निकाल',
    register: 'नोंदणी',
    login: 'लॉगिन',
    logout: 'लॉगआउट',
    elections: 'निवडणुका',
    dashboard: 'डॅशबोर्ड',
    welcome: 'स्वागत आहे',
    vote_success: 'मत यशस्वीपणे सादर केले!',
    vote_confirm: 'तुम्हाला या उमेदवाराला मत द्यायचे आहे का?',
    total_votes: 'एकूण मते',
    active_elections: 'सक्रिय निवडणुका',
  }
};

let currentLang = localStorage.getItem('cvs-lang') || 'en';

function applyLang(lang) {
  currentLang = lang;
  localStorage.setItem('cvs-lang', lang);
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (i18n[lang] && i18n[lang][key]) el.textContent = i18n[lang][key];
  });
  const langBtn = document.getElementById('langToggle');
  if (langBtn) langBtn.textContent = lang === 'en' ? 'मराठी' : 'English';
}

const langToggle = document.getElementById('langToggle');
if (langToggle) {
  applyLang(currentLang);
  langToggle.addEventListener('click', () => applyLang(currentLang === 'en' ? 'mr' : 'en'));
}

// ── FILE PREVIEW ──────────────────────────────────────────
document.querySelectorAll('.file-preview-input').forEach(input => {
  input.addEventListener('change', () => {
    const preview = document.querySelector(input.getAttribute('data-preview'));
    if (!preview || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; };
    reader.readAsDataURL(input.files[0]);
  });
});
