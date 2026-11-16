<header class="sticky top-0 z-20 h-18 glass-nav px-6 sm:px-8 flex items-center justify-between gap-4">
    <div class="flex items-center gap-3 min-w-0">
        <div>
            <h1 class="text-sm sm:text-base font-bold text-white tracking-tight truncate">
                @yield('page_heading', 'Executive Operations Hub')
            </h1>
            <p class="text-xs text-slate-400 hidden sm:block truncate">
                @yield('page_subheading', 'Real-time flock lifecycle stages, production movement, growth targets, and operational alerts.')
            </p>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <!-- Live Flock Status Pill -->
        <div class="hidden sm:flex items-center gap-2.5 px-3.5 py-1.5 rounded-full glass-card text-xs">
            <span class="flex h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-slate-200 font-semibold">{{ number_format($summary['total_live_birds']) }} Birds Active</span>
            <span class="text-slate-400">•</span>
            <span class="text-slate-300">{{ $summary['active_incubation_count'] }} in Setter</span>
            @if ($summary['ready_to_move_count'] > 0)
                <span class="text-slate-400">•</span>
                <span class="font-bold text-amber-300 bg-amber-500/20 px-2 py-0.5 rounded-full border border-amber-500/30">{{ $summary['ready_to_move_count'] }} Ready to Move</span>
            @endif
        </div>

        <!-- AI Assistant Slide-over Trigger -->
        <button type="button"
                onclick="toggleAiDrawer(true)"
                class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-semibold shadow-[0_0_20px_rgba(16,185,129,0.25)] border border-white/20 transition cursor-pointer">
            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
            </svg>
            <span>Ask Farm AI</span>
        </button>
    </div>
</header>
