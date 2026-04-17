<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: A real-time Debt Service Coverage Ratio (DSCR) calculator for real estate investors.
 * Version: 1.3
 * Author: GLTS
 */

if (!defined('ABSPATH')) {
    exit;
}

function dscr_calc_shortcode()
{
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

            body {
                margin: 0;
                padding: 20px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background-color: #f9fafb;
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

            .dscr-container h1 {
                font-size: 32px;
                margin-bottom: 8px;
                color: inherit;
            }

            .dscr-container .subtitle {
                color: var(--muted);
                margin-bottom: 32px;
            }

            .dscr-field-group {
                margin-bottom: 24px;
            }

            .dscr-field-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
            }

            .dscr-control-row {
                display: flex;
                gap: 14px;
            }

            .slider-container {
                flex: 1;
                display: flex;
                align-items: center;
            }

            .slider-track {
                width: 100%;
                height: 48px;
                background-color: #ffffff;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                position: relative;
                padding: 2px;
                box-sizing: border-box;
                display: flex;
                align-items: center;
            }

            .slider-fill {
                height: calc(100% - 8px);
                background-color: #006b35;
                border-radius: 6px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                transition: width 0.3s ease;
                min-width: 48px;
            }

            .slider-thumb {
                width: 42px;
                height: calc(100% - 4px);
                background-color: #27a768;
                border-radius: 6px;
                margin-right: 2px;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: inset 0 0 4px rgba(0, 0, 0, 0.1);
                cursor: pointer;
                flex-shrink: 0;
            }

            .thumb-lines {
                display: flex;
                gap: 4px;
            }

            .thumb-lines span {
                width: 3px;
                height: 16px;
                background-color: #006b35;
                border-radius: 2px;
                opacity: 0.8;
            }

            .slider-track:hover .slider-fill {
                filter: brightness(1.05);
            }

            .dscr-range-input {
                position: absolute;
                inset: 0;
                opacity: 0;
                cursor: pointer;
                width: 100%;
                height: 100%;
                z-index: 3;
                margin: 0;
                padding: 0;
                -webkit-appearance: none;
                appearance: none;
            }

            .dscr-range-input::-webkit-slider-runnable-track {
                width: 100%;
                height: 100%;
                cursor: pointer;
            }

            .dscr-range-input::-webkit-slider-thumb {
                -webkit-appearance: none;
                appearance: none;
                width: 32px;
                height: 24px;
                cursor: pointer;
                margin-top: -12px;
            }

            .dscr-range-input::-moz-range-track {
                width: 100%;
                height: 100%;
                cursor: pointer;
            }

            .dscr-range-input::-moz-range-thumb {
                width: 32px;
                height: 24px;
                cursor: pointer;
                border: none;
                background: transparent;
            }

            .dscr-num-box {
                width: 180px;
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
                padding: 0 6px;
                height: 100%;
                display: flex;
                align-items: center;
                border-right: 1px solid #d1d5db;
                font-size: 12px;
                min-width: fit-content;
                justify-content: center;
                flex-shrink: 0;
            }

            .dscr-num-box input {
                flex: 1;
                border: none !important;
                background: transparent !important;
                padding: 0 8px !important;
                text-align: right;
                font-weight: 700;
                font-size: 14px;
                color: #111827;
                outline: none;
                box-shadow: none !important;
                width: 100%;
                min-width: 0;
            }

            /* RESULTS PANEL */
            .dscr-results {
                background: var(--dark);
                border-radius: 20px;
                padding: 28px;
                color: #fff;
                margin-top: 150px;
                position: sticky;
                top: 20px;
            }

            .dscr-results h2 {
                text-align: center;
                margin-bottom: 24px;
                color: #fff;
                border: none;
                margin-top: 0;
            }

            .result-block {
                margin-bottom: 20px;
            }

            .result-label {
                font-size: 14px;
                color: #cbd5e1;
                margin-bottom: 6px;
            }

            .result-value {
                background: var(--dark-2);
                border-radius: 10px;
                padding: 16px;
                text-align: center;
                font-size: 28px;
                font-weight: 700;
            }

            .payment-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
            }

            .payment-card {
                background: var(--card);
                border-radius: 12px;
                padding: 16px;
                min-height: 100px;
            }

            .payment-card .amount {
                font-size: 20px;
                font-weight: 700;
            }

            .payment-card .desc {
                font-size: 13px;
                color: #cbd5e1;
                margin-top: 6px;
            }

            .payment-info {
                margin-top: 8px;
                font-size: 11px;
                color: #9ca3af;
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .payment-info::before {
                content: "ℹ";
                font-size: 12px;
            }

            .cta-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                margin-top: 24px;
            }

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

            .cta:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .cta.secondary {
                background: #374151;
            }

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
                .dscr-container {
                    grid-template-columns: 1fr;
                }

                .dscr-results {
                    position: static;
                }
            }
        </style>

        <div class="dscr-container">
            <div class="dscr-inputs">
                <h1>DSCR Rental Calculator</h1>
                <p class="subtitle">Enter your property details below to see your ratio.</p>

                <!-- Fields -->
                <div class="dscr-field-group" data-id="price">
                    <label>Purchase Price/As-Is Value</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5000000" step="5000" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="5000" value="0" />
                        </div>
                    </div>
                </div>



                <div class="dscr-field-group" data-id="ltv">
                    <label>LTV (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="100" step="1" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="1" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rate">
                    <label>Interest Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="1" max="15" step="0.01" value="6.75" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="0.01" value="6.75" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="term">
                    <label>Loan Term</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="5" max="40" step="1" value="30" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">Yrs</span>
                            <input type="text" inputmode="decimal" data-step="1" value="30" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="origination">
                    <label>Origination Points</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5" step="0.25" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">pts</span>
                            <input type="text" inputmode="decimal" data-step="0.25" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="closing-fees">
                    <label>Loan Closing Fees</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="10000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rent">
                    <label>Monthly Gross Rent</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="50000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="vacancy">
                    <label>Vacancy Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20" step="0.5" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="text" inputmode="decimal" data-step="0.5" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="taxes">
                    <label>Property Taxes</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="50000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="insurance">
                    <label>Insurance</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="hoa">
                    <label>Monthly HOA</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="2000" step="10" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="10" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="repair">
                    <label>Annual Repair and Maintenance</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="5000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="utilities">
                    <label>Annual Utilities</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="10000" step="50" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="third-party">
                    <label>3rd Party Closing Cost</label>
                    <div class="dscr-control-row">
                        <div class="slider-container">
                            <div class="slider-track">
                                <div class="slider-fill" style="width: 0%;">
                                    <div class="slider-thumb">
                                        <div class="thumb-lines">
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                                <input type="range" class="dscr-range-input" min="0" max="20000" step="100" value="0" />
                            </div>
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="text" inputmode="decimal" data-step="100" value="0" />
                        </div>
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
                            <div class="desc">Monthly PITIA</div>
                        </div>
                        <div class="payment-info">Principal, Interest, Taxes, Insurance, HOA</div>
                    </div>
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-cashflow">$0.00</div>
                            <div class="desc">Net Monthly CashFlow</div>
                        </div>
                        <div class="payment-info">Net Monthly CashFlow</div>
                    </div>
                    <div class="payment-item">
                        <div class="payment-card">
                            <div class="amount" id="val-closing-cost">0.00%</div>
                            <div class="desc">ROI</div>
                        </div>
                        <div class="payment-info">ROI Divided by Purchase Price/Value</div>
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
                    <button class="cta secondary" id="downloadPdfBtn">Download PDF</button>
                    <button class="cta" id="applyBtn">Apply Now</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        var dscrPdfLogo = "<?php echo esc_js(plugin_dir_url(__FILE__) . 'logo.png'); ?>";
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const app = document.getElementById('dscr-calculator-app');
            const groups = app.querySelectorAll('.dscr-field-group');
            const vals = {
                dscr: app.querySelector('#val-dscr'),
                loan: app.querySelector('#val-loan'),
                pi: app.querySelector('#val-pi'),
                pitia: app.querySelector('#val-pitia'),
                cashflow: app.querySelector('#val-cashflow'),
                closingCost: app.querySelector('#val-closing-cost')
            };

            // Store all calculated values for PDF
            let calculatedValues = {};
            const applyBtn = app.querySelector('#applyBtn');
            const readyCheck = app.querySelector('#readyCheck');
            const checkIcon = app.querySelector('#checkIcon');
            const checkWrapper = app.querySelector('#checkWrapper');

            function formatCurrency(num) {
                return '$' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function formatNumberWithCommas(num) {
                if (num === '' || num === null || num === undefined) return '0';
                const n = parseFloat(String(num).replace(/,/g, ''));
                if (isNaN(n)) return '0';
                // Check if number has decimals
                const parts = String(n).split('.');
                const intPart = parseInt(parts[0], 10).toLocaleString('en-US');
                if (parts.length > 1) {
                    return intPart + '.' + parts[1];
                }
                return intPart;
            }

            function parseNumericValue(val) {
                return parseFloat(String(val).replace(/,/g, '')) || 0;
            }

            function updateUI(groupId, value, source) {
                const group = app.querySelector(`.dscr-field-group[data-id="${groupId}"]`);
                const range = group.querySelector('.dscr-range-input');
                const textInput = group.querySelector('input[type="text"]');
                const fill = group.querySelector('.slider-fill');

                // Sync the other input
                if (source === 'range') {
                    textInput.value = formatNumberWithCommas(value);
                } else if (source === 'number') {
                    const numVal = parseNumericValue(value);
                    const min = parseFloat(range.min);
                    const max = parseFloat(range.max);
                    const clampedNum = Math.max(min, Math.min(max, numVal));
                    range.value = clampedNum;
                    value = clampedNum;
                }

                // Calculate percentage for visual fill
                const min = parseFloat(range.min);
                const max = parseFloat(range.max);
                const clampedVal = Math.min(Math.max(parseFloat(value) || 0, min), max);

                // Calculate percentage (0 to 100)
                let percent = 0;
                if (max > min) {
                    percent = ((clampedVal - min) / (max - min)) * 100;
                }

                // Clamp percent to valid range (0-100)
                percent = Math.max(0, Math.min(100, percent));

                // Update fill width - the thumb is inside the fill, so it will be positioned correctly
                fill.style.width = percent + '%';

                calculate();
            }

            function calculate() {
                const getVal = (id) => parseNumericValue(app.querySelector(`[data-id="${id}"] input[type="text"]`).value);

                const price = getVal('price');
                const units = 1;
                const ltv = getVal('ltv');
                const rate = getVal('rate');
                const term = getVal('term') || 1;
                const origination = getVal('origination');
                const closingFees = getVal('closing-fees');
                const rent = getVal('rent');
                const vacancy = getVal('vacancy');
                const taxes = getVal('taxes');
                const insurance = getVal('insurance');
                const hoa = getVal('hoa');
                const repair = getVal('repair');
                const utilities = getVal('utilities');
                const thirdParty = getVal('third-party');

                // Calculate loan amount from purchase price and LTV
                // Formula: Loan Amount = Purchase Price * LTV
                const loanAmount = (price * ltv) / 100;
                vals.loan.textContent = '$' + loanAmount.toLocaleString();

                // Calculate origination fee (as percentage of loan amount)
                const originationFee = (loanAmount * origination) / 100;

                // Calculate monthly Principal & Interest
                // Using fixed 360 payments (30 years)
                let monthlyPI = 0;
                if (loanAmount > 0) {
                    const monthlyRate = (rate / 100) / 12;
                    const numberOfPayments = 360; // Fixed to 360 payments
                    if (monthlyRate === 0) {
                        monthlyPI = loanAmount / numberOfPayments;
                    } else {
                        monthlyPI = loanAmount * (monthlyRate * Math.pow(1 + monthlyRate, numberOfPayments)) / (Math.pow(1 + monthlyRate, numberOfPayments) - 1);
                    }
                }
                vals.pi.textContent = formatCurrency(monthlyPI);

                // Calculate monthly expenses
                const monthlyTaxes = taxes / 12;
                const monthlyInsurance = insurance / 12;
                const monthlyHOA = hoa; // Already monthly

                // Total monthly debt service (PITIA)
                const pitia = monthlyPI + monthlyTaxes + monthlyInsurance + monthlyHOA;
                vals.pitia.textContent = formatCurrency(pitia);

                // Calculate Down Payment
                const downPayment = price - loanAmount;

                // Calculate Annual Mortgage Payment = PITIA * 12
                const annualMortgagePayment = pitia * 12;

                // Calculate Annual HOA
                const annualHOA = hoa * 12;

                // Calculate Annual Repairs and Maintenance
                const annualRepair = repair * units;

                // Annual Rental Income = Gross Monthly Rental Income * 12
                const annualRentalIncome = rent * 12;

                // Vacancy Deduction = Annual Rental Income * vacancy Rate
                const vacancyRate = vacancy / 100;
                const vacancyDeduction = annualRentalIncome * vacancyRate;

                // Net Effective Rent = Annual Rental Income - Vacancy Deduction
                const netEffectiveRent = annualRentalIncome - vacancyDeduction;

                // Operating Expenses = (Taxes and Insurance + Annual HOA + Annual Repairs and Maint + Annual Utilities) + (Monthly Payment (P&I) * 12)
                const taxesAndInsurance = taxes + insurance;
                const operatingExpenses = (taxesAndInsurance + annualHOA + annualRepair + utilities) + (monthlyPI * 12);

                // Net Operating Income = Net Effective Rent - Operating Expenses
                const netOperatingIncome = netEffectiveRent - operatingExpenses;

                // Net Monthly Cashflow = Net Operating Income / 12
                const netMonthlyCashflow = netOperatingIncome / 12;
                vals.cashflow.textContent = formatCurrency(netMonthlyCashflow);

                // Calculate ROI = Net Operating Income / Purchase Price
                const roi = price > 0 ? (netOperatingIncome / price) * 100 : 0;
                vals.closingCost.textContent = roi.toFixed(2) + '%';

                // Calculate Total Closing Cost (used internally for cash needed to close and PDF)
                const totalClosingCost = originationFee + closingFees + thirdParty;

                // Calculate Cash Needed to Close
                const cashNeededToClose = downPayment + totalClosingCost;

                // Cap Rate = Net Operating Income / Purchase Price (in %)
                const capRate = price > 0 ? (netOperatingIncome / price) * 100 : 0;

                // Cash on Cash Return = Net Operating Income / Cash Needed to Close
                const cashOnCashReturn = cashNeededToClose > 0 ? (netOperatingIncome / cashNeededToClose) * 100 : 0;

                // DSCR = (Net Effective Rent / 12) / PITIA
                // This is monthly NOI divided by monthly debt service
                const monthlyNetEffectiveRent = netEffectiveRent / 12;
                let dscr = pitia > 0 ? monthlyNetEffectiveRent / pitia : 0;
                vals.dscr.textContent = dscr.toFixed(2);

                // DSCR Status Color
                if (dscr >= 1.25) vals.dscr.style.color = '#22c55e';
                else if (dscr >= 1.0) vals.dscr.style.color = '#eab308';
                else vals.dscr.style.color = '#ef4444';

                // Store all calculated values for PDF
                calculatedValues = {
                    pricePerUnit: price / units,
                    loanAmount: loanAmount,
                    downPayment: downPayment,
                    monthlyPI: monthlyPI,
                    pitia: pitia,
                    annualMortgagePayment: annualMortgagePayment,
                    originationFeeAmount: originationFee,
                    grossMonthlyRentalIncome: rent,
                    annualRentalIncome: annualRentalIncome,
                    vacancyDeduction: vacancyDeduction,
                    netEffectiveRent: netEffectiveRent,
                    taxesAndInsurance: taxesAndInsurance,
                    annualHOA: annualHOA,
                    annualRepair: annualRepair,
                    annualUtilities: utilities,
                    operatingExpenses: operatingExpenses,
                    netOperatingIncome: netOperatingIncome,
                    netMonthlyCashflow: netMonthlyCashflow,
                    capRate: capRate,
                    cashOnCashReturn: cashOnCashReturn,
                    dscr: dscr,
                    totalClosingCost: totalClosingCost,
                    cashNeededToClose: cashNeededToClose
                };
            }

            groups.forEach(group => {
                const range = group.querySelector('.dscr-range-input');
                const textInput = group.querySelector('input[type="text"]');
                const fill = group.querySelector('.slider-fill');
                const id = group.dataset.id;

                // Disable transition while dragging for instant response
                range.addEventListener('mousedown', () => {
                    fill.style.transition = 'none';
                });

                range.addEventListener('mouseup', () => {
                    fill.style.transition = 'width 0.3s ease';
                });

                range.addEventListener('touchstart', () => {
                    fill.style.transition = 'none';
                });

                range.addEventListener('touchend', () => {
                    fill.style.transition = 'width 0.3s ease';
                });

                range.addEventListener('input', (e) => updateUI(id, e.target.value, 'range'));
                textInput.addEventListener('input', (e) => {
                    updateUI(id, e.target.value, 'number');
                });
                textInput.addEventListener('blur', (e) => {
                    e.target.value = formatNumberWithCommas(e.target.value);
                });

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

            function formatCurrencyPDF(value) {
                return '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function buildPdfHtml() {
                const getInputVal = (id) => parseNumericValue(app.querySelector(`[data-id="${id}"] input[type="text"]`).value);
                const cv = calculatedValues;

                const logoSrc = (typeof dscrPdfLogo !== 'undefined' && dscrPdfLogo) ? dscrPdfLogo : 'logo.png';

                const inputPrice = getInputVal('price');
                const inputLTV = getInputVal('ltv');
                const inputRate = getInputVal('rate');
                const inputTerm = getInputVal('term') || 30;
                const inputOrigination = getInputVal('origination');
                const inputClosingFees = getInputVal('closing-fees');
                const inputRent = getInputVal('rent');
                const inputVacancy = getInputVal('vacancy');
                const inputTaxes = getInputVal('taxes');
                const inputInsurance = getInputVal('insurance');
                const inputHOA = getInputVal('hoa');
                const inputRepair = getInputVal('repair');
                const inputUtilities = getInputVal('utilities');
                const inputThirdParty = getInputVal('third-party');

                return `
<style>
  #pdf-report * { margin: 0; padding: 0; box-sizing: border-box; }
  #pdf-report { font-family: "Segoe UI", Arial, sans-serif; background: #fff; width: 1000px; color: #333; display: flex; flex-direction: column; min-height: 1415px; }
  #pdf-report .pdf-container { max-width: 1000px; margin: 0; background: #ffffff; padding: 0; flex: 1; }
  #pdf-report .pdf-header { background: #3c4a5d; height: 120px; position: relative; width: 100%; }
  #pdf-report .pdf-logo-box { position: absolute; bottom: 0; left: 0; background: #ffffff; width: 260px; height: 100px; border-radius: 0 14px 0 0; display: flex; align-items: center; justify-content: center; padding: 10px 15px; }
  #pdf-report .pdf-logo-box img { max-height: 100%; }
  #pdf-report .pdf-contact { position: absolute; top: 38px; right: 40px; color: #ffffff; font-size: 13px; text-align: right; line-height: 1.6; }
  #pdf-report .pdf-contact-row { display: flex; justify-content: flex-end; gap: 10px; }
  #pdf-report .pdf-label { font-weight: 600; }
  #pdf-report .pdf-title { position: absolute; bottom: -22px; left: 65%; transform: translateX(-50%); background: #1e7a52; color: white; padding: 10px 40px; border-radius: 8px; font-weight: bold; font-size: 18px; z-index: 10; white-space: nowrap; }
  #pdf-report .pdf-main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; padding: 10px 15px; margin-top: 30px; }
  #pdf-report .pdf-column { display: flex; flex-direction: column; gap: 20px; }
  #pdf-report .pdf-card { background: #fafafa; border: 1px solid #d9c7a3; padding: 14px 16px; display: flex; flex-direction: column; }
  #pdf-report .pdf-section-gap { height: 12px; }
  #pdf-report .pdf-card h3 { margin: 0 0 8px; font-size: 14px; border-bottom: 1px solid #d6c7b2; padding-bottom: 5px; color: #333; }
  #pdf-report .pdf-sub-title { font-weight: bold; margin: 6px 0 3px; font-size: 13px; }
  #pdf-report .pdf-row { display: flex; justify-content: space-between; font-size: 12px; margin: 4px 0; }
  #pdf-report .pdf-positive span:last-child { color: #1a8f3c; font-weight: bold; }
  #pdf-report .pdf-negative span:last-child { color: #b33939; }
  #pdf-report .pdf-divider { border-top: 1px solid #d6c7b2; margin: 0 0; }
  #pdf-report .pdf-footer { text-align: center; font-size: 14px; padding: 10px 15px; color: #777; font-style: italic; margin-top: auto; }
  #pdf-report .pdf-bar { display: block; height: 10px; background: #3c4a5d; }
</style>
<div id="pdf-report">
<div class="pdf-container">
  <div class="pdf-header">
    <div class="pdf-logo-box">
      <img src="${logoSrc}" alt="Express Capital Financing" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.outerHTML='<h2 style=\\'color:#1e7a52; margin:0; font-size: 18px;\\'>Express Capital Financing</h2>'" />
    </div>
    <div class="pdf-contact">
      <div class="pdf-contact-row">
        <span class="pdf-label">Call:</span>
        <span>(718) 285-0806</span>
      </div>
      <div class="pdf-contact-row">
        <span class="pdf-label">Email:</span>
        <span>info@expresscapitalfinancing.com</span>
      </div>
    </div>
    <div class="pdf-title">DSCR Calculator Report</div>
  </div>

  <div class="pdf-main-grid">
    <div class="pdf-column">
      <div class="pdf-card">
        <div class="pdf-sub-title">Property Info</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Property Address</span><span></span></div>
        <div class="pdf-row"><span>Purchase or Refinance?</span><span>Purchase</span></div>
        <div class="pdf-row"><span>Purchase Price</span><span>${formatCurrencyPDF(inputPrice)}</span></div>
        <div class="pdf-row"><span>Number of Units</span><span>1</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Loan Information</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>LTV</span><span>${inputLTV}%</span></div>
        <div class="pdf-row"><span>Interest Rate</span><span>${inputRate}%</span></div>
        <div class="pdf-row"><span>Amortization (years)</span><span>${inputTerm}</span></div>
        <div class="pdf-row"><span>Origination Points</span><span>${inputOrigination}%</span></div>
        <div class="pdf-row"><span>Loan Closing Fees</span><span>${formatCurrencyPDF(inputClosingFees)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Income</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Total Rent</span><span>${formatCurrencyPDF(inputRent)}</span></div>
        <div class="pdf-row"><span>Vacancy Rate</span><span>${inputVacancy}%</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Expenses</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Property Taxes</span><span>${formatCurrencyPDF(inputTaxes)}</span></div>
        <div class="pdf-row"><span>Insurance</span><span>${formatCurrencyPDF(inputInsurance)}</span></div>
        <div class="pdf-row"><span>Monthly HOA</span><span>${formatCurrencyPDF(inputHOA)}</span></div>
        <div class="pdf-row"><span>Annual Repair and Maint (Per Unit)</span><span>${formatCurrencyPDF(inputRepair)}</span></div>
        <div class="pdf-row"><span>Annual Utilities</span><span>${formatCurrencyPDF(inputUtilities)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-row"><span>3rd Party Closing Cost (Title, Insurance)</span><span>${formatCurrencyPDF(inputThirdParty)}</span></div>
      </div>
    </div>

    <div class="pdf-column">
      <div class="pdf-card">
        <h3>Basic Info</h3>
        <div class="pdf-row"><span>Price Per Unit</span><span>${formatCurrencyPDF(cv.pricePerUnit)}</span></div>
        <div class="pdf-row"><span>Loan Amount</span><span>${formatCurrencyPDF(cv.loanAmount)}</span></div>
        <div class="pdf-row"><span>Down Payment</span><span>${formatCurrencyPDF(cv.downPayment)}</span></div>
      </div>

      <div class="pdf-card">
        <h3>Loan Information</h3>
        <div class="pdf-row pdf-positive"><span>Monthly Payments (P&I)</span><span>${formatCurrencyPDF(cv.monthlyPI)}</span></div>
        <div class="pdf-row"><span>PITIA</span><span>${formatCurrencyPDF(cv.pitia)}</span></div>
        <div class="pdf-row"><span>Annual Mortgage Payment</span><span>${formatCurrencyPDF(cv.annualMortgagePayment)}</span></div>
        <div class="pdf-row"><span>Origination Fee Amount</span><span>${formatCurrencyPDF(cv.originationFeeAmount)}</span></div>

        <div class="pdf-section-gap"></div>

        <div class="pdf-sub-title">Property Income</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Gross Monthly Rental Income</span><span>${formatCurrencyPDF(cv.grossMonthlyRentalIncome)}</span></div>
        <div class="pdf-row"><span>Annual Rental Income</span><span>${formatCurrencyPDF(cv.annualRentalIncome)}</span></div>
        <div class="pdf-row"><span>Vacancy Deduction</span><span>${formatCurrencyPDF(cv.vacancyDeduction)}</span></div>
        <div class="pdf-row"><span>Net Effective Rent</span><span>${formatCurrencyPDF(cv.netEffectiveRent)}</span></div>

        <div class="pdf-sub-title">Property Expenses</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Tax and Insurance</span><span>${formatCurrencyPDF(cv.taxesAndInsurance)}</span></div>
        <div class="pdf-row"><span>Annual HOA</span><span>${formatCurrencyPDF(cv.annualHOA)}</span></div>
        <div class="pdf-row"><span>Annual Repair and Maint</span><span>${formatCurrencyPDF(cv.annualRepair)}</span></div>
        <div class="pdf-row"><span>Annual Utilities</span><span>${formatCurrencyPDF(cv.annualUtilities)}</span></div>

        <div class="pdf-sub-title">Overview</div>
        <div class="pdf-divider"></div>
        <div class="pdf-row"><span>Operating Expenses</span><span>${formatCurrencyPDF(cv.operatingExpenses)}</span></div>
        <div class="pdf-row pdf-negative"><span>Net Operating Income</span><span>${formatCurrencyPDF(cv.netOperatingIncome)}</span></div>
        <div class="pdf-row"><span>Net Monthly Cashflow</span><span>${formatCurrencyPDF(cv.netMonthlyCashflow)}</span></div>
        <div class="pdf-row"><span>Cap Rate</span><span>${cv.capRate.toFixed(1)}%</span></div>
        <div class="pdf-row"><span>Cash On Cash Return</span><span>${cv.cashOnCashReturn.toFixed(1)}%</span></div>
        <div class="pdf-row"><span>DSCR*</span><span>${cv.dscr.toFixed(2)}</span></div>

        <div class="pdf-section-gap"></div>
        <div class="pdf-row"><span>Total Closing Cost</span><span>${formatCurrencyPDF(cv.totalClosingCost)}</span></div>
        <div class="pdf-row"><span>Cash Needed to Close</span><span>${formatCurrencyPDF(cv.cashNeededToClose)}</span></div>
      </div>
    </div>
  </div>

</div>
<div class="pdf-footer">
  Disclaimer: This calculator provides estimates only. Consult professionals before making investment decisions.
</div>
<div class="pdf-bar"></div>
</div>`;
            }

            function downloadPDF() {
                if (!window.html2pdf) {
                    alert('PDF library not loaded. Please refresh the page and try again.');
                    return;
                }

                if (!calculatedValues || Object.keys(calculatedValues).length === 0) {
                    alert('Please calculate values first before downloading PDF.');
                    return;
                }

                try {
                    const htmlContent = buildPdfHtml();

                    const wrapper = document.createElement('div');
                    wrapper.style.position = 'absolute';
                    wrapper.style.left = '0';
                    wrapper.style.top = '0';
                    wrapper.style.width = '1000px';
                    wrapper.style.zIndex = '-9999';
                    wrapper.style.overflow = 'hidden';
                    wrapper.style.background = '#ffffff';
                    wrapper.innerHTML = htmlContent;
                    document.body.appendChild(wrapper);

                    const pdfContent = wrapper.querySelector('#pdf-report');

                    const opt = {
                        margin: 0,
                        filename: 'DSCR_Calculator_Report.pdf',
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            letterRendering: true,
                            width: 1000,
                            scrollX: 0,
                            scrollY: 0
                        },
                        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                    };

                    html2pdf().set(opt).from(pdfContent).save().then(function () {
                        document.body.removeChild(wrapper);
                    }).catch(function (err) {
                        console.error('PDF generation error:', err);
                        document.body.removeChild(wrapper);
                        alert('Error generating PDF: ' + err.message);
                    });
                } catch (error) {
                    console.error('Error generating PDF:', error);
                    alert('Error generating PDF: ' + error.message);
                }
            }

            // Add PDF download button event
            const pdfBtn = app.querySelector('#downloadPdfBtn') || app.querySelector('.cta.secondary');
            if (pdfBtn) {
                pdfBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    console.log('PDF button clicked');
                    try {
                        downloadPDF();
                    } catch (error) {
                        console.error('Error generating PDF:', error);
                        alert('Error generating PDF. Please check the console for details.');
                    }
                });
            } else {
                console.error('PDF button not found');
            }

            // Final init
            calculate();
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dscr_calculator', 'dscr_calc_shortcode');
