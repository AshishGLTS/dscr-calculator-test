<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: A real-time Debt Service Coverage Ratio (DSCR) calculator for real estate investors.
 * Version: 1.2
 * Author: GLTS
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function dscr_calc_shortcode() {
    ob_start();
    ?>
    <div id="dscr-calculator-app" class="dscr-wrapper">
        <style>
            :root {
                --green: #0b6e3d;
                --dark: #2f3b4f;
                --dark-2: #1f2937;
                --gray: #f4f5f7;
                --text: #1f2937;
                --muted: #6b7280;
                --gold: #c79a3b;
                --card: #3a465a;
            }

            .dscr-wrapper {
                max-width: 1200px;
                margin: 40px auto;
                color: var(--text);
                line-height: 1.5;
            }

            .dscr-container {
                display: grid;
                grid-template-columns: 1.2fr 1fr;
                gap: 32px;
                align-items: flex-start;
            }

            .dscr-container h1 { font-size: 32px; margin-bottom: 8px; color: inherit; }
            .dscr-container .subtitle { color: var(--muted); margin-bottom: 32px; }

            .dscr-field-group { margin-bottom: 24px; }
            .dscr-field-group label { display: block; font-weight: 600; margin-bottom: 8px; }

            .dscr-control-row { display: flex; gap: 14px; }

            .dscr-range-wrapper {
                flex: 1;
                height: 40px;
                background: #f3f4f6;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                position: relative;
                overflow: hidden;
            }

            .dscr-range-fill {
                height: 100%;
                background: #065f46;
                width: 0%;
                pointer-events: none;
            }

            .dscr-range-handle-visual {
                position: absolute;
                top: 50%;
                left: 0%;
                transform: translate(-50%, -50%);
                height: 24px;
                width: 32px;
                background: #0b6e3d;
                border-radius: 6px;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 3px;
                pointer-events: none;
                z-index: 2;
                transition: left 0.1s ease-out;
            }

            .handle-line { width: 3px; height: 14px; background: #064e3b; border-radius: 2px; }

            .dscr-range-input {
                position: absolute;
                inset: 0;
                opacity: 0;
                cursor: pointer;
                width: 100%;
                height: 100%;
                z-index: 3;
                margin: 0;
            }

            .dscr-num-box {
                width: 140px;
                display: flex;
                align-items: center;
                background: #f1f1f1;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                height: 44px;
                overflow: hidden;
            }

            .dscr-num-box .prefix {
                background: #e5e7eb;
                color: #111827;
                font-weight: 700;
                padding: 0 10px;
                height: 100%;
                display: flex;
                align-items: center;
                border-right: 1px solid #d1d5db;
                font-size: 12px;
                min-width: 50px;
                justify-content: center;
            }

            .dscr-num-box input {
                flex: 1;
                border: none !important;
                background: transparent !important;
                padding: 0 12px !important;
                text-align: right;
                font-weight: 700;
                font-size: 15px;
                color: #111827;
                outline: none;
                box-shadow: none !important;
                width: 100%;
            }

            /* RESULTS PANEL */
            .dscr-results {
                background: var(--dark);
                border-radius: 20px;
                padding: 28px;
                color: #fff;
                position: sticky;
                top: 20px;
            }

            .dscr-results h2 { text-align: center; margin-bottom: 24px; color: #fff; border:none; margin-top:0;}

            .result-block { margin-bottom: 20px; }
            .result-label { font-size: 14px; color: #cbd5e1; margin-bottom: 6px; }
            .result-value {
                background: var(--dark-2);
                border-radius: 10px;
                padding: 16px;
                text-align: center;
                font-size: 28px;
                font-weight: 700;
            }

            .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
            .payment-card { background: var(--card); border-radius: 12px; padding: 16px; min-height: 100px; }
            .payment-card .amount { font-size: 20px; font-weight: 700; }
            .payment-card .desc { font-size: 13px; color: #cbd5e1; margin-top: 6px; }

            .payment-info { margin-top: 8px; font-size: 11px; color: #9ca3af; display: flex; align-items: center; gap: 4px; }
            .payment-info::before { content: "ℹ"; font-size: 12px; }

            .cta-group { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; }
            .cta {
                background: var(--gold);
                color: #fff;
                border: none;
                border-radius: 12px;
                padding: 14px 12px;
                font-size: 16px;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s;
            }
            .cta:disabled { opacity: 0.5; cursor: not-allowed; }
            .cta.secondary { background: #374151; }

            /* Checkbox Styling */
            .custom-check-row {
                margin-top: 24px;
                display: flex;
                gap: 12px;
                align-items: flex-start;
                cursor: pointer;
            }
            #checkIcon {
                background: #16a34a;
                color: #fff;
                border-radius: 6px;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                flex-shrink: 0;
                transition: background 0.2s;
            }

            @media (max-width: 900px) {
                .dscr-container { grid-template-columns: 1fr; }
                .dscr-results { position: static; }
            }
        </style>

        <div class="dscr-container">
            <div class="dscr-inputs">
                <h1>DSCR Rental Calculator</h1>
                <p class="subtitle">Enter your property details below to see your ratio.</p>

                <!-- Fields -->
                <?php
                $fields = [
                    ['id' => 'price', 'label' => 'Purchase Price', 'prefix' => '$', 'min' => 50000, 'max' => 2000000, 'step' => 5000, 'val' => 300000],
                    ['id' => 'down', 'label' => 'Down Payment', 'prefix' => '$', 'min' => 0, 'max' => 1000000, 'step' => 1000, 'val' => 60000],
                    ['id' => 'rate', 'label' => 'Interest Rate (%)', 'prefix' => '%', 'min' => 1, 'max' => 15, 'step' => 0.1, 'val' => 7.5],
                    ['id' => 'term', 'label' => 'Term Years', 'prefix' => 'Yrs', 'min' => 5, 'max' => 40, 'step' => 1, 'val' => 30],
                    ['id' => 'taxes', 'label' => 'Annual Taxes', 'prefix' => '$', 'min' => 0, 'max' => 20000, 'step' => 100, 'val' => 2400],
                    ['id' => 'insurance', 'label' => 'Annual Insurance', 'prefix' => '$', 'min' => 0, 'max' => 10000, 'step' => 50, 'val' => 1200],
                    ['id' => 'hoa', 'label' => 'Annual HOA', 'prefix' => '$', 'min' => 0, 'max' => 12000, 'step' => 100, 'val' => 0],
                    ['id' => 'rent', 'label' => 'Monthly Gross Rent', 'prefix' => '$', 'min' => 0, 'max' => 15000, 'step' => 100, 'val' => 3500],
                ];

                foreach ($fields as $f): ?>
                <div class="dscr-field-group" data-id="<?php echo $f['id']; ?>">
                    <label><?php echo $f['label']; ?></label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="<?php echo $f['min']; ?>" 
                                   max="<?php echo $f['max']; ?>" 
                                   step="<?php echo $f['step']; ?>" 
                                   value="<?php echo $f['val']; ?>" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix"><?php echo $f['prefix']; ?></span>
                            <input type="number" step="<?php echo $f['step']; ?>" value="<?php echo $f['val']; ?>" />
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- RESULTS -->
            <div class="dscr-results">
                <h2>Your Results</h2>

                <div class="result-block">
                    <div class="result-label">DSCR (Debt Service Coverage Ratio)</div>
                    <div class="result-value" id="val-dscr">0.00</div>
                </div>

                <div class="result-block">
                    <div class="result-label">Loan Amount</div>
                    <div class="result-value" id="val-loan">$0</div>
                </div>

                <div class="result-label" style="margin-top:24px;">Monthly Breakdown</div>

                <div class="payment-grid">
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-pi">$0.00</div>
                            <div class="desc">Principal &amp; Interest</div>
                        </div>
                        <div class="payment-info">Based on term and rate</div>
                    </div>

                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-pitia">$0.00</div>
                            <div class="desc">Full PITIA</div>
                        </div>
                        <div class="payment-info">Includes T, I, and HOA</div>
                    </div>
                </div>

                <div class="custom-check-row" id="checkWrapper">
                    <input type="checkbox" id="readyCheck" checked style="display:none;" />
                    <div id="checkIcon">✓</div>
                    <div>
                        <strong>Looks good.</strong><br />
                        <span style="font-size: 13px; color: #cbd5e1;">Ready to proceed with your application?</span>
                    </div>
                </div>

                <div class="cta-group">
                    <button class="cta secondary">Download PDF</button>
                    <button class="cta" id="applyBtn">Apply Now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const app = document.getElementById('dscr-calculator-app');
        const groups = app.querySelectorAll('.dscr-field-group');
        const vals = {
            dscr: app.querySelector('#val-dscr'),
            loan: app.querySelector('#val-loan'),
            pi: app.querySelector('#val-pi'),
            pitia: app.querySelector('#val-pitia')
        };
        const applyBtn = app.querySelector('#applyBtn');
        const readyCheck = app.querySelector('#readyCheck');
        const checkIcon = app.querySelector('#checkIcon');
        const checkWrapper = app.querySelector('#checkWrapper');

        function formatCurrency(num) {
            return '$' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function updateUI(groupId, value, source) {
            const group = app.querySelector(`.dscr-field-group[data-id="${groupId}"]`);
            const range = group.querySelector('.dscr-range-input');
            const number = group.querySelector('input[type="number"]');
            const fill = group.querySelector('.dscr-range-fill');
            const handle = group.querySelector('.dscr-range-handle-visual');

            // Sync the other input
            if (source === 'range') {
                number.value = value;
            } else if (source === 'number') {
                range.value = value;
            }

            // Calculate percentage for visual fill and handle
            const min = parseFloat(range.min);
            const max = parseFloat(range.max);
            const clampedVal = Math.min(Math.max(parseFloat(value) || 0, min), max);
            const percent = ((clampedVal - min) / (max - min)) * 100;
            
            fill.style.width = percent + '%';
            handle.style.left = percent + '%';

            calculate();
        }

        function calculate() {
            const getVal = (id) => parseFloat(app.querySelector(`[data-id="${id}"] input[type="number"]`).value) || 0;
            
            const price = getVal('price');
            const down = getVal('down');
            const rate = getVal('rate');
            const term = getVal('term') || 1;
            const taxes = getVal('taxes');
            const insurance = getVal('insurance');
            const hoa = getVal('hoa');
            const rent = getVal('rent');

            const loanAmount = Math.max(0, price - down);
            vals.loan.textContent = '$' + loanAmount.toLocaleString();

            let monthlyPI = 0;
            if (loanAmount > 0) {
                const monthlyRate = (rate / 100) / 12;
                const totalPayments = term * 12;
                if (monthlyRate === 0) {
                    monthlyPI = loanAmount / totalPayments;
                } else {
                    monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, totalPayments)) / (Math.pow(1 + monthlyRate, totalPayments) - 1);
                }
            }
            vals.pi.textContent = formatCurrency(monthlyPI);

            const pitia = monthlyPI + (taxes / 12) + (insurance / 12) + (hoa / 12);
            vals.pitia.textContent = formatCurrency(pitia);

            let dscr = pitia > 0 ? rent / pitia : 0;
            vals.dscr.textContent = dscr.toFixed(2);
            
            // DSCR Status Color
            if (dscr >= 1.25) vals.dscr.style.color = '#22c55e';
            else if (dscr >= 1.0) vals.dscr.style.color = '#eab308';
            else vals.dscr.style.color = '#ef4444';
        }

        groups.forEach(group => {
            const range = group.querySelector('.dscr-range-input');
            const number = group.querySelector('input[type="number"]');
            const id = group.dataset.id;

            range.addEventListener('input', (e) => updateUI(id, e.target.value, 'range'));
            number.addEventListener('input', (e) => updateUI(id, e.target.value, 'number'));
            
            // Initialize
            updateUI(id, range.value, 'range');
        });

        // Custom Checkbox Toggle
        function toggleCheck() {
            readyCheck.checked = !readyCheck.checked;
            if (readyCheck.checked) {
                checkIcon.textContent = '✓';
                checkIcon.style.background = '#16a34a';
                applyBtn.disabled = false;
            } else {
                checkIcon.textContent = '';
                checkIcon.style.background = '#9ca3af';
                applyBtn.disabled = true;
            }
        }

        checkWrapper.addEventListener('click', toggleCheck);
        
        // Final init
        calculate();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dscr_calculator', 'dscr_calc_shortcode');