@extends('layouts.app')

@section('title', 'Module 1: Incubation & Hatchery Management')
@section('page_heading', 'Module 1: Incubation & Hatchery Management')
@section('page_subheading', 'Track setter intake, batch-linked gas & repair expenses, real-time candling diagnostics, final hatch breakouts, and one-click production transfer.')

@section('content')
<div class="space-y-6">
    <!-- Incubator Page Header Bar -->
    <div class="glass-card rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                <i class="fa-solid fa-egg text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Hatchery & Incubator Command Center</h2>
                <p class="text-xs text-slate-500 dark:text-slate-300">Dedicated multi-species setter intake, candling diagnostics, gas expenses, and hatch breakouts.</p>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" onclick="openModal('addEggsModal')" class="px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/25 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95">
                <i class="fa-solid fa-plus text-[10px]"></i> Add New Eggs
            </button>
            <a href="/?page=dashboard" class="px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-mono font-semibold border border-slate-300 dark:border-slate-700 transition-all flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Action Forms Strip (Clean 3-Card Manager Event Grid) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- 2. On-Demand Gas Refill, Maintenance & Expenses -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">1. Gas Refill & Maintenance</h2>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 font-bold">As Required</span>
            </div>
            <form method="POST" action="/?page=incubation" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="add_incubation_expense">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Target Incubation Batch</label>
                    <select name="incubation_batch_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                        @foreach ($incubationBatches as $ib)
                            <option value="{{ $ib['id'] }}">{{ $ib['batch_code'] }} ({{ $ib['status'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Expense Type</label>
                        <select name="expense_type" onchange="toggleGasWeightField(this.value)" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="gas">Fuel / Gas Refill</option>
                            <option value="repair">Maintenance / Repair</option>
                            <option value="other">Other Expense (Electricity / Parts)</option>
                        </select>
                    </div>
                    <div id="gasWeightWrapper">
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Gas Weight (kg)</label>
                        <input type="number" step="any" min="0" name="gas_weight_kg" placeholder="25.0" value="25"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Total Cost (₦)</label>
                        <input type="number" step="0.01" name="amount" placeholder="32500" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Date</label>
                        <input type="date" name="date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Invoice / Part Note</label>
                    <input type="text" name="description" placeholder="e.g. 25kg LPG refill or thermostat replacement"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold shadow-md shadow-amber-500/20 transition cursor-pointer">
                    + Log Batch Expense
                </button>
            </form>
        </div>

        <!-- 3. On-Demand Candling & Stage Logs -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">2. Candling & Stage Check</h2>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30 font-bold">Candling / Transfer</span>
            </div>
            <form method="POST" action="/?page=incubation" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="add_candling_log">
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch</label>
                        <select name="incubation_batch_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                            @foreach ($incubationBatches as $ib)
                                <option value="{{ $ib['id'] }}">{{ $ib['batch_code'] }} ({{ $ib['current_stage'] ?? 'Setter' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Incubator Stage</label>
                        <select name="stage" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="Setter">Setter (Turning ON)</option>
                            <option value="Hatcher">Hatcher (Lockdown / No Turn)</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Milestone</label>
                        <select name="milestone" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="Daily Inspection">Daily Check</option>
                            <option value="Early Candling">Early Candling</option>
                            <option value="Transfer Candling">Hatcher Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Temp (°C)</label>
                        <input type="number" step="any" name="temperature_c" placeholder="37.5"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Humidity (%)</label>
                        <input type="number" step="any" name="humidity_pct" placeholder="58.0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Clear / Infertile</label>
                        <input type="number" name="infertiles" value="0" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Early Mortality</label>
                        <input type="number" name="early_deaths" value="0" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Cracked / Damaged</label>
                        <input type="number" name="cracked" value="0" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Dead-in-Shell (DIS)</label>
                        <input type="number" name="dead_in_shell" value="0" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Inspection Notes</label>
                    <input type="text" name="notes" placeholder="e.g. Removed clear eggs on Tray B2"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-bold shadow-md shadow-cyan-500/20 transition cursor-pointer">
                    + Record Telemetry & Candling
                </button>
            </form>
        </div>

        <!-- 4. Final Hatch Breakout & Automated Metrics -->
        <div class="glass-card rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-bold text-slate-900 dark:text-white">3. Final Hatch Breakout</h2>
                <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-bold">Auto-Metrics</span>
            </div>
            <form method="POST" action="/?page=incubation" class="space-y-3">
                @csrf
                <input type="hidden" name="action" value="record_hatch_breakout">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Incubation Batch</label>
                    <select name="incubation_batch_id" required class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">
                        @foreach ($incubationBatches as $ib)
                            <option value="{{ $ib['id'] }}">{{ $ib['batch_code'] }} ({{ number_format((int)$ib['eggs_set']) }} eggs)</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-emerald-600 dark:text-emerald-300 mb-1">Live Grade-A Chicks</label>
                        <input type="number" name="live_chicks" placeholder="1720" min="1" required
                               class="w-full rounded-xl border border-emerald-500/50 bg-emerald-500/10 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono font-semibold focus:border-emerald-400 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Culls</label>
                        <input type="number" name="culls" value="25" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Pipped (Unhatched)</label>
                        <input type="number" name="pipped" value="20" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Dead-in-Shell (DIS)</label>
                        <input type="number" name="dead_in_shell" value="40" min="0" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Breakout Date</label>
                    <input type="date" name="breakout_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <button type="submit" class="w-full py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
                    Compute Hatchability & Cost/Chick
                </button>
            </form>
        </div>
    </div>

    <!-- Batch-by-Batch Incubation Cards with Candling Logs, Expenses & One-Click "Move to Production" -->
    <div class="space-y-5">
        @foreach ($incubationBatches as $ib)
            @php $m = $ib['metrics']; @endphp
            <div class="glass-card rounded-2xl overflow-hidden">
                <!-- Batch Header -->
                <div class="px-6 py-4 bg-slate-100/85 dark:bg-slate-900/50 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-base font-mono font-bold text-slate-900 dark:text-white">{{ $ib['batch_code'] }}</span>
                        @if (!empty($ib['is_client_service']))
                            <span class="text-[11px] font-mono px-2.5 py-0.5 rounded-full font-bold bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30">
                                CLIENT SERVICE • {{ $ib['client_name'] ?: 'Customer' }}
                            </span>
                        @endif
                        <span class="text-[11px] font-mono px-2.5 py-0.5 rounded-full font-bold bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30">
                            {{ $ib['species_label'] ?? 'Chicken' }} • Day {{ $ib['incubation_day'] }}/{{ $ib['total_days'] ?? 21 }} ({{ $ib['current_stage'] ?? 'Setter' }})
                        </span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-semibold {{ $ib['status'] === 'Completed' ? 'bg-amber-500/15 text-amber-800 dark:text-amber-300 border border-amber-500/30' : ($ib['status'] === 'Moved to Production' || $ib['status'] === 'Collected' ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300 border border-emerald-500/30' : 'bg-cyan-500/15 text-cyan-800 dark:text-cyan-300 border border-cyan-500/30') }}">
                            {{ $ib['status'] }}
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-300">
                            {{ $ib['parent_flock_info'] }}
                            @if (!empty($ib['client_phone']))
                                • <a href="tel:{{ $ib['client_phone'] }}" class="font-mono text-cyan-600 dark:text-cyan-400 hover:underline">{{ $ib['client_phone'] }}</a>
                            @endif
                            • {{ $ib['setter_id'] ?? 'Setter A' }} • Set {{ $ib['start_date'] }}
                        </span>
                    </div>

                    <!-- One-Click UI Transition ("Push to Production" or "Client Collection") -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="openEditIncBatchModal({{ (int)$ib['id'] }})"
                                class="px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-xs font-semibold flex items-center gap-1.5 transition cursor-pointer">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        @if (!empty($ib['is_client_service']))
                            <span class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-white/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200">
                                Fee: <strong>₦{{ number_format($ib['service_fee'], 0) }}</strong> • Paid: <strong class="text-emerald-600 dark:text-emerald-400">₦{{ number_format($ib['amount_paid'], 0) }}</strong>
                                @if ($ib['balance_due'] > 0)
                                    • Due: <strong class="text-rose-600 dark:text-rose-400">₦{{ number_format($ib['balance_due'], 0) }}</strong>
                                @endif
                            </span>
                            @if ($ib['balance_due'] > 0)
                                <form method="POST" action="/?page=incubation" class="inline-flex items-center gap-1">
                                    <input type="hidden" name="action" value="record_incubation_payment">
                                    <input type="hidden" name="incubation_batch_id" value="{{ $ib['id'] }}">
                                    <input type="number" step="0.01" min="1" max="{{ $ib['balance_due'] }}" name="payment_amount" value="{{ $ib['balance_due'] }}" required
                                           class="w-24 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 px-2 py-1 text-[11px] font-mono text-slate-900 dark:text-white">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-[11px] font-bold cursor-pointer">
                                        Collect Fee
                                    </button>
                                </form>
                            @endif
                            @if ($ib['status'] !== 'Collected')
                                <form method="POST" action="/?page=incubation" class="inline">
                                    <input type="hidden" name="action" value="mark_incubation_collected">
                                    <input type="hidden" name="incubation_batch_id" value="{{ $ib['id'] }}">
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold shadow-sm cursor-pointer">
                                        ✓ Mark Chicks Collected
                                    </button>
                                </form>
                            @else
                                <span class="px-3 py-1.5 rounded-xl bg-emerald-500/15 border border-emerald-500/35 text-emerald-700 dark:text-emerald-300 text-xs font-semibold">
                                    ✓ Collected ({{ $ib['collected_date'] ?? 'Done' }})
                                </span>
                            @endif
                        @elseif ($ib['linked_production_batch'])
                            <a href="/?page=production" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/35 text-emerald-800 dark:text-emerald-300 text-xs font-semibold hover:bg-emerald-500/25 transition">
                                ✓ Linked to {{ $ib['linked_production_batch']['batch_code'] }} ({{ number_format((int)$ib['linked_production_batch']['initial_bird_count']) }} chicks)
                            </a>
                        @else
                            <form method="POST" action="/?page=incubation" class="flex items-center gap-2">
                                @csrf
                                <input type="hidden" name="action" value="push_to_production">
                                <input type="hidden" name="incubation_batch_id" value="{{ $ib['id'] }}">
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer">
                                    <span>Move to Production ({{ number_format($m['live_chicks'] > 0 ? $m['live_chicks'] : $m['viable_eggs_remaining']) }} Chicks @ ₦{{ number_format($m['total_incubation_cost'], 0) }})</span>
                                    <span>→</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- Automated Metrics Strip -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 divide-y sm:divide-y-0 sm:divide-x divide-slate-200/70 dark:divide-slate-800/70 border-b border-slate-200/80 dark:border-slate-800/80 bg-white/80 dark:bg-slate-900/45">
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Eggs Set</span>
                        <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ number_format((int)$ib['eggs_set']) }}</div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-300 font-mono">Base: ₦{{ number_format((float)$ib['egg_cost'], 2) }}</div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gas & Repairs</span>
                        <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-0.5">₦{{ number_format($m['gas_cost'] + $m['repair_cost'], 2) }}</div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-300 font-mono">Gas: {{ number_format($m['gas_weight_kg'], 1) }}kg (₦{{ number_format($m['gas_cost'], 0) }})</div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Candling Removed</span>
                        <div class="text-lg font-bold font-mono text-rose-600 dark:text-rose-400 mt-0.5">{{ number_format($m['total_removed']) }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">eggs</span></div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-300">Infertile: {{ $m['infertiles'] }} • Early: {{ $m['early_deaths'] }}</div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Fertility %</span>
                        <div class="text-lg font-bold font-mono text-slate-900 dark:text-white mt-0.5">{{ number_format($m['fertility_rate'], 2) }}%</div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-300">(Set - Infertiles) / Set</div>
                    </div>
                    <div class="p-4">
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Hatchability %</span>
                        <div class="text-lg font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-0.5">
                            {{ $ib['breakout'] ? number_format($m['hatchability_rate'], 2) . '%' : 'In Setter' }}
                        </div>
                        <div class="text-[11px] text-slate-600 dark:text-slate-300">
                            {{ $ib['breakout'] ? number_format($m['live_chicks']) . ' Grade-A Chicks' : $m['viable_eggs_remaining'] . ' viable remaining' }}
                        </div>
                    </div>
                    <div class="p-4 bg-emerald-500/10 dark:bg-emerald-500/15">
                        <span class="text-[11px] font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">Initial Cost / Chick</span>
                        <div class="text-lg font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-0.5">₦{{ number_format($m['cost_per_chick'], 2) }}</div>
                        <div class="text-[11px] text-emerald-800/80 dark:text-emerald-300/90 font-mono">Total: ₦{{ number_format($m['total_incubation_cost'], 2) }}</div>
                    </div>
                </div>

                <!-- Candling Logs & Expense Ledger Sub-Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-slate-200/70 dark:divide-slate-800/70">
                    <!-- Candling History -->
                    <div class="p-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 mb-3">Candling & Stage Inspection Logs</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200/80 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                        <th class="pb-2">Date</th>
                                        <th class="pb-2">Milestone</th>
                                        <th class="pb-2 text-right">Infertile</th>
                                        <th class="pb-2 text-right">Early Death</th>
                                        <th class="pb-2 text-right">Cracked</th>
                                        <th class="pb-2 text-right">DIS</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/60 font-mono">
                                    @forelse ($ib['logs'] as $log)
                                        <tr>
                                            <td class="py-2 text-slate-600 dark:text-slate-300">{{ $log['log_date'] }}</td>
                                            <td class="py-2 font-sans font-semibold text-slate-900 dark:text-white">{{ $log['milestone'] ?? 'Inspection' }}</td>
                                            <td class="py-2 text-right text-slate-800 dark:text-slate-200">{{ $log['infertiles'] }}</td>
                                            <td class="py-2 text-right text-slate-800 dark:text-slate-200">{{ $log['early_deaths'] }}</td>
                                            <td class="py-2 text-right text-slate-800 dark:text-slate-200">{{ $log['cracked'] }}</td>
                                            <td class="py-2 text-right font-bold text-rose-600 dark:text-rose-400">{{ $log['dead_in_shell'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-3 text-center font-sans text-slate-500 dark:text-slate-400">No candling inspections logged yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Batch-Linked Expenses -->
                    <div class="p-5">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200 mb-3">Batch-Linked Gas & Repair Expenses</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200/80 dark:border-slate-700/80 text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                        <th class="pb-2">Date</th>
                                        <th class="pb-2">Type</th>
                                        <th class="pb-2">Description</th>
                                        <th class="pb-2 text-right">Gas (kg)</th>
                                        <th class="pb-2 text-right">Cost (₦)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/50 dark:divide-slate-800/60 font-mono">
                                    @forelse ($ib['expenses'] as $exp)
                                        <tr>
                                            <td class="py-2 text-slate-600 dark:text-slate-300">{{ $exp['date'] }}</td>
                                            <td class="py-2 font-sans">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $exp['expense_type'] === 'gas' ? 'bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-500/30' : 'bg-slate-200/80 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700' }}">
                                                    {{ $exp['expense_type'] }}
                                                </span>
                                            </td>
                                            <td class="py-2 font-sans font-medium text-slate-800 dark:text-slate-200">{{ $exp['description'] ?? 'Batch Expense' }}</td>
                                            <td class="py-2 text-right text-slate-700 dark:text-slate-300">{{ (float)$exp['gas_weight_kg'] > 0 ? number_format((float)$exp['gas_weight_kg'], 1) . ' kg' : '—' }}</td>
                                            <td class="py-2 text-right font-bold text-slate-900 dark:text-white">₦{{ number_format((float)$exp['amount'], 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="py-3 text-center font-sans text-slate-500 dark:text-slate-400">No operational expenses logged yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- DEDICATED POPUP MODAL: ADD NEW EGGS BATCH -->
@php
    $todayPrefix = 'INC-' . date('Y-m-d') . '-';
    $todaySeq = 1;
    foreach ($incubationBatches ?? [] as $_ibc) {
        $c = (string) ($_ibc['batch_code'] ?? '');
        if (str_starts_with($c, $todayPrefix)) {
            $n = (int) substr($c, strlen($todayPrefix));
            if ($n >= $todaySeq) $todaySeq = $n + 1;
        }
    }
    $nextBatchNum = sprintf('%s%02d', $todayPrefix, $todaySeq);
@endphp
<div id="addEggsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/75 backdrop-blur-sm opacity-0 transition-opacity duration-200">
    <div id="addEggsModalBox" class="glass-card rounded-3xl w-full max-w-md max-h-[90vh] overflow-y-auto p-6 shadow-2xl border border-emerald-500/40 transform scale-95 transition-transform duration-200">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-200/20 dark:border-slate-800">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-400">
                    <i class="fa-solid fa-egg text-base"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">Add New Egg Batch</h3>
                    <p class="text-[10px] font-mono text-slate-500 dark:text-slate-400">Load new eggs & initial gas into setter</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('addEggsModal')" class="w-8 h-8 rounded-xl bg-slate-200/60 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <form method="POST" action="/?page=incubation" class="space-y-3.5">
            @csrf
            <input type="hidden" name="action" value="create_incubation_batch">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch Number (Auto date-complete)</label>
                    <input type="text" name="batch_code" value="{{ $nextBatchNum }}" placeholder="e.g. {{ $nextBatchNum }}"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-emerald-600 dark:text-emerald-400 px-3 py-2 text-xs font-mono font-bold focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Intake Date</label>
                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Poultry Egg Type</label>
                <select name="species" id="speciesSelector" onchange="updateSpeciesTargetPreview(this.value)" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                    <option value="chicken">🐔 Chicken — 21 Days (18d Setter + 3d Hatcher)</option>
                    <option value="turkey">🦃 Turkey — 28 Days (25d Setter + 3d Hatcher)</option>
                    <option value="duck">🦆 Duck (Pekin/Mallard) — 28 Days (25d Setter + 3d Hatcher)</option>
                    <option value="muscovy_duck">🦆 Muscovy Duck — 35 Days (31d Setter + 4d Hatcher)</option>
                    <option value="guinea_fowl">🐦 Guinea Fowl — 28 Days (24d Setter + 4d Hatcher)</option>
                    <option value="quail">🐤 Quail (Coturnix) — 17 Days (14d Setter + 3d Hatcher)</option>
                    <option value="goose">🦢 Goose — 30 Days (26d Setter + 4d Hatcher)</option>
                    <option value="ostrich">🦩 Ostrich — 42 Days (39d Setter + 3d Hatcher)</option>
                </select>
            </div>

            <div id="speciesTargetHint" class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-[10px] font-mono text-emerald-700 dark:text-emerald-300 leading-relaxed">
                Setter (18d): 37.5–37.8°C • 55–60% RH | Transfer Day 18 → Hatcher (3d): 70–75% RH
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Egg Ownership</label>
                <select name="ownership_type" id="incOwnershipType" onchange="toggleClientHatchFields(this.value)"
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-semibold focus:border-emerald-500 focus:outline-none">
                    <option value="Farm">Farm Owned (Move to Production after Hatch)</option>
                    <option value="Client">Client Hatching Service (Customer Collects Chicks)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Customer / Supplier Name</label>
                    <input type="text" name="client_name" id="incClientName" placeholder="Person who brought the eggs"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Customer Phone Number</label>
                    <input type="text" name="client_phone" id="incClientPhone" placeholder="e.g. 0803..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div id="clientHatchFields" class="hidden space-y-2.5 p-3 rounded-2xl bg-purple-500/10 border border-purple-500/30">
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-purple-700 dark:text-purple-300 mb-1">Hatching Service Fee (₦)</label>
                        <input type="number" step="0.01" min="0" name="service_fee" id="incServiceFee" placeholder="50000"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-purple-700 dark:text-purple-300 mb-1">Amount Paid Upfront (₦)</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" placeholder="50000"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-purple-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Number of Eggs</label>
                    <input type="number" name="eggs_set" placeholder="2500" min="1" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
                <div id="farmEggPriceWrapper">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Egg Price (₦)</label>
                    <input type="number" step="0.01" name="egg_cost" id="incEggCostInput" placeholder="625000" min="0" value="0" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">First Gas Intake (kg, Optional)</label>
                    <input type="number" step="any" name="initial_gas_kg" placeholder="0" value="0" min="0"
                           class="w-full rounded-xl border border-amber-500/40 bg-amber-500/10 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-400 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">First Gas Price (₦, Optional)</label>
                    <input type="number" step="0.01" name="initial_gas_cost" placeholder="0" value="0" min="0"
                           class="w-full rounded-xl border border-amber-500/40 bg-amber-500/10 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-400 focus:outline-none">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('addEggsModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-300 dark:hover:bg-slate-700 transition cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-bold shadow-md shadow-emerald-500/25 transition cursor-pointer">
                    + Load Eggs into Incubator
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.speciesTargets = @json(\App\Core\VeterinaryKnowledgeBase::getIncubationSpeciesProfiles());

function updateSpeciesTargetPreview(speciesKey) {
    const hint = document.getElementById('speciesTargetHint');
    if (!hint || !window.speciesTargets || !window.speciesTargets[speciesKey]) return;
    const p = window.speciesTargets[speciesKey];
    hint.innerText = `Setter (${p.setter_days}d): ${p.setter_temp_c} • ${p.setter_humidity} | Transfer Day ${p.transfer_day} → Hatcher (${p.hatcher_days}d): ${p.hatcher_humidity}`;
}

function toggleGasWeightField(expenseType) {
    const wrapper = document.getElementById('gasWeightWrapper');
    if (!wrapper) return;
    if (expenseType === 'gas') {
        wrapper.classList.remove('hidden');
    } else {
        wrapper.classList.add('hidden');
    }
}

function toggleClientHatchFields(ownership) {
    const clientBox = document.getElementById('clientHatchFields');
    const eggPriceWrap = document.getElementById('farmEggPriceWrapper');
    const eggCostInput = document.getElementById('incEggCostInput');
    const clientNameInput = document.getElementById('incClientName');
    const clientPhoneInput = document.getElementById('incClientPhone');
    const isClient = ownership === 'Client';
    if (clientBox) clientBox.classList.toggle('hidden', !isClient);
    if (eggPriceWrap) eggPriceWrap.classList.toggle('hidden', isClient);
    if (clientNameInput) clientNameInput.required = isClient;
    if (clientPhoneInput) clientPhoneInput.required = isClient;
    if (eggCostInput) {
        eggCostInput.required = !isClient;
        if (isClient) eggCostInput.value = '0';
    }
}

window.incBatchesEditMap = <?php
$incEditMap = [];
foreach ($incubationBatches ?? [] as $_ib) {
    $ibId = (int) ($_ib['id'] ?? 0);
    $incEditMap[$ibId] = [
        'id' => $ibId,
        'batch_code' => $_ib['batch_code'] ?? '',
        'species' => $_ib['species'] ?? 'chicken',
        'setter_id' => $_ib['setter_id'] ?? 'Setter A',
        'parent_flock_info' => $_ib['parent_flock_info'] ?? '',
        'flock_age_weeks' => (int) ($_ib['flock_age_weeks'] ?? 0),
        'eggs_set' => (int) ($_ib['eggs_set'] ?? 0),
        'start_date' => $_ib['start_date'] ?? '',
        'status' => $_ib['status'] ?? 'Active',
        'ownership_type' => !empty($_ib['is_client_service']) ? 'Client' : 'Farm',
        'client_name' => $_ib['client_name'] ?? '',
        'client_phone' => $_ib['client_phone'] ?? '',
        'service_fee' => (float) ($_ib['service_fee'] ?? 0),
        'egg_cost' => (float) ($_ib['egg_cost'] ?? ($_ib['metrics']['egg_cost'] ?? 0)),
    ];
}
echo json_encode($incEditMap, JSON_UNESCAPED_UNICODE);
?>;

function openEditIncBatchModal(batchId) {
    const b = window.incBatchesEditMap[String(batchId)] || window.incBatchesEditMap[batchId];
    if (!b) return;
    document.getElementById('editIncBatchId').value = b.id;
    document.getElementById('editIncBatchCode').value = b.batch_code || '';
    document.getElementById('editIncStartDate').value = b.start_date || '';
    document.getElementById('editIncSpecies').value = b.species || 'chicken';
    document.getElementById('editIncSetterId').value = b.setter_id || 'Setter A';
    document.getElementById('editIncParentInfo').value = b.parent_flock_info || '';
    document.getElementById('editIncFlockAge').value = b.flock_age_weeks ?? 0;
    document.getElementById('editIncEggsSet').value = b.eggs_set ?? 1;

    const statusSel = document.getElementById('editIncStatus');
    if (statusSel) {
        if (b.status && ![...statusSel.options].some((o) => o.value === b.status)) {
            const opt = document.createElement('option');
            opt.value = b.status;
            opt.textContent = b.status;
            statusSel.appendChild(opt);
        }
        statusSel.value = b.status || 'Active';
    }

    const isClient = b.ownership_type === 'Client';
    document.getElementById('editIncClientFields').classList.toggle('hidden', !isClient);
    document.getElementById('editIncFarmCostWrap').classList.toggle('hidden', isClient);
    document.getElementById('editIncClientName').value = b.client_name || '';
    document.getElementById('editIncClientPhone').value = b.client_phone || '';
    document.getElementById('editIncServiceFee').value = b.service_fee ?? 0;
    document.getElementById('editIncEggCost').value = b.egg_cost ?? 0;

    openModal('editIncubationBatchModal');
}
</script>

<!-- MODAL: EDIT INCUBATION BATCH -->
<div id="editIncubationBatchModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="editIncubationBatchModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-amber-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Incubator Batch</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Update batch details, species, egg count, or client billing.</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editIncubationBatchModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=incubation" class="space-y-3 text-xs">
            @csrf
            <input type="hidden" name="action" value="update_incubation_batch">
            <input type="hidden" name="incubation_batch_id" id="editIncBatchId">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Batch Code</label>
                    <input type="text" name="batch_code" id="editIncBatchCode" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" id="editIncStartDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Species</label>
                    <select name="species" id="editIncSpecies"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-semibold focus:border-amber-500 focus:outline-none">
                        <option value="chicken">Chicken (21 Days)</option>
                        <option value="turkey">Turkey (28 Days)</option>
                        <option value="duck">Duck - Pekin/Mallard (28 Days)</option>
                        <option value="muscovy_duck">Muscovy Duck (35 Days)</option>
                        <option value="guinea_fowl">Guinea Fowl (28 Days)</option>
                        <option value="quail">Quail (17 Days)</option>
                        <option value="goose">Goose (30 Days)</option>
                        <option value="ostrich">Ostrich (42 Days)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Setter Unit</label>
                    <input type="text" name="setter_id" id="editIncSetterId"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Parent Flock / Source</label>
                    <input type="text" name="parent_flock_info" id="editIncParentInfo"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Parent Age (Wks)</label>
                    <input type="number" name="flock_age_weeks" id="editIncFlockAge" min="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Eggs Set</label>
                    <input type="number" name="eggs_set" id="editIncEggsSet" min="1" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Status</label>
                    <select name="status" id="editIncStatus"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 focus:border-amber-500 focus:outline-none">
                        <option value="Active">Active</option>
                        <option value="Completed">Completed</option>
                        <option value="Moved to Production">Moved to Production</option>
                        <option value="Collected">Collected</option>
                    </select>
                </div>
            </div>

            <div id="editIncFarmCostWrap">
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Egg Purchase / Stock Cost (₦)</label>
                <input type="number" step="any" min="0" name="egg_cost" id="editIncEggCost"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono focus:border-amber-500 focus:outline-none">
            </div>

            <div id="editIncClientFields" class="hidden space-y-2.5 p-3 rounded-2xl bg-purple-500/10 border border-purple-500/30">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-purple-700 dark:text-purple-300 mb-1">Client Name</label>
                        <input type="text" name="client_name" id="editIncClientName"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-purple-700 dark:text-purple-300 mb-1">Client Phone</label>
                        <input type="text" name="client_phone" id="editIncClientPhone"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-purple-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-purple-700 dark:text-purple-300 mb-1">Service Fee (₦)</label>
                        <input type="number" step="any" min="0" name="service_fee" id="editIncServiceFee"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-1.5 text-xs font-mono focus:border-purple-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('editIncubationBatchModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">
                    Save Batch Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

