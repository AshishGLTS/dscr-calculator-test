<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: A real-time Debt Service Coverage Ratio (DSCR) calculator for real estate investors.
 * Version: 1.1
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
                transition: width 0.1s ease-out;
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
                min-width: 45px;
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

            .dscr-results h2 { text-align: center; margin-bottom: 24px; color: #fff; border:none; }

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
                transition: opacity 0.2s;
            }
            .cta.secondary { background: #374151; }

            @media (max-width: 900px) {
                .dscr-container { grid-template-columns: 1fr; }
                .dscr-results { position: static; }
            }
        </style>

        <div class="dscr-container">
            <!-- INPUTS -->
            <div class="dscr-inputs">
                <h1>DSCR Rental Calculator</h1>
                <p class="subtitle">Calculate your Debt Service Coverage Ratio in real-time.</p>

                <!-- Purchase Price -->
                <div class="dscr-field-group" data-id="price">
                    <label>Purchase Price</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="50000" max="2000000" step="5000" value="300000" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="300000" /></div>
                    </div>
                </div>

                <!-- Down Payment -->
                <div class="dscr-field-group" data-id="down">
                    <label>Down Payment</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="0" max="1000000" step="1000" value="60000" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="60000" /></div>
                    </div>
                </div>

                <!-- Interest Rate -->
                <div class="dscr-field-group" data-id="rate">
                    <label>Interest Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="1" max="15" step="0.1" value="7.5" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">%</span><input type="number" step="0.1" value="7.5" /></div>
                    </div>
                </div>

                <!-- Term -->
                <div class="dscr-field-group" data-id="term">
                    <label>Term Years</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="5" max="40" step="1" value="30" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">Yrs</span><input type="number" value="30" /></div>
                    </div>
                </div>

                <!-- Taxes -->
                <div class="dscr-field-group" data-id="taxes">
                    <label>Annual Taxes</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="0" max="20000" step="100" value="2400" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="2400" /></div>
                    </div>
                </div>

                <!-- Insurance -->
                <div class="dscr-field-group" data-id="insurance">
                    <label>Annual Insurance</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="0" max="10000" step="50" value="1200" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="1200" /></div>
                    </div>
                </div>

                <!-- HOA -->
                <div class="dscr-field-group" data-id="hoa">
                    <label>Annual HOA</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="0" max="12000" step="100" value="0" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="0" /></div>
                    </div>
                </div>

                <!-- Rent -->
                <div class="dscr-field-group" data-id="rent">
                    <label>Monthly Gross Rent</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual"><div class="handle-line"></div><div class="handle-line"></div></div>
                            <input type="range" class="dscr-range-input" min="0" max="15000" step="100" value="3500" />
                        </div>
                        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="3500" /></div>
                    </div>
                </div>
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

                <label style="margin-top:24px; display:flex; gap:12px; align-items:flex-start; cursor:pointer;">
                    <input type="checkbox" id="readyCheck" checked style="accent-color: #16a34a; width: 20px; height: 20px;" />
                    <div>
                        <strong>Ready to proceed?</strong><br />
                        <span style="font-size: 13px; color: #cbd5e1;">Submit your details for a quote.</span>
                    </div>
                </label>

                <div class="cta-group">
                    <button class="cta secondary">Download PDF</button>
                    <button class="cta" id="applyBtn">Apply Now</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const groups = document.querySelectorAll('.dscr-field-group');
        const vals = {
            dscr: document.getElementById('val-dscr'),
            loan: document.getElementById('val-loan'),
            pi: document.getElementById('val-pi'),
            pitia: document.getElementById('val-pitia')
        };
        const applyBtn = document.getElementById('applyBtn');
        const readyCheck = document.getElementById('readyCheck');

        function formatCurrency(num) {
            return '$' + new Float64Array([num])[0].toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function updateUI(groupId, value) {
            const group = document.querySelector(`.dscr-field-group[data-id="${groupId}"]`);
            const range = group.querySelector('.dscr-range-input');
            const number = group.querySelector('input[type="number"]');
            const fill = group.querySelector('.dscr-range-fill');
            const handle = group.querySelector('.dscr-range-handle-visual');

            range.value = value;
            number.value = value;

            const percent = ((value - range.min) / (range.max - range.min)) * 100;
            fill.style.width = percent + '%';
            handle.style.left = percent + '%';

            calculate();
        }

        function calculate() {
            const price = parseFloat(document.querySelector('[data-id="price"] input[type="number"]').value) || 0;
            const down = parseFloat(document.querySelector('[data-id="down"] input[type="number"]').value) || 0;
            const rate = parseFloat(document.querySelector('[data-id="rate"] input[type="number"]').value) || 0;
            const term = parseFloat(document.querySelector('[data-id="term"] input[type="number"]').value) || 1;
            const taxes = parseFloat(document.querySelector('[data-id="taxes"] input[type="number"]').value) || 0;
            const insurance = parseFloat(document.querySelector('[data-id="insurance"] input[type="number"]').value) || 0;
            const hoa = parseFloat(document.querySelector('[data-id="hoa"] input[type="number"]').value) || 0;
            const rent = parseFloat(document.querySelector('[data-id="rent"] input[type="number"]').value) || 0;

            // 1. Loan Amount
            const loanAmount = Math.max(0, price - down);
            vals.loan.textContent = '$' + loanAmount.toLocaleString();

            // 2. Monthly P&I
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

            // 3. PITIA
            const monthlyTaxes = taxes / 12;
            const monthlyIns = insurance / 12;
            const monthlyHOA = hoa / 12;
            const pitia = monthlyPI + monthlyTaxes + monthlyIns + monthlyHOA;
            vals.pitia.textContent = formatCurrency(pitia);

            // 4. DSCR Calculation
            // Formula: DSCR = Monthly Gross Rent / PITIA
            let dscr = 0;
            if (pitia > 0) {
                dscr = rent / pitia;
            }
            vals.dscr.textContent = dscr.toFixed(2);
            
            // Visual Color Coding for DSCR
            if (dscr >= 1.2) vals.dscr.style.color = '#22c55e'; // Good green
            else if (dscr >= 1.0) vals.dscr.style.color = '#eab308'; // Warning yellow
            else vals.dscr.style.color = '#ef4444'; // Bad red
        }

        groups.forEach(group => {
            const range = group.querySelector('.dscr-range-input');
            const number = group.querySelector('input[type="number"]');
            const id = group.dataset.id;

            range.addEventListener('input', (e) => updateUI(id, e.target.value));
            number.addEventListener('input', (e) => updateUI(id, e.target.value));
            
            // Initialize positions
            updateUI(id, range.value);
        });

        readyCheck.addEventListener('change', () => {
            applyBtn.disabled = !readyCheck.checked;
            applyBtn.style.opacity = readyCheck.checked ? '1' : '0.5';
        });

        calculate();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dscr_calculator', 'dscr_calc_shortcode');