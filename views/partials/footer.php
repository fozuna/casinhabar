<?php $version = getenv('APP_VERSION') ?: '0.1.0'; ?>
<footer class="fixed bottom-0 left-0 right-0 bg-ghost_white-700 border-t border-carbon_black-300 text-carbon_black-700 z-10">
  <div class="ml-0 md:ml-64">
    <div class="max-w-7xl mx-auto px-6 py-3 flex flex-col md:flex-row items-center justify-between gap-2">
      <div class="text-sm">Traxter - Sistemas e Automações</div>
      <div class="text-sm opacity-90">Versão <?php echo htmlspecialchars($version); ?> • © <?php echo date('Y'); ?> Todos os direitos reservados</div>
    </div>
  </div>
</footer>
