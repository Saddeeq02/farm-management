@extends('layouts.app')

@section('title', 'General Financial Statement — Blackswan Farms and General Enterprises')

@section('content')
@php $fin = $wholeFarmFinancials; @endphp

<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                Whole-Farm Financial Statement
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Consolidated Fixed Assets, Rent, Flow / Current Assets, and All-Batch P&amp;L for Blackswan Farms and General Enterprises.
            </p>
        </div>
        <button type="button" onclick="printWholeFarmStatement()"
                class="px-4 py-2 rounded-xl bg-slate-900 dark:bg-emerald-500 hover:bg-slate-800 dark:hover:bg-emerald-400 text-white dark:text-slate-950 text-xs font-bold flex items-center gap-2 shadow-sm transition cursor-pointer">
            <i class="fa-solid fa-print"></i>
            <span>Print Whole-Farm Statement</span>
        </button>
    </div>

    <!-- Top 4 Executive Financial KPIs -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card rounded-2xl p-4 border border-amber-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-amber-600 dark:text-amber-300 font-semibold">Fixed Assets + Annual Rent</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                ₦{{ number_format($fin['total_fixed_and_rent'], 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Assets: ₦{{ number_format($fin['fixed_capital_total'], 0) }} • Rent: ₦{{ number_format($fin['annual_farm_rent'], 0) }}/yr
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-cyan-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-cyan-600 dark:text-cyan-300 font-semibold">Flow / Current Assets</span>
            <div class="text-2xl font-extrabold font-mono text-slate-900 dark:text-white mt-1">
                ₦{{ number_format($fin['total_flow_assets'], 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Live Flocks + Incubator + Store + Credit
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border border-emerald-500/30">
            <span class="text-[10px] font-mono uppercase tracking-wider text-emerald-600 dark:text-emerald-300 font-semibold">Sales Collected / Billed</span>
            <div class="text-2xl font-extrabold font-mono text-emerald-600 dark:text-emerald-400 mt-1">
                ₦{{ number_format($fin['cash_collected'], 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Billed: ₦{{ number_format($fin['total_sales_billed'], 0) }} • Due: ₦{{ number_format($fin['accounts_receivable'], 0) }}
            </p>
        </div>

        <div class="glass-card rounded-2xl p-4 border {{ $fin['net_farm_profit'] >= 0 ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-rose-500/40 bg-rose-500/10' }}">
            <span class="text-[10px] font-mono uppercase tracking-wider {{ $fin['net_farm_profit'] >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }} font-semibold">
                Whole-Farm Net Position
            </span>
            <div class="text-2xl font-extrabold font-mono {{ $fin['net_farm_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }} mt-1">
                {{ $fin['net_farm_profit'] >= 0 ? '+' : '' }}₦{{ number_format($fin['net_farm_profit'], 2) }}
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 font-mono">
                Billed Revenue minus Operating Cost
            </p>
        </div>
    </div>

    <!-- Flow / Current Assets vs Operating Outflows -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Flow / Continuous Assets -->
        <div class="glass-card rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-water text-cyan-500"></i> Flow &amp; Current Assets (Working Capital)
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Assets continuously rotating through your hatchery, pens, store, and customers.</p>
                </div>
            </div>
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Live Flocks in Pens (At Cost)</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['live_flock_asset_value'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Eggs in Incubator / Hatcher (Carried Cost)</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['incubator_egg_asset_value'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Feed, Vaccines &amp; Meds Remaining in Store</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['store_inventory_value'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Customer Receivables (Credit Owed)</span>
                    <span class="font-mono font-bold text-amber-600 dark:text-amber-400">₦{{ number_format($fin['accounts_receivable'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
                    <span class="font-bold text-emerald-700 dark:text-emerald-300">Cash Collected from Sales</span>
                    <span class="font-mono font-extrabold text-emerald-700 dark:text-emerald-300">₦{{ number_format($fin['cash_collected'], 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Operating Spend & Fixed Burden -->
        <div class="glass-card rounded-2xl p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-emerald-500"></i> Whole-Farm Operating Spend &amp; Monthly Burden
                    </h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Where money is spent across all batches + fixed monthly farm commitments.</p>
                </div>
            </div>
            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Incubation Eggs, Gas &amp; Direct DOC Purchases</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['spend_incubation_and_doc'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Feed Consumed Across All Flocks</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['spend_feed_consumed'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Medications &amp; Vaccines</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['spend_meds_vaccines'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-white/70 dark:bg-slate-900/60 border border-slate-200/70 dark:border-slate-800">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Labor, Sanitation, Water &amp; Rent Share</span>
                    <span class="font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['spend_labor_sanit_other'] + $fin['spend_overhead_rent'], 2) }}</span>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-amber-500/10 border border-amber-500/30">
                    <span class="font-bold text-amber-700 dark:text-amber-300">Monthly Fixed Burden (Rent + Overhead + Depreciation)</span>
                    <span class="font-mono font-extrabold text-amber-700 dark:text-amber-300">₦{{ number_format($fin['monthly_fixed_burden'], 2) }}/mo</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Fixed Assets & Commitments Register -->
    <div class="glass-card rounded-2xl p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-building-shield text-amber-500"></i> Fixed Assets &amp; Commitments (Rent, Pens, Incubators &amp; Equipment)
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Long-term farm infrastructure alongside your Annual Farm Rent.</p>
            </div>
        </div>

        <!-- Add Fixed Asset Form -->
        <form method="POST" action="/?page=finance" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2.5 bg-slate-100/70 dark:bg-slate-900/60 p-3.5 rounded-2xl border border-slate-200/80 dark:border-slate-800">
            <input type="hidden" name="action" value="add_fixed_asset">
            <input type="text" name="asset_name" required placeholder="Asset Name (e.g. 5kVA Generator)"
                   class="lg:col-span-2 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
            <select name="category" class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                <option value="Poultry House / Pen">Poultry House / Pen</option>
                <option value="Incubator / Hatchery">Incubator / Hatchery</option>
                <option value="Generator / Power">Generator / Power</option>
                <option value="Borehole / Water">Borehole / Water</option>
                <option value="Cages / Equipment">Cages / Equipment</option>
                <option value="Land / Lease">Land / Lease</option>
            </select>
            <input type="number" step="0.01" min="1" name="purchase_cost" required placeholder="Cost (₦)"
                   class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
            <input type="number" min="1" name="useful_life_months" value="60" required placeholder="Life (Months)"
                   class="rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-emerald-500 focus:outline-none">
            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold cursor-pointer transition">
                + Add Fixed Asset
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[10px] font-mono">
                        <th class="py-2 pr-3">Asset / Commitment</th>
                        <th class="py-2 pr-3">Category</th>
                        <th class="py-2 pr-3 text-right">Capital Value (₦)</th>
                        <th class="py-2 pr-3 text-right">Life / Cycle</th>
                        <th class="py-2 pr-3 text-right">Monthly Burden</th>
                        <th class="py-2 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
                    <tr class="bg-amber-500/5">
                        <td class="py-2.5 pr-3 font-semibold text-slate-900 dark:text-white">Annual Farm Rent Commitment</td>
                        <td class="py-2.5 pr-3 font-mono text-[11px] text-amber-600 dark:text-amber-300">Fixed Rent</td>
                        <td class="py-2.5 pr-3 text-right font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fin['annual_farm_rent'], 2) }}</td>
                        <td class="py-2.5 pr-3 text-right font-mono text-slate-500">12 mos</td>
                        <td class="py-2.5 pr-3 text-right font-mono text-amber-600 dark:text-amber-300">₦{{ number_format($fin['monthly_rent'], 2) }}/mo</td>
                        <td class="py-2.5 text-right"><a href="/?page=inventory" class="text-[11px] font-mono text-cyan-600 dark:text-cyan-400 underline">Edit in Store</a></td>
                    </tr>
                    @forelse ($fin['fixed_assets'] as $fa)
                        <tr>
                            <td class="py-2.5 pr-3 font-semibold text-slate-900 dark:text-white">{{ $fa['asset_name'] }}</td>
                            <td class="py-2.5 pr-3 text-slate-600 dark:text-slate-300">{{ $fa['category'] }}</td>
                            <td class="py-2.5 pr-3 text-right font-mono font-bold text-slate-900 dark:text-white">₦{{ number_format($fa['purchase_cost'], 2) }}</td>
                            <td class="py-2.5 pr-3 text-right font-mono text-slate-500">{{ $fa['useful_life_months'] }} mos</td>
                            <td class="py-2.5 pr-3 text-right font-mono text-slate-600 dark:text-slate-300">₦{{ number_format($fa['monthly_depreciation'], 2) }}/mo</td>
                            <td class="py-2.5 text-right">
                                <form method="POST" action="/?page=finance" class="inline">
                                    <input type="hidden" name="action" value="delete_fixed_asset">
                                    <input type="hidden" name="asset_id" value="{{ $fa['id'] }}">
                                    <button type="submit" class="text-slate-400 hover:text-rose-500 cursor-pointer" title="Remove Asset">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-slate-400 italic">No fixed equipment or buildings added yet. Use the form above to record pens, incubators, generators, or boreholes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- All-Batch Master Financial Table -->
    <div id="wholeFarmPrintArea" class="glass-card rounded-2xl p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-200/80 dark:border-slate-800 pb-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fa-solid fa-table-list text-emerald-500"></i> All-Batch Financial Ledger (Whole System)
                </h3>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">Every Production Flock &amp; Incubation Batch side-by-side.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 uppercase text-[10px] font-mono">
                        <th class="py-2 pr-3">Batch</th>
                        <th class="py-2 pr-3">Stage</th>
                        <th class="py-2 pr-3">Count</th>
                        <th class="py-2 pr-3 text-right">Base Cost</th>
                        <th class="py-2 pr-3 text-right">Feed &amp; Meds</th>
                        <th class="py-2 pr-3 text-right">Overhead</th>
                        <th class="py-2 pr-3 text-right">Total Cost</th>
                        <th class="py-2 pr-3 text-right">Billed Rev</th>
                        <th class="py-2 pr-3 text-right">Collected</th>
                        <th class="py-2 text-right">Net P&amp;L</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 font-mono">
                    @foreach ($fin['batches'] as $row)
                        <tr>
                            <td class="py-2.5 pr-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $row['batch_code'] }}</div>
                                <div class="font-sans text-[10px] text-slate-500 truncate max-w-[160px]">{{ $row['label'] }}</div>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $row['stage'] === 'Production' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300' }}">
                                    {{ $row['stage'] }} • {{ $row['status'] }}
                                </span>
                            </td>
                            <td class="py-2.5 pr-3 font-sans text-[11px] text-slate-600 dark:text-slate-300">{{ $row['units_label'] }}</td>
                            <td class="py-2.5 pr-3 text-right text-slate-700 dark:text-slate-300">₦{{ number_format($row['base_cost'], 0) }}</td>
                            <td class="py-2.5 pr-3 text-right text-slate-700 dark:text-slate-300">₦{{ number_format($row['feed_med_cost'], 0) }}</td>
                            <td class="py-2.5 pr-3 text-right text-slate-700 dark:text-slate-300">₦{{ number_format($row['overhead_other'], 0) }}</td>
                            <td class="py-2.5 pr-3 text-right font-bold text-slate-900 dark:text-white">₦{{ number_format($row['total_cost'], 0) }}</td>
                            <td class="py-2.5 pr-3 text-right text-cyan-600 dark:text-cyan-400">₦{{ number_format($row['revenue_billed'], 0) }}</td>
                            <td class="py-2.5 pr-3 text-right text-emerald-600 dark:text-emerald-400">₦{{ number_format($row['cash_collected'], 0) }}</td>
                            <td class="py-2.5 text-right font-bold {{ $row['net_profit'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $row['net_profit'] >= 0 ? '+' : '' }}₦{{ number_format($row['net_profit'], 0) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function printWholeFarmStatement() {
    const win = window.open('', '_blank', 'width=960,height=800');
    if (!win) { window.print(); return; }
    const tableHtml = document.getElementById('wholeFarmPrintArea')?.innerHTML || '';
    win.document.write(`<!DOCTYPE html><html><head><title>Whole-Farm Financial Statement — Blackswan Farms and General Enterprises</title>
        <script src="https://cdn.tailwindcss.com"><\/script></head>
        <body class="p-8 font-sans text-slate-900">
            <div class="flex justify-between items-start border-b pb-4 mb-5">
                <div>
                    <h1 class="text-xl font-extrabold">Blackswan Farms and General Enterprises</h1>
                    <p class="text-xs text-slate-500">Whole-Farm General Financial Statement (Generated {{ date('Y-m-d H:i') }})</p>
                </div>
                <div class="text-right font-mono text-xs">
                    <div>Fixed + Rent: <strong>₦{{ number_format($fin['total_fixed_and_rent'], 2) }}</strong></div>
                    <div>Flow Assets: <strong>₦{{ number_format($fin['total_flow_assets'], 2) }}</strong></div>
                    <div>Net Position: <strong>₦{{ number_format($fin['net_farm_profit'], 2) }}</strong></div>
                </div>
            </div>
            ${tableHtml}
            <script>setTimeout(() => window.print(), 400);<\/script>
        </body></html>`);
    win.document.close();
}
</script>
@endsection

