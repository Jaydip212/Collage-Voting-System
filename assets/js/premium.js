// ============================================================
// COLLEGE VOTING SYSTEM – PREMIUM JS v2.0
// assets/js/premium.js
// Ultra-premium: Particles, scroll animations, glow effects
// ============================================================

// ── PARTICLE SYSTEM ──────────────────────────────────────────
(function initParticles() {
  const canvas = document.getElementById('particles-canvas');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  let W = canvas.width  = window.innerWidth;
  let H = canvas.height = window.innerHeight;

  window.addEventListener('resize', () => {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  });

  const COLORS = ['#4f46e5', '#6366f1', '#818cf8', '#06b6d4', '#22d3ee', '#a78bfa'];
  const NUM    = Math.min(Math.floor(W * H / 18000), 55);

  const particles = Array.from({ length: NUM }, () => ({
    x:    Math.random() * W,
    y:    Math.random() * H,
    r:    Math.random() * 1.8 + 0.4,
    vx:   (Math.random() - 0.5) * 0.35,
    vy:   (Math.random() - 0.5) * 0.35,
    col:  COLORS[Math.floor(Math.random() * COLORS.length)],
    alpha: Math.random() * 0.5 + 0.15,
  }));

  function draw() {
    ctx.clearRect(0, 0, W, H);

    // Draw connections
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 130) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.strokeStyle = `rgba(99,102,241,${0.12 * (1 - dist / 130)})`;
          ctx.lineWidth = 0.6;
          ctx.stroke();
        }
      }
    }

    // Draw particles
    particles.forEach(p => {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
      ctx.fillStyle = p.col + Math.round(p.alpha * 255).toString(16).padStart(2,'0');
      ctx.fill();

      // Move
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < -10) p.x = W + 10;
      if (p.x > W + 10) p.x = -10;
      if (p.y < -10) p.y = H + 10;
      if (p.y > H + 10) p.y = -10;
    });

    requestAnimationFrame(draw);
  }

  draw();
})();

// ── SCROLL REVEAL ANIMATIONS ──────────────────────────────────
(function initScrollReveal() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

  // Add section-fade class to target elements
  document.querySelectorAll(
    'section > .container > *:not(:first-child), .glass-card, .stat-card, .election-card, .candidate-card'
  ).forEach(el => {
    if (!el.classList.contains('section-fade')) {
      el.classList.add('section-fade');
      observer.observe(el);
    }
  });
})();

// ── NAVBAR SCROLL EFFECT ──────────────────────────────────────
(function initNavbarScroll() {
  const navbar = document.getElementById('navbar');
  if (!navbar) return;
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar.style.boxShadow = '0 4px 40px rgba(0,0,0,0.5)';
      navbar.style.background = 'rgba(8, 8, 20, 0.95)';
    } else {
      navbar.style.boxShadow = '';
      navbar.style.background = '';
    }
  }, { passive: true });
})();

// ── CURSOR GLOW EFFECT ────────────────────────────────────────
(function initCursorGlow() {
  const glow = document.createElement('div');
  glow.style.cssText = `
    position: fixed;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(79,70,229,0.06) 0%, transparent 70%);
    pointer-events: none;
    z-index: 1;
    transform: translate(-50%, -50%);
    transition: opacity 0.3s ease;
  `;
  document.body.appendChild(glow);

  document.addEventListener('mousemove', (e) => {
    glow.style.left = e.clientX + 'px';
    glow.style.top  = e.clientY + 'px';
  }, { passive: true });

  document.addEventListener('mouseleave', () => { glow.style.opacity = '0'; });
  document.addEventListener('mouseenter',  () => { glow.style.opacity = '1'; });
})();

// ── COUNTER ANIMATION (Enhanced) ─────────────────────────────
(function enhanceCounters() {
  const counters = document.querySelectorAll('[data-counter]');
  if (!counters.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el     = entry.target;
      const target = parseInt(el.getAttribute('data-counter')) || 0;
      const duration = 1800;
      const start = performance.now();

      const tick = (now) => {
        const elapsed  = now - start;
        const progress = Math.min(elapsed / duration, 1);
        // Easing: easeOutExpo
        const eased = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
        el.textContent = Math.round(eased * target).toLocaleString('en-IN');
        if (progress < 1) requestAnimationFrame(tick);
      };

      requestAnimationFrame(tick);
      observer.unobserve(el);
    });
  }, { threshold: 0.3 });

  counters.forEach(el => observer.observe(el));
})();

// ── TYPING EFFECT FOR HERO H1 ─────────────────────────────────
(function initTypingEffect() {
  const heroText = document.querySelector('.hero h1');
  if (!heroText || heroText.getAttribute('data-typed')) return;
  // Only add subtle typewriter cursor glow
  heroText.style.textShadow = '0 0 80px rgba(79,70,229,0.2)';
})();

// ── GLASS CARD TILT EFFECT (subtle 3D) ───────────────────────
(function initCardTilt() {
  document.querySelectorAll('.glass-card, .stat-card, .election-card').forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect  = card.getBoundingClientRect();
      const x     = (e.clientX - rect.left) / rect.width  - 0.5;
      const y     = (e.clientY - rect.top)  / rect.height - 0.5;
      const tiltX = y * -6;
      const tiltY = x *  6;
      card.style.transform = `perspective(800px) rotateX(${tiltX}deg) rotateY(${tiltY}deg) translateY(-4px)`;
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = '';
      card.style.transition = 'transform 0.4s ease';
    });

    card.addEventListener('mouseenter', () => {
      card.style.transition = 'transform 0.1s ease';
    });
  });
})();

// ── BUTTON RIPPLE EFFECT ──────────────────────────────────────
(function initRipple() {
  document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      const rect   = this.getBoundingClientRect();
      const size   = Math.max(rect.width, rect.height) * 2;
      ripple.style.cssText = `
        position: absolute;
        width: ${size}px; height: ${size}px;
        left: ${e.clientX - rect.left - size/2}px;
        top: ${e.clientY - rect.top  - size/2}px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        transform: scale(0);
        animation: rippleAnim 0.6s ease-out forwards;
        pointer-events: none;
      `;

      if (!document.querySelector('#ripple-style')) {
        const style = document.createElement('style');
        style.id = 'ripple-style';
        style.textContent = `
          @keyframes rippleAnim {
            to { transform: scale(1); opacity: 0; }
          }
        `;
        document.head.appendChild(style);
      }

      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 700);
    });
  });
})();

// ── LIVE VOTE BADGE PULSE ─────────────────────────────────────
(function pulseLiveBadges() {
  document.querySelectorAll('.badge-success').forEach(badge => {
    if (badge.textContent.includes('LIVE')) {
      badge.style.animation = 'liveBadge 2s ease-in-out infinite';
      if (!document.querySelector('#live-badge-style')) {
        const s = document.createElement('style');
        s.id = 'live-badge-style';
        s.textContent = `
          @keyframes liveBadge {
            0%, 100% { box-shadow: 0 0 8px rgba(16,185,129,0.3); }
            50%       { box-shadow: 0 0 20px rgba(16,185,129,0.6); }
          }
        `;
        document.head.appendChild(s);
      }
    }
  });
})();

// ── PAGE LOAD ANIMATION ───────────────────────────────────────
(function initPageLoad() {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.5s ease';
  window.addEventListener('load', () => {
    document.body.style.opacity = '1';
  });
  // Fallback
  setTimeout(() => { document.body.style.opacity = '1'; }, 800);
})();

console.log('%c🗳️ HVD College Voting System — Premium UI v2.0', 'color:#818cf8;font-size:1rem;font-weight:bold;');
