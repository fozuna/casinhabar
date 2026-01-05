<?php $userName = $_SESSION['user_name'] ?? ''; ?>
<header class="md:hidden fixed top-0 left-0 right-0 z-20 bg-brand text-ghost_white-600">
  <div class="px-4 h-12 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l9 7-1.5 2L12 6 4.5 12 3 10l9-7z"/><path d="M5 13v7h5v-4h4v4h5v-7l-7-5-7 5z"/></svg>
      <span class="font-semibold">Casinha Finance</span>
    </div>
    <button id="btnMenu" class="inline-flex items-center justify-center w-10 h-10 rounded bg-brand">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/></svg>
    </button>
  </div>
  <div class="h-12"></div>
  <script>
    (function(){
      var btn=document.getElementById('btnMenu');
      var aside=document.getElementById('sidebar');
      var overlay=document.getElementById('overlay');
      function open(){aside.classList.remove('-translate-x-full');overlay.classList.remove('hidden');}
      function close(){aside.classList.add('-translate-x-full');overlay.classList.add('hidden');}
      if(btn){btn.addEventListener('click',open);} if(overlay){overlay.addEventListener('click',close);} document.addEventListener('keydown',function(e){if(e.key==='Escape') close();});
    })();
  </script>
</header>
<div id="overlay" class="hidden fixed inset-0 bg-black/40 z-10 md:hidden"></div>
<aside id="sidebar" class="fixed left-0 top-0 h-screen w-64 bg-brand text-ghost_white-600 shadow-lg md:flex md:flex-col z-20 transform -translate-x-full md:translate-x-0 transition-transform">
  <div class="px-4 py-4 border-b border-brand">
    <div class="text-lg font-semibold flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l9 7-1.5 2L12 6 4.5 12 3 10l9-7z"/><path d="M5 13v7h5v-4h4v4h5v-7l-7-5-7 5z"/></svg>
      Casinha Finance
    </div>
    <div class="text-xs mt-1 opacity-90">Olá, <?php echo htmlspecialchars($userName); ?></div>
  </div>
  <nav class="p-3 flex-1 overflow-y-auto">
    <a href="index.php?page=dashboard" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h7V3H3v10zm0 8h7v-6H3v6zm11 0h7V11h-7v10zm0-18v6h7V3h-7z"/></svg>
      <span>Dashboard</span>
    </a>
    <div class="mt-3 text-xs uppercase opacity-80 px-3">Cadastros</div>
    <a href="index.php?page=customers" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.7 0 4.9-2.2 4.9-4.9S14.7 2.2 12 2.2 7.1 4.4 7.1 7.1 9.3 12 12 12zm0 2.4c-3.3 0-9.8 1.7-9.8 5v2.4h19.6v-2.4c0-3.3-6.5-5-9.8-5z"/></svg>
      <span>Clientes</span>
    </a>
    <a href="index.php?page=suppliers" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l7 4-7 4-7-4 7-4zm0 10l7 4-7 4-7-4 7-4z"/></svg>
      <span>Fornecedores</span>
    </a>
    <div class="mt-3 text-xs uppercase opacity-80 px-3">Lançamentos</div>
    <a href="index.php?page=entries&dir=receita" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a11 11 0 100 22 11 11 0 000-22zm1 16.9v2.1h-2v-2.1a5.8 5.8 0 01-3.6-2l1.7-1.1a4 4 0 003.1 1.4c1.4 0 2.1-.6 2.1-1.5 0-.9-.8-1.3-2.4-1.8-2.1-.7-3.6-1.6-3.6-3.6 0-1.7 1.3-3 3.3-3.4V3.9h2v2.1a5.3 5.3 0 013.1 1.6l-1.5 1.2a3.6 3.6 0 00-2.9-1.2c-1.3 0-2 .6-2 1.4 0 .9.9 1.3 2.6 1.8 2 .6 3.5 1.6 3.5 3.6 0 1.8-1.3 3.1-3.4 3.5z"/></svg>
      <span>Receitas</span>
    </a>
    <a href="index.php?page=entries&dir=despesa" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 1a11 11 0 100 22 11 11 0 000-22zM7 7h10v2H7V7zm0 4h10v2H7v-2zm0 4h10v2H7v-2z"/></svg>
      <span>Despesas</span>
    </a>
    <div class="mt-3 text-xs uppercase opacity-80 px-3">Administração</div>
    <a href="index.php?page=cost-centers" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 6v-6h6v6h-6z"/></svg>
      <span>Centros de Custos</span>
    </a>
    <a href="index.php?page=users" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.7 0 3-1.3 3-3s-1.3-3-3-3-3 1.3-3 3 1.3 3 3 3zm-8 0c1.7 0 3-1.3 3-3S9.7 5 8 5s-3 1.3-3 3 1.3 3 3 3zm0 2c-2.7 0-8 1.3-8 4v2h10v-2c0-1.4.6-2.6 1.5-3.6-.9-.2-2-.4-3.5-.4zm8 0c-.6 0-1.1 0-1.6.1 1.2.9 2 2.3 2 3.9v2h8v-2c0-2.7-5.3-4-8.4-4z"/></svg>
      <span>Usuários</span>
    </a>
  </nav>
  <div class="p-3 border-t border-brand">
    <a class="flex items-center gap-3 px-3 py-2 rounded hover:bg-brand/80" href="logout.php">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M10 3H4a2 2 0 00-2 2v14a2 2 0 002 2h6v-2H4V5h6V3zm5.7 6.3L14.3 7l-5 5 5 5 1.4-1.4L12.4 12l3.3-2.7zM20 11h-8v2h8v-2z"/></svg>
      <span>Sair</span>
    </a>
  </div>
</aside>

