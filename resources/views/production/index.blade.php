@extends('layouts.app')

@section('title', 'Module 3: Flock Production & Daily Telemetry')
@section('page_heading', 'Module 3: Flock Production & Daily Telemetry')
@section('page_subheading', 'Fixed house rent overhead, age-based feed stage alerts, daily gram/ml consumption telemetry, and live cost-per-bird attribution.')

@section('content')
<div class="space-y-6">
    <!-- Production Page Header Bar -->
    <div class="glass-card rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-500 dark:text-emerald-400">
                <i class="fa-solid fa-kiwi-bird text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Flock Production &amp; Telemetry Command Center</h2>
                <p class="text-xs text-slate-500 dark:text-slate-300">Track live flock mortality, feed &amp; medication consumption, house rent overhead, and breakeven cost per bird.</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openModal('addProductionBatchModal')" class="px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/25 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                <i class="fa-solid fa-plus text-[10px]"></i> New Direct DOC Batch
            </button>
            <a href="/?page=dashboard" class="px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-mono font-semibold border border-slate-300 dark:border-slate-700 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Age-Based Feed Transition Alert Banners for Active Batches -->
    @if (!empty($productionBatches))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        @foreach ($productionBatches as $pb)
            @php $alert = $pb['feed_alert']; @endphp
            <div class="glass-card rounded-2xl p-4 flex items-start gap-3.5 border {{ $alert['needs_transition'] ? 'border-amber-500/40 bg-amber-500/10' : 'border-emerald-500/35 bg-emerald-500/10' }}">
                <div class="h-9 w-9 shrink-0 rounded-xl flex items-center justify-center font-bold text-sm shadow-sm {{ $alert['needs_transition'] ? 'bg-amber-500 text-slate-950' : 'bg-emerald-500 text-slate-950' }}">
                    {{ $alert['needs_transition'] ? '!' : '✓' }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-mono font-bold uppercase tracking-wide px-2 py-0.5 rounded bg-white/80 dark:bg-slate-900/70 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-slate-700">
                            {{ $pb['batch_code'] }} • {{ $pb['flock_type'] }}
                        </span>
                        <span class="text-xs font-semibold {{ $alert['needs_transition'] ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                            Recommended: {{ $alert['recommended_stage'] }} Feed
                        </span>
                    </div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mt-1.5">{{ $alert['title'] }}</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 leading-relaxed">{{ $alert['message'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- Top Grid: Daily Batch Telemetry Form (2/3) + Fixed House Rent Overhead (1/3) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Daily Batch Telemetry Form -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-5">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Daily Batch Telemetry &amp; Consumption Form</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-300">Logs mortality, deducts exact grams/ml from inventory, and recalculates Live Cost per Bird.</p>
                </div>
                <span class="text-[11px] font-mono px-2.5 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-bold">
                    Auto-Ledger Attribution
                </span>
            </div>

            <form method="POST" action="/?page=production" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="add_production_daily_log">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Production Batch</label>
                        <select name="production_batch_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            @foreach ($productionBatches as $pb)
                                <option value="{{ $pb['id'] }}">{{ $pb['batch_code'] }} ({{ number_format($pb['metrics']['current_in_house_birds']) }} birds)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-rose-600 dark:text-rose-400 mb-1">Mortality Count (Birds Lost)</label>
                        <input type="number" name="mortality_count" value="0" min="0" required
                               class="w-full rounded-xl border border-rose-300 dark:border-rose-500/40 bg-rose-50/70 dark:bg-rose-950/30 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-rose-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Water Intake (Liters)</label>
                        <input type="number" step="any" name="water_liters" value="350" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <!-- Feed & Medication Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2 border-t border-slate-200/70 dark:border-slate-800/70">
                    <div class="p-3.5 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Feed Consumption (Grams)</span>
                            <span id="liveFeedCostBadge" class="text-[11px] font-mono font-semibold text-emerald-700 dark:text-emerald-400">Auto ₦/g</span>
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 dark:text-slate-400 mb-1">Select Feed Stock</label>
                            <select name="feed_item_id" id="prodFeedSelect" onchange="updateDailyTelemetryCostPreview()"
                                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                                <option value="" data-rate="0">-- No Feed Logged --</option>
                                @foreach ($inventoryItems as $item)
                                    @if (in_array($item['category'], ['starter', 'grower', 'layer'], true))
                                        <option value="{{ $item['id'] }}" data-rate="{{ $item['unit_cost_per_g_or_ml'] }}">
                                            [{{ strtoupper($item['category']) }}] {{ $item['item_name'] }} (₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 4) }}/g • {{ number_format((float)$item['total_quantity_g_or_ml']) }}g left)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] text-slate-600 dark:text-slate-400 mb-1">Weight Consumed in Grams (e.g. 150000 or 0.245)</label>
                            <input type="number" step="any" name="feed_grams" id="prodFeedGrams" value="150000" min="0"
                                   oninput="updateDailyTelemetryCostPreview()"
                                   class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Medications &amp; Vaccines (Tick &amp; Enter Dosage)</span>
                            <span id="liveMedCostBadge" class="text-[11px] font-mono font-semibold text-cyan-700 dark:text-cyan-400">Multi-Med Ready</span>
                        </div>
                        <div class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                            @php $prodMedList = array_filter($inventoryItems ?? [], fn($it) => ($it['category'] ?? '') === 'medicine'); @endphp
                            @forelse ($prodMedList as $item)
                                <div class="flex items-center justify-between gap-2 bg-white/90 dark:bg-slate-800/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700">
                                    <label class="flex items-center gap-2 min-w-0 cursor-pointer flex-1">
                                        <input type="checkbox" name="med_items[]" value="{{ $item['id'] }}"
                                               onchange="const inp = this.closest('div').querySelector('input[type=number]'); inp.disabled = !this.checked; if(!this.checked) inp.value=''; else inp.focus();"
                                               class="rounded text-cyan-500 focus:ring-cyan-500">
                                        <span class="text-[11px] font-medium text-slate-800 dark:text-slate-100 truncate">
                                            {{ $item['item_name'] }}
                                            <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">({{ number_format((float)$item['total_quantity_g_or_ml']) }}{{ $item['unit_label'] }})</span>
                                        </span>
                                    </label>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <input type="number" step="any" min="0" name="med_doses[{{ $item['id'] }}]" placeholder="Dose" disabled
                                               class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-cyan-700 dark:text-cyan-300 px-2 py-1 text-[11px] font-mono outline-none disabled:opacity-40">
                                        <span class="text-[10px] font-mono text-slate-500">{{ $item['unit_label'] }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="text-[11px] text-slate-400 italic py-1">No medications or vaccines in store yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Optional Daily Eggs Collected for this Flock -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-medium text-amber-700 dark:text-amber-300 mb-1">Good Eggs Laid Today (Optional — adds to Eggs Store)</label>
                        <input type="number" name="good_eggs" value="0" min="0"
                               class="w-full rounded-xl border border-amber-400/50 bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300 font-bold px-3 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Cracked / Broken Eggs Today (Optional)</label>
                        <input type="number" name="cracked_eggs" value="0" min="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <!-- Labor, Sanitation, Water/Other, Date & Notes -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-1">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Direct Labor Cost (₦)</label>
                        <input type="number" step="0.01" name="labor_cost" value="15000" min="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Sanitation &amp; Litter (₦)</label>
                        <input type="number" step="0.01" name="sanitation_cost" value="8000" min="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-cyan-700 dark:text-cyan-300 mb-1">Water / Other Expense (₦)</label>
                        <input type="number" step="0.01" name="water_other_cost" value="0" min="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Log Date</label>
                        <input type="date" name="log_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Clinical / House Note</label>
                        <input type="text" name="notes" placeholder="e.g. Routine feeding &amp; litter turn"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-1 flex justify-end">
                    <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer active:scale-95">
                        + Record Daily Telemetry &amp; Update Live Cost/Bird
                    </button>
                </div>
            </form>
        </div>

        <!-- General Farm Rent Summary (Shared Daily Across All Live Chickens) -->
        <div class="glass-card rounded-2xl p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">General Farm Rent</h3>
                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 font-bold">Annual ÷ 365</span>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-300 leading-relaxed">
                    Annual farm rent entered in Inventory is shared daily across all available live chickens in active batches.
                </p>

                <div class="mt-4 space-y-2.5 font-mono text-xs">
                    <div class="rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-3 flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Annual Rent Paid</span>
                        <span class="font-bold text-slate-900 dark:text-white">₦{{ number_format((float)($generalRent['annual_rent'] ?? 0), 2) }}</span>
                    </div>
                    <div class="rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-3 flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Daily Farm Rent</span>
                        <span class="font-bold text-amber-600 dark:text-amber-300">₦{{ number_format((float)($generalRent['daily_rent'] ?? 0), 2) }}/day</span>
                    </div>
                    <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/30 p-3 flex items-center justify-between">
                        <span class="text-emerald-700 dark:text-emerald-300 font-sans font-semibold">Rent / Live Chicken / Day</span>
                        <span class="font-black text-emerald-700 dark:text-emerald-300">₦{{ number_format((float)($generalRent['rent_per_bird_per_day'] ?? 0), 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-slate-200/70 dark:border-slate-800/70 space-y-2.5">
                <div class="text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
                    <span>Available Live Chickens (All)</span>
                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ number_format((int)($generalRent['total_live_birds'] ?? 0)) }} birds</span>
                </div>
                <a href="/?page=inventory" class="block w-full text-center py-2 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-white text-white dark:text-slate-950 text-xs font-mono font-bold transition">
                    Edit Annual Rent in Inventory →
                </a>
            </div>
        </div>
    </div>

    <!-- Clickable Production Batch Cards (Select a batch to view all details & expenses) -->
    @if (!empty($productionBatches))
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Select a Production Batch to View Full Details &amp; Expenses
            </h3>
            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ count($productionBatches) }} Active Batch(es)</span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach ($productionBatches as $idx => $pb)
                @php $m = $pb['metrics']; @endphp
                <button type="button"
                        id="prodBatchCard-{{ $pb['id'] }}"
                        onclick="selectProductionBatch({{ $pb['id'] }})"
                        class="prod-batch-card text-left glass-card rounded-2xl p-4 transition-all cursor-pointer hover:scale-[1.01] {{ $idx === 0 ? 'ring-2 ring-emerald-500 border-emerald-500/60' : 'border-slate-200/80 dark:border-slate-700/70 opacity-90 hover:opacity-100' }}">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-mono font-extrabold text-slate-900 dark:text-white">{{ $pb['batch_code'] }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-semibold">{{ $pb['status'] }}</span>
                        </div>
                        <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">Day {{ $pb['age_days'] }} • Wk {{ $pb['age_weeks'] }}</span>
                    </div>
                    <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 mt-1 truncate">{{ $pb['flock_type'] }}</p>
                    <div class="mt-3 grid grid-cols-3 gap-2 pt-2.5 border-t border-slate-200/70 dark:border-slate-800/70 font-mono text-[11px]">
                        <div>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400">Live Birds</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ number_format($m['current_in_house_birds']) }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400">Cost / Bird</span>
                            <span class="font-bold text-slate-900 dark:text-white">₦{{ number_format($m['cost_per_bird'], 2) }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] text-emerald-700 dark:text-emerald-300">Sell Price</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">₦{{ number_format($m['recommended_selling_price'], 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-2.5 flex items-center justify-between text-[11px]">
                        <span class="text-slate-500 dark:text-slate-400">
                            {{ !empty($pb['origin_incubation_batch']) ? 'From ' . $pb['origin_incubation_batch']['batch_code'] : 'Direct Purchase' }}
                        </span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">View Details &amp; Expenses →</span>
                    </div>
                </button>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Selected Batch Financial Attribution & Telemetry History -->
    <div id="prodBatchDetailContainer">
    @forelse ($productionBatches as $idx => $pb)
        @php $m = $pb['metrics']; @endphp
        <div id="prodBatchDetail-{{ $pb['id'] }}" class="prod-batch-detail glass-card rounded-2xl overflow-hidden {{ $idx === 0 ? '' : 'hidden' }}">
            <div class="px-6 py-4 bg-slate-100/70 dark:bg-slate-900/60 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="text-base font-mono font-bold text-slate-900 dark:text-white">{{ $pb['batch_code'] }}</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-semibold">{{ $pb['status'] }}</span>
                        @if (!empty($pb['origin_incubation_batch']))
                            <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30">
                                From {{ $pb['origin_incubation_batch']['batch_code'] }}
                            </span>
                        @endif
                        @if (($m['total_good_eggs'] ?? 0) > 0)
                            <a href="/?page=eggs" class="text-[11px] font-mono px-2 py-0.5 rounded bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30 font-bold hover:bg-amber-500/25">
                                <i class="fa-solid fa-egg mr-1"></i>{{ number_format((int) $m['total_good_eggs']) }} eggs • ₦{{ number_format((float) ($m['egg_cost_per_egg'] ?? 0), 2) }}/egg
                            </a>
                        @endif
                        <span class="text-xs text-slate-600 dark:text-slate-300">Day {{ $pb['age_days'] }} (Week {{ $pb['age_weeks'] }}) • Started {{ $pb['start_date'] }}</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $pb['flock_type'] }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-6 text-right font-mono">
                    <div>
                        <span class="text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400 block">Live Variable Cost / Bird</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-white">₦{{ number_format($m['cost_per_bird'], 2) }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ bird</span></span>
                    </div>
                    <div class="pl-5 border-l border-slate-300/70 dark:border-slate-700/70">
                        <span class="text-[11px] uppercase tracking-wider text-emerald-700 dark:text-emerald-300 block">Live Selling Price (+{{ number_format($m['target_margin_pct'], 0) }}%)</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">₦{{ number_format($m['recommended_selling_price'], 2) }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ bird</span></span>
                    </div>
                    <button type="button" onclick="openEditProdBatchModal({{ (int)$pb['id'] }})"
                            class="px-3.5 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-[11px] font-sans font-semibold flex items-center gap-1.5 shadow-sm transition cursor-pointer">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Batch
                    </button>
                    <button type="button" onclick="openBatchPnLModal({{ (int)$pb['id'] }})"
                            class="px-3.5 py-2 rounded-xl bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white text-[11px] font-sans font-semibold flex items-center gap-1.5 shadow-sm transition cursor-pointer">
                        <i class="fa-solid fa-file-invoice-dollar text-emerald-400"></i> Batch P&amp;L Report
                    </button>
                </div>
            </div>

            <!-- Formula Attribution Breakdown Strip -->
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/70 dark:divide-slate-800/70 border-b border-slate-200/80 dark:border-slate-800/80 text-xs bg-white/80 dark:bg-slate-900/45">
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Surviving Pool</span>
                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ number_format($m['surviving_biological_birds']) }} <span class="font-normal text-slate-500 dark:text-slate-400">birds</span></div>
                    <div class="text-[11px] text-rose-600 dark:text-rose-400">Init: {{ $m['initial_birds'] }} (-{{ $m['total_mortality'] }} dead)</div>
                </div>
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Incubation / DOC Base</span>
                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5">₦{{ number_format($m['incubation_cost'], 2) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">Carried Capital</div>
                </div>
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Gross Margin / Bird</span>
                    <div class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5">+₦{{ number_format((float)($m['gross_profit_per_bird'] ?? $m['profit_per_bird'] ?? 0), 2) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">
                        Fixed assets in Net Profit
                    </div>
                </div>
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Feed Consumed</span>
                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5">₦{{ number_format($m['total_feed_cost'], 2) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ \App\Core\FarmService::fmtQty((float)$m['total_feed_grams']) }}g ({{ \App\Core\FarmService::fmtQty((float)$m['total_feed_kg']) }}kg)</div>
                </div>
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Medication &amp; Vaccines</span>
                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5">₦{{ number_format($m['total_med_cost'], 2) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">{{ \App\Core\FarmService::fmtQty((float)$m['total_med_dose']) }} g/ml</div>
                </div>
                <div class="p-3.5">
                    <span class="text-[10px] font-semibold uppercase text-slate-500 dark:text-slate-400">Labor, Sanit. &amp; Other</span>
                    <div class="text-sm font-bold font-mono text-slate-900 dark:text-white mt-0.5">₦{{ number_format($m['total_labor_cost'] + $m['total_sanitation_cost'] + ($m['total_water_other_cost'] ?? 0), 2) }}</div>
                    <div class="text-[11px] text-slate-500 dark:text-slate-400">L: ₦{{ number_format($m['total_labor_cost'], 0) }} • S: ₦{{ number_format($m['total_sanitation_cost'], 0) }} • O: ₦{{ number_format($m['total_water_other_cost'] ?? 0, 0) }}</div>
                </div>
                <div class="p-3.5 bg-emerald-500/15 dark:bg-emerald-500/20">
                    <span class="text-[10px] font-semibold uppercase text-emerald-700 dark:text-emerald-300">Total Variable Flock Cost</span>
                    <div class="text-sm font-bold font-mono text-emerald-700 dark:text-emerald-300 mt-0.5">₦{{ number_format($m['total_accumulated_cost'], 2) }}</div>
                    <div class="text-[11px] text-emerald-700 dark:text-emerald-300">Absorbed Mortality: +₦{{ number_format($m['mortality_absorbed_per_bird'], 2) }}/bird</div>
                </div>
            </div>

            <!-- Daily Telemetry Logs Table -->
            <div class="p-5 overflow-x-auto bg-slate-100/40 dark:bg-slate-950/40">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-3">Daily Telemetry &amp; Financial Attribution Ledger</h4>
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/80 text-slate-500 dark:text-slate-400">
                            <th class="pb-2">Date</th>
                            <th class="pb-2 text-right">Mortality</th>
                            <th class="pb-2 text-right">Eggs</th>
                            <th class="pb-2 text-right">Water (L)</th>
                            <th class="pb-2">Feed Type &amp; Grams</th>
                            <th class="pb-2 text-right">Feed Cost</th>
                            <th class="pb-2">Medication &amp; Dose</th>
                            <th class="pb-2 text-right">Med Cost</th>
                            <th class="pb-2 text-right">Labor + Sanit. + Other</th>
                            <th class="pb-2 text-right">Daily Total</th>
                            <th class="pb-2 text-right pl-2">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 font-mono">
                        @forelse ($pb['logs'] as $log)
                            <tr class="hover:bg-white/40 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="py-2.5 text-slate-600 dark:text-slate-300">{{ $log['log_date'] }}</td>
                                <td class="py-2.5 text-right {{ (int)$log['mortality_count'] > 0 ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-slate-400 dark:text-slate-500' }}">
                                    {{ $log['mortality_count'] }}
                                </td>
                                <td class="py-2.5 text-right {{ (int)($log['good_eggs'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-300 font-semibold' : 'text-slate-400 dark:text-slate-500' }}">
                                    @if ((int)($log['good_eggs'] ?? 0) > 0)
                                        {{ number_format((int)$log['good_eggs']) }} pcs
                                        @if ((int)($log['cracked_eggs'] ?? 0) > 0)
                                            <span class="block text-[10px] text-rose-500">({{ (int)$log['cracked_eggs'] }} crk)</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="py-2.5 text-right text-slate-700 dark:text-slate-200">{{ \App\Core\FarmService::fmtQty((float)$log['water_liters']) }} L</td>
                                <td class="py-2.5 font-sans text-slate-800 dark:text-slate-100">
                                    {{ $log['feed_item_name'] }}
                                    <span class="font-mono text-slate-500 dark:text-slate-400">({{ \App\Core\FarmService::fmtQty((float)$log['feed_grams']) }}g)</span>
                                </td>
                                <td class="py-2.5 text-right text-slate-800 dark:text-slate-100">₦{{ number_format((float)$log['feed_cost_calculated'], 2) }}</td>
                                <td class="py-2.5 font-sans text-slate-800 dark:text-slate-100">
                                    {{ $log['med_item_name'] }}
                                    @if (empty($log['has_multi_meds']) && (float)$log['med_dose_g_ml'] > 0)
                                        <span class="font-mono text-slate-500 dark:text-slate-400">({{ \App\Core\FarmService::fmtQty((float)$log['med_dose_g_ml']) }}{{ $log['med_unit'] }})</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right text-slate-800 dark:text-slate-100">₦{{ number_format((float)$log['med_cost_calculated'], 2) }}</td>
                                <td class="py-2.5 text-right text-slate-700 dark:text-slate-200">₦{{ number_format((float)$log['labor_cost'] + (float)$log['sanitation_cost'] + (float)($log['water_other_cost'] ?? 0), 2) }}</td>
                                <td class="py-2.5 text-right font-bold text-slate-900 dark:text-white">₦{{ number_format($log['daily_total_cost'], 2) }}</td>
                                <td class="py-2.5 text-right pl-2">
                                    <button type="button" onclick="openEditProdLogModal({{ (int)$log['id'] }})"
                                            class="px-2 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-[10px] font-sans font-semibold transition cursor-pointer">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-4 text-center font-sans text-slate-500 dark:text-slate-400">No daily telemetry recorded for this batch yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($pb['sales']))
            <div class="px-5 pb-5 bg-slate-100/40 dark:bg-slate-950/40 border-t border-slate-200/70 dark:border-slate-800/70 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Batch Sales &amp; Realized Revenue</h4>
                    <span class="text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
                        Total Sold: {{ number_format($m['total_birds_sold']) }} birds • ₦{{ number_format($m['realized_revenue'], 2) }}
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 text-xs font-mono">
                    @foreach ($pb['sales'] as $sale)
                        <div class="rounded-xl bg-white/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 p-2.5 flex items-center justify-between">
                            <div>
                                <div class="font-sans font-semibold text-slate-800 dark:text-slate-100">{{ $sale['customer_name'] ?? 'Direct Buyer' }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $sale['sale_date'] }} • {{ number_format((int)$sale['birds_sold']) }} birds</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-emerald-600 dark:text-emerald-400">₦{{ number_format((float)$sale['total_amount'], 2) }}</div>
                                <div class="text-[10px] text-slate-500 dark:text-slate-400">{{ $sale['payment_status'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @empty
        <div class="glass-card rounded-2xl p-8 text-center">
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No active production flocks yet.</p>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Click <strong>+ New Direct DOC Batch</strong> above or transfer hatched chicks from the Incubator module.</p>
        </div>
    @endforelse
    </div>
</div>

<!-- MODAL: NEW DIRECT DOC PRODUCTION BATCH -->
<div id="addProductionBatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="addProductionBatchModalBox" class="glass-card max-w-lg w-full rounded-3xl p-6 sm:p-8 border border-emerald-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                    <i class="fa-solid fa-kiwi-bird"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">New Direct DOC Production Batch</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">For flocks purchased directly as Day-Old Chicks (or use Transfer from Incubator).</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('addProductionBatchModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=production" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="create_production_batch">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch Code (Auto if blank)</label>
                    <input type="text" name="batch_code" placeholder="e.g. PRD-{{ date('Y') }}-05"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Flock Breed / Species</label>
                    <input type="text" name="flock_type" placeholder="e.g. Ross 308 Broilers, ISA Brown Layers, Local Turkey" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">Chicken Age (Days)</label>
                    <input type="number" name="initial_age_days" value="1" min="1" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Initial Bird Count</label>
                    <input type="number" name="initial_bird_count" id="docInitialBirds" min="1" placeholder="1500" oninput="updateDirectDocPricingPreview()" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">DOC Purchase Cost (₦)</label>
                    <input type="number" step="0.01" min="0" name="transferred_incubation_cost" id="docPurchaseCost" placeholder="750000" oninput="updateDirectDocPricingPreview()" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Selling Price per Bird (₦)</label>
                    <input type="number" step="0.01" min="0" name="selling_price_per_bird" id="docSellingPriceInput" placeholder="650.00" oninput="this.dataset.userEdited='1'" required
                           class="w-full rounded-xl border border-emerald-500/50 bg-white/90 dark:bg-slate-800/90 text-emerald-700 dark:text-emerald-300 px-3 py-2 text-xs font-mono font-bold focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <!-- Live DOC Cost per Chick & Suggested Selling Price Preview -->
            <div class="rounded-2xl bg-slate-100/90 dark:bg-slate-900/80 border border-emerald-500/30 p-3 grid grid-cols-2 gap-2.5 text-center font-mono">
                <div class="rounded-xl bg-white/80 dark:bg-slate-800/80 p-2 border border-slate-200 dark:border-slate-700">
                    <span class="block text-[10px] text-slate-500 dark:text-slate-400">DOC Cost / Chick</span>
                    <span id="docCostPerChickPreview" class="text-xs font-bold text-amber-600 dark:text-amber-300">₦0.00</span>
                </div>
                <div class="rounded-xl bg-emerald-500/15 p-2 border border-emerald-500/30">
                    <span class="block text-[10px] text-emerald-700 dark:text-emerald-300">Suggested Selling Price (+30%)</span>
                    <span id="docSuggestedPricePreview" class="text-xs font-bold text-emerald-700 dark:text-emerald-300">₦0.00</span>
                </div>
            </div>

            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                <i class="fa-solid fa-circle-info text-cyan-500 mr-1"></i> Farm rent is managed as a general farm-wide overhead across all products.
            </p>

            <div class="pt-3 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('addProductionBatchModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/25 transition cursor-pointer active:scale-95">
                    + Create Production Flock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateDirectDocPricingPreview() {
    const birds = parseFloat(document.getElementById('docInitialBirds')?.value || '0');
    const docCost = parseFloat(document.getElementById('docPurchaseCost')?.value || '0');
    const cpc = (birds > 0 && docCost >= 0) ? (docCost / birds) : 0;
    const suggested = Math.round(cpc * 1.30 * 100) / 100;
    const fmt = (n) => '₦' + n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const cpcEl = document.getElementById('docCostPerChickPreview');
    const sugEl = document.getElementById('docSuggestedPricePreview');
    const inputEl = document.getElementById('docSellingPriceInput');

    if (cpcEl) cpcEl.textContent = fmt(cpc);
    if (sugEl) sugEl.textContent = fmt(suggested);
    if (inputEl && !inputEl.dataset.userEdited) {
        inputEl.value = suggested > 0 ? suggested.toFixed(2) : '';
    }
}

function selectProductionBatch(batchId) {
    document.querySelectorAll('.prod-batch-detail').forEach((el) => {
        el.classList.add('hidden');
    });
    const targetDetail = document.getElementById('prodBatchDetail-' + batchId);
    if (targetDetail) {
        targetDetail.classList.remove('hidden');
        targetDetail.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    document.querySelectorAll('.prod-batch-card').forEach((card) => {
        card.classList.remove('ring-2', 'ring-emerald-500', 'border-emerald-500/60', 'opacity-100');
        card.classList.add('border-slate-200/80', 'dark:border-slate-700/70', 'opacity-90');
    });
    const activeCard = document.getElementById('prodBatchCard-' + batchId);
    if (activeCard) {
        activeCard.classList.add('ring-2', 'ring-emerald-500', 'border-emerald-500/60', 'opacity-100');
        activeCard.classList.remove('border-slate-200/80', 'dark:border-slate-700/70', 'opacity-90');
    }

    const formSelect = document.querySelector('form[action="/?page=production"] select[name="production_batch_id"]');
    if (formSelect) {
        formSelect.value = String(batchId);
    }
}

function fmtCurrency(n) {
    return '₦' + Number(n || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function openBatchPnLModal(batchOrId) {
    const pb = (typeof batchOrId === 'object' && batchOrId !== null)
        ? batchOrId
        : (window.prodBatchesEditMap?.[String(batchOrId)] || window.prodBatchesEditMap?.[batchOrId]);
    if (!pb) return;
    const m = pb.metrics || {};
    document.getElementById('printReportTitle').textContent = 'Batch Profit & Loss Close-Out Report';
    const otherCost = Number(m.total_labor_cost || 0) + Number(m.total_sanitation_cost || 0) + Number(m.total_water_other_cost || 0);
    const totalRev = Number(m.realized_revenue ?? m.total_revenue ?? 0);
    const totalCost = Number(m.total_accumulated_cost || 0);
    const netProfit = totalRev - totalCost;
    const soldBirds = Number(m.total_birds_sold ?? m.birds_sold ?? 0);
    const remBirds = Number(m.current_in_house_birds ?? m.remaining_live_birds ?? 0);

    document.getElementById('printReportBody').innerHTML = `
        <div class="space-y-4 text-slate-800 font-sans text-xs">
            <div class="flex items-start justify-between border-b border-slate-300 pb-3">
                <div>
                    <h4 class="text-base font-extrabold text-slate-900">Blackswan Farms and General Enterprises</h4>
                    <p class="text-[11px] text-slate-500">Batch P&amp;L &amp; Cost Attribution Statement</p>
                </div>
                <div class="text-right font-mono text-[11px]">
                    <div class="font-bold text-slate-900">${pb.batch_code || ''}</div>
                    <div class="text-slate-500">${pb.flock_type || ''} • ${pb.status || ''}</div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center bg-slate-100 rounded-lg p-2.5 font-mono">
                <div><span class="block text-[10px] text-slate-500">Initial Birds</span><strong class="text-slate-900">${m.initial_birds || 0}</strong></div>
                <div><span class="block text-[10px] text-slate-500">Mortality</span><strong class="text-rose-600">-${m.total_mortality || 0}</strong></div>
                <div><span class="block text-[10px] text-slate-500">Sold / Remaining</span><strong class="text-emerald-700">${soldBirds} / ${remBirds}</strong></div>
            </div>
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-300 text-slate-500 uppercase text-[10px]">
                        <th class="py-1.5 text-left">Cost &amp; Revenue Component</th>
                        <th class="py-1.5 text-right">Amount (₦)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono">
                    <tr><td class="py-1.5 font-sans">Incubation / DOC Carried Capital</td><td class="py-1.5 text-right">${fmtCurrency(m.incubation_cost)}</td></tr>
                    <tr><td class="py-1.5 font-sans">Feed Consumed (${m.total_feed_kg || 0} kg)</td><td class="py-1.5 text-right">${fmtCurrency(m.total_feed_cost)}</td></tr>
                    <tr><td class="py-1.5 font-sans">Medication &amp; Vaccines</td><td class="py-1.5 text-right">${fmtCurrency(m.total_med_cost)}</td></tr>
                    <tr><td class="py-1.5 font-sans">Labor, Sanitation &amp; Water/Other</td><td class="py-1.5 text-right">${fmtCurrency(otherCost)}</td></tr>
                    <tr class="font-bold bg-slate-50"><td class="py-2 font-sans">Total Variable Flock Cost</td><td class="py-2 text-right">${fmtCurrency(totalCost)}</td></tr>
                    <tr class="font-bold text-emerald-700"><td class="py-2 font-sans">Total Sales Revenue</td><td class="py-2 text-right">${fmtCurrency(totalRev)}</td></tr>
                    <tr class="font-extrabold text-sm ${netProfit >= 0 ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50'}">
                        <td class="py-2.5 font-sans">Batch Gross Profit / (Loss)</td>
                        <td class="py-2.5 text-right">${netProfit >= 0 ? '+' : ''}${fmtCurrency(netProfit)}</td>
                    </tr>
                </tbody>
            </table>
            <p class="text-[10px] text-slate-500 italic">Note: Fixed Assets, Incubator Machine, Annual Rent &amp; General Overhead are deducted at the whole-farm level in Finance → Net Profit Distribution.</p>
        </div>
    `;
    openModal('printReportModal');
}

function printDocumentArea() {
    const content = document.getElementById('printReportBody').innerHTML;
    const title = document.getElementById('printReportTitle').textContent;
    const win = window.open('', '_blank', 'width=760,height=820');
    if (!win) {
        window.print();
        return;
    }
    win.document.write(`<!DOCTYPE html><html><head><title>${title}</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; padding: 28px; color: #0f172a; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 6px 4px; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .font-mono { font-family: ui-monospace, monospace; }
            .font-bold, .font-extrabold { font-weight: 700; }
            .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
            .flex { display: flex; justify-content: space-between; }
            .border-b { border-bottom: 1px solid #cbd5e1; }
            .bg-slate-100, .bg-slate-50 { background: #f1f5f9; padding: 8px; border-radius: 6px; }
        </style></head><body>${content}</body></html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 250);
}

window.prodBatchesEditMap = <?php
$pbEditMap = [];
$pLogsEditMap = [];
foreach ($productionBatches ?? [] as $_pb) {
    $pbId = (int) ($_pb['id'] ?? 0);
    $pbEditMap[$pbId] = [
        'id' => $pbId,
        'batch_code' => $_pb['batch_code'] ?? '',
        'flock_type' => $_pb['flock_type'] ?? '',
        'initial_bird_count' => (int) ($_pb['initial_bird_count'] ?? 0),
        'initial_age_days' => (int) ($_pb['initial_age_days'] ?? 1),
        'start_date' => $_pb['start_date'] ?? '',
        'transferred_incubation_cost' => (float) ($_pb['transferred_incubation_cost'] ?? ($_pb['metrics']['incubation_cost'] ?? 0)),
        'selling_price_per_bird' => (float) ($_pb['target_selling_price'] ?? ($_pb['metrics']['recommended_selling_price'] ?? 0)),
        'status' => $_pb['status'] ?? 'Active',
        'metrics' => $_pb['metrics'] ?? [],
    ];
    foreach ($_pb['logs'] ?? [] as $_lg) {
        $lgId = (int) ($_lg['id'] ?? 0);
        $pLogsEditMap[$lgId] = [
            'id' => $lgId,
            'batch_code' => $_pb['batch_code'] ?? '',
            'log_date' => $_lg['log_date'] ?? '',
            'mortality_count' => (int) ($_lg['mortality_count'] ?? 0),
            'good_eggs' => (int) ($_lg['good_eggs'] ?? 0),
            'cracked_eggs' => (int) ($_lg['cracked_eggs'] ?? 0),
            'water_liters' => (float) ($_lg['water_liters'] ?? 0),
            'feed_item_id' => (int) ($_lg['feed_item_id'] ?? 0),
            'feed_grams' => (float) ($_lg['feed_grams'] ?? 0),
            'med_item_id' => (int) ($_lg['med_item_id'] ?? 0),
            'med_dose_g_ml' => (float) ($_lg['med_dose_g_ml'] ?? 0),
            'medications_used' => $_lg['medications_used'] ?? [],
            'labor_cost' => (float) ($_lg['labor_cost'] ?? 0),
            'sanitation_cost' => (float) ($_lg['sanitation_cost'] ?? 0),
            'water_other_cost' => (float) ($_lg['water_other_cost'] ?? 0),
            'notes' => $_lg['notes'] ?? '',
        ];
    }
}
echo json_encode($pbEditMap, JSON_UNESCAPED_UNICODE);
?>;
window.prodLogsEditMap = <?php echo json_encode($pLogsEditMap, JSON_UNESCAPED_UNICODE); ?>;

function openEditProdBatchModal(batchId) {
    const b = window.prodBatchesEditMap[String(batchId)] || window.prodBatchesEditMap[batchId];
    if (!b) return;
    document.getElementById('editProdBatchId').value = b.id;
    document.getElementById('editProdBatchCode').value = b.batch_code || '';
    document.getElementById('editProdBatchStartDate').value = b.start_date || '';
    document.getElementById('editProdBatchFlockType').value = b.flock_type || '';
    document.getElementById('editProdBatchInitAge').value = b.initial_age_days || 1;
    document.getElementById('editProdBatchInitCount').value = b.initial_bird_count || 1;
    document.getElementById('editProdBatchIncCost').value = b.transferred_incubation_cost ?? 0;
    document.getElementById('editProdBatchSellPrice').value = b.selling_price_per_bird ?? 0;
    document.getElementById('editProdBatchStatus').value = b.status || 'Active';
    openModal('editProductionBatchModal');
}

function openEditProdLogModal(logId) {
    const l = window.prodLogsEditMap[String(logId)] || window.prodLogsEditMap[logId];
    if (!l) return;
    document.getElementById('editProdLogId').value = l.id;
    document.getElementById('editProdLogSubtitle').textContent = 'Flock ' + (l.batch_code || '') + ' • Refunds old stock before applying updated quantities';
    document.getElementById('editProdLogDate').value = l.log_date || '';
    document.getElementById('editProdLogMortality').value = l.mortality_count ?? 0;
    document.getElementById('editProdLogWater').value = l.water_liters ?? 0;
    document.getElementById('editProdLogGoodEggs').value = l.good_eggs ?? 0;
    document.getElementById('editProdLogCrackedEggs').value = l.cracked_eggs ?? 0;
    document.getElementById('editProdLogFeedItem').value = l.feed_item_id ? String(l.feed_item_id) : '';
    document.getElementById('editProdLogFeedGrams').value = l.feed_grams ?? 0;
    document.getElementById('editProdLogLabor').value = l.labor_cost ?? 0;
    document.getElementById('editProdLogSanit').value = l.sanitation_cost ?? 0;
    document.getElementById('editProdLogWaterOther').value = l.water_other_cost ?? 0;
    document.getElementById('editProdLogNotes').value = l.notes || '';

    const container = document.getElementById('editProdLogMedList');
    if (container) {
        container.querySelectorAll('input[type="checkbox"][data-med-id]').forEach((cb) => {
            cb.checked = false;
        });
        container.querySelectorAll('input[type="number"][data-med-dose]').forEach((inp) => {
            inp.disabled = true;
            inp.value = '';
        });

        const medEntries = Array.isArray(l.medications_used) && l.medications_used.length > 0
            ? l.medications_used
            : (l.med_item_id && parseFloat(l.med_dose_g_ml || 0) > 0
                ? [{ item_id: l.med_item_id, dose_g_ml: l.med_dose_g_ml }]
                : []);

        medEntries.forEach((mu) => {
            const mId = String(mu.item_id || mu.med_item_id || '');
            const dose = mu.dose_g_ml ?? mu.med_dose_g_ml ?? mu.dose ?? '';
            if (!mId) return;
            const cb = container.querySelector(`input[type="checkbox"][data-med-id="${mId}"]`);
            const inp = container.querySelector(`input[type="number"][data-med-dose="${mId}"]`);
            if (cb && inp) {
                cb.checked = true;
                inp.disabled = false;
                inp.value = dose;
            }
        });
    }
    openModal('editProdLogModal');
}
</script>

<!-- MODAL: EDIT PRODUCTION BATCH -->
<div id="editProductionBatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="editProductionBatchModalBox" class="glass-card max-w-lg w-full rounded-3xl p-6 sm:p-8 border border-amber-500/40 relative transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Production Batch</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Adjust batch details, DOC capital cost, or status.</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editProductionBatchModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=production" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="update_production_batch">
            <input type="hidden" name="production_batch_id" id="editProdBatchId">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch Code</label>
                    <input type="text" name="batch_code" id="editProdBatchCode" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="editProdBatchStartDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Flock Breed / Species</label>
                    <input type="text" name="flock_type" id="editProdBatchFlockType" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Initial Age (Days)</label>
                    <input type="number" name="initial_age_days" id="editProdBatchInitAge" min="1" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Initial Bird Count</label>
                    <input type="number" name="initial_bird_count" id="editProdBatchInitCount" min="1" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">DOC / Inc. Cost (₦)</label>
                    <input type="number" step="any" min="0" name="transferred_incubation_cost" id="editProdBatchIncCost" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Target Price / Bird (₦)</label>
                    <input type="number" step="any" min="0" name="selling_price_per_bird" id="editProdBatchSellPrice" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-emerald-700 dark:text-emerald-300 font-bold px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch Status</label>
                <select name="status" id="editProdBatchStatus"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-amber-500 focus:outline-none">
                    <option value="Active">Active</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('editProductionBatchModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-amber-500/25 transition cursor-pointer active:scale-95">
                    Save Batch Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDIT PRODUCTION DAILY LOG -->
<div id="editProdLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="editProdLogModalBox" class="glass-card max-w-2xl w-full rounded-3xl p-6 sm:p-7 border border-amber-500/40 relative transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Daily Telemetry Log</h3>
                    <p id="editProdLogSubtitle" class="text-[11px] text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editProdLogModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=production" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="update_production_daily_log">
            <input type="hidden" name="log_id" id="editProdLogId">

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Log Date</label>
                    <input type="date" name="log_date" id="editProdLogDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Mortality</label>
                    <input type="number" name="mortality_count" id="editProdLogMortality" min="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Water (L)</label>
                    <input type="number" step="any" min="0" name="water_liters" id="editProdLogWater"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-amber-700 dark:text-amber-300 mb-1">Good Eggs</label>
                    <input type="number" name="good_eggs" id="editProdLogGoodEggs" min="0"
                           class="w-full rounded-xl border border-amber-400/50 bg-amber-50/50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-300 font-bold px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Cracked Eggs</label>
                    <input type="number" name="cracked_eggs" id="editProdLogCrackedEggs" min="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 pt-2 border-t border-slate-200/70 dark:border-slate-800/70">
                <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70 space-y-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 block">Feed Consumption (Grams)</span>
                    <div>
                        <label class="block text-[11px] text-slate-600 dark:text-slate-400 mb-1">Feed Stock</label>
                        <select name="feed_item_id" id="editProdLogFeedItem"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none">
                            <option value="">-- No Feed Logged --</option>
                            @foreach ($inventoryItems as $item)
                                @if (in_array($item['category'], ['starter', 'grower', 'layer'], true))
                                    <option value="{{ $item['id'] }}">
                                        [{{ strtoupper($item['category']) }}] {{ $item['item_name'] }} ({{ number_format((float)$item['total_quantity_g_or_ml']) }}g left)
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-600 dark:text-slate-400 mb-1">Grams Consumed (decimals allowed e.g. 0.245)</label>
                        <input type="number" step="any" min="0" name="feed_grams" id="editProdLogFeedGrams"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70 space-y-2">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-100 block">Medications &amp; Vaccines</span>
                    <div id="editProdLogMedList" class="max-h-36 overflow-y-auto space-y-1.5 pr-1">
                        @forelse ($prodMedList as $item)
                            <div class="flex items-center justify-between gap-2 bg-white/90 dark:bg-slate-800/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700">
                                <label class="flex items-center gap-2 min-w-0 cursor-pointer flex-1">
                                    <input type="checkbox" name="med_items[]" value="{{ $item['id'] }}" data-med-id="{{ $item['id'] }}"
                                           onchange="const inp = this.closest('div').querySelector('input[type=number]'); inp.disabled = !this.checked; if(!this.checked) inp.value=''; else inp.focus();"
                                           class="rounded text-cyan-500 focus:ring-cyan-500">
                                    <span class="text-[11px] font-medium text-slate-800 dark:text-slate-100 truncate">
                                        {{ $item['item_name'] }}
                                        <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">({{ number_format((float)$item['total_quantity_g_or_ml']) }}{{ $item['unit_label'] }})</span>
                                    </span>
                                </label>
                                <div class="flex items-center gap-1 shrink-0">
                                    <input type="number" step="any" min="0" name="med_doses[{{ $item['id'] }}]" data-med-dose="{{ $item['id'] }}" placeholder="Dose" disabled
                                           class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-cyan-700 dark:text-cyan-300 px-2 py-1 text-[11px] font-mono outline-none disabled:opacity-40">
                                    <span class="text-[10px] font-mono text-slate-500">{{ $item['unit_label'] }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-[11px] text-slate-400 italic py-1">No medications or vaccines in store yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 pt-1">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Labor (₦)</label>
                    <input type="number" step="any" min="0" name="labor_cost" id="editProdLogLabor"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Sanitation (₦)</label>
                    <input type="number" step="any" min="0" name="sanitation_cost" id="editProdLogSanit"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-cyan-700 dark:text-cyan-300 mb-1">Water / Other (₦)</label>
                    <input type="number" step="any" min="0" name="water_other_cost" id="editProdLogWaterOther"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</label>
                    <input type="text" name="notes" id="editProdLogNotes"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-3 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('editProdLogModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-amber-500/25 transition cursor-pointer active:scale-95">
                    Save Telemetry Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Print Report Modal -->
<div id="printReportModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/70 backdrop-blur-sm p-4">
    <div id="printReportModalBox" class="bg-white rounded-3xl w-full max-w-xl p-6 shadow-2xl border border-slate-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
            <h3 id="printReportTitle" class="text-sm font-bold text-slate-900">Batch Profit &amp; Loss Close-Out Report</h3>
            <button type="button" onclick="closeModal('printReportModal')" class="text-slate-400 hover:text-slate-700 cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div id="printReportBody"></div>
        <div class="pt-4 mt-4 border-t border-slate-200 flex items-center justify-end gap-2">
            <button type="button" onclick="closeModal('printReportModal')" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 cursor-pointer">Close</button>
            <button type="button" onclick="printDocumentArea()" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print"></i> Print / Save PDF
            </button>
        </div>
    </div>
</div>
@endsection
