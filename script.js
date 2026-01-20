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
