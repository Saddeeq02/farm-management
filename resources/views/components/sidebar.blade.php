<aside class="lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:w-64 glass-sidebar text-slate-300 flex flex-col justify-between">
    <!-- Top Branding & Navigation -->
    <div class="flex-1 overflow-y-auto">
        <!-- Luxury Brand Header -->
        <div class="h-18 px-5 flex items-center justify-between border-b border-white/8">
            <a href="/?page=dashboard" class="flex items-center gap-3 group">
                <!-- Glowing Crest Icon -->
                <div class="relative">
                    <div class="absolute -inset-1 rounded-xl bg-emerald-500/30 blur-sm group-hover:bg-emerald-500/50 transition"></div>
                    <div class="relative h-9 w-9 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-700 flex items-center justify-center text-white shadow-lg ring-1 ring-white/20">
                        <!-- Feather Leaf / Agro Icon -->
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3c-4.5 4.5-6.5 8-6.5 12a6.5 6.5 0 0 0 13 0c0-4-2-7.5-6.5-12Z" />
                            <path d="M12 3v18" />
                        </svg>
                    </div>
                </div>
                <div>
                    <span class="block text-sm font-bold text-white tracking-tight leading-snug">Aurelia Poultry</span>
                    <span class="block text-[10px] text-emerald-400 font-medium tracking-wide">Enterprise Farm ERP</span>
                </div>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="px-3.5 py-5 space-y-5">
            <!-- Section 1: Overview -->
            <div class="space-y-1">
                <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Operations
                </div>

                <a href="/?page=dashboard"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'dashboard' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5">
                        <svg class="w-4.5 h-4.5 {{ $currentPage === 'dashboard' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300' }} transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="7" height="9" x="3" y="3" rx="1" />
                            <rect width="7" height="5" x="14" y="3" rx="1" />
                            <rect width="7" height="9" x="14" y="12" rx="1" />
                            <rect width="7" height="5" x="3" y="16" rx="1" />
                        </svg>
                        <span>Operations Hub</span>
                    </span>
                    @if ($currentPage === 'dashboard')
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]"></span>
                    @endif
                </a>
            </div>

            <!-- Section 2: Biological & Economic Lifecycle -->
            <div class="space-y-1">
                <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Flock Lifecycle
                </div>

                <!-- 1. Incubation -->
                <a href="/?page=incubation"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'incubation' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4.5 h-4.5 shrink-0 {{ $currentPage === 'incubation' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300' }} transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="8" />
                            <path d="M12 4v2m0 12v2M4 12h2m12 0h2" />
                        </svg>
                        <span class="truncate">1. Incubation</span>
                    </span>
                    <span class="shrink-0 text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/5 text-slate-300 border border-white/8">
                        {{ $summary['active_incubation_count'] }}
                    </span>
                </a>

                <!-- 2. Inventory -->
                <a href="/?page=inventory"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'inventory' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4.5 h-4.5 shrink-0 {{ $currentPage === 'inventory' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300' }} transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z" />
                            <path d="m3.3 7 8.7 5 8.7-5M12 22V12" />
                        </svg>
                        <span class="truncate">2. Feed & Med Stock</span>
                    </span>
                    <span class="shrink-0 text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/5 text-slate-300 border border-white/8">
                        g/ml
                    </span>
                </a>

                <!-- 3. Telemetry -->
                <a href="/?page=production"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'production' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4.5 h-4.5 shrink-0 {{ $currentPage === 'production' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300' }} transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                        <span class="truncate">3. Flock Telemetry</span>
                    </span>
                    <span class="shrink-0 text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/5 text-slate-300 border border-white/8">
                        {{ number_format($summary['total_live_birds']) }}
                    </span>
                </a>

                <!-- 4. Pricing & Sales -->
                <a href="/?page=sales"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'sales' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4.5 h-4.5 shrink-0 {{ $currentPage === 'sales' ? 'text-emerald-400' : 'text-slate-400 group-hover:text-slate-300' }} transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" x2="12" y1="2" y2="22" />
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                        </svg>
                        <span class="truncate">4. Pricing & Sales</span>
                    </span>
                    <span class="shrink-0 text-[10px] font-mono px-2 py-0.5 rounded-md bg-white/5 text-slate-300 border border-white/8">
                        ₦ Engine
                    </span>
                </a>
            </div>

            <!-- Section 3: AI Intelligence -->
            <div class="space-y-1">
                <div class="px-3 pb-1.5 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Intelligence
                </div>

                <a href="/?page=ai"
                   class="group flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold transition-all duration-200 {{ $currentPage === 'ai' ? 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.18)]' : 'text-slate-400 hover:bg-white/5 hover:text-slate-200' }}">
                    <span class="flex items-center gap-2.5 min-w-0">
                        <svg class="w-4.5 h-4.5 shrink-0 text-emerald-400 group-hover:rotate-12 transition" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
                        </svg>
                        <span class="truncate">5. Ask Farm AI</span>
                    </span>
                    <span class="shrink-0 text-[9px] font-bold font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Gemini
                    </span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Bottom Telemetry Capsule -->
    <div class="p-3 m-3 rounded-2xl bg-white/[0.03] border border-white/8 backdrop-blur-md space-y-2 text-xs">
        <div class="flex items-center justify-between pb-1 border-b border-white/6">
            <span class="flex items-center gap-1.5 text-[11px] font-medium text-slate-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                Live Telemetry
            </span>
            <span class="text-[10px] font-mono text-emerald-400">Operational</span>
        </div>
        <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Live Birds</span>
            <span class="font-mono font-bold text-white">{{ number_format($summary['total_live_birds']) }}</span>
        </div>
        <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Feed Stock</span>
            <span class="font-mono font-bold text-emerald-400">{{ number_format($summary['total_feed_kg_remaining'], 0) }} kg</span>
        </div>
        <div class="flex items-center justify-between text-[11px]">
            <span class="text-slate-400">Eggs in Setter</span>
            <span class="font-mono font-bold text-slate-300">{{ number_format($summary['active_eggs_in_incubator']) }}</span>
        </div>
    </div>
</aside>
