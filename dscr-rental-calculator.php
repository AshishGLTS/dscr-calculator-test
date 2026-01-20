<?php
/**
 * Plugin Name: DSCR Rental Calculator Test
 * Description: A real-time Debt Service Coverage Ratio (DSCR) calculator for real estate investors.
 * Version: 1.2.4
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

            .dscr-range-wrapper {
                flex: 1;
                height: 40px;
                background: #f3f4f6;
                border: 1px solid #d1d5db;
                border-radius: 10px;
                position: relative;
                overflow: visible;
                padding: 0 19px;
                box-sizing: border-box;
            }

            .dscr-range-fill {
                height: calc(100% - 8px);
                background: #065f46;
                width: 0%;
                pointer-events: none;
                border-radius: 6px;
                position: absolute;
                left: 19px;
                top: 4px;
                min-width: 0;
            }

            .dscr-range-handle-visual {
                position: absolute;
                top: 50%;
                left: 0%;
                transform: translate(-50%, -50%);
                height: 30px;
                width: 40px;
                background: #0b6e3d;
                border-radius: 8px;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 3px;
                pointer-events: none;
                z-index: 2;
                transition: left 0.05s linear, background 0.2s, height 0.2s, width 0.2s;
                box-sizing: border-box;
                will-change: left;
            }

            .dscr-range-wrapper.has-value .dscr-range-handle-visual {
                height: 30px;
                width: 38px;
                background: #61977c;
                border-radius: 6px;
            }

            .handle-pause-icon {
                display: none;
            }

            .dscr-range-wrapper:not(.has-value) .handle-pause-icon {
                display: block;
            }

            .dscr-range-wrapper:not(.has-value) .handle-line {
                display: none;
            }

            .dscr-range-wrapper.has-value .handle-pause-icon {
                display: none;
            }

            .handle-pause-icon svg {
                width: 12px;
                height: 12px;
            }

            .handle-line { 
                width: 3px; 
                height: 14px; 
                background: #064e3b; 
                border-radius: 2px; 
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
                    <label>Purchase Price</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="5000000" 
                                   step="5000" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="5000" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="units">
                    <label>Number of Units</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="1" 
                                   max="50" 
                                   step="1" 
                                   value="1" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">#</span>
                            <input type="number" step="1" value="1" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="ltv">
                    <label>LTV (%)</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="100" 
                                   step="1" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="number" step="1" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rate">
                    <label>Interest Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="1" 
                                   max="15" 
                                   step="0.1" 
                                   value="1" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="number" step="0.1" value="1" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="term">
                    <label>Years</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="5" 
                                   max="40" 
                                   step="1" 
                                   value="5" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">Yrs</span>
                            <input type="number" step="1" value="5" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="origination">
                    <label>Origination Points</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="5" 
                                   step="0.25" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">pts</span>
                            <input type="number" step="0.25" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="closing-fees">
                    <label>Loan Closing Fees</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="10000" 
                                   step="100" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="rent">
                    <label>Monthly Gross Rent</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="50000" 
                                   step="100" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="vacancy">
                    <label>Vacancy Rate (%)</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="20" 
                                   step="0.5" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">%</span>
                            <input type="number" step="0.5" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="taxes">
                    <label>Property Taxes</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="50000" 
                                   step="100" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="100" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="insurance">
                    <label>Insurance</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="20000" 
                                   step="50" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="hoa">
                    <label>Monthly HOA</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="2000" 
                                   step="10" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="10" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="repair">
                    <label>Annual Repair and Maint (per unit)</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="5000" 
                                   step="50" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="utilities">
                    <label>Annual Utilities</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="10000" 
                                   step="50" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="50" value="0" />
                        </div>
                    </div>
                </div>

                <div class="dscr-field-group" data-id="third-party">
                    <label>3rd Party Closing Cost</label>
                    <div class="dscr-control-row">
                        <div class="dscr-range-wrapper">
                            <div class="dscr-range-fill"></div>
                            <div class="dscr-range-handle-visual">
                                <div class="handle-pause-icon">
                                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="3" y="2" width="2" height="8" fill="white" rx="1"/>
                                        <rect x="7" y="2" width="2" height="8" fill="white" rx="1"/>
                                    </svg>
                                </div>
                                <div class="handle-line"></div>
                                <div class="handle-line"></div>
                            </div>
                            <input type="range" class="dscr-range-input" 
                                   min="0" 
                                   max="20000" 
                                   step="100" 
                                   value="0" />
                        </div>
                        <div class="dscr-num-box">
                            <span class="prefix">$</span>
                            <input type="number" step="100" value="0" />
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
                            <div class="amount" id="val-closing-cost">$0.00</div>
                            <div class="desc">Total Closing Cost</div>
                        </div>
                        <div class="payment-info">Total Closing Cost</div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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
            return '$' + num.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function updateUI(groupId, value, source) {
            const group = app.querySelector(`.dscr-field-group[data-id="${groupId}"]`);
            const range = group.querySelector('.dscr-range-input');
            const number = group.querySelector('input[type="number"]');
            const fill = group.querySelector('.dscr-range-fill');
            const handle = group.querySelector('.dscr-range-handle-visual');
            const wrapper = group.querySelector('.dscr-range-wrapper');

            // Sync the other input
            if (source === 'range') {
                number.value = value;
            } else if (source === 'number') {
                const numVal = parseFloat(value) || 0;
                const min = parseFloat(range.min);
                const max = parseFloat(range.max);
                const clampedNum = Math.max(min, Math.min(max, numVal));
                range.value = clampedNum;
                value = clampedNum;
            }

            // Calculate percentage for visual fill and handle
            const min = parseFloat(range.min);
            const max = parseFloat(range.max);
            const clampedVal = Math.min(Math.max(parseFloat(value) || 0, min), max);
            
            // Check if value is at minimum (zero or minimum value)
            const isAtMinimum = clampedVal === min;
            
            // Toggle has-value class to show/hide pause icon vs lines
            if (isAtMinimum) {
                wrapper.classList.remove('has-value');
            } else {
                wrapper.classList.add('has-value');
            }
            
            // Calculate percentage (0 to 100)
            let percent = 0;
            if (max > min) {
                percent = ((clampedVal - min) / (max - min)) * 100;
            }
            
            // Clamp percent to valid range (0-100)
            percent = Math.max(0, Math.min(100, percent));
            
            // Get wrapper dimensions for positioning
            const wrapperWidth = wrapper.offsetWidth;
            const padding = 19; // left and right padding
            const innerWidth = wrapperWidth - (padding * 2);
            
            if (isAtMinimum) {
                // At minimum: position handle at left edge (19px from wrapper left)
                handle.style.left = padding + 'px';
                fill.style.width = '0%';
            } else {
                // Update fill width - fill extends to where the handle center should be
                const fillWidth = (percent / 100) * innerWidth;
                fill.style.width = fillWidth + 'px';
                
                // Update handle position - account for padding
                const handleLeft = padding + (percent / 100) * innerWidth;
                handle.style.left = handleLeft + 'px';
            }

            calculate();
        }

        function calculate() {
            const getVal = (id) => parseFloat(app.querySelector(`[data-id="${id}"] input[type="number"]`).value) || 0;
            
            const price = getVal('price');
            const units = getVal('units') || 1;
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
            
            // Calculate Total Closing Cost
            const totalClosingCost = originationFee + closingFees + thirdParty;
            vals.closingCost.textContent = formatCurrency(totalClosingCost);
            
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
            const number = group.querySelector('input[type="number"]');
            const handle = group.querySelector('.dscr-range-handle-visual');
            const id = group.dataset.id;

            // Disable transition while dragging for instant response
            range.addEventListener('mousedown', () => {
                handle.style.transition = 'none';
            });
            
            range.addEventListener('mouseup', () => {
                handle.style.transition = '';
            });
            
            range.addEventListener('touchstart', () => {
                handle.style.transition = 'none';
            });
            
            range.addEventListener('touchend', () => {
                handle.style.transition = '';
            });

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
        
        // PDF Download Function
        function downloadPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Title
            doc.setFontSize(20);
            doc.text('DSCR Rental Calculator Report', 105, 20, { align: 'center' });
            
            // Date
            doc.setFontSize(10);
            doc.text(`Generated: ${new Date().toLocaleDateString()}`, 105, 30, { align: 'center' });
            
            let yPos = 45;
            const lineHeight = 8;
            const leftMargin = 20;
            const rightMargin = 190;
            
            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.text('CALCULATED RESULTS', leftMargin, yPos);
            yPos += lineHeight + 5;
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            
            // Format helper
            const formatValue = (val, isPercent = false, isCurrency = true) => {
                if (isCurrency) {
                    return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                } else if (isPercent) {
                    return val.toFixed(2) + '%';
                } else {
                    return val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            };
            
            // Get input values for formula display
            const getInputVal = (id) => parseFloat(app.querySelector(`[data-id="${id}"] input[type="number"]`).value) || 0;
            const inputPrice = getInputVal('price');
            const inputLTV = getInputVal('ltv');
            
            // Results
            const results = [
                ['Price per Unit', formatValue(calculatedValues.pricePerUnit)],
                ['Loan Amount', formatValue(calculatedValues.loanAmount), `Formula: Purchase Price × LTV = $${inputPrice.toLocaleString()} × ${inputLTV}%`],
                ['Down Payment', formatValue(calculatedValues.downPayment)],
                ['Monthly Payment (P&I)', formatValue(calculatedValues.monthlyPI)],
                ['PITIA', formatValue(calculatedValues.pitia)],
                ['Annual Mortgage Payment', formatValue(calculatedValues.annualMortgagePayment), 'Formula: PITIA × 12'],
                ['Origination Fee Amount', formatValue(calculatedValues.originationFeeAmount)],
                ['Gross Monthly Rental Income', formatValue(calculatedValues.grossMonthlyRentalIncome)],
                ['Annual Rental Income', formatValue(calculatedValues.annualRentalIncome), 'Formula: Gross Monthly Rental Income × 12'],
                ['Vacancy Deduction', formatValue(calculatedValues.vacancyDeduction), 'Formula: Annual Rental Income × Vacancy Rate'],
                ['Net Effective Rent', formatValue(calculatedValues.netEffectiveRent), 'Formula: Annual Rental Income - Vacancy Deduction'],
                ['Taxes and Insurance', formatValue(calculatedValues.taxesAndInsurance)],
                ['Annual HOA', formatValue(calculatedValues.annualHOA)],
                ['Annual Repairs and Maintenance', formatValue(calculatedValues.annualRepair)],
                ['Annual Utilities', formatValue(calculatedValues.annualUtilities)],
                ['Operating Expenses', formatValue(calculatedValues.operatingExpenses), 'Formula: (Taxes and Insurance + Annual HOA + Annual Repairs and Maint + Annual Utilities) + (Monthly Payment (P&I) × 12)'],
                ['Net Operating Income', formatValue(calculatedValues.netOperatingIncome), 'Formula: Net Effective Rent - Operating Expenses'],
                ['Net Monthly Cashflow', formatValue(calculatedValues.netMonthlyCashflow), 'Formula: Net Operating Income / 12'],
                ['Cap Rate', formatValue(calculatedValues.capRate, true, false), 'Formula: Net Operating Income / Purchase Price (%)'],
                ['Cash on Cash Return', formatValue(calculatedValues.cashOnCashReturn, true, false), 'Formula: Net Operating Income / Cash Needed to Close (%)'],
                ['DSCR', calculatedValues.dscr.toFixed(2), 'Formula: (Net Effective Rent / 12) / PITIA'],
                ['Total Closing Cost', formatValue(calculatedValues.totalClosingCost)],
                ['Cash Needed to Close', formatValue(calculatedValues.cashNeededToClose)]
            ];
            
            results.forEach(([label, value, formula]) => {
                if (yPos > 270) {
                    doc.addPage();
                    yPos = 20;
                }
                doc.setFont(undefined, 'bold');
                doc.text(label + ':', leftMargin, yPos);
                doc.setFont(undefined, 'normal');
                doc.text(value, rightMargin, yPos, { align: 'right' });
                yPos += lineHeight;
                
                // Add formula if provided
                if (formula) {
                    doc.setFontSize(8);
                    doc.setFont(undefined, 'italic');
                    doc.text(formula, leftMargin + 5, yPos, { maxWidth: 150 });
                    yPos += lineHeight;
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                }
            });
            
            // Save PDF
            doc.save('DSCR_Calculator_Report.pdf');
        }
        
        // Add PDF download button event
        const pdfBtn = app.querySelector('.cta.secondary');
        if (pdfBtn) {
            pdfBtn.addEventListener('click', downloadPDF);
        }
        
        // Final init
        calculate();
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('dscr_calculator', 'dscr_calc_shortcode');
