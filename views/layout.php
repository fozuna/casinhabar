<?php
require __DIR__ . '/partials/head.php';
?>
<body class="bg-ghost_white-600 min-h-screen">
  <div class="flex">
    <?php require __DIR__ . '/partials/nav.php'; ?>
    <main class="flex-1 px-3 md:px-6 py-4 md:py-6 pb-24 ml-0 md:ml-64">
      <?php echo $content ?? ''; ?>
    </main>
  </div>
  <?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>

