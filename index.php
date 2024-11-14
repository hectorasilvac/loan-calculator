<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loan Calculator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="styles.css" rel="stylesheet">
    <script defer src="scripts.js"></script>
  </head>
  <body>
    <div class="container mt-5 bg-white p-4 rounded shadow">
      <div class="row">
        <!-- Left Column: Form -->
        <div class="col-md-6">
          <h1 class="mb-4">SBA Loan Calculator</h1>
          <p class="text-muted">Estimate your monthly SBA loan payments.</p>
          <form id="loanForm">
            <!-- Buying a new business or real estate -->
            <div class="mb-4">
              <label class="form-label fw-bold">Are you buying a new business or real estate?</label>
              <div class="d-flex">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="buyingOption" id="buyingYes" value="yes">
                  <label class="form-check-label" for="buyingYes">
                    Yes
                  </label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="buyingOption" id="buyingNo" value="no" checked>
                  <label class="form-check-label" for="buyingNo">
                    No
                  </label>
                </div>
              </div>
            </div>

            <!-- Buyout Amount / Amount Seeking -->
            <div class="mb-4">
              <label for="amountSeeking" class="form-label" id="amountLabel">Amount Seeking? ($)</label>
              <input type="text" class="form-control" id="amountSeeking" placeholder="$100,000" value="$100,000" required minlength="5" maxlength="11" oninput="formatCurrencyAndValidateAmountSeeking()">
              <div class="invalid-feedback" id="amountSeekingError"></div>
              <div class="d-flex align-items-center mt-3">
                <span class="small text-muted me-2">$50K</span>
                <input type="range" class="form-range" id="amountRange" min="50000" max="5000000" step="1000" value="100000" placeholder="100000">
                <span class="small text-muted ms-2">$5M</span>
              </div>
            </div>

            <!-- Down Payment -->
            <div class="mb-4 d-none" id="downPaymentDiv">
              <label for="downPayment" class="form-label">Down Payment (minimum 15%)</label>
              <input type="text" class="form-control" id="downPayment" placeholder="15" value="15" required minlength="1" maxlength="2" oninput="validateDownPayment()">
              <div class="invalid-feedback" id="downPaymentError"></div>
            </div>

            <!-- Term Length -->
            <div class="mb-4">
              <label for="termLength" class="form-label">Term Length?</label>
              <select class="form-select" id="termLength" required>
                <option value="" disabled>Select term length</option>
                <option value="5">5 years</option>
                <option value="10" selected>10 years</option>
                <option value="15">15 years</option>
              </select>
            </div>

            <!-- SBA Fees -->
            <div class="mb-4">
              <label for="sbaFees" class="form-label">SBA Fees (%)</label>
              <input type="text" class="form-control bg-light" id="sbaFees" value="3" readonly>
            </div>

            <!-- Annual Rate -->
            <div class="mb-4">
              <label for="annualRate" class="form-label">Annual Rate (%)</label>
              <input type="text" class="form-control bg-light" id="annualRate" value="6.75" readonly>
            </div>
          </form>
        </div>

        <!-- Right Column: Estimated Payment Details -->
        <div class="col-md-6">
          <div class="mt-5">
            <h2>Your estimated monthly payment</h2>
            <p class="display-4" id="monthlyPayment">$976.00</p>
            <div class="d-flex justify-content-between mt-4">
              <!-- One Time-Fee -->
              <div>
                <p class="text-uppercase small text-muted">One Time-Fee</p>
                <p class="h4" id="oneTimeFee">$2,550</p>
              </div>
              <!-- DownPayment -->
              <div>
                <p class="text-uppercase small text-muted">DownPayment</p>
                <p class="h4" id="downPaymentText">$15,000</p>
              </div>
              <!-- Repayment -->
              <div>
                <p class="text-uppercase small text-muted">Repayment</p>
                <p class="h4" id="repayment">$117,121</p>
              </div>
            </div>
            <hr>
            <div class="card p-4 bg-light text-center mt-4">
              <h3>Ready to get the capital you need?</h3>
              <p class="small text-muted">Find out what you qualify from for 75+ lenders in 15 minutes.</p>
              <button type="button" class="btn btn-dark text-white fw-bold rounded-pill">GET AN OFFER</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Amortization Table -->
      <div class="mt-5">
        <h2>Amortization for an SBA Loan</h2>
        <div class="d-flex justify-content-between mt-4">
          <!-- Loan Amount -->
          <div>
            <p class="h4" id="loanAmount">$212,500</p>
            <p class="text-uppercase small text-muted">Loan Amount</p>
          </div>
          <!-- Total Interest Paid -->
          <div>
            <p class="h4" id="totalInterestPaid">$80,350</p>
            <p class="text-uppercase small text-muted">Total Interest Paid</p>
          </div>
          <!-- Total Cost of Loan -->
          <div>
            <p class="h4" id="totalCostOfLoan">$212,500</p>
            <p class="text-uppercase small text-muted">Total Cost of Loan</p>
          </div>
          <!-- Payoff Date -->
          <div>
            <p class="h4" id="payoffDate">Oct 2024</p>
            <p class="text-uppercase small text-muted">Payoff Date</p>
          </div>
        </div>
        <hr>
        <table class="table table-bordered mt-4">
          <thead class="table-dark">
            <tr>
              <th>Year</th>
              <th>Beginning Balance</th>
              <th>Interest</th>
              <th>Principal</th>
              <th>Ending Balance</th>
            </tr>
          </thead>
          <tbody id="amortizationSchedule">
            <!-- Amortization schedule rows will be populated dynamically -->
          </tbody>
        </table>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  </body>
</html>
