@extends('layouts.app')

@section('title', 'Eggs Stockroom & Distribution — Blackswan Farms and General Enterprises')
@section('page_heading', 'Eggs Stockroom, Hatch Transfers & Sales')
@section('page_subheading', 'Track available egg crates, send eggs straight to your Incubator, or record egg sales.')

@section('content')
@php
    $ov = $layersOverview ?? ['flocks' => [], 'transfers' => [], 'sales' => [], 'summary' => []];
    $flocks = $ov['flocks'] ?? [];
    $transfers = $ov['transfers'] ?? [];
    $sales = $ov['sales'] ?? [];
    $sum = $ov['summary'] ?? [];
    $availEggs = (int) ($sum['available_eggs'] ?? 0);
@endphp

<div class="space-y-6">
    <!-- Top Header & Action Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Eggs Stockroom &amp; Allocation
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Standard 30 eggs = 1 crate. Transfer available eggs into your Incubator or sell by crate / piece.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="/?page=layers"
               class="px-3.5 py-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/80 dark:bg-slate-900/70 hover:bg-slate-200/70 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-mono font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-feather text-emerald-500"></i>
                <span>Layers &amp; Collect</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
            <button type="button" onclick="openSellEggsModal()"
                    class="px-4 py-2 rounded-xl border border-cyan-500/40 bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-700 dark:text-cyan-300 text-xs font-mono font-bold transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-receipt"></i>
                <span>+ Sell Eggs</span>
            </button>
            <button type="button" onclick="openTransferEggsModal()"
                    class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-emerald-500/25 transition cursor-pointer flex items-center gap-1.5">
                <i class="fa-solid fa-temperature-arrow-up"></i>
                <span>Transfer to Incubator</span>
            </button>
        </div>
    </div>

    <!-- 4 KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-4 border border-emerald-500/35">
            <span class="text-[10px] font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-300 font-semibold">Available Egg Stock</span>
            <div class="text-2xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                {{ number_format($availEggs) }} pcs
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                {{ number_format((int) ($sum['available_crates'] ?? 0)) }} crate(s), {{ (int) ($sum['available_loose'] ?? 0) }} pcs ready now
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-amber-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-amber-600 dark:text-amber-300 font-semibold">Total Eggs Collected</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                {{ number_format((int) ($sum['total_good_eggs'] ?? 0)) }} pcs
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                {{ number_format((int) ($sum['total_good_crates'] ?? 0)) }} crates • From {{ count($flocks) }} layer flock(s)
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-purple-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-purple-600 dark:text-purple-300 font-semibold">Sent to Incubator</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                {{ number_format((int) ($sum['total_transferred_eggs'] ?? 0)) }} pcs
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Across {{ (int) ($sum['transfer_batch_count'] ?? 0) }} setter batch(es)
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-cyan-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-300 font-semibold">Egg Sales Revenue</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                ₦{{ number_format((float) ($sum['egg_sales_billed'] ?? 0), 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Paid: ₦{{ number_format((float) ($sum['egg_sales_collected'] ?? 0), 0) }} • Due: ₦{{ number_format((float) ($sum['egg_sales_receivables'] ?? 0), 0) }}
            </p>
        </div>
    </div>

    <!-- Egg Stock Breakdown by Layer Flock -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-amber-500"></i> Egg Stock by Layer Flock
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Live eggs available per source flock for hatch setting or direct sale.</p>
            </div>
            @if ((int) ($sum['unassigned_sold_eggs'] ?? 0) > 0)
                <span class="text-[11px] font-mono px-2.5 py-1 rounded-lg bg-cyan-500/10 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30">
                    Combined Stock Sold: {{ number_format((int) $sum['unassigned_sold_eggs']) }} pcs
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
                <thead class="bg-slate-100/80 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Flock</th>
                        <th class="py-3 px-4">Species</th>
                        <th class="py-3 px-4">Collected</th>
                        <th class="py-3 px-4">Transferred</th>
                        <th class="py-3 px-4">Sold</th>
                        <th class="py-3 px-4">Available Now</th>
                        <th class="py-3 px-4 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                    @forelse ($flocks as $f)
                        <tr>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $f['batch_code'] }}</span>
                                <span class="block text-[10px] font-sans text-slate-500">{{ $f['flock_name'] }}</span>
                            </td>
                            <td class="py-3 px-4 font-sans">{{ $f['species_label'] }}</td>
                            <td class="py-3 px-4">{{ number_format((int) $f['total_good_eggs']) }} pcs</td>
                            <td class="py-3 px-4 text-purple-600 dark:text-purple-300">{{ number_format((int) $f['transferred_eggs']) }} pcs</td>
                            <td class="py-3 px-4 text-cyan-600 dark:text-cyan-300">{{ number_format((int) $f['sold_eggs']) }} pcs</td>
                            <td class="py-3 px-4 font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format((int) $f['available_eggs']) }} pcs
                                <span class="block text-[10px] text-slate-500 font-normal">{{ $f['available_crates'] }} cr, {{ $f['available_loose'] }} pcs</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap space-x-1.5">
                                <button type="button" onclick="openTransferEggsModal({{ (int) $f['id'] }}, {{ (int) min($f['available_eggs'], $availEggs) }})"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-[11px] font-bold transition cursor-pointer">
                                    Transfer
                                </button>
                                <button type="button" onclick="openSellEggsModal({{ (int) $f['id'] }})"
                                        class="px-2.5 py-1 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30 text-[11px] font-bold transition cursor-pointer">
                                    Sell
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                                No eggs collected yet. <a href="/?page=layers" class="text-emerald-500 font-bold hover:underline">Go to Layers Page to collect eggs →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2-Tab Ledger: Egg Sales & Incubator Transfers -->
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 dark:border-slate-800 pb-2">
            <button type="button" id="tabBtnEggSales" onclick="switchEggLedgerTab('eggSalesTab')"
                    class="px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-mono font-bold shadow-sm transition cursor-pointer">
                Egg Sales Ledger ({{ count($sales) }})
            </button>
            <button type="button" id="tabBtnEggTransfers" onclick="switchEggLedgerTab('eggTransfersTab')"
                    class="px-4 py-2 rounded-xl bg-white/70 dark:bg-slate-900/70 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 text-xs font-mono font-bold transition cursor-pointer">
                Incubator Transfers ({{ count($transfers) }})
            </button>
        </div>

        <!-- TAB 1: EGG SALES LEDGER -->
        <div id="eggSalesTab" class="glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-100/80 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Source</th>
                            <th class="py-3 px-4">Buyer &amp; Phone</th>
                            <th class="py-3 px-4">Quantity</th>
                            <th class="py-3 px-4">Total (₦)</th>
                            <th class="py-3 px-4">Paid / Balance</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                        @forelse ($sales as $es)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ $es['sale_date'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-bold">{{ $es['flock_code'] }}</span>
                                    <span class="block text-[10px] font-sans text-slate-500">{{ $es['flock_name'] }}</span>
                                </td>
                                <td class="py-3 px-4 font-sans">
                                    <span class="font-semibold text-slate-900 dark:text-white">{{ $es['buyer_name'] }}</span>
                                    @if (!empty($es['buyer_phone']))
                                        <span class="block text-[10px] font-mono text-slate-500">{{ $es['buyer_phone'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-bold">{{ $es['quantity'] }} {{ $es['unit_mode'] }}</span>
                                    <span class="block text-[10px] text-slate-500">({{ number_format((int) $es['total_eggs']) }} eggs @ ₦{{ number_format((float) $es['unit_price'], 2) }})</span>
                                </td>
                                <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">₦{{ number_format((float) $es['total_amount'], 2) }}</td>
                                <td class="py-3 px-4">
                                    <span class="text-emerald-600 dark:text-emerald-400">Paid: ₦{{ number_format((float) $es['amount_paid'], 2) }}</span>
                                    @if ((float) $es['balance_due'] > 0)
                                        <span class="block text-[10px] text-amber-600 dark:text-amber-300 font-bold">Due: ₦{{ number_format((float) $es['balance_due'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ ($es['payment_status'] ?? '') === 'Paid' ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' : (($es['payment_status'] ?? '') === 'Partial' ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300' : 'bg-rose-500/20 text-rose-600 dark:text-rose-300') }}">
                                        {{ $es['payment_status'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    @if ((float) ($es['balance_due'] ?? 0) > 0)
                                        <button type="button" onclick="openCollectEggPaymentModal({{ (int) $es['id'] }}, '{{ addslashes($es['buyer_name']) }}', {{ (float) $es['balance_due'] }})"
                                                class="px-2.5 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-[11px] font-bold transition cursor-pointer">
                                            Collect Balance
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Settled</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                                    No egg sales recorded yet. Click <strong>"+ Sell Eggs"</strong> above when you sell crates or eggs.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: INCUBATOR TRANSFERS LEDGER -->
        <div id="eggTransfersTab" class="hidden glass-card rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
                    <thead class="bg-slate-100/80 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Source Layer Flock</th>
                            <th class="py-3 px-4">Eggs Transferred</th>
                            <th class="py-3 px-4">Internal Valuation</th>
                            <th class="py-3 px-4">Incubator Batch</th>
                            <th class="py-3 px-4">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                        @forelse ($transfers as $tr)
                            <tr>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white whitespace-nowrap">{{ $tr['transfer_date'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $tr['flock_code'] }}</span>
                                    <span class="block text-[10px] font-sans text-slate-500">{{ $tr['flock_name'] }}</span>
                                </td>
                                <td class="py-3 px-4 font-bold text-purple-600 dark:text-purple-300">
                                    {{ number_format((int) $tr['eggs_transferred']) }} pcs
                                    <span class="block text-[10px] text-slate-500 font-normal">{{ $tr['crates'] }} cr, {{ $tr['loose'] }} pcs</span>
                                </td>
                                <td class="py-3 px-4">₦{{ number_format((float) ($tr['egg_valuation'] ?? 0), 2) }}</td>
                                <td class="py-3 px-4">
                                    <a href="/?page=incubation" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-bold text-[11px] hover:bg-emerald-500/25">
                                        {{ $tr['incubation_batch_code'] }} →
                                    </a>
                                </td>
                                <td class="py-3 px-4 font-sans text-slate-500 dark:text-slate-400">{{ $tr['notes'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                                    No eggs transferred to the Incubator yet. Click <strong>"Transfer to Incubator"</strong> above to set eggs into an incubation batch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: TRANSFER EGGS TO INCUBATOR -->
<div id="transferEggsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="transferEggsModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-emerald-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center">
                    <i class="fa-solid fa-temperature-arrow-up"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Transfer Eggs to Incubator</h3>
            </div>
            <button type="button" onclick="closeModal('transferEggsModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=eggs" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="transfer_eggs_to_incubator">

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Source Layer Flock</label>
                <select name="layer_flock_id" id="transferFlockSelect" required
                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                    @forelse ($flocks as $f)
                        <option value="{{ $f['id'] }}">{{ $f['batch_code'] }} • {{ $f['flock_name'] }} ({{ (int) $f['available_eggs'] }} avail)</option>
                    @empty
                        <option value="">Collect eggs on Layers page first</option>
                    @endforelse
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-emerald-600 dark:text-emerald-300 mb-1">Eggs to Transfer (pcs)</label>
                    <input type="number" name="eggs_transferred" id="transferEggsQty" min="1" placeholder="e.g. 60" required
                           class="w-full rounded-xl border border-emerald-500/50 bg-white/90 dark:bg-slate-800/90 text-emerald-600 dark:text-emerald-300 font-bold px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Setter Unit</label>
                    <input type="text" name="setter_id" value="Setter A" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Transfer / Set Date</label>
                    <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Internal Valuation (₦, optional)</label>
                    <input type="number" step="0.01" min="0" name="egg_valuation" value="0" placeholder="0.00"
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Notes</label>
                <input type="text" name="notes" placeholder="e.g. Fertile breeder eggs set in Tray 1-3"
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-emerald-500">
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('transferEggsModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">Load into Incubator</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 2: SELL EGGS -->
<div id="sellEggsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="sellEggsModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-cyan-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-500 flex items-center justify-center">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Record Egg Sale</h3>
            </div>
            <button type="button" onclick="closeModal('sellEggsModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=eggs" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="record_egg_sale">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Source Egg Stock</label>
                    <select name="layer_flock_id" id="sellFlockSelect"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                        <option value="">Combined Egg Stock ({{ $availEggs }} avail)</option>
                        @foreach ($flocks as $f)
                            <option value="{{ $f['id'] }}">{{ $f['batch_code'] }} • {{ $f['flock_name'] }} ({{ (int) $f['available_eggs'] }} avail)</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Sale Date</label>
                    <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Unit</label>
                    <select name="unit_mode" id="sellEggUnitMode" onchange="updateEggSalePreview()"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                        <option value="crates">Crates (30 pcs)</option>
                        <option value="pieces">Pieces (1 pc)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Quantity</label>
                    <input type="number" step="0.5" min="0.5" name="quantity" id="sellEggQty" value="1" oninput="updateEggSalePreview()" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-cyan-600 dark:text-cyan-300 mb-1">Price / Unit (₦)</label>
                    <input type="number" step="0.01" min="0" name="unit_price" id="sellEggUnitPrice" placeholder="5500" oninput="updateEggSalePreview()" required
                           class="w-full rounded-xl border border-cyan-500/50 bg-white/90 dark:bg-slate-800/90 text-cyan-600 dark:text-cyan-300 font-bold px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
            </div>

            <div class="rounded-2xl bg-slate-100/90 dark:bg-slate-900/80 border border-cyan-500/30 p-3 flex items-center justify-between font-mono">
                <span class="text-slate-500 dark:text-slate-400">Total: <strong id="sellEggTotalPieces" class="text-slate-900 dark:text-white">30 eggs</strong></span>
                <span id="sellEggTotalAmount" class="text-sm font-extrabold text-emerald-600 dark:text-emerald-400">₦ 0.00</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Buyer Name</label>
                    <input type="text" name="buyer_name" placeholder="e.g. Alhaji Musa Store" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 outline-none focus:border-cyan-500">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Buyer Phone (Optional)</label>
                    <input type="text" name="buyer_phone" placeholder="e.g. 0803..."
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Payment Status</label>
                    <select name="payment_status" id="sellEggPaymentStatus" onchange="toggleEggPartialPaid()"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono outline-none focus:border-cyan-500">
                        <option value="Paid">Paid (Full Cash/Transfer)</option>
                        <option value="Partial">Partial Payment</option>
                        <option value="Credit">Credit (Pay Later)</option>
                    </select>
                </div>
                <div id="sellEggPartialWrap" class="hidden">
                    <label class="block text-[11px] font-medium text-amber-600 dark:text-amber-300 mb-1">Amount Paid Now (₦)</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" value="0"
                           class="w-full rounded-xl border border-amber-500/50 bg-white/90 dark:bg-slate-800/90 text-amber-600 dark:text-amber-300 font-bold px-3 py-2 font-mono outline-none">
                </div>
            </div>

            <div class="pt-2 flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('sellEggsModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-emerald-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">Complete Egg Sale</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL 3: COLLECT EGG SALE BALANCE -->
<div id="collectEggPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="collectEggPaymentModalBox" class="glass-card max-w-md w-full rounded-3xl p-6 border border-amber-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Collect Egg Sale Balance</h3>
            <button type="button" onclick="closeModal('collectEggPaymentModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=eggs" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="action" value="record_egg_sale_payment">
            <input type="hidden" name="egg_sale_id" id="collectEggSaleId" value="">

            <div class="rounded-xl bg-amber-500/10 border border-amber-500/30 p-3 font-mono">
                <div id="collectEggBuyerLabel" class="font-bold text-slate-900 dark:text-white">Buyer</div>
                <div id="collectEggBalanceLabel" class="text-amber-600 dark:text-amber-300 mt-1">Balance Due: ₦0.00</div>
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Payment Received (₦)</label>
                <input type="number" step="0.01" min="0.01" name="payment_amount" id="collectEggPayAmount" required
                       class="w-full rounded-xl border border-amber-500/50 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 font-mono font-bold outline-none">
            </div>

            <div class="flex justify-end gap-2.5">
                <button type="button" onclick="closeModal('collectEggPaymentModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-mono font-bold cursor-pointer">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
  function switchEggLedgerTab(tabId) {
    const salesTab = document.getElementById('eggSalesTab');
    const transTab = document.getElementById('eggTransfersTab');
    const btnSales = document.getElementById('tabBtnEggSales');
    const btnTrans = document.getElementById('tabBtnEggTransfers');
    if (!salesTab || !transTab || !btnSales || !btnTrans) return;

    const activeCls = 'px-4 py-2 rounded-xl bg-emerald-500 text-slate-950 text-xs font-mono font-bold shadow-sm transition cursor-pointer';
    const inactiveCls = 'px-4 py-2 rounded-xl bg-white/70 dark:bg-slate-900/70 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 text-xs font-mono font-bold transition cursor-pointer';

    if (tabId === 'eggTransfersTab') {
      salesTab.classList.add('hidden');
      transTab.classList.remove('hidden');
      btnTrans.className = activeCls;
      btnSales.className = inactiveCls;
    } else {
      transTab.classList.add('hidden');
      salesTab.classList.remove('hidden');
      btnSales.className = activeCls;
      btnTrans.className = inactiveCls;
    }
  }

  function openTransferEggsModal(flockId, maxAvail) {
    const sel = document.getElementById('transferFlockSelect');
    const qty = document.getElementById('transferEggsQty');
    if (sel && flockId) sel.value = String(flockId);
    if (qty && maxAvail > 0) qty.max = String(maxAvail);
    openModal('transferEggsModal');
  }

  function openSellEggsModal(flockId) {
    const sel = document.getElementById('sellFlockSelect');
    if (sel) sel.value = flockId ? String(flockId) : '';
    updateEggSalePreview();
    openModal('sellEggsModal');
  }

  function updateEggSalePreview() {
    const mode = document.getElementById('sellEggUnitMode')?.value || 'crates';
    const qty = Math.max(0, parseFloat(document.getElementById('sellEggQty')?.value || '0'));
    const price = Math.max(0, parseFloat(document.getElementById('sellEggUnitPrice')?.value || '0'));
    const totalEggs = mode === 'crates' ? Math.round(qty * 30) : Math.round(qty);
    const totalAmt = qty * price;
    const pcsEl = document.getElementById('sellEggTotalPieces');
    const amtEl = document.getElementById('sellEggTotalAmount');
    if (pcsEl) pcsEl.textContent = totalEggs.toLocaleString('en-US') + ' eggs';
    if (amtEl) amtEl.textContent = '₦ ' + totalAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function toggleEggPartialPaid() {
    const status = document.getElementById('sellEggPaymentStatus')?.value || 'Paid';
    const wrap = document.getElementById('sellEggPartialWrap');
    if (wrap) wrap.classList.toggle('hidden', status !== 'Partial');
  }

  function openCollectEggPaymentModal(saleId, buyerName, balanceDue) {
    document.getElementById('collectEggSaleId').value = String(saleId);
    document.getElementById('collectEggBuyerLabel').textContent = buyerName;
    document.getElementById('collectEggBalanceLabel').textContent = 'Balance Due: ₦' + Number(balanceDue).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const payInp = document.getElementById('collectEggPayAmount');
    payInp.value = String(balanceDue);
    payInp.max = String(balanceDue);
    openModal('collectEggPaymentModal');
  }
</script>
@endsection
