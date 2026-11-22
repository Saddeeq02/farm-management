@extends('layouts.app')

@section('title', 'Module 4: Pricing Calculator, Sales & Profit Margin Engine')
@section('page_heading', 'Module 4: Pricing Calculator, Sales & Profit Margin Engine')
@section('page_subheading', 'Configure target profit margins per batch, compute exact recommended selling prices, and record commercial bird sales.')

@section('content')
<div class="space-y-6">
    <!-- Sales Command Header Bar -->
    <div class="glass-card rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                <i class="fa-solid fa-receipt text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Pricing Calculator &amp; Commercial Sales Ledger</h2>
                <p class="text-xs text-slate-500 dark:text-slate-300">Configure target profit margins per batch, verify live breakeven prices, and record customer sales.</p>
            </div>
        </div>
        <a href="/?page=dashboard" class="px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-mono font-semibold border border-slate-300 dark:border-slate-700 transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Dashboard
        </a>
    </div>

    <!-- Batch Pricing & Target Margin Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach ($productionBatches as $pb)
            @php $m = $pb['metrics']; @endphp
            <div class="glass-card rounded-2xl overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Card Header -->
                    <div class="px-6 py-4 bg-slate-100/80 dark:bg-slate-900/75 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between gap-3">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono font-bold text-base text-slate-900 dark:text-white">{{ $pb['batch_code'] }}</span>
                                <span class="text-[11px] px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-mono font-semibold">
                                    {{ number_format($m['current_in_house_birds']) }} Unsold Birds
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $pb['flock_type'] }}</p>
                        </div>
                        <div class="text-right font-mono flex flex-col items-end gap-1">
                            <div>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block">Recommended Selling Price</span>
                                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">₦{{ number_format($m['recommended_selling_price'], 2) }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">/ bird</span></span>
                            </div>
                            <button type="button" onclick="openBatchPnLModal({{ (int)$pb['id'] }})" class="text-[10px] px-2.5 py-1 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30 font-bold transition cursor-pointer">
                                <i class="fa-solid fa-file-invoice-dollar mr-1"></i> Batch P&amp;L Report
                            </button>
                        </div>
                    </div>

                    <!-- Cost & Margin Breakdown Grid -->
                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5 font-mono">
                            <div class="p-3.5 rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70">
                                <span class="text-[10px] font-sans font-semibold uppercase text-slate-500 dark:text-slate-400 block">Accumulated Batch Cost</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white mt-1 block">₦{{ number_format($m['total_accumulated_cost'], 2) }}</span>
                                <span class="text-[11px] font-sans text-slate-500 dark:text-slate-400">{{ $m['surviving_biological_birds'] }} surviving pool</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70">
                                <span class="text-[10px] font-sans font-semibold uppercase text-slate-500 dark:text-slate-400 block">Exact Breakeven / Bird</span>
                                <span class="text-sm font-bold text-slate-900 dark:text-white mt-1 block">₦{{ number_format($m['cost_per_bird'], 2) }}</span>
                                <span class="text-[11px] font-sans text-rose-600 dark:text-rose-400">+₦{{ number_format($m['mortality_absorbed_per_bird'], 2) }} mortality absorbed</span>
                            </div>
                            <div class="p-3.5 rounded-xl bg-emerald-500/15 border border-emerald-500/30 col-span-2 sm:col-span-1">
                                <span class="text-[10px] font-sans font-semibold uppercase text-emerald-700 dark:text-emerald-300 block">Net Profit / Bird</span>
                                <span class="text-sm font-bold text-emerald-700 dark:text-emerald-300 mt-1 block">+₦{{ number_format($m['profit_per_bird'], 2) }}</span>
                                <span class="text-[11px] font-sans text-emerald-700 dark:text-emerald-300">@ {{ number_format($m['target_margin_pct'], 1) }}% Margin</span>
                            </div>
                        </div>

                        <!-- Projected vs Realized Revenue Bar -->
                        <div class="p-4 rounded-xl bg-white/70 dark:bg-slate-900/50 border border-slate-200/80 dark:border-slate-700/70 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 dark:text-slate-300 font-medium">Projected Total Batch Revenue:</span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($m['projected_total_revenue'], 2) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-600 dark:text-slate-300 font-medium">Projected Net Batch Profit:</span>
                                <span class="font-mono font-bold {{ $m['projected_net_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $m['projected_net_profit'] >= 0 ? '+' : '' }}₦{{ number_format($m['projected_net_profit'], 2) }}
                                </span>
                            </div>
                            <div class="pt-2 border-t border-slate-200/80 dark:border-slate-700/70 flex items-center justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Realized Sales Revenue ({{ $m['total_birds_sold'] }} birds sold):</span>
                                <span class="font-mono font-semibold text-slate-800 dark:text-slate-200">₦{{ number_format($m['realized_revenue'], 2) }}</span>
                            </div>
                        </div>

                        <!-- Target Profit Margin Configuration Form -->
                        <form method="POST" action="/?page=sales" class="pt-3 border-t border-slate-200/70 dark:border-slate-800/70 flex flex-wrap items-end justify-between gap-3">
                            @csrf
                            <input type="hidden" name="action" value="update_target_margin">
                            <input type="hidden" name="production_batch_id" value="{{ $pb['id'] }}">

                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    Target Profit Margin (%) — <span class="font-mono text-emerald-600 dark:text-emerald-400">Price = Cost × (1 + Margin/100)</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="number" step="0.5" name="target_profit_margin_pct"
                                           id="marginInput_{{ $pb['id'] }}"
                                           value="{{ $m['target_margin_pct'] }}" min="0" max="500" required
                                           oninput="previewBatchMargin({{ $pb['id'] }}, {{ $m['cost_per_bird'] }}, this.value)"
                                           class="w-28 rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-sm font-mono focus:border-emerald-500 focus:outline-none">
                                    <div class="flex items-center gap-1">
                                        @foreach ([15, 25, 30, 40] as $preset)
                                            <button type="button"
                                                    onclick="setPresetMargin({{ $pb['id'] }}, {{ $m['cost_per_bird'] }}, {{ $preset }})"
                                                    class="px-2 py-1 rounded-lg text-xs font-mono border border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-800/70 text-slate-700 dark:text-slate-200 hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400 transition cursor-pointer">
                                                {{ $preset }}%
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                                <p id="marginPreview_{{ $pb['id'] }}" class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-1">
                                    Live Preview: ₦{{ number_format($m['recommended_selling_price'], 2) }} / bird
                                </p>
                            </div>

                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 hover:bg-slate-800 dark:hover:bg-white text-white dark:text-slate-950 text-xs font-mono font-bold transition cursor-pointer active:scale-95">
                                Save Target Margin
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Lower Section: Record Commercial Sale Form + Sales & Depletion Ledger -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Record Commercial Sale Form -->
        <div class="glass-card rounded-2xl p-6">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Commercial Bird Sale</h3>
            <p class="text-xs text-slate-500 dark:text-slate-300 mt-0.5 mb-4">Automatically deducts sold birds from batch inventory and updates realized revenue.</p>

            <form method="POST" action="/?page=sales" class="space-y-3.5">
                @csrf
                <input type="hidden" name="action" value="record_batch_sale">

                @php
                    $defaultSaleBatch = null;
                    foreach ($productionBatches as $_spb) {
                        if ((int)($_spb['metrics']['current_in_house_birds'] ?? 0) > 0) {
                            $defaultSaleBatch = $_spb;
                            break;
                        }
                    }
                    if ($defaultSaleBatch === null && !empty($productionBatches)) {
                        $defaultSaleBatch = $productionBatches[0];
                    }
                    $defaultSaleBatchId = (int)($defaultSaleBatch['id'] ?? 0);
                    $defaultAvailBirds = (int)($defaultSaleBatch['metrics']['current_in_house_birds'] ?? 0);
                    $defaultRecPrice = (float)($defaultSaleBatch['metrics']['recommended_selling_price'] ?? 1700);
                @endphp

                <div>
                    <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Production Batch</label>
                    <select name="production_batch_id" id="saleBatchSelect" onchange="syncRecommendedPriceToSale()" required
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs sm:text-sm focus:border-emerald-500 focus:outline-none">
                        @foreach ($productionBatches as $pb)
                            @php $avail = (int)($pb['metrics']['current_in_house_birds'] ?? 0); @endphp
                            <option value="{{ $pb['id'] }}"
                                    data-rec-price="{{ $pb['metrics']['recommended_selling_price'] }}"
                                    data-available="{{ $avail }}"
                                    {{ (int)$pb['id'] === $defaultSaleBatchId ? 'selected' : '' }}
                                    {{ $avail <= 0 ? 'disabled' : '' }}>
                                {{ $pb['batch_code'] }} ({{ number_format($avail) }} available{{ $avail <= 0 ? ' — Sold Out' : '' }} • Rec: ₦{{ number_format($pb['metrics']['recommended_selling_price'], 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Birds Sold (Qty)</label>
                        <input type="number" name="birds_sold" id="saleBirdsQty" value="1" min="1" max="{{ max(1, $defaultAvailBirds) }}" required
                               oninput="updateSaleTotalPreview()"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Unit Selling Price (₦)</label>
                        <input type="number" step="0.01" name="unit_price" id="saleUnitPrice"
                               value="{{ number_format($defaultRecPrice, 2, '.', '') }}" min="0.01" required
                               oninput="updateSaleTotalPreview()"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-emerald-700 dark:text-emerald-300 px-3.5 py-2 text-sm font-mono font-bold focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="p-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-between font-mono text-xs">
                    <span class="text-emerald-800 dark:text-emerald-300 font-sans font-medium">Total Invoice Amount:</span>
                    <span id="saleTotalPreview" class="text-sm font-bold text-emerald-700 dark:text-emerald-300">
                        ₦{{ number_format($defaultRecPrice, 2) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Customer Name</label>
                        <input type="text" name="buyer_name" placeholder="e.g. Zaria Fresh Mart" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Customer Phone Number</label>
                        <input type="text" name="buyer_contact" placeholder="0803-000-0000"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Payment Status</label>
                        <select name="payment_status" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Amount Paid (₦)</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" placeholder="Auto if Paid"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Sale Date</label>
                        <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs sm:text-sm font-mono font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer active:scale-95">
                    + Record Sale &amp; Deduct Flock Inventory
                </button>
            </form>
        </div>

        <!-- Commercial Sales & Depletion Ledger Table -->
        @php
            $totalReceivables = array_sum(array_map(fn($pb) => (float)($pb['metrics']['total_receivables'] ?? 0), $productionBatches));
        @endphp
        <div class="lg:col-span-2 glass-card rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800/80 bg-slate-100/70 dark:bg-slate-900/60 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Commercial Sales &amp; Flock Depletion Ledger</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-300">All recorded bird sales across production batches with payment collection and printable receipts.</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @if ($totalReceivables > 0)
                        <span class="font-mono text-xs font-bold px-3 py-1 rounded-lg bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30">
                            Receivables: ₦{{ number_format($totalReceivables, 2) }}
                        </span>
                    @endif
                    <span class="font-mono text-xs font-bold px-3 py-1 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30">
                        Realized: ₦{{ number_format($summary['total_realized_revenue'], 2) }}
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto bg-slate-100/40 dark:bg-slate-950/40">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-700/80 bg-slate-100/70 dark:bg-slate-900/60 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 px-3">Batch</th>
                            <th class="py-3.5 px-3">Customer Details</th>
                            <th class="py-3.5 px-3 text-right">Birds</th>
                            <th class="py-3.5 px-3 text-right">Unit Price</th>
                            <th class="py-3.5 px-3 text-right">Total / Paid</th>
                            <th class="py-3.5 px-4 text-right">Status &amp; Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 text-sm">
                        @foreach ($productionBatches as $pb)
                            @foreach ($pb['sales'] as $sale)
                                @php
                                    $balDue = (float) ($sale['balance_due'] ?? 0);
                                    $amtPaid = (float) ($sale['amount_paid'] ?? $sale['total_amount']);
                                @endphp
                                <tr class="hover:bg-white/40 dark:hover:bg-slate-800/30 transition">
                                    <td class="py-3.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-300">{{ $sale['sale_date'] }}</td>
                                    <td class="py-3.5 px-3 font-mono font-semibold text-xs text-slate-900 dark:text-white">{{ $pb['batch_code'] }}</td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-slate-900 dark:text-white text-xs">{{ $sale['buyer_name'] ?? 'Commercial Buyer' }}</div>
                                        @if (!empty($sale['buyer_contact']))
                                            <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">{{ $sale['buyer_contact'] }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-3 text-right font-mono font-semibold text-slate-800 dark:text-slate-200">{{ number_format((int)$sale['birds_sold']) }}</td>
                                    <td class="py-3.5 px-3 text-right font-mono text-xs text-slate-700 dark:text-slate-300">₦{{ number_format((float)$sale['unit_price'], 2) }}</td>
                                    <td class="py-3.5 px-3 text-right font-mono">
                                        <div class="font-bold text-emerald-600 dark:text-emerald-400 text-xs">₦{{ number_format((float)$sale['total_amount'], 2) }}</div>
                                        @if ($balDue > 0)
                                            <div class="text-[10px] text-amber-600 dark:text-amber-300">Paid: ₦{{ number_format($amtPaid, 0) }} • Due: ₦{{ number_format($balDue, 0) }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sale['payment_status'] === 'Paid' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : ($sale['payment_status'] === 'Partial' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/30') }}">
                                                {{ $sale['payment_status'] }}
                                            </span>
                                            @if ($balDue > 0)
                                                <button type="button" onclick="openCollectPaymentModal({{ (int)$sale['id'] }})" class="px-2 py-0.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 text-[10px] font-mono font-bold cursor-pointer">
                                                    Collect
                                                </button>
                                            @endif
                                            <button type="button" onclick="openSaleReceiptModal({{ (int)$sale['id'] }})" class="px-2 py-0.5 rounded-lg bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-[10px] font-mono font-semibold cursor-pointer" title="Print Receipt">
                                                <i class="fa-solid fa-print"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: COLLECT BALANCE PAYMENT -->
<div id="collectPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="collectPaymentModalBox" class="glass-card max-w-md w-full rounded-3xl p-6 border border-amber-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Collect Customer Balance Payment</h3>
            <button type="button" onclick="closeModal('collectPaymentModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" action="/?page=sales" class="space-y-3 text-xs font-mono">
            @csrf
            <input type="hidden" name="action" value="record_sale_payment">
            <input type="hidden" name="sale_id" id="collectSaleId">
            <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30">
                <div class="text-slate-600 dark:text-slate-300">Customer: <strong id="collectCustomerName" class="text-slate-900 dark:text-white"></strong></div>
                <div class="text-amber-700 dark:text-amber-300 mt-1">Outstanding Balance: <strong id="collectBalanceDue">₦0.00</strong></div>
            </div>
            <div>
                <label class="block text-slate-700 dark:text-slate-300 mb-1">Payment Amount Received (₦)</label>
                <input type="number" step="0.01" min="0.01" name="payment_amount" id="collectPaymentAmount" required class="w-full bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-slate-900 dark:text-white font-bold outline-none focus:border-emerald-500">
            </div>
            <div class="pt-2 flex justify-end gap-2">
                <button type="button" onclick="closeModal('collectPaymentModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold cursor-pointer">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: PRINTABLE RECEIPT / BATCH P&L REPORT -->
<div id="printReportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="printReportModalBox" class="bg-white text-slate-900 max-w-xl w-full rounded-3xl p-6 sm:p-8 border border-slate-300 shadow-2xl relative transform scale-95 transition-transform duration-300 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-4">
            <div>
                <h3 id="printModalTitle" class="text-lg font-extrabold text-slate-900">Blackswan Farms and General Enterprises</h3>
                <p class="text-[11px] font-mono text-slate-500">Official Farm Document</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="printDocumentArea()" class="px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-mono font-bold cursor-pointer">
                    <i class="fa-solid fa-print mr-1"></i> Print / Save PDF
                </button>
                <button type="button" onclick="closeModal('printReportModal')" class="text-slate-400 hover:text-slate-900 cursor-pointer"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
        </div>
        <div id="printDocumentBody" class="space-y-4 text-xs"></div>
    </div>
</div>

<script>
window.salesBatchesMap = <?php
$_sbMap = [];
$_srMap = [];
foreach (($productionBatches ?? []) as $_pb) {
    $_pbId = (int) ($_pb['id'] ?? 0);
    $_sbMap[$_pbId] = [
        'id' => $_pbId,
        'batch_code' => $_pb['batch_code'] ?? '',
        'flock_type' => $_pb['flock_type'] ?? '',
        'status' => $_pb['status'] ?? 'Active',
        'age_days' => (int) ($_pb['age_days'] ?? 0),
        'age_weeks' => (int) ($_pb['age_weeks'] ?? 0),
        'metrics' => $_pb['metrics'] ?? [],
    ];
    foreach (($_pb['sales'] ?? []) as $_sl) {
        $_slId = (int) ($_sl['id'] ?? 0);
        $_srMap[$_slId] = array_merge($_sl, [
            'batch_code' => $_pb['batch_code'] ?? '',
            'flock_type' => $_pb['flock_type'] ?? '',
        ]);
    }
}
echo json_encode($_sbMap, JSON_UNESCAPED_UNICODE);
?>;
window.salesRecordsMap = <?php echo json_encode($_srMap, JSON_UNESCAPED_UNICODE); ?>;

function formatSalesNaira(val, decimals = 2) {
    const num = Number(val) || 0;
    return '₦' + num.toLocaleString('en-NG', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

function previewBatchMargin(batchId, costPerBird, marginPct) {
    const pct = parseFloat(marginPct) || 0;
    const recPrice = Number(costPerBird) * (1 + (pct / 100));
    const profitPerBird = recPrice - Number(costPerBird);
    const el = document.getElementById('marginPreview_' + batchId);
    if (el) {
        el.textContent = 'Live Preview: ' + formatSalesNaira(recPrice, 2) + ' / bird (+' + formatSalesNaira(profitPerBird, 2) + ' profit/bird)';
    }
}

function setPresetMargin(batchId, costPerBird, presetPct) {
    const input = document.getElementById('marginInput_' + batchId);
    if (input) {
        input.value = presetPct;
        previewBatchMargin(batchId, costPerBird, presetPct);
    }
}

function syncRecommendedPriceToSale() {
    const sel = document.getElementById('saleBatchSelect');
    const priceInput = document.getElementById('saleUnitPrice');
    const qtyInput = document.getElementById('saleBirdsQty');
    if (!sel || !priceInput) return;
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;
    const rec = opt.getAttribute('data-rec-price');
    const avail = parseInt(opt.getAttribute('data-available') || '0', 10);
    if (rec !== null && rec !== '') {
        priceInput.value = parseFloat(rec).toFixed(2);
    }
    if (qtyInput && avail > 0) {
        qtyInput.max = String(avail);
        if ((parseInt(qtyInput.value || '1', 10) || 1) > avail) {
            qtyInput.value = String(avail);
        }
    }
    updateSaleTotalPreview();
}

function updateSaleTotalPreview() {
    const qtyEl = document.getElementById('saleBirdsQty');
    const priceEl = document.getElementById('saleUnitPrice');
    const previewEl = document.getElementById('saleTotalPreview');
    if (!qtyEl || !priceEl || !previewEl) return;
    const qty = Math.max(0, parseInt(qtyEl.value, 10) || 0);
    const price = Math.max(0, parseFloat(priceEl.value) || 0);
    previewEl.textContent = formatSalesNaira(qty * price, 2);
}

function openCollectPaymentModal(saleId) {
    const sale = window.salesRecordsMap[String(saleId)] || window.salesRecordsMap[saleId];
    if (!sale) return;
    const bal = sale.balance_due !== undefined ? Number(sale.balance_due) : Math.max(0, Number(sale.total_amount || 0) - Number(sale.amount_paid || 0));
    document.getElementById('collectSaleId').value = sale.id;
    document.getElementById('collectCustomerName').innerText = sale.buyer_name || 'Customer';
    document.getElementById('collectBalanceDue').innerText = formatSalesNaira(bal, 2);
    document.getElementById('collectPaymentAmount').value = bal.toFixed(2);
    openModal('collectPaymentModal');
}

function openSaleReceiptModal(saleId) {
    const sale = window.salesRecordsMap[String(saleId)] || window.salesRecordsMap[saleId];
    if (!sale) return;
    const batchCode = sale.batch_code || '';
    const flockType = sale.flock_type || '';
    const fmt = (n) => formatSalesNaira(n, 2);
    const paid = sale.amount_paid !== undefined ? sale.amount_paid : (sale.payment_status === 'Paid' ? sale.total_amount : 0);
    const bal = sale.balance_due !== undefined ? sale.balance_due : Math.max(0, Number(sale.total_amount) - Number(paid));
    document.getElementById('printModalTitle').innerText = 'Customer Sales Receipt #' + sale.id;
    document.getElementById('printDocumentBody').innerHTML = `
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
            <div class="flex justify-between"><span class="text-slate-500">Customer Name:</span><strong>${sale.buyer_name || 'Commercial Buyer'}</strong></div>
            <div class="flex justify-between"><span class="text-slate-500">Phone / Contact:</span><span class="font-mono">${sale.buyer_contact || '—'}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Sale Date:</span><span class="font-mono">${sale.sale_date}</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Batch:</span><span class="font-mono font-bold">${batchCode} (${flockType})</span></div>
        </div>
        <table class="w-full text-left border-collapse font-mono">
            <thead><tr class="border-b border-slate-300 text-slate-500"><th class="py-2">Description</th><th class="py-2 text-right">Qty</th><th class="py-2 text-right">Unit Price</th><th class="py-2 text-right">Total</th></tr></thead>
            <tbody>
                <tr class="border-b border-slate-200"><td class="py-2.5 font-sans">Live Poultry Birds (${batchCode})</td><td class="py-2.5 text-right">${Number(sale.birds_sold).toLocaleString()}</td><td class="py-2.5 text-right">${fmt(sale.unit_price)}</td><td class="py-2.5 text-right font-bold">${fmt(sale.total_amount)}</td></tr>
            </tbody>
        </table>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-1.5 font-mono">
            <div class="flex justify-between"><span>Total Invoice:</span><strong>${fmt(sale.total_amount)}</strong></div>
            <div class="flex justify-between text-emerald-700"><span>Amount Paid:</span><strong>${fmt(paid)}</strong></div>
            <div class="flex justify-between ${bal > 0 ? 'text-rose-600' : 'text-slate-600'}"><span>Balance Due:</span><strong>${fmt(bal)}</strong></div>
            <div class="flex justify-between pt-1 border-t border-emerald-200"><span>Status:</span><strong>${sale.payment_status}</strong></div>
        </div>
    `;
    openModal('printReportModal');
}

function openBatchPnLModal(batchId) {
    const pb = (typeof batchId === 'object' && batchId !== null)
        ? batchId
        : (window.salesBatchesMap[String(batchId)] || window.salesBatchesMap[batchId]);
    if (!pb) return;
    const m = pb.metrics || {};
    const fmt = (n) => formatSalesNaira(n, 2);
    const net = Number(m.realized_revenue || 0) - Number(m.total_accumulated_cost || 0);
    document.getElementById('printModalTitle').innerText = 'Batch P&L Report • ' + pb.batch_code;
    document.getElementById('printDocumentBody').innerHTML = `
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
            <div class="flex justify-between"><span class="text-slate-500">Batch &amp; Breed:</span><strong>${pb.batch_code} — ${pb.flock_type}</strong></div>
            <div class="flex justify-between"><span class="text-slate-500">Status &amp; Age:</span><span class="font-mono">${pb.status} • Day ${pb.age_days} (Week ${pb.age_weeks})</span></div>
            <div class="flex justify-between"><span class="text-slate-500">Flock Pool:</span><span class="font-mono">Init ${m.initial_birds} | Dead ${m.total_mortality} (${m.mortality_rate_pct}%) | Sold ${m.total_birds_sold} | Live ${m.current_in_house_birds}</span></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200 space-y-1.5 font-mono">
            <div class="font-sans font-bold text-slate-700 mb-1">Expense Breakdown</div>
            <div class="flex justify-between"><span>DOC / Incubation Base:</span><span>${fmt(m.incubation_cost)}</span></div>
            <div class="flex justify-between"><span>General Farm Rent &amp; Overhead:</span><span>${fmt(m.house_rent)}</span></div>
            <div class="flex justify-between"><span>Feed Consumed (${m.total_feed_kg} kg):</span><span>${fmt(m.total_feed_cost)}</span></div>
            <div class="flex justify-between"><span>Medications &amp; Vaccines:</span><span>${fmt(m.total_med_cost)}</span></div>
            <div class="flex justify-between"><span>Labor, Sanitation &amp; Water/Other:</span><span>${fmt(Number(m.total_labor_cost || 0) + Number(m.total_sanitation_cost || 0) + Number(m.total_water_other_cost || 0))}</span></div>
            <div class="flex justify-between pt-2 border-t border-slate-200 font-bold text-slate-900"><span>Total Accumulated Cost:</span><span>${fmt(m.total_accumulated_cost)}</span></div>
        </div>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 space-y-1.5 font-mono">
            <div class="flex justify-between"><span>Breakeven Cost / Bird:</span><strong>${fmt(m.cost_per_bird)}</strong></div>
            <div class="flex justify-between"><span>Realized Sales Revenue:</span><strong>${fmt(m.realized_revenue)}</strong></div>
            <div class="flex justify-between"><span>Projected Total Revenue:</span><strong>${fmt(m.projected_total_revenue)}</strong></div>
            <div class="flex justify-between pt-2 border-t border-emerald-200 ${net >= 0 ? 'text-emerald-700' : 'text-rose-600'}"><span>Realized Net Profit / Loss:</span><strong>${fmt(net)}</strong></div>
        </div>
    `;
    openModal('printReportModal');
}

function printDocumentArea() {
    const title = document.getElementById('printModalTitle').innerText;
    const body = document.getElementById('printDocumentBody').innerHTML;
    const win = window.open('', '_blank', 'width=800,height=700');
    win.document.write('<html><head><title>' + title + '</title><script src="https://cdn.tailwindcss.com"><\/script></head><body class="p-8 font-sans text-slate-900"><h1 class="text-xl font-bold mb-1">Blackswan Farms and General Enterprises</h1><h2 class="text-sm text-slate-600 mb-4">' + title + '</h2>' + body + '<script>setTimeout(() => window.print(), 400);<\/script></body></html>');
    win.document.close();
}

syncRecommendedPriceToSale();
</script>
@endsection