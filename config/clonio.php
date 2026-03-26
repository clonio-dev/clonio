<?php

declare(strict_types=1);

return [
    'audit_secret' => env('AUDIT_SECRET'),

    /**
     * We have 2 application modes: `marketing` and `application`.
     *
     * The application mode presents the application with auth and clonings stuff.
     * The marketing mode presents just the marketing home page.
     *
     * Both provide the technical documentation.
     */
    'mode' => env('APP_MODE', 'application'),

    /**
     * PII detection patterns used to auto-suggest identifier remapping.
     *
     * When a table name or any of its column names contains one of these
     * substrings (case-insensitive), the primary key of that table is
     * automatically suggested for remapping during cloning configuration.
     *
     * Patterns are derived from HIPAA Safe Harbor, GDPR, and PCI DSS
     * compliance requirements. Add or remove entries to match your data model.
     */
    'pii' => [
        'table_patterns' => [
            // English entity names
            'user', 'patient', 'customer', 'person', 'member', 'employee',
            'profile', 'contact', 'account', 'people', 'client', 'subscriber',
            'beneficiary', 'insured', 'applicant', 'candidate', 'visitor', 'guest',
            // German entity names
            'kunde', 'mitarbeiter', 'benutzer', 'versicherter', 'bewerber',
        ],

        'column_patterns' => [
            // Names
            'name', 'vorname', 'nachname', 'maiden',
            // Contact
            'email', 'phone', 'telefon', 'handy', 'mobile', 'fax',
            // Address
            'address', 'adresse', 'street', 'strasse', 'city', 'zip', 'postal', 'plz',
            // Dates
            'birth', 'geburt', 'dob', 'admission', 'discharge', 'death', 'sterbe',
            // Government IDs
            'ssn', 'social_security', 'personalausweis', 'ausweis', 'passport',
            // Medical
            'mrn', 'medical_record', 'health', 'krankenkasse', 'versicherung',
            // Financial
            'iban', 'card_number', 'cc_number', 'cvv', 'cvc',
            // Licenses
            'license', 'certificate', 'führerschein', 'kennzeichen',
            // Biometric / image
            'biometric', 'fingerprint', 'retinal', 'photo', 'portrait',
            // Sensitive categories
            'race', 'ethnicity', 'religion', 'political', 'genetic', 'criminal',
        ],
    ],
];
