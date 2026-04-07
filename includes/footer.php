<?php
// includes/footer.php – Reusable page footer
?>

<!-- FOOTER -->
<footer style="background:rgba(15,15,26,0.95);border-top:1px solid rgba(255,255,255,0.08);padding:40px 5% 24px;margin-top:80px;position:relative;z-index:1">
  <div style="max-width:1200px;margin:0 auto">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:40px;margin-bottom:32px">
      <!-- Brand -->
      <div>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
          <div style="width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#06b6d4);display:flex;align-items:center;justify-content:center;font-size:1.1rem">🗳️</div>
          <div>
            <div style="font-weight:700;font-size:0.95rem;color:var(--text-primary)"><?= SITE_NAME ?></div>
            <div style="font-size:0.72rem;color:var(--text-muted)"><?= SITE_TAGLINE ?></div>
          </div>
        </div>
        <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.6">Secure, transparent, and modern digital voting for our college community. Exercise your democratic right responsibly.</p>
      </div>
      <!-- Quick Links -->
      <div>
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px">Quick Links</div>
        <?php foreach([['Home','index.php'],['About','about.php'],['Contact','contact.php'],['Elections','student/elections.php']] as [$label,$href]): ?>
          <a href="<?= BASE_URL ?>/<?= $href ?>" style="display:block;font-size:0.83rem;color:var(--text-secondary);padding:4px 0;"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
      <!-- Portals -->
      <div>
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px">Portals</div>
        <?php foreach([['Student Login','login.php?role=student'],['Teacher Login','login.php?role=teacher'],['HOD Login','login.php?role=hod'],['Admin Panel','login.php?role=admin']] as [$label,$href]): ?>
          <a href="<?= BASE_URL ?>/<?= $href ?>" style="display:block;font-size:0.83rem;color:var(--text-secondary);padding:4px 0;"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
      <!-- Contact -->
      <div>
        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:12px">Contact</div>
        <p style="font-size:0.82rem;color:var(--text-muted);line-height:1.7">
          <i class="fas fa-map-marker-alt" style="color:var(--primary-light);margin-right:6px"></i>College Road, City<br>
          <i class="fas fa-envelope" style="color:var(--primary-light);margin-right:6px"></i>admin@college.edu<br>
          <i class="fas fa-phone" style="color:var(--primary-light);margin-right:6px"></i>+91 98765 43210
        </p>
      </div>
    </div>
    <div style="border-top:1px solid rgba(255,255,255,0.08);padding-top:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
      <p style="font-size:0.78rem;color:var(--text-muted)">© <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
      <p style="font-size:0.78rem;color:var(--text-muted)">Built with ❤️ for Digital Democracy</p>
    </div>
  </div>
</footer>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- QR Code -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<!-- Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script src="<?= BASE_URL ?>/assets/js/premium.js"></script>
</body>
</html>
