<!-- Executive Slide-Over AI Copilot Drawer (Dark Obsidian Glass) -->
<div id="aiDrawerBackdrop"
     onclick="toggleAiDrawer(false)"
     class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm hidden transition-opacity"></div>

<aside id="aiDrawerPanel"
       class="fixed inset-y-0 right-0 z-50 w-full max-w-lg bg-[#0B0F19]/95 backdrop-blur-2xl border-l border-white/10 shadow-2xl flex flex-col translate-x-full transition-transform duration-250 ease-out text-slate-200">
    <!-- Header -->
    <div class="px-6 py-4.5 border-b border-white/10 bg-[#070b14]/90 text-white flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-700 flex items-center justify-center shadow-lg shadow-emerald-500/20 border border-white/20">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z" />
                </svg>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-semibold text-white tracking-tight">Farm & Veterinary AI</h2>
                    <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        Gemini Active
                    </span>
                </div>
                <p class="text-[11px] text-slate-400">Live access to batches, financial ledger & medication inventory</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="/?page=ai" class="text-xs text-slate-300 hover:text-white font-medium px-2 py-1 rounded-md hover:bg-white/10 transition">
                Full View
            </a>
            <button type="button" onclick="toggleAiDrawer(false)" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition cursor-pointer">
                ✕
            </button>
        </div>
    </div>

    <!-- Quick Prompt Pills -->
    <div class="px-5 py-3 bg-white/[0.02] border-b border-white/8 flex flex-wrap gap-1.5">
        <button type="button" onclick="askFarmAiQuick('Tell me about the medications I uploaded and their dosage for my flocks')"
                class="text-[11px] font-medium px-3 py-1 rounded-full bg-white/[0.04] border border-white/10 text-slate-300 hover:border-emerald-500/50 hover:text-white transition cursor-pointer">
            Uploaded Meds & Dosage
        </button>
        <button type="button" onclick="askFarmAiQuick('What do I need to invest in for the next month?')"
                class="text-[11px] font-medium px-3 py-1 rounded-full bg-white/[0.04] border border-white/10 text-slate-300 hover:border-emerald-500/50 hover:text-white transition cursor-pointer">
            Next Month Investment
        </button>
        <button type="button" onclick="askFarmAiQuick('How is PRD-2026-01 doing and what is the recommended selling price?')"
                class="text-[11px] font-medium px-3 py-1 rounded-full bg-white/[0.04] border border-white/10 text-slate-300 hover:border-emerald-500/50 hover:text-white transition cursor-pointer">
            Cost/Bird & Pricing
        </button>
        <button type="button" onclick="askFarmAiQuick('What if 25 birds die tomorrow in PRD-2026-01?')"
                class="text-[11px] font-medium px-3 py-1 rounded-full bg-white/[0.04] border border-white/10 text-slate-300 hover:border-emerald-500/50 hover:text-white transition cursor-pointer">
            What-If Mortality
        </button>
    </div>

    <!-- Messages Container -->
    <div id="aiDrawerMessages" class="flex-1 p-5 overflow-y-auto space-y-4 bg-black/20">
        @foreach ($aiChatHistory as $msg)
            @if ($msg['role'] === 'user')
                <div class="flex justify-end">
                    <div class="max-w-[85%] rounded-2xl rounded-tr-xs bg-emerald-600/30 border border-emerald-500/40 text-emerald-100 px-4 py-2.5 text-xs sm:text-sm leading-relaxed shadow-lg">
                        {{ $msg['message'] }}
                    </div>
                </div>
            @else
                <div class="flex justify-start">
                    <div class="max-w-[94%] rounded-2xl rounded-tl-xs bg-white/[0.05] border border-white/10 px-4 py-3.5 text-xs sm:text-sm text-slate-200 leading-relaxed shadow-lg space-y-1.5">
                        <div class="flex items-center justify-between text-[10px] text-slate-400 pb-1.5 border-b border-white/8">
                            <span class="font-semibold text-emerald-400">Google Gemini</span>
                            <span>{{ $msg['created_at'] ?? '' }}</span>
                        </div>
                        <div class="space-y-1 text-slate-200">{!! $msg['message_html'] !!}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Input Form -->
    <form id="aiDrawerForm" onsubmit="submitAiDrawerQuestion(event)" class="p-4 bg-[#070b14]/90 border-t border-white/10 flex items-center gap-2.5">
        <input type="text"
               id="aiDrawerInput"
               placeholder="Ask anything about batches, uploaded meds, costs, or feed..."
               class="flex-1 rounded-xl bg-white/[0.05] border border-white/15 px-4 py-2.5 text-xs sm:text-sm text-white placeholder-slate-500 focus:border-emerald-500 focus:outline-none transition">
        <button type="submit"
                id="aiDrawerSubmitBtn"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs sm:text-sm font-semibold transition cursor-pointer shadow-[0_0_20px_rgba(16,185,129,0.25)] border border-white/10">
            Send
        </button>
    </form>
</aside>
