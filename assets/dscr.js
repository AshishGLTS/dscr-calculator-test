document.addEventListener("DOMContentLoaded", function () {

  /* =========================
     SLIDER + NUMBER SYNC
  ========================== */

  const groups = document.querySelectorAll(".dscr-field-group");

  groups.forEach(group => {
    const range = group.querySelector(".dscr-range-input");
    const number = group.querySelector(".dscr-num-box input");
    const wrapper = group.querySelector(".dscr-range-wrapper");

    function update(value) {
      value = Number(value) || 0;

      range.value = value;
      number.value = value;

      const min = Number(range.min);
      const max = Number(range.max);
      const percent = ((value - min) / (max - min)) * 100;

      wrapper.style.setProperty("--percent", percent + "%");

      calculate(); // 🔥 realtime calc
    }

    update(range.value);

    range.addEventListener("input", () => update(range.value));
    number.addEventListener("input", () => update(number.value));
  });

  /* =========================
     CALCULATION LOGIC
  ========================== */

  const inputs = document.querySelectorAll(".dscr-num-box input");

  const dscrEl = document.querySelector(".result-value");
  const loanEl = document.querySelectorAll(".result-value")[1];
  const piEl = document.querySelector(".payment-card .amount");
  const pitiaEl = document.querySelectorAll(".payment-card .amount")[1];

  function calculate() {
    const [
      purchase,
      down,
      rate,
      years,
      taxes,
      insurance,
      hoa,
      rent
    ] = Array.from(inputs).map(i => Number(i.value) || 0);

    const loan = purchase - down;
    const r = rate / 100 / 12;
    const n = years * 12;

    let monthlyPI = 0;
    if (r > 0 && n > 0) {
      monthlyPI =
        loan *
        (r * Math.pow(1 + r, n)) /
        (Math.pow(1 + r, n) - 1);
    }

    const pitia =
      monthlyPI +
      taxes / 12 +
      insurance / 12 +
      hoa / 12;

    const dscr = pitia > 0 ? rent / pitia : 0;

    // Update UI (NO UI CHANGE)
    loanEl.textContent = "$" + loan.toLocaleString();
    piEl.textContent = "$" + monthlyPI.toFixed(2);
    pitiaEl.textContent = "$" + pitia.toFixed(2);
    dscrEl.textContent = dscr.toFixed(2);
  }

  calculate();
});
