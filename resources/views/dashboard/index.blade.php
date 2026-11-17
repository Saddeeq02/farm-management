@extends('layouts.app')

@section('title', 'Blackswan Farms and General Enterprises — Executive Hub')

@section('content')
@php
    $activePb = $productionBatches[0] ?? null;
    $activeIb = $incubationBatches[0] ?? null;
    $liveBirds = $summary['total_live_birds'] ?? 0;
    $totalExpenses = $activePb ? ($activePb['metrics']['total_accumulated_cost'] ?? 0) : 0;
    $costPerBird = $activePb ? ($activePb['metrics']['cost_per_bird'] ?? 0) : 0;
    $mortalityPct = $activePb ? ($activePb['metrics']['mortality_rate_pct'] ?? 0) : 0;
    $mortalityCount = $activePb ? ($activePb['metrics']['total_mortality'] ?? 0) : 0;
    $recPrice = $activePb ? ($activePb['metrics']['recommended_selling_price'] ?? 0) : 0;
    $marginPct = $activePb ? ($activePb['target_profit_margin_pct'] ?? $activePb['metrics']['target_margin_pct'] ?? 35) : 35;
    $projectedProfit = $activePb ? ($activePb['metrics']['projected_net_profit'] ?? 0) : 0;
@endphp

<!-- 1. BATCH CONTEXT BAR -->
<div class="glass-card rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
  <div class="flex items-center gap-3.5">
    <div class="w-11 h-11 rounded-xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
      <i class="fa-solid fa-feather-pointed text-xl"></i>
    </div>
    <div>
      <div class="flex items-center gap-2 flex-wrap">
        <span class="text-base font-extrabold text-slate-900 dark:text-white tracking-wide" id="activeBatchTitle">
          @if ($activePb)
            Batch #{{ $activePb['batch_code'] }} • {{ $activePb['flock_type'] }}
          @else
            No Active Production Flock
          @endif
        </span>
        <span class="text-[10px] px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 font-mono font-bold border border-emerald-500/30">
          {{ $activePb['status'] ?? 'No Active Flock' }}
        </span>
        @if ($activePb && $activePb['feed_alert']['needs_transition'])
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300 font-mono font-bold border border-amber-500/40 animate-pulse">
            <i class="fa-solid fa-bell mr-1"></i> Switch to {{ $activePb['feed_alert']['recommended_stage'] }}
          </span>
        @endif
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">
        House: <strong class="text-slate-700 dark:text-slate-200">{{ $activePb['house_pen_id'] ?? '—' }}</strong> • 
        Age: <strong class="text-slate-700 dark:text-slate-200">Day {{ $activePb['age_days'] ?? 0 }} (Wk {{ $activePb['age_weeks'] ?? 0 }})</strong> • 
        Scheduled Harvest: <strong class="text-slate-700 dark:text-slate-200">{{ $activePb ? 'Day 42' : '—' }}</strong>
      </p>
    </div>
  </div>

  <div class="flex items-center gap-4 sm:gap-6 text-xs font-mono flex-wrap">
    <div class="flex flex-col">
      <span class="text-slate-500 dark:text-slate-400 text-[10px]">FEED STAGE</span>
      <span class="font-bold text-amber-600 dark:text-amber-400 text-sm flex items-center gap-1">
        <i class="fa-solid fa-wheat-awn text-xs"></i> {{ $activePb['current_feed_stage'] ?? 'None' }}
      </span>
    </div>
        <i class="fa-solid fa-wheat-awn text-xs"></i> {{ $activePb['current_feed_stage'] ?? 'Grower' }}
      </span>
    </div>
    <div class="h-8 w-px bg-slate-300 dark:bg-slate-800 hidden sm:block"></div>
    <div class="flex flex-col">
      <span class="text-slate-500 dark:text-slate-400 text-[10px]">REMAINING FEED STORE</span>
      <span class="font-bold text-emerald-600 dark:text-emerald-400 text-sm">
        {{ number_format($summary['total_feed_kg_remaining'], 0) }} kg in store
      </span>
    </div>
    <div class="h-8 w-px bg-slate-300 dark:bg-slate-800 hidden sm:block"></div>
    <div class="flex flex-col">
      <span class="text-slate-500 dark:text-slate-400 text-[10px]">TOTAL EXPENSE ACCRUED</span>
      <span class="font-extrabold text-slate-900 dark:text-white text-sm" id="statAccumulatedSpend">
        ₦ {{ number_format($totalExpenses, 2) }}
      </span>
    </div>
  </div>
</div>

<!-- 2. 4-COLUMN KPI METRIC CARDS -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
  
  <!-- CARD 1: Live Headcount & Mortality -->
  <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
    <div class="flex items-center justify-between text-xs mb-2">
      <span class="font-bold tracking-wider text-slate-600 dark:text-slate-400 uppercase">Live Headcount</span>
      <span class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"><i class="fa-solid fa-crow"></i></span>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight" id="kpiHeadcount">
        {{ number_format($liveBirds) }}
      </span>
      <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
        / {{ number_format($summary['total_live_birds'] ?? 0) }} birds
      </span>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs">
      <span class="text-slate-500 dark:text-slate-400">Mortality Loss:</span>
      <div class="flex items-center gap-1 font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
        <span id="kpiMortalityRate">{{ $mortalityPct }}%</span>
        <span class="text-[10px] text-slate-400">({{ $mortalityCount }} culls)</span>
      </div>
    </div>
    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-1.5 rounded-full" style="width: {{ max(5, 100 - $mortalityPct) }}%"></div>
    </div>
  </div>

  <!-- CARD 2: True Unit Cost per Bird -->
  <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
    <div class="flex items-center justify-between text-xs mb-2">
      <span class="font-bold tracking-wider text-slate-600 dark:text-slate-400 uppercase">True Cost / Bird</span>
      <span class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20"><i class="fa-solid fa-calculator"></i></span>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-3xl font-extrabold text-slate-900 dark:text-white font-mono tracking-tight" id="kpiCostPerBird">
        ₦ {{ number_format($costPerBird, 2) }}
      </span>
      <span class="text-xs font-mono text-cyan-700 dark:text-cyan-300 font-bold bg-cyan-100 dark:bg-cyan-500/10 px-1.5 py-0.5 rounded border border-cyan-300 dark:border-cyan-500/30">Breakeven</span>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs">
      <span class="text-slate-500 dark:text-slate-400">Mortality Burden Share:</span>
      <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">+₦ {{ number_format($activePb['metrics']['mortality_cost_burden_per_bird'] ?? 0, 2) }} / bird</span>
    </div>
    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
      <div class="bg-gradient-to-r from-cyan-500 to-blue-500 h-1.5 rounded-full" style="width: 72%"></div>
    </div>
  </div>

  <!-- CARD 3: Hatchery Benchmark -->
  <div class="glass-card rounded-2xl p-5 relative overflow-hidden group">
    <div class="flex items-center justify-between text-xs mb-2">
      <span class="font-bold tracking-wider text-slate-600 dark:text-slate-400 uppercase">Hatchability (Bench)</span>
      <span class="p-1.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20"><i class="fa-solid fa-temperature-arrow-up"></i></span>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-3xl font-extrabold text-amber-600 dark:text-amber-300 font-mono tracking-tight" id="kpiHatchRate">
        {{ $summary['avg_hatchability_pct'] }}%
      </span>
      <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
        Setter: {{ number_format($summary['active_eggs_in_incubator']) }} eggs
      </span>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs">
      <span class="text-slate-500 dark:text-slate-400">Hatched Chicks Produced:</span>
      <span class="font-mono text-amber-600 dark:text-amber-300 font-semibold">
        {{ number_format($summary['total_chicks_hatched']) }} Grade-A
      </span>
    </div>
    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
      <div class="bg-gradient-to-r from-amber-500 to-yellow-400 h-1.5 rounded-full" style="width: {{ $summary['avg_hatchability_pct'] }}%"></div>
    </div>
  </div>

  <!-- CARD 4: Target Margin & Dynamic Price -->
  <div class="glass-card rounded-2xl p-5 relative overflow-hidden group border-emerald-500/40">
    <div class="flex items-center justify-between text-xs mb-2">
      <span class="font-bold tracking-wider text-emerald-600 dark:text-emerald-400 uppercase">Rec. Market Price</span>
      <span class="p-1.5 rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30"><i class="fa-solid fa-tag"></i></span>
    </div>
    <div class="flex items-baseline gap-2">
      <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-300 font-mono tracking-tight" id="kpiRecPrice">
        ₦ {{ number_format($recPrice, 2) }}
      </span>
      <span class="text-xs font-mono px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 font-bold" id="kpiMarginBadge">
        +{{ $marginPct }}% Margin
      </span>
    </div>
    <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800/80 flex items-center justify-between text-xs">
      <span class="text-slate-500 dark:text-slate-400">Est. Batch Net Profit:</span>
      <span class="font-mono text-emerald-600 dark:text-emerald-300 font-bold" id="kpiProjectedProfit">
        ₦ {{ number_format($projectedProfit, 0) }}
      </span>
    </div>
    <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 mt-2 overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-500 to-lime-400 h-1.5 rounded-full" style="width: 100%"></div>
    </div>
  </div>

</div>

<!-- 3. NAVIGATION TABS (HIGH-CONTRAST FROSTED GLASS CAPSULES) -->
<div class="flex items-center gap-2.5 overflow-x-auto pb-1.5 no-scrollbar text-xs font-semibold">
  <button onclick="switchTab('analyticsTab')" id="tabBtn-analyticsTab" class="tab-btn px-4 py-2.5 rounded-xl tab-btn-active flex items-center gap-2 whitespace-nowrap cursor-pointer">
    <i class="fa-solid fa-chart-pie text-emerald-400 text-sm"></i>
    <span>Cost-to-Sale & Financial Engine</span>
  </button>
  <button onclick="switchTab('incubationTab')" id="tabBtn-incubationTab" class="tab-btn px-4 py-2.5 rounded-xl tab-btn-inactive flex items-center gap-2 whitespace-nowrap cursor-pointer">
    <i class="fa-solid fa-egg text-amber-400 text-sm"></i>
    <span>Incubation & Candling Telemetry</span>
  </button>
  <button onclick="switchTab('productionTab')" id="tabBtn-productionTab" class="tab-btn px-4 py-2.5 rounded-xl tab-btn-inactive flex items-center gap-2 whitespace-nowrap cursor-pointer">
    <i class="fa-solid fa-wheat-awn text-emerald-400 text-sm"></i>
    <span>Production, Feed & Health Log</span>
  </button>
  <button onclick="switchTab('inventoryTab')" id="tabBtn-inventoryTab" class="tab-btn px-4 py-2.5 rounded-xl tab-btn-inactive flex items-center gap-2 whitespace-nowrap cursor-pointer">
    <i class="fa-solid fa-boxes-stacked text-cyan-400 text-sm"></i>
    <span>Inventory & Unit Cost Engine</span>
  </button>
  <button onclick="switchTab('simulatorTab')" id="tabBtn-simulatorTab" class="tab-btn px-4 py-2.5 rounded-xl tab-btn-inactive flex items-center gap-2 whitespace-nowrap cursor-pointer">
    <i class="fa-solid fa-sliders text-purple-400 text-sm"></i>
    <span>Real-time Margin & Price Simulator</span>
  </button>
</div>

<!-- ========================================== -->
<!-- TAB 1: FINANCIAL & COST-TO-SALE ANALYTICS -->
<!-- ========================================== -->
<div id="analyticsTab" class="tab-content space-y-6">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Visual Chart: Expenses Breakdown -->
    <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-emerald-500"></i>
            Batch Expense Allocation & Overhead Absorption
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Total accumulated operational outlay distributed across active live inventory</p>
        </div>
        <div class="flex items-center gap-2 text-xs font-mono bg-white/80 dark:bg-slate-900/80 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
          <span class="text-slate-500 dark:text-slate-400">Live Flock:</span>
          <span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ number_format($liveBirds) }} birds surviving</span>
        </div>
      </div>

      <!-- Chart Canvas Container -->
      <div class="relative h-64 sm:h-72 w-full flex items-center justify-center">
        <canvas id="costBreakdownChart"></canvas>
      </div>

      <!-- Granular Breakdown Footer Badges -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-4 mt-2 border-t border-slate-200 dark:border-slate-800/80 text-xs font-mono">
        <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400 block text-[10px]">FEED INTAKE</span>
          <span class="text-emerald-600 dark:text-emerald-400 font-bold">₦ {{ number_format($activePb['metrics']['costs']['feed'] ?? 0) }}</span>
          <span class="text-slate-400 dark:text-slate-500 block text-[9px]">Calculated g/ml</span>
        </div>
        <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400 block text-[10px]">INCUB. BASE</span>
          <span class="text-cyan-600 dark:text-cyan-400 font-bold">₦ {{ number_format($activePb['metrics']['costs']['initial_chicks'] ?? 0) }}</span>
          <span class="text-slate-400 dark:text-slate-500 block text-[9px]">Eggs + Gas carried</span>
        </div>
        <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400 block text-[10px]">BARN RENT</span>
          <span class="text-amber-600 dark:text-amber-400 font-bold">₦ {{ number_format($activePb['fixed_house_rent'] ?? 0) }}</span>
          <span class="text-slate-400 dark:text-slate-500 block text-[9px]">Lump-sum cycle</span>
        </div>
        <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400 block text-[10px]">VET & SANIT.</span>
          <span class="text-purple-600 dark:text-purple-400 font-bold">₦ {{ number_format(($activePb['metrics']['costs']['medication'] ?? 0) + ($activePb['metrics']['costs']['sanitation'] ?? 0)) }}</span>
          <span class="text-slate-400 dark:text-slate-500 block text-[9px]">Meds & bio-security</span>
        </div>
        <div class="p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 col-span-2 sm:col-span-1">
          <span class="text-slate-500 dark:text-slate-400 block text-[10px]">DIRECT LABOR</span>
          <span class="text-rose-600 dark:text-rose-400 font-bold">₦ {{ number_format($activePb['metrics']['costs']['labor'] ?? 0) }}</span>
          <span class="text-slate-400 dark:text-slate-500 block text-[9px]">Farm shifts</span>
        </div>
      </div>
    </div>

    <!-- True Cost Per Bird Computation Ledger -->
    <div class="glass-card rounded-3xl p-6 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-scale-balanced text-cyan-500"></i> Unit Cost Ledger
          </h3>
          <span class="text-[11px] font-mono bg-cyan-100 dark:bg-cyan-950/70 text-cyan-800 dark:text-cyan-300 border border-cyan-300 dark:border-cyan-800/40 px-2 py-0.5 rounded font-bold">Per-Bird Share</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">How every gram of feed, milliliter of medicine, and kobo of barn rent divides among survivors:</p>

        <div class="space-y-3 font-mono text-xs">
          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Feed (Starter + Grower + Finisher)</span>
            </div>
            <span class="text-slate-900 dark:text-white font-bold">
              ₦ {{ number_format(!empty($activePb['metrics']['current_in_house_birds']) ? (($activePb['metrics']['costs']['feed'] ?? 0) / $activePb['metrics']['current_in_house_birds']) : 0, 2) }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Incubator Carryover (Egg + Gas)</span>
            </div>
            <span class="text-slate-900 dark:text-white font-bold">
              ₦ {{ number_format(!empty($activePb['metrics']['current_in_house_birds']) ? (($activePb['metrics']['costs']['initial_chicks'] ?? 0) / $activePb['metrics']['current_in_house_birds']) : 0, 2) }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
              <span class="text-slate-700 dark:text-slate-300">House Rent Lump-Sum</span>
            </div>
            <span class="text-slate-900 dark:text-white font-bold">
              ₦ {{ number_format(!empty($activePb['metrics']['current_in_house_birds']) ? (($activePb['fixed_house_rent'] ?? 0) / $activePb['metrics']['current_in_house_birds']) : 0, 2) }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Medications, Vaccine & Sanitation</span>
            </div>
            <span class="text-slate-900 dark:text-white font-bold">
              ₦ {{ number_format(!empty($activePb['metrics']['current_in_house_birds']) ? ((($activePb['metrics']['costs']['medication'] ?? 0) + ($activePb['metrics']['costs']['sanitation'] ?? 0)) / $activePb['metrics']['current_in_house_birds']) : 0, 2) }}
            </span>
          </div>

          <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/90 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
              <span class="text-slate-700 dark:text-slate-300">Direct Farm Labor Allocation</span>
            </div>
            <span class="text-slate-900 dark:text-white font-bold">
              ₦ {{ number_format(!empty($activePb['metrics']['current_in_house_birds']) ? (($activePb['metrics']['costs']['labor'] ?? 0) / $activePb['metrics']['current_in_house_birds']) : 0, 2) }}
            </span>
          </div>
        </div>
      </div>

      <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50 dark:bg-slate-900/40 -mx-6 -mb-6 p-6 rounded-b-3xl">
        <div class="flex items-center justify-between">
          <div>
            <span class="text-xs text-slate-500 dark:text-slate-400 block font-medium">EXACT BREAKEVEN BASE</span>
            <span class="text-lg font-bold font-mono text-emerald-600 dark:text-emerald-400">
              ₦ {{ number_format($costPerBird, 2) }} / bird
            </span>
          </div>
          <button onclick="switchTab('simulatorTab')" class="text-xs font-semibold px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 transition-all flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-wand-magic-sparkles"></i> Simulate Margin
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- TAB 2: INCUBATION & CANDLING MODULE -->
<!-- ========================================== -->
<div id="incubationTab" class="tab-content hidden space-y-6">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Incubation Parameters Card -->
    <div class="glass-card rounded-3xl p-6">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-temperature-full text-amber-500"></i> Setter Batch #{{ $activeIb['batch_code'] ?? 'None' }}
        </h3>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
          {{ $activeIb['status'] ?? 'No Batch' }}
        </span>
      </div>
      <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
        Parent: {{ $activeIb['parent_flock_info'] ?? '—' }} (Age: {{ $activeIb['flock_age_weeks'] ?? 0 }} Wks)
      </p>

      <div class="space-y-4 font-mono text-xs">
        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Eggs Set at Intake:</span>
          <span class="font-bold text-slate-900 dark:text-white">{{ number_format($activeIb['eggs_set'] ?? 0) }} pcs</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Total Egg Initial Cost:</span>
          <span class="font-bold text-amber-600 dark:text-amber-300">₦ {{ number_format($activeIb['egg_cost'] ?? 0, 2) }}</span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Gas Refills Logged (kg):</span>
          <span class="font-bold text-cyan-600 dark:text-cyan-400">
            {{ number_format($activeIb['metrics']['total_gas_kg'] ?? 0, 1) }} kg (₦ {{ number_format($activeIb['metrics']['total_gas_cost'] ?? 0, 2) }})
          </span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Incubator Part Repairs:</span>
          <span class="font-bold text-slate-800 dark:text-slate-200">
            ₦ {{ number_format($activeIb['metrics']['total_repairs_cost'] ?? 0, 2) }}
          </span>
        </div>
        <div class="flex justify-between items-center py-2 border-b border-slate-200 dark:border-slate-800">
          <span class="text-slate-500 dark:text-slate-400">Incubation Day:</span>
          <span class="font-bold text-emerald-600 dark:text-emerald-400">
            Day {{ $activeIb['incubation_day'] ?? 0 }} {{ $activeIb ? '(Thermal Setter)' : '' }}
          </span>
        </div>
            Day {{ $activeIb['incubation_day'] ?? 12 }} (Thermal Setter)
          </span>
        </div>
      </div>

      <!-- Candling Diagnostics Chart -->
      <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800">
        <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 block mb-2">Candling Loss Telemetry</span>
        <div class="h-44 w-full">
          <canvas id="candlingChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Real-Time Candling Entry Table -->
    <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
      <div>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="fa-solid fa-binoculars text-cyan-500"></i> Daily Candling & Breakout Logs
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Immediate logging of daily infertiles, clears, dead-in-shell, and cracked eggs</p>
          </div>
          <div class="flex items-center gap-2">
            <a href="/?page=incubation" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-xs font-mono shadow-md shadow-emerald-500/20 transition-all flex items-center gap-1.5">
              <i class="fa-solid fa-egg"></i> Open Incubator Input Page <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
            <button onclick="openModal('transferModal')" class="px-3 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-xs font-mono transition-all flex items-center gap-1.5 cursor-pointer">
              <i class="fa-solid fa-arrow-right-arrow-left"></i> Handover to Barn
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-100 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-2.5 px-3">Batch Code</th>
                <th class="py-2.5 px-3">Eggs Set</th>
                <th class="py-2.5 px-3">Infertiles (Clears)</th>
                <th class="py-2.5 px-3">Dead in Shell</th>
                <th class="py-2.5 px-3">Cracked</th>
                <th class="py-2.5 px-3">Viable Remaining</th>
                <th class="py-2.5 px-3">Status</th>
              </tr>
            </thead>
            <tbody id="candlingTableBody" class="divide-y divide-slate-200 dark:divide-slate-800/60">
              @forelse ($incubationBatches as $ib)
                <tr>
                  <td class="py-2.5 px-3 text-slate-900 dark:text-white font-semibold">{{ $ib['batch_code'] }}</td>
                  <td class="py-2.5 px-3 font-bold">{{ number_format($ib['eggs_set']) }}</td>
                  <td class="py-2.5 px-3 text-amber-600 dark:text-amber-400 font-bold">{{ number_format($ib['metrics']['infertiles'] ?? $ib['metrics']['total_infertile_eggs'] ?? 0) }}</td>
                  <td class="py-2.5 px-3 text-rose-600 dark:text-rose-400">{{ number_format(($ib['metrics']['early_deaths'] ?? 0) + ($ib['metrics']['candling_dis'] ?? 0)) }}</td>
                  <td class="py-2.5 px-3">{{ number_format($ib['metrics']['cracked'] ?? $ib['metrics']['total_cracked_eggs'] ?? 0) }}</td>
                  <td class="py-2.5 px-3 text-emerald-600 dark:text-emerald-400 font-bold">
                    {{ number_format($ib['metrics']['viable_eggs_remaining']) }}
                  </td>
                  <td class="py-2.5 px-3">
                    <span class="px-2 py-0.5 rounded-full text-[10px] {{ $ib['status'] === 'Active' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-500/20 text-slate-400' }}">
                      {{ $ib['status'] }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="py-6 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                    No incubation batches recorded yet. <a href="/?page=incubation" class="text-emerald-500 font-bold hover:underline">Add First Batch →</a>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

        <div class="mt-6 p-4 rounded-2xl bg-emerald-500/5 border border-emerald-500/20 flex flex-wrap items-center justify-between gap-3">
          <div class="text-xs text-slate-600 dark:text-slate-300">
            <i class="fa-solid fa-circle-info text-emerald-500 mr-1.5"></i>
            All egg intakes, setter/hatcher candling logs, gas expenses, and breakouts are managed on the dedicated Incubator page.
          </div>
          <a href="/?page=incubation" class="px-3.5 py-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-xs font-mono font-bold transition-all">
            Manage Incubator Inputs →
          </a>
        </div>
      </div>
    </div>
  </div>

<!-- ========================================== -->
<!-- TAB 3: PRODUCTION & FEED TELEMETRY -->
<!-- ========================================== -->
<div id="productionTab" class="tab-content hidden space-y-6">
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Left: Feed Transition & Age Alert -->
    <div class="glass-card rounded-3xl p-6 space-y-5">
      <div>
        <span class="text-[10px] font-mono px-2 py-0.5 rounded bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/30 uppercase tracking-wider font-bold">
          Automated Feeding Alert
        </span>
        <h3 class="text-base font-bold text-slate-900 dark:text-white mt-2 flex items-center gap-2">
          <i class="fa-solid fa-bell text-amber-500 animate-bounce"></i> 
          {{ ($activePb['feed_alert']['needs_transition'] ?? false) ? 'Transition to ' . $activePb['feed_alert']['recommended_stage'] : 'Feeding Stage on Target' }}
        </h3>
        <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">
          @if ($activePb)
            Batch #{{ $activePb['batch_code'] }} is at <strong>Day {{ $activePb['age_days'] }}</strong>. 
            Current ration: <strong>{{ $activePb['current_feed_stage'] }}</strong>. 
            Target harvest: Day 42.
          @else
            No active broiler flocks currently in pen.
          @endif
        </p>
      </div>

      <!-- Feed Unit Cost Engine Breakdown -->
      <div class="p-4 rounded-2xl bg-slate-100/90 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 space-y-3 font-mono text-xs">
        <span class="text-slate-700 dark:text-slate-300 font-bold block border-b border-slate-200 dark:border-slate-800 pb-2">Unit Cost Engine (Per Gram / ml)</span>
        
        @forelse ($inventoryItems as $item)
          <div class="flex justify-between items-center text-slate-600 dark:text-slate-400">
            <span>{{ $item['item_name'] ?? $item['name'] ?? '' }}:</span>
            <span class="text-emerald-700 dark:text-emerald-300 font-bold">
              ₦ {{ number_format((float) ($item['unit_cost_per_g_or_ml'] ?? $item['cost_per_base_unit'] ?? 0), 3) }} / {{ $item['unit_label'] ?? $item['base_unit'] ?? 'g' }}
            </span>
          </div>
        @empty
          <p class="text-slate-400 dark:text-slate-500 text-xs font-sans">No inventory items recorded yet.</p>
        @endforelse
      </div>

      <!-- Facility Rent Allocation Spec -->
      <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-500/20 text-xs">
        <div class="flex items-center gap-2 text-amber-700 dark:text-amber-300 font-bold mb-1">
          <i class="fa-solid fa-house-chimney"></i> Lump-Sum Rent Allocation
        </div>
        <p class="text-slate-600 dark:text-slate-400 text-[11px] font-mono leading-relaxed">
          @if ($activePb)
            {{ $activePb['house_pen_id'] ?? 'Barn 01' }} lump-sum house rent of <strong>₦ {{ number_format($activePb['fixed_house_rent'] ?? 0) }}</strong> is allocated directly to this batch. Zero daily manual rent logging needed.
          @else
            No barn house rent currently allocated.
          @endif
        </p>
      </div>
    </div>

    <!-- Right: Daily Telemetry Log Table -->
    <div class="glass-card rounded-3xl p-6 lg:col-span-2 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="fa-solid fa-book-open text-emerald-500"></i> Daily Production & Cost Telemetry
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tracked feed in grams, medication dosage in ml, and live bird count</p>
          </div>
          <button onclick="openModal('dailyLogModal')" class="px-3.5 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-xs font-mono transition-all flex items-center gap-1.5 cursor-pointer">
            <i class="fa-solid fa-plus"></i> Log Today's Entry
          </button>
        </div>

        <!-- Production Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
            <thead class="bg-slate-100 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-2.5 px-3">Date / Day</th>
                <th class="py-2.5 px-3">Mortality</th>
                <th class="py-2.5 px-3">Feed Intake (g)</th>
                <th class="py-2.5 px-3">Feed Cost</th>
                <th class="py-2.5 px-3">Water / Meds</th>
                <th class="py-2.5 px-3">Labor / Sanit.</th>
              </tr>
            </thead>
            <tbody id="productionTableBody" class="divide-y divide-slate-200 dark:divide-slate-800/60">
              @if ($activePb && !empty($activePb['daily_logs']))
                @foreach (array_slice($activePb['daily_logs'], 0, 8) as $log)
                  <tr>
                    <td class="py-2.5 px-3 text-slate-900 dark:text-white font-semibold">
                      Day {{ $log['day_number'] }} • {{ $log['log_date'] }}
                    </td>
                    <td class="py-2.5 px-3 text-rose-600 dark:text-rose-400 font-bold">
                      -{{ $log['mortality_count'] }} birds
                    </td>
                    <td class="py-2.5 px-3">
                      {{ number_format($log['feed_consumed_grams']) }} g
                    </td>
                    <td class="py-2.5 px-3 text-emerald-700 dark:text-emerald-300 font-semibold">
                      ₦ {{ number_format($log['feed_cost'], 2) }}
                    </td>
                    <td class="py-2.5 px-3 text-slate-500">
                      {{ number_format($log['water_intake_liters']) }}L (₦{{ number_format($log['med_cost'], 2) }})
                    </td>
                    <td class="py-2.5 px-3">
                      ₦ {{ number_format($log['labor_cost'] + $log['sanitation_cost'], 2) }}
                    </td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="6" class="py-6 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                    No daily telemetry logs recorded yet. Click <strong>"Log Today's Entry"</strong> above to record your first daily log.
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-slate-200 dark:border-slate-800 text-[11px] font-mono text-slate-500 dark:text-slate-400 flex items-center justify-between">
        <span>*Surviving live count dynamically updates every morning at 06:00.</span>
        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">FCR (Feed Conversion Ratio): 1.54 (Optimal)</span>
      </div>
    </div>

  </div>
</div>

<!-- ========================================== -->
<!-- TAB 4: INVENTORY & UNIT COST ENGINE -->
<!-- ========================================== -->
<div id="inventoryTab" class="tab-content hidden space-y-6">
  <div class="glass-card rounded-3xl p-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <div>
        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
          <i class="fa-solid fa-boxes-stacked text-emerald-500"></i>
          Inventory & Base-Unit Unit Cost Engine (₦ / gram & ₦ / ml)
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Bulk bags (50kg) and bottles (1L) automatically converted into base operational units
        </p>
      </div>
      <button onclick="document.getElementById('inventoryIntakeForm').classList.toggle('hidden')" class="px-3.5 py-1.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 text-xs font-mono transition-all flex items-center gap-1.5 cursor-pointer">
        <i class="fa-solid fa-plus"></i> + Add Bulk Purchase
      </button>
    </div>

    <!-- Collapsible Bulk Purchase Intake -->
    <div id="inventoryIntakeForm" class="hidden mb-6 p-4 rounded-2xl bg-white/[0.04] border border-white/8 space-y-3">
      <span class="text-xs font-bold text-slate-900 dark:text-white block">Log New Bulk Feed or Medication Intake</span>
      <form method="POST" action="/?page=inventory" class="grid grid-cols-1 sm:grid-cols-5 gap-2.5 text-xs font-mono">
        @csrf
        <input type="hidden" name="action" value="add_inventory_item">
        <input type="text" name="name" placeholder="Item Name (e.g. Grower Pellets)" required class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-slate-900 dark:text-white outline-none">
        <select name="category" class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-slate-900 dark:text-white outline-none">
          <option value="Feed">Feed (kg)</option>
          <option value="Medication">Medication (ml/g)</option>
          <option value="Vaccine">Vaccine</option>
          <option value="Sanitation">Sanitation</option>
        </select>
        <input type="number" step="0.01" name="package_size_qty" placeholder="Pkg Size (e.g. 50)" required class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-slate-900 dark:text-white outline-none">
        <input type="number" step="0.01" name="purchase_price" placeholder="Cost (₦)" required class="bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl px-2.5 py-1.5 text-slate-900 dark:text-white outline-none">
        <button type="submit" class="py-1.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-bold hover:from-emerald-400 hover:to-teal-400 transition cursor-pointer">
          Compute & Store
        </button>
      </form>
    </div>

    <!-- Inventory Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
        <thead class="bg-slate-100 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
          <tr>
            <th class="py-2.5 px-3">Item Name</th>
            <th class="py-2.5 px-3">Category</th>
            <th class="py-2.5 px-3">Package Size</th>
            <th class="py-2.5 px-3">Purchase Price</th>
            <th class="py-2.5 px-3">Remaining Balance</th>
            <th class="py-2.5 px-3">Exact Cost / Base Unit</th>
            <th class="py-2.5 px-3">Stock Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
          @forelse ($inventoryItems as $item)
            <tr>
              <td class="py-3 px-3 text-slate-900 dark:text-white font-semibold">
                {{ $item['item_name'] ?? $item['name'] ?? '' }}
              </td>
              <td class="py-3 px-3">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ strtolower($item['category'] ?? '') === 'feed' ? 'bg-amber-500/20 text-amber-300' : 'bg-cyan-500/20 text-cyan-300' }}">
                  {{ ucfirst($item['category'] ?? '') }}
                </span>
              </td>
              <td class="py-3 px-3">{{ number_format((float) ($item['remaining_bulk'] ?? $item['package_size_qty'] ?? 0), 1) }} {{ $item['bulk_unit_label'] ?? $item['package_unit'] ?? '' }}</td>
              <td class="py-3 px-3 font-bold">₦ {{ number_format((float) ($item['total_cost'] ?? $item['purchase_price'] ?? 0), 2) }}</td>
              <td class="py-3 px-3 text-emerald-600 dark:text-emerald-400 font-bold">
                {{ number_format((float) ($item['total_quantity_g_or_ml'] ?? $item['remaining_base_units'] ?? 0)) }} {{ $item['unit_label'] ?? $item['base_unit'] ?? 'g' }}
              </td>
              <td class="py-3 px-3 text-cyan-600 dark:text-cyan-300 font-bold">
                ₦ {{ number_format((float) ($item['unit_cost_per_g_or_ml'] ?? $item['cost_per_base_unit'] ?? 0), 4) }} / {{ $item['unit_label'] ?? $item['base_unit'] ?? 'g' }}
              </td>
              <td class="py-3 px-3">
                @if (!empty($item['low_stock']) || !empty($item['is_low_stock']))
                  <span class="text-rose-400 font-bold">Low Stock Alert</span>
                @else
                  <span class="text-emerald-400">Adequate</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-6 text-center text-slate-400 dark:text-slate-500 font-sans text-xs">
                No inventory items in stock. Click <strong>"+ Add Bulk Purchase"</strong> above to record your first purchase.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================================== -->
<!-- TAB 5: REAL-TIME MARGIN & PRICE SIMULATOR -->
<!-- ========================================== -->
<div id="simulatorTab" class="tab-content hidden space-y-6">
  <div class="glass-card rounded-3xl p-6 lg:p-8">
    
    <div class="max-w-3xl mb-8">
      <span class="text-xs font-mono px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-500/30 uppercase tracking-wider font-bold">Dynamic Financial Engine</span>
      <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white mt-2">Commercial Sales & Margin Interactive Simulator</h2>
      <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">Adjust profit margin percentage, simulate additional feed intake or mortality surges, and watch recommended bird pricing recalculate in real time.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
      
      <!-- Controls Column -->
      <div class="lg:col-span-7 space-y-6">
        
        <!-- Profit Margin Slider -->
        <div class="p-5 rounded-2xl bg-slate-100/90 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800 space-y-3">
          <div class="flex justify-between items-center">
            <label for="marginSlider" class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <i class="fa-solid fa-percent text-emerald-500"></i> Target Batch Profit Margin:
            </label>
            <span id="sliderMarginValue" class="text-lg font-mono font-extrabold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-500/10 px-3 py-1 rounded-lg border border-emerald-300 dark:border-emerald-500/30">
              {{ $marginPct }}%
            </span>
          </div>
          <input type="range" id="marginSlider" min="10" max="60" value="{{ $marginPct }}" step="1" oninput="updatePricingSimulation()" class="w-full">
          <div class="flex justify-between text-[11px] font-mono text-slate-500">
            <span>10% (Low Margin Bulk)</span>
            <span>35% (Target)</span>
            <span>60% (Festive Peak)</span>
          </div>
        </div>

        <!-- Interactive Simulator Inputs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="p-4 rounded-2xl bg-slate-100/70 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Simulate Added Feed per Bird (Grams):</label>
            <div class="relative">
              <input type="number" id="simFeedGrams" value="0" step="50" oninput="updatePricingSimulation()" class="w-full bg-white dark:bg-slate-800/90 text-sm font-mono text-slate-900 dark:text-white px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 outline-none focus:border-emerald-500">
              <span class="absolute right-3 top-2 text-xs font-mono text-slate-400">grams</span>
            </div>
            <span class="text-[10px] text-slate-500 block">@ ₦0.42 / gram (Finisher Feed)</span>
          </div>

          <div class="p-4 rounded-2xl bg-slate-100/70 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 space-y-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">Simulate Additional Mortalities:</label>
            <div class="relative">
              <input type="number" id="simMortality" value="0" step="5" oninput="updatePricingSimulation()" class="w-full bg-white dark:bg-slate-800/90 text-sm font-mono text-slate-900 dark:text-white px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-700 outline-none focus:border-emerald-500">
              <span class="absolute right-3 top-2 text-xs font-mono text-slate-400">birds</span>
            </div>
            <span class="text-[10px] text-slate-500 block">Reduces denominator, lifts cost/bird</span>
          </div>
        </div>

        <div class="flex items-center gap-3 p-4 rounded-2xl bg-slate-100/60 dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800 text-xs text-slate-600 dark:text-slate-400">
          <i class="fa-solid fa-shield-halved text-emerald-500 text-lg"></i>
          <span>Every calculation incorporates the entire batch lifecycle: ₦{{ number_format($activePb['fixed_house_rent'] ?? 250000) }} housing rent, incubation base, and all previous feed and vet expenses.</span>
        </div>
      </div>

      <!-- Dynamic Result Card -->
      <div class="lg:col-span-5">
        <div class="p-6 rounded-3xl bg-white/90 dark:bg-gradient-to-b dark:from-slate-900/90 dark:via-slate-900/95 dark:to-emerald-950/40 border border-emerald-400/40 shadow-2xl relative overflow-hidden">
          <div class="absolute -top-12 -right-12 w-36 h-36 bg-emerald-500/10 rounded-full blur-2xl"></div>
          
          <span class="text-xs font-mono uppercase tracking-wider text-slate-500 dark:text-slate-400 block mb-1 font-bold">REAL-TIME COMMERCIAL PRICING</span>
          <div class="text-3xl sm:text-4xl font-extrabold text-emerald-600 dark:text-emerald-300 font-mono tracking-tight my-2" id="simRecPrice">
            ₦ {{ number_format($recPrice, 2) }}
          </div>
          <span class="text-xs text-slate-500 dark:text-slate-400 block font-mono">Recommended wholesale asking price per live bird</span>

          <div class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-800 space-y-3 font-mono text-xs">
            <div class="flex justify-between text-slate-700 dark:text-slate-300">
              <span>Surviving Bird Count:</span>
              <span class="font-bold text-slate-900 dark:text-white" id="simLiveBirds">{{ number_format($liveBirds) }} birds</span>
            </div>
            <div class="flex justify-between text-slate-700 dark:text-slate-300">
              <span>Dynamic Cost per Bird:</span>
              <span class="font-bold text-cyan-600 dark:text-cyan-300" id="simUnitCost">₦ {{ number_format($costPerBird, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-700 dark:text-slate-300">
              <span>Target Profit / Bird:</span>
              <span class="font-bold text-emerald-600 dark:text-emerald-400" id="simProfitPerBird">+₦ {{ number_format($recPrice - $costPerBird, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-700 dark:text-slate-300 pt-2 border-t border-slate-200 dark:border-slate-800/80">
              <span class="font-bold text-slate-900 dark:text-white">Projected Gross Sales:</span>
              <span class="font-bold text-slate-900 dark:text-white text-sm" id="simGrossRevenue">₦ {{ number_format($recPrice * $liveBirds, 0) }}</span>
            </div>
            <div class="flex justify-between text-emerald-600 dark:text-emerald-400 pt-1">
              <span class="font-bold">Projected Net Batch Profit:</span>
              <span class="font-extrabold text-base" id="simNetProfit">₦ {{ number_format($projectedProfit, 0) }}</span>
            </div>
          </div>

          <form method="POST" action="/?page=sales" class="mt-6">
            @csrf
            <input type="hidden" name="action" value="update_target_margin">
            <input type="hidden" name="production_batch_id" value="{{ $activePb['id'] ?? 1 }}">
            <input type="hidden" id="formMarginInput" name="target_profit_margin_pct" value="{{ $marginPct }}">
            <button type="submit" onclick="document.getElementById('formMarginInput').value = document.getElementById('marginSlider').value;" class="w-full py-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-xs tracking-wider uppercase transition-all shadow-lg shadow-emerald-500/25 active:scale-95 flex items-center justify-center gap-2 cursor-pointer">
              <i class="fa-solid fa-check-double"></i> Save as Batch Target Price
            </button>
          </form>
        </div>
      </div>

    </div>

    <!-- Commercial Depletion Sales Ledger -->
    <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-800">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="fa-solid fa-receipt text-cyan-500"></i> Commercial Sales & Depletion Ledger
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Batches harvested and sold to commercial distributors</p>
        </div>
        <button onclick="openModal('recordSaleModal')" class="px-3.5 py-1.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 text-cyan-700 dark:text-cyan-300 border border-cyan-500/30 text-xs font-mono transition-all flex items-center gap-1.5 cursor-pointer">
          <i class="fa-solid fa-plus"></i> + Record Sale
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left font-mono text-xs text-slate-700 dark:text-slate-300">
          <thead class="bg-slate-100 dark:bg-slate-900/80 text-slate-500 dark:text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th class="py-2.5 px-3">Sale Date</th>
              <th class="py-2.5 px-3">Flock Code</th>
              <th class="py-2.5 px-3">Birds Sold</th>
              <th class="py-2.5 px-3">Unit Price (₦)</th>
              <th class="py-2.5 px-3">Gross Revenue (₦)</th>
              <th class="py-2.5 px-3">Realized Profit (₦)</th>
              <th class="py-2.5 px-3">Buyer Notes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60">
            @php $salesFound = false; @endphp
            @foreach ($productionBatches as $pb)
              @if (!empty($pb['sales']))
                @foreach ($pb['sales'] as $sale)
                  @php 
                    $salesFound = true; 
                    $birdsSold = $sale['birds_sold'] ?? $sale['quantity_sold'] ?? 0;
                    $unitPrice = $sale['unit_price'] ?? $sale['sale_price_per_bird'] ?? 0;
                    $totalRev = $sale['total_amount'] ?? $sale['total_revenue'] ?? ($birdsSold * $unitPrice);
                    $netProf = $sale['net_profit'] ?? ($totalRev - ($birdsSold * ($pb['metrics']['cost_per_bird'] ?? 712.50)));
                  @endphp
                  <tr>
                    <td class="py-2.5 px-3 text-slate-900 dark:text-white font-semibold">{{ $sale['sale_date'] }}</td>
                    <td class="py-2.5 px-3">{{ $pb['batch_code'] }}</td>
                    <td class="py-2.5 px-3 font-bold">{{ number_format($birdsSold) }} birds</td>
                    <td class="py-2.5 px-3">₦ {{ number_format($unitPrice, 2) }}</td>
                    <td class="py-2.5 px-3 text-emerald-600 dark:text-emerald-400 font-bold">₦ {{ number_format($totalRev, 2) }}</td>
                    <td class="py-2.5 px-3 text-cyan-600 dark:text-cyan-300 font-bold">₦ {{ number_format($netProf, 2) }}</td>
                    <td class="py-2.5 px-3 text-slate-400">{{ $sale['buyer_name'] ?? $sale['customer_name'] ?? 'Wholesale distributor' }}</td>
                  </tr>
                @endforeach
              @endif
            @endforeach
            @if (!$salesFound)
              <tr>
                <td colspan="7" class="py-4 px-3 text-center text-slate-400 italic">
                  No sales recorded yet. Click "+ Record Sale" to log commercial bird harvests.
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

@endsection
