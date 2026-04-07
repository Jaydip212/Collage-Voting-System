<?php
// includes/dashboard_footer.php
?>
  </div><!-- end .page-content -->
</main><!-- end .main-content -->
</div><!-- end .layout-wrapper -->

<script>
// Show sidebar toggle on mobile
if (window.innerWidth <= 1024) {
  document.getElementById('sidebarToggle').style.display = 'flex';
}
window.addEventListener('resize', () => {
  const btn = document.getElementById('sidebarToggle');
  if (btn) btn.style.display = window.innerWidth <= 1024 ? 'flex' : 'none';
});
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
</body>
</html>
