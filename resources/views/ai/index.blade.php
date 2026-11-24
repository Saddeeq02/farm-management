@extends('layouts.app')

@section('title', 'Ask Farm & Veterinary AI — Blackswan Farms and General Enterprises')
@section('page_heading', 'Farm & Veterinary AI Copilot')
@section('page_subheading', 'Live access to your uploaded medications, active flocks, hatchery batches, and pricing engine.')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left 2/3: Interactive Farm AI Chat Workspace -->
    <div class="lg:col-span-2 glass-card rounded-2xl shadow-lg flex flex-col h-[680px] overflow-hidden">
        <!-- Chat Top Bar -->
        <div class="px-5 py-4 bg-slate-900/90 text-white border-b border-slate-700/70 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-slate-950 flex items-center justify-center shadow-md shadow-emerald-500/25">
                    <i class="fa-solid fa-robot text-sm"></i>
                </div>
                <div>
                    @php
                        $hasGeminiKey = !empty($geminiApiKey) || !empty(getenv('GEMINI_API_KEY'));
                        $activeGeminiModel = (!empty($geminiModel) && $geminiModel !== 'gemini-3.8-flash') ? $geminiModel : 'gemini-2.5-flash';
                    @endphp
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-bold text-white">Farm &amp; Veterinary AI Assistant</h2>
                        @if ($hasGeminiKey)
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-semibold">
                                Live Gemini ({{ $activeGeminiModel }})
                            </span>
                        @else
                            <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold">
                                Offline Mode • Add Key →
                            </span>
                        @endif
                    </div>
                    <p class="text-[11px] text-slate-400">Connected to your live flocks, medications, hatchery, and pricing</p>
                </div>
            </div>

            <form method="POST" action="/?page=ai">
                @csrf
                <input type="hidden" name="action" value="clear_ai_chat">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-mono font-medium border border-slate-700 transition cursor-pointer">
                    Clear Chat
                </button>
            </form>
        </div>

        <!-- Messages Feed -->
        <div id="aiPageMessages" class="flex-1 p-5 overflow-y-auto space-y-4">
            @foreach ($aiChatHistory as $msg)
                @if ($msg['role'] === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[82%] rounded-2xl rounded-tr-sm bg-emerald-600 text-white px-4 py-3 text-xs sm:text-sm leading-relaxed shadow-sm">
                            {{ $msg['message'] }}
                        </div>
                    </div>
                @else
                    <div class="flex justify-start">
                        <div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 px-5 py-4 text-xs sm:text-sm text-slate-800 dark:text-slate-100 leading-relaxed shadow-sm space-y-2">
                            <div class="flex items-center justify-between text-[11px] font-mono text-slate-400 pb-1.5 border-b border-slate-200/70 dark:border-slate-800">
                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $msg['engine'] ?? 'Farm & Vet AI' }}</span>
                                <span>{{ $msg['created_at'] ?? '' }}</span>
                            </div>
                            <div class="space-y-1.5">{!! $msg['message_html'] !!}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <!-- Chat Input Bar (Instant AJAX) -->
        <form id="aiPageForm" onsubmit="submitAiPageQuestion(event)" class="p-4 bg-white/80 dark:bg-slate-900/80 border-t border-slate-200 dark:border-slate-800 flex items-center gap-3">
            <input type="text"
                   name="question"
                   id="aiPageInput"
                   placeholder="Ask about any uploaded medicine, flock symptom, batch cost, or selling price..."
                   required
                   class="flex-1 min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-4 py-2.5 text-xs sm:text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:border-emerald-500 focus:outline-none">
            <button type="submit" id="aiPageSubmitBtn" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs sm:text-sm font-mono font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer shrink-0">
                Send
            </button>
        </form>
    </div>

    <!-- Right 1/3: Gemini AI Connection + Uploaded Medications + Quick Questions -->
    <div class="space-y-5">
        <!-- Connect Real Google Gemini AI -->
        <div class="glass-card rounded-2xl p-5 border {{ $hasGeminiKey ? 'border-emerald-500/35' : 'border-amber-500/40' }} space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                        <i class="fa-solid fa-sparkles {{ $hasGeminiKey ? 'text-emerald-500' : 'text-amber-500' }}"></i>
                        Google Gemini AI Brain
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ $hasGeminiKey ? 'Connected — multi-turn Gemini AI is answering with your live farm data.' : 'Paste a free Google AI Studio key to unlock full natural-language Gemini AI.' }}
                    </p>
                </div>
            </div>
            <form method="POST" action="/?page=ai" class="space-y-2.5 text-xs">
                @csrf
                <input type="hidden" name="action" value="save_ai_settings">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">
                        Gemini API Key (<a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener" class="text-emerald-600 dark:text-emerald-400 underline">Get free key</a>)
                    </label>
                    <input type="password" name="gemini_api_key" value="{{ $geminiApiKey ?? '' }}" placeholder="AIzaSy..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs font-mono text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                </div>
                <div class="flex items-center gap-2">
                    <select name="gemini_model" class="flex-1 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-mono text-slate-900 dark:text-white focus:border-emerald-500 focus:outline-none">
                        <option value="gemini-2.5-flash" {{ $activeGeminiModel === 'gemini-2.5-flash' ? 'selected' : '' }}>gemini-2.5-flash (Recommended)</option>
                        <option value="gemini-2.5-flash-lite" {{ $activeGeminiModel === 'gemini-2.5-flash-lite' ? 'selected' : '' }}>gemini-2.5-flash-lite (Fastest)</option>
                        <option value="gemini-2.5-pro" {{ $activeGeminiModel === 'gemini-2.5-pro' ? 'selected' : '' }}>gemini-2.5-pro (Deepest)</option>
                    </select>
                    <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-mono font-bold shrink-0 cursor-pointer">
                        Save
                    </button>
                </div>
            </form>
        </div>

        <!-- Uploaded Medications & Vaccines Card -->
        <div class="glass-card rounded-2xl p-5 space-y-3.5">
            <div class="flex items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Uploaded Medications in Stock</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Click any medication for instant AI dosage advice:</p>
                </div>
                <a href="/?page=inventory" class="text-[11px] font-mono font-semibold text-emerald-600 dark:text-emerald-400 hover:underline shrink-0">+ Store</a>
            </div>

            <div class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
                @php $medCount = 0; @endphp
                @foreach ($inventoryItems as $item)
                    @if ($item['category'] === 'medicine')
                        @php $medCount++; @endphp
                        <div class="p-3 rounded-xl bg-slate-100/80 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $item['item_name'] }}</div>
                                    <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5">
                                        Stock: {{ number_format((float)$item['total_quantity_g_or_ml'], 2) }} {{ $item['unit_label'] }} • ₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 2) }}/{{ $item['unit_label'] }}
                                    </div>
                                </div>
                            </div>
                            <button type="button"
                                    onclick="askAiPageQuick('Give me full clinical advice, disease targets, mixing instructions, and exact flock dosage for {{ addslashes($item['item_name']) }}')"
                                    class="w-full py-1.5 px-3 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-semibold transition cursor-pointer">
                                Ask AI: Usage &amp; Flock Dosage →
                            </button>
                        </div>
                    @endif
                @endforeach
                @if ($medCount === 0)
                    <p class="text-xs text-slate-400 italic py-2">No medications uploaded in Inventory yet.</p>
                @endif
            </div>
        </div>

        <!-- One-Click Smart Questions -->
        <div class="glass-card rounded-2xl p-5 space-y-3">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Quick Veterinary &amp; Financial Questions</h3>
            <div class="space-y-2">
                @php
                    $samplePrompts = [
                        'How are all my Noilers, Silver Lace Chicks, and Turkeys doing right now?',
                        'My birds are coughing and sneezing — which uploaded medication should I give and what is the exact dose?',
                        'Tell me about all the medications I uploaded in inventory and what each one treats.',
                        'What are the key feeding and biosecurity rules for raising Turkeys alongside Noilers?',
                        'What are the drug dosing formulas, withdrawal periods, and pre-vaccine rules?',
                    ];
                @endphp

                @foreach ($samplePrompts as $prompt)
                    <button type="button"
                            onclick="askAiPageQuick('{{ addslashes($prompt) }}')"
                            class="w-full text-left p-3 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white/60 dark:bg-slate-900/50 hover:border-emerald-500 hover:bg-emerald-500/10 text-xs text-slate-700 dark:text-slate-200 font-medium transition cursor-pointer">
                        “{{ $prompt }}”
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Learned Farm House Knowledge Memory -->
        <div class="glass-card rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Learned Farm House Memory</h3>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-semibold">
                    {{ count($aiMemories ?? []) }} Learned
                </span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Auto-learned from your daily logs, inventory, hatchery, sales, and chat rules.
            </p>
            <form method="POST" action="/?page=ai" class="flex gap-2">
                <input type="hidden" name="action" value="add_ai_memory">
                <input type="text" name="fact" required placeholder="Teach a rule (e.g. Pen B uses borehole water)"
                       class="flex-1 min-w-0 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-3 py-1.5 text-xs text-slate-800 dark:text-white focus:border-emerald-500 focus:outline-none">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold shrink-0 cursor-pointer">
                    Teach
                </button>
            </form>
            <div class="space-y-1.5 max-h-48 overflow-y-auto pr-1">
                @forelse (array_slice(array_reverse($aiMemories ?? []), 0, 12) as $mem)
                    <div class="flex items-start justify-between gap-2 p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 text-xs">
                        <div class="min-w-0">
                            <span class="inline-block text-[9px] font-mono uppercase px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold mb-1">
                                {{ $mem['category'] ?? 'rule' }}
                            </span>
                            <p class="text-slate-700 dark:text-slate-200 leading-snug break-words">{{ $mem['fact'] }}</p>
                        </div>
                        <form method="POST" action="/?page=ai" class="shrink-0">
                            <input type="hidden" name="action" value="delete_ai_memory">
                            <input type="hidden" name="memory_id" value="{{ $mem['id'] }}">
                            <button type="submit" title="Remove learned rule" class="text-slate-400 hover:text-rose-500 p-1 cursor-pointer">
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

<script>
  function askAiPageQuick(question) {
    const input = document.getElementById('aiPageInput');
    if (input) {
      input.value = question;
      submitAiPageQuestion(null);
    }
  }

  async function submitAiPageQuestion(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('aiPageInput');
    const feed = document.getElementById('aiPageMessages');
    const btn = document.getElementById('aiPageSubmitBtn');
    const query = (input ? input.value : '').trim();
    if (!query || !feed) return;

    const userWrap = document.createElement('div');
    userWrap.className = 'flex justify-end';
    const userBubble = document.createElement('div');
    userBubble.className = 'max-w-[82%] rounded-2xl rounded-tr-sm bg-emerald-600 text-white px-4 py-3 text-xs sm:text-sm leading-relaxed shadow-sm';
    userBubble.textContent = query;
    userWrap.appendChild(userBubble);
    feed.appendChild(userWrap);

    if (input) input.value = '';
    if (btn) btn.disabled = true;

    const loadingWrap = document.createElement('div');
    loadingWrap.className = 'flex justify-start';
    loadingWrap.innerHTML = `<div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 px-5 py-3 text-xs font-mono text-slate-600 dark:text-slate-300 shadow-sm"><i class="fa-solid fa-spinner fa-spin text-emerald-500 mr-2"></i>Analyzing live flocks &amp; veterinary inventory...</div>`;
    feed.appendChild(loadingWrap);
    feed.scrollTop = feed.scrollHeight;

    try {
      const formData = new FormData();
      formData.append('action', 'ask_ai_ajax');
      formData.append('question', query);

      const response = await fetch('/?page=ai', { method: 'POST', body: formData });
      const data = await response.json();
      loadingWrap.remove();

      const aiWrap = document.createElement('div');
      aiWrap.className = 'flex justify-start';
      aiWrap.innerHTML = `
        <div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-white/95 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-700/80 px-5 py-4 text-xs sm:text-sm text-slate-800 dark:text-slate-100 leading-relaxed shadow-sm space-y-2">
          <div class="flex items-center justify-between text-[11px] font-mono text-slate-400 pb-1.5 border-b border-slate-200/70 dark:border-slate-800">
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">${data.engine || 'Farm & Vet AI'}</span>
            <span>${data.timestamp || ''}</span>
          </div>
          <div class="space-y-1.5">${data.answer_html || data.answer || 'No response received.'}</div>
        </div>
      `;
      feed.appendChild(aiWrap);
    } catch (err) {
      loadingWrap.remove();
      const errWrap = document.createElement('div');
      errWrap.className = 'flex justify-start';
      errWrap.innerHTML = `<div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-rose-500/10 border border-rose-500/40 px-4 py-3 text-xs text-rose-500">Network error reaching Farm AI. Please try again.</div>`;
      feed.appendChild(errWrap);
    } finally {
      if (btn) btn.disabled = false;
      feed.scrollTop = feed.scrollHeight;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const feed = document.getElementById('aiPageMessages');
    if (feed) feed.scrollTop = feed.scrollHeight;
  });
</script>
@endsection
