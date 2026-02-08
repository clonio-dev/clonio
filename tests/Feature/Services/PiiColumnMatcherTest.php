<?php

declare(strict_types=1);

use App\Services\PiiColumnMatcher;

it('matches email column', function (): void {
    $matcher = new PiiColumnMatcher;
    $result = $matcher->match('email');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('Email Address')
        ->and($result['transformation']['strategy'])->toBe('fake')
        ->and($result['transformation']['options']['fakerMethod'])->toBe('safeEmail');
});

it('matches first_name column', function (): void {
    $matcher = new PiiColumnMatcher;
    $result = $matcher->match('first_name');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('First Name');
});

it('matches case insensitively', function (): void {
    $matcher = new PiiColumnMatcher;

    expect($matcher->match('EMAIL'))->not->toBeNull()
        ->and($matcher->match('Email'))->not->toBeNull()
        ->and($matcher->match('FIRST_NAME'))->not->toBeNull();
});

it('does not match non-PII columns', function (): void {
    $matcher = new PiiColumnMatcher;

    expect($matcher->match('id'))->toBeNull()
        ->and($matcher->match('created_at'))->toBeNull()
        ->and($matcher->match('status'))->toBeNull()
        ->and($matcher->match('amount'))->toBeNull();
});

it('matches multiple columns at once', function (): void {
    $matcher = new PiiColumnMatcher;
    $results = $matcher->matchColumns(['id', 'email', 'first_name', 'status', 'phone']);

    expect($results)->toHaveCount(3)
        ->and($results)->toHaveKeys(['email', 'first_name', 'phone']);
});

it('matches password columns with hash strategy', function (): void {
    $matcher = new PiiColumnMatcher;
    $result = $matcher->match('password');

    expect($result)->not->toBeNull()
        ->and($result['transformation']['strategy'])->toBe('hash');
});

it('matches credit card with mask strategy', function (): void {
    $matcher = new PiiColumnMatcher;
    $result = $matcher->match('credit_card');

    expect($result)->not->toBeNull()
        ->and($result['transformation']['strategy'])->toBe('mask');
});

it('matches various email column name variants', function (): void {
    $matcher = new PiiColumnMatcher;

    expect($matcher->match('e_mail'))->not->toBeNull()
        ->and($matcher->match('email_address'))->not->toBeNull()
        ->and($matcher->match('user_email'))->not->toBeNull()
        ->and($matcher->match('contact_email'))->not->toBeNull();
});

it('matches address-related columns', function (): void {
    $matcher = new PiiColumnMatcher;

    expect($matcher->match('address'))->not->toBeNull()
        ->and($matcher->match('address')['name'])->toBe('Street Address')
        ->and($matcher->match('city'))->not->toBeNull()
        ->and($matcher->match('city')['name'])->toBe('City')
        ->and($matcher->match('zip_code'))->not->toBeNull()
        ->and($matcher->match('zip_code')['name'])->toBe('Postal Code');
});

it('matches date of birth columns', function (): void {
    $matcher = new PiiColumnMatcher;

    expect($matcher->match('birth_date'))->not->toBeNull()
        ->and($matcher->match('dob'))->not->toBeNull()
        ->and($matcher->match('geburtsdatum'))->not->toBeNull();
});

it('matches national ID columns with hash strategy', function (): void {
    $matcher = new PiiColumnMatcher;
    $result = $matcher->match('ssn');

    expect($result)->not->toBeNull()
        ->and($result['name'])->toBe('National ID')
        ->and($result['transformation']['strategy'])->toBe('hash');
});

it('returns empty array when no columns match', function (): void {
    $matcher = new PiiColumnMatcher;
    $results = $matcher->matchColumns(['id', 'created_at', 'updated_at', 'status']);

    expect($results)->toBeEmpty();
});
