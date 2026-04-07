<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$success = '';
$error   = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $role    = trim($_POST['role']    ?? '');

    if (!$name || !$email || !$subject || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // In production: send email via PHPMailer / SMTP
        // For demo: just show success
        $success = "Thank you, <strong>$name</strong>! Your message has been received. We will get back to you at <strong>$email</strong> within 24 hours.";
    }
}

$pageTitle = 'Contact Us';
$pageDesc  = 'Get in touch with the ABC College of Engineering Voting System support team.';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ── PAGE HERO ────────────────────────────────────────────── -->
<section style="padding:120px 5% 60px;position:relative;z-index:1;text-align:center">
  <div style="max-width:680px;margin:0 auto">
    <div class="hero-badge animate-fadeIn" style="display:inline-flex;margin-bottom:20px">
      <i class="fas fa-envelope" style="color:var(--accent)"></i>
      <span>We're here to help</span>
    </div>
    <h1 style="margin-bottom:16px">Contact <span class="text-gradient">Us</span></h1>
    <p style="color:var(--text-secondary);font-size:1rem;line-height:1.8">
      Have questions about voting, registration, or the system? 
      Reach out to us and we'll respond as quickly as possible.
    </p>
  </div>
</section>

<!-- ── MAIN CONTENT ──────────────────────────────────────────── -->
<section style="padding:0 5% 100px;position:relative;z-index:1">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:36px;align-items:start;max-width:1100px;margin:0 auto">

      <!-- ── LEFT: Contact Info Cards ── -->
      <div style="display:flex;flex-direction:column;gap:18px">

        <div class="glass-card" style="padding:24px;border-left:4px solid var(--primary)">
          <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(79,70,229,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
              <i class="fas fa-map-marker-alt" style="color:var(--primary)"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:4px">Address</div>
              <div style="font-size:0.9rem;font-weight:600">College Road, City – 400001</div>
              <div style="font-size:0.78rem;color:var(--text-muted)">Maharashtra, India</div>
            </div>
          </div>
        </div>

        <div class="glass-card" style="padding:24px;border-left:4px solid var(--accent)">
          <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(6,182,212,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
              <i class="fas fa-envelope" style="color:var(--accent)"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:4px">Email</div>
              <div style="font-size:0.9rem;font-weight:600">admin@college.edu</div>
              <div style="font-size:0.78rem;color:var(--text-muted)">voting-support@college.edu</div>
            </div>
          </div>
        </div>

        <div class="glass-card" style="padding:24px;border-left:4px solid var(--success)">
          <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(16,185,129,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
              <i class="fas fa-phone" style="color:var(--success)"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:4px">Phone</div>
              <div style="font-size:0.9rem;font-weight:600">+91 98765 43210</div>
              <div style="font-size:0.78rem;color:var(--text-muted)">Mon–Sat, 9 AM – 5 PM</div>
            </div>
          </div>
        </div>

        <div class="glass-card" style="padding:24px;border-left:4px solid var(--warning)">
          <div style="display:flex;align-items:center;gap:14px">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(245,158,11,0.15);display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0">
              <i class="fas fa-clock" style="color:var(--warning)"></i>
            </div>
            <div>
              <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-muted);margin-bottom:4px">Support Hours</div>
              <div style="font-size:0.9rem;font-weight:600">Mon – Fri: 9 AM – 6 PM</div>
              <div style="font-size:0.78rem;color:var(--text-muted)">Saturday: 9 AM – 1 PM</div>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="glass-card" style="padding:24px">
          <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:14px">Quick Help</div>
          <?php
          $quickLinks = [
            ['fas fa-sign-in-alt',  'Login Issues',         'login.php',           'var(--primary)'],
            ['fas fa-user-plus',    'Registration Help',    'register.php',        'var(--accent)'],
            ['fas fa-info-circle',  'About the System',     'about.php',           'var(--success)'],
            ['fas fa-shield-alt',   'Admin Panel',          'login.php?role=admin','var(--warning)'],
          ];
          foreach ($quickLinks as [$icon, $label, $href, $color]): ?>
            <a href="<?= BASE_URL ?>/<?= $href ?>" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;text-decoration:none;transition:background 0.2s;margin-bottom:4px"
               onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
              <i class="<?= $icon ?>" style="color:<?= $color ?>;width:16px;text-align:center"></i>
              <span style="font-size:0.85rem;color:var(--text-secondary)"><?= $label ?></span>
              <i class="fas fa-chevron-right" style="margin-left:auto;font-size:0.65rem;color:var(--text-muted)"></i>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- ── RIGHT: Contact Form ── -->
      <div class="glass-card" style="padding:36px">
        <h3 style="margin-bottom:6px">Send us a <span class="text-gradient">Message</span></h3>
        <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:28px">Fill in the form below and we'll get back to you within 24 hours.</p>

        <?php if ($success): ?>
          <div style="background:rgba(16,185,129,0.12);border:1px solid var(--success);border-radius:10px;padding:18px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px">
            <i class="fas fa-check-circle" style="color:var(--success);font-size:1.2rem;margin-top:2px"></i>
            <div style="font-size:0.88rem;color:var(--success);line-height:1.6"><?= $success ?></div>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div style="background:rgba(239,68,68,0.12);border:1px solid var(--danger);border-radius:10px;padding:18px;margin-bottom:24px;display:flex;align-items:flex-start;gap:12px">
            <i class="fas fa-exclamation-circle" style="color:var(--danger);font-size:1.2rem;margin-top:2px"></i>
            <div style="font-size:0.88rem;color:var(--danger)"><?= htmlspecialchars($error) ?></div>
          </div>
        <?php endif; ?>

        <form method="POST" action="" id="contactForm" novalidate>

          <!-- Name + Role row -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
            <div>
              <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:6px">
                Full Name <span style="color:var(--danger)">*</span>
              </label>
              <input type="text" name="name" id="contact_name" placeholder="Your full name"
                     value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                     style="width:100%;padding:11px 14px;border-radius:8px;border:1px solid var(--border);background:var(--bg-glass);color:var(--text-primary);font-size:0.88rem;outline:none;transition:border-color 0.2s;box-sizing:border-box"
                     onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'" required>
            </div>
            <div>
              <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:6px">Your Role</label>
              <select name="role" id="contact_role"
                      style="width:100%;padding:11px 14px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--text-primary);font-size:0.88rem;outline:none;transition:border-color 0.2s;box-sizing:border-box"
                      onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'">
                <option value="">Select role...</option>
                <option value="student"  <?= ($_POST['role']??'')==='student'  ?'selected':'' ?>>🎓 Student</option>
                <option value="teacher"  <?= ($_POST['role']??'')==='teacher'  ?'selected':'' ?>>👩‍🏫 Teacher</option>
                <option value="hod"      <?= ($_POST['role']??'')==='hod'      ?'selected':'' ?>>🏛️ HOD</option>
                <option value="other"    <?= ($_POST['role']??'')==='other'    ?'selected':'' ?>>👤 Other</option>
              </select>
            </div>
          </div>

          <!-- Email -->
          <div style="margin-bottom:16px">
            <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:6px">
              Email Address <span style="color:var(--danger)">*</span>
            </label>
            <input type="email" name="email" id="contact_email" placeholder="your@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   style="width:100%;padding:11px 14px;border-radius:8px;border:1px solid var(--border);background:var(--bg-glass);color:var(--text-primary);font-size:0.88rem;outline:none;transition:border-color 0.2s;box-sizing:border-box"
                   onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'" required>
          </div>

          <!-- Subject -->
          <div style="margin-bottom:16px">
            <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:6px">
              Subject <span style="color:var(--danger)">*</span>
            </label>
            <select name="subject" id="contact_subject"
                    style="width:100%;padding:11px 14px;border-radius:8px;border:1px solid var(--border);background:var(--bg-card);color:var(--text-primary);font-size:0.88rem;outline:none;transition:border-color 0.2s;box-sizing:border-box"
                    onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'" required>
              <option value="">Select a topic...</option>
              <option value="Login / Password Issue"      <?= ($_POST['subject']??'')==='Login / Password Issue'      ? 'selected':'' ?>>🔑 Login / Password Issue</option>
              <option value="Registration Problem"        <?= ($_POST['subject']??'')==='Registration Problem'        ? 'selected':'' ?>>📝 Registration Problem</option>
              <option value="Voting Issue"                <?= ($_POST['subject']??'')==='Voting Issue'                ? 'selected':'' ?>>🗳️ Voting Issue</option>
              <option value="Result / Certificate"        <?= ($_POST['subject']??'')==='Result / Certificate'        ? 'selected':'' ?>>🏆 Result / Certificate</option>
              <option value="Technical Bug"               <?= ($_POST['subject']??'')==='Technical Bug'               ? 'selected':'' ?>>🐛 Technical Bug</option>
              <option value="Feature Request"             <?= ($_POST['subject']??'')==='Feature Request'             ? 'selected':'' ?>>💡 Feature Request</option>
              <option value="Other"                       <?= ($_POST['subject']??'')==='Other'                       ? 'selected':'' ?>>📌 Other</option>
            </select>
          </div>

          <!-- Message -->
          <div style="margin-bottom:24px">
            <label style="font-size:0.78rem;font-weight:600;color:var(--text-secondary);display:block;margin-bottom:6px">
              Message <span style="color:var(--danger)">*</span>
            </label>
            <textarea name="message" id="contact_message" rows="5" placeholder="Describe your issue or question in detail..."
                      style="width:100%;padding:11px 14px;border-radius:8px;border:1px solid var(--border);background:var(--bg-glass);color:var(--text-primary);font-size:0.88rem;outline:none;transition:border-color 0.2s;resize:vertical;box-sizing:border-box;font-family:inherit"
                      onfocus="this.style.borderColor='var(--primary)'" onblur="this.style.borderColor='var(--border)'" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px">Minimum 20 characters</div>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;padding:13px;font-size:0.95rem;justify-content:center" id="contactSubmitBtn">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>

          <p style="font-size:0.75rem;color:var(--text-muted);text-align:center;margin-top:14px">
            <i class="fas fa-lock"></i> Your information is kept private and never shared.
          </p>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Simple form validation -->
<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
  const msg = document.getElementById('contact_message').value.trim();
  if (msg.length < 20) {
    e.preventDefault();
    document.getElementById('contact_message').style.borderColor = 'var(--danger)';
    document.getElementById('contact_message').focus();
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
