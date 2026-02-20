<?php

declare(strict_types=1);

use App\Data\SynchronizationOptionsData;

it('parses enforceColumnTypes from config', function (): void {
    $config = [
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [],
                'enforceColumnTypes' => true,
            ],
            [
                'tableName' => 'posts',
                'columnMutations' => [
                    ['columnName' => 'title', 'strategy' => 'fake', 'options' => ['fakerMethod' => 'sentence']],
                ],
            ],
        ],
        'keepUnknownTablesOnTarget' => true,
        'version' => '1.0',
    ];

    $options = SynchronizationOptionsData::from($config);

    // users table should be present because enforceColumnTypes is true
    $usersOptions = $options->getAnonymizationOptionsForTable('users');
    expect($usersOptions)->not->toBeNull();
    expect($usersOptions->enforceColumnTypes)->toBeTrue();

    // posts table should be present because it has column mutations
    $postsOptions = $options->getAnonymizationOptionsForTable('posts');
    expect($postsOptions)->not->toBeNull();
    expect($postsOptions->enforceColumnTypes)->toBeFalse();
});

it('defaults enforceColumnTypes to false when not specified', function (): void {
    $config = [
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'email', 'strategy' => 'mask', 'options' => ['visibleChars' => 2, 'maskChar' => '*']],
                ],
            ],
        ],
        'keepUnknownTablesOnTarget' => true,
        'version' => '1.0',
    ];

    $options = SynchronizationOptionsData::from($config);

    $usersOptions = $options->getAnonymizationOptionsForTable('users');
    expect($usersOptions)->not->toBeNull();
    expect($usersOptions->enforceColumnTypes)->toBeFalse();
});

it('only stores non-keep column mutations', function (): void {
    $config = [
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'id', 'strategy' => 'keep', 'options' => []],
                    ['columnName' => 'email', 'strategy' => 'fake', 'options' => ['fakerMethod' => 'email']],
                    ['columnName' => 'name', 'strategy' => 'keep', 'options' => []],
                ],
            ],
        ],
        'keepUnknownTablesOnTarget' => true,
        'version' => '1.0',
    ];

    $options = SynchronizationOptionsData::from($config);

    $usersOptions = $options->getAnonymizationOptionsForTable('users');
    expect($usersOptions)->not->toBeNull();
    // Only email should be in column mutations (keep is filtered out)
    expect($usersOptions->columnMutations)->toHaveCount(1);
    expect($usersOptions->columnMutations->first()->columnName)->toBe('email');
});
