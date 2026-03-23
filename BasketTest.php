<?php

/**
 * Unit Tests for basket.php
 *
 * Covers step resolution, rental day calculation, estimated totals, deposit
 * calculation, individual extra pricing, extras totals, basket totals, grand
 * totals, rental details validation, extras grouping by category, payment
 * method validation, one-time extras deduplication, and booking confirmation.
 *
 * Run with: ./vendor/bin/phpunit BasketTest.php
 * Install PHPUnit first: composer require --dev phpunit/phpunit
 */

use PHPUnit\Framework\TestCase;

// ──────────────────────────────────────────────────────────────────────────────
// Helper functions extracted from basket.php
// These mirror the production logic exactly so they can be tested in isolation
// without requiring a live database connection or active session.
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Resolves the current checkout step from a raw GET parameter.
 * Returns 'success' for the success string, or an integer for all other values.
 * When no step is provided, the real code on line 14 of basket.php defaults
 * to the string '1' before intval is ever called.
 * Mirrors lines 14–20 of basket.php.
 */
function resolveStep(mixed $rawStep): string|int
{
    if ($rawStep === 'success') {
        return 'success';
    }
    return intval($rawStep);
}

/**
 * Calculates the number of rental days between two date strings.
 * A minimum of 1 day is enforced even when the dates are identical.
 * Mirrors lines 104–107 and 192–193 of basket.php.
 */
function calculateRentalDays(string $startDate, string $endDate): int
{
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
    $days  = $start->diff($end)->days;
    return $days > 0 ? $days : 1;
}

/**
 * Calculates the estimated total cost for a single basket item.
 * Mirrors line 218 of basket.php.
 */
function calculateEstimatedTotal(float $pricePerDay, int $rentalDays): float
{
    return $pricePerDay * $rentalDays;
}

/**
 * Calculates the deposit amount as 20% of the estimated total.
 * Mirrors line 219 of basket.php.
 */
function calculateDepositAmount(float $estimatedTotal): float
{
    return $estimatedTotal * 0.2;
}

/**
 * Calculates the cost of a single extra for a booking.
 * Per-day extras are multiplied by rental days; one-time extras are a fixed charge.
 * Mirrors lines 371–374 of basket.php.
 */
function calculateExtraPrice(array $extra, int $rentalDays): float
{
    if ($extra['unit'] === 'per day') {
        return $extra['price'] * $rentalDays;
    }
    return $extra['price'];
}

/**
 * Calculates the total cost of all selected extras for a single booking item.
 * One-time extras are only charged once per booking regardless of how many
 * basket items are present — duplicates are tracked and skipped.
 * Mirrors the corrected extras loop in basket.php.
 *
 * @param array $selectedExtraIds   List of selected extra_id values.
 * @param array $allExtras          Full extras catalogue from basket-5.php.
 * @param int   $rentalDays         Number of rental days for the booking item.
 * @param array $oneTimeCharged     Extra IDs that have already been charged as
 *                                  one-time fees in a previous basket item.
 */
function calculateExtrasTotal(
    array $selectedExtraIds,
    array $allExtras,
    int   $rentalDays,
    array &$oneTimeCharged = []
): float {
    $total = 0.0;
    foreach ($selectedExtraIds as $extraId) {
        foreach ($allExtras as $extra) {
            if ($extra['extra_id'] == $extraId) {
                if ($extra['unit'] === 'per day') {
                    $total += $extra['price'] * $rentalDays;
                } elseif (!in_array($extraId, $oneTimeCharged)) {
                    $total += $extra['price'];
                    $oneTimeCharged[] = $extraId;
                }
                break;
            }
        }
    }
    return $total;
}

/**
 * Calculates the grand total by summing the basket total and extras total.
 * Mirrors line 586 of basket.php.
 */
function calculateGrandTotal(float $basketTotal, float $extrasTotal): float
{
    return $basketTotal + $extrasTotal;
}

/**
 * Calculates the running basket total from an array of basket items.
 * Mirrors lines 562–565 of basket.php.
 */
function calculateBasketTotal(array $basketItems): float
{
    $total = 0.0;
    foreach ($basketItems as $item) {
        $total += $item['estimated_total'];
    }
    return $total;
}

/**
 * Validates the rental details form fields submitted at step 2.
 * Location IDs must be non-zero integers; both date strings must be parseable.
 * Returns an error message string, or an empty string if all inputs are valid.
 * Mirrors lines 164–176 of basket.php.
 */
function validateRentalDetails(
    int    $pickupLocation,
    int    $dropoffLocation,
    string $pickupDate,
    string $dropoffDate
): string {
    // Location IDs must be non-zero integers and both dates must be present
    if (empty($pickupLocation) || empty($dropoffLocation) || empty($pickupDate) || empty($dropoffDate)) {
        return 'Please fill in all required fields';
    }

    // Both dates must be parseable by strtotime
    if (strtotime($pickupDate) === false || strtotime($dropoffDate) === false) {
        return 'Please enter valid dates';
    }

    return '';
}

/**
 * Groups the extras catalogue by category.
 * Mirrors lines 46–53 of basket.php.
 *
 * @param array $extras  Flat array of extra items, each with a 'category' key.
 * @return array         Associative array keyed by category name.
 */
function groupExtrasByCategory(array $extras): array
{
    $grouped = [];
    foreach ($extras as $extra) {
        $category = $extra['category'];
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $extra;
    }
    return $grouped;
}

/**
 * Validates the payment method field submitted at step 5.
 * Returns an error message string, or an empty string if valid.
 * Mirrors lines 284–288 of basket.php.
 */
function validatePaymentMethod(string $paymentMethod): string
{
    if (empty($paymentMethod)) {
        return 'Please select a payment method';
    }
    return '';
}

/**
 * Builds the booking confirmation session payload after a successful payment.
 * Mirrors lines 492–497 of basket.php.
 */
function buildBookingConfirmation(
    array  $bookingIds,
    float  $totalAmount,
    string $paymentMethod,
    float  $extrasTotal
): array {
    return [
        'booking_ids'    => $bookingIds,
        'total_amount'   => $totalAmount,
        'payment_method' => $paymentMethod,
        'extras_total'   => $extrasTotal,
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// Shared test fixture — the extras catalogue from basket.php lines 31–44
// ──────────────────────────────────────────────────────────────────────────────

function getTestExtras(): array
{
    return [
        ['extra_id' => 1,  'name' => 'Personal Accident Insurance', 'price' => 12.50, 'category' => 'Protection Products',  'unit' => 'per day'],
        ['extra_id' => 2,  'name' => 'Theft Protection',            'price' => 10.00, 'category' => 'Protection Products',  'unit' => 'per day'],
        ['extra_id' => 3,  'name' => 'Additional Driver',           'price' => 10.00, 'category' => 'Additional Services',  'unit' => 'per day'],
        ['extra_id' => 4,  'name' => 'Young Driver Fee',            'price' => 15.00, 'category' => 'Additional Services',  'unit' => 'per day'],
        ['extra_id' => 5,  'name' => 'Child Seat',                  'price' => 7.50,  'category' => 'Equipment & Services', 'unit' => 'per day'],
        ['extra_id' => 6,  'name' => 'Booster Seat',                'price' => 7.50,  'category' => 'Equipment & Services', 'unit' => 'per day'],
        ['extra_id' => 7,  'name' => 'GPS Navigation',              'price' => 8.00,  'category' => 'Equipment & Services', 'unit' => 'per day'],
        ['extra_id' => 8,  'name' => 'Pre-paid Fuel',               'price' => 60.00, 'category' => 'Equipment & Services', 'unit' => 'one-time'],
        ['extra_id' => 9,  'name' => 'One-Way Rental Fee',          'price' => 45.00, 'category' => 'Equipment & Services', 'unit' => 'one-time'],
        ['extra_id' => 10, 'name' => 'Out-of-Hours Service',        'price' => 25.00, 'category' => 'Equipment & Services', 'unit' => 'one-time'],
        ['extra_id' => 11, 'name' => 'Winter Tyres',                'price' => 12.00, 'category' => 'Equipment & Services', 'unit' => 'per day'],
        ['extra_id' => 12, 'name' => 'Roadside Assistance',         'price' => 7.00,  'category' => 'Equipment & Services', 'unit' => 'per day'],
    ];
}

// ──────────────────────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────────────────────

class BasketTest extends TestCase
{
    // ── Step Resolution ───────────────────────────────────────────────────────

    /**
     * The string 'success' must be returned as-is without being cast to an integer.
     */
    public function testSuccessStepIsReturnedAsString(): void
    {
        $this->assertSame('success', resolveStep('success'));
    }

    /**
     * A numeric string should be cast to its integer equivalent.
     */
    public function testNumericStepIsCastToInteger(): void
    {
        $this->assertSame(1, resolveStep('1'));
        $this->assertSame(3, resolveStep('3'));
        $this->assertSame(5, resolveStep('5'));
    }

    /**
     * An empty string step resolves to zero via intval.
     * Note: when no step is provided in the URL, the real code on line 14 of
     * basket.php defaults to the string '1' before intval is ever called,
     * so zero can never occur in production.
     */
    public function testEmptyStepResolvesToZero(): void
    {
        $this->assertSame(0, resolveStep(''));
    }

    /**
     * The real default when no step is present in the URL is '1', which
     * resolves to the integer 1 after intval is applied.
     * Mirrors the default on line 14 of basket.php.
     */
    public function testDefaultStepResolvesToOne(): void
    {
        $this->assertSame(1, resolveStep('1'));
    }

    // ── Rental Day Calculation ────────────────────────────────────────────────

    /**
     * A standard multi-day rental should return the correct number of days.
     */
    public function testRentalDaysCalculatedCorrectly(): void
    {
        $this->assertSame(3, calculateRentalDays('2025-06-01', '2025-06-04'));
        $this->assertSame(7, calculateRentalDays('2025-06-01', '2025-06-08'));
    }

    /**
     * Identical start and end dates represent a same-day rental.
     * The minimum of 1 day must be enforced as per line 107 of basket.php.
     */
    public function testSameDayRentalEnforcesMinimumOfOneDay(): void
    {
        $this->assertSame(1, calculateRentalDays('2025-06-01', '2025-06-01'));
    }

    /**
     * A single overnight rental spanning exactly one calendar day should return 1.
     */
    public function testSingleDayRentalReturnsOne(): void
    {
        $this->assertSame(1, calculateRentalDays('2025-06-01', '2025-06-02'));
    }

    /**
     * A rental spanning a full calendar month should return the correct day count.
     */
    public function testMonthLongRentalCalculatedCorrectly(): void
    {
        $this->assertSame(30, calculateRentalDays('2025-06-01', '2025-07-01'));
    }

    // ── Estimated Total Calculation ───────────────────────────────────────────

    /**
     * The estimated total must equal the daily rate multiplied by rental days.
     * Mirrors line 218 of basket.php.
     */
    public function testEstimatedTotalCalculatedCorrectly(): void
    {
        $this->assertEqualsWithDelta(150.00, calculateEstimatedTotal(50.00, 3), 0.001);
        $this->assertEqualsWithDelta(350.00, calculateEstimatedTotal(50.00, 7), 0.001);
    }

    /**
     * A single-day rental should return exactly the daily rate.
     */
    public function testEstimatedTotalForOneDayEqualsDailyRate(): void
    {
        $this->assertEqualsWithDelta(75.00, calculateEstimatedTotal(75.00, 1), 0.001);
    }

    // ── Deposit Amount Calculation ────────────────────────────────────────────

    /**
     * The deposit must be exactly 20% of the estimated total.
     * Mirrors line 219 of basket.php.
     */
    public function testDepositIsExactlyTwentyPercent(): void
    {
        $this->assertEqualsWithDelta(30.00,  calculateDepositAmount(150.00), 0.001);
        $this->assertEqualsWithDelta(70.00,  calculateDepositAmount(350.00), 0.001);
        $this->assertEqualsWithDelta(100.00, calculateDepositAmount(500.00), 0.001);
    }

    /**
     * The deposit for a zero total should be zero.
     */
    public function testDepositForZeroTotalIsZero(): void
    {
        $this->assertEqualsWithDelta(0.00, calculateDepositAmount(0.00), 0.001);
    }

    // ── Individual Extra Pricing ──────────────────────────────────────────────

    /**
     * A per-day extra must be multiplied by the number of rental days.
     */
    public function testPerDayExtraIsMultipliedByRentalDays(): void
    {
        $extra = ['extra_id' => 1, 'name' => 'Personal Accident Insurance', 'price' => 12.50, 'unit' => 'per day'];
        $this->assertEqualsWithDelta(37.50, calculateExtraPrice($extra, 3), 0.001);
        $this->assertEqualsWithDelta(87.50, calculateExtraPrice($extra, 7), 0.001);
    }

    /**
     * A one-time extra must always cost its fixed price regardless of rental duration.
     */
    public function testOneTimeExtraIsFixedRegardlessOfRentalDays(): void
    {
        $extra = ['extra_id' => 8, 'name' => 'Pre-paid Fuel', 'price' => 60.00, 'unit' => 'one-time'];
        $this->assertEqualsWithDelta(60.00, calculateExtraPrice($extra, 1),  0.001);
        $this->assertEqualsWithDelta(60.00, calculateExtraPrice($extra, 7),  0.001);
        $this->assertEqualsWithDelta(60.00, calculateExtraPrice($extra, 30), 0.001);
    }

    /**
     * The one-way rental fee is a one-time charge and must not scale with days.
     */
    public function testOneWayRentalFeeIsFixedCharge(): void
    {
        $extra = ['extra_id' => 9, 'name' => 'One-Way Rental Fee', 'price' => 45.00, 'unit' => 'one-time'];
        $this->assertEqualsWithDelta(45.00, calculateExtraPrice($extra, 5), 0.001);
    }

    // ── Extras Total — Single Basket Item ────────────────────────────────────

    /**
     * No selected extras should produce a total of zero.
     */
    public function testExtrasTotalIsZeroWhenNoneSelected(): void
    {
        $charged = [];
        $this->assertEqualsWithDelta(0.00, calculateExtrasTotal([], getTestExtras(), 3, $charged), 0.001);
    }

    /**
     * Selecting a single per-day extra should return its daily rate times rental days.
     * Extra ID 7: GPS Navigation £8.00 per day × 3 days = £24.00
     */
    public function testExtrasTotalWithSinglePerDayExtra(): void
    {
        $charged = [];
        $this->assertEqualsWithDelta(24.00, calculateExtrasTotal([7], getTestExtras(), 3, $charged), 0.001);
    }

    /**
     * Selecting a single one-time extra should return its fixed price.
     * Extra ID 8: Pre-paid Fuel £60.00 one-time
     */
    public function testExtrasTotalWithSingleOneTimeExtra(): void
    {
        $charged = [];
        $this->assertEqualsWithDelta(60.00, calculateExtrasTotal([8], getTestExtras(), 5, $charged), 0.001);
    }

    /**
     * A mix of per-day and one-time extras should be summed correctly.
     * GPS Navigation (£8.00 × 3 days) + Pre-paid Fuel (£60.00 one-time) = £84.00
     */
    public function testExtrasTotalWithMixedExtras(): void
    {
        $charged = [];
        $this->assertEqualsWithDelta(84.00, calculateExtrasTotal([7, 8], getTestExtras(), 3, $charged), 0.001);
    }

    /**
     * Selecting multiple per-day extras should multiply each by rental days.
     * Personal Accident Insurance (£12.50 × 5) + Theft Protection (£10.00 × 5) = £112.50
     */
    public function testExtrasTotalWithMultiplePerDayExtras(): void
    {
        $charged = [];
        $this->assertEqualsWithDelta(112.50, calculateExtrasTotal([1, 2], getTestExtras(), 5, $charged), 0.001);
    }

    // ── One-Time Extras Deduplication (multi-item basket) ────────────────────

    /**
     * A one-time extra must only be charged once across multiple basket items.
     * If Pre-paid Fuel (£60.00) is selected and there are two basket items,
     * the total charge must still be £60.00 — not £120.00.
     * This guards against the overcharging bug fixed in basket.php.
     */
    public function testOneTimeExtraIsOnlyChargedOnceAcrossMultipleItems(): void
    {
        $charged = [];

        // First basket item — one-time extra should be charged here
        $firstItemTotal = calculateExtrasTotal([8], getTestExtras(), 3, $charged);
        $this->assertEqualsWithDelta(60.00, $firstItemTotal, 0.001);

        // Second basket item — same one-time extra must NOT be charged again
        $secondItemTotal = calculateExtrasTotal([8], getTestExtras(), 3, $charged);
        $this->assertEqualsWithDelta(0.00, $secondItemTotal, 0.001);
    }

    /**
     * Per-day extras must still be charged for every basket item even when
     * a one-time extra is skipped on subsequent items.
     * GPS Navigation (£8.00 × 3) is per-day so it applies to both items.
     * Pre-paid Fuel (£60.00) is one-time so it applies to the first item only.
     * Total across two items: (£24.00 + £60.00) + (£24.00 + £0.00) = £108.00
     */
    public function testPerDayExtrasStillApplyToEachItemWhenOneTimeIsSkipped(): void
    {
        $charged = [];

        $firstItemTotal  = calculateExtrasTotal([7, 8], getTestExtras(), 3, $charged);
        $secondItemTotal = calculateExtrasTotal([7, 8], getTestExtras(), 3, $charged);

        $this->assertEqualsWithDelta(84.00,  $firstItemTotal,  0.001);
        $this->assertEqualsWithDelta(24.00,  $secondItemTotal, 0.001);
        $this->assertEqualsWithDelta(108.00, $firstItemTotal + $secondItemTotal, 0.001);
    }

    /**
     * Multiple one-time extras must each only be charged once across all items.
     * Pre-paid Fuel (£60.00) + One-Way Rental Fee (£45.00) = £105.00 total,
     * regardless of how many basket items are present.
     */
    public function testMultipleOneTimeExtrasEachChargedOnlyOnce(): void
    {
        $charged = [];

        $firstItemTotal  = calculateExtrasTotal([8, 9], getTestExtras(), 3, $charged);
        $secondItemTotal = calculateExtrasTotal([8, 9], getTestExtras(), 3, $charged);

        $this->assertEqualsWithDelta(105.00, $firstItemTotal,  0.001);
        $this->assertEqualsWithDelta(0.00,   $secondItemTotal, 0.001);
    }

    // ── Basket Total Calculation ──────────────────────────────────────────────

    /**
     * The basket total must be the sum of all item estimated totals.
     */
    public function testBasketTotalSumsAllItemEstimatedTotals(): void
    {
        $items = [
            ['estimated_total' => 150.00],
            ['estimated_total' => 200.00],
        ];
        $this->assertEqualsWithDelta(350.00, calculateBasketTotal($items), 0.001);
    }

    /**
     * An empty basket should produce a total of zero.
     */
    public function testBasketTotalIsZeroForEmptyBasket(): void
    {
        $this->assertEqualsWithDelta(0.00, calculateBasketTotal([]), 0.001);
    }

    /**
     * A basket containing a single item should return that item's estimated total.
     */
    public function testBasketTotalWithSingleItem(): void
    {
        $items = [['estimated_total' => 87.50]];
        $this->assertEqualsWithDelta(87.50, calculateBasketTotal($items), 0.001);
    }

    // ── Grand Total Calculation ───────────────────────────────────────────────

    /**
     * The grand total must equal basket total plus extras total.
     * Mirrors line 586 of basket.php.
     */
    public function testGrandTotalIsBasketPlusExtras(): void
    {
        $this->assertEqualsWithDelta(434.00, calculateGrandTotal(350.00, 84.00), 0.001);
    }

    /**
     * A grand total with no extras selected should equal the basket total.
     */
    public function testGrandTotalWithNoExtrasEqualsBasketTotal(): void
    {
        $this->assertEqualsWithDelta(200.00, calculateGrandTotal(200.00, 0.00), 0.001);
    }

    // ── Rental Details Validation ─────────────────────────────────────────────

    /**
     * All four required fields being present and valid should pass validation.
     */
    public function testRentalDetailsPassesWithAllValidInputs(): void
    {
        $error = validateRentalDetails(1, 2, '2025-06-01', '2025-06-05');
        $this->assertSame('', $error);
    }

    /**
     * A pickup location of zero (not selected or absent from the POST data)
     * should be rejected with the required fields message.
     */
    public function testRentalDetailsFailsWhenPickupLocationIsNotSelected(): void
    {
        $error = validateRentalDetails(0, 2, '2025-06-01', '2025-06-05');
        $this->assertSame('Please fill in all required fields', $error);
    }

    /**
     * A dropoff location of zero (not selected or absent from the POST data)
     * should be rejected with the required fields message.
     */
    public function testRentalDetailsFailsWhenDropoffLocationIsNotSelected(): void
    {
        $error = validateRentalDetails(1, 0, '2025-06-01', '2025-06-05');
        $this->assertSame('Please fill in all required fields', $error);
    }

    /**
     * A pickup date that is empty or absent should be rejected with the
     * required fields message.
     */
    public function testRentalDetailsFailsWhenPickupDateIsAbsent(): void
    {
        $error = validateRentalDetails(1, 2, '', '2025-06-05');
        $this->assertSame('Please fill in all required fields', $error);
    }

    /**
     * A dropoff date that is empty or absent should be rejected with the
     * required fields message.
     */
    public function testRentalDetailsFailsWhenDropoffDateIsAbsent(): void
    {
        $error = validateRentalDetails(1, 2, '2025-06-01', '');
        $this->assertSame('Please fill in all required fields', $error);
    }

    // ── Extras Grouping by Category ───────────────────────────────────────────

    /**
     * The extras catalogue should be grouped into exactly three categories.
     * Mirrors lines 46–53 of basket.php.
     */
    public function testExtrasAreGroupedIntoThreeCategories(): void
    {
        $grouped = groupExtrasByCategory(getTestExtras());
        $this->assertCount(3, $grouped);
        $this->assertArrayHasKey('Protection Products',  $grouped);
        $this->assertArrayHasKey('Additional Services',  $grouped);
        $this->assertArrayHasKey('Equipment & Services', $grouped);
    }

    /**
     * The Protection Products category should contain exactly 2 extras.
     */
    public function testProtectionProductsCategoryHasTwoExtras(): void
    {
        $grouped = groupExtrasByCategory(getTestExtras());
        $this->assertCount(2, $grouped['Protection Products']);
    }

    /**
     * The Additional Services category should contain exactly 2 extras.
     */
    public function testAdditionalServicesCategoryHasTwoExtras(): void
    {
        $grouped = groupExtrasByCategory(getTestExtras());
        $this->assertCount(2, $grouped['Additional Services']);
    }

    /**
     * The Equipment & Services category should contain exactly 8 extras.
     */
    public function testEquipmentAndServicesCategoryHasEightExtras(): void
    {
        $grouped = groupExtrasByCategory(getTestExtras());
        $this->assertCount(8, $grouped['Equipment & Services']);
    }

    /**
     * An empty extras list should produce an empty grouped array.
     */
    public function testGroupingEmptyExtrasReturnsEmptyArray(): void
    {
        $this->assertSame([], groupExtrasByCategory([]));
    }

    // ── Payment Method Validation ─────────────────────────────────────────────

    /**
     * An empty payment method should be rejected.
     * Mirrors lines 284–288 of basket.php.
     */
    public function testPaymentMethodFailsWhenEmpty(): void
    {
        $error = validatePaymentMethod('');
        $this->assertSame('Please select a payment method', $error);
    }

    /**
     * A recognised payment method string should pass validation.
     */
    public function testPaymentMethodPassesWithValidValue(): void
    {
        $this->assertSame('', validatePaymentMethod('card'));
        $this->assertSame('', validatePaymentMethod('cash'));
    }

    // ── Booking Confirmation Payload ──────────────────────────────────────────

    /**
     * The booking confirmation array must contain all four required keys with
     * the correct values, matching the session payload built on lines 492–497
     * of basket.php.
     */
    public function testBookingConfirmationPayloadIsBuiltCorrectly(): void
    {
        $confirmation = buildBookingConfirmation([101, 102], 434.00, 'card', 84.00);

        $this->assertSame([101, 102], $confirmation['booking_ids']);
        $this->assertEqualsWithDelta(434.00, $confirmation['total_amount'],  0.001);
        $this->assertSame('card',            $confirmation['payment_method']);
        $this->assertEqualsWithDelta(84.00,  $confirmation['extras_total'],  0.001);
    }

    /**
     * A confirmation with no extras should record an extras total of zero.
     */
    public function testBookingConfirmationWithNoExtrasHasZeroExtrasTotal(): void
    {
        $confirmation = buildBookingConfirmation([55], 200.00, 'cash', 0.00);

        $this->assertEqualsWithDelta(0.00,   $confirmation['extras_total'],  0.001);
        $this->assertEqualsWithDelta(200.00, $confirmation['total_amount'],  0.001);
    }
}
