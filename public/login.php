<?php
require __DIR__ . '/../bootstrap.php';
use App\Core\Auth;
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (Auth::login($email, $password)) {
    header('Location: index.php');
    exit;
  } else {
    $error = 'Credenciais inválidas';
  }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login • Casinha Finance</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#C65D3B' },
            imperial_blue: { DEFAULT: '#0a2463', 100: '#020713', 200: '#040e27', 300: '#06163a', 400: '#081d4e', 500: '#0a2463', 600: '#113fab', 700: '#235ee9', 800: '#6c94f0', 900: '#b6c9f8' },
            blue_bell: { DEFAULT: '#3e92cc', 100: '#0b1d2a', 200: '#163a54', 300: '#21577e', 400: '#2d74a8', 500: '#3e92cc', 600: '#64a7d6', 700: '#8bbde0', 800: '#b2d3ea', 900: '#d8e9f5' },
            ghost_white: { DEFAULT: '#fffaff', 100: '#650065', 200: '#ca00ca', 300: '#ff30ff', 400: '#ff95ff', 500: '#fffaff', 600: '#fffbff', 700: '#fffcff', 800: '#fffdff', 900: '#fffeff' },
            magenta_bloom: { DEFAULT: '#d8315b', 100: '#2d0812', 200: '#591123', 300: '#861935', 400: '#b22246', 500: '#d8315b', 600: '#e05a7c', 700: '#e7849d', 800: '#efadbd', 900: '#f7d6de' },
            carbon_black: { DEFAULT: '#1e1b18', 100: '#060605', 200: '#0c0b0a', 300: '#13110f', 400: '#191614', 500: '#1e1b18', 600: '#524941', 700: '#84776a', 800: '#aea49a', 900: '#d7d2cd' }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-ghost_white-600 min-h-screen">
  <div class="grid md:grid-cols-4 h-screen">
    <div class="hidden md:block md:col-span-3 relative bg-brand">
      <?php $bg = getenv('LOGIN_BG_URL') ?: 'https://cdn6.campograndenews.com.br/uploads/noticias/2025/11/18/d5a6f582aed3f03556f21b1d644961cd6b699cb1.png'; ?>
      <img src="<?php echo htmlspecialchars($bg); ?>" alt="" class="absolute inset-0 w-full h-full object-cover" style="opacity:0.15" />
      <div class="absolute inset-0 bg-gradient-to-t from-black/10 to-black/10"></div>
      <div class="relative p-8 text-ghost_white-600">
        <div class="text-3xl font-semibold">Casinha Finance</div>
        <div class="mt-2 text-sm">Gestão financeira simples e elegante</div>
      </div>
    </div>
    <div class="col-span-1 flex items-center justify-center bg-white shadow md:rounded-l-xl">
      <div class="w-full max-w-sm p-6">
        <div class="flex justify-center mb-4">
          <img src="assets/logo-casinha.png" alt="Casinha Bar" class="h-20 object-contain" />
        </div>
        <div class="text-2xl font-semibold text-brand mb-4">Entrar</div>
        <form method="post" action="login.php" class="space-y-4">
          <?php if (!empty($error)) { echo '<div class="text-brand text-sm">' . htmlspecialchars($error) . '</div>'; } ?>
          <div>
            <label class="block text-sm mb-1">E-mail</label>
            <input name="email" type="email" required class="w-full form-input" />
          </div>
          <div>
            <label class="block text-sm mb-1">Senha</label>
            <input name="password" type="password" required class="w-full form-input" />
          </div>
          <button class="bg-brand hover:bg-brand/90 transition text-white w-full py-2 rounded">Entrar</button>
        </form>
        <div class="mt-4 text-center text-sm text-carbon_black-600">Casinha Finance</div>
      </div>
    </div>
  </div>
</body>
</html>

