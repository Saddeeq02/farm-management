@extends('layouts.app')

@section('title', 'Module 2: Inventory & Unit-Cost Engine')
@section('page_heading', 'Module 2: Inventory & Base-Unit Cost Engine')
@section('page_subheading', 'Converts bulk feed bags (kg) and clinical bottles (L) into exact base units (grams & ml) for precision daily cost attribution.')

@section('content')
<div class="space-y-6">
    <!-- Inventory Command Header Bar -->
    <div class="glass-card rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
                <i class="fa-solid fa-boxes-stacked text-lg"></i>
            </div>
            <div>
                <h2 class="text-base font-extrabold text-slate-900 dark:text-white">Inventory, Base-Unit Costing &amp; General Farm Rent</h2>
                <p class="text-xs text-slate-500 dark:text-slate-300">Manage feed &amp; vaccine stock in grams/ml and set annual farm rent shared daily across all live chickens.</p>
            </div>
        </div>
        <a href="/?page=dashboard" class="px-3.5 py-1.5 rounded-xl bg-slate-200/80 dark:bg-slate-800/80 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-mono font-semibold border border-slate-300 dark:border-slate-700 transition-all flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Dashboard
        </a>
    </div>

    <!-- General Farm Annual Rent Card -->
    <div class="glass-card rounded-2xl p-5 border border-amber-500/30">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">General Farm Annual Rent (Shared Across All Live Chickens)</h3>
                <p class="text-xs text-slate-500 dark:text-slate-300">Enter total rent paid per year. Daily rent (÷ 365) is automatically divided across all available live birds per day in every batch.</p>
            </div>
            <span class="text-[11px] font-mono px-2.5 py-0.5 rounded bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30 font-bold">
                Annual ÷ 365 ÷ Live Birds
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-end">
            <form method="POST" action="/?page=inventory" class="lg:col-span-2 flex flex-col sm:flex-row gap-3 items-stretch sm:items-end">
                @csrf
                <input type="hidden" name="action" value="update_annual_farm_rent">
                <div class="flex-1">
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Total Rent Paid per Year (₦)</label>
                    <input type="number" step="0.01" min="0" name="annual_farm_rent"
                           value="{{ number_format((float)($generalRent['annual_rent'] ?? 0), 2, '.', '') }}"
                           placeholder="e.g. 1200000" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-xs font-mono font-bold focus:border-amber-500 focus:outline-none">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 text-xs font-mono font-bold shadow-md shadow-amber-500/20 transition cursor-pointer active:scale-95">
                    Save Annual Rent
                </button>
            </form>

            <div class="lg:col-span-3 grid grid-cols-2 sm:grid-cols-4 gap-3 font-mono">
                <div class="rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-3">
                    <span class="block text-[10px] uppercase text-slate-500 dark:text-slate-400">Annual Rent</span>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">₦{{ number_format((float)($generalRent['annual_rent'] ?? 0), 2) }}</span>
                </div>
                <div class="rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-3">
                    <span class="block text-[10px] uppercase text-slate-500 dark:text-slate-400">Daily Farm Rent</span>
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-300">₦{{ number_format((float)($generalRent['daily_rent'] ?? 0), 2) }}/day</span>
                </div>
                <div class="rounded-xl bg-white/80 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 p-3">
                    <span class="block text-[10px] uppercase text-slate-500 dark:text-slate-400">Live Chickens</span>
                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ number_format((int)($generalRent['total_live_birds'] ?? 0)) }} birds</span>
                </div>
                <div class="rounded-xl bg-emerald-500/15 border border-emerald-500/30 p-3">
                    <span class="block text-[10px] uppercase text-emerald-700 dark:text-emerald-300">Overhead / Bird / Day</span>
                    <span class="text-xs font-black text-emerald-700 dark:text-emerald-300">₦{{ number_format((float)($generalRent['rent_per_bird_per_day'] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- General Farm Overhead Ledger (Utilities, Fuel, Staff Salaries) -->
    <div class="glass-card rounded-2xl p-5 border border-cyan-500/30">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">General Farm Overhead Ledger (Fuel, Utilities &amp; Staff Salaries)</h3>
                <p class="text-xs text-slate-500 dark:text-slate-300">Shared farm-wide operating expenses (amortized over 30-day cycle + Annual Rent ÷ 365 across all active live birds).</p>
            </div>
            <span class="text-[11px] font-mono px-2.5 py-0.5 rounded bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30 font-bold">
                Total Logged: ₦{{ number_format((float)($generalRent['total_general_expenses'] ?? 0), 2) }} (₦{{ number_format((float)($generalRent['daily_general_expenses'] ?? 0), 2) }}/day)
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
            <form method="POST" action="/?page=inventory" class="lg:col-span-2 space-y-3">
                @csrf
                <input type="hidden" name="action" value="add_general_expense">
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Category</label>
                        <select name="category" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-cyan-500 focus:outline-none">
                            <option value="Generator Fuel">Generator Fuel</option>
                            <option value="Electricity">Electricity</option>
                            <option value="Water / Borehole">Water / Borehole</option>
                            <option value="Staff Salaries">Staff Salaries</option>
                            <option value="Security / Other">Security / Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Expense Date</label>
                        <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono focus:border-cyan-500 focus:outline-none">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2.5">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Description</label>
                        <input type="text" name="description" placeholder="e.g. 100L Diesel for Farm Generator" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs focus:border-cyan-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Amount (₦)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" placeholder="45000" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-1.5 text-xs font-mono font-bold focus:border-cyan-500 focus:outline-none">
                    </div>
                </div>
                <button type="submit" class="w-full py-2 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-mono font-bold shadow-sm transition cursor-pointer active:scale-95">
                    + Log General Farm Expense
                </button>
            </form>

            <div class="lg:col-span-3 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-950/40 max-h-48 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 bg-slate-100/70 dark:bg-slate-900/60">
                            <th class="py-2 px-3">Date</th>
                            <th class="py-2 px-3">Category</th>
                            <th class="py-2 px-3">Description</th>
                            <th class="py-2 px-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 font-mono">
                        @forelse (($generalExpenses ?? []) as $ge)
                            <tr>
                                <td class="py-2 px-3 text-slate-600 dark:text-slate-300">{{ $ge['expense_date'] ?? '' }}</td>
                                <td class="py-2 px-3 font-sans font-semibold text-cyan-700 dark:text-cyan-300">{{ $ge['category'] ?? '' }}</td>
                                <td class="py-2 px-3 font-sans text-slate-800 dark:text-slate-100">{{ $ge['description'] ?? '' }}</td>
                                <td class="py-2 px-3 text-right font-bold text-slate-900 dark:text-white">₦{{ number_format((float)($ge['amount'] ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-5 text-center font-sans text-slate-500 dark:text-slate-400">No general farm overhead expenses logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Grid: Bulk Intake Form + Live Unit-Cost Formula Preview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Bulk Purchase Intake Form -->
        <div class="lg:col-span-2 glass-card rounded-2xl p-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">Log Bulk Feed or Medication Purchase</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-300">Enter bulk weight (kg or grams) or volume (L or ml) and total purchase cost to compute exact unit price.</p>
                </div>
                <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 font-semibold">
                    1 kg = 1,000 g • 1 L = 1,000 ml
                </span>
            </div>

            <form method="POST" action="/?page=inventory" class="space-y-4">
                @csrf
                <input type="hidden" name="action" value="add_inventory_item">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Item / Brand Name</label>
                        <input type="text" name="item_name" placeholder="e.g. Ultima Broiler Starter (10 Bags x 50kg)" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Inventory Category</label>
                        <select name="category" id="invCategory" onchange="recalculateUnitCostPreview()"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="starter">Feed — Starter</option>
                            <option value="grower">Feed — Grower</option>
                            <option value="layer">Feed — Finisher / Layer</option>
                            <option value="medicine">Medication / Vaccine</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Quantity Purchased</label>
                        <input type="number" step="any" name="quantity_value" id="invQtyValue" value="50" min="0.0001" required
                               oninput="recalculateUnitCostPreview()"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Input Unit</label>
                        <select name="input_unit_mode" id="invUnitMode" onchange="recalculateUnitCostPreview()"
                                class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm focus:border-emerald-500 focus:outline-none">
                            <option value="kg">Kilograms (kg → g)</option>
                            <option value="g">Grams (g direct)</option>
                            <option value="l">Liters (L → ml)</option>
                            <option value="ml">Milliliters (ml direct)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Total Purchase Price (₦)</label>
                        <input type="number" step="0.01" name="total_cost" id="invTotalCost" value="20000" min="1" required
                               oninput="recalculateUnitCostPreview()"
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Purchase Date</label>
                        <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3.5 py-2 text-sm font-mono focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-end">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs sm:text-sm font-mono font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer active:scale-95">
                        + Add to Base-Unit Inventory Ledger
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Unit-Cost Formula Calculator Card -->
        <div class="rounded-2xl bg-slate-900/95 text-white border border-slate-700/80 p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-400">Live Base-Unit Conversion</span>
                <h3 class="text-base font-semibold mt-1">Unit-Cost Engine Preview</h3>
                <p class="text-xs text-slate-400 mt-1 font-mono">
                    Cost per g/ml = Total Price ÷ Total Weight (g or ml)
                </p>

                <div class="mt-5 p-4 rounded-xl bg-slate-800/90 border border-slate-700 space-y-3 font-mono text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Converted Base Units:</span>
                        <span id="previewBaseUnits" class="text-white font-semibold">50,000 g</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">Total Purchase Cost:</span>
                        <span id="previewTotalPrice" class="text-white font-semibold">₦20,000.00</span>
                    </div>
                    <div class="pt-2 border-t border-slate-700 flex items-center justify-between">
                        <span class="text-emerald-400 font-sans font-semibold">Computed Unit Cost:</span>
                        <span id="previewUnitCost" class="text-base font-bold text-emerald-400">₦0.4000 / g</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-800 text-[11px] text-slate-400 leading-relaxed">
                Every gram of feed or ml of vaccine logged in Module 3 automatically multiplies by this unit rate and deducts from inventory.
            </div>
        </div>
    </div>

    <!-- Inventory Stock Ledger Table -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between bg-slate-100/70 dark:bg-slate-900/60">
            <div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Active Feed &amp; Medication Stock Ledger</h3>
                <p class="text-xs text-slate-500 dark:text-slate-300">Real-time balances in grams (g) and milliliters (ml) with exact unit cost attribution.</p>
            </div>
        </div>

        <div class="overflow-x-auto bg-slate-100/40 dark:bg-slate-950/40">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 dark:border-slate-700/80 bg-slate-100/70 dark:bg-slate-900/60 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="py-3.5 px-5">Item Name</th>
                        <th class="py-3.5 px-4">Category</th>
                        <th class="py-3.5 px-4 text-right">Initial Base Qty</th>
                        <th class="py-3.5 px-4 text-right">Remaining Base Qty</th>
                        <th class="py-3.5 px-4 text-right">Purchase Cost</th>
                        <th class="py-3.5 px-4 text-right">Exact Unit Cost</th>
                        <th class="py-3.5 px-5 text-right">Remaining Asset Value</th>
                        <th class="py-3.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70 text-sm">
                    @foreach ($inventoryItems as $item)
                        <tr class="hover:bg-white/40 dark:hover:bg-slate-800/30 transition">
                            <td class="py-4 px-5">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $item['item_name'] }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Purchased {{ $item['purchase_date'] ?? '2026-10-15' }} • Consumed: {{ \App\Core\FarmService::fmtQty((float)$item['total_consumed']) }} {{ $item['unit_label'] }}</div>
                            </td>
                            <td class="py-4 px-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase {{ $item['category'] === 'medicine' ? 'bg-purple-500/15 text-purple-700 dark:text-purple-300 border border-purple-500/30' : ($item['category'] === 'starter' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30' : ($item['category'] === 'grower' ? 'bg-blue-500/15 text-blue-700 dark:text-blue-300 border border-blue-500/30' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/30')) }}">
                                    {{ $item['category'] }}
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ \App\Core\FarmService::fmtQty((float)($item['initial_quantity_g_or_ml'] ?? $item['total_quantity_g_or_ml'])) }} {{ $item['unit_label'] }}
                                <div class="text-[11px] text-slate-500 dark:text-slate-400">({{ \App\Core\FarmService::fmtQty((float)$item['initial_bulk']) }} {{ $item['bulk_unit_label'] }})</div>
                            </td>
                            <td class="py-4 px-4 text-right font-mono">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ \App\Core\FarmService::fmtQty((float)$item['total_quantity_g_or_ml']) }} {{ $item['unit_label'] }}</div>
                                <div class="w-28 ml-auto mt-1 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                    <div class="h-full {{ $item['low_stock'] ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $item['stock_pct'] }}%"></div>
                                </div>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ \App\Core\FarmService::fmtQty((float)$item['remaining_bulk']) }} {{ $item['bulk_unit_label'] }} ({{ $item['stock_pct'] }}%)</div>
                            </td>
                            <td class="py-4 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ₦{{ number_format((float)$item['total_cost'], 2) }}
                            </td>
                            <td class="py-4 px-4 text-right font-mono">
                                <span class="inline-block px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 font-semibold">
                                    ₦{{ number_format((float)$item['unit_cost_per_g_or_ml'], 4) }} / {{ $item['unit_label'] }}
                                </span>
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">₦{{ number_format($item['cost_per_kg_or_l'], 2) }} / {{ $item['bulk_unit_label'] }}</div>
                            </td>
                            <td class="py-4 px-5 text-right font-mono font-semibold text-slate-900 dark:text-white">
                                ₦{{ number_format($item['remaining_value'], 2) }}
                            </td>
                            <td class="py-4 px-4 text-right whitespace-nowrap">
                                <button type="button" onclick="openEditInventoryModal({{ (int)$item['id'] }})"
                                        class="px-2.5 py-1 rounded-lg bg-amber-500/15 hover:bg-amber-500/25 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-xs font-sans font-semibold transition cursor-pointer">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: EDIT INVENTORY ITEM -->
<div id="editInventoryItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md hidden opacity-0 transition-opacity duration-300">
    <div id="editInventoryItemModalBox" class="glass-card max-w-lg w-full max-h-[90vh] overflow-y-auto rounded-3xl p-6 sm:p-7 border border-amber-500/40 relative transform scale-95 transition-transform duration-300">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Inventory Product</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Adjust stock quantities, purchase price, or unit; recalculates exact unit cost.</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('editInventoryItemModal')" class="text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="/?page=inventory" class="space-y-3.5 text-xs">
            @csrf
            <input type="hidden" name="action" value="update_inventory_item">
            <input type="hidden" name="inventory_item_id" id="editInvItemId">

            <div>
                <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Item / Brand Name</label>
                <input type="text" name="item_name" id="editInvName" required
                       class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-amber-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Category</label>
                    <select name="category" id="editInvCategory"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs focus:border-amber-500 focus:outline-none">
                        <option value="starter">Feed — Starter</option>
                        <option value="grower">Feed — Grower</option>
                        <option value="layer">Feed — Finisher / Layer</option>
                        <option value="medicine">Medication / Vaccine</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Base Unit Type</label>
                    <select name="unit_type" id="editInvUnitType"
                            class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                        <option value="g">g (Grams)</option>
                        <option value="ml">ml (Milliliters)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Initial Base Qty (g / ml)</label>
                    <input type="number" step="any" min="0.0001" name="initial_quantity_g_or_ml" id="editInvInitialQty" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Remaining Base Qty (g / ml)</label>
                    <input type="number" step="any" min="0" name="total_quantity_g_or_ml" id="editInvRemQty" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Total Purchase Price (₦)</label>
                    <input type="number" step="any" min="0" name="total_cost" id="editInvTotalCost" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-1">Purchase Date</label>
                    <input type="date" name="purchase_date" id="editInvPurchaseDate" required
                           class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white/90 dark:bg-slate-800/90 text-slate-900 dark:text-white px-3 py-2 text-xs font-mono focus:border-amber-500 focus:outline-none">
                </div>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeModal('editInventoryItemModal')" class="px-4 py-2 rounded-xl bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold cursor-pointer">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 font-mono font-bold shadow-md cursor-pointer">
                    Save Product Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
window.invItemsEditMap = <?php
$invEditMap = [];
foreach ($inventoryItems ?? [] as $_it) {
    $itId = (int) ($_it['id'] ?? 0);
    $invEditMap[$itId] = [
        'id' => $itId,
        'item_name' => $_it['item_name'] ?? '',
        'category' => $_it['category'] ?? 'starter',
        'unit_type' => (($_it['unit_type'] ?? 'g') === 'ml') ? 'ml' : 'g',
        'initial_quantity_g_or_ml' => (float) ($_it['initial_quantity_g_or_ml'] ?? ($_it['total_quantity_g_or_ml'] ?? 0)),
        'total_quantity_g_or_ml' => (float) ($_it['total_quantity_g_or_ml'] ?? 0),
        'total_cost' => (float) ($_it['total_cost'] ?? 0),
        'purchase_date' => $_it['purchase_date'] ?? date('Y-m-d'),
    ];
}
echo json_encode($invEditMap, JSON_UNESCAPED_UNICODE);
?>;

function openEditInventoryModal(itemId) {
    const it = window.invItemsEditMap[String(itemId)] || window.invItemsEditMap[itemId];
    if (!it) return;
    document.getElementById('editInvItemId').value = it.id;
    document.getElementById('editInvName').value = it.item_name || '';
    document.getElementById('editInvCategory').value = it.category || 'starter';
    document.getElementById('editInvUnitType').value = it.unit_type || 'g';
    document.getElementById('editInvInitialQty').value = it.initial_quantity_g_or_ml ?? 0;
    document.getElementById('editInvRemQty').value = it.total_quantity_g_or_ml ?? 0;
    document.getElementById('editInvTotalCost').value = it.total_cost ?? 0;
    document.getElementById('editInvPurchaseDate').value = it.purchase_date || '';
    openModal('editInventoryItemModal');
}
</script>
@endsection