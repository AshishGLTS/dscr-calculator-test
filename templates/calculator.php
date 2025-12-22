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

* {
  box-sizing: border-box;
  font-family: Inter, system-ui, sans-serif;
}

.container {
  max-width: 1200px;
  margin: 40px auto;
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 32px;
  padding: 0 20px;
  align-items: flex-start;
}

h1 {
  font-size: 32px;
  margin-bottom: 8px;
}

.subtitle {
  color: var(--muted);
  margin-bottom: 32px;
}

.field {
  margin-bottom: 22px;
}

label {
  display: block;
  font-weight: 600;
  margin-bottom: 8px;
}

.dscr-field-group {
  margin-bottom: 24px;
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
  overflow: hidden;
  --percent: 30%;
}

.dscr-range-fill {
  height: 100%;
  background: #065f46;
  width: var(--percent);
}

.dscr-range-handle-visual {
  position: absolute;
  top: 50%;
  left: calc(var(--percent) - 16px);
  transform: translateY(-50%);
  height: 24px;
  width: 32px;
  background: #0b6e3d;
  border-radius: 6px;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 3px;
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
  font-weight: 700;
  padding: 0 12px;
  height: 100%;
  display: flex;
  align-items: center;
  border-right: 1px solid #d1d5db;
}

.dscr-num-box input {
  flex: 1;
  border: none;
  background: transparent;
  padding: 0 12px;
  text-align: right;
  font-weight: 700;
  font-size: 15px;
  outline: none;
}

.results {
  background: var(--dark);
  border-radius: 20px;
  padding: 28px;
  color: #fff;
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
}

.amount {
  font-size: 22px;
  font-weight: 700;
}

@media (max-width: 900px) {
  .container {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="container">

  <div class="inputs">
    <h1>DSCR Rental Calculator</h1>
    <p class="subtitle">Semper vel adipiscing laoreet iaculis sed at.</p>

    <!-- INPUT GROUPS (UNCHANGED) -->
    <!-- Purchase Price -->
    <div class="dscr-field-group">
      <label>Purchase Price</label>
      <div class="dscr-control-row">
        <div class="dscr-range-wrapper">
          <div class="dscr-range-fill"></div>
          <div class="dscr-range-handle-visual">
            <div class="handle-line"></div><div class="handle-line"></div>
          </div>
          <input type="range" class="dscr-range-input" min="0" max="1000000" step="1000" value="300000">
        </div>
        <div class="dscr-num-box"><span class="prefix">$</span><input type="number" value="300000"></div>
      </div>
    </div>

    <!-- REPEAT OTHER INPUT GROUPS EXACTLY AS YOU ALREADY HAVE -->
  </div>

  <div class="results">
    <h2>Your Results</h2>

    <div class="result-block">
      <div class="result-label">DSCR</div>
      <div class="result-value">0.00</div>
    </div>

    <div class="result-block">
      <div class="result-label">Loan Amount</div>
      <div class="result-value">$0</div>
    </div>

    <div class="payment-grid">
      <div class="payment-card">
        <div class="amount">$0.00</div>
        <div class="desc">Monthly Principal & Interest</div>
      </div>

      <div class="payment-card">
        <div class="amount">$0.00</div>
        <div class="desc">Monthly PITIA</div>
      </div>
    </div>
  </div>

</div>
