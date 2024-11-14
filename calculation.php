<?php

header('Content-Type: application/json');

class LoanCalculator {
    const MIN_AMOUNT_SEEKING = 50000;
    const MAX_AMOUNT_SEEKING = 5000000;
    const MIN_DOWN_PAYMENT = 15;
    const MAX_DOWN_PAYMENT = 95;
    const ANNUAL_RATE_ALLOWED = 6.75;
    const SBA_FEES_ALLOWED = [2.5, 3];

    private $errors = [];

    public function validate_input($input, $name) {
        $camelCaseName = lcfirst(str_replace(' ', '', ucwords($name)));
        if (is_null($input) || trim($input) === '') {
            $this->errors[$camelCaseName . 'Error'] = "Only numbers are allowed.";
        } elseif (!is_numeric($input)) {
            $this->errors[$camelCaseName . 'Error'] = "Only numbers are allowed.";
        } elseif (strlen($input) > 10) {
            $this->errors[$camelCaseName . 'Error'] = "$name cannot be more than 10 characters.";
        }
        return (isset($this->errors[$camelCaseName . 'Error']) ? null : floatval($input));
    }

    public function validate_specific_values($annualRate, $sbaFeesPercentage) {
        if ($annualRate != self::ANNUAL_RATE_ALLOWED) {
            $this->errors['annualRateError'] = "Annual rate must be " . self::ANNUAL_RATE_ALLOWED . "%";
        }
        if (!in_array($sbaFeesPercentage, self::SBA_FEES_ALLOWED)) {
            $this->errors['sbaFeesError'] = "SBA fees must be either " . implode('% or ', self::SBA_FEES_ALLOWED) . "%";
        }
    }

    public function validate_amount_and_down_payment($amountSeeking, $downPayment) {
        if (!is_null($amountSeeking)) {
            if (preg_match('/[^0-9,$]/', $amountSeeking) || trim($amountSeeking) === '' || preg_match('/^\$\s*$/', $amountSeeking) || (substr_count($amountSeeking, '$') > 1)) {
                $this->errors['amountSeekingError'] = "Only numbers are allowed.";
            } elseif ($amountSeeking < self::MIN_AMOUNT_SEEKING) {
                $this->errors['amountSeekingError'] = "The minimum amount you can request is $" . number_format(self::MIN_AMOUNT_SEEKING) . ".";
            } elseif ($amountSeeking > self::MAX_AMOUNT_SEEKING) {
                $this->errors['amountSeekingError'] = "The maximum amount you can request is $" . number_format(self::MAX_AMOUNT_SEEKING) . ".";
            }
        }

        if (!is_null($downPayment)) {
            if (preg_match('/[^0-9]/', $downPayment) || trim($downPayment) === '' || preg_match('/\$/', $downPayment)) {
                $this->errors['downPaymentError'] = "Only numbers are allowed.";
            } elseif ($downPayment < self::MIN_DOWN_PAYMENT) {
                $this->errors['downPaymentError'] = "The minimum down payment is " . self::MIN_DOWN_PAYMENT . "%";
            } elseif ($downPayment > self::MAX_DOWN_PAYMENT) {
                $this->errors['downPaymentError'] = "The maximum down payment is " . self::MAX_DOWN_PAYMENT . "%";
            }
        }
    }

    public function calculate_monthly_payment($loanAmount, $monthlyRate, $numberOfPayments) {
        return ($loanAmount * $monthlyRate) / (1 - pow(1 + $monthlyRate, -$numberOfPayments));
    }

    public function generate_amortization_schedule($loanAmount, $monthlyRate, $estimatedMonthlyPayment, $numberOfPayments, $startMonth, $startYear) {
        $schedule = [];
        $beginningBalance = $loanAmount;

        for ($paymentNumber = 0; $paymentNumber < $numberOfPayments; $paymentNumber++) {
            $interestPayment = $beginningBalance * $monthlyRate;
            $principalPayment = $estimatedMonthlyPayment - $interestPayment;
            $endingBalance = $beginningBalance - $principalPayment;

            // Correct ending balance to zero if it goes negative due to precision errors
            if ($endingBalance < 0) {
                $endingBalance = 0;
            }

            $currentMonth = $startMonth + $paymentNumber;
            $currentYear = $startYear + floor(($currentMonth - 1) / 12);
            $month = (($currentMonth - 1) % 12) + 1;

            $schedule[] = [
                'year' => $currentYear,
                'month' => $month,
                'beginningBalance' => round($beginningBalance, 2),
                'interest' => round($interestPayment, 2),
                'principal' => round($principalPayment, 2),
                'endingBalance' => round($endingBalance, 2)
            ];

            $beginningBalance = $endingBalance;
        }

        return $schedule;
    }

    public function get_errors() {
        // Return only the first error per input field
        $filteredErrors = [];
        foreach ($this->errors as $key => $error) {
            if (!isset($filteredErrors[$key])) {
                $filteredErrors[$key] = $error;
            }
        }
        return $filteredErrors;
    }

    public function build_response($data) {
        return [
            'status' => 'success',
            'data' => $data
        ];
    }

    public function build_error_response() {
        return [
            'status' => 'error',
            'errors' => $this->get_errors()
        ];
    }
}

$calculator = new LoanCalculator();

// Retrieve POST data
$amountSeeking = filter_input(INPUT_POST, 'amountSeeking', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$termLength = filter_input(INPUT_POST, 'termLength', FILTER_SANITIZE_NUMBER_INT);
$downPayment = filter_input(INPUT_POST, 'downPayment', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$sbaFeesPercentage = filter_input(INPUT_POST, 'sbaFees', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$annualRate = filter_input(INPUT_POST, 'annualRate', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

// Validate inputs
$amountSeeking = $calculator->validate_input($amountSeeking, 'Amount Seeking');
$termLength = $calculator->validate_input($termLength, 'Term Length');
$downPayment = $calculator->validate_input($downPayment, 'Down Payment');
$sbaFeesPercentage = $calculator->validate_input($sbaFeesPercentage, 'SBA Fees');
$annualRate = $calculator->validate_input($annualRate, 'Annual Rate');

// Validate specific values for SBA Fees, Annual Rate, Amount Seeking, and Down Payment
$calculator->validate_specific_values($annualRate, $sbaFeesPercentage);
$calculator->validate_amount_and_down_payment($amountSeeking, $downPayment);

// Check for validation errors
if (!empty($calculator->get_errors())) {
    echo json_encode($calculator->build_error_response());
    exit;
}

// Calculations
$loanAmount = $amountSeeking;
$downPaymentAmount = $loanAmount * ($downPayment / 100);
$loanAfterDownPayment = $loanAmount - $downPaymentAmount;

$monthlyRate = ($annualRate / 100) / 12;
$numberOfPayments = $termLength * 12;

// Calculate Estimated Monthly Payment
$estimatedMonthlyPayment = $calculator->calculate_monthly_payment($loanAfterDownPayment, $monthlyRate, $numberOfPayments);

// Calculate Total Repayment and Total Interest Paid
$totalRepayment = $estimatedMonthlyPayment * $numberOfPayments;
$totalInterestPaid = $totalRepayment - $loanAfterDownPayment;

// Calculate One-Time Fee
$oneTimeFee = $loanAmount * ($sbaFeesPercentage / 100);

// Calculate Total Cost of Loan (Total Repayment + One-Time Fee)
$totalCostOfLoan = $totalRepayment + $oneTimeFee;

// Payoff Date
$payoffDate = date('Y-m-d', strtotime("+$termLength years"));

// Generate Amortization Schedule
$currentMonth = date('n');
$currentYear = date('Y');
$startMonth = $currentMonth + 1;
$startYear = $currentYear;
if ($startMonth > 12) {
    $startMonth = 1;
    $startYear++;
}
$amortizationSchedule = $calculator->generate_amortization_schedule($loanAfterDownPayment, $monthlyRate, $estimatedMonthlyPayment, $numberOfPayments, $startMonth, $startYear);

// Build the response
$data = [
    'estimatedMonthlyPayment' => round($estimatedMonthlyPayment, 2),
    'oneTimeFee' => round($oneTimeFee, 2),
    'downPaymentAmount' => round($downPaymentAmount, 2),
    'totalRepayment' => round($totalRepayment, 2),
    'totalCostOfLoan' => round($totalCostOfLoan, 2),
    'totalInterestPaid' => round($totalInterestPaid, 2),
    'loanAmount' => round($loanAfterDownPayment, 2),
    'payoffDate' => $payoffDate,
    'amortizationSchedule' => $amortizationSchedule
];

echo json_encode($calculator->build_response($data));
?>
