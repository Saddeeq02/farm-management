<?php

declare(strict_types=1);

namespace App\Core;

class AiCopilotService
{
    private FarmService $farm;

    public function __construct(FarmService $farm)
    {
        $this->farm = $farm;
    }

    /**
     * Convert Markdown-like bold/lists into clean, safe HTML for chat bubbles (zero raw ** or ###).
     */
    public static function formatToHtml(string $text): string
    {
        $escaped = htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');

        // Headings ### ... or #### ...
        $escaped = preg_replace('/^#{2,4}\s+(.+)$/m', '<div class="font-semibold text-slate-900 text-xs sm:text-sm mt-3 mb-1 tracking-tight">$1</div>', $escaped);

        // Bold **...**
        $escaped = preg_replace('/\*\*(.+?)\*\*/s', '<strong class="font-semibold text-slate-900">$1</strong>', $escaped);

        // Inline code `...`
        $escaped = preg_replace('/`([^`]+)`/', '<code class="px-1.5 py-0.5 rounded bg-slate-100 text-emerald-800 font-mono text-[11px] border border-slate-200/60">$1</code>', $escaped);

        // Italic *...*
        $escaped = preg_replace('/(?<!\*)\*([^\*\n]+)\*(?!\*)/', '<em class="text-slate-600">$1</em>', $escaped);

        // Bullet points "- ..." or "* ..." or "• ..."
        $lines = explode("\n", $escaped);
        $out = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '- ') || str_starts_with($trimmed, '* ') || str_starts_with($trimmed, '• ')) {
                $itemContent = substr($trimmed, 2);
                $out[] = '<div class="flex items-start gap-2 pl-1 py-0.5"><span class="text-emerald-600 font-bold leading-5">•</span><span class="flex-1 leading-relaxed">' . $itemContent . '</span></div>';
            } else {
                $out[] = $line;
            }
        }

        return nl2br(implode("\n", $out), false);
    }

    /**
     * Handle a user question from inside the Farm Management System UI.
     * Pre-configured in the backend by default so no user setup is required.
     */
    public function ask(string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            $msg = 'Hi there! Ask me anything about your uploaded medications, flock symptoms, incubation batches, feed stock, or selling prices.';
            return [
                'answer' => $msg,
                'answer_html' => self::formatToHtml($msg),
                'engine' => 'Farm & Vet AI',
                'timestamp' => date('H:i'),
            ];
        }

        // Persistent Self-Learning: Detect if user is teaching the AI a permanent farm rule/fact
        if (preg_match('/(remember\s+that|learn\s+that|save\s+rule|note\s+that|keep\s+in\s+mind|on\s+our\s+farm|in\s+our\s+farm|we\s+use\s+|from\s+now\s+on|always\s+use|never\s+use|my\s+supplier)/i', $question)) {
            $this->farm->addAiMemory($question, 'user_instruction');
        }

        $systemSnapshot = $this->buildLiveSystemSnapshot();

        // Check if backend environment or store has a Gemini key; otherwise use the built-in Farm & Veterinary AI Engine
        $apiKey = (string) (getenv('GEMINI_API_KEY') ?: $this->farm->getDatabase()->getSetting('gemini_api_key', '') ?: '');
        $model = (string) ($this->farm->getDatabase()->getSetting('gemini_model', 'gemini-3.8-flash') ?: 'gemini-3.8-flash');

        if ($apiKey !== '') {
            $geminiReply = $this->callGeminiInteractionsApi($apiKey, $model, $question, $systemSnapshot);
            if ($geminiReply !== null) {
                $this->saveChatTurn($question, $geminiReply, 'Farm & Vet AI');
                return [
                    'answer' => $geminiReply,
                    'answer_html' => self::formatToHtml($geminiReply),
                    'engine' => 'Farm & Vet AI',
                    'timestamp' => date('H:i'),
                ];
            }
        }

        $localReply = $this->generateSmartFarmResponse($question, $systemSnapshot);
        $this->saveChatTurn($question, $localReply, 'Farm & Vet AI');

        return [
            'answer' => $localReply,
            'answer_html' => self::formatToHtml($localReply),
            'engine' => 'Farm & Vet AI',
            'timestamp' => date('H:i'),
        ];
    }

    public function getChatHistory(): array
    {
        $history = $this->farm->getDatabase()->getTable('ai_chat_history');

        // Automatically clear legacy unformatted history if it didn't have message_html
        if (!empty($history) && !isset($history[0]['message_html'])) {
            $this->clearChatHistory();
            $history = [];
        }

        if (empty($history)) {
            $summary = $this->farm->getExecutiveSummary();
            $meds = $this->getUploadedMedications($this->farm->getInventoryItemsEnriched());
            $medNames = array_map(fn($m) => "**{$m['item_name']}**", $meds);
            $medListStr = !empty($medNames) ? implode(', ', $medNames) : 'your uploaded medications';

            $welcome = "Hello! I'm your **Farm & Veterinary AI Assistant** — active by default in the backend and connected to all 8 live database tables.\n\n"
                . "I have live access to your **" . number_format($summary['total_live_birds']) . " active birds**, **" . number_format($summary['active_eggs_in_incubator']) . " incubating eggs**, and your uploaded medications ({$medListStr}).\n\n"
                . "Ask me anything naturally:\n"
                . "- \"Tell me about the medications I uploaded and how to use them\"\n"
                . "- \"My birds are coughing and sneezing — which medicine in my stock should I give?\"\n"
                . "- \"What is the recommended dosage and cost for PRD-2026-01 today?\"\n"
                . "- \"How is PRD-2026-01 doing financially and what price should I sell at?\"";

            return [
                [
                    'id' => 1,
                    'role' => 'assistant',
                    'message' => $welcome,
                    'message_html' => self::formatToHtml($welcome),
                    'engine' => 'Farm & Vet AI',
                    'created_at' => date('H:i'),
                ],
            ];
        }

        foreach ($history as $idx => $item) {
            if (!isset($item['message_html'])) {
                $history[$idx]['message_html'] = self::formatToHtml((string) ($item['message'] ?? ''));
            }
        }

        return $history;
    }

    public function clearChatHistory(): void
    {
        $db = $this->farm->getDatabase();
        $reflection = new \ReflectionClass($db);
        if ($reflection->hasProperty('store')) {
            $prop = $reflection->getProperty('store');
            $prop->setAccessible(true);
            $store = $prop->getValue($db);
            $store['ai_chat_history'] = [];
            $prop->setValue($db, $store);
            $saveMethod = $reflection->getMethod('saveStore');
            $saveMethod->setAccessible(true);
            $saveMethod->invoke($db);
        }
    }

    private function saveChatTurn(string $question, string $answer, string $engine): void
    {
        $db = $this->farm->getDatabase();
        $db->insert('ai_chat_history', [
            'role' => 'user',
            'message' => $question,
            'message_html' => htmlspecialchars($question, ENT_QUOTES, 'UTF-8'),
            'engine' => 'You',
            'created_at' => date('H:i'),
        ]);
        $db->insert('ai_chat_history', [
            'role' => 'assistant',
            'message' => $answer,
            'message_html' => self::formatToHtml($answer),
            'engine' => $engine,
            'created_at' => date('H:i'),
        ]);
    }

    private function buildLiveSystemSnapshot(): array
    {
        $inv = $this->farm->getInventoryItemsEnriched();
        return [
            'summary' => $this->farm->getExecutiveSummary(),
            'incubation_batches' => $this->farm->getIncubationBatchesEnriched(),
            'inventory_items' => $inv,
            'uploaded_medications' => $this->getUploadedMedications($inv),
            'production_batches' => $this->farm->getProductionBatchesEnriched(),
            'active_reminders' => $this->farm->getActiveReminders(),
            'custom_farm_memories' => $this->farm->getAiMemories(),
            'incubation_species_profiles' => VeterinaryKnowledgeBase::getIncubationSpeciesProfiles(),
        ];
    }

    /**
     * Extract all medications uploaded into Inventory (Module 2) and attach clinical knowledge profiles.
     */
    public function getUploadedMedications(array $inventoryItems): array
    {
        $meds = [];
        foreach ($inventoryItems as $item) {
            if (($item['category'] ?? '') === 'medicine') {
                $item['clinical_profile'] = $this->analyzeMedicationName((string) $item['item_name'], (string) ($item['unit_label'] ?? 'ml'));
                $meds[] = $item;
            }
        }
        return $meds;
    }

    /**
     * Built-in Poultry Veterinary Pharmacology Knowledge Base that analyzes any uploaded medication name!
     */
    public function analyzeMedicationName(string $medName, string $unitLabel = 'ml'): array
    {
        $n = strtolower($medName);

        if (preg_match('/(gumboro|ibd|infectious\s*bursal)/i', $n) && preg_match('/(lasota|ndv|newcastle)/i', $n)) {
            return [
                'class' => 'Combined Viral Vaccine & Immune Booster Pack',
                'targets' => 'Infectious Bursal Disease (Gumboro) + Newcastle Disease (Ranikhet) + Vaccine Stress Recovery',
                'symptoms_matched' => ['gumboro', 'lasota', 'newcastle', 'vaccine', 'vaccination', 'white diarrhea', 'twisted neck', 'drooping wings', 'ruffled feathers', 'viral'],
                'route' => 'Oral drinking water vaccination (skim milk stabilized)',
                'dosage_per_1000_birds' => 500.0, // 500 ml reconstituted / pack allocation per 1000 birds
                'dosage_guide' => "Administer **0.5 {$unitLabel} per bird** (500 {$unitLabel} per 1,000 birds) in cool, chlorine-free drinking water after withholding water for 1.5–2 hours. Ideal at **Days 7, 14, 21, and 28**.",
                'withdrawal' => '0 days (Vaccine & Vitamin supplement)',
                'precaution' => 'Do not use chlorinated water or disinfectants in drinkers 24 hours before or after vaccination. Follow immediately with multivitamins/electrolytes.',
            ];
        }

        if (preg_match('/(gumboro|ibd|bursal)/i', $n)) {
            return [
                'class' => 'Live Attenuated Viral Vaccine (IBDV)',
                'targets' => 'Infectious Bursal Disease (Gumboro)',
                'symptoms_matched' => ['gumboro', 'ibd', 'bursa', 'whitish watery diarrhea', 'pecking vent', 'prostration', 'vaccine'],
                'route' => 'Drinking water or eye drop',
                'dosage_per_1000_birds' => 400.0,
                'dosage_guide' => "Give at **Day 7–10** (1st dose) and **Day 18–21** (booster) at **0.4 {$unitLabel} per bird** in cool chlorine-free water consumed within 2 hours.",
                'withdrawal' => '0 days',
                'precaution' => 'Store at 2°C–8°C; avoid direct sunlight and chlorinated water.',
            ];
        }

        if (preg_match('/(lasota|newcastle|ndv|komarov)/i', $n)) {
            return [
                'class' => 'Live Lentogenic Newcastle Disease Vaccine',
                'targets' => 'Newcastle Disease (NDV)',
                'symptoms_matched' => ['lasota', 'newcastle', 'twisted neck', 'torticollis', 'greenish diarrhea', 'paralysis', 'gasping', 'vaccine'],
                'route' => 'Drinking water, intraocular, or spray',
                'dosage_per_1000_birds' => 450.0,
                'dosage_guide' => "Administer at **Day 10–12** and **Day 21–24** at **0.45 {$unitLabel} per bird** in cool non-chlorinated drinking water.",
                'withdrawal' => '0 days',
                'precaution' => 'Do not vaccinate sick or severely stressed birds; give multivitamins after vaccination.',
            ];
        }

        if (preg_match('/(tylodox|tylosin|doxy|enro|crd|respirat|mycoplasma|coryza)/i', $n)) {
            return [
                'class' => 'Broad-Spectrum Macrolide + Tetracycline Antibiotic',
                'targets' => 'Chronic Respiratory Disease (CRD / Mycoplasma), Infectious Coryza, E. coli (Colibacillosis), Coughing, Sneezing, Rales & Sinusitis',
                'symptoms_matched' => ['cough', 'coughing', 'sneez', 'sneezing', 'rattling', 'rales', 'breathing', 'respiratory', 'crd', 'mucus', 'swollen head', 'coryza', 'antibiotic', 'tylodox', 'tylosin', 'doxycycline', 'infection'],
                'route' => 'Soluble powder in drinking water (3–5 consecutive days)',
                'dosage_per_1000_birds' => 200.0, // 200g per 1000 birds per day (1g per 2 liters)
                'dosage_guide' => "Dissolve **1 g per 2 Liters of drinking water** (approx. **0.2 {$unitLabel} per bird/day** or **200 {$unitLabel} per 1,000 birds**) for **3 to 5 consecutive days**.",
                'withdrawal' => '7 days before slaughter',
                'precaution' => 'Prepare fresh medicated water daily and remove unmedicated water sources during treatment.',
            ];
        }

        if (preg_match('/(amprol|cocci|toltrazuril|diclazuril|sulfa|embazin|anticocci)/i', $n)) {
            return [
                'class' => 'Anticoccidial / Protozoal Therapy',
                'targets' => 'Avian Coccidiosis (Eimeria tenella, E. necatrix, E. acervulina) — bloody droppings & intestinal lesions',
                'symptoms_matched' => ['blood', 'bloody', 'droppings', 'stool', 'diarrhea', 'coccidiosis', 'cocci', 'pale comb', 'amprolium', 'sulfa'],
                'route' => 'Oral via drinking water (3–5 days)',
                'dosage_per_1000_birds' => 250.0,
                'dosage_guide' => "Administer **0.25 {$unitLabel} per bird** (250 {$unitLabel} per 1,000 birds) in drinking water for **3–5 days**. Avoid excess B-vitamins during Amprolium treatment.",
                'withdrawal' => '5–7 days before slaughter',
                'precaution' => 'Keep litter dry and turn wood shavings frequently to prevent oocyst sporulation.',
            ];
        }

        if (preg_match('/(oxytet|tetracycline|neoceryl|neomycin|gentamicin|colistin|keproceryl)/i', $n)) {
            return [
                'class' => 'Broad-Spectrum Antibacterial + Vitamin Complex',
                'targets' => 'Early Chick Mortality, Omphalitis (Yolk Sac Infection), Pullorum, Fowl Typhoid, Enteritis & Bacterial Diarrhea',
                'symptoms_matched' => ['diarrhea', 'enteritis', 'yolk sac', 'early mortality', 'typhoid', 'salmonella', 'bacterial', 'oxytet', 'antibiotic'],
                'route' => 'Oral via drinking water (3–5 days)',
                'dosage_per_1000_birds' => 200.0,
                'dosage_guide' => "Dissolve **1 g per 2 Liters of water** (approx. **0.2 {$unitLabel} per bird/day**) for **3–5 days**.",
                'withdrawal' => '7 days before slaughter',
                'precaution' => 'Clean drinkers daily to prevent biofilm buildup.',
            ];
        }

        if (preg_match('/(vitamin|multivit|vitalyte|electrolyte|amino|stress|glucose)/i', $n)) {
            return [
                'class' => 'Metabolic, Anti-Stress & Immune Support Supplement',
                'targets' => 'Heat Stress, Post-Vaccination Stress, Feed Transition Stress, Appetite Boost & Growth Recovery',
                'symptoms_matched' => ['stress', 'heat', 'panting', 'weak', 'weakness', 'appetite', 'vitamin', 'electrolyte', 'boost', 'growth'],
                'route' => 'Oral in drinking water',
                'dosage_per_1000_birds' => 150.0,
                'dosage_guide' => "Add **0.15 {$unitLabel} per bird** (150 {$unitLabel} per 1,000 birds) to drinking water for **3–5 days** during brooding, heat waves, or feed transitions.",
                'withdrawal' => '0 days',
                'precaution' => 'Safe for all flock ages with zero withdrawal period.',
            ];
        }

        if (preg_match('/(worm|deworm|piperazine|levamisole|albendazole|fenbendazole)/i', $n)) {
            return [
                'class' => 'Poultry Anthelmintic (Dewormer)',
                'targets' => 'Roundworms (Ascaridia galli), Hairworms (Capillaria), and Cecal Worms (Heterakis)',
                'symptoms_matched' => ['worm', 'worms', 'deworm', 'weight loss', 'thin', 'parasite'],
                'route' => 'Single-day drinking water medication',
                'dosage_per_1000_birds' => 300.0,
                'dosage_guide' => "Administer **0.3 {$unitLabel} per bird** in morning water consumed within 4 hours; repeat after 3–4 weeks if needed.",
                'withdrawal' => '7 days',
                'precaution' => 'Follow with multivitamins and litter sanitation.',
            ];
        }

        // Dynamic fallback for ANY custom medication name uploaded by the user!
        return [
            'class' => 'Clinical Poultry Medication / Veterinary Stock (' . $medName . ')',
            'targets' => 'Flock health protection, bacterial/viral prophylaxis, and clinical recovery for ' . $medName,
            'symptoms_matched' => [strtolower($medName)],
            'route' => 'Oral administration via drinking water',
            'dosage_per_1000_birds' => 250.0,
            'dosage_guide' => "Standard flock maintenance dose: **0.25 {$unitLabel} per bird** (**250 {$unitLabel} per 1,000 birds**) mixed into clean daily drinking water.",
            'withdrawal' => 'Check manufacturer label (typically 5–7 days for antibiotics, 0 days for vitamins/vaccines)',
            'precaution' => 'Ensure uniform mixing in clean, non-chlorinated drinking water and log exact dosage in Module 3.',
        ];
    }

    private function callGeminiInteractionsApi(string $apiKey, string $model, string $question, array $snapshot): ?string
    {
        $vetSummary = VeterinaryKnowledgeBase::getSystemPromptSummary();
        $memories = $snapshot['custom_farm_memories'] ?? [];
        $memoryLines = array_map(fn($m) => "- [{$m['category']}] {$m['fact']}", array_slice(array_reverse($memories), 0, 25));
        $memoryBlock = !empty($memoryLines) ? implode("\n", $memoryLines) : '- No custom farm rules saved yet.';
        $systemInstruction = "You are Google Gemini, serving as the intelligent Poultry Farm & Veterinary AI Copilot for Blackswan Farms and General Enterprises, embedded inside their Poultry Farm & Hatchery Management System. "
            . "You possess deep domain veterinary learning extracted from Dr. Kosiella's authoritative textbooks AND the MSD Veterinary Consult Clinical Handbooks (Poultry Farming Success, Farmer's Veterinary Clinical Handbook, Goat Farming Handbook, and MSD Clinical Practical Procedures). "
            . "You have direct real-time access to the farm's live database snapshot (batches, daily telemetry, mortality, expenses, feed inventory, uploaded medications, clinical reminders, and persistent learned farm memory). "
            . "Always apply the farm's learned rules/history in `LEARNED FARM MEMORY` alongside the clinical protocols. "
            . "Talk naturally, fluently, and intelligently like official Google Gemini. "
            . "When the user asks about diseases, vaccines, or medications, look at the uploaded medications in the database, recommend the exact product, calculate the dosage for the active flock size, and give sound clinical guidance. "
            . "Always enforce critical protocols: e.g. Turkey poults strictly need 28% Crude Protein (cannot survive on broiler feed), never house turkeys with chickens due to Blackhead Disease (Histomoniasis), stop antibiotics 24h before live vaccines, neutralize water with 20g/10L skimmed milk, withhold water 1.5-2h before vaccination, avoid unbalanced feed dilution, observe drug withdrawal periods, and manage wet litter/aflatoxins during the rainy season. "
            . "When the user asks about financial or batch metrics, use exact Naira (₦) figures and batch codes from the data. "
            . "Do not give robotic or rigid answers; be conversational, sharp, and helpful.\n\n"
            . "LEARNED FARM MEMORY (AUTO-LEARNED & MANAGER RULES):\n" . $memoryBlock . "\n\n"
            . $vetSummary . "\n\n"
            . "LIVE FARM DATABASE SNAPSHOT:\n" . json_encode($snapshot, JSON_UNESCAPED_UNICODE);

        $payload = json_encode([
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $question]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 4000,
            ]
        ], JSON_UNESCAPED_UNICODE);

        $modelName = !empty($model) ? $model : 'gemini-2.5-flash-lite';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . urlencode($apiKey);

        // Uses HTTP stream context so it works reliably on any PHP installation (even without php-curl)
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 30,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ]
        ];

        try {
            $ctx = stream_context_create($opts);
            $response = @file_get_contents($url, false, $ctx);
            if ($response !== false) {
                $decoded = json_decode($response, true);
                if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                    return (string) $decoded['candidates'][0]['content']['parts'][0]['text'];
                }
            }
        } catch (\Throwable $e) {
            // Log or fallback
        }

        return null;
    }

    /**
     * Natural, conversational Farm & Veterinary AI responder with full awareness of uploaded medications.
     */
    private function generateSmartFarmResponse(string $question, array $snapshot): string
    {
        $q = strtolower(trim($question));
        $summary = $snapshot['summary'];
        $incBatches = $snapshot['incubation_batches'];
        $invItems = $snapshot['inventory_items'];
        $uploadedMeds = $snapshot['uploaded_medications'] ?? [];
        $prodBatches = $snapshot['production_batches'];
        $primaryBatch = $prodBatches[0] ?? null;

        // 1. Check if the user is asking about a specific uploaded medication by name OR asking about medications/symptoms/treatment/vaccines/dosage
        $matchedMed = null;
        foreach ($uploadedMeds as $med) {
            $medNameLower = strtolower($med['item_name']);
            // Match full or partial distinct words of the uploaded medication name
            $words = array_filter( preg_split('/[\s\-\+\(\)\/]+/', $medNameLower), fn($w) => strlen($w) >= 4 && !in_array($w, ['pack', 'broad', 'spectrum', 'poultry', 'soluble'], true));
            foreach ($words as $w) {
                if (str_contains($q, $w)) {
                    $matchedMed = $med;
                    break 2;
                }
            }
            // Also check symptom match
            foreach ($med['clinical_profile']['symptoms_matched'] as $sym) {
                if (strlen($sym) >= 4 && str_contains($q, $sym)) {
                    $matchedMed = $med;
                    break 2;
                }
            }
        }

        // 0. MSD Veterinary Consult & New Handbooks (Poultry Farming Success, Farmer's Handbook, Goat Handbook, MSD Clinical)
        if (preg_match('/(handbook|msd|dango|recipe|pdf|compare.*disease|newcastle|influenza|bird flu|bronchitis|salmonella|withdrawal|dose calc|dosing formula|mg\/kg|slow|not growing|stunt|dilution|dilut|layer|egg|shell|hen-day|hen day|goat|ruminant|forage|ppr|foot rot|copper)/i', $q)) {
            if (preg_match('/(slow|not growing|stunt|dilution|dilut|layer|egg|shell|hen-day|hen day)/i', $q)) {
                $recipes = VeterinaryKnowledgeBase::getBroilerAndLayerManagementRecipes();
                $lines = ["### Broiler & Layer Management Recipes (Poultry Farming Success Handbook)\n"];
                $lines[] = "- **Feed Dilution Rule:** {$recipes['feed_dilution_rule']}";
                $lines[] = "- **Feed Storage Rule:** {$recipes['feed_storage_rule']}";
                $lines[] = "- **Layer Egg Formula:** `{$recipes['layer_egg_formula']}` — {$recipes['layer_checklist']}";
                $lines[] = "\n**6-Step Broiler Slow-Growth Action Plan:**";
                foreach ($recipes['broiler_slow_growth_plan'] as $step) {
                    $lines[] = "- {$step}";
                }
                return implode("\n", $lines);
            }

            if (preg_match('/(goat|ruminant|forage|ppr|foot rot|copper)/i', $q)) {
                $goat = VeterinaryKnowledgeBase::getRuminantGoatReference();
                $lines = ["### Goat & Ruminant Ration & Health Reference (Goat Farming Handbook)\n"];
                $lines[] = "- **Daily Dry Matter (DM) Intake:** {$goat['daily_dm_formula']}";
                $lines[] = "- **Mineral Safety:** {$goat['mineral_safety']}";
                $lines[] = "\n**Priority Goat Health Protocols:**";
                foreach ($goat['priority_diseases'] as $dName => $dDesc) {
                    $lines[] = "- **{$dName}:** {$dDesc}";
                }
                return implode("\n", $lines);
            }

            if (preg_match('/(withdrawal|dose calc|dosing formula|mg\/kg)/i', $q)) {
                $rules = VeterinaryKnowledgeBase::getDrugDosingAndWithdrawalRules();
                return "### Rational Drug Dosing & Withdrawal Rules (MSD & Farmer's Handbooks)\n"
                    . "- **Dose Formula:** `{$rules['dose_formula']}`\n"
                    . "- **Antibiotic Stewardship:** {$rules['antibiotic_stewardship']}\n"
                    . "- **Pre-Vaccine Rule:** {$rules['pre_vaccine_rule']}\n"
                    . "- **Withdrawal Period:** {$rules['withdrawal_rule']}\n"
                    . "- **Notifiable Disease Rule:** {$rules['notifiable_rule']}";
            }

            if (preg_match('/(newcastle|influenza|bird flu|bronchitis|salmonella|gumboro|coccid|compare.*disease)/i', $q)) {
                $diseases = VeterinaryKnowledgeBase::getPoultryDiseaseProtocols();
                $lines = ["### Priority Poultry Disease Differentials (MSD Veterinary Clinical Handbook)\n"];
                foreach ($diseases as $name => $info) {
                    $lines[] = "**{$name}** (*{$info['etiology']}*)";
                    $lines[] = "- **Signs:** {$info['signs']}";
                    $lines[] = "- **Treatment:** {$info['treatment']}";
                    $lines[] = "- **Prevention:** {$info['prevention']}\n";
                }
                return implode("\n", $lines);
            }

            // General overview of all 4 integrated handbooks
            $rules = VeterinaryKnowledgeBase::getDrugDosingAndWithdrawalRules();
            $recipes = VeterinaryKnowledgeBase::getBroilerAndLayerManagementRecipes();
            $goat = VeterinaryKnowledgeBase::getRuminantGoatReference();
            return "### Integrated MSD & Dr. Salisu Hassan Dango Handbooks Active\n"
                . "I have learned and integrated all 4 veterinary & farming handbooks:\n"
                . "- **MSD Veterinary Clinical Handbook:** 7 Priority Poultry Disease differentials (Newcastle, Avian Influenza, Gumboro, Infectious Bronchitis, Coccidiosis, CRD, Salmonellosis) + `{$rules['dose_formula']}`\n"
                . "- **Farmer's Veterinary Clinical Handbook:** {$rules['withdrawal_rule']} {$rules['pre_vaccine_rule']}\n"
                . "- **Poultry Farming Success:** 6-step Broiler Slow-Growth Action Plan, Layer `{$recipes['layer_egg_formula']}`, and anti-dilution rule (*{$recipes['feed_dilution_rule']}*)\n"
                . "- **Goat Farming Handbook:** {$goat['daily_dm_formula']} ({$goat['mineral_safety']})\n\n"
                . "Ask me about any disease, dosing formula, broiler growth issue, layer egg drop, or goat ration!";
        }

        // If user asked about a specific uploaded medication or clinical symptom (coughing, sneezing, diarrhea, coccidiosis, gumboro, lasota, tylodox, etc.)
        if ($matchedMed !== null || preg_match('/(medicat|medicin|vaccin|drug|treat|dose|dosage|symptom|sick|cough|sneez|diarrhea|droppings|blood|crd|gumboro|lasota|tylodox|antibiotic|vet|advice|advise|prescri)/i', $q)) {
            if ($matchedMed !== null) {
                $prof = $matchedMed['clinical_profile'];
                $unit = $matchedMed['unit_label'];
                $unitCost = (float) $matchedMed['unit_cost_per_g_or_ml'];
                $remaining = (float) $matchedMed['total_quantity_g_or_ml'];

                $lines = [
                    "### Veterinary & Clinical Advisory: **{$matchedMed['item_name']}**",
                    "- **Classification:** {$prof['class']}",
                    "- **Primary Indications (What It Treats):** {$prof['targets']}",
                    "- **Your Current Stock in Inventory:** **" . number_format($remaining) . " {$unit}** remaining ({$matchedMed['stock_pct']}%) at **₦" . number_format($unitCost, 2) . " per {$unit}**",
                    "- **Administration & Mixing Guide:** {$prof['dosage_guide']}",
                    "- **Withdrawal Period:** {$prof['withdrawal']}",
                    "- **Clinical Precaution:** {$prof['precaution']}",
                ];

                // Compute exact dosage & cost for each active production batch!
                if (!empty($prodBatches)) {
                    $lines[] = "\n### Recommended Dosage & Cost for Your Active Flocks";
                    foreach ($prodBatches as $pb) {
                        $birds = (int) $pb['metrics']['current_in_house_birds'];
                        $recommendedDose = round(($birds / 1000.0) * (float) $prof['dosage_per_1000_birds'], 1);
                        $doseCost = round($recommendedDose * $unitCost, 2);
                        $enoughStock = $remaining >= $recommendedDose ? '✅ In Stock' : '⚠️ Restock Needed';
                        $lines[] = "- **{$pb['batch_code']}** (" . number_format($birds) . " live birds, Day {$pb['age_days']}): Administer **{$recommendedDose} {$unit}** → Exact Batch Cost: **₦" . number_format($doseCost, 2) . "** ({$enoughStock})";
                    }
                }

                // If user asked about symptoms not covered by this med alone, mention other uploaded meds too
                if (preg_match('/(blood|cocci)/i', $q) && !preg_match('/(amprol|cocci|sulfa)/i', strtolower($matchedMed['item_name']))) {
                    $lines[] = "\n*Veterinary Note:* For bloody droppings (Coccidiosis), we also recommend uploading **Amprolium 20%** or **Toltrazuril** in **Module 2 (Inventory)** so you can log anticoccidial treatment alongside your current stock.";
                }

                return implode("\n", $lines);
            }

            // General overview of all uploaded medications + clinical advice
            $lines = ["### Your Uploaded Medications & Veterinary Advisory\n"];
            if (empty($uploadedMeds)) {
                $lines[] = "You haven't uploaded any medications in **Module 2 (Inventory)** yet. Once you add any medication or vaccine (e.g., *Gumboro*, *Lasota*, *Tylodox*, *Amprolium*, or *Multivitamins*), I will automatically recognize its name, explain what it treats, and calculate the exact dosage for your flocks!";
                return implode("\n", $lines);
            }

            $lines[] = "I scanned your **Module 2 Inventory** and found **" . count($uploadedMeds) . " uploaded medication(s)**. Here is what each one does and how much to give your active flocks:\n";

            foreach ($uploadedMeds as $med) {
                $prof = $med['clinical_profile'];
                $unit = $med['unit_label'];
                $unitCost = (float) $med['unit_cost_per_g_or_ml'];
                $remaining = (float) $med['total_quantity_g_or_ml'];

                $lines[] = "#### **{$med['item_name']}** (`" . number_format($remaining) . " {$unit}` in stock @ **₦" . number_format($unitCost, 2) . "/{$unit}**)";
                $lines[] = "- **Used For:** {$prof['targets']}";
                $lines[] = "- **How to Use:** {$prof['dosage_guide']}";

                foreach ($prodBatches as $pb) {
                    $birds = (int) $pb['metrics']['current_in_house_birds'];
                    $dose = round(($birds / 1000.0) * (float) $prof['dosage_per_1000_birds'], 1);
                    $cost = round($dose * $unitCost, 2);
                    $lines[] = "- **Dose for {$pb['batch_code']} ({$birds} birds, Day {$pb['age_days']}):** **{$dose} {$unit}** (Costs **₦" . number_format($cost, 2) . "**)";
                }
                $lines[] = "";
            }

            $lines[] = "*Tip: Whenever you upload a new medication in **Module 2 (Inventory)**, I automatically detect its name and can advise you on its disease targets, water mixing ratio, and exact dosage!*";
            return implode("\n", $lines);
        }

        // 2. Natural Greetings & Small Talk ("hi", "hello", "how are you", "good morning", "thanks")
        if (preg_match('/^(hi|hello|hey|salam|assalam|good\s*(morning|afternoon|evening)|how\s*are\s*you|what\'?s\s*up)\b/i', $q)) {
            $medCount = count($uploadedMeds);
            return "Hello! I'm doing great and monitoring your farm and clinical ledger.\n\n"
                . "Right now you have **" . number_format($summary['total_live_birds']) . " live birds** across **{$summary['active_production_count']} active flocks**, **" . number_format($summary['active_eggs_in_incubator']) . " viable eggs** in the incubator, and **{$medCount} uploaded medications** in stock.\n\n"
                . "What can I help you with? You can ask me about any uploaded medication, describe flock symptoms for clinical advice, check a batch like `PRD-2026-01`, or calculate your selling price.";
        }

        if (preg_match('/^(thanks|thank\s*you|ok\s*thanks|great|awesome|cool)\b/i', $q)) {
            return "You're very welcome! Ask me anytime you need medication advice, batch cost checks, or pricing calculations.";
        }

        // 3. Specific Production Batch Lookup (e.g. PRD-2026-01, PRD-2026-02)
        foreach ($prodBatches as $pb) {
            $codeLower = strtolower($pb['batch_code']);
            if (str_contains($q, $codeLower) || (str_contains($q, 'batch 1') && $pb['id'] == 1) || (str_contains($q, 'batch 2') && $pb['id'] == 2)) {
                $m = $pb['metrics'];
                if (preg_match('/(?:make|profit|target|earn).*?(?:₦|ngn|\b)([0-9,]{4,})/i', $question, $pm)) {
                    $desiredProfit = (float) str_replace(',', '', $pm[1]);
                    $inHouse = max(1, (int) $m['current_in_house_birds']);
                    $neededTotalRevenue = (float) $m['total_accumulated_cost'] + $desiredProfit;
                    $neededFromRemaining = max(0.0, $neededTotalRevenue - (float) $m['realized_revenue']);
                    $reqPrice = round($neededFromRemaining / $inHouse, 2);

                    return "To make **₦" . number_format($desiredProfit, 2) . " net profit** on **{$pb['batch_code']}** ({$pb['flock_type']}):\n\n"
                        . "- Your total cost so far is **₦" . number_format($m['total_accumulated_cost'], 2) . "**, so you need **₦" . number_format($neededTotalRevenue, 2) . "** in total revenue.\n"
                        . "- You've already made **₦" . number_format($m['realized_revenue'], 2) . "** from selling **{$m['total_birds_sold']} birds**.\n"
                        . "- Across your remaining **" . number_format($inHouse) . " birds in house**, you should sell each bird at **₦" . number_format($reqPrice, 2) . " / bird**.";
                }

                return "Here is the live status for **{$pb['batch_code']}** ({$pb['flock_type']} — Day {$pb['age_days']}, Week {$pb['age_weeks']}):\n\n"
                    . "- **Flock Count:** **" . number_format($m['current_in_house_birds']) . " birds currently in house** (Started with " . number_format($m['initial_birds']) . ", lost {$m['total_mortality']} to mortality, sold {$m['total_birds_sold']}).\n"
                    . "- **Total Money Spent:** **₦" . number_format($m['total_accumulated_cost'], 2) . "** (Chick cost: ₦" . number_format($m['incubation_cost'], 0) . ", House rent: ₦" . number_format($m['house_rent'], 0) . ", Feed: ₦" . number_format($m['total_feed_cost'], 0) . ", Meds: ₦" . number_format($m['total_med_cost'], 0) . ", Labor/Sanit: ₦" . number_format($m['total_labor_cost'] + $m['total_sanitation_cost'], 0) . ").\n"
                    . "- **Exact Breakeven Cost:** **₦" . number_format($m['cost_per_bird'], 2) . " per bird** (includes +₦" . number_format($m['mortality_absorbed_per_bird'], 2) . " absorbed from mortalities).\n"
                    . "- **Recommended Selling Price ({$m['target_margin_pct']}% margin):** **₦" . number_format($m['recommended_selling_price'], 2) . " per bird** (+₦" . number_format($m['profit_per_bird'], 2) . " profit/bird → **₦" . number_format($m['projected_net_profit'], 2) . "** projected net profit).\n"
                    . "- **Feed Advisory:** {$pb['feed_alert']['title']}.";
            }
        }

        // 4. Specific Incubation Batch Lookup (e.g. INC-2026-01, INC-2026-02, INC-2026-03)
        foreach ($incBatches as $ib) {
            $codeLower = strtolower($ib['batch_code']);
            if (str_contains($q, $codeLower)) {
                $m = $ib['metrics'];
                $reply = "Here is the hatchery breakdown for **{$ib['batch_code']}** ({$ib['parent_flock_info']} — Status: `{$ib['status']}`):\n\n"
                    . "- **Eggs Loaded:** " . number_format((int) $ib['eggs_set']) . " eggs on {$ib['start_date']} (Egg cost: **₦" . number_format((float) $ib['egg_cost'], 2) . "**).\n"
                    . "- **Gas & Repairs:** **₦" . number_format($m['gas_cost'] + $m['repair_cost'], 2) . "** (" . number_format($m['gas_weight_kg'], 1) . " kg gas used) → Total Batch Cost: **₦" . number_format($m['total_incubation_cost'], 2) . "**.\n"
                    . "- **Candling Removals:** {$m['total_removed']} eggs removed ({$m['infertiles']} infertile, {$m['early_deaths']} early deaths, {$m['cracked']} cracked, {$m['candling_dis']} dead-in-shell) → **Fertility Rate: {$m['fertility_rate']}%**.";

                if ($ib['breakout']) {
                    $reply .= "\n- **Hatch Outcome:** **" . number_format($m['live_chicks']) . " Grade-A Live Chicks** (**{$m['hatchability_rate']}% hatchability**) at an exact **Initial Cost of ₦" . number_format($m['cost_per_chick'], 2) . " per chick**.";
                }
                return $reply;
            }
        }

        // 5. "What-if" Mortality Simulation
        if (preg_match('/(?:what\s*if|if|suppose)\s+(\d+)\s*(?:more\s*)?(?:birds?|chickens?|chicks?)\s*(?:die|died|lost|mortality)/i', $question, $mm)) {
            $extraDead = (int) $mm[1];
            if ($primaryBatch) {
                $m = $primaryBatch['metrics'];
                $currentSurviving = (int) $m['surviving_biological_birds'];
                $newSurviving = max(1, $currentSurviving - $extraDead);
                $totalCost = (float) $m['total_accumulated_cost'];
                $oldCostPerBird = (float) $m['cost_per_bird'];
                $newCostPerBird = round($totalCost / $newSurviving, 2);
                $diff = round($newCostPerBird - $oldCostPerBird, 2);
                $newRecPrice = round($newCostPerBird * (1 + ($m['target_margin_pct'] / 100)), 2);

                return "If **{$extraDead} more birds die** in **{$primaryBatch['batch_code']}**:\n\n"
                    . "- Surviving birds drop from **{$currentSurviving}** to **{$newSurviving} birds**.\n"
                    . "- Your **Breakeven Cost per Bird** rises by **+₦" . number_format($diff, 2) . "** (from **₦" . number_format($oldCostPerBird, 2) . "** to **₦" . number_format($newCostPerBird, 2) . " / bird**).\n"
                    . "- To maintain your **{$m['target_margin_pct']}% profit margin**, your recommended selling price adjusts to **₦" . number_format($newRecPrice, 2) . " / bird**.";
            }
        }

        // 6. Custom Profit Target in Naira
        if (preg_match('/(?:make|profit|target|earn).*?(?:₦|ngn|\b)([0-9,]{4,})/i', $question, $m)) {
            $desiredProfit = (float) str_replace(',', '', $m[1]);
            if ($primaryBatch) {
                $inHouse = max(1, (int) $primaryBatch['metrics']['current_in_house_birds']);
                $totalCost = (float) $primaryBatch['metrics']['total_accumulated_cost'];
                $realizedRev = (float) $primaryBatch['metrics']['realized_revenue'];
                $neededTotalRevenue = $totalCost + $desiredProfit;
                $neededFromRemaining = max(0.0, $neededTotalRevenue - $realizedRev);
                $reqPriceRemaining = round($neededFromRemaining / $inHouse, 2);
                $effectiveMarginPct = round((($neededTotalRevenue / $totalCost) - 1) * 100, 1);

                return "To make **₦" . number_format($desiredProfit, 2) . " net profit** on **{$primaryBatch['batch_code']}** ({$primaryBatch['flock_type']}):\n\n"
                    . "- **Total Batch Cost So Far:** ₦" . number_format($totalCost, 2) . "\n"
                    . "- **Total Revenue Needed:** ₦" . number_format($neededTotalRevenue, 2) . " (**{$effectiveMarginPct}%** margin)\n"
                    . "- **Already Collected from Sales:** ₦" . number_format($realizedRev, 2) . " ({$primaryBatch['metrics']['total_birds_sold']} birds sold)\n"
                    . "- **Required Selling Price for Remaining " . number_format($inHouse) . " Birds:** **₦" . number_format($reqPriceRemaining, 2) . " per bird**";
            }
        }

        // 7. Incubation / Hatchery questions
        if (preg_match('/(incubat|hatch|candl|fertil|\bchicks?\b|\beggs?\b|infertil|pipped|breakout)/i', $q)) {
            $lines = ["Here is how your **Incubation & Hatchery** batches are performing:\n"];
            foreach ($incBatches as $ib) {
                $m = $ib['metrics'];
                if ($ib['breakout']) {
                    $lines[] = "- **{$ib['batch_code']}** ({$ib['status']}): Set **" . number_format((int) $ib['eggs_set']) . " eggs**, achieved **{$m['fertility_rate']}% fertility** and **{$m['hatchability_rate']}% hatchability** (**" . number_format($m['live_chicks']) . " Grade-A chicks** @ **₦" . number_format($m['cost_per_chick'], 2) . " / chick**).";
                } else {
                    $lines[] = "- **{$ib['batch_code']}** (Active — Day {$ib['incubation_day']}): **" . number_format($m['viable_eggs_remaining']) . " viable eggs** remaining out of " . number_format((int) $ib['eggs_set']) . " set (**{$m['fertility_rate']}% fertility** so far).";
                }
            }
            return implode("\n", $lines);
        }

        // 8. Feed & Inventory Stock questions
        if (preg_match('/(invent|feed|starter|grower|layer|finisher|gram|ml|stock|bag)/i', $q)) {
            $lines = ["You have **₦" . number_format($summary['total_inventory_value'], 2) . "** worth of feed and medication in stock (**" . number_format($summary['total_feed_kg_remaining'], 1) . " kg** of feed):\n"];
            foreach ($invItems as $item) {
                $lines[] = "- **{$item['item_name']}** (`" . strtoupper($item['category']) . "`): **" . number_format((float) $item['total_quantity_g_or_ml']) . " {$item['unit_label']}** left ({$item['remaining_bulk']} {$item['bulk_unit_label']}) at **₦" . number_format((float) $item['unit_cost_per_g_or_ml'], 4) . " / {$item['unit_label']}**.";
            }
            return implode("\n", $lines);
        }

        // 9. Cost per Bird, Pricing, Profit, or Mortality questions
        if (preg_match('/(cost|price|pricing|profit|margin|sell|sale|revenue|breakeven|bird|mortal|dead|loss)/i', $q)) {
            $lines = ["Here is the live **Breakeven Cost per Bird** and **Recommended Selling Price** for your flocks:\n"];
            foreach ($prodBatches as $pb) {
                $m = $pb['metrics'];
                $lines[] = "- **{$pb['batch_code']}** ({$pb['flock_type']} — " . number_format($m['current_in_house_birds']) . " birds in house, {$m['total_mortality']} mortalities):\n"
                    . "  • Breakeven Cost: **₦" . number_format($m['cost_per_bird'], 2) . " / bird** (includes +₦" . number_format($m['mortality_absorbed_per_bird'], 2) . " mortality absorption)\n"
                    . "  • Recommended Selling Price (@ {$m['target_margin_pct']}% margin): **₦" . number_format($m['recommended_selling_price'], 2) . " / bird** (+₦" . number_format($m['profit_per_bird'], 2) . " profit/bird)\n"
                    . "  • Projected Net Profit: **₦" . number_format($m['projected_net_profit'], 2) . "**";
            }
            return implode("\n", $lines);
        }

        // 10. Turkey Management & Clinical Protocols (Dr. Kosiella's "From Poult to Profit")
        if (preg_match('/(turkey|poult|tom|hen|blackhead|histomon|histomonas|heterakis)/i', $q)) {
            $lines = ["### Dr. Kosiella's Turkey Production & Clinical Protocol\n"];
            $lines[] = "- **Crude Protein (CP) Requirement:** Turkey poults (Weeks 1–4) require **28% Crude Protein (CP)**. *Never feed turkey poults standard broiler starter (21–23% CP)*, as this causes irreversible stunting, poor feathering, and high mortality.";
            $lines[] = "- **Brooding Temperature:** Week 1 requires **35°C–37°C** under hover (higher than chickens' 33°C–35°C). Reduce by 3°C weekly until reaching 21°C–24°C at Week 5–6.";
            $lines[] = "- **Blackhead Disease (*Histomoniasis meleagridis*):** Transmitted by the cecal worm (*Heterakis gallinarum*) carried asymptomatically by chickens. **Golden Rule:** NEVER house turkeys with chickens or on ground that housed chickens in the last 3–4 years. Watch for sulfur-yellow watery droppings and liver bullseye necrosis.";
            $lines[] = "- **Water Vaccination Protocol:** Turkeys consume large water volumes; administer Newcastle Lasota and Gumboro using chlorine-free water neutralized with **20g skimmed milk powder per 10 Liters**.";
            return implode("\n", $lines);
        }

        // 11. Live Vaccination Protocols & 24-Hour Preparation Rules
        if (preg_match('/(vaccin|gumboro|lasota|marek|fowl\s*pox|deworm|schedule|calendar|water\s*prep|skim\s*milk|chlorin)/i', $q)) {
            $lines = ["### Dr. Kosiella's Live Vaccination Protocols & Schedule\n"];
            $lines[] = "- **24-Hour Antibiotic Rule:** Stop ALL antibiotics, anticoccidials, and acidifiers in drinking water **24 hours BEFORE and 24 hours AFTER** administering any live vaccine (e.g. Gumboro or Lasota). Antibiotics weaken the live viral titer.";
            $lines[] = "- **Water Preparation:** NEVER use chlorinated or treated tap water. Live vaccine viruses are rapidly destroyed by chlorine. Neutralize clean well or borehole water with **20g skimmed milk powder per 10 Liters of water (2g/L)** 20 minutes before mixing the vaccine.";
            $lines[] = "- **Thirst Period (Water Withholding):** Withdraw water for **1.5 to 2 hours** prior to vaccination so all birds drink immediately and consume the full dosage within 1.5 to 2 hours.";
            $lines[] = "- **Broiler Core Vaccination Schedule:**";
            $lines[] = "  • **Day 1:** Marek's (Hatchery SQ) + Infectious Bronchitis / ND B1 (Eye drop)";
            $lines[] = "  • **Day 7–8:** 1st Gumboro (IBD Intermediate Plus) via drinking water";
            $lines[] = "  • **Day 10–12:** Newcastle Disease (ND Lasota) via drinking water";
            $lines[] = "  • **Day 14–16:** 2nd Gumboro (IBD Booster) via drinking water";
            $lines[] = "  • **Day 21–24:** 2nd Newcastle Lasota (Booster) via drinking water";
            $lines[] = "  • **Day 28–30:** Fowl Pox via wing-web stab or Infectious Coryza vaccine";
            return implode("\n", $lines);
        }

        // 12. Brooding Temperature Guidelines (Day & Week by Week)
        if (preg_match('/(brood|heat|temperature|temp|charcoal|gas|heater|lamp|cold|chilling|hover)/i', $q)) {
            $lines = ["### Precision Brooding Temperature Protocol (Dr. Kosiella Guidelines)\n"];
            $lines[] = "- **Broiler Chicks:**";
            $lines[] = "  • Days 1–3: **33°C – 35°C** (Litter level: 32°C)";
            $lines[] = "  • Days 4–7: **31°C – 32°C**";
            $lines[] = "  • Week 2 (Days 8–14): **29°C – 31°C**";
            $lines[] = "  • Week 3 (Days 15–21): **26°C – 28°C**";
            $lines[] = "  • Week 4+ (Days 22+): **21°C – 24°C** (Ambient)";
            $lines[] = "- **Turkey Poults:**";
            $lines[] = "  • Week 1: **35°C – 37°C** under hover (critical due to low subcutaneous fat)";
            $lines[] = "  • Reduce by **3°C each week** until reaching 21°C–24°C at Week 5–6.";
            $lines[] = "- **Chicks Behavior Check:** If chicks huddle tightly under heater = TOO COLD. If spread out panting against walls = TOO HOT. If evenly scattered chirping softly = PERFECT COMFORT.";
            return implode("\n", $lines);
        }

        // 13. Rainy Season Special Management Protocols
        if (preg_match('/(rain|rainy|damp|wet|humidity|ammonia|aflatoxin|fungus|mould|curtain)/i', $q)) {
            $lines = ["### Dr. Kosiella's Rainy Season Poultry Management Protocols\n"];
            $lines[] = "- **Coccidiosis & Damp Litter Control:** Relative humidity exceeds 80% during rainy seasons. When litter moisture passes 25%, *Eimeria* coccidia oocysts sporulate rapidly within 24–48h. Check bell drinkers twice daily, rake wet cake immediately, and top-dress with dry kiln-dried wood shavings.";
            $lines[] = "- **Ventilation vs. Rain Curtains:** When rolling down side curtains to block rain, **never seal the poultry house completely**. Keep top ridge vents open to exhaust caustic ammonia vapors; trapped ammonia burns the tracheal cilia and triggers severe CRD / Mycoplasma.";
            $lines[] = "- **Aflatoxin Prevention:** Mold (*Aspergillus flavus*) thrives in warm, humid feed bags. Store feed on raised wooden pallets at least **30 cm away from walls**. Stock a maximum of 10–14 days feed supply, and add mycotoxin binders to feed rations.";
            $lines[] = "- **Fowl Pox Mosquito Vectors:** Stagnant rain puddles multiply mosquitoes that transmit Fowl Pox virus through skin bites. Drain puddles within 20 meters of sheds and administer wing-web Fowl Pox vaccine at Week 4–6.";
            return implode("\n", $lines);
        }

        // 14. Active Reminders & Notifications Trigger
        if (preg_match('/(reminder|alert|notification|bell|due|warning|protocol|pending)/i', $q)) {
            $rems = $this->farm->getActiveReminders();
            if (empty($rems)) {
                return "All active flocks and pens are currently clear of critical vaccine or feeding reminders for today! Telemetry is 100% normal.";
            }
            $lines = ["### Active Farm & Clinical Reminders (" . count($rems) . " Dropped Alerts)\n"];
            foreach ($rems as $r) {
                $icon = $r['level'] === 'critical' ? '🚨' : ($r['level'] === 'warning' ? '⚠️' : 'ℹ️');
                $lines[] = "{$icon} **{$r['title']}** [Flock: `{$r['flock_code']}`]";
                $lines[] = "  • **Category:** {$r['category']} (Species: {$r['species']})";
                $lines[] = "  • **Purpose & Route:** {$r['purpose']} — *{$r['method']}*";
                if (!empty($r['pre_warning'])) {
                    $lines[] = "  • **Mandatory Precaution:** {$r['pre_warning']}";
                }
                $lines[] = "";
            }
            return implode("\n", $lines);
        }

        // 15. Learned Farm House Memory Recall
        $memories = array_reverse($snapshot['custom_farm_memories'] ?? []);
        if (preg_match('/(memory|learned|what do you know|farm rule|remembered)/i', $q)) {
            if (empty($memories)) {
                return "I haven't recorded any farm house memories yet. Log daily events or tell me a rule like *\"On our farm we use borehole water\"* and I will remember and apply it!";
            }
            $lines = ["### Learned Farm House Knowledge (" . count($memories) . " items)\n"];
            foreach (array_slice($memories, 0, 12) as $mem) {
                $cat = strtoupper((string) ($mem['category'] ?? 'rule'));
                $lines[] = "- **[{$cat}]** {$mem['fact']} *({$mem['learned_date']})*";
            }
            return implode("\n", $lines);
        }

        // Default concise, helpful reply
        $medNames = array_map(fn($m) => "**{$m['item_name']}**", $uploadedMeds);
        return "Here is your quick farm & veterinary pulse:\n"
            . "- **Active Flocks:** **" . number_format($summary['total_live_birds']) . " live birds** in house (`PRD-2026-01` @ **₦" . number_format($prodBatches[1]['metrics']['cost_per_bird'] ?? $prodBatches[0]['metrics']['cost_per_bird'], 2) . "/bird**, `PRD-2026-02` @ **₦" . number_format($prodBatches[0]['metrics']['cost_per_bird'], 2) . "/bird**).\n"
            . "- **Uploaded Medications Ready for Advice:** " . (!empty($medNames) ? implode(', ', $medNames) : 'None yet') . ".\n"
            . "- **Feed & Hatchery:** **" . number_format($summary['total_feed_kg_remaining'], 0) . " kg** of feed in stock and **" . number_format($summary['active_eggs_in_incubator']) . " viable eggs** incubating.\n\n"
            . "Ask me about any medication name, flock symptom (like coughing or diarrhea), or batch pricing!";
    }
}
