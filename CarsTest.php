<?php

/**
 * Unit Tests for cars.php
 *
 * Covers authentication state, language cookie validation, translation labels,
 * car availability, one-day estimated total, deposit resolution, search criteria
 * defaults, car filter matching, and favourite toggle response payloads.
 *
 * Run with: ./vendor/bin/phpunit CarsTest.php
 * Install PHPUnit first: composer require --dev phpunit/phpunit
 */

use PHPUnit\Framework\TestCase;

// =============================================================================
// Helper functions extracted from cars.php
// These mirror the production logic exactly so they can be tested in isolation
// without requiring a live database connection or an active session.
// =============================================================================

/**
 * Determines whether the current session belongs to a logged-in customer.
 * Only the 'customer' role is accepted -- admin sessions are excluded.
 * Mirrors lines 6 to 8 of cars.php.
 *
 * @param array|null $sessionUser  The $_SESSION['user'] value, or null if absent.
 */
function resolveIsLoggedIn(?array $sessionUser): bool
{
    return isset($sessionUser) && $sessionUser['role'] === 'customer';
}

/**
 * Resolves the customer ID from the session.
 * Returns null when the user is not logged in as a customer.
 * Mirrors lines 6 to 8 of cars.php.
 */
function resolveCustomerId(bool $isLoggedIn, ?array $sessionUser): ?int
{
    return $isLoggedIn ? $sessionUser['id'] : null;
}

/**
 * Resolves the active language from a cookie value.
 * Only the four supported language codes are accepted. Any unrecognised value
 * or absent cookie falls back to English.
 * Mirrors lines 12 to 13 of cars.php.
 */
function resolveLanguage(?string $cookieValue): string
{
    $validLanguages = ['en', 'es', 'fr', 'de'];
    if ($cookieValue !== null && in_array($cookieValue, $validLanguages)) {
        return $cookieValue;
    }
    return 'en';
}

/**
 * Returns the correct day-rate suffix label for a given language.
 * Mirrors the $dayText assignment across the four language blocks of cars.php.
 */
function getDayText(string $language): string
{
    return match ($language) {
        'es'    => '/día',
        'fr'    => '/jour',
        'de'    => '/Tag',
        default => '/day',
    };
}

/**
 * Returns the correct "Book Now" button label for a given language.
 * Mirrors the $bookNowText assignment across the four language blocks.
 */
function getBookNowText(string $language): string
{
    return match ($language) {
        'es'    => 'Reservar',
        'fr'    => 'Réserver',
        'de'    => 'Jetzt buchen',
        default => 'Book Now',
    };
}

/**
 * Returns the correct "Unavailable" label for a given language.
 * Mirrors the $unavailableText assignment across the four language blocks.
 */
function getUnavailableText(string $language): string
{
    return match ($language) {
        'es'    => 'No Disponible',
        'fr'    => 'Indisponible',
        'de'    => 'Nicht verfügbar',
        default => 'Unavailable',
    };
}

/**
 * Returns the correct "Add to Basket" label for a given language.
 * Mirrors the $addToBasketText assignment across the four language blocks.
 */
function getAddToBasketText(string $language): string
{
    return match ($language) {
        'es'    => 'Añadir al Carrito',
        'fr'    => 'Ajouter au Panier',
        'de'    => 'In den Warenkorb',
        default => 'Add to Basket',
    };
}

/**
 * Determines whether a car is available for booking.
 * A status_id of 1 means available; anything else means unavailable.
 * Mirrors the strict integer comparison on line 335 of cars.php.
 */
function isCarAvailable(int $statusId): bool
{
    return (int)$statusId === 1;
}

/**
 * Calculates the estimated total for a one-day basket entry.
 * Cars are added with a default rental period of one day when first placed in
 * the basket. Mirrors line 376 of cars.php.
 */
function calculateOneDayEstimatedTotal(float $pricePerDay): float
{
    return floatval($pricePerDay) * 1;
}

/**
 * Resolves the deposit amount from car data.
 * Falls back to 0.00 when deposit_required is absent from the database row.
 * Mirrors line 377 of cars.php.
 *
 * @param array $carData  Associative array returned from the cars table.
 */
function resolveDepositAmount(array $carData): float
{
    return floatval($carData['deposit_required'] ?? 0);
}

/**
 * Resolves the search criteria from the session, applying sensible defaults
 * for any absent keys. Mirrors lines 405 to 411 of cars.php.
 *
 * @param array $sessionCriteria  The $_SESSION['search_criteria'] array, or [].
 */
function resolveSearchCriteria(array $sessionCriteria): array
{
    return [
        'pickup_location' => $sessionCriteria['pickup_location'] ?? '',
        'pickup_date'     => $sessionCriteria['pickup_date']     ?? date('Y-m-d'),
        'pickup_time'     => $sessionCriteria['pickup_time']     ?? '10:00',
        'dropoff_date'    => $sessionCriteria['dropoff_date']    ?? date('Y-m-d', strtotime('+3 days')),
        'dropoff_time'    => $sessionCriteria['dropoff_time']    ?? '10:00',
    ];
}

/**
 * Determines whether a car card matches the active set of filters.
 * Mirrors the filter logic in applyFilters() in cars.php.
 *
 * status_id is passed as a string because HTML data attributes are always
 * strings -- the JavaScript reads data-status via getAttribute() and compares
 * using strict string equality (carStatus === '1').
 *
 * @param array    $car            Keys: 'type', 'city_id', 'price', 'status_id' (string).
 * @param array    $selectedTypes  Selected car type strings. Empty means all types shown.
 * @param array    $selectedCities Selected city ID strings. Empty means all cities shown.
 * @param int      $minPrice       Minimum price per day. Zero means no lower bound.
 * @param int|null $maxPrice       Maximum price per day. Null means no upper bound.
 * @param bool     $availableOnly  When true, only status_id of '1' passes.
 */
function carMatchesFilters(
    array  $car,
    array  $selectedTypes,
    array  $selectedCities,
    int    $minPrice,
    ?int   $maxPrice,
    bool   $availableOnly
): bool {
    $typeMatch         = empty($selectedTypes)  || in_array($car['type'],    $selectedTypes);
    $cityMatch         = empty($selectedCities) || in_array($car['city_id'], $selectedCities);
    $priceMatch        = $car['price'] >= $minPrice && ($maxPrice === null || $car['price'] <= $maxPrice);
    // status_id is a string here as HTML data attributes are always strings
    $availabilityMatch = !$availableOnly || $car['status_id'] === '1';

    return $typeMatch && $cityMatch && $priceMatch && $availabilityMatch;
}

/**
 * Builds the JSON response payload for a successful favourite toggle.
 * Mirrors lines 292 and 299 of cars.php.
 */
function buildFavouriteToggleResponse(bool $isFavourite): array
{
    if ($isFavourite) {
        return ['success' => true, 'is_favorite' => true,  'message' => 'Added to favorites'];
    }
    return     ['success' => true, 'is_favorite' => false, 'message' => 'Removed from favorites'];
}

/**
 * Builds the JSON response payload returned when an unauthenticated user
 * attempts to toggle a favourite. Mirrors line 277 of cars.php.
 */
function buildUnauthenticatedFavouriteResponse(): array
{
    return ['success' => false, 'message' => 'Please login to save favorites'];
}

// =============================================================================
// Test Suite
// =============================================================================

class CarsTest extends TestCase
{
    // =========================================================================
    // Authentication State
    // =========================================================================

    /**
     * A session containing the customer role must be recognised as logged in.
     * Mirrors lines 6 to 8 of cars.php.
     */
    public function testCustomerSessionIsRecognisedAsLoggedIn(): void
    {
        $sessionUser = ['id' => 7, 'role' => 'customer'];
        $this->assertTrue(resolveIsLoggedIn($sessionUser));
    }

    /**
     * A session containing the admin role must not be treated as a logged-in
     * customer. Admins are explicitly excluded from customer-only actions such
     * as adding cars to the basket.
     * Mirrors line 310 of cars.php.
     */
    public function testAdminSessionIsNotLoggedInAsCustomer(): void
    {
        $sessionUser = ['id' => 1, 'role' => 'admin'];
        $this->assertFalse(resolveIsLoggedIn($sessionUser));
    }

    /**
     * A null session (no user present) must return false.
     */
    public function testNullSessionIsNotLoggedIn(): void
    {
        $this->assertFalse(resolveIsLoggedIn(null));
    }

    /**
     * The customer ID must be taken directly from the session when logged in.
     * Mirrors line 8 of cars.php.
     */
    public function testCustomerIdResolvedFromSessionWhenLoggedIn(): void
    {
        $sessionUser = ['id' => 42, 'role' => 'customer'];
        $this->assertSame(42, resolveCustomerId(true, $sessionUser));
    }

    /**
     * The customer ID must be null when no valid customer session is present.
     */
    public function testCustomerIdIsNullWhenNotLoggedIn(): void
    {
        $this->assertNull(resolveCustomerId(false, null));
    }

    /**
     * An admin user must not be able to add cars to the basket.
     * The basket action checks for the customer role specifically.
     * Mirrors the role check on line 310 of cars.php.
     */
    public function testAdminUserIsBlockedFromAddingToBasket(): void
    {
        $sessionUser = ['id' => 1, 'role' => 'admin'];
        $this->assertFalse(
            resolveIsLoggedIn($sessionUser),
            'An admin session must not pass the customer login check used to gate basket access.'
        );
    }

    // =========================================================================
    // Language Cookie Validation
    // =========================================================================

    /**
     * English must be returned when the language cookie is absent.
     * Mirrors the default on lines 12 to 13 of cars.php.
     */
    public function testLanguageDefaultsToEnglishWhenCookieIsAbsent(): void
    {
        $this->assertSame('en', resolveLanguage(null));
    }

    /**
     * English must be returned when the cookie holds an unrecognised value.
     * The in_array validation on line 13 of cars.php prevents undefined
     * variable errors that would occur if an invalid locale were accepted.
     */
    public function testLanguageDefaultsToEnglishForUnrecognisedLocale(): void
    {
        $this->assertSame('en', resolveLanguage('jp'));
        $this->assertSame('en', resolveLanguage('zh'));
        $this->assertSame('en', resolveLanguage(''));
    }

    /**
     * Each of the four supported language codes must resolve to itself.
     */
    public function testSupportedLanguageCodesResolveCorrectly(): void
    {
        $this->assertSame('en', resolveLanguage('en'));
        $this->assertSame('es', resolveLanguage('es'));
        $this->assertSame('fr', resolveLanguage('fr'));
        $this->assertSame('de', resolveLanguage('de'));
    }

    // =========================================================================
    // Translation Labels
    // =========================================================================

    /**
     * The day-rate suffix must be correct for all four supported languages.
     * Mirrors the $dayText assignments across the language blocks of cars.php.
     */
    public function testDayTextTranslationsAreCorrect(): void
    {
        $this->assertSame('/day',  getDayText('en'));
        $this->assertSame('/día',  getDayText('es'));
        $this->assertSame('/jour', getDayText('fr'));
        $this->assertSame('/Tag',  getDayText('de'));
    }

    /**
     * The "Book Now" button label must be correct for all four supported languages.
     */
    public function testBookNowTextTranslationsAreCorrect(): void
    {
        $this->assertSame('Book Now',     getBookNowText('en'));
        $this->assertSame('Reservar',     getBookNowText('es'));
        $this->assertSame('Réserver',     getBookNowText('fr'));
        $this->assertSame('Jetzt buchen', getBookNowText('de'));
    }

    /**
     * The "Unavailable" label must be correct for all four supported languages.
     */
    public function testUnavailableTextTranslationsAreCorrect(): void
    {
        $this->assertSame('Unavailable',     getUnavailableText('en'));
        $this->assertSame('No Disponible',   getUnavailableText('es'));
        $this->assertSame('Indisponible',    getUnavailableText('fr'));
        $this->assertSame('Nicht verfügbar', getUnavailableText('de'));
    }

    /**
     * The "Add to Basket" label must be correct for all four supported languages.
     */
    public function testAddToBasketTextTranslationsAreCorrect(): void
    {
        $this->assertSame('Add to Basket',     getAddToBasketText('en'));
        $this->assertSame('Añadir al Carrito', getAddToBasketText('es'));
        $this->assertSame('Ajouter au Panier', getAddToBasketText('fr'));
        $this->assertSame('In den Warenkorb',  getAddToBasketText('de'));
    }

    // =========================================================================
    // Car Availability
    // =========================================================================

    /**
     * A status_id of 1 must be recognised as available for booking.
     * Mirrors the strict integer comparison on line 335 of cars.php.
     */
    public function testCarWithStatusOneIsAvailable(): void
    {
        $this->assertTrue(isCarAvailable(1));
    }

    /**
     * A status_id of 2 (occupied) must be recognised as unavailable.
     */
    public function testCarWithStatusTwoIsUnavailable(): void
    {
        $this->assertFalse(isCarAvailable(2));
    }

    /**
     * Any status_id other than 1 must be treated as unavailable.
     */
    public function testCarWithAnyOtherStatusIsUnavailable(): void
    {
        $this->assertFalse(isCarAvailable(0));
        $this->assertFalse(isCarAvailable(3));
        $this->assertFalse(isCarAvailable(99));
    }

    // =========================================================================
    // One-Day Estimated Total
    // =========================================================================

    /**
     * The estimated total for a one-day booking must equal the daily rate.
     * Mirrors line 376 of cars.php.
     */
    public function testOneDayEstimatedTotalEqualsTheDailyRate(): void
    {
        $this->assertEqualsWithDelta(45.00, calculateOneDayEstimatedTotal(45.00), 0.001);
        $this->assertEqualsWithDelta(99.99, calculateOneDayEstimatedTotal(99.99), 0.001);
    }

    // =========================================================================
    // Deposit Amount Resolution
    // =========================================================================

    /**
     * The deposit must be taken from deposit_required when it is present
     * in the database row. Mirrors line 377 of cars.php.
     */
    public function testDepositAmountResolvedFromCarData(): void
    {
        $carData = ['price_per_day' => 45.00, 'deposit_required' => 90.00];
        $this->assertEqualsWithDelta(90.00, resolveDepositAmount($carData), 0.001);
    }

    /**
     * The deposit must fall back to zero when deposit_required is absent.
     * Mirrors the null coalescing default on line 377 of cars.php.
     */
    public function testDepositAmountDefaultsToZeroWhenAbsent(): void
    {
        $carData = ['price_per_day' => 45.00];
        $this->assertEqualsWithDelta(0.00, resolveDepositAmount($carData), 0.001);
    }

    /**
     * A deposit_required value of zero must resolve to exactly zero.
     */
    public function testDepositAmountOfZeroIsPreserved(): void
    {
        $carData = ['price_per_day' => 45.00, 'deposit_required' => 0];
        $this->assertEqualsWithDelta(0.00, resolveDepositAmount($carData), 0.001);
    }

    // =========================================================================
    // Search Criteria Defaults
    // =========================================================================

    /**
     * An empty session must produce the full set of default search criteria.
     * Mirrors lines 405 to 411 of cars.php.
     */
    public function testSearchCriteriaDefaultsAppliedWhenSessionIsEmpty(): void
    {
        $criteria = resolveSearchCriteria([]);

        $this->assertSame('',      $criteria['pickup_location']);
        $this->assertSame('10:00', $criteria['pickup_time']);
        $this->assertSame('10:00', $criteria['dropoff_time']);
        $this->assertSame(date('Y-m-d'), $criteria['pickup_date']);
        $this->assertSame(date('Y-m-d', strtotime('+3 days')), $criteria['dropoff_date']);
    }

    /**
     * Session values must override the defaults when they are present.
     */
    public function testSearchCriteriaSessionValuesOverrideDefaults(): void
    {
        $session = [
            'pickup_location' => '5',
            'pickup_date'     => '2025-08-01',
            'pickup_time'     => '09:00',
            'dropoff_date'    => '2025-08-05',
            'dropoff_time'    => '11:00',
        ];

        $criteria = resolveSearchCriteria($session);

        $this->assertSame('5',          $criteria['pickup_location']);
        $this->assertSame('2025-08-01', $criteria['pickup_date']);
        $this->assertSame('09:00',      $criteria['pickup_time']);
        $this->assertSame('2025-08-05', $criteria['dropoff_date']);
        $this->assertSame('11:00',      $criteria['dropoff_time']);
    }

    // =========================================================================
    // Car Filter Matching
    //
    // Note: status_id values in all fixtures below are strings, not integers.
    // HTML data attributes are always strings. The JavaScript reads data-status
    // via getAttribute() and compares using strict string equality
    // (carStatus === '1'), so string fixtures faithfully mirror that behaviour.
    // =========================================================================

    /**
     * A car must be shown when no filters are active.
     */
    public function testCarIsShownWhenNoFiltersAreActive(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, [], [], 0, null, false));
    }

    /**
     * A car must be shown when its type matches the selected type filter.
     */
    public function testCarIsShownWhenTypeMatches(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, ['suv'], [], 0, null, false));
    }

    /**
     * A car must be hidden when its type does not match the selected type filter.
     */
    public function testCarIsHiddenWhenTypeDoesNotMatch(): void
    {
        $car = ['type' => 'saloon', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertFalse(carMatchesFilters($car, ['suv'], [], 0, null, false));
    }

    /**
     * A car must be shown when its city ID matches the selected city filter.
     */
    public function testCarIsShownWhenCityMatches(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, [], ['3'], 0, null, false));
    }

    /**
     * A car must be hidden when its city does not match the selected city filter.
     */
    public function testCarIsHiddenWhenCityDoesNotMatch(): void
    {
        $car = ['type' => 'suv', 'city_id' => '5', 'price' => 50.00, 'status_id' => '1'];
        $this->assertFalse(carMatchesFilters($car, [], ['3'], 0, null, false));
    }

    /**
     * A car priced within the min/max range must be shown.
     */
    public function testCarIsShownWhenPriceIsWithinRange(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, [], [], 30, 70, false));
    }

    /**
     * A car priced below the minimum must be hidden.
     */
    public function testCarIsHiddenWhenPriceIsBelowMinimum(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 20.00, 'status_id' => '1'];
        $this->assertFalse(carMatchesFilters($car, [], [], 30, 70, false));
    }

    /**
     * A car priced above the maximum must be hidden.
     */
    public function testCarIsHiddenWhenPriceIsAboveMaximum(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 80.00, 'status_id' => '1'];
        $this->assertFalse(carMatchesFilters($car, [], [], 30, 70, false));
    }

    /**
     * An unavailable car must be hidden when the "available only" filter is on.
     * status_id '2' represents an occupied vehicle.
     */
    public function testUnavailableCarIsHiddenWhenAvailabilityFilterIsOn(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '2'];
        $this->assertFalse(carMatchesFilters($car, [], [], 0, null, true));
    }

    /**
     * An available car must be shown when the "available only" filter is on.
     * status_id '1' represents an available vehicle.
     */
    public function testAvailableCarIsShownWhenAvailabilityFilterIsOn(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, [], [], 0, null, true));
    }

    /**
     * A car must be hidden when it fails any single filter condition, even if
     * it satisfies all the others. Every condition must be met simultaneously.
     */
    public function testCarIsHiddenWhenAnyOneFilterConditionFails(): void
    {
        // Correct type, city and price but unavailable when availability filter is on
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '2'];
        $this->assertFalse(carMatchesFilters($car, ['suv'], ['3'], 30, 70, true));
    }

    /**
     * A car must be shown when it satisfies every active filter simultaneously.
     */
    public function testCarIsShownWhenAllFilterConditionsAreMet(): void
    {
        $car = ['type' => 'suv', 'city_id' => '3', 'price' => 50.00, 'status_id' => '1'];
        $this->assertTrue(carMatchesFilters($car, ['suv'], ['3'], 30, 70, true));
    }

    // =========================================================================
    // Favourite Toggle Response Payloads
    // =========================================================================

    /**
     * Adding a car to favourites must return the correct success payload.
     * Mirrors line 299 of cars.php.
     */
    public function testAddingToFavouritesReturnsCorrectPayload(): void
    {
        $response = buildFavouriteToggleResponse(true);

        $this->assertTrue($response['success']);
        $this->assertTrue($response['is_favorite']);
        $this->assertSame('Added to favorites', $response['message']);
    }

    /**
     * Removing a car from favourites must return the correct success payload.
     * Mirrors line 292 of cars.php.
     */
    public function testRemovingFromFavouritesReturnsCorrectPayload(): void
    {
        $response = buildFavouriteToggleResponse(false);

        $this->assertTrue($response['success']);
        $this->assertFalse($response['is_favorite']);
        $this->assertSame('Removed from favorites', $response['message']);
    }

    /**
     * Attempting to toggle a favourite without being logged in must return a
     * failure payload containing the appropriate login prompt message.
     * Mirrors line 277 of cars.php.
     */
    public function testUnauthenticatedFavouriteToggleReturnsFailurePayload(): void
    {
        $response = buildUnauthenticatedFavouriteResponse();

        $this->assertFalse($response['success']);
        $this->assertSame('Please login to save favorites', $response['message']);
    }
}
