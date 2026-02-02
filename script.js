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
        const fill = group.querySelector('.slider-fill');

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
    
    // Function to load logo image and convert to base64
    function loadLogoImage(logoUrl) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = img.width;
                    canvas.height = img.height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);
                    const imgData = canvas.toDataURL('image/png');
                    resolve(imgData);
                } catch (e) {
                    console.error('Error converting logo to base64:', e);
                    reject(e);
                }
            };
            
            img.onerror = function() {
                console.warn('Logo image failed to load from:', logoUrl);
                reject(new Error('Logo image not found'));
            };
            
            img.src = logoUrl;
        });
    }
    
    // PDF Download Function
    function downloadPDF() {
        // Check if jsPDF is loaded
        if (!window.jspdf) {
            alert('PDF library not loaded. Please refresh the page and try again.');
            console.error('jsPDF library not found');
            return;
        }
        
        // Check if calculated values exist
        if (!calculatedValues || Object.keys(calculatedValues).length === 0) {
            alert('Please calculate values first before downloading PDF.');
            console.error('No calculated values available');
            return;
        }
        
        // Get logo URL (you can customize this path)
        const logoUrl = 'assets/images/logo.png'; // Update this path to your logo
        
        // Load logo first, then generate PDF
        loadLogoImage(logoUrl).then(function(logoData) {
            // Logo loaded successfully, proceed with PDF generation
            createPDF(null, logoData);
        }).catch(function(error) {
            // Logo failed to load, generate PDF without logo (will use fallback)
            console.warn('Logo not loaded, using fallback:', error);
            createPDF(null, null);
        });
    }
    
    function createPDF(userData, logoImageData) {
        try {
            // Check for jsPDF library
            let jsPDF;
            if (typeof window.jspdf !== 'undefined') {
                jsPDF = window.jspdf.jsPDF;
            } else if (typeof window.jsPDF !== 'undefined') {
                jsPDF = window.jsPDF;
            } else {
                throw new Error('jsPDF library not loaded');
            }
            
            const doc = new jsPDF();
        
            // Helper function for currency formatting
            function formatCurrencyPDF(value) {
                return '$' + Math.round(value).toLocaleString('en-US');
            }
            
            // Colors matching Express Capital Financing logo
            const primaryColor = [34, 139, 34]; // Dark green (matching logo)
            const highlightColor = [255, 193, 7]; // Gold/Mustard yellow (matching logo)
            const textColor = [51, 51, 51];
            const lightGray = [245, 245, 245];
            const white = [255, 255, 255];
            const darkGray = [64, 64, 64];
            
            let yPos = 20;
            
            // Header with company branding - Light background
            const lightBgColor = [248, 249, 250]; // Light gray background
            doc.setFillColor(...lightBgColor);
            doc.rect(0, 0, 210, 80, 'F');
            
            // Add border line at bottom of header
            doc.setDrawColor(200, 200, 200);
            doc.setLineWidth(0.5);
            doc.line(10, 80, 200, 80);
            
            // Logo on left side
            const logoX = 15;
            const logoY = 15;
            const logoWidth = 70;
            const logoHeight = 25;
            
            // Add logo image if available, otherwise use fallback text
            if (logoImageData) {
                try {
                    doc.addImage(logoImageData, 'PNG', logoX, logoY, logoWidth, logoHeight);
                } catch (e) {
                    console.error('Error adding logo image to PDF:', e);
                    // Fall through to fallback
                    addTextLogo();
                }
            } else {
                // Fallback: Text-based logo if image not available
                addTextLogo();
            }
            
            function addTextLogo() {
                doc.setFontSize(18);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...primaryColor); // Dark green
                doc.text('EXPRESS', logoX, logoY + 8);
                doc.setFontSize(16);
                doc.text('CAPITAL', logoX, logoY + 18);
                doc.setFontSize(14);
                doc.setTextColor(100, 100, 100); // Dark gray
                doc.text('FINANCING', logoX, logoY + 28);
            }
            
            // Contact information on right side
            const contactX = 200; // Right edge of page
            const contactY = 15;
            
            doc.setFontSize(10);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...textColor);
            doc.text('Contact Information', contactX, contactY, { align: 'right' });
            
            doc.setFontSize(9);
            doc.setFont('helvetica', 'normal');
            doc.text('Phone: (718) 285-0806', contactX, contactY + 8, { align: 'right' });
            doc.text('Email: info@expresscapitalfinancing.com', contactX, contactY + 15, { align: 'right' });
            
            // Office addresses
            doc.setFontSize(8);
            doc.setFont('helvetica', 'bold');
            doc.text('New York Office:', contactX, contactY + 25, { align: 'right' });
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.text('14 53rd St, #408N', contactX, contactY + 31, { align: 'right' });
            doc.text('Brooklyn, NY 11232', contactX, contactY + 37, { align: 'right' });
            
            // Report title section with gold highlight
            yPos = 60;
            doc.setFillColor(...highlightColor);
            doc.rect(0, yPos - 5, 210, 15, 'F');
            doc.setTextColor(0, 0, 0); // Black text on gold
            doc.setFontSize(18);
            doc.setFont('helvetica', 'bold');
            doc.text('DSCR Rental Calculator Report', 105, yPos + 5, { align: 'center' });
            
            yPos = 90;
            doc.setTextColor(...textColor);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.text('Generated on: ' + new Date().toLocaleDateString(), 10, yPos);
            
            // Get input values
            const getInputVal = (id) => parseFloat(app.querySelector(`[data-id="${id}"] input[type="number"]`).value) || 0;
            const inputPrice = getInputVal('price');
            const inputUnits = getInputVal('units') || 1;
            const inputLTV = getInputVal('ltv');
            const inputRate = getInputVal('rate');
            const inputTerm = getInputVal('term') || 30;
            const inputOrigination = getInputVal('origination');
            
            // Key Results Section (Highlighted with gold)
            yPos += 15;
            doc.setFillColor(...highlightColor);
            doc.rect(10, yPos - 5, 190, 65, 'F');
            doc.setFontSize(16);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(0, 0, 0); // Black text on gold background
            doc.text('Key Results', 15, yPos + 5);
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            
            const keyResults = [
                { label: 'DSCR', value: calculatedValues.dscr.toFixed(2), x: 15 },
                { label: 'Cash on Cash Return', value: calculatedValues.cashOnCashReturn.toFixed(2) + '%', x: 110 },
                { label: 'Cap Rate', value: calculatedValues.capRate.toFixed(2) + '%', x: 15 },
                { label: 'Net Monthly Cashflow', value: formatCurrencyPDF(calculatedValues.netMonthlyCashflow), x: 110 },
                { label: 'Cash Needed to Close', value: formatCurrencyPDF(calculatedValues.cashNeededToClose), x: 15 },
                { label: 'Net Operating Income', value: formatCurrencyPDF(calculatedValues.netOperatingIncome), x: 110 }
            ];
            
            let resultY = yPos + 15;
            keyResults.forEach((result, index) => {
                // Layout for 6 items: 2 columns, 3 rows
                if (index === 2) {
                    resultY += 12; // Move to second row
                } else if (index === 4) {
                    resultY += 12; // Move to third row
                }
                doc.setTextColor(0, 0, 0); // Black text on gold background
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(11);
                doc.text(result.label + ':', result.x, resultY);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...primaryColor); // Dark green for values
                doc.text(result.value, result.x + 60, resultY);
            });
            
            yPos += 70;
            
            // Financial Breakdown
            doc.setFillColor(...primaryColor); // Dark green background
            doc.rect(10, yPos - 5, 190, 8, 'F');
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...white); // White text on dark green
            doc.text('Financial Breakdown', 15, yPos + 1);
            doc.setTextColor(...textColor); // Reset to dark text
            
            yPos += 10;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const financialData = [
                { label: 'Purchase Price', value: formatCurrencyPDF(inputPrice) },
                { label: 'Number of Units', value: inputUnits.toString() },
                { label: 'Price per Unit', value: formatCurrencyPDF(calculatedValues.pricePerUnit) },
                { label: 'LTV (%)', value: inputLTV.toFixed(2) + '%' },
                { label: 'Loan Amount', value: formatCurrencyPDF(calculatedValues.loanAmount) },
                { label: 'Down Payment', value: formatCurrencyPDF(calculatedValues.downPayment) },
                { label: 'Monthly Payment (P&I)', value: formatCurrencyPDF(calculatedValues.monthlyPI) },
                { label: 'PITIA', value: formatCurrencyPDF(calculatedValues.pitia) },
                { label: 'Annual Mortgage Payment', value: formatCurrencyPDF(calculatedValues.annualMortgagePayment) },
                { label: 'Origination Fee Amount', value: formatCurrencyPDF(calculatedValues.originationFeeAmount) },
                { label: 'Loan Closing Fees', value: formatCurrencyPDF(getInputVal('closing-fees')) },
                { label: '3rd Party Closing Cost', value: formatCurrencyPDF(getInputVal('third-party')) },
                { label: 'Total Closing Cost', value: formatCurrencyPDF(calculatedValues.totalClosingCost) },
                { label: 'Cash Needed to Close', value: formatCurrencyPDF(calculatedValues.cashNeededToClose) }
            ];
            
            financialData.forEach((item) => {
                if (yPos > 250) {
                    doc.addPage();
                    yPos = 20;
                }
                doc.setTextColor(...textColor);
                doc.setFont('helvetica', 'normal');
                doc.text(item.label + ':', 15, yPos);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...primaryColor); // Dark green for values
                doc.text(item.value, 150, yPos);
                doc.setFont('helvetica', 'normal');
                yPos += 8;
            });
            
            // Rental Income & Expenses
            yPos += 5;
            doc.setFillColor(...primaryColor); // Dark green background
            doc.rect(10, yPos - 5, 190, 8, 'F');
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...white); // White text on dark green
            doc.text('Rental Income & Expenses', 15, yPos + 1);
            doc.setTextColor(...textColor); // Reset to dark text
            
            yPos += 10;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const rentalData = [
                { label: 'Gross Monthly Rental Income', value: formatCurrencyPDF(calculatedValues.grossMonthlyRentalIncome) },
                { label: 'Annual Rental Income', value: formatCurrencyPDF(calculatedValues.annualRentalIncome) },
                { label: 'Vacancy Rate (%)', value: getInputVal('vacancy').toFixed(2) + '%' },
                { label: 'Vacancy Deduction', value: formatCurrencyPDF(calculatedValues.vacancyDeduction) },
                { label: 'Net Effective Rent', value: formatCurrencyPDF(calculatedValues.netEffectiveRent) },
                { label: 'Property Taxes', value: formatCurrencyPDF(getInputVal('taxes')) },
                { label: 'Insurance', value: formatCurrencyPDF(getInputVal('insurance')) },
                { label: 'Taxes and Insurance', value: formatCurrencyPDF(calculatedValues.taxesAndInsurance) },
                { label: 'Monthly HOA', value: formatCurrencyPDF(getInputVal('hoa')) },
                { label: 'Annual HOA', value: formatCurrencyPDF(calculatedValues.annualHOA) },
                { label: 'Annual Repairs and Maintenance', value: formatCurrencyPDF(calculatedValues.annualRepair) },
                { label: 'Annual Utilities', value: formatCurrencyPDF(calculatedValues.annualUtilities) }
            ];
            
            rentalData.forEach((item) => {
                if (yPos > 250) {
                    doc.addPage();
                    yPos = 20;
                }
                doc.setTextColor(...textColor);
                doc.setFont('helvetica', 'normal');
                doc.text(item.label + ':', 15, yPos);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...primaryColor); // Dark green for values
                doc.text(item.value, 150, yPos);
                doc.setFont('helvetica', 'normal');
                yPos += 8;
            });
            
            // Operating Results
            yPos += 5;
            doc.setFillColor(...primaryColor); // Dark green background
            doc.rect(10, yPos - 5, 190, 8, 'F');
            doc.setFontSize(14);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...white); // White text on dark green
            doc.text('Operating Results', 15, yPos + 1);
            doc.setTextColor(...textColor); // Reset to dark text
            
            yPos += 10;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'normal');
            const operatingData = [
                { label: 'Operating Expenses', value: formatCurrencyPDF(calculatedValues.operatingExpenses) },
                { label: 'Net Operating Income', value: formatCurrencyPDF(calculatedValues.netOperatingIncome) },
                { label: 'Net Monthly Cashflow', value: formatCurrencyPDF(calculatedValues.netMonthlyCashflow) },
                { label: 'Cap Rate', value: calculatedValues.capRate.toFixed(2) + '%' },
                { label: 'Cash on Cash Return', value: calculatedValues.cashOnCashReturn.toFixed(2) + '%' },
                { label: 'DSCR', value: calculatedValues.dscr.toFixed(2) }
            ];
            
            operatingData.forEach((item) => {
                if (yPos > 250) {
                    doc.addPage();
                    yPos = 20;
                }
                doc.setTextColor(...textColor);
                doc.setFont('helvetica', 'normal');
                doc.text(item.label + ':', 15, yPos);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...primaryColor); // Dark green for values
                doc.text(item.value, 150, yPos);
                doc.setFont('helvetica', 'normal');
                yPos += 8;
            });
            
            // Footer
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text('Page ' + i + ' of ' + pageCount, 105, 290, { align: 'center' });
                doc.text('Disclaimer: This calculator provides estimates only. Consult with financial and real estate professionals before making investment decisions.', 105, 285, { align: 'center', maxWidth: 190 });
            }
            
            // Download PDF
            const fileName = 'DSCR-Calculator-Report-' + new Date().getTime() + '.pdf';
            doc.save(fileName);
        
            // Save PDF
            doc.save('DSCR_Calculator_Report.pdf');
        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF: ' + error.message);
        }
    }
    
    // Add PDF download button event
    const pdfBtn = app.querySelector('#downloadPdfBtn') || app.querySelector('.cta.secondary');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', function(e) {
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
