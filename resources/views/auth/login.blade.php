<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farm Manager Sign In — Blackswan Farms and General Enterprises</title>
  <meta name="theme-color" content="#10b981">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              emerald: '#10b981',
              cyan: '#06b6d4',
              amber: '#f59e0b',
              rose: '#f43f5e'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace']
          }
        }
      }
    };
    (function () {
      const saved = localStorage.getItem('aurapoultry_theme') || 'dark';
      document.documentElement.classList.toggle('dark', saved === 'dark');
    })();
  </script>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 font-sans antialiased flex items-center justify-center p-4 relative overflow-hidden">
  <!-- Full-Bleed Farm Background Image (Crisp & Clear) -->
  <div class="pointer-events-none fixed inset-0 overflow-hidden">
    <img src="/assets/images/poultry-house.jpg" alt="" class="w-full h-full object-cover object-center">
    <div class="absolute inset-0 bg-gradient-to-b from-slate-900/20 via-slate-900/15 to-slate-900/35 dark:from-slate-950/55 dark:via-slate-950/35 dark:to-slate-950/65"></div>
  </div>

  <!-- Theme Toggle -->
  <button type="button" onclick="toggleLoginTheme()" title="Toggle Light/Dark Mode"
          class="fixed top-5 right-5 z-20 w-10 h-10 rounded-xl glass-card flex items-center justify-center text-slate-700 dark:text-slate-200 hover:text-emerald-500 transition cursor-pointer">
    <i id="loginThemeIcon" class="fa-solid fa-sun text-sm"></i>
  </button>

  <main class="relative z-10 w-full max-w-md">
    <div class="glass-card rounded-3xl p-7 sm:p-8 shadow-2xl border border-white/50 dark:border-slate-700/70 bg-white/80 dark:bg-slate-900/75 backdrop-blur-xl">
      <!-- Brand Header -->
      <div class="flex flex-col items-center text-center mb-7">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-cyan-500 flex items-center justify-center shadow-lg shadow-emerald-500/25 mb-3.5">
          <i class="fa-solid fa-feather-pointed text-slate-950 text-2xl"></i>
        </div>
        <h1 class="text-lg sm:text-xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-snug">Blackswan Farms and General Enterprises</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Farm Manager Sign In</p>
      </div>

      @if (!empty($loginError))
        <div class="mb-5 rounded-xl bg-rose-500/10 border border-rose-500/30 px-3.5 py-2.5 flex items-center gap-2.5 text-xs text-rose-600 dark:text-rose-300">
          <i class="fa-solid fa-circle-exclamation shrink-0"></i>
          <span>{{ $loginError }}</span>
        </div>
      @endif

      @if (!empty($loginSuccess))
        <div class="mb-5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 flex items-center gap-2.5 text-xs text-emerald-700 dark:text-emerald-300">
          <i class="fa-solid fa-circle-check shrink-0"></i>
          <span>{{ $loginSuccess }}</span>
        </div>
      @endif

      <form method="POST" action="/" class="space-y-4">
        <input type="hidden" name="action" value="login">

        <div>
          <label for="username" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400">
              <i class="fa-solid fa-user text-xs"></i>
            </span>
            <input id="username" type="text" name="username" required autofocus autocomplete="username"
                   placeholder="manager"
                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-900/80 text-slate-900 dark:text-white pl-10 pr-3.5 py-2.5 text-sm focus:border-emerald-500 focus:outline-none transition">
          </div>
        </div>

        <div>
          <label for="password" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-3.5 flex items-center text-slate-400">
              <i class="fa-solid fa-lock text-xs"></i>
            </span>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-900/80 text-slate-900 dark:text-white pl-10 pr-10 py-2.5 text-sm focus:border-emerald-500 focus:outline-none transition">
            <button type="button" onclick="togglePasswordVisibility()" tabindex="-1"
                    class="absolute inset-y-0 right-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer">
              <i id="pwdEyeIcon" class="fa-solid fa-eye text-xs"></i>
            </button>
          </div>
        </div>

        <button type="submit"
                class="w-full mt-2 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-sm font-bold shadow-lg shadow-emerald-500/25 transition cursor-pointer active:scale-[0.99]">
          Sign In to Dashboard
        </button>
      </form>
    </div>
  </main>

  <script>
    function syncLoginThemeIcon() {
      const isDark = document.documentElement.classList.contains('dark');
      const icon = document.getElementById('loginThemeIcon');
      if (icon) {
        icon.className = isDark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm';
      }
    }
    function toggleLoginTheme() {
      const isDark = document.documentElement.classList.toggle('dark');
      localStorage.setItem('aurapoultry_theme', isDark ? 'dark' : 'light');
      syncLoginThemeIcon();
    }
    function togglePasswordVisibility() {
      const input = document.getElementById('password');
      const icon = document.getElementById('pwdEyeIcon');
      if (!input || !icon) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.className = show ? 'fa-solid fa-eye-slash text-xs' : 'fa-solid fa-eye text-xs';
    }
    syncLoginThemeIcon();
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
  </script>
</body>
</html>

