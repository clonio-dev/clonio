<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PII Column Pattern Definitions
    |--------------------------------------------------------------------------
    |
    | Each entry maps a pattern (regex) to a PII category name and a
    | recommended anonymization transformation. The matcher checks column
    | names against these patterns to auto-suggest transformations.
    |
    */

    'patterns' => [
        // Email addresses
        [
            'pattern' => '/^(e[-_]?mail|email[-_]?addr(ess)?|user[-_]?email|contact[-_]?email)$/i',
            'name' => 'Email Address',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'safeEmail',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // First name
        [
            'pattern' => '/^(first[-_]?name|given[-_]?name|vorname|prenom)$/i',
            'name' => 'First Name',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'firstName',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Last name
        [
            'pattern' => '/^(last[-_]?name|sur[-_]?name|family[-_]?name|nachname|nom)$/i',
            'name' => 'Last Name',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'lastName',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Full name
        [
            'pattern' => '/^(full[-_]?name|display[-_]?name|name|user[-_]?name|username|nick[-_]?name)$/i',
            'name' => 'Person Name',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'name',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Phone number
        [
            'pattern' => '/^(phone|phone[-_]?number|tel(ephone)?|mobile|cell|fax|contact[-_]?number)$/i',
            'name' => 'Phone Number',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'phoneNumber',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Address fields
        [
            'pattern' => '/^(address|street|street[-_]?address|addr(ess)?[-_]?line[-_]?\d?|postal[-_]?address)$/i',
            'name' => 'Street Address',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'address',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // City
        [
            'pattern' => '/^(city|town|ort|stadt|ville)$/i',
            'name' => 'City',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'city',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Postal/ZIP code
        [
            'pattern' => '/^(zip|zip[-_]?code|postal[-_]?code|postcode|plz)$/i',
            'name' => 'Postal Code',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'postcode',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // IP address
        [
            'pattern' => '/^(ip|ip[-_]?addr(ess)?|client[-_]?ip|remote[-_]?ip|user[-_]?ip)$/i',
            'name' => 'IP Address',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'ipv4',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Password / secret
        [
            'pattern' => '/^(password|passwd|pwd|secret|token|api[-_]?key|access[-_]?token|refresh[-_]?token)$/i',
            'name' => 'Password/Secret',
            'transformation' => [
                'strategy' => 'hash',
                'options' => [
                    'algorithm' => 'sha256',
                    'salt' => '',
                ],
            ],
        ],
        // Date of birth
        [
            'pattern' => '/^(birth[-_]?date|date[-_]?of[-_]?birth|dob|birthday|geburtsdatum)$/i',
            'name' => 'Date of Birth',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'date',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
        // Social security / national ID
        [
            'pattern' => '/^(ssn|social[-_]?security|national[-_]?id|tax[-_]?id|personal[-_]?id)$/i',
            'name' => 'National ID',
            'transformation' => [
                'strategy' => 'hash',
                'options' => [
                    'algorithm' => 'sha256',
                    'salt' => '',
                ],
            ],
        ],
        // Credit card
        [
            'pattern' => '/^(credit[-_]?card|card[-_]?number|cc[-_]?number|payment[-_]?card)$/i',
            'name' => 'Credit Card',
            'transformation' => [
                'strategy' => 'mask',
                'options' => [
                    'visibleChars' => 4,
                    'maskChar' => '*',
                    'preserveFormat' => false,
                ],
            ],
        ],
        // Company
        [
            'pattern' => '/^(company|company[-_]?name|organization|org[-_]?name|firma)$/i',
            'name' => 'Company Name',
            'transformation' => [
                'strategy' => 'fake',
                'options' => [
                    'fakerMethod' => 'company',
                    'fakerMethodArguments' => [],
                ],
            ],
        ],
    ],
];
