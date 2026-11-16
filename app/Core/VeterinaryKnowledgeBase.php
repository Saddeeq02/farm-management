<?php

declare(strict_types=1);

namespace App\Core;

/**
 * VeterinaryKnowledgeBase
 *
 * Deep clinical veterinary repository for Broiler Chickens and Meat Turkeys,
 * extracted and compiled directly from Dr. Kosiella's textbooks:
 * 1. "From Poult to Profit: The Ultimate Turkey Production Guide"
 * 2. "Poultry Farm Starter Toolkit — June Edition (Rainy Season Special)"
 */
final class VeterinaryKnowledgeBase
{
    /**
     * Complete 8-Week Broiler Chicken Medication & Vaccination Schedule
     */
    public static function getChickenSchedule(): array
    {
        return [
            1 => [
                'day_range' => 'Day 1',
                'target' => 'Marek\'s Disease Vaccine',
                'category' => 'Vaccine',
                'purpose' => 'Prevent Marek\'s viral paralysis. Confirm hatchery vaccination.',
                'method' => 'Subcutaneous injection at hatchery',
                'is_critical' => true,
                'pre_warning' => null,
            ],
            2 => [
                'day_range' => 'Day 1–2',
                'target' => 'Glucose + Vitamin C OR Antistress Solution',
                'category' => 'Supplement',
                'purpose' => 'Immediate energy boost, rehydration, and travel stress relief.',
                'method' => 'Drinking water (vital for weak/tired day-old chicks)',
                'is_critical' => true,
                'pre_warning' => 'Give antistress water for 1–2 hours BEFORE introducing dry starter feed.',
            ],
            3 => [
                'day_range' => 'Day 3–5',
                'target' => 'Broad-Spectrum Antibiotic (Enrofloxacin, Oxytetracycline, or Amoxycol)',
                'category' => 'Medication',
                'purpose' => 'Prevent early bacterial mortality, yolk sac infection (omphalitis), and E. coli.',
                'method' => 'Drinking water (3 days max)',
                'is_critical' => false,
                'pre_warning' => 'Do not prolong beyond 5 days to avoid destroying chick gut flora.',
            ],
            6 => [
                'day_range' => 'Day 6',
                'target' => 'Plain Chlorine-Free Water or Vitamins (Pre-Vaccine Prep)',
                'category' => 'Pre-Vaccine',
                'purpose' => 'Flush out all antibiotic residues before Newcastle vaccination tomorrow.',
                'method' => 'Drinking water (chlorine-free, clean drinkers)',
                'is_critical' => true,
                'pre_warning' => 'CRITICAL RULE: Zero antibiotics allowed 24h before vaccination!',
            ],
            7 => [
                'day_range' => 'Day 7 (Week 1)',
                'target' => 'Lasota Vaccine — Newcastle Disease (NDV)',
                'category' => 'Vaccine',
                'purpose' => 'Primary immunity against Newcastle Disease virus.',
                'method' => 'Eye drop (recommended) or drinking water with skimmed milk stabilizer (20g/10L)',
                'is_critical' => true,
                'pre_warning' => 'Withdraw drinking water for 1–2 hours before vaccination so birds drink eagerly within 1 hour.',
            ],
            8 => [
                'day_range' => 'Day 8',
                'target' => 'Multivitamins & Electrolytes',
                'category' => 'Supplement',
                'purpose' => 'Stress recovery and immune enhancement following Newcastle vaccination.',
                'method' => 'Drinking water for 24 hours',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            9 => [
                'day_range' => 'Day 9–12',
                'target' => 'Anticoccidial Preventive (Amprolium or Toltrazuril)',
                'category' => 'Medication',
                'purpose' => 'Prevent coccidiosis outbreak as maternal antibodies decline.',
                'method' => 'Drinking water (Amprolium for 3–4 days; Toltrazuril for 48 hours)',
                'is_critical' => true,
                'pre_warning' => 'Inspect litter for dampness under drinkers where coccidia oocysts multiply rapidly.',
            ],
            13 => [
                'day_range' => 'Day 13',
                'target' => 'Plain Chlorine-Free Water or Vitamins (Pre-Gumboro Prep)',
                'category' => 'Pre-Vaccine',
                'purpose' => 'Flush drug residues and prepare immune system for Gumboro vaccine.',
                'method' => 'Clean, unchlorinated drinking water',
                'is_critical' => true,
                'pre_warning' => 'Do not administer antibiotics today. Ensure clean drinkers.',
            ],
            14 => [
                'day_range' => 'Day 14 (Week 2)',
                'target' => 'Gumboro Vaccine — Infectious Bursal Disease (IBD)',
                'category' => 'Vaccine',
                'purpose' => 'Protect Bursa of Fabricius from IBD immunosuppression.',
                'method' => 'Eye drop or drinking water with skimmed milk powder (20g per 10L)',
                'is_critical' => true,
                'pre_warning' => 'Withdraw water 1–2 hours prior. Keep vaccine strictly between 2–8°C on ice.',
            ],
            15 => [
                'day_range' => 'Day 15–17',
                'target' => 'Multivitamins or Plain Water',
                'category' => 'Supplement',
                'purpose' => 'Post-Gumboro immune recovery.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            18 => [
                'day_range' => 'Day 18–20',
                'target' => 'Respiratory/Bacterial Antibiotic (Tylosin, Enrofloxacin, or Amoxycol)',
                'category' => 'Medication',
                'purpose' => 'Prevent and treat Chronic Respiratory Disease (CRD / Mycoplasma) and secondary E. coli.',
                'method' => 'Drinking water (3 days max)',
                'is_critical' => false,
                'pre_warning' => 'Check pen ventilation and avoid cold night draughts.',
            ],
            21 => [
                'day_range' => 'Day 21',
                'target' => 'Plain Water or Multivitamin (Pre-Lasota Booster Prep)',
                'category' => 'Pre-Vaccine',
                'purpose' => 'Clear birds of antibiotics in preparation for Newcastle booster.',
                'method' => 'Drinking water',
                'is_critical' => true,
                'pre_warning' => 'Stop all medications. Prepare unchlorinated well or borehole water.',
            ],
            22 => [
                'day_range' => 'Day 22 (Week 3)',
                'target' => 'Lasota Booster — Newcastle Disease',
                'category' => 'Vaccine',
                'purpose' => 'Boost circulating antibodies against Newcastle Disease.',
                'method' => 'Drinking water with skimmed milk stabilizer (20g/10L)',
                'is_critical' => true,
                'pre_warning' => 'Ensure all birds drink within 1.5 to 2 hours in early morning before heat sets in.',
            ],
            23 => [
                'day_range' => 'Day 23–27',
                'target' => 'Plain Water or Multivitamins',
                'category' => 'Supplement',
                'purpose' => 'Support growth curve and recovery.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            28 => [
                'day_range' => 'Day 28 (Week 4)',
                'target' => 'Gumboro Booster Vaccine (IBD Booster)',
                'category' => 'Vaccine',
                'purpose' => 'Consolidate long-term immunity against field strains of IBD.',
                'method' => 'Drinking water with skimmed milk powder',
                'is_critical' => true,
                'pre_warning' => 'Never use chlorinated tap water — chlorine destroys live virus within minutes.',
            ],
            29 => [
                'day_range' => 'Day 29–30',
                'target' => 'Multivitamins & Electrolytes',
                'category' => 'Supplement',
                'purpose' => 'Post-vaccine recovery.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            31 => [
                'day_range' => 'Day 31–35',
                'target' => 'Appetite & Growth Multivitamins + Calcium/Bone Meal in Feed',
                'category' => 'Supplement',
                'purpose' => 'Bone calcification to support heavy rapid weight gain without leg weakness.',
                'method' => 'Water and feed addition',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            37 => [
                'day_range' => 'Day 37 (Week 5)',
                'target' => 'Broad-Spectrum Dewormer (Piperazine, Levamisole, or Albendazole)',
                'category' => 'Medication',
                'purpose' => 'Eradicate roundworms and tapeworms that consume feed nutrients.',
                'method' => 'Single morning water treatment',
                'is_critical' => false,
                'pre_warning' => 'Optional for 6-week harvest flocks, but recommended for flocks kept past 7 weeks.',
            ],
            39 => [
                'day_range' => 'Day 39',
                'target' => 'Lasota 2nd Booster (Optional)',
                'category' => 'Vaccine',
                'purpose' => 'Extra immunity for heavy broilers kept beyond 6–7 weeks.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => 'Skip if planning harvest at 6 weeks.',
            ],
            42 => [
                'day_range' => 'Day 42–44 (Week 6)',
                'target' => 'Final Preventive Anticoccidial (Optional)',
                'category' => 'Medication',
                'purpose' => 'Prevent late coccidiosis flare-up in heavy, dense pens.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => 'Verify drug withdrawal period before administration.',
            ],
            45 => [
                'day_range' => 'Day 45–56 (Week 7–8)',
                'target' => 'WITHDRAWAL PERIOD — Plain Water & Finisher Ration Only',
                'category' => 'Withdrawal',
                'purpose' => 'Ensure meat is free of drug residues before slaughter and commercial sale.',
                'method' => 'Strict plain water, clean feed, zero antibiotics, zero anticoccidials',
                'is_critical' => true,
                'pre_warning' => 'STOP all medications 14 days before slaughter. Protect consumer health.',
            ],
        ];
    }

    /**
     * Complete Meat Turkey Medication & Vaccination Schedule (From Poult to Profit)
     */
    public static function getTurkeySchedule(): array
    {
        return [
            1 => [
                'day_range' => 'Day 1',
                'target' => 'Marek\'s Disease Vaccine',
                'category' => 'Vaccine',
                'purpose' => 'Subcutaneous hatchery vaccination against Marek\'s disease.',
                'method' => 'Subcutaneous (at hatchery)',
                'is_critical' => true,
                'pre_warning' => 'Confirm hatchery administration before accepting poults.',
            ],
            2 => [
                'day_range' => 'Day 1–2',
                'target' => 'Antistress Solution (Electrolytes + Glucose)',
                'category' => 'Supplement',
                'purpose' => 'Counteract severe dehydration and travel fatigue in fragile poults.',
                'method' => 'Drinking water (Day 1 & Day 2)',
                'is_critical' => true,
                'pre_warning' => 'Poults are sight-learners: dip their beaks in water if needed so they find drinkers.',
            ],
            3 => [
                'day_range' => 'Day 3–5',
                'target' => 'Probiotics or Mild Antibiotic (Oxytetracycline)',
                'category' => 'Medication',
                'purpose' => 'Establish healthy gut microbiome and eliminate early bacterial infections.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => 'Maintain brooding heat strictly at 35–37°C under hover.',
            ],
            6 => [
                'day_range' => 'Day 6',
                'target' => 'Multivitamin or Plain Water',
                'category' => 'Pre-Vaccine',
                'purpose' => 'Boost immune system in preparation for Day 7 Newcastle vaccination.',
                'method' => 'Chlorine-free drinking water',
                'is_critical' => true,
                'pre_warning' => 'Zero antibiotics today.',
            ],
            7 => [
                'day_range' => 'Day 7',
                'target' => 'Newcastle Disease Vaccine (Lasota)',
                'category' => 'Vaccine',
                'purpose' => 'Protect poults against virulent Newcastle Disease.',
                'method' => 'Eye drop or drinking water with skimmed milk powder (20g/10L)',
                'is_critical' => true,
                'pre_warning' => 'Withdraw water 1–2 hours beforehand so birds drink rapidly in early morning.',
            ],
            8 => [
                'day_range' => 'Day 8–9',
                'target' => 'Plain Water or Multivitamin',
                'category' => 'Supplement',
                'purpose' => 'Immune and appetite recovery post-vaccine.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            10 => [
                'day_range' => 'Day 10–14',
                'target' => 'Anticoccidial (Amprolium, Sulfonamides, or Embazin)',
                'category' => 'Medication',
                'purpose' => 'Control and prevent coccidiosis infection as poults explore litter.',
                'method' => 'Drinking water (4–5 days)',
                'is_critical' => true,
                'pre_warning' => 'Keep litter bone-dry. Turkeys drink heavily and spill water easily.',
            ],
            16 => [
                'day_range' => 'Day 16',
                'target' => 'Hemorrhagic Enteritis (HE) Vaccine (Optional)',
                'category' => 'Vaccine',
                'purpose' => 'Prevent "Bloody Gut" adenoviral enteritis in commercial turkey flocks.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => 'Recommended in areas with high commercial turkey density.',
            ],
            17 => [
                'day_range' => 'Day 17–20',
                'target' => 'Plain Chlorine-Free Water',
                'category' => 'Supplement',
                'purpose' => 'Rest period for gut and liver.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => null,
            ],
            21 => [
                'day_range' => 'Day 21 (Week 3)',
                'target' => 'Newcastle Booster Vaccine (Lasota)',
                'category' => 'Vaccine',
                'purpose' => 'Establish long-term circulating antibody titers.',
                'method' => 'Drinking water with skimmed milk powder',
                'is_critical' => true,
                'pre_warning' => 'Clean all drinkers thoroughly before vaccination.',
            ],
            35 => [
                'day_range' => 'Week 5',
                'target' => 'Broad-Spectrum Deworming (Albendazole, Levamisole, or Ivermectin)',
                'category' => 'Medication',
                'purpose' => 'Eliminate cecal worms (Heterakis gallinarum) and roundworms.',
                'method' => 'Drinking water (single day treatment)',
                'is_critical' => true,
                'pre_warning' => 'CRITICAL TURKEY RULE: Cecal worms carry Histomonas meleagridis (Blackhead Disease). Deworming is vital!',
            ],
            42 => [
                'day_range' => 'Week 6',
                'target' => 'Fowl Pox Vaccine',
                'category' => 'Vaccine',
                'purpose' => 'Prevent dry and wet Fowl Pox scabs on head, snood, and mouth.',
                'method' => 'Wing-web stab (using double-pronged needle)',
                'is_critical' => true,
                'pre_warning' => 'Extremely important if layers, noilers, or standing water (mosquitoes) are nearby.',
            ],
            49 => [
                'day_range' => 'Week 7',
                'target' => 'Fowl Cholera Vaccine (M9 Strain, Optional)',
                'category' => 'Vaccine',
                'purpose' => 'Prevent Pasteurella multocida systemic bacterial infection.',
                'method' => 'Drinking water',
                'is_critical' => false,
                'pre_warning' => 'Consult local vet based on regional disease pressure.',
            ],
            56 => [
                'day_range' => 'Every 4 Weeks (Monthly)',
                'target' => 'Repeat Newcastle Lasota Booster',
                'category' => 'Vaccine',
                'purpose' => 'Continuous protection throughout long turkey rearing cycle (16–24 weeks).',
                'method' => 'Drinking water',
                'is_critical' => true,
                'pre_warning' => 'Turkeys are raised for months; single boosters do not last till harvest.',
            ],
            70 => [
                'day_range' => 'Every 4–6 Weeks',
                'target' => 'Rotate Dewormers (Alternate Classes: Albendazole -> Levamisole -> Piperazine)',
                'category' => 'Medication',
                'purpose' => 'Prevent drug-resistant helminth parasites.',
                'method' => 'Drinking water',
                'is_critical' => true,
                'pre_warning' => 'Always alternate chemical classes to stop parasite resistance.',
            ],
        ];
    }

    /**
     * Brooding Temperature Benchmarks (Dr. Kosiella Specifications)
     */
    public static function getBroodingTemperatures(): array
    {
        return [
            'chicken' => [
                'Week 1 (Days 1–7)' => ['temp_c' => '33–35°C', 'temp_f' => '91–95°F', 'action' => 'Check chick behavior: spread evenly means perfect; huddling under lamp means too cold; panting against wall means too hot.'],
                'Week 2 (Days 8–14)' => ['temp_c' => '30–32°C', 'temp_f' => '86–90°F', 'action' => 'Expand brooder ring by 50%; reduce heat source height slightly.'],
                'Week 3 (Days 15–21)' => ['temp_c' => '27–29°C', 'temp_f' => '81–84°F', 'action' => 'Remove brooder ring; allow birds full run of partitioned pen.'],
                'Week 4 (Days 22–28)' => ['temp_c' => '24–26°C', 'temp_f' => '75–79°F', 'action' => 'Turn off artificial heat during warm daytime hours.'],
                'Week 5+ (Days 29+)' => ['temp_c' => '21–24°C', 'temp_f' => '70–75°F', 'action' => 'Ambient room temperature. Focus on high ventilation and air exchange.'],
            ],
            'turkey' => [
                'Week 1 (Days 1–7)' => ['temp_c' => '35–37°C', 'temp_f' => '95–98°F', 'action' => 'Turkey poults require HIGHER initial heat than chicks. Never allow poults to chill; chilling causes irreversible stunting.'],
                'Week 2 (Days 8–14)' => ['temp_c' => '32–34°C', 'temp_f' => '90–93°F', 'action' => 'Step down temperature by 2–3°C per week.'],
                'Week 3 (Days 15–21)' => ['temp_c' => '29–31°C', 'temp_f' => '84–88°F', 'action' => 'Gradually widen brooder perimeter.'],
                'Week 4 (Days 22–28)' => ['temp_c' => '26–28°C', 'temp_f' => '78–82°F', 'action' => 'Monitor night temperatures carefully.'],
                'Week 5+ (Days 29+)' => ['temp_c' => '21–24°C', 'temp_f' => '70–75°F', 'action' => 'Maintain comfortable ambient conditions with heavy cross-ventilation.'],
            ],
        ];
    }

    /**
     * Feed Nutrition & Crude Protein Standards (Dr. Kosiella Specifications)
     */
    public static function getFeedNutritionBenchmarks(): array
    {
        return [
            'chicken' => [
                'Super Starter (Days 0–10)' => ['crude_protein' => '23–24% CP', 'energy' => '3,000 kcal/kg', 'notes' => 'Crumble form. High digestibility to kickstart rapid organ development.'],
                'Starter (Days 11–21)' => ['crude_protein' => '21–22% CP', 'energy' => '3,050 kcal/kg', 'notes' => 'Starter mash or crumble.'],
                'Grower (Days 22–35)' => ['crude_protein' => '19–20% CP', 'energy' => '3,100 kcal/kg', 'notes' => 'Pellet form preferred to maximize intake.'],
                'Finisher (Days 36–Harvest)' => ['crude_protein' => '18–19% CP', 'energy' => '3,200 kcal/kg', 'notes' => 'High energy formulation to build heavy breast muscle and finish weight.'],
            ],
            'turkey' => [
                'Poult Pre-Starter (Weeks 1–4)' => ['crude_protein' => '28% CP', 'energy' => '2,800–2,900 kcal/kg', 'notes' => 'TURKEY ESSENTIAL: Never feed broiler starter (21% CP) to poults! Poults will fail to grow, suffer weak legs, and die.'],
                'Turkey Starter (Weeks 5–8)' => ['crude_protein' => '24–26% CP', 'energy' => '2,900–3,000 kcal/kg', 'notes' => 'High lysine and methionine amino acid levels.'],
                'Turkey Grower (Weeks 9–14)' => ['crude_protein' => '20–22% CP', 'energy' => '3,050–3,150 kcal/kg', 'notes' => 'Transition to grower pellets.'],
                'Turkey Finisher (Weeks 15–Harvest)' => ['crude_protein' => '16–18% CP', 'energy' => '3,200–3,300 kcal/kg', 'notes' => 'High energy finishing ration.'],
            ],
        ];
    }

    /**
     * Blackhead Disease (Histomoniasis) Protocol (Dr. Kosiella Warning)
     */
    public static function getBlackheadDiseaseProtocol(): array
    {
        return [
            'causative_agent' => 'Histomonas meleagridis (protozoan parasite)',
            'vector' => 'Heterakis gallinarum (the common chicken cecal worm) and earthworms',
            'severity' => 'Extremely lethal in turkeys (up to 80–100% mortality). Chickens are asymptomatic carriers.',
            'symptoms' => [
                'Sulfur-yellow, foamy, watery droppings (classic diagnostic sign)',
                'Lethargy, drooping wings, ruffled feathers, reluctance to walk',
                'Cyanosis / darkening of head skin ("black head" - occurs in late stages due to poor oxygenation)',
                'Liver lesions: circular, sunken, target-like necrotic bullseye rings on post-mortem liver',
            ],
            'golden_prevention_rules' => [
                'RULE 1: NEVER house turkeys in the same pen or adjacent to chickens, guinea fowls, or ducks.',
                'RULE 2: NEVER put turkeys on ground or litter that housed chickens within the past 3 to 4 years.',
                'RULE 3: Strict routine deworming with Levamisole, Albendazole, or Ivermectin to kill cecal worm vectors.',
                'RULE 4: Provide dry, clean litter and concrete flooring to prevent earthworm ingestion.',
            ],
            'treatment' => 'Dimetridazole or Metronidazole (where legally permitted under veterinary prescription). Supportive electrolytes and biosecurity disinfection.',
        ];
    }

    /**
     * Rainy Season Special Farm Protocols (Dr. Kosiella Guidelines)
     */
    public static function getRainySeasonProtocols(): array
    {
        return [
            'coccidiosis_moisture_control' => [
                'hazard' => 'Warm, damp litter (>25% moisture) creates optimal incubation for coccidia oocysts within 24–48 hours.',
                'action' => 'Inspect litter around bell drinkers daily. Immediately rake wet patches and replace with dry, kiln-dried wood shavings. Never let litter cake.',
            ],
            'ammonia_and_crd' => [
                'hazard' => 'Closed curtains during heavy rain trap high ammonia fumes, scorching the trachea and triggering CRD / Mycoplasma.',
                'action' => 'Maintain top ridge ventilation even when curtains are drawn against side rain. Never seal a pen completely airtight.',
            ],
            'fowl_pox_mosquitoes' => [
                'hazard' => 'Puddles and tall grass around the pen multiply mosquitoes, which transmit Fowl Pox through bites.',
                'action' => 'Clear standing water within 20 meters of the pen. Clear bushes. Ensure Fowl Pox wing-web vaccination is completed.',
            ],
            'feed_spoilage_and_aflatoxins' => [
                'hazard' => 'High atmospheric humidity causes Aspergillus flavus mould growth in feed bags, releasing lethal aflatoxins.',
                'action' => 'Store feed bags on raised wooden pallets, 30cm away from walls. Buy smaller feed quantities (maximum 10–14 days stock). Add toxin binders to feed.',
            ],
        ];
    }

    /**
     * Multi-Species Incubation Timetables & Environmental Targets (Setter vs Hatcher)
     */
    public static function getIncubationSpeciesProfiles(): array
    {
        return [
            'chicken' => [
                'label' => 'Chicken (Broiler / Layer)',
                'total_days' => 21,
                'setter_days' => 18,
                'hatcher_days' => 3,
                'transfer_day' => 18,
                'candling_days' => [7, 14, 18],
                'setter_temp_c' => '37.5°C – 37.8°C',
                'setter_humidity' => '55% – 60% RH',
                'hatcher_temp_c' => '36.8°C – 37.2°C',
                'hatcher_humidity' => '70% – 75% RH',
                'notes' => 'Turn eggs hourly during Days 1–18. Stop turning on Day 18 transfer to hatcher.',
            ],
            'turkey' => [
                'label' => 'Turkey (Meat / Heritage)',
                'total_days' => 28,
                'setter_days' => 25,
                'hatcher_days' => 3,
                'transfer_day' => 25,
                'candling_days' => [10, 24, 25],
                'setter_temp_c' => '37.5°C',
                'setter_humidity' => '58% – 62% RH',
                'hatcher_temp_c' => '36.5°C – 37.0°C',
                'hatcher_humidity' => '75% – 80% RH',
                'notes' => '28-day cycle. Transfer to hatcher on Day 25; poults require 35–37°C brooding immediately upon breakout.',
            ],
            'duck' => [
                'label' => 'Duck (Pekin / Mallard / Runner)',
                'total_days' => 28,
                'setter_days' => 25,
                'hatcher_days' => 3,
                'transfer_day' => 25,
                'candling_days' => [7, 14, 25],
                'setter_temp_c' => '37.5°C',
                'setter_humidity' => '60% – 65% RH',
                'hatcher_temp_c' => '37.0°C',
                'hatcher_humidity' => '80% – 85% RH',
                'notes' => 'Cool eggs 15 mins daily & mist with lukewarm water from Day 14 to Day 25.',
            ],
            'muscovy_duck' => [
                'label' => 'Muscovy Duck',
                'total_days' => 35,
                'setter_days' => 31,
                'hatcher_days' => 4,
                'transfer_day' => 31,
                'candling_days' => [10, 21, 31],
                'setter_temp_c' => '37.5°C',
                'setter_humidity' => '60% – 65% RH',
                'hatcher_temp_c' => '37.0°C',
                'hatcher_humidity' => '80% – 85% RH',
                'notes' => '35-day incubation. Daily cooling and misting from Day 14 to Day 31.',
            ],
            'guinea_fowl' => [
                'label' => 'Guinea Fowl',
                'total_days' => 28,
                'setter_days' => 24,
                'hatcher_days' => 4,
                'transfer_day' => 24,
                'candling_days' => [10, 24],
                'setter_temp_c' => '37.5°C – 37.8°C',
                'setter_humidity' => '55% – 60% RH',
                'hatcher_temp_c' => '37.0°C',
                'hatcher_humidity' => '75% – 80% RH',
                'notes' => 'Thick shells; stop turning on Day 24 and raise humidity to 75%+.',
            ],
            'quail' => [
                'label' => 'Quail (Coturnix / Japanese)',
                'total_days' => 17,
                'setter_days' => 14,
                'hatcher_days' => 3,
                'transfer_day' => 14,
                'candling_days' => [7, 14],
                'setter_temp_c' => '37.7°C',
                'setter_humidity' => '50% – 55% RH',
                'hatcher_temp_c' => '37.2°C',
                'hatcher_humidity' => '70% – 75% RH',
                'notes' => 'Rapid 17-day cycle. Transfer to fine-mesh hatcher trays on Day 14.',
            ],
            'goose' => [
                'label' => 'Goose',
                'total_days' => 30,
                'setter_days' => 26,
                'hatcher_days' => 4,
                'transfer_day' => 26,
                'candling_days' => [10, 20, 26],
                'setter_temp_c' => '37.4°C',
                'setter_humidity' => '60% – 65% RH',
                'hatcher_temp_c' => '36.9°C',
                'hatcher_humidity' => '80% – 85% RH',
                'notes' => 'Turn eggs 180° (not just 90°) and cool/mist daily from Day 10 to Day 26.',
            ],
            'ostrich' => [
                'label' => 'Ostrich',
                'total_days' => 42,
                'setter_days' => 39,
                'hatcher_days' => 3,
                'transfer_day' => 39,
                'candling_days' => [14, 28, 39],
                'setter_temp_c' => '36.2°C – 36.5°C',
                'setter_humidity' => '25% – 35% RH',
                'hatcher_temp_c' => '36.0°C',
                'hatcher_humidity' => '45% – 50% RH',
                'notes' => 'Lower temperature and very low humidity (25–35%) required to achieve 13–15% egg weight loss.',
            ],
        ];
    }

    /**
     * Synthesized Deep Veterinary Knowledge Prompt for Gemini AI & Clinical Engine
     * Extracted from Dr. Kosiella's "From Poult to Profit: The Ultimate Turkey Production Guide"
     * and "Poultry Starter Toolkit - June Edition: Rainy Season Special".
     */
    public static function getSystemPromptSummary(): string
    {
        return <<<EOT
=== DEEP VETERINARY & POULTRY CLINICAL PROTOCOLS (DR. KOSIELLA TEXTBOOKS) ===

1. SPECIES SPECIFIC NUTRITION & CRUDE PROTEIN (CP) REQUIREMENTS:
   - MEAT TURKEYS: Day 1 to Week 4 (Starter) requires 28% Crude Protein (CP) + 2,800 kcal/kg ME.
     * CRITICAL RULE: Turkeys CANNOT be fed standard broiler starter (which is only 21-23% CP). Feeding broiler feed to turkey poults causes severe stunting, leg weakness, and high mortality.
     * Grower (Weeks 5-8): 24-26% CP.
     * Developer (Weeks 9-12): 20-22% CP.
     * Finisher (Weeks 13+): 16-18% CP.
   - BROILER CHICKENS: Starter (Days 1-14): 22-23% CP (3,000 kcal). Grower (Days 15-28): 20-21% CP (3,100 kcal). Finisher (Days 29-slaughter): 18-19% CP (3,200 kcal).

2. BROODING TEMPERATURE & VENTILATION:
   - Turkey Poults: Day 1-7: 35°C–37°C under hover. Poults have poor eyesight and thermoregulation; provide bright light (minimum 50-70 lux) and ring guards. Decrease 3°C per week until reaching ambient 21°C–24°C at Week 5-6.
   - Broiler Chicks: Day 1-3: 33°C–35°C; Day 4-7: 32°C; Day 8-14: 29°C–31°C; Day 15-21: 26°C–28°C; Day 22+: 21°C–24°C.

3. GOLDEN RULES FOR LIVE VACCINES (GUMBORO / LASOTA / MAREK / FOWL POX):
   - 24-HOUR RULE: Stop ALL antibiotics, acidifiers, and anticoccidials 24 hours BEFORE and 24 hours AFTER live vaccination.
   - WATER PREPARATION: Never use chlorinated tap water. Live virus is killed by chlorine. Always neutralize water by adding 20g of skimmed milk powder per 10 Liters of water (2g/L) 20 minutes before adding vaccine.
   - WATER DEPRIVATION: Withhold water for 1.5 to 2 hours before vaccination so flock is thirsty and drinks the entire reconstituted vaccine volume within 1.5 to 2 hours in the morning.
   - AFTER-CARE: Follow up with multivitamins + electrolytes (Vitalyte/Amino-total) in drinking water for 24-48 hours.

4. TURKEY BLACKHEAD DISEASE (HISTOMONIASIS MELEAGRIDIS) PROTOCOL:
   - CAUSE & VECTOR: Histomonas meleagridis flagellate protozoan transmitted via Heterakis gallinarum (cecal worm) and earthworms.
   - STRICT FARM RULE: NEVER house turkeys with chickens, guinea fowls, or ducks. NEVER place turkeys on old chicken litter or ground used by chickens within 3-4 years.
   - SYMPTOMS: Sulfur-yellow foamy droppings, drooping wings, liver necrosis with circular sunken bullseye lesions, darkened cyanotic head.
   - CONTROL: Regular deworming with Levamisole/Albendazole to eliminate cecal worms, concrete floors, dry litter.

5. RAINY SEASON SPECIAL MANAGEMENT:
   - COCCIDIOSIS: Wet litter (>25% moisture) accelerates sporulation within 24-48h. Rake daily, replace damp spots under drinkers with dry wood shavings. Prophylactic or therapeutic amprolium/toltrazuril.
   - AMMONIA & CRD: Never seal pen completely with curtains during rain. Keep continuous top ridge air circulation.
   - FEED AFLATOXIN: High humidity breeds Aspergillus flavus fungus. Keep feed bags elevated on wooden pallets, 30cm off walls. Keep maximum 10-14 days feed stock on farm; use feed toxin binders.
   - MOSQUITO & FOWL POX: Rain puddles breed mosquitoes which vector Fowl Pox. Clear vegetation 20m around pen; vaccinate wing-web.

6. MULTI-SPECIES INCUBATION SCHEDULES (SETTER vs HATCHER):
   - Chicken: 21 days (Days 1-18 Setter @ 37.5-37.8°C, 55-60% RH; Days 19-21 Hatcher @ 36.8-37.2°C, 70-75% RH).
   - Turkey: 28 days (Days 1-25 Setter @ 37.5°C, 58-62% RH; Days 26-28 Hatcher @ 36.5-37.0°C, 75-80% RH).
   - Duck (Pekin): 28 days (Days 1-25 Setter; Days 26-28 Hatcher, 80-85% RH, misting Days 14-25).
   - Muscovy Duck: 35 days (Days 1-31 Setter; Days 32-35 Hatcher).
   - Guinea Fowl: 28 days (Days 1-24 Setter; Days 25-28 Hatcher).
   - Quail: 17 days (Days 1-14 Setter; Days 15-17 Hatcher).
   - Goose: 30 days (Days 1-26 Setter; Days 27-30 Hatcher).
   - Ostrich: 42 days (Days 1-39 Setter @ 36.2-36.5°C, 25-35% RH; Days 40-42 Hatcher @ 45-50% RH).

7. PRIORITY POULTRY DISEASE DIFFERENTIALS (MSD VETERINARY CLINICAL HANDBOOK):
   - Newcastle Disease (APMV-1): Respiratory distress, greenish diarrhoea, twisted neck/nervous signs -> Strict biosecurity + vaccination (no antiviral cure).
   - Avian Influenza (NOTIFIABLE): Sudden high mortality, head/face swelling, sharp egg drop -> Do NOT treat independently; quarantine & report immediately to veterinary authorities.
   - Infectious Bursal Disease (Gumboro): Ruffled feathers, watery diarrhoea, trembling, sharp mortality peak in young growers -> Electrolytes, stress reduction, secondary bacterial control, maternal-antibody-timed vaccination.
   - Infectious Bronchitis: Gasping, coughing, nasal discharge, misshapen/thin eggshells -> Supportive care + vaccination.
   - Coccidiosis: Bloody/watery droppings, ruffled feathers -> Anticoccidial (Amprolium/Toltrazuril/Sulpha) + electrolytes + dry litter management.
   - Mycoplasma CRD: Persistent rales, nasal/ocular discharge, swollen sinuses -> Formulary antibiotics (Tylosin/Doxycycline) reduce clinical impact; all-in/all-out biosecurity.
   - Salmonellosis: Young chick diarrhoea/mortality; carrier state in adults -> Sensitivity-guided antibiotics + strict hygiene (zoonotic risk).

8. RATIONAL DRUG DOSING & WITHDRAWAL RULES (FARMER'S & MSD HANDBOOKS):
   - Dose Formula: Dose required (mg) = Body weight (kg) × Prescribed dose (mg/kg); Volume (mL) = Dose required (mg) ÷ Concentration (mg/mL).
   - Withdrawal Period: Strictly observe meat/egg withdrawal days on the product label before slaughter or sale.
   - Never use antibiotics to treat viral diseases; always complete the full prescribed course.

9. BROILER & LAYER NUTRITION & MANAGEMENT RECIPES (POULTRY FARMING SUCCESS):
   - Avoid Unbalanced Dilution: Never dilute complete commercial feed with maize or bran unless formulated by a nutritionist (lowers protein/amino acids).
   - Broiler Slow-Growth 6-Step Plan: (1) Confirm age/strain target weight, (2) Weigh weekly representative sample, (3) Check feed phase, freshness & water flow/drinker height, (4) Fix heat stress, ventilation & stocking density, (5) Check droppings/respiratory signs, (6) Correct management or consult vet.
   - Layer Egg Production Formula: Hen-Day Egg Production % = (Eggs Produced ÷ Live Hens That Day) × 100. Keep consistent lighting, calcium/phosphorus, and uninterrupted clean water.

10. GOAT / RUMINANT MIXED-FARM REFERENCE (GOAT FARMING HANDBOOK):
   - Daily Dry Matter (DM) Rule: ~3% of body weight/day (e.g., 25 kg goat = 0.75 kg DM/day; at 25% forage DM = 3.0 kg fresh forage/day).
   - Mineral Safety: Use goat-specific mineral mixes (sheep mixes are often copper-free; cattle mixes can cause copper toxicity in goats).
   - Priority Goat Risks: PPR (vaccinate), Pneumonia (ventilation + antibiotics), Foot Rot (hoof trim + footbath + dry housing), Coccidiosis (dry bedding + anticoccidials).
EOT;
    }

    /**
     * Priority Poultry Disease Protocols (MSD Veterinary Clinical Handbook)
     */
    public static function getPoultryDiseaseProtocols(): array
    {
        return [
            'Newcastle Disease' => [
                'etiology' => 'Avian paramyxovirus type 1 (APMV-1)',
                'signs' => 'Respiratory distress, greenish diarrhoea, nervous signs (twisted neck/paralysis), sharp drop in egg production.',
                'treatment' => 'No specific antiviral cure; supportive electrolytes/vitamins and control of secondary bacterial infections.',
                'prevention' => 'Strict biosecurity and timely LaSota/Komarov vaccination per schedule.',
            ],
            'Avian Influenza' => [
                'etiology' => 'Avian Influenza Type A virus (Notifiable Disease)',
                'signs' => 'Sudden flock mortality, swelling of head/face, respiratory distress, diarrhoea, severe egg drop.',
                'treatment' => 'No treatment permitted. Quarantine immediately and report suspect cases to official veterinary authorities.',
                'prevention' => 'Strict biosecurity, exclude wild birds, controlled stock introduction.',
            ],
            'Infectious Bursal Disease (Gumboro)' => [
                'etiology' => 'Birnavirus targeting the bursa of Fabricius (immunosuppressive)',
                'signs' => 'Depression, ruffled feathers, watery diarrhoea, trembling, prostration, sharp mortality peak in young growers.',
                'treatment' => 'No antiviral; supportive electrolytes, stress reduction, and management of secondary infections.',
                'prevention' => 'Vaccination timed to maternal antibody levels, biosecurity, and clean litter.',
            ],
            'Infectious Bronchitis' => [
                'etiology' => 'Avian infectious bronchitis coronavirus',
                'signs' => 'Gasping, coughing, nasal discharge, poor growth, reduced egg production and thin/wrinkled shells.',
                'treatment' => 'Supportive care, warmth/ventilation balance, and secondary bacterial control.',
                'prevention' => 'Vaccination and strict all-in/all-out hygiene.',
            ],
            'Coccidiosis' => [
                'etiology' => 'Eimeria spp. intestinal protozoa',
                'signs' => 'Bloody or watery droppings, ruffled feathers, huddling, pale combs, poor weight gain.',
                'treatment' => 'Anticoccidial medication (Amprolium, Toltrazuril, or Sulphadimidine) + electrolytes/vitamins.',
                'prevention' => 'Dry litter management, proper stocking density, and coccidiostat programme.',
            ],
            'Mycoplasma CRD' => [
                'etiology' => 'Mycoplasma gallisepticum (often with secondary E. coli)',
                'signs' => 'Persistent rales, coughing, nasal/ocular discharge, swollen sinuses, reduced growth/lay.',
                'treatment' => 'Formulary antibiotics (e.g., Tylosin, Doxycycline) to reduce clinical impact; does not clear carrier state.',
                'prevention' => 'Source Mycoplasma-free chicks, all-in/all-out system, good ventilation.',
            ],
            'Salmonellosis' => [
                'etiology' => 'Salmonella spp. (zoonotic)',
                'signs' => 'Diarrhoea, pasted vents, depression, increased chick mortality; subclinical carrier adults.',
                'treatment' => 'Sensitivity-guided antibiotics used judiciously + supportive fluids and strict sanitation.',
                'prevention' => 'Clean hatchery/stock sourcing, rodent control, and handler hygiene.',
            ],
        ];
    }

    /**
     * Rational Drug Dosing & Withdrawal Rules (MSD & Farmer's Handbooks)
     */
    public static function getDrugDosingAndWithdrawalRules(): array
    {
        return [
            'dose_formula' => 'Dose required (mg) = Body weight (kg) × Prescribed dose (mg/kg). Volume (mL) = Dose required (mg) ÷ Concentration (mg/mL).',
            'antibiotic_stewardship' => 'Confirm bacterial indication; never use antibiotics for viral diseases; complete the full 3–5 day course.',
            'pre_vaccine_rule' => 'Stop all antibiotics, acidifiers, and anticoccidials 24 hours BEFORE and 24 hours AFTER live vaccination.',
            'withdrawal_rule' => 'Strictly observe meat and egg withdrawal days on the medicine label before slaughter or selling eggs/birds.',
            'notifiable_rule' => 'Suspected Avian Influenza or sudden unexplained mass mortality must be quarantined and reported to a veterinary authority.',
        ];
    }

    /**
     * Broiler & Layer Management Recipes (Poultry Farming Success Handbook)
     */
    public static function getBroilerAndLayerManagementRecipes(): array
    {
        return [
            'feed_dilution_rule' => 'Do not dilute complete commercial feed with extra maize or bran unless formulated by a nutritionist—unbalanced dilution lowers protein, amino acids, and minerals.',
            'feed_storage_rule' => 'Store feed on wooden pallets, 30 cm off walls, in dry ventilated rooms protected from rodents; never feed mouldy feed (mycotoxin risk).',
            'broiler_slow_growth_plan' => [
                '1. Confirm age, strain, and breeder target weight.',
                '2. Weigh a representative sample weekly from different pen zones.',
                '3. Verify correct feed phase (Starter/Grower/Finisher), freshness, and uninterrupted clean water.',
                '4. Check heat stress, airflow/ventilation, litter dryness, and stocking density.',
                '5. Inspect droppings, respiratory signs, and daily mortality.',
                '6. Correct management gaps immediately or consult a veterinarian.',
            ],
            'layer_egg_formula' => 'Hen-Day Egg Production % = (Eggs Produced ÷ Live Hens That Day) × 100.',
            'layer_checklist' => 'Maintain consistent lighting programme, balanced layer ration with adequate Calcium & Phosphorus, and continuous clean water.',
        ];
    }

    /**
     * Goat & Ruminant Reference (Goat Farming & MSD Clinical Handbooks)
     */
    public static function getRuminantGoatReference(): array
    {
        return [
            'daily_dm_formula' => 'Daily Dry Matter (DM) intake ≈ 3% of body weight (e.g., 25 kg goat = 0.75 kg DM/day; at 25% forage DM = 3.0 kg fresh forage/day).',
            'mineral_safety' => 'Use goat-specific mineral mixes—sheep mixes are often copper-free while cattle mixes can cause copper toxicity in goats.',
            'priority_diseases' => [
                'PPR (Peste des Petits Ruminants)' => 'High fever, ocular/nasal discharge, mouth sores, diarrhoea — prevent via PPR vaccination and quarantine.',
                'Pneumonia' => 'Coughing, nasal discharge, laboured breathing — improve ventilation, reduce crowding, treat early with formulary antibiotics.',
                'Foot Rot' => 'Lameness, foul hoof odour — hoof trimming, footbath, dry bedding.',
                'Coccidiosis' => 'Diarrhoea in kids — dry pens, raised feeders, anticoccidial therapy.',
            ],
        ];
    }
}
