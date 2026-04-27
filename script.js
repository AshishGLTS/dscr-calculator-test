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
  #pdf-report { font-family: "Segoe UI", Arial, sans-serif; background: #fff; width: 1000px; color: #333; display: flex; flex-direction: column; height: 1410px; max-height: 1410px; overflow: hidden; }
  #pdf-report .pdf-container { max-width: 1000px; margin: 0; background: #ffffff; padding: 0; flex: 1; display: flex; flex-direction: column; }
  #pdf-report .pdf-header { background: #3c4a5d; height: 110px; position: relative; width: 100%; flex-shrink: 0; }
  #pdf-report .pdf-logo-box { position: absolute; bottom: 0; left: 0; background: #ffffff; width: 260px; height: 95px; border-radius: 0 14px 0 0; display: flex; align-items: center; justify-content: center; padding: 8px 15px; }
  #pdf-report .pdf-logo-box img { max-height: 100%; }
  #pdf-report .pdf-contact { position: absolute; top: 32px; right: 40px; color: #ffffff; font-size: 15px; text-align: right; line-height: 1.6; }
  #pdf-report .pdf-contact-row { display: flex; justify-content: flex-end; gap: 10px; }
  #pdf-report .pdf-label { font-weight: 600; }
  #pdf-report .pdf-title { position: absolute; bottom: -20px; left: 65%; transform: translateX(-50%); background: #1e7a52; color: white; padding: 8px 40px; border-radius: 8px; font-weight: bold; font-size: 20px; z-index: 10; white-space: nowrap; }
  #pdf-report .pdf-main-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 8px 15px; margin-top: 28px; flex: 1; }
  #pdf-report .pdf-column { display: flex; flex-direction: column; gap: 10px; }
  #pdf-report .pdf-card { background: #fafafa; border: 1px solid #d9c7a3; padding: 10px 14px; display: flex; flex-direction: column; }
  #pdf-report .pdf-section-gap { height: 6px; }
  #pdf-report .pdf-card h3 { margin: 0 0 6px; font-size: 18px; border-bottom: 1px solid #d6c7b2; padding-bottom: 4px; color: #333; }
  #pdf-report .pdf-sub-title { font-weight: bold; margin: 4px 0 2px; font-size: 17px; }
  #pdf-report .pdf-row { display: flex; justify-content: space-between; font-size: 16px; margin: 2px 0; }
  #pdf-report .pdf-positive span:last-child { color: #1a8f3c; font-weight: bold; }
  #pdf-report .pdf-negative span:last-child { color: #b33939; }
  #pdf-report .pdf-divider { border-top: 1px solid #d6c7b2; margin: 6px 0; }
  #pdf-report .pdf-footer { text-align: center; font-size: 14px; padding: 10px 15px; color: #777; font-style: italic; margin-top: auto; margin-bottom: 20px; }
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
                html2canvas: { scale: 2, useCORS: true, letterRendering: true, width: 1000, scrollX: 0, scrollY: 0 },
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

    const leadModal = document.getElementById('dscr-lead-modal');
    const leadName = document.getElementById('dscr-lead-name');
    const leadEmail = document.getElementById('dscr-lead-email');
    const leadCancel = document.getElementById('dscr-lead-cancel');
    const leadSubmit = document.getElementById('dscr-lead-submit');
    const leadError = document.getElementById('dscr-lead-error');
    const leadLoading = document.getElementById('dscr-lead-loading');
    const pdfBtn = app.querySelector('#downloadPdfBtn') || app.querySelector('.cta.secondary');

    if (pdfBtn && leadModal) {
        pdfBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!calculatedValues || Object.keys(calculatedValues).length === 0) {
                alert('Please calculate values first before downloading PDF.');
                return;
            }
            leadModal.style.display = 'flex';
        });

        leadCancel.addEventListener('click', function() {
            leadModal.style.display = 'none';
            leadError.style.display = 'none';
            leadLoading.style.display = 'none';
            leadName.value = '';
            leadEmail.value = '';
        });

        leadSubmit.addEventListener('click', function(e) {
            e.preventDefault();
            const name = leadName.value.trim();
            const email = leadEmail.value.trim();
            
            if (!name || !email) {
                showError('Please provide both your name and email.');
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('Please enter a valid email address.');
                return;
            }

            leadError.style.display = 'none';
            leadLoading.style.display = 'block';
            leadSubmit.disabled = true;
            leadCancel.disabled = true;

            generateAndSendPdf(name, email);
        });

        function generateAndSendPdf(name, email) {
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
                    html2canvas: { scale: 2, useCORS: true, letterRendering: true, width: 1000, scrollX: 0, scrollY: 0 },
                    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
                };

                html2pdf().set(opt).from(pdfContent).outputPdf('datauristring').then(function(pdfBase64) {
                    if (typeof dscrAjax !== 'undefined') {
                        const getInputValStr = (id) => {
                            const el = app.querySelector(`[data-id="${id}"] input[type="text"]`);
                            return el ? el.value : '0';
                        };
                        const formatCurrencyPDF = (value) => '$' + Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        
                        const cv = calculatedValues;
                        const fd = {
                            fullName: name,
                            email: email,
                            purchaseOr: 'Purchase',
                            purchasePrice: formatCurrencyPDF(getInputValStr('price')),
                            numberOf119: '1',
                            ltv120: getInputValStr('ltv') + '%',
                            number121: getInputValStr('rate') + '%',
                            number122: getInputValStr('term') || '30',
                            number123: getInputValStr('origination') + '%',
                            loanClosing: formatCurrencyPDF(getInputValStr('closing-fees')),
                            typeA153: formatCurrencyPDF(getInputValStr('rent')),
                            vacancyRate152: getInputValStr('vacancy') + '%',
                            propertyTaxes: formatCurrencyPDF(getInputValStr('taxes')),
                            insurance: formatCurrencyPDF(getInputValStr('insurance')),
                            monthlyHoa: formatCurrencyPDF(getInputValStr('hoa')),
                            annualRepairs: formatCurrencyPDF(getInputValStr('repair')),
                            annualUtilities: formatCurrencyPDF(getInputValStr('utilities')),
                            thirdParty: formatCurrencyPDF(getInputValStr('third-party')),
                            pricePer89: formatCurrencyPDF(cv.pricePerUnit),
                            loanamount: formatCurrencyPDF(cv.loanAmount),
                            downPayment: formatCurrencyPDF(cv.downPayment),
                            monthlyPayment92: formatCurrencyPDF(cv.monthlyPI),
                            typeA93: formatCurrencyPDF(cv.pitia),
                            typeA94: formatCurrencyPDF(cv.annualMortgagePayment),
                            typeA95: formatCurrencyPDF(cv.originationFeeAmount),
                            grossMonthly96: formatCurrencyPDF(cv.grossMonthlyRentalIncome),
                            typeA99: formatCurrencyPDF(cv.vacancyDeduction),
                            typeA99_2: formatCurrencyPDF(cv.netEffectiveRent),
                            taxesAnd100: formatCurrencyPDF(cv.taxesAndInsurance),
                            typeA101: formatCurrencyPDF(cv.annualHOA),
                            typeA102: formatCurrencyPDF(cv.annualRepair),
                            typeA103: formatCurrencyPDF(cv.annualUtilities),
                            operatingExpenses104: formatCurrencyPDF(cv.operatingExpenses),
                            typeA105: formatCurrencyPDF(cv.netOperatingIncome),
                            typeA106: formatCurrencyPDF(cv.netMonthlyCashflow),
                            typeA107: cv.capRate.toFixed(2) + '%',
                            typeA108: cv.cashOnCashReturn.toFixed(2) + '%',
                            typeA109: cv.dscr.toFixed(2),
                            typeA110: formatCurrencyPDF(cv.totalClosingCost),
                            typeA111: formatCurrencyPDF(cv.cashNeededToClose)
                        };

                        const formData = new URLSearchParams();
                        formData.append('action', 'dscr_submit_lead');
                        formData.append('nonce', dscrAjax.nonce);
                        formData.append('name', name);
                        formData.append('email', email);
                        formData.append('pdf_data', pdfBase64);
                        formData.append('form_data', JSON.stringify(fd));

                        fetch(dscrAjax.url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        })
                        .then(res => res.json())
                        .then(data => {
                            console.log("Server response:", data);
                            finalizeDownload(opt, pdfContent, wrapper);
                        })
                        .catch(err => {
                            console.error("Ajax Error:", err);
                            finalizeDownload(opt, pdfContent, wrapper);
                        });
                    } else {
                        // Fallback if WP ajax is completely missing
                        finalizeDownload(opt, pdfContent, wrapper);
                    }
                });
            } catch (err) {
                showError('Error preparing PDF: ' + err.message);
            }
        }

        function finalizeDownload(opt, pdfContent, wrapper) {
            html2pdf().set(opt).from(pdfContent).save().then(function () {
                document.body.removeChild(wrapper);
                hideModal();
            }).catch(function (err) {
                document.body.removeChild(wrapper);
                showError('Error downloading PDF: ' + err.message);
            });
        }

        function hideModal() {
            if(leadModal) {
                leadModal.style.display = 'none';
                leadLoading.style.display = 'none';
                leadSubmit.disabled = false;
                leadCancel.disabled = false;
                leadName.value = '';
                leadEmail.value = '';
            }
        }

        function showError(msg) {
            leadError.style.display = 'block';
            leadError.textContent = msg;
            leadSubmit.disabled = false;
            leadCancel.disabled = false;
            leadLoading.style.display = 'none';
        }

    } else if (pdfBtn) {
        // Fallback for static HTML without modal injected
        pdfBtn.addEventListener('click', function (e) {
            e.preventDefault();
            try { downloadPDF(); } catch (err) { alert(err.message); }
        });
    }

    // Final init
    calculate();
});
