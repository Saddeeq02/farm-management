@extends('layouts.app')

@section('title', 'Layers Management — Blackswan Farms and General Enterprises')
@section('page_heading', 'Layers & Egg Collection')
@section('page_subheading', 'Track laying flocks, daily egg output, feed & medication care, and mortality.')

@section('content')
@php
    $ov = $layersOverview ?? ['flocks' => [], 'collection_logs' => [], 'summary' => []];
    $flocks = $ov['flocks'] ?? [];
    $logs = $ov['collection_logs'] ?? [];
    $sum = $ov['summary'] ?? [];
    $activeFlocks = array_filter($flocks, fn($f) => ($f['status'] ?? 'Active') === 'Active');
    $feedItems = array_filter($inventoryItems ?? [], fn($it) => in_array($it['category'] ?? '', ['layer', 'grower', 'starter'], true));
    $medItems = array_filter($inventoryItems ?? [], fn($it) => ($it['category'] ?? '') === 'medicine');
    $profiles = \App\Core\VeterinaryKnowledgeBase::getIncubationSpeciesProfiles();
@endphp

<div class="space-y-6">
    <!-- Top Header & Action Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Layers &amp; Breeder Flocks
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Log daily egg collections (30 eggs = 1 crate), feed, and flock care — collected eggs flow straight to your Eggs Stockroom.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/?page=eggs"
               class="px-3.5 py-2 rounded-xl border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 text-xs font-mono font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-egg"></i>
                <span>Eggs Store ({{ number_format((int) ($sum['available_eggs'] ?? 0)) }})</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
            <button type="button" onclick="openLayerDailyModal()"
                    class="px-4 py-2 rounded-xl border border-cyan-500/40 bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-700 dark:text-cyan-300 text-xs font-mono font-bold transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>+ Log Daily Eggs &amp; Care</span>
            </button>
            <button type="button" onclick="openModal('addLayerFlockModal')"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/25 transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>+ Add Layer Flock</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-4 border border-emerald-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-300 font-semibold">Live Layer Birds</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                {{ number_format((int) ($sum['live_layers'] ?? 0)) }} birds
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Across {{ (int) ($sum['active_flock_count'] ?? 0) }} active flock(s)
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-cyan-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-300 font-semibold">Laying Rate (Latest)</span>
            <div class="text-2xl font-extrabold font-mono text-cyan-600 dark:text-cyan-300 mt-1">
                {{ number_format((float) ($sum['avg_laying_rate_pct'] ?? 0), 1) }}%
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Latest eggs collected / live hens
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-amber-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-amber-600 dark:text-amber-300 font-semibold">Total Eggs Produced</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                {{ number_format((int) ($sum['total_good_eggs'] ?? 0)) }} pcs
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                {{ number_format((int) ($sum['total_good_crates'] ?? 0)) }} crates, {{ (int) ($sum['total_good_loose'] ?? 0) }} pcs • {{ number_format((int) ($sum['total_cracked_eggs'] ?? 0)) }} cracked
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-purple-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-purple-600 dark:text-purple-300 font-semibold">Layer Flock Investment</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                ₦{{ number_format((float) ($sum['total_layer_cost'] ?? 0), 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Birds + layer feed &amp; meds spent
            </p>
        </div>
    </div>

    <!-- Active Layer Flocks Grid -->
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Layer &amp; Breeder Flocks
            </h3>
            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ count($flocks) }} Flock(s)</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse ($flocks as $f)
                <div class="glass-card rounded-2xl p-5 flex flex-col justify-between space-y-4">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-mono font-extrabold text-slate-900 dark:text-white">{{ $f['batch_code'] }}</span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold {{ ($f['status'] ?? 'Active') === 'Active' ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : 'bg-slate-500/20 text-slate-500 dark:text-slate-400 border border-slate-500/30' }}">
                                    {{ $f['status'] ?? 'Active' }}
                                </span>
                            </div>
                            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">Wk {{ (int) ($f['current_age_weeks'] ?? 20) }}</span>
                        </div>

                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-1.5 truncate">
                            {{ $f['flock_name'] }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                            {{ $f['species_label'] }} • Started {{ $f['start_date'] }}
                        </p>

                        <div class="mt-3.5 grid grid-cols-3 gap-2 pt-3 border-t border-slate-200/70 dark:border-slate-800/70 font-mono text-[11px]">
                            <div>
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400">Live Birds</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ number_format((int) $f['current_bird_count']) }}/{{ number_format((int) $f['initial_bird_count']) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-slate-500 dark:text-slate-400">Laying %</span>
                                <span class="font-bold text-cyan-600 dark:text-cyan-300">{{ number_format((float) $f['latest_laying_rate_pct'], 1) }}%</span>
                            </div>
                            <div>
                                <span class="block text-[10px] text-amber-600 dark:text-amber-300">Eggs Avail</span>
                                <span class="font-bold text-amber-600 dark:text-amber-300">{{ number_format((int) $f['available_eggs']) }} pcs</span>
                            </div>
                        </div>

                        <div class="mt-2.5 grid grid-cols-2 gap-2 text-[11px] font-mono text-slate-600 dark:text-slate-300 bg-slate-100/70 dark:bg-slate-900/60 rounded-xl p-2.5 border border-slate-200/60 dark:border-slate-800/70">
                            <div>
                                <span class="block text-[9px] text-slate-400">TOTAL LAID</span>
                                <span class="font-bold">{{ number_format((int) $f['total_good_eggs']) }} ({{ $f['total_good_crates'] }} cr)</span>
                            </div>
                            <div>
                                <span class="block text-[9px] text-slate-400">FEED + MED COST / EGG</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">₦{{ number_format((float) $f['cost_per_egg'], 2) }} <span class="text-[10px] font-normal text-slate-500">(₦{{ number_format((float) ($f['cost_per_crate'] ?? 0), 0) }}/cr)</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/70 dark:border-slate-800/70">
                        @if (($f['status'] ?? 'Active') === 'Active')
                            <button type="button" onclick="openLayerDailyModal({{ (int) $f['id'] }})"
                                    class="flex-1 py-2 px-3 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-[11px] font-mono font-bold transition cursor-pointer">
                                + Log Today's Eggs
                            </button>
                            <form method="POST" action="/?page=layers" onsubmit="return confirm('Retire this layer flock? Existing egg stock will remain available.')">
                                @csrf
                                <input type="hidden" name="action" value="retire_layer_flock">
                                <input type="hidden" name="layer_flock_id" value="{{ $f['id'] }}">
                                <button type="submit" class="py-2 px-2.5 rounded-xl bg-slate-200/80 dark:bg-slate-800 hover:bg-rose-500/20 text-slate-600 dark:text-slate-400 hover:text-rose-400 text-[11px] font-mono transition cursor-pointer" title="Retire Flock">
                                    Retire
                                </button>
                            </form>
                        @else
                            <a href="/?page=eggs" class="flex-1 text-center py-2 px-3 rounded-xl bg-slate-200/60 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[11px] font-mono font-semibold">
                                View Egg Stock →
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="md:col-span-2 xl:col-span-3 glass-card rounded-2xl p-8 text-center space-y-3">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-emerald-500/15 text-emerald-500 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-feather"></i>
                    </div>
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">No Layer Flocks Added Yet</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                        Add your laying hens or breeder flocks to start logging daily eggs, crates, mortality, feed, and medications.
                    </p>
                    <button type="button" onclick="openModal('addLayerFlockModal')"
                            class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 text-xs font-mono font-bold cursor-pointer">
                        + Add First Layer Flock
                    </button>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Daily Egg Collection & Flock Care Logs -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-book-open text-emerald-500"></i> Daily Egg Collection &amp; Care History
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Good eggs collected flow straight into your Eggs Stockroom (30 eggs = 1 crate).</p>
            </div>
            <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ count($logs) }} Log(s)</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-100/80 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Flock</th>
                        <th class="py-3 px-4">Good Eggs</th>
                        <th class="py-3 px-4">Cracked</th>
                        <th class="py-3 px-4">Mortality</th>
                        <th class="py-3 px-4">Feed Used</th>
                        <th class="py-3 px-4">Medications</th>
                        <th class="py-3 px-4">Notes</th>
                        <th class="py-3 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                    @forelse ($logs as $l)
                        <tr>
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ $l['log_date'] }}</td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $l['flock_code'] }}</span>
                                <span class="block text-[10px] text-slate-500 font-sans">{{ $l['flock_name'] }}</span>
                            </td>
                            <td class="py-3 px-4 font-bold text-amber-600 dark:text-amber-300">
                                {{ number_format((int) $l['good_eggs']) }} pcs
                                <span class="block text-[10px] text-slate-500 font-normal">{{ $l['good_crates'] }} cr, {{ $l['good_loose'] }} pcs</span>
                            </td>
                            <td class="py-3 px-4 text-rose-500">{{ number_format((int) $l['cracked_eggs']) }}</td>
                            <td class="py-3 px-4 {{ (int) $l['mortality_count'] > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">
                                {{ (int) $l['mortality_count'] > 0 ? '-' . (int) $l['mortality_count'] : '0' }}
                            </td>
                            <td class="py-3 px-4">
                                @if ((float) ($l['feed_grams'] ?? 0) > 0)
                                    @if ((float) ($l['feed_grams'] ?? 0) < 1000)
                                        <span>{{ \App\Core\FarmService::fmtQty((float) $l['feed_grams']) }} g</span>
                                    @else
                                        <span>{{ \App\Core\FarmService::fmtQty((float) $l['feed_grams'] / 1000) }} kg</span>
                                    @endif
                                    <span class="block text-[10px] text-emerald-600 dark:text-emerald-400">₦{{ number_format((float) $l['feed_cost_calculated'], 2) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if (!empty($l['medications_used']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($l['medications_used'] as $mu)
                                            <span class="px-1.5 py-0.5 rounded bg-purple-500/15 text-purple-600 dark:text-purple-300 border border-purple-500/25 text-[10px]">
                                                {{ $mu['item_name'] }} ({{ $mu['dose'] }}{{ $mu['unit'] ?? '' }})
                                            </span>
                                        @endforeach
                                    </div>
                                    <span class="block text-[10px] text-purple-500 mt-0.5">₦{{ number_format((float) $l['med_cost_calculated'], 2) }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-sans text-slate-500 dark:text-slate-400">{{ $l['notes'] ?: '—' }}</td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <button type="button" onclick="openEditLayerLogModal({{ (int) $l['id'] }})"
                                        class="px-2.5 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-[10px] font-sans font-semibold transition cursor-pointer">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                                No daily egg collections logged yet. Click <strong>"+ Log Daily Eggs &amp; Care"</strong> above to record your first collection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: ADD LAYER FLOCK -->
<div id="addLayerFlockModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="addLayerFlockModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-emerald-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                    <i class="fa-solid fa-feather"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Add Layer / Breeder Flock</h3>
            </div>
            <button type="button" onclick="closeModal('addLayerFlockModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=layers" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="create_layer_flock">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Flock Code (Auto if blank)</label>
                    <input type="text" name="batch_code" placeholder="e.g. LAY-{{ date('Y') }}-01"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Species</label>
                    <select name="species" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-emerald-500">
                        @foreach ($profiles as $sKey => $sProf)
                            <option value="{{ $sKey }}">{{ $sProf['label'] }} ({{ $sProf['total_days'] }}d Hatch)</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Flock Name / Breed</label>
                <input type="text" name="flock_name" placeholder="e.g. ISA Brown Layers, Noiler Breeders, Local Turkeys" required
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Bird Count</label>
                    <input type="number" name="initial_bird_count" min="1" value="100" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Age (Weeks)</label>
                    <input type="number" name="age_weeks" min="1" value="22" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Flock Cost (₦)</label>
                    <input type="number" step="0.01" min="0" name="purchase_cost" placeholder="0.00" value="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Start Date</label>
                    <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</label>
                    <input type="text" name="notes" placeholder="e.g. Battery cage Pen A"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('addLayerFlockModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">+ Save Layer Flock</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: DAILY EGG COLLECTION & CARE -->
<div id="layerDailyLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="layerDailyLogModalBox" class="glass-card max-w-xl w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-cyan-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-500 flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Log Daily Egg Collection &amp; Care</h3>
            </div>
            <button type="button" onclick="closeModal('layerDailyLogModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=layers" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="record_layer_daily_log">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Select Layer Flock</label>
                    <select name="layer_flock_id" id="layerLogFlockSelect" required
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                        @forelse ($activeFlocks as $af)
                            <option value="{{ $af['id'] }}">{{ $af['batch_code'] }} • {{ $af['flock_name'] }} ({{ $af['current_bird_count'] }} live)</option>
                        @empty
                            <option value="">Add a layer flock first</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Collection Date</label>
                    <input type="date" name="log_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">Good Eggs (pcs)</label>
                    <input type="number" name="good_eggs" id="layerGoodEggsInput" min="0" value="0" oninput="updateEggCratePreview()" required
                           class="w-full rounded-xl border border-amber-500/50 bg-white/90 dark:bg-slate-800/90 text-amber-600 dark:text-amber-300 font-bold px-3 py-2 font-mono outline-none focus:border-amber-500">
                    <span id="layerCratePreview" class="block text-[10px] font-mono text-slate-500 mt-1">0 crates, 0 pcs</span>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Cracked / Broken</label>
                    <input type="number" name="cracked_eggs" min="0" value="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-rose-500 mb-1">Mortality (birds)</label>
                    <input type="number" name="mortality_count" min="0" value="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-rose-500 font-bold px-3 py-2 font-mono outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Feed Consumed (Optional — deducts store)</label>
                    <select name="feed_item_id" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                        <option value="">-- No Feed Deducted --</option>
                        @foreach ($feedItems as $fi)
                            <option value="{{ $fi['id'] }}">{{ $fi['item_name'] }} ({{ number_format((float) $fi['total_quantity_g_or_ml'] / 1000, 1) }} kg left)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Feed Qty (kg / g)</label>
                    <div class="flex gap-1.5">
                        <input type="number" step="any" min="0" name="feed_qty" placeholder="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-2 font-mono outline-none focus:border-cyan-500">
                        <select name="feed_unit" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2 py-2 font-mono outline-none">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Medications &amp; Supplements Used (Optional)</label>
                <div class="max-h-36 overflow-y-auto space-y-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/60 p-2.5">
                    @forelse ($medItems as $item)
                        <div class="flex items-center justify-between gap-2 bg-white dark:bg-slate-800/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-2 min-w-0 cursor-pointer flex-1">
                                <input type="checkbox" name="med_items[]" value="{{ $item['id'] }}"
                                       onchange="const inp = this.closest('div').querySelector('input[type=number]'); inp.disabled = !this.checked; if(!this.checked) inp.value=''; else inp.focus();"
                                       class="rounded text-emerald-500 focus:ring-emerald-500">
                                <span class="text-[11px] font-medium text-slate-800 dark:text-slate-100 truncate">
                                    {{ $item['item_name'] }}
                                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">({{ number_format((float)$item['total_quantity_g_or_ml']) }}{{ $item['unit_label'] }})</span>
                                </span>
                            </label>
                            <div class="flex items-center gap-1 shrink-0">
                                <input type="number" step="any" min="0" name="med_doses[{{ $item['id'] }}]" placeholder="Dose" disabled
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-purple-600 dark:text-purple-300 px-2 py-1 text-[11px] font-mono outline-none disabled:opacity-40">
                                <span class="text-[10px] font-mono text-slate-500">{{ $item['unit_label'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 italic py-1">No medications in store yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</label>
                <input type="text" name="notes" placeholder="e.g. Morning &amp; afternoon egg pick"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-cyan-500">
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('layerDailyLogModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-emerald-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">Save Daily Record</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: EDIT LAYER DAILY EGG LOG -->
<div id="editLayerLogModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="editLayerLogModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-amber-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-pen"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Layer Daily Egg Log</h3>
                    <p id="editLayerLogSubtitle" class="text-[11px] text-slate-500 dark:text-slate-400"></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editLayerLogModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=layers" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="update_layer_daily_log">
            <input type="hidden" name="log_id" id="editLayerLogId">
            <input type="hidden" name="med_editor_present" value="1">

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Collection Date</label>
                <input type="date" name="log_date" id="editLayerLogDate" required
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">Good Eggs (pcs)</label>
                    <input type="number" name="good_eggs" id="editLayerLogGoodEggs" min="0" required
                           class="w-full rounded-xl border border-amber-500/50 bg-white/90 dark:bg-slate-800/90 text-amber-600 dark:text-amber-300 font-bold px-3 py-2 font-mono outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Cracked / Broken</label>
                    <input type="number" name="cracked_eggs" id="editLayerLogCrackedEggs" min="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-rose-500 mb-1">Mortality (birds)</label>
                    <input type="number" name="mortality_count" id="editLayerLogMortality" min="0"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-rose-500 font-bold px-3 py-2 font-mono outline-none focus:border-rose-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Feed Consumed (Refunds previous &amp; deducts updated)</label>
                    <select name="feed_item_id" id="editLayerLogFeedItem"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-amber-500">
                        <option value="">-- No Feed Deducted --</option>
                        @foreach ($feedItems as $fi)
                            <option value="{{ $fi['id'] }}">{{ $fi['item_name'] }} ({{ number_format((float) $fi['total_quantity_g_or_ml'] / 1000, 1) }} kg left)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Feed Qty (kg / g)</label>
                    <div class="flex gap-1.5">
                        <input type="number" step="any" min="0" name="feed_qty" id="editLayerLogFeedQty" placeholder="0"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2.5 py-2 font-mono outline-none focus:border-amber-500">
                        <select name="feed_unit" id="editLayerLogFeedUnit" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-2 py-2 font-mono outline-none">
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1.5">Medications &amp; Supplements Used (Optional)</label>
                <div id="editLayerLogMedList" class="max-h-36 overflow-y-auto space-y-1.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-900/60 p-2.5">
                    @forelse ($medItems as $item)
                        <div class="flex items-center justify-between gap-2 bg-white dark:bg-slate-800/90 rounded-lg px-2.5 py-1.5 border border-slate-200 dark:border-slate-700">
                            <label class="flex items-center gap-2 min-w-0 cursor-pointer flex-1">
                                <input type="checkbox" name="med_items[]" value="{{ $item['id'] }}" data-med-id="{{ $item['id'] }}"
                                       onchange="const inp = this.closest('div').querySelector('input[type=number]'); inp.disabled = !this.checked; if(!this.checked) inp.value=''; else inp.focus();"
                                       class="rounded text-emerald-500 focus:ring-emerald-500">
                                <span class="text-[11px] font-medium text-slate-800 dark:text-slate-100 truncate">
                                    {{ $item['item_name'] }}
                                    <span class="font-mono text-[10px] text-slate-500 dark:text-slate-400">({{ \App\Core\FarmService::fmtQty((float)$item['total_quantity_g_or_ml']) }}{{ $item['unit_label'] }})</span>
                                </span>
                            </label>
                            <div class="flex items-center gap-1 shrink-0">
                                <input type="number" step="any" min="0" name="med_doses[{{ $item['id'] }}]" data-med-dose="{{ $item['id'] }}" placeholder="Dose" disabled
                                       class="w-20 rounded-lg border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-purple-600 dark:text-purple-300 px-2 py-1 text-[11px] font-mono outline-none disabled:opacity-40">
                                <span class="text-[10px] font-mono text-slate-500">{{ $item['unit_label'] }}</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 italic py-1">No medications in store yet.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</label>
                <input type="text" name="notes" id="editLayerLogNotes" placeholder="Notes"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-amber-500">
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('editLayerLogModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
  window.layerLogsEditMap = <?php
  $layerEditMap = [];
  foreach ($logs ?? [] as $_ll) {
      $llId = (int) ($_ll['id'] ?? 0);
      $layerEditMap[$llId] = [
          'id' => $llId,
          'flock_code' => $_ll['flock_code'] ?? '',
          'flock_name' => $_ll['flock_name'] ?? '',
          'log_date' => $_ll['log_date'] ?? '',
          'good_eggs' => (int) ($_ll['good_eggs'] ?? 0),
          'cracked_eggs' => (int) ($_ll['cracked_eggs'] ?? 0),
          'mortality_count' => (int) ($_ll['mortality_count'] ?? 0),
          'feed_item_id' => (int) ($_ll['feed_item_id'] ?? 0),
          'feed_grams' => (float) ($_ll['feed_grams'] ?? 0),
          'medications_used' => $_ll['medications_used'] ?? [],
          'notes' => $_ll['notes'] ?? '',
      ];
  }
  echo json_encode($layerEditMap, JSON_UNESCAPED_UNICODE);
  ?>;

  function openEditLayerLogModal(logId) {
    const l = window.layerLogsEditMap[String(logId)] || window.layerLogsEditMap[logId];
    if (!l) return;
    document.getElementById('editLayerLogId').value = l.id;
    document.getElementById('editLayerLogSubtitle').textContent = (l.flock_code || '') + (l.flock_name ? ' • ' + l.flock_name : '');
    document.getElementById('editLayerLogDate').value = l.log_date || '';
    document.getElementById('editLayerLogGoodEggs').value = l.good_eggs ?? 0;
    document.getElementById('editLayerLogCrackedEggs').value = l.cracked_eggs ?? 0;
    document.getElementById('editLayerLogMortality').value = l.mortality_count ?? 0;
    document.getElementById('editLayerLogFeedItem').value = l.feed_item_id ? String(l.feed_item_id) : '';
    const g = parseFloat(l.feed_grams || 0);
    if (g > 0 && g < 1000) {
      document.getElementById('editLayerLogFeedUnit').value = 'g';
      document.getElementById('editLayerLogFeedQty').value = parseFloat(g.toFixed(4));
    } else if (g >= 1000) {
      document.getElementById('editLayerLogFeedUnit').value = 'kg';
      document.getElementById('editLayerLogFeedQty').value = parseFloat((g / 1000).toFixed(4));
    } else {
      document.getElementById('editLayerLogFeedUnit').value = 'kg';
      document.getElementById('editLayerLogFeedQty').value = 0;
    }
    document.getElementById('editLayerLogNotes').value = l.notes || '';

    const container = document.getElementById('editLayerLogMedList');
    if (container) {
      container.querySelectorAll('input[type="checkbox"][data-med-id]').forEach((cb) => {
        cb.checked = false;
      });
      container.querySelectorAll('input[type="number"][data-med-dose]').forEach((inp) => {
        inp.disabled = true;
        inp.value = '';
      });
      const medEntries = Array.isArray(l.medications_used) ? l.medications_used : [];
      medEntries.forEach((mu) => {
        const mId = String(mu.item_id || mu.med_item_id || '');
        const dose = mu.dose ?? mu.dose_g_ml ?? '';
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

    openModal('editLayerLogModal');
  }

  function openLayerDailyModal(flockId) {
    const sel = document.getElementById('layerLogFlockSelect');
    if (sel && flockId) {
      sel.value = String(flockId);
    }
    updateEggCratePreview();
    openModal('layerDailyLogModal');
  }

  function updateEggCratePreview() {
    const inp = document.getElementById('layerGoodEggsInput');
    const out = document.getElementById('layerCratePreview');
    if (!inp || !out) return;
    const n = Math.max(0, parseInt(inp.value || '0', 10));
    const crates = Math.floor(n / 30);
    const loose = n % 30;
    out.textContent = crates + ' crate(s), ' + loose + ' pcs';
  }
</script>
@endsection

