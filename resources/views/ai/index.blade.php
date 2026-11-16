@extends('layouts.app')

@section('title', 'Ask Farm & Veterinary AI — Poultry Farm & Hatchery Management System')
@section('page_heading', 'Farm & Veterinary AI Copilot')
@section('page_subheading', 'Pre-configured in the backend with live access to your uploaded medications, flock telemetry, hatchery batches, and pricing engine.')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left 2/3: Interactive Farm AI Chat Workspace -->
    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/90 shadow-xs flex flex-col h-[700px] overflow-hidden">
        <!-- Chat Top Bar -->
        <div class="px-6 py-4 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold">Farm & Veterinary AI Assistant</h2>
                        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            Active in Backend
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">Connected to all 8 farm tables + live uploaded medication inventory</p>
                </div>
            </div>

            <form method="POST" action="/?page=ai">
                @csrf
                <input type="hidden" name="action" value="clear_ai_chat">
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium transition cursor-pointer">
                    Clear Chat
                </button>
            </form>
        </div>

        <!-- Messages Feed -->
        <div id="aiPageMessages" class="flex-1 p-6 overflow-y-auto space-y-4 bg-slate-50/60">
            @foreach ($aiChatHistory as $msg)
                @if ($msg['role'] === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[80%] rounded-2xl rounded-tr-sm bg-emerald-600 text-white px-4 py-3 text-sm leading-relaxed shadow-xs">
                            {{ $msg['message'] }}
                        </div>
                    </div>
                @else
                    <div class="flex justify-start">
                        <div class="max-w-[90%] rounded-2xl rounded-tl-sm bg-white border border-slate-200/90 px-5 py-4 text-sm text-slate-700 leading-relaxed shadow-xs space-y-2">
                            <div class="flex items-center justify-between text-xs text-slate-400 pb-1.5 border-b border-slate-100">
                                <span class="font-semibold text-emerald-700">{{ $msg['engine'] ?? 'Farm & Vet AI' }}</span>
                                <span>{{ $msg['created_at'] ?? '' }}</span>
                            </div>
                            <div class="space-y-1 text-xs sm:text-sm">{!! $msg['message_html'] !!}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Chat Input Bar -->
        <form method="POST" action="/?page=ai" class="p-4 bg-white border-t border-slate-200 flex items-center gap-3">
            @csrf
            <input type="hidden" name="action" value="ask_ai_question">
            <input type="text"
                   name="question"
                   id="aiPageInput"
                   placeholder="Ask about any uploaded medicine, flock symptom, batch cost, or selling price..."
                   required
                   class="flex-1 rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-emerald-600 focus:outline-none">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-xs transition cursor-pointer">
                Send
            </button>
        </form>
    </div>

    <!-- Right 1/3: Uploaded Medications Clinical Card + Quick Questions -->
    <div class="space-y-5">
        <!-- Uploaded Medications & Vaccines Card (Synced from Module 2 Inventory) -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-3.5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Uploaded Medications in Stock</h3>
                    <p class="text-xs text-slate-500">Click any uploaded medication for instant AI clinical & dosage advice:</p>
                </div>
                <a href="/?page=inventory" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">+ Upload Med</a>
            </div>

            <div class="space-y-2.5">
                @foreach ($inventoryItems as $item)
                    @if ($item['category'] === 'medicine')
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="text-xs font-semibold text-slate-900">{{ $item['item_name'] }}</div>
                                    <div class="text-[11px] font-mono text-slate-500 mt-0.5">
                                        Stock: {{ number_format((float)$item['total_quantity_g_or_ml']) }} {{ $item['unit_label'] }} • ₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 2) }}/{{ $item['unit_label'] }}
                                    </div>
                                </div>
                            </div>
                            <form method="POST" action="/?page=ai">
                                @csrf
                                <input type="hidden" name="action" value="ask_ai_question">
                                <input type="hidden" name="question" value="Give me full clinical advice, disease targets, mixing instructions, and exact flock dosage for {{ $item['item_name'] }}">
                                <button type="submit"
                                        class="w-full py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition cursor-pointer">
                                    Ask AI: Usage & Flock Dosage →
                                </button>
                            </form>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- One-Click Smart Questions -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-3">
            <h3 class="text-sm font-semibold text-slate-900">Quick Veterinary & Financial Questions</h3>
            <div class="space-y-2">
                @php
                    $samplePrompts = [
                        'My birds are coughing and sneezing — which uploaded medication should I give and what is the exact dose?',
                        'Tell me about all the medications I uploaded in inventory and what each one treats.',
                        'My broilers are growing slowly — give me the 6-step action plan and feed dilution rules.',
                        'Compare Newcastle Disease, Gumboro, and Coccidiosis signs and treatment plans.',
                        'What are the drug dosing formulas, withdrawal periods, and pre-vaccine rules?',
                        'How is PRD-2026-01 doing and what is the recommended selling price per bird?'
                    ];
                @endphp

                @foreach ($samplePrompts as $prompt)
                    <form method="POST" action="/?page=ai">
                        @csrf
                        <input type="hidden" name="action" value="ask_ai_question">
                        <input type="hidden" name="question" value="{{ $prompt }}">
                        <button type="submit"
                                class="w-full text-left p-3 rounded-xl border border-slate-200/90 hover:border-emerald-500 hover:bg-emerald-50/40 text-xs text-slate-700 font-medium transition cursor-pointer">
                            “{{ $prompt }}”
                        </button>
                    </form>
                @endforeach
            </div>
        </div>

        <!-- Learned Farm House Knowledge Memory -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-slate-900">Learned Farm House Memory</h3>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">
                    {{ count($aiMemories ?? []) }} Learned
                </span>
            </div>
            <p class="text-[11px] text-slate-500">
                Auto-learned from your daily logs, inventory, hatchery, sales, and chat rules—applied across Farm AI and clinical reminders.
            </p>
            <form method="POST" action="/?page=ai" class="flex gap-2">
                <input type="hidden" name="action" value="add_ai_memory">
                <input type="text" name="fact" required placeholder="Teach a rule (e.g. Pen B uses borehole water)"
                       class="flex-1 min-w-0 rounded-xl border border-slate-300 px-3 py-1.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-none">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shrink-0 cursor-pointer">
                    Teach
                </button>
            </form>
            <div class="space-y-1.5 max-h-56 overflow-y-auto pr-1">
                @forelse (array_slice(array_reverse($aiMemories ?? []), 0, 12) as $mem)
                    <div class="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
                        <div class="min-w-0">
                            <span class="inline-block text-[9px] font-mono uppercase px-1.5 py-0.5 rounded bg-slate-200 text-slate-700 font-bold mb-1">
                                {{ $mem['category'] ?? 'rule' }}
                            </span>
                            <p class="text-slate-700 leading-snug break-words">{{ $mem['fact'] }}</p>
                        </div>
                        <form method="POST" action="/?page=ai" class="shrink-0">
                            <input type="hidden" name="action" value="delete_ai_memory">
                            <input type="hidden" name="memory_id" value="{{ $mem['id'] }}">
                            <button type="submit" title="Remove learned rule" class="text-slate-400 hover:text-rose-600 p-1 cursor-pointer">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-2">No farm memories recorded yet. Teach a rule above or log farm activity.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
