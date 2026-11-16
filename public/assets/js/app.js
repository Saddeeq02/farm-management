// Poultry Farm & Hatchery Management System — Interactive Calculators & AI Drawer Controller

function formatNaira(val, decimals = 2) {
    const num = Number(val) || 0;
    return '₦' + num.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

// Module 1: Toggle gas weight field when switching between gas and repair
function toggleGasWeightField(expenseType) {
    const wrapper = document.getElementById('gasWeightWrapper');
    if (!wrapper) return;
    wrapper.style.opacity = expenseType === 'gas' ? '1' : '0.4';
}

// Module 2: Live Unit-Cost Engine Preview (Cost per Gram / ml = Total Price / Base Units)
function recalculateUnitCostPreview() {
    const categoryEl = document.getElementById('invCategory');
    const qtyEl = document.getElementById('invQtyValue');
    const unitModeEl = document.getElementById('invUnitMode');
    const costEl = document.getElementById('invTotalCost');

    if (!qtyEl || !unitModeEl || !costEl) return;

    const category = categoryEl ? categoryEl.value : 'starter';
    const rawQty = Math.max(0.01, parseFloat(qtyEl.value) || 0);
    const mode = unitModeEl.value;
    const totalCost = Math.max(0, parseFloat(costEl.value) || 0);

    let baseUnits = rawQty;
    let baseUnitLabel = 'g';

    if (category !== 'medicine') {
        baseUnitLabel = 'g';
        baseUnits = (mode === 'g') ? rawQty : rawQty * 1000;
    } else {
        if (mode === 'kg') {
            baseUnitLabel = 'g';
            baseUnits = rawQty * 1000;
        } else if (mode === 'g') {
            baseUnitLabel = 'g';
            baseUnits = rawQty;
        } else if (mode === 'l') {
            baseUnitLabel = 'ml';
            baseUnits = rawQty * 1000;
        } else {
            baseUnitLabel = 'ml';
            baseUnits = rawQty;
        }
    }

    const unitCost = baseUnits > 0 ? (totalCost / baseUnits) : 0;

    const previewUnitsEl = document.getElementById('previewBaseUnits');
    const previewPriceEl = document.getElementById('previewTotalPrice');
    const previewUnitCostEl = document.getElementById('previewUnitCost');

    if (previewUnitsEl) {
        previewUnitsEl.textContent = baseUnits.toLocaleString('en-US') + ' ' + baseUnitLabel;
    }
    if (previewPriceEl) {
        previewPriceEl.textContent = formatNaira(totalCost, 2);
    }
    if (previewUnitCostEl) {
        previewUnitCostEl.textContent = formatNaira(unitCost, 4) + ' / ' + baseUnitLabel;
    }
}

// Module 3: Live Feed & Medication Daily Telemetry Cost Preview
function updateDailyTelemetryCostPreview() {
    const feedSelect = document.getElementById('prodFeedSelect');
    const feedGramsInput = document.getElementById('prodFeedGrams');
    const feedBadge = document.getElementById('liveFeedCostBadge');

    if (feedSelect && feedGramsInput && feedBadge) {
        const selectedOption = feedSelect.options[feedSelect.selectedIndex];
        const rate = parseFloat(selectedOption ? selectedOption.getAttribute('data-rate') : '0') || 0;
        const grams = parseFloat(feedGramsInput.value) || 0;
        const cost = grams * rate;
        feedBadge.textContent = cost > 0 ? ('Deducts ' + formatNaira(cost, 2)) : 'Auto ₦/g';
    }

    const medSelect = document.getElementById('prodMedSelect');
    const medDoseInput = document.getElementById('prodMedDose');
    const medBadge = document.getElementById('liveMedCostBadge');

    if (medSelect && medDoseInput && medBadge) {
        const selectedOption = medSelect.options[medSelect.selectedIndex];
        const rate = parseFloat(selectedOption ? selectedOption.getAttribute('data-rate') : '0') || 0;
        const dose = parseFloat(medDoseInput.value) || 0;
        const cost = dose * rate;
        medBadge.textContent = cost > 0 ? ('Deducts ' + formatNaira(cost, 2)) : 'Auto ₦/ml';
    }
}

// Module 4: Target Profit Margin Live Preview
function previewBatchMargin(batchId, costPerBird, marginPct) {
    const pct = parseFloat(marginPct) || 0;
    const recPrice = costPerBird * (1 + (pct / 100));
    const profitPerBird = recPrice - costPerBird;
    const el = document.getElementById('marginPreview_' + batchId);
    if (el) {
        el.textContent = 'Live Preview: ' + formatNaira(recPrice, 2) + ' / bird (+' + formatNaira(profitPerBird, 2) + ' profit/bird)';
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
    if (!sel || !priceInput) return;
    const opt = sel.options[sel.selectedIndex];
    const rec = opt ? opt.getAttribute('data-rec-price') : null;
    if (rec) {
        priceInput.value = parseFloat(rec).toFixed(2);
        updateSaleTotalPreview();
    }
}

function updateSaleTotalPreview() {
    const qtyEl = document.getElementById('saleBirdsQty');
    const priceEl = document.getElementById('saleUnitPrice');
    const previewEl = document.getElementById('saleTotalPreview');
    if (!qtyEl || !priceEl || !previewEl) return;
    const qty = parseInt(qtyEl.value, 10) || 0;
    const price = parseFloat(priceEl.value) || 0;
    previewEl.textContent = formatNaira(qty * price, 2);
}

// Module 5: Embedded Farm AI Slide-Over Drawer
function toggleAiDrawer(open) {
    const backdrop = document.getElementById('aiDrawerBackdrop');
    const panel = document.getElementById('aiDrawerPanel');
    if (!backdrop || !panel) return;

    if (open) {
        backdrop.classList.remove('hidden');
        panel.classList.remove('translate-x-full');
        const container = document.getElementById('aiDrawerMessages');
        if (container) container.scrollTop = container.scrollHeight;
        const input = document.getElementById('aiDrawerInput');
        if (input) setTimeout(() => input.focus(), 120);
    } else {
        backdrop.classList.add('hidden');
        panel.classList.add('translate-x-full');
    }
}

function askFarmAiQuick(questionText) {
    const input = document.getElementById('aiDrawerInput');
    if (input) {
        input.value = questionText;
        submitAiDrawerQuestion(new Event('submit'));
    }
}

async function submitAiDrawerQuestion(event) {
    if (event && event.preventDefault) event.preventDefault();
    const input = document.getElementById('aiDrawerInput');
    const container = document.getElementById('aiDrawerMessages');
    const btn = document.getElementById('aiDrawerSubmitBtn');
    if (!input || !container) return;

    const question = input.value.trim();
    if (!question) return;

    input.value = '';
    if (btn) btn.disabled = true;

    // Append user bubble
    const userDiv = document.createElement('div');
    userDiv.className = 'flex justify-end';
    userDiv.innerHTML = `<div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-emerald-600 text-white px-3.5 py-2.5 text-xs leading-relaxed shadow-sm"></div>`;
    userDiv.firstChild.textContent = question;
    container.appendChild(userDiv);

    // Append thinking indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'flex justify-start';
    loadingDiv.innerHTML = `<div class="rounded-2xl rounded-tl-sm bg-white border border-slate-200 px-4 py-2.5 text-xs text-slate-400">Analyzing live farm tables...</div>`;
    container.appendChild(loadingDiv);
    container.scrollTop = container.scrollHeight;

    try {
        const formData = new FormData();
        formData.append('action', 'ask_ai_ajax');
        formData.append('question', question);

        const response = await fetch('/?page=ai', {
            method: 'POST',
            body: formData,
        });
        const data = await response.json();
        loadingDiv.remove();

        const botDiv = document.createElement('div');
        botDiv.className = 'flex justify-start';
        botDiv.innerHTML = `
            <div class="max-w-[92%] rounded-2xl rounded-tl-sm bg-white border border-slate-200 px-4 py-3 text-xs text-slate-700 leading-relaxed shadow-sm space-y-1.5">
                <div class="flex items-center justify-between text-[10px] text-slate-400 pb-1 border-b border-slate-100">
                    <span class="font-semibold text-emerald-700">${data.engine || 'Farm & Vet AI'}</span>
                    <span>${data.timestamp || ''}</span>
                </div>
                <div class="space-y-1 ai-reply-body"></div>
            </div>
        `;
        botDiv.querySelector('.ai-reply-body').innerHTML = data.answer_html || data.answer || 'Completed analysis.';
        container.appendChild(botDiv);
        container.scrollTop = container.scrollHeight;
    } catch (err) {
        loadingDiv.remove();
    } finally {
        if (btn) btn.disabled = false;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    recalculateUnitCostPreview();
    updateDailyTelemetryCostPreview();
    const aiPageMsg = document.getElementById('aiPageMessages');
    if (aiPageMsg) {
        aiPageMsg.scrollTop = aiPageMsg.scrollHeight;
    }
});

