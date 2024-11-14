function calculateLoan() {
  // Validate inputs before proceeding
  if (!validateAmountSeeking() || !validateDownPayment()) {
    return;
  }

  // Gather form data
  const amountSeeking = document.getElementById('amountSeeking').value.replace(/[^0-9]/g, '');
  const termLength = document.getElementById('termLength').value.replace(/[^0-9]/g, '');
  const buyingOption = document.querySelector('input[name="buyingOption"]:checked').value;
  const sbaFees = document.getElementById('sbaFees').value;
  const annualRate = document.getElementById('annualRate').value;
  const downPayment = document.getElementById('downPayment').value.replace(/[^0-9]/g, '');

  const formData = new FormData();
  formData.append('amountSeeking', amountSeeking);
  formData.append('termLength', termLength);
  formData.append('sbaFees', sbaFees);
  formData.append('annualRate', annualRate);
  formData.append('downPayment', downPayment);

  fetch('calculation.php', {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(response => {
      if (response.status === 'success') {
        const data = response.data;
        updateLoanDetails(data);
        populateAmortizationSchedule(data.amortizationSchedule);
      } else if (response.status === 'error') {
        handleBackendValidationErrors(response.errors);
      }
    })
    .catch(error => console.error('Network error:', error));
}

function handleBackendValidationErrors(errors) {
  if (errors.amountSeekingError) {
    const amountSeekingInput = document.getElementById('amountSeeking');
    const amountSeekingError = document.getElementById('amountSeekingError');
    amountSeekingError.textContent = errors.amountSeekingError;
    amountSeekingInput.classList.add('is-invalid');
  }

  if (errors.downPaymentError) {
    const downPaymentInput = document.getElementById('downPayment');
    const downPaymentError = document.getElementById('downPaymentError');
    downPaymentError.textContent = errors.downPaymentError;
    downPaymentInput.classList.add('is-invalid');
  }

  // Scroll to the first error to ensure visibility
  const firstErrorElement = document.querySelector('.is-invalid');
  if (firstErrorElement) {
    firstErrorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

function updateLoanDetails(data) {
  document.getElementById('monthlyPayment').textContent = formatCurrencyValue(data.estimatedMonthlyPayment, 2);
  document.getElementById('oneTimeFee').textContent = formatCurrencyValue(data.oneTimeFee);
  document.getElementById('downPaymentText').textContent = formatCurrencyValue(data.downPaymentAmount);
  document.getElementById('repayment').textContent = formatCurrencyValue(data.totalRepayment);
  document.getElementById('loanAmount').textContent = formatCurrencyValue(data.loanAmount);
  document.getElementById('totalInterestPaid').textContent = formatCurrencyValue(data.totalInterestPaid);
  document.getElementById('totalCostOfLoan').textContent = formatCurrencyValue(data.totalCostOfLoan);
  document.getElementById('payoffDate').textContent = new Date(data.payoffDate).toLocaleString('en-US', { month: 'short', year: 'numeric' });
}

function populateAmortizationSchedule(scheduleData) {
  const amortizationSchedule = document.getElementById('amortizationSchedule');
  amortizationSchedule.innerHTML = '';
  let currentYear = null;
  scheduleData.forEach(function (entry) {
    if (entry.year !== currentYear) {
      currentYear = entry.year;
      const yearRow = document.createElement('tr');
      yearRow.classList.add('table-secondary');
      yearRow.innerHTML = `
        <td>${entry.year}</td>
        <td colspan="4">Year Summary</td>
      `;
      amortizationSchedule.appendChild(yearRow);
    }
    const monthRow = document.createElement('tr');
    monthRow.innerHTML = `
      <td>${entry.month}/${entry.year}</td>
      <td>${formatCurrencyValue(entry.beginningBalance)}</td>
      <td>${formatCurrencyValue(entry.interest)}</td>
      <td>${formatCurrencyValue(entry.principal)}</td>
      <td>${formatCurrencyValue(entry.endingBalance)}</td>
    `;
    amortizationSchedule.appendChild(monthRow);
  });
}

function formatCurrencyValue(value, fractionDigits = 0) {
  return '$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: fractionDigits, maximumFractionDigits: fractionDigits });
}

// Add event listeners to form inputs
document.addEventListener('DOMContentLoaded', function() {
  calculateLoan();

  document.getElementById('amountSeeking').addEventListener('input', debounce(function() {
    validateAmountSeeking();
    calculateLoan();
  }, 300));

  document.getElementById('termLength').addEventListener('change', calculateLoan);

  document.getElementById('downPayment').addEventListener('input', debounce(function() {
    validateDownPayment();
    calculateLoan();
  }, 300));

  document.getElementById('amountRange').addEventListener('input', function() {
    document.getElementById('amountSeeking').value = '$' + Number(this.value).toLocaleString();
    validateAmountSeeking();
    calculateLoan();
  });
});

document.getElementById('buyingYes').addEventListener('change', function() {
  document.getElementById('amountLabel').textContent = 'Buyout Amount ($)';
  document.getElementById('downPaymentDiv').classList.remove('d-none');
  document.getElementById('sbaFees').value = '2.5';
  resetValidationErrors();
  document.getElementById('amountSeeking').value = '$100,000';
  document.getElementById('amountRange').value = '100000';
  document.getElementById('downPayment').value = '15';
  calculateLoan();
});

document.getElementById('buyingNo').addEventListener('change', function() {
  document.getElementById('amountLabel').textContent = 'Amount Seeking? ($)';
  document.getElementById('downPaymentDiv').classList.add('d-none');
  document.getElementById('sbaFees').value = '3';
  resetValidationErrors();
  document.getElementById('amountSeeking').value = '$100,000';
  document.getElementById('amountRange').value = '100000';
  document.getElementById('downPayment').value = '15';
  calculateLoan();
});

document.getElementById('amountSeeking').addEventListener('input', function() {
  const value = parseInt(this.value.replace(/[^0-9]/g, ''));
  if (!isNaN(value)) {
    document.getElementById('amountRange').value = value;
  }
});

function formatCurrency(input) {
  let value = input.value.replace(/[^0-9]/g, '');
  if (value) {
    value = parseInt(value).toLocaleString('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 0 });
    input.value = value;
  }
}

function formatCurrencyAndValidateAmountSeeking() {
  formatCurrency(document.getElementById('amountSeeking'));
  validateAmountSeeking();
}

function validateAmountSeeking() {
  const amountSeekingInput = document.getElementById('amountSeeking');
  const amountSeekingError = document.getElementById('amountSeekingError');
  let value = parseInt(amountSeekingInput.value.replace(/[^0-9]/g, ''));

  if (/[^0-9,$]/.test(amountSeekingInput.value) || amountSeekingInput.value.trim() === '' || /^\$\s*$/.test(amountSeekingInput.value) || (amountSeekingInput.value.match(/\$/g) || []).length > 1) {
    amountSeekingError.textContent = 'Only numbers are allowed.';
    amountSeekingInput.classList.add('is-invalid');
    return false;
  } else if (amountSeekingInput.value.length < 5 || value < 50000) {
    amountSeekingError.textContent = 'The minimum amount you can request is $50,000.';
    amountSeekingInput.classList.add('is-invalid');
    return false;
  } else if (amountSeekingInput.value.length > 11 || value > 5000000) {
    amountSeekingError.textContent = 'The maximum amount you can request is $5,000,000.';
    amountSeekingInput.classList.add('is-invalid');
    return false;
  } else {
    amountSeekingError.textContent = '';
    amountSeekingInput.classList.remove('is-invalid');
    return true;
  }
}

function validateDownPayment() {
  const downPaymentInput = document.getElementById('downPayment');
  const downPaymentError = document.getElementById('downPaymentError');
  let value = parseInt(downPaymentInput.value.replace(/[^0-9]/g, ''));

  if (/[^0-9]/.test(downPaymentInput.value) || downPaymentInput.value.trim() === '' || /\$/.test(downPaymentInput.value)) {
    downPaymentError.textContent = 'Only numbers are allowed.';
    downPaymentInput.classList.add('is-invalid');
    return false;
  } else if (downPaymentInput.value.length < 1 || value < 15) {
    downPaymentError.textContent = 'The minimum down payment is 15%.';
    downPaymentInput.classList.add('is-invalid');
    return false;
  } else if (downPaymentInput.value.length > 2 || value > 95) {
    downPaymentError.textContent = 'The maximum down payment is 95%.';
    downPaymentInput.classList.add('is-invalid');
    return false;
  } else {
    downPaymentError.textContent = '';
    downPaymentInput.classList.remove('is-invalid');
    return true;
  }
}

function resetValidationErrors() {
  document.getElementById('downPaymentError').textContent = '';
  document.getElementById('downPayment').classList.remove('is-invalid');
  document.getElementById('amountSeekingError').textContent = '';
  document.getElementById('amountSeeking').classList.remove('is-invalid');
}

function debounce(func, delay) {
  let timer;
  return function(...args) {
    clearTimeout(timer);
    timer = setTimeout(() => func.apply(this, args), delay);
  };
}
