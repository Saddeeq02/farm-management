<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Blackswan Farms and General Enterprises — Farm Management')</title>
  <meta name="theme-color" content="#10b981">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
  
  <!-- Google Fonts: Plus Jakarta Sans & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- FontAwesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            brand: {
              emerald: '#10b981',
              neonGreen: '#22c55e',
              amber: '#f59e0b',
              gold: '#fbbf24',
              cyan: '#06b6d4',
              rose: '#f43f5e',
              purple: '#a855f7'
            }
          },
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace']
          }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="/assets/css/app.css?v=3.5.4">
</head>
<body class="min-h-screen flex flex-col justify-between antialiased font-sans transition-colors duration-300 overflow-x-clip">

  <!-- Dynamic Backdrop Layers -->
  <div class="bg-canvas-layer"></div>
  <div class="bg-grid-overlay"></div>

  <!-- Animated Ambient Floating Glow Orbs (Subtle) -->
  <div class="ambient-orb w-96 h-96 bg-emerald-500/15 top-16 left-12"></div>
  <div class="ambient-orb w-[26rem] h-[26rem] bg-cyan-500/10 top-72 right-16" style="animation-delay: -5s;"></div>

  <!-- MAIN APP CONTAINER -->
  <div class="w-full max-w-full flex-grow flex flex-col relative z-10 overflow-x-clip">

    <!-- EXECUTIVE NAV & CONTROLS HEADER -->
    <header class="sticky top-0 z-40 lg:z-50 glass-header px-3.5 sm:px-4 lg:px-8 py-2.5 lg:py-3 flex items-center justify-between gap-2 sm:gap-4">
      
      <!-- Brand & Status -->
      <div class="flex items-center space-x-2.5 sm:space-x-3 min-w-0">
        <button id="sidebarToggleBtn" onclick="toggleSidebar()" class="lg:hidden w-9 h-9 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-900/70 text-slate-700 dark:text-slate-200 hover:bg-white dark:hover:bg-slate-800 transition-all cursor-pointer flex items-center justify-center shrink-0" title="Toggle Navigation Menu">
          <i class="fa-solid fa-bars text-sm"></i>
        </button>
        <a href="/?page=dashboard" class="flex items-center space-x-2.5 sm:space-x-3 group min-w-0">
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 flex items-center justify-center text-slate-950 font-black shadow-lg shadow-emerald-500/30 group-hover:scale-105 transition-transform shrink-0">
            <i class="fa-solid fa-feather-pointed text-sm sm:text-base"></i>
          </div>
          <div class="min-w-0">
            <div class="flex items-center space-x-2">
              <h1 class="text-base sm:text-xl font-extrabold tracking-tight truncate bg-clip-text text-transparent bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-500 dark:from-emerald-300 dark:via-teal-200 dark:to-cyan-200">
                Blackswan Farms <span class="hidden sm:inline text-xs font-mono px-2 py-0.5 rounded-full border border-emerald-500/40 bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 tracking-wider">&amp; General Enterprises</span>
              </h1>
              <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/50">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 mr-1.5 animate-pulse"></span> TELEMETRY ACTIVE
              </span>
            </div>
            <p class="hidden sm:block text-xs text-slate-600 dark:text-slate-300 font-medium drop-shadow-sm">Precision Hatchery & Lifecycle Financial Engine</p>
          </div>
        </a>
      </div>

      <!-- Action Cluster: Theme Toggle, Reminders (Mobile + Desktop), Batch Switcher & Modals (Desktop) -->
      <div class="flex items-center justify-end gap-2 sm:gap-3 shrink-0">

        <!-- LIGHT / DARK MODE TOGGLE BUTTON -->
        <button id="themeToggleBtn" onclick="toggleTheme()" class="px-2.5 sm:px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-900/70 hover:bg-white dark:hover:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm transition-all flex items-center gap-2 cursor-pointer active:scale-95" title="Toggle Light/Dark Theme">
          <span id="themeIconContainer">
            <i class="fa-solid fa-moon text-indigo-400 text-sm"></i>
          </span>
          <span id="themeTextLabel" class="hidden sm:inline font-mono">Dark</span>
        </button>

        <!-- CLINICAL & OPERATIONAL REMINDERS DROP TRIGGER -->
        @php
          $unreadRemindersCount = count(array_filter($reminders ?? [], fn($r) => empty($r['is_read'])));
          $hasUnreadDueToday = count(array_filter($reminders ?? [], fn($r) => empty($r['is_read']) && !empty($r['is_due_today']))) > 0;
        @endphp
        <div class="relative" id="remindersDropdownContainer">
          <button id="remindersBellBtn" onclick="toggleRemindersDropdown()" class="relative px-2.5 sm:px-3 py-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-900/70 hover:bg-white dark:hover:bg-slate-900 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm transition-all flex items-center gap-1.5 cursor-pointer active:scale-95" title="Clinical & Operational Reminders">
            <i class="fa-solid fa-bell text-amber-500 {{ $hasUnreadDueToday ? 'animate-bounce' : '' }}"></i>
            <span class="hidden sm:inline font-mono">Reminders</span>
            <span id="remindersBadgeCount" class="{{ $unreadRemindersCount > 0 ? '' : 'hidden' }} inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold font-mono leading-none text-white bg-rose-500 rounded-full shadow-sm animate-pulse">
              {{ $unreadRemindersCount }}
            </span>
          </button>

          <!-- REMINDER NOTIFICATION DROP PANEL -->
          <div id="remindersDropdown" class="hidden absolute right-0 mt-2 w-[calc(100vw-1.5rem)] max-w-sm sm:w-96 rounded-2xl p-4 shadow-2xl z-50 border border-slate-200 dark:border-slate-700/80 backdrop-blur-xl bg-white/95 dark:bg-slate-950/95 max-h-[75vh] sm:max-h-[480px] overflow-y-auto text-slate-900 dark:text-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 gap-2">
              <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400">
                  <i class="fa-solid fa-bell-concierge text-xs"></i>
                </div>
                <div>
                  <h4 class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">Clinical & Farm Reminders</h4>
                  <p class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">Dr. Kosiella Veterinary Protocols</p>
                </div>
              </div>
              <span id="remindersActivePill" class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-bold shrink-0">
                {{ $unreadRemindersCount }} Unread
              </span>
            </div>

            <!-- Controls Row: Push Alerts + Mark All Read -->
            <div class="mt-2.5 flex items-center justify-between gap-2 pb-2 border-b border-slate-200/70 dark:border-slate-800/70">
              <button type="button" id="pushNotifyToggleBtn" onclick="toggleBrowserNotifications()" class="text-[10px] font-mono px-2.5 py-1 rounded-lg border border-cyan-500/30 bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-500/20 font-semibold transition cursor-pointer flex items-center gap-1">
                <i class="fa-solid fa-tower-broadcast text-[9px]"></i>
                <span id="pushNotifyLabel">Enable Notification Bar Alerts</span>
              </button>
              @if (!empty($reminders))
                <button type="button" onclick="markAllRemindersRead()" class="text-[10px] font-mono px-2.5 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 font-semibold transition cursor-pointer">
                  <i class="fa-solid fa-check-double mr-1 text-emerald-500"></i> Mark All Read
                </button>
              @endif
            </div>

            <!-- Reminder List Items -->
            <div id="remindersListContainer" class="mt-3 space-y-2.5">
              @forelse (($reminders ?? []) as $rem)
                <div id="reminderCard-{{ $rem['id'] }}" class="reminder-card-item p-3 rounded-xl border {{ !empty($rem['is_read']) ? 'opacity-65' : '' }} {{ $rem['level'] === 'critical' ? 'bg-rose-50/90 dark:bg-rose-500/10 border-rose-200 dark:border-rose-500/40' : ($rem['level'] === 'warning' ? 'bg-amber-50/90 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/40' : 'bg-emerald-50/90 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/30') }} transition-all">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-1.5 flex-wrap">
                      <span class="text-[10px] font-mono px-2 py-0.5 rounded-md font-bold uppercase {{ $rem['level'] === 'critical' ? 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30' : ($rem['level'] === 'warning' ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30' : 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30') }}">
                        {{ $rem['species'] }} • {{ $rem['flock_code'] }}
                      </span>
                      @if (!empty($rem['is_due_today']))
                        <span class="text-[9px] font-mono font-black px-1.5 py-0.5 rounded bg-rose-600 text-white animate-pulse">
                          DUE TODAY
                        </span>
                      @endif
                    </div>
                    <span class="text-[10px] font-mono text-slate-500 dark:text-slate-400 shrink-0">Day {{ $rem['day_number'] ?? '0' }}</span>
                  </div>

                  <!-- Specific Batch Name -->
                  <div class="mt-1.5 text-[11px] font-semibold text-cyan-700 dark:text-cyan-300 flex items-center gap-1">
                    <i class="fa-solid fa-layer-group text-[10px]"></i>
                    <span>Batch: {{ $rem['batch_name'] ?? $rem['species'] }} ({{ $rem['flock_code'] }})</span>
                  </div>

                  <h5 class="text-xs font-bold mt-1 text-slate-900 dark:text-white leading-snug">
                    {{ $rem['title'] }}
                  </h5>

                  <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                    {{ $rem['purpose'] }}
                  </p>

                  <div class="mt-2 text-[10px] font-mono text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-route text-cyan-600 dark:text-cyan-400"></i>
                    <span>Route: {{ $rem['method'] }}</span>
                  </div>

                  @if (!empty($rem['pre_warning']))
                    <div class="mt-2 p-2 rounded-lg bg-amber-100/90 dark:bg-amber-950/70 border border-amber-300 dark:border-amber-500/40 text-[10px] text-amber-900 dark:text-amber-200 flex items-start gap-1.5">
                      <i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 mt-0.5"></i>
                      <span class="leading-relaxed font-medium">{{ $rem['pre_warning'] }}</span>
                    </div>
                  @endif

                  <!-- Action Buttons -->
                  <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-2 flex-wrap">
                    <button type="button" onclick="askAiAboutReminder({{ json_encode($rem['title'] . ' for batch ' . $rem['flock_code'] . ' (' . ($rem['batch_name'] ?? $rem['species']) . ')') }})" class="text-[10px] font-mono font-semibold text-emerald-700 dark:text-emerald-400 hover:text-emerald-600 dark:hover:text-emerald-300 underline flex items-center gap-1 cursor-pointer">
                      <i class="fa-solid fa-sparkles"></i> Consult AI
                    </button>
                    <div class="flex items-center gap-1.5">
                      @if (!empty($rem['is_read']))
                        <span class="px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-semibold text-[10px] font-mono border border-emerald-500/25">
                          <i class="fa-solid fa-check mr-0.5"></i> Read
                        </span>
                      @else
                        <button type="button" id="markReadBtn-{{ $rem['id'] }}" onclick="markReminderRead({{ json_encode($rem['id']) }})" class="mark-read-btn px-2 py-1 rounded-lg bg-slate-200/80 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-[10px] font-mono transition-all cursor-pointer">
                          <i class="fa-regular fa-eye-slash mr-0.5"></i> Mark Read
                        </button>
                      @endif
                      @if (!empty($rem['is_due_today']) && !empty($rem['batch_id']))
                        <button type="button" onclick="openModal('dailyLogModal'); const sel = document.querySelector('#dailyLogModal select[name=production_batch_id]'); if (sel) sel.value = '{{ $rem['batch_id'] }}';" class="px-2.5 py-1 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-[10px] font-mono shadow-sm transition-all cursor-pointer">
                          <i class="fa-solid fa-check mr-1"></i> Log Administered
                        </button>
                      @endif
                    </div>
                  </div>
                </div>
              @empty
                <div class="text-center py-6 text-slate-500 dark:text-slate-400">
                  <i class="fa-regular fa-calendar-check text-2xl text-emerald-500 dark:text-emerald-400 mb-2"></i>
                  <p class="text-xs font-medium">All active flocks are up to date!</p>
                  <p class="text-[10px] font-mono text-slate-500 mt-0.5">No critical vaccines or treatments pending today.</p>
                </div>
              @endforelse
            </div>
          </div>
        </div>

        <!-- Realtime Batch Switcher (Desktop Header) -->
        <div class="hidden lg:flex items-center min-w-0 bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700/70 rounded-xl p-1 shadow-sm">
          <label for="batchSelect" class="text-xs font-mono text-slate-500 dark:text-slate-400 px-2 flex items-center shrink-0">
            <i class="fa-solid fa-layer-group mr-1.5 text-cyan-500"></i>
          </label>
          <select id="batchSelect" onchange="switchBatch(this.value)" class="max-w-[260px] truncate bg-slate-100 dark:bg-slate-800 text-xs font-mono text-slate-800 dark:text-emerald-300 font-semibold py-1 px-2.5 rounded-lg border-0 focus:ring-1 focus:ring-emerald-500 outline-none cursor-pointer">
            @foreach ($productionBatches as $pb)
              <option value="{{ $pb['batch_code'] }}">{{ $pb['batch_code'] }} ({{ $pb['flock_type'] }} - Day {{ $pb['age_days'] }})</option>
            @endforeach
            @foreach ($incubationBatches as $ib)
              <option value="{{ $ib['batch_code'] }}">{{ $ib['batch_code'] }} ({{ $ib['parent_flock_info'] }} - Day {{ $ib['incubation_day'] }})</option>
            @endforeach
          </select>
        </div>

        <!-- Clock Widget -->
        <div class="hidden lg:flex items-center space-x-2 bg-white/80 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 px-3 py-1.5 rounded-xl font-mono text-xs text-slate-700 dark:text-slate-200 shadow-sm">
          <i class="fa-regular fa-clock text-emerald-500"></i>
          <span id="realtimeClock">--:--:-- WAT</span>
        </div>

        <!-- Modal Trigger: Push to Production (Desktop Header) -->
        <button onclick="openModal('transferModal')" class="hidden lg:flex relative group overflow-hidden px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white text-xs font-semibold shadow-md shadow-emerald-500/25 active:scale-95 transition-all items-center gap-1.5 cursor-pointer">
          <i class="fa-solid fa-arrow-right-arrow-left"></i>
          <span>Transfer Batch</span>
        </button>

        <!-- Modal Trigger: Daily Log (Desktop Header) -->
        <button onclick="openModal('dailyLogModal')" class="hidden lg:flex px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-medium border border-slate-300 dark:border-slate-700 transition-all items-center gap-1.5 active:scale-95 cursor-pointer">
          <i class="fa-solid fa-pen-to-square text-amber-500"></i>
          <span>Daily Log</span>
        </button>

        <!-- Modal Trigger: New Sale (Desktop Header) -->
        <button onclick="openModal('recordSaleModal')" class="hidden lg:flex px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-medium border border-slate-300 dark:border-slate-700 transition-all items-center gap-1.5 active:scale-95 cursor-pointer">
          <i class="fa-solid fa-receipt text-cyan-500"></i>
          <span>Record Sale</span>
        </button>
      </div>
    </header>

    <!-- MOBILE QUICK CONTROLS STRIP (Non-sticky so header stays compact) -->
    <div class="lg:hidden px-3.5 pt-3 space-y-2">
      <div class="flex items-center bg-white/85 dark:bg-slate-900/85 border border-slate-200/80 dark:border-slate-800 rounded-xl px-2.5 py-1.5 shadow-sm">
        <i class="fa-solid fa-layer-group text-cyan-500 text-xs mr-2 shrink-0"></i>
        <select onchange="switchBatch(this.value)" class="w-full min-w-0 bg-transparent text-xs font-mono text-slate-800 dark:text-emerald-300 font-semibold outline-none truncate">
          @foreach ($productionBatches as $pb)
            <option value="{{ $pb['batch_code'] }}" class="bg-white dark:bg-slate-900">{{ $pb['batch_code'] }} ({{ $pb['flock_type'] }} • Day {{ $pb['age_days'] }})</option>
          @endforeach
          @foreach ($incubationBatches as $ib)
            <option value="{{ $ib['batch_code'] }}" class="bg-white dark:bg-slate-900">{{ $ib['batch_code'] }} ({{ $ib['parent_flock_info'] }} • Day {{ $ib['incubation_day'] }})</option>
          @endforeach
        </select>
      </div>
      <div class="grid grid-cols-3 gap-2">
        <button onclick="openModal('transferModal')" class="px-2 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white text-[11px] font-semibold shadow-sm flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
          <i class="fa-solid fa-arrow-right-arrow-left text-[10px]"></i>
          <span>Transfer</span>
        </button>
        <button onclick="openModal('dailyLogModal')" class="px-2 py-2 rounded-xl bg-white/85 dark:bg-slate-900/85 text-slate-800 dark:text-slate-200 text-[11px] font-semibold border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
          <i class="fa-solid fa-pen-to-square text-amber-500 text-[10px]"></i>
          <span>Daily Log</span>
        </button>
        <button onclick="openModal('recordSaleModal')" class="px-2 py-2 rounded-xl bg-white/85 dark:bg-slate-900/85 text-slate-800 dark:text-slate-200 text-[11px] font-semibold border border-slate-200/80 dark:border-slate-700/80 flex items-center justify-center gap-1.5 active:scale-95 cursor-pointer">
          <i class="fa-solid fa-receipt text-cyan-500 text-[10px]"></i>
          <span>Sale</span>
        </button>
      </div>
    </div>

    <!-- WORKSPACE BODY: GLASSMORPHIC SIDEBAR + MAIN CONTENT -->
    <div class="flex flex-1 min-h-0 relative">

      <!-- Mobile Sidebar Backdrop -->
      <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-xs hidden lg:hidden"></div>

      <!-- NAVIGATION SIDEBAR -->
      <aside id="appSidebar" class="fixed lg:sticky lg:top-[73px] lg:h-[calc(100vh-73px)] lg:self-start inset-y-0 left-0 z-50 lg:z-20 w-64 lg:w-60 shrink-0 transform -translate-x-full lg:translate-x-0 lg:transform-none transition-transform duration-300 ease-out bg-white/95 dark:bg-slate-950/95 lg:bg-transparent backdrop-blur-xl border-r border-slate-200/80 dark:border-slate-800/80 flex flex-col justify-between p-4 pt-5 overflow-y-auto">
        <div class="space-y-1.5">
          <div class="px-3 pb-2 flex items-center justify-between">
            <span class="text-[10px] font-mono font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">Farm Modules</span>
            <button onclick="toggleSidebar(false)" class="lg:hidden text-slate-500 hover:text-slate-900 dark:hover:text-white cursor-pointer">
              <i class="fa-solid fa-xmark text-sm"></i>
            </button>
          </div>

          @php
            $navItems = [
              ['page' => 'dashboard',  'label' => 'Dashboard',  'icon' => 'fa-gauge-high',    'sub' => 'Executive Overview'],
              ['page' => 'incubation', 'label' => 'Incubator',  'icon' => 'fa-egg',           'sub' => 'Hatchery & Setters'],
              ['page' => 'production', 'label' => 'Production', 'icon' => 'fa-kiwi-bird',     'sub' => 'Flock Telemetry'],
              ['page' => 'inventory',  'label' => 'Inventory',  'icon' => 'fa-boxes-stacked', 'sub' => 'Feed & Vaccines'],
              ['page' => 'sales',      'label' => 'Sales',      'icon' => 'fa-receipt',        'sub' => 'Revenue & Orders'],
              ['page' => 'finance',    'label' => 'Finance',    'icon' => 'fa-scale-balanced', 'sub' => 'Assets & Whole P&L'],
              ['page' => 'ai',         'label' => 'Farm AI',    'icon' => 'fa-robot',          'sub' => 'Vet Copilot'],
            ];
            $activePage = $currentPage ?? 'dashboard';
          @endphp

          @foreach ($navItems as $item)
            @php $isActive = $activePage === $item['page']; @endphp
            <a href="/?page={{ $item['page'] }}"
               class="group flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ $isActive ? 'bg-emerald-500 text-slate-950 font-bold shadow-md shadow-emerald-500/25' : 'text-slate-700 dark:text-slate-300 hover:bg-white/70 dark:hover:bg-slate-800/70 hover:text-emerald-600 dark:hover:text-emerald-400' }}">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 {{ $isActive ? 'bg-slate-950/15 text-slate-950' : 'bg-slate-200/70 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 group-hover:text-emerald-600 dark:group-hover:text-emerald-400' }}">
                <i class="fa-solid {{ $item['icon'] }} text-xs"></i>
              </div>
              <div class="min-w-0">
                <div class="text-xs font-semibold leading-tight truncate">{{ $item['label'] }}</div>
                <div class="text-[10px] leading-tight truncate {{ $isActive ? 'text-slate-900/75 font-mono' : 'text-slate-500 dark:text-slate-400' }}">{{ $item['sub'] }}</div>
              </div>
            </a>
          @endforeach
        </div>

        <div class="pt-4 mt-4 border-t border-slate-200/70 dark:border-slate-800/70 px-2 space-y-2.5">
          <div class="rounded-xl bg-emerald-500/10 border border-emerald-500/25 p-3 text-[11px]">
            <div class="flex items-center gap-1.5 font-bold text-emerald-700 dark:text-emerald-300">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>Dr. Kosiella Protocols</span>
            </div>
            <p class="text-[10px] text-slate-600 dark:text-slate-400 mt-1 leading-relaxed">
              Live cost-per-bird & clinical reminders synced across all modules.
            </p>
          </div>
          <button type="button" id="pwaInstallBtn" onclick="triggerPwaInstall()" class="hidden w-full mb-2 flex items-center justify-center gap-2 px-3 py-2 rounded-xl border border-emerald-500/40 bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 text-[11px] font-mono font-bold transition-all cursor-pointer">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <span>Install Phone App</span>
          </button>
          <a href="/?action=download_backup" class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:bg-emerald-500/15 hover:border-emerald-500/40 text-slate-700 dark:text-slate-200 text-[11px] font-mono font-semibold transition-all">
            <i class="fa-solid fa-download text-emerald-500"></i>
            <span>1-Click Data Backup</span>
          </a>
          <div class="grid grid-cols-2 gap-2 mt-2">
            <button type="button" onclick="openModal('changePasswordModal')" class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:bg-slate-200/70 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-mono font-semibold transition-all cursor-pointer">
              <i class="fa-solid fa-key text-amber-500"></i>
              <span>Password</span>
            </button>
            <a href="/?action=logout" class="flex items-center justify-center gap-1.5 px-2.5 py-2 rounded-xl border border-rose-500/30 bg-rose-500/10 hover:bg-rose-500/20 text-rose-600 dark:text-rose-300 text-[11px] font-mono font-semibold transition-all">
              <i class="fa-solid fa-right-from-bracket"></i>
              <span>Sign Out</span>
            </a>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT COLUMN -->
      <div class="flex-1 min-w-0 flex flex-col">

    <!-- Notification Messages (Flash Alerts) -->
    @if (!empty($flashSuccess))
      <div class="mx-4 lg:mx-8 mt-4 rounded-2xl glass-card px-4 py-3 flex items-center justify-between shadow-lg border-emerald-500/50 text-emerald-200 text-xs font-mono">
        <div class="flex items-center gap-2.5">
          <i class="fa-solid fa-circle-check text-emerald-400 text-base"></i>
          <span class="font-semibold text-slate-900 dark:text-emerald-100">{{ $flashSuccess }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
      </div>
    @endif

    @if (!empty($flashError))
      <div class="mx-4 lg:mx-8 mt-4 rounded-2xl glass-card px-4 py-3 flex items-center justify-between shadow-lg border-rose-500/50 text-rose-200 text-xs font-mono">
        <div class="flex items-center gap-2.5">
          <i class="fa-solid fa-triangle-exclamation text-rose-400 text-base"></i>
          <span class="font-semibold text-slate-900 dark:text-rose-100">{{ $flashError }}</span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white cursor-pointer"><i class="fa-solid fa-xmark"></i></button>
      </div>
    @endif

    <!-- CONTENT BODY -->
    <main class="p-4 lg:p-8 pb-24 lg:pb-8 max-w-7xl mx-auto w-full space-y-6">
      @yield('content')
    </main>

    <!-- FOOTER -->
    <footer class="mt-12 mb-16 lg:mb-0 border-t border-slate-300 dark:border-slate-900 bg-white/70 dark:bg-slate-950/70 px-4 lg:px-8 py-4 flex flex-wrap items-center justify-between text-xs text-slate-500 gap-4">
      <div class="flex items-center space-x-2">
        <i class="fa-solid fa-feather-pointed text-emerald-500"></i>
        <span>Blackswan Farms and General Enterprises • Integrated Hatchery &amp; Broiler Financial Ledger</span>
      </div>
      <div class="flex items-center space-x-4 font-mono text-[11px]">
        <span>Database: <strong class="text-slate-700 dark:text-slate-300">Synced (8 Tables)</strong></span>
        <span>Lump Rent Allocation: <strong class="text-slate-700 dark:text-slate-300">Active</strong></span>
        <span>Telemetry Frequency: <strong class="text-slate-700 dark:text-slate-300">Real-time</strong></span>
        <span>Google Gemini: <strong class="text-emerald-500">{{ $geminiModel }}</strong></span>
      </div>
    </footer>

      </div><!-- /MAIN CONTENT COLUMN -->
    </div><!-- /WORKSPACE BODY -->

  </div>

  <!-- MOBILE BOTTOM NAVIGATION BAR (Phone PWA Navigation - Top-Level Fixed) -->
  <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 border-t border-slate-200/80 dark:border-slate-800/80 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl px-2 py-1.5 shadow-[0_-8px_24px_rgba(0,0,0,0.18)]">
    <div class="grid grid-cols-5 gap-1 text-center">
      <a href="/?page=dashboard" class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-semibold transition touch-manipulation {{ $currentPage === 'dashboard' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15' : 'text-slate-500 dark:text-slate-400' }}">
        <i class="fa-solid fa-gauge-high text-sm mb-0.5"></i>
        <span>Home</span>
      </a>
      <a href="/?page=incubation" class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-semibold transition touch-manipulation {{ $currentPage === 'incubation' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15' : 'text-slate-500 dark:text-slate-400' }}">
        <i class="fa-solid fa-egg text-sm mb-0.5"></i>
        <span>Incubate</span>
      </a>
      <a href="/?page=production" class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-semibold transition touch-manipulation {{ $currentPage === 'production' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15' : 'text-slate-500 dark:text-slate-400' }}">
        <i class="fa-solid fa-kiwi-bird text-sm mb-0.5"></i>
        <span>Flocks</span>
      </a>
      <a href="/?page=inventory" class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-semibold transition touch-manipulation {{ $currentPage === 'inventory' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15' : 'text-slate-500 dark:text-slate-400' }}">
        <i class="fa-solid fa-boxes-stacked text-sm mb-0.5"></i>
        <span>Store</span>
      </a>
      <a href="/?page=sales" class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-semibold transition touch-manipulation {{ $currentPage === 'sales' ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-500/15' : 'text-slate-500 dark:text-slate-400' }}">
        <i class="fa-solid fa-receipt text-sm mb-0.5"></i>
        <span>Sales</span>
      </a>
    </div>
  </nav>

  <!-- FLOATING AGRI-AI ASSISTANT (OMNIPRESENT ACROSS EVERY SCREEN) -->
  <div id="aiFloatingContainer" class="fixed bottom-20 right-4 lg:bottom-6 lg:right-6 z-50 flex flex-col items-end">
    
    <!-- AI Companion Chat Window / Drawer -->
    <div id="aiChatDrawer" class="hidden opacity-0 scale-95 transition-all duration-300 mb-3 w-[92vw] sm:w-[440px] rounded-3xl p-5 shadow-2xl border border-emerald-500/40 flex flex-col justify-between max-h-[590px] bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl text-slate-900 dark:text-slate-100">
      
      <!-- Drawer Header -->
      <div class="flex items-center justify-between pb-3.5 border-b border-slate-200 dark:border-slate-700/80">
        <div class="flex items-center gap-3">
          <!-- Animated Farm Chick Mascot Avatar -->
          <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-500 via-teal-400 to-cyan-400 flex items-center justify-center text-slate-950 shadow-md shadow-emerald-500/30 p-1 farm-ai-mascot">
            <svg class="w-7 h-7 drop-shadow-sm" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="18" cy="19" r="14" fill="#FBBF24" stroke="#ffffff" stroke-width="1.5" />
              <path d="M15 5C15 3.5 16.5 2 18 2C19.5 2 21 3.5 21 5C22 5 23.5 6 23.5 7.5C23.5 9 22 10 20 10H16C14 10 12.5 9 12.5 7.5C12.5 6 14 5 15 5Z" fill="#F59E0B" />
              <circle cx="13" cy="16" r="2" fill="#0F172A" />
              <circle cx="13.6" cy="15.4" r="0.7" fill="#FFFFFF" />
              <circle cx="23" cy="16" r="2" fill="#0F172A" />
              <circle cx="23.6" cy="15.4" r="0.7" fill="#FFFFFF" />
              <ellipse cx="10.5" cy="19" rx="1.8" ry="1.2" fill="#F43F5E" opacity="0.6" />
              <ellipse cx="25.5" cy="19" rx="1.8" ry="1.2" fill="#F43F5E" opacity="0.6" />
              <polygon points="18,17 21,21 15,21" fill="#EA580C" />
            </svg>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-extrabold tracking-wide text-slate-900 dark:text-white">Farm AI Copilot</span>
              <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-bold border border-emerald-500/30">Gemini 2.5</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-300">Intelligent Veterinary & Lifecycle Advisory</p>
          </div>
        </div>
        <button onclick="toggleAiDrawer()" class="w-8 h-8 rounded-xl bg-slate-200/80 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 flex items-center justify-center text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer">
          <i class="fa-solid fa-minus text-xs"></i>
        </button>
      </div>

      <!-- Quick Suggested Prompts Pill Row -->
      <div class="py-2.5 flex items-center gap-1.5 overflow-x-auto no-scrollbar text-[11px] font-mono">
        <button onclick="sendQuickAiPrompt('Calculate current batch breakeven')" class="whitespace-nowrap px-3 py-1 rounded-full bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/35 text-emerald-700 dark:text-emerald-300 font-semibold transition-all cursor-pointer">
          <i class="fa-solid fa-calculator text-[10px] mr-1"></i> Breakeven
        </button>
        <button onclick="sendQuickAiPrompt('Suggest feed transition for current flocks')" class="whitespace-nowrap px-3 py-1 rounded-full bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/35 text-amber-800 dark:text-amber-300 font-semibold transition-all cursor-pointer">
          <i class="fa-solid fa-wheat-awn text-[10px] mr-1"></i> Feed Transition
        </button>
        <button onclick="sendQuickAiPrompt('Tell me about the medications I uploaded and their dosage')" class="whitespace-nowrap px-3 py-1 rounded-full bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/35 text-cyan-700 dark:text-cyan-300 font-semibold transition-all cursor-pointer">
          <i class="fa-solid fa-capsules text-[10px] mr-1"></i> Uploaded Meds
        </button>
      </div>

      <!-- Dialogue History -->
      <div id="aiChatHistory" class="flex-grow overflow-y-auto space-y-3.5 pr-1.5 my-1.5 max-h-[330px] text-xs font-sans">
        @forelse ($aiChatHistory as $msg)
          @if ($msg['role'] === 'user')
            <div class="p-3 rounded-2xl bg-cyan-50 dark:bg-slate-800/90 text-slate-800 dark:text-slate-100 border border-cyan-200 dark:border-slate-600 text-xs ml-6 shadow-sm">
              <span class="text-cyan-700 dark:text-cyan-300 font-bold block text-[10px] uppercase font-mono mb-0.5">You</span>
              <div class="leading-relaxed">{{ $msg['message'] }}</div>
            </div>
          @else
            <div class="p-3.5 rounded-2xl bg-slate-100/90 dark:bg-slate-900/95 border border-emerald-500/30 dark:border-emerald-500/35 text-slate-800 dark:text-slate-100 text-xs mr-3 shadow-md space-y-1.5">
              <div class="flex items-center justify-between text-[11px] text-emerald-700 dark:text-emerald-400 font-mono pb-1 border-b border-emerald-500/20 font-bold">
                <span class="flex items-center gap-1.5">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                  Farm Copilot
                </span>
                <span class="text-slate-500 dark:text-slate-400">{{ $msg['created_at'] ?? '' }}</span>
              </div>
              <div class="ai-prose space-y-1.5 leading-relaxed">{!! $msg['message_html'] !!}</div>
            </div>
          @endif
        @empty
          <div class="p-3.5 rounded-2xl bg-slate-100/90 dark:bg-slate-900/95 border border-emerald-500/30 dark:border-emerald-500/35 text-slate-800 dark:text-slate-100 shadow-md">
            <p class="font-bold text-emerald-700 dark:text-emerald-400 text-xs mb-1 flex items-center gap-1.5">
              <i class="fa-solid fa-sparkles text-amber-500 dark:text-amber-400"></i> Agri-AI Copilot Ready
            </p>
            <p class="text-slate-700 dark:text-slate-200 leading-relaxed">
              Hello! I am actively tracking your incubation batches, feed consumption, mortality, and financial ledger. Ask me about cost allocations, rent absorption, or daily feeding targets.
            </p>
          </div>
        @endforelse
      </div>

      <!-- AI Prompt Input Box -->
      <form onsubmit="handleAiMessageSubmit(event)" class="pt-3 border-t border-slate-700/80 flex items-center gap-2">
        <input type="text" id="aiInputPrompt" placeholder="Ask AI about batches, meds, costs, or feed..." class="flex-grow bg-slate-900 text-xs text-white px-3.5 py-2.5 rounded-xl border border-slate-700 outline-none focus:border-emerald-500 placeholder-slate-400 font-mono">
        <button type="submit" id="aiSubmitBtn" class="w-9 h-9 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold flex items-center justify-center transition-all cursor-pointer active:scale-95 shadow-lg">
          <i class="fa-solid fa-paper-plane text-xs"></i>
        </button>
      </form>
    </div>

    <!-- Pinned Floating AI Action Button (Animated Farm Mascot) -->
    <button onclick="toggleAiDrawer()" id="aiFloatingBtn" class="farm-ai-btn relative group px-3.5 py-2.5 rounded-2xl bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 hover:scale-105 active:scale-95 text-slate-950 font-bold flex items-center gap-3 transition-all cursor-pointer border-2 border-white/80 shadow-2xl">
      <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-300 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-amber-400 border border-white"></span>
      </span>
      
      <!-- Animated Farm Chick Icon -->
      <div class="farm-ai-mascot flex items-center justify-center">
        <svg class="w-7 h-7 drop-shadow-md" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="18" cy="19" r="14" fill="#FBBF24" stroke="#ffffff" stroke-width="1.5" />
          <path d="M15 5C15 3.5 16.5 2 18 2C19.5 2 21 3.5 21 5C22 5 23.5 6 23.5 7.5C23.5 9 22 10 20 10H16C14 10 12.5 9 12.5 7.5C12.5 6 14 5 15 5Z" fill="#F59E0B" />
          <circle cx="13" cy="16" r="2" fill="#0F172A" />
          <circle cx="13.6" cy="15.4" r="0.7" fill="#FFFFFF" />
          <circle cx="23" cy="16" r="2" fill="#0F172A" />
          <circle cx="23.6" cy="15.4" r="0.7" fill="#FFFFFF" />
          <ellipse cx="10.5" cy="19" rx="1.8" ry="1.2" fill="#F43F5E" opacity="0.6" />
          <ellipse cx="25.5" cy="19" rx="1.8" ry="1.2" fill="#F43F5E" opacity="0.6" />
          <polygon points="18,17 21,21 15,21" fill="#EA580C" />
        </svg>
      </div>

      <div class="text-left hidden sm:block">
        <span class="block text-xs font-black tracking-tight text-slate-950 leading-none">Farm Copilot</span>
        <span class="block text-[10px] text-emerald-950/80 font-bold leading-tight">AI Active</span>
      </div>
    </button>
  </div>

  <!-- MODAL 1: TRANSFER FROM INCUBATION TO PRODUCTION -->
  <div id="transferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-8 border border-emerald-500/40 relative transform scale-95 transition-transform duration-300" id="transferModalBox">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
            <i class="fa-solid fa-dolly"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Incubator-to-Production Transfer</h3>
        </div>
        <button onclick="closeModal('transferModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>

      <p class="text-xs text-slate-600 dark:text-slate-400 mb-5">
        Hand over hatched chicks from the incubator to a live production barn. All incubation expenses (gas, repairs, egg purchase) will transfer into the new batch ledger as baseline capital.
      </p>

      <form method="POST" action="/?page=incubation" class="space-y-4 text-xs font-mono">
        @csrf
        <input type="hidden" name="action" value="push_to_production">
        
        <div>
          <label class="block text-slate-700 dark:text-slate-300 mb-1">Source Incubation Batch</label>
          <select name="incubation_batch_id" id="transferIncubationSelect" onchange="updateTransferPricingPreview()" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-emerald-500">
            @foreach ($incubationBatches as $ib)
              @if ($ib['status'] === 'Completed' || $ib['status'] === 'Active')
                @php
                  $tChicks = (int) (($ib['metrics']['live_chicks'] ?? 0) > 0 ? $ib['metrics']['live_chicks'] : ($ib['metrics']['viable_eggs_remaining'] ?? 0));
                  $tTotalCost = (float) ($ib['metrics']['total_incubation_cost'] ?? 0);
                  $tCostPerChick = $tChicks > 0 ? round($tTotalCost / $tChicks, 2) : 0;
                @endphp
                <option value="{{ $ib['id'] }}" data-chicks="{{ $tChicks }}" data-total-cost="{{ $tTotalCost }}" data-cost-per-chick="{{ $tCostPerChick }}">
                  {{ $ib['batch_code'] }} ({{ $ib['parent_flock_info'] }} - {{ number_format($tChicks) }} chicks)
                </option>
              @endif
            @endforeach
          </select>
        </div>

        <!-- Live Incubation Cost Breakdown & Suggested Selling Price -->
        <div class="rounded-2xl bg-slate-100/90 dark:bg-slate-900/80 border border-emerald-500/30 p-3.5 space-y-2">
          <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
            <span>Incubation Cost Summary</span>
            <span id="transferChicksBadge" class="font-bold text-emerald-700 dark:text-emerald-300">-- chicks</span>
          </div>
          <div class="grid grid-cols-3 gap-2 text-center">
            <div class="rounded-xl bg-white/80 dark:bg-slate-800/80 p-2 border border-slate-200 dark:border-slate-700">
              <span class="block text-[10px] text-slate-500 dark:text-slate-400">Total Spent</span>
              <span id="transferTotalSpent" class="text-xs font-bold text-slate-900 dark:text-white">₦0.00</span>
            </div>
            <div class="rounded-xl bg-white/80 dark:bg-slate-800/80 p-2 border border-slate-200 dark:border-slate-700">
              <span class="block text-[10px] text-slate-500 dark:text-slate-400">Spent / Chick</span>
              <span id="transferCostPerChick" class="text-xs font-bold text-amber-600 dark:text-amber-300">₦0.00</span>
            </div>
            <div class="rounded-xl bg-emerald-500/15 p-2 border border-emerald-500/30">
              <span class="block text-[10px] text-emerald-700 dark:text-emerald-300">Suggested (+30%)</span>
              <span id="transferSuggestedPrice" class="text-xs font-bold text-emerald-700 dark:text-emerald-300">₦0.00</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Target Production Barn</label>
            <input type="text" name="house_pen_id" value="Barn 01 (Deep Litter)" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-emerald-500">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Selling Price per Bird (₦)</label>
            <input type="number" step="0.01" min="0" name="selling_price_per_bird" id="transferSellingPriceInput" required class="w-full bg-white dark:bg-slate-800 border border-emerald-500/50 rounded-xl px-3 py-2 text-emerald-700 dark:text-emerald-300 font-bold outline-none focus:border-emerald-500">
          </div>
        </div>
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          <i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i> Farm rent is managed as a general farm-wide overhead across all products.
        </p>

        <div class="pt-2 flex justify-end gap-3">
          <button type="button" onclick="closeModal('transferModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 cursor-pointer">Cancel</button>
          <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold shadow-md cursor-pointer">Confirm Handover</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 2: LOG DAILY PRODUCTION TELEMETRY -->
  <div id="dailyLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-8 border border-amber-500/40 relative transform scale-95 transition-transform duration-300" id="dailyLogModalBox">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
            <i class="fa-solid fa-pen-to-square"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Daily Production & Health Log</h3>
        </div>
        <button onclick="closeModal('dailyLogModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>

      <form method="POST" action="/?page=production" class="space-y-4 text-xs font-mono">
        @csrf
        <input type="hidden" name="action" value="add_production_daily_log">
        
        <div>
          <label class="block text-slate-700 dark:text-slate-300 mb-1">Production Flock</label>
          <select name="production_batch_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-emerald-500">
            @foreach ($productionBatches as $pb)
              <option value="{{ $pb['id'] }}">{{ $pb['batch_code'] }} ({{ $pb['flock_type'] }} - Day {{ $pb['age_days'] }})</option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Mortality Count Today</label>
            <input type="number" name="mortality_count" placeholder="e.g. 4" min="0" value="0" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-rose-600 dark:text-rose-400 font-bold outline-none focus:border-rose-500">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Water Intake (Liters)</label>
            <input type="number" step="0.1" name="water_liters" placeholder="e.g. 350" value="350" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-cyan-600 dark:text-cyan-400 font-bold outline-none focus:border-cyan-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Feed Item in Store</label>
            <select name="feed_item_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-emerald-500">
              <option value="">-- No Feed Logged --</option>
              @foreach ($inventoryItems as $item)
                @if (in_array($item['category'], ['starter', 'grower', 'layer'], true))
                  <option value="{{ $item['id'] }}">[{{ strtoupper($item['category']) }}] {{ $item['item_name'] }} (₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 4) }}/g • {{ number_format((float)$item['total_quantity_g_or_ml']) }}g left)</option>
                @endif
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Feed Consumed (Grams)</label>
            <input type="number" step="1" name="feed_grams" placeholder="e.g. 150000" value="0" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-emerald-600 dark:text-emerald-400 font-bold outline-none focus:border-emerald-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Medication in Store</label>
            <select name="med_item_id" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-purple-500">
              <option value="">-- None / Routine Water --</option>
              @foreach ($inventoryItems as $item)
                @if ($item['category'] === 'medicine')
                  <option value="{{ $item['id'] }}">{{ $item['item_name'] }} (₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 2) }}/{{ $item['unit_label'] }} • {{ number_format((float)$item['total_quantity_g_or_ml']) }}{{ $item['unit_label'] }} left)</option>
                @endif
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Med Dosage (ml/g)</label>
            <input type="number" step="0.1" name="med_dose_g_ml" placeholder="e.g. 250" value="0" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-purple-600 dark:text-purple-300 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Direct Labor (₦)</label>
            <input type="number" step="0.01" name="labor_cost" value="0" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Sanitation (₦)</label>
            <input type="number" step="0.01" name="sanitation_cost" value="0" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Water / Other (₦)</label>
            <input type="number" step="0.01" name="water_other_cost" value="0" min="0" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-cyan-600 dark:text-cyan-300 outline-none">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Log Date</label>
            <input type="date" name="log_date" value="{{ date('Y-m-d') }}" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Clinical / House Note</label>
            <input type="text" name="notes" placeholder="e.g. Routine feeding &amp; water" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none">
          </div>
        </div>

        <div class="pt-2 flex justify-end gap-3">
          <button type="button" onclick="closeModal('dailyLogModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 cursor-pointer">Cancel</button>
          <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold shadow-md cursor-pointer">Commit Daily Record</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL 3: RECORD COMMERCIAL BATCH SALE -->
  <div id="recordSaleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-8 border border-cyan-500/40 relative transform scale-95 transition-transform duration-300" id="recordSaleModalBox">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-500 flex items-center justify-center">
            <i class="fa-solid fa-receipt"></i>
          </div>
          <h3 class="text-lg font-bold text-slate-900 dark:text-white">Record Commercial Bird Sale</h3>
        </div>
        <button onclick="closeModal('recordSaleModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
      </div>

      <form method="POST" action="/?page=sales" class="space-y-4 text-xs font-mono">
        @csrf
        <input type="hidden" name="action" value="record_batch_sale">
        
        <div>
          <label class="block text-slate-700 dark:text-slate-300 mb-1">Select Harvest Flock</label>
          <select name="production_batch_id" id="modalSaleBatchSelect" onchange="syncModalSalePrice()" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
            @foreach ($productionBatches as $pb)
              <option value="{{ $pb['id'] }}" data-rec-price="{{ $pb['metrics']['recommended_selling_price'] }}">
                {{ $pb['batch_code'] }} ({{ number_format($pb['metrics']['current_in_house_birds']) }} available • Rec: ₦{{ number_format($pb['metrics']['recommended_selling_price'], 2) }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Birds Sold (Qty)</label>
            <input type="number" name="birds_sold" placeholder="e.g. 50" min="1" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Selling Price / Bird (₦)</label>
            <input type="number" step="0.01" min="0.01" name="unit_price" id="modalSaleUnitPrice" value="{{ $productionBatches[0]['metrics']['recommended_selling_price'] ?? '' }}" placeholder="e.g. 1850" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-emerald-600 dark:text-emerald-400 font-bold outline-none focus:border-emerald-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Customer Name</label>
            <input type="text" name="buyer_name" placeholder="e.g. Zaria Fresh Mart" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Customer Phone Number</label>
            <input type="text" name="buyer_contact" placeholder="e.g. 0803-000-0000" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Payment Status</label>
            <select name="payment_status" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
              <option value="Paid">Paid</option>
              <option value="Partial">Partial</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Amount Paid (₦)</label>
            <input type="number" step="0.01" min="0" name="amount_paid" placeholder="Auto if Paid" class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
          </div>
          <div>
            <label class="block text-slate-700 dark:text-slate-300 mb-1">Sale Date</label>
            <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white outline-none focus:border-cyan-500">
          </div>
        </div>

        <div class="pt-2 flex justify-end gap-3">
          <button type="button" onclick="closeModal('recordSaleModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 cursor-pointer">Cancel</button>
          <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-bold shadow-md cursor-pointer">Commit Sale & Deplete</button>
        </div>
      </form>
    </div>
  </div>

  <!-- CLINICAL REMINDER SLIDE-DOWN DROP TOAST -->
  <div id="reminderDropToast" class="pointer-events-none fixed top-20 left-4 right-4 sm:left-auto sm:right-8 z-50 transform -translate-y-48 opacity-0 transition-all duration-500 sm:max-w-sm sm:w-full rounded-2xl p-4 shadow-2xl border border-rose-300 dark:border-rose-500/50 bg-white/95 dark:bg-slate-950/95 backdrop-blur-xl text-slate-900 dark:text-slate-100">
    <div class="flex items-start justify-between gap-3">
      <div class="flex items-start gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-rose-500/15 border border-rose-500/35 flex items-center justify-center text-rose-600 dark:text-rose-400 mt-0.5 shrink-0">
          <i class="fa-solid fa-bell text-sm animate-bounce"></i>
        </div>
        <div>
          <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-rose-500/15 text-rose-700 dark:text-rose-300 font-bold border border-rose-500/30">VET REMINDER DUE TODAY</span>
          <h5 id="reminderToastTitle" class="text-xs font-extrabold text-slate-900 dark:text-white mt-1.5">Vaccine / Treatment Due</h5>
          <p id="reminderToastText" class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed"></p>
        </div>
      </div>
      <button onclick="dismissReminderToast()" class="text-slate-400 hover:text-slate-700 dark:hover:text-white cursor-pointer"><i class="fa-solid fa-xmark text-sm"></i></button>
    </div>
    <div class="mt-3 flex items-center justify-end gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
      <button type="button" onclick="markToastReminderRead()" class="px-2.5 py-1 rounded-lg bg-slate-200/80 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-[10px] font-mono font-semibold cursor-pointer">Mark Read</button>
      <button type="button" onclick="dismissReminderToast(); toggleRemindersDropdown();" class="px-2.5 py-1 rounded-lg bg-slate-200/80 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-[10px] font-mono font-semibold cursor-pointer">View All Drops</button>
      <button type="button" onclick="dismissReminderToast(); openModal('dailyLogModal');" class="px-3 py-1 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-[10px] font-mono shadow-md cursor-pointer">Log Administered</button>
    </div>
  </div>

  <!-- NOTIFICATION TOAST -->
  <div id="toastMessage" class="pointer-events-none fixed bottom-20 left-4 lg:bottom-6 lg:left-6 z-50 transform translate-y-40 opacity-0 transition-all duration-300 px-4 py-3 rounded-2xl bg-slate-900 text-white border border-emerald-500/50 font-mono text-xs flex items-center gap-3 shadow-2xl">
    <i class="fa-solid fa-circle-check text-emerald-400 text-base" id="toastIcon"></i>
    <span id="toastText">Action completed successfully.</span>
  </div>

  <!-- SYSTEM JAVASCRIPT ENGINE -->
  <script>
    // System State Store
    const state = {
      isDark: true,
      currentBatchId: 'PRD-2026-01',
      liveHeadcount: {{ $summary['total_live_birds'] ?? 5570 }},
      accumulatedExpenses: 3428500,
      feedUnitCostGrams: 0.42,
      targetMarginPct: 35,
      mortalityCount: 65,
      isAiDrawerOpen: false
    };

    let costBreakdownChart = null;
    let candlingChart = null;

    // AI Companion Toggle & Logic (Grounded in Live Gemini API)
    function toggleAiDrawer() {
      state.isAiDrawerOpen = !state.isAiDrawerOpen;
      const drawer = document.getElementById('aiChatDrawer');
      if (state.isAiDrawerOpen) {
        drawer.classList.remove('hidden');
        setTimeout(() => {
          drawer.classList.remove('opacity-0', 'scale-95');
          drawer.classList.add('opacity-100', 'scale-100');
          document.getElementById('aiInputPrompt').focus();
        }, 10);
      } else {
        drawer.classList.add('opacity-0', 'scale-95');
        drawer.classList.remove('opacity-100', 'scale-100');
        setTimeout(() => {
          drawer.classList.add('hidden');
        }, 300);
      }
    }

    function sendQuickAiPrompt(promptText) {
      document.getElementById('aiInputPrompt').value = promptText;
      processAiPrompt(promptText);
    }

    function handleAiMessageSubmit(e) {
      e.preventDefault();
      const input = document.getElementById('aiInputPrompt');
      const val = input.value.trim();
      if (!val) return;
      processAiPrompt(val);
      input.value = '';
    }

    async function processAiPrompt(query) {
      const chatHistory = document.getElementById('aiChatHistory');
      
      // User message bubble
      const userBubble = document.createElement('div');
      userBubble.className = 'p-3 rounded-2xl bg-cyan-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-cyan-200 dark:border-slate-700 text-xs ml-6 shadow-sm';
      userBubble.innerHTML = `<span class="text-cyan-700 dark:text-cyan-400 font-bold block text-[10px] uppercase font-mono mb-0.5">You</span>${query}`;
      chatHistory.appendChild(userBubble);
      chatHistory.scrollTop = chatHistory.scrollHeight;

      // Loading bubble
      const loadingBubble = document.createElement('div');
      loadingBubble.className = 'p-3 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-500/20 text-slate-700 dark:text-slate-200 text-xs mr-4 space-y-1';
      loadingBubble.id = 'aiThinkingBubble';
      loadingBubble.innerHTML = `<span class="text-emerald-700 dark:text-emerald-400 font-bold block text-[10px] uppercase font-mono mb-1"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Gemini Copilot Thinking...</span>Synthesizing live farm database & pharmacology data...`;
      chatHistory.appendChild(loadingBubble);
      chatHistory.scrollTop = chatHistory.scrollHeight;

      try {
        const formData = new FormData();
        formData.append('action', 'ask_ai_ajax');
        formData.append('question', query);

        const response = await fetch('/?page=ai', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        loadingBubble.remove();

        const aiBubble = document.createElement('div');
        aiBubble.className = 'p-3.5 rounded-2xl bg-slate-100/90 dark:bg-slate-900/95 border border-emerald-500/30 dark:border-emerald-500/35 text-slate-800 dark:text-slate-100 text-xs mr-3 shadow-md space-y-1.5';
        aiBubble.innerHTML = `
          <div class="flex items-center justify-between text-[11px] text-emerald-700 dark:text-emerald-400 font-mono pb-1 border-b border-emerald-500/20 font-bold">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
              Farm Copilot
            </span>
            <span class="text-slate-500 dark:text-slate-400">Just now</span>
          </div>
          <div class="ai-prose space-y-1.5 leading-relaxed">${data.answer_html || data.answer || data.message_html || data.message}</div>
        `;
        chatHistory.appendChild(aiBubble);
        chatHistory.scrollTop = chatHistory.scrollHeight;
      } catch (err) {
        loadingBubble.remove();
        const errBubble = document.createElement('div');
        errBubble.className = 'p-3 rounded-2xl bg-rose-950/40 border border-rose-500/20 text-rose-200 text-xs mr-4';
        errBubble.innerHTML = `AI request failed: ${err.message}. Please verify Gemini API settings.`;
        chatHistory.appendChild(errBubble);
      }
    }

    // Window Onload Initialization
    window.addEventListener('DOMContentLoaded', function() {
      initClock();
      initTheme();
      initCharts();
      updatePricingSimulation();
    });

    // Realtime Clock
    function initClock() {
      const clockEl = document.getElementById('realtimeClock');
      function update() {
        const now = new Date();
        clockEl.innerText = now.toLocaleTimeString('en-US', { hour12: false }) + ' WAT';
      }
      setInterval(update, 1000);
      update();
    }

    // THEME SWITCHER LOGIC
    function initTheme() {
      const savedTheme = localStorage.getItem('aurapoultry_theme');
      if (savedTheme === 'light') {
        setTheme(false);
      } else {
        setTheme(true);
      }
    }

    function toggleTheme() {
      setTheme(!state.isDark);
    }

    function setTheme(dark) {
      state.isDark = dark;
      const htmlEl = document.documentElement;
      const iconContainer = document.getElementById('themeIconContainer');
      const label = document.getElementById('themeTextLabel');

      if (dark) {
        htmlEl.classList.add('dark');
        if (iconContainer) iconContainer.innerHTML = '<i class="fa-solid fa-moon text-indigo-400 text-sm"></i>';
        if (label) label.innerText = 'Dark';
        localStorage.setItem('aurapoultry_theme', 'dark');
      } else {
        htmlEl.classList.remove('dark');
        if (iconContainer) iconContainer.innerHTML = '<i class="fa-solid fa-sun text-amber-500 text-sm"></i>';
        if (label) label.innerText = 'Light';
        localStorage.setItem('aurapoultry_theme', 'light');
      }

      if (typeof updateChartsTheme === 'function') updateChartsTheme();
    }

    // Tab Switcher
    function switchTab(tabId) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('tab-btn-active');
        btn.classList.add('tab-btn-inactive');
      });

      const selectedContent = document.getElementById(tabId);
      const selectedBtn = document.getElementById('tabBtn-' + tabId);

      if (selectedContent) selectedContent.classList.remove('hidden');
      if (selectedBtn) {
        selectedBtn.classList.remove('tab-btn-inactive');
        selectedBtn.classList.add('tab-btn-active');
      }
    }

    // Sidebar Control (Mobile)
    function toggleSidebar(forceState) {
      const sidebar = document.getElementById('appSidebar');
      const backdrop = document.getElementById('sidebarBackdrop');
      if (!sidebar || !backdrop) return;
      const isClosed = sidebar.classList.contains('-translate-x-full');
      const shouldOpen = typeof forceState === 'boolean' ? forceState : isClosed;
      if (shouldOpen) {
        sidebar.classList.remove('-translate-x-full');
        backdrop.classList.remove('hidden');
      } else {
        sidebar.classList.add('-translate-x-full');
        backdrop.classList.add('hidden');
      }
    }

    // Transfer Modal Live Cost & Suggested Selling Price
    function updateTransferPricingPreview() {
      const sel = document.getElementById('transferIncubationSelect');
      if (!sel || sel.selectedIndex < 0) return;
      const opt = sel.options[sel.selectedIndex];
      const chicks = parseInt(opt.dataset.chicks || '0', 10);
      const totalSpent = parseFloat(opt.dataset.totalCost || '0');
      const costPerChick = parseFloat(opt.dataset.costPerChick || '0');
      const suggested = Math.round(costPerChick * 1.30 * 100) / 100;

      const fmt = (n) => '₦' + n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const chicksEl = document.getElementById('transferChicksBadge');
      const totalEl = document.getElementById('transferTotalSpent');
      const cpcEl = document.getElementById('transferCostPerChick');
      const sugEl = document.getElementById('transferSuggestedPrice');
      const inputEl = document.getElementById('transferSellingPriceInput');

      if (chicksEl) chicksEl.textContent = chicks.toLocaleString('en-NG') + ' chicks';
      if (totalEl) totalEl.textContent = fmt(totalSpent);
      if (cpcEl) cpcEl.textContent = fmt(costPerChick);
      if (sugEl) sugEl.textContent = fmt(suggested);
      if (inputEl) inputEl.value = suggested > 0 ? suggested.toFixed(2) : '';
    }

    function syncModalSalePrice() {
      const sel = document.getElementById('modalSaleBatchSelect');
      const priceInput = document.getElementById('modalSaleUnitPrice');
      if (!sel || !priceInput || sel.selectedIndex < 0) return;
      const recPrice = parseFloat(sel.options[sel.selectedIndex].dataset.recPrice || '0');
      if (recPrice > 0) {
        priceInput.value = recPrice.toFixed(2);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      updateTransferPricingPreview();
      syncModalSalePrice();
    });

    // Modal Control Functions
    function openModal(id) {
      const modal = document.getElementById(id);
      const box = document.getElementById(id + 'Box');
      if (!modal || !box) return;
      modal.classList.remove('hidden');
      setTimeout(() => {
        modal.classList.remove('opacity-0');
        box.classList.remove('scale-95');
        box.classList.add('scale-100');
      }, 10);
    }

    function closeModal(id) {
      const modal = document.getElementById(id);
      const box = document.getElementById(id + 'Box');
      if (!modal || !box) return;
      modal.classList.add('opacity-0');
      box.classList.remove('scale-100');
      box.classList.add('scale-95');
      setTimeout(() => {
        modal.classList.add('hidden');
      }, 300);
    }

    // Toast Notification helper
    function showToast(text, isError = false) {
      const toast = document.getElementById('toastMessage');
      const toastText = document.getElementById('toastText');
      const toastIcon = document.getElementById('toastIcon');

      toastText.innerText = text;
      if (isError) {
        toast.classList.remove('border-emerald-500/50');
        toast.classList.add('border-rose-500/50');
        toastIcon.className = 'fa-solid fa-triangle-exclamation text-rose-400 text-base';
      } else {
        toast.classList.remove('border-rose-500/50');
        toast.classList.add('border-emerald-500/50');
        toastIcon.className = 'fa-solid fa-circle-check text-emerald-400 text-base';
      }

      toast.classList.remove('translate-y-20', 'opacity-0');
      setTimeout(() => {
        toast.classList.add('translate-y-20', 'opacity-0');
      }, 3500);
    }

    // Chart.js Visualizations
    function initCharts() {
      const isDark = state.isDark;
      const textColor = isDark ? '#cbd5e1' : '#334155';
      const borderColor = isDark ? '#08101c' : '#ffffff';

      // 1. Cost Breakdown Donut Chart
      const donutCanvas = document.getElementById('costBreakdownChart');
      if (donutCanvas) {
        const ctxDonut = donutCanvas.getContext('2d');
        costBreakdownChart = new Chart(ctxDonut, {
          type: 'doughnut',
          data: {
            labels: ['Feed Intake', 'Incubation Base', 'Barn Rent (Lump-Sum)', 'Medication & Sanit.', 'Labor'],
            datasets: [{
              data: [2057100, 580000, 450000, 191400, 150000],
              backgroundColor: ['#10b981', '#06b6d4', '#f59e0b', '#a855f7', '#f43f5e'],
              borderWidth: 2,
              borderColor: borderColor,
              hoverOffset: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'right',
                labels: {
                  color: textColor,
                  font: { family: '"JetBrains Mono"', size: 11 },
                  boxWidth: 12,
                  padding: 14
                }
              },
              tooltip: {
                callbacks: {
                  label: function(item) {
                    const val = item.raw;
                    return ` ₦ ${val.toLocaleString()} (${((val / 3428500) * 100).toFixed(1)}%)`;
                  }
                }
              }
            },
            cutout: '72%'
          }
        });
      }

      // 2. Candling Diagnostics Bar Chart
      const candleCanvas = document.getElementById('candlingChart');
      if (candleCanvas) {
        const ctxCandle = candleCanvas.getContext('2d');
        candlingChart = new Chart(ctxCandle, {
          type: 'bar',
          data: {
            labels: ['Clears', 'Early Dead', 'Cracked', 'DIS'],
            datasets: [{
              label: 'Eggs Discarded',
              data: [477, 250, 65, 142],
              backgroundColor: ['#f59e0b', '#f43f5e', '#64748b', '#ef4444'],
              borderRadius: 6
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              x: {
                ticks: { color: textColor, font: { family: '"JetBrains Mono"', size: 10 } },
                grid: { display: false }
              },
              y: {
                ticks: { color: textColor, font: { family: '"JetBrains Mono"', size: 10 } },
                grid: { color: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)' }
              }
            }
          }
        });
      }
    }

    function updateChartsTheme() {
      if (!costBreakdownChart || !candlingChart) return;
      const isDark = state.isDark;
      const textColor = isDark ? '#cbd5e1' : '#334155';
      const borderColor = isDark ? '#08101c' : '#ffffff';
      const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

      costBreakdownChart.options.plugins.legend.labels.color = textColor;
      costBreakdownChart.data.datasets[0].borderColor = borderColor;
      costBreakdownChart.update();

      candlingChart.options.scales.x.ticks.color = textColor;
      candlingChart.options.scales.y.ticks.color = textColor;
      candlingChart.options.scales.y.grid.color = gridColor;
      candlingChart.update();
    }

    // Pricing Simulation Engine
    function updatePricingSimulation() {
      const marginInput = document.getElementById('marginSlider');
      const simFeedInput = document.getElementById('simFeedGrams');
      const simMortInput = document.getElementById('simMortality');
      if (!marginInput) return;

      const marginPct = parseFloat(marginInput.value) || 35;
      const extraFeedPerBird = parseFloat(simFeedInput?.value) || 0;
      const extraMort = parseFloat(simMortInput?.value) || 0;

      const sliderLabel = document.getElementById('sliderMarginValue');
      if (sliderLabel) sliderLabel.innerText = marginPct + '%';

      const simulatedLiveBirds = Math.max(1, state.liveHeadcount - extraMort);
      const addedFeedCost = extraFeedPerBird * simulatedLiveBirds * 0.42;
      const totalAccumulated = state.accumulatedExpenses + addedFeedCost;
      const breakevenPerBird = totalAccumulated / simulatedLiveBirds;
      const recPricePerBird = breakevenPerBird * (1 + (marginPct / 100));
      const profitPerBird = recPricePerBird - breakevenPerBird;
      const projectedGross = recPricePerBird * simulatedLiveBirds;
      const projectedNet = projectedGross - totalAccumulated;

      const setEl = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
      setEl('simLiveBirds', simulatedLiveBirds.toLocaleString() + ' birds');
      setEl('simUnitCost', '₦ ' + breakevenPerBird.toFixed(2));
      setEl('simProfitPerBird', '+₦ ' + profitPerBird.toFixed(2));
      setEl('simRecPrice', '₦ ' + recPricePerBird.toFixed(2));
      setEl('simGrossRevenue', '₦ ' + Math.round(projectedGross).toLocaleString());
      setEl('simNetProfit', '₦ ' + Math.round(projectedNet).toLocaleString());

      setEl('kpiRecPrice', '₦ ' + recPricePerBird.toFixed(2));
      setEl('kpiMarginBadge', `+${marginPct}% Margin`);
      setEl('kpiProjectedProfit', '₦ ' + Math.round(projectedNet).toLocaleString());
    }

    function applySimulatedPricing() {
      const margin = document.getElementById('marginSlider').value;
      const price = document.getElementById('simRecPrice').innerText;
      showToast(`Batch target margin locked at ${margin}%. Rec Price: ${price}`);
    }

    function switchBatch(batchCode) {
      showToast('Switched flock context to: ' + batchCode);
      const titleEl = document.getElementById('activeBatchTitle');
      if (titleEl) {
        titleEl.innerText = 'Batch #' + batchCode;
      }
    }

    // Clinical & Operational Reminders Drop Engine
    function toggleRemindersDropdown() {
      const panel = document.getElementById('remindersDropdown');
      if (!panel) return;
      panel.classList.toggle('hidden');
    }

    function askAiAboutReminder(topic) {
      if (!state.isAiDrawerOpen) {
        toggleAiDrawer();
      }
      const prompt = "Please explain the clinical details, water preparation, and dosage for: " + topic;
      processAiPrompt(prompt);
      const panel = document.getElementById('remindersDropdown');
      if (panel) panel.classList.add('hidden');
    }

    function showReminderDropToast(title, desc, reminderId) {
      const toast = document.getElementById('reminderDropToast');
      if (!toast) return;
      if (reminderId) toast.dataset.reminderId = reminderId;
      if (title) document.getElementById('reminderToastTitle').innerText = title;
      if (desc) document.getElementById('reminderToastText').innerText = desc;
      toast.classList.remove('-translate-y-48', 'opacity-0', 'pointer-events-none');
      toast.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
    }

    function dismissReminderToast() {
      const toast = document.getElementById('reminderDropToast');
      if (!toast) return;
      toast.classList.add('-translate-y-48', 'opacity-0', 'pointer-events-none');
      toast.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
    }

    function updateRemindersCountUI(remaining) {
      const badge = document.getElementById('remindersBadgeCount');
      const pill = document.getElementById('remindersActivePill');
      if (pill) pill.innerText = remaining + ' Unread';
      if (badge) {
        if (remaining > 0) {
          badge.innerText = remaining;
          badge.classList.remove('hidden');
        } else {
          badge.classList.add('hidden');
        }
      }
    }

    function styleCardAsRead(reminderId) {
      const card = document.getElementById('reminderCard-' + reminderId);
      if (card) card.classList.add('opacity-65');
      const btn = document.getElementById('markReadBtn-' + reminderId);
      if (btn) {
        const badge = document.createElement('span');
        badge.className = 'px-2 py-1 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-semibold text-[10px] font-mono border border-emerald-500/25';
        badge.innerHTML = '<i class="fa-solid fa-check mr-0.5"></i> Read';
        btn.replaceWith(badge);
      }
    }

    async function markReminderRead(reminderId) {
      if (!reminderId) return;
      try {
        const form = new FormData();
        form.append('action', 'mark_reminder_read');
        form.append('reminder_id', reminderId);
        const res = await fetch('/?page={{ $currentPage ?? 'dashboard' }}', { method: 'POST', body: form });
        const data = await res.json();
        styleCardAsRead(reminderId);
        updateRemindersCountUI(data.remaining ?? 0);
      } catch (e) {
        console.error('Failed to mark reminder read:', e);
      }
    }

    async function markAllRemindersRead() {
      try {
        const form = new FormData();
        form.append('action', 'mark_all_reminders_read');
        await fetch('/?page={{ $currentPage ?? 'dashboard' }}', { method: 'POST', body: form });
        document.querySelectorAll('.reminder-card-item').forEach((card) => {
          const id = card.id.replace('reminderCard-', '');
          styleCardAsRead(id);
        });
        updateRemindersCountUI(0);
        dismissReminderToast();
      } catch (e) {
        console.error('Failed to mark all reminders read:', e);
      }
    }

    function markToastReminderRead() {
      const toast = document.getElementById('reminderDropToast');
      const remId = toast ? toast.dataset.reminderId : '';
      dismissReminderToast();
      if (remId) markReminderRead(remId);
    }

    function refreshPushButtonLabel() {
      const label = document.getElementById('pushNotifyLabel');
      const btn = document.getElementById('pushNotifyToggleBtn');
      if (!label || !btn || !('Notification' in window)) return;
      if (Notification.permission === 'granted') {
        label.innerText = 'Notification Bar Alerts Active';
        btn.classList.remove('border-cyan-500/30', 'bg-cyan-500/10', 'text-cyan-700', 'dark:text-cyan-300');
        btn.classList.add('border-emerald-500/30', 'bg-emerald-500/15', 'text-emerald-700', 'dark:text-emerald-300');
      } else if (Notification.permission === 'denied') {
        label.innerText = 'Notifications Blocked in Browser';
      } else {
        label.innerText = 'Enable Notification Bar Alerts';
      }
    }

    async function toggleBrowserNotifications() {
      if (!('Notification' in window)) {
        showToast('Browser notifications are not supported on this device.', true);
        return;
      }
      if (Notification.permission === 'default') {
        const perm = await Notification.requestPermission();
        refreshPushButtonLabel();
        if (perm === 'granted') {
          showToast('Notification bar alerts enabled!');
          dispatchDuePushNotifications(true);
        }
      } else if (Notification.permission === 'granted') {
        dispatchDuePushNotifications(true);
        showToast('Notification bar alerts are active.');
      } else {
        showToast('Please allow notifications in your browser site settings.', true);
      }
    }

    function dispatchDuePushNotifications(forceNow = false) {
      if (!('Notification' in window) || Notification.permission !== 'granted') return;
      @php
        $duePushItems = array_values(array_filter($reminders ?? [], fn($r) => !empty($r['is_due_today']) && empty($r['is_read'])));
      @endphp
      const dueItems = {!! json_encode(array_slice($duePushItems, 0, 3)) !!};
      const todayKey = new Date().toISOString().slice(0, 10);

      dueItems.forEach((item) => {
        const storageKey = 'aurapoultry_push_' + todayKey + '_' + item.id;
        if (!forceNow && localStorage.getItem(storageKey)) return;
        const n = new Notification('Blackswan Farms • ' + item.flock_code + ' (' + (item.batch_name || item.species) + ')', {
          body: item.title + ' — ' + item.purpose,
          tag: item.id
        });
        n.onclick = () => {
          window.focus();
          const panel = document.getElementById('remindersDropdown');
          if (panel) panel.classList.remove('hidden');
          n.close();
        };
        localStorage.setItem(storageKey, '1');
      });
    }

    // Auto-close reminders drop on outside click
    document.addEventListener('click', function(e) {
      const container = document.getElementById('remindersDropdownContainer');
      const panel = document.getElementById('remindersDropdown');
      if (container && panel && !container.contains(e.target)) {
        panel.classList.add('hidden');
      }
    });

    // Auto-slide down reminder drop toast on load if any items are due today
    document.addEventListener('DOMContentLoaded', function() {
      refreshPushButtonLabel();
      dispatchDuePushNotifications(false);
      @php
        $dueTodayList = array_values(array_filter($reminders ?? [], fn($r) => !empty($r['is_due_today']) && empty($r['is_read'])));
        $firstDue = $dueTodayList[0] ?? null;
      @endphp
      @if ($firstDue)
        setTimeout(() => {
          showReminderDropToast(
            {!! json_encode($firstDue['title']) !!},
            {!! json_encode('Batch ' . $firstDue['flock_code'] . ' (' . ($firstDue['batch_name'] ?? $firstDue['species']) . '): ' . $firstDue['purpose']) !!},
            {!! json_encode($firstDue['id']) !!}
          );
        }, 1500);
      @endif
    });

    // PWA Service Worker & Install Prompt
    let deferredPwaPrompt = null;
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPwaPrompt = e;
      const btn = document.getElementById('pwaInstallBtn');
      if (btn) btn.classList.remove('hidden');
    });
    async function triggerPwaInstall() {
      if (!deferredPwaPrompt) return;
      deferredPwaPrompt.prompt();
      await deferredPwaPrompt.userChoice;
      deferredPwaPrompt = null;
      const btn = document.getElementById('pwaInstallBtn');
      if (btn) btn.classList.add('hidden');
    }
  </script>

  <!-- Change Farm Manager Password Modal -->
  <div id="changePasswordModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div id="changePasswordModalBox" class="glass-card rounded-3xl w-full max-w-md p-6 shadow-2xl border border-slate-200/80 dark:border-slate-700/80">
      <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800/80 pb-3 mb-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-key text-amber-500"></i> Change Manager Password
        </h3>
        <button type="button" onclick="closeModal('changePasswordModal')" class="text-slate-400 hover:text-slate-700 dark:hover:text-white cursor-pointer">
          <i class="fa-solid fa-xmark text-lg"></i>
        </button>
      </div>
      <form method="POST" action="/?page={{ $currentPage ?? 'dashboard' }}" class="space-y-3.5">
        <input type="hidden" name="action" value="change_manager_password">
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Current Password</label>
          <input type="password" name="current_password" required
                 class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">New Password (min 6 chars)</label>
          <input type="password" name="new_password" minlength="6" required
                 class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600 dark:text-slate-300 mb-1">Confirm New Password</label>
          <input type="password" name="confirm_password" minlength="6" required
                 class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
        </div>
        <div class="pt-2 flex items-center justify-end gap-2">
          <button type="button" onclick="closeModal('changePasswordModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold cursor-pointer">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold cursor-pointer">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
