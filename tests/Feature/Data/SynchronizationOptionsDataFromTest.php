<?php

declare(strict_types=1);

use App\Data\ColumnMutationStrategyEnum;
use App\Data\RowSelectionStrategyEnum;
use App\Data\SynchronizationOptionsData;

it('returns defaults when config is null', function (): void {
    $options = SynchronizationOptionsData::from(null);

    expect($options->keepUnknownTablesOnTarget)->toBeTrue()
        ->and($options->tableAnonymizationOptions)->toBeNull();
});

it('returns defaults when config has no tables', function (): void {
    $options = SynchronizationOptionsData::from([
        'keepUnknownTablesOnTarget' => false,
    ]);

    expect($options->keepUnknownTablesOnTarget)->toBeFalse()
        ->and($options->tableAnonymizationOptions)->toBeNull();
});

it('parses keepUnknownTablesOnTarget from config', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'email', 'strategy' => 'fake', 'options' => ['fakerMethod' => 'email']],
                ],
            ],
        ],
        'keepUnknownTablesOnTarget' => false,
    ]);

    expect($options->keepUnknownTablesOnTarget)->toBeFalse();
});

it('defaults keepUnknownTablesOnTarget to true when not set', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'email', 'strategy' => 'fake', 'options' => []],
                ],
            ],
        ],
    ]);

    expect($options->keepUnknownTablesOnTarget)->toBeTrue();
});

it('parses row selection per table', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'logs',
                'columnMutations' => [],
                'rowSelection' => [
                    'strategy' => 'first_x',
                    'limit' => 500,
                    'sortColumn' => 'id',
                ],
            ],
        ],
    ]);

    $tableOptions = $options->getAnonymizationOptionsForTable('logs');
    expect($tableOptions)->not->toBeNull()
        ->and($tableOptions->rowSelection)->not->toBeNull()
        ->and($tableOptions->rowSelection->strategy)->toBe(RowSelectionStrategyEnum::FirstX)
        ->and($tableOptions->rowSelection->limit)->toBe(500)
        ->and($tableOptions->rowSelection->sortColumn)->toBe('id');
});

it('creates table entry for tables with only row selection and no column mutations', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'events',
                'columnMutations' => [],
                'rowSelection' => [
                    'strategy' => 'last_x',
                    'limit' => 100,
                ],
            ],
        ],
    ]);

    $tableOptions = $options->getAnonymizationOptionsForTable('events');
    expect($tableOptions)->not->toBeNull()
        ->and($tableOptions->columnMutations)->toBeEmpty()
        ->and($tableOptions->rowSelection->strategy)->toBe(RowSelectionStrategyEnum::LastX)
        ->and($tableOptions->rowSelection->limit)->toBe(100)
        ->and($tableOptions->rowSelection->sortColumn)->toBeNull();
});

it('parses both column mutations and row selection together', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'email', 'strategy' => 'fake', 'options' => ['fakerMethod' => 'email']],
                    ['columnName' => 'name', 'strategy' => 'mask', 'options' => ['visibleChars' => 2]],
                ],
                'rowSelection' => [
                    'strategy' => 'first_x',
                    'limit' => 1000,
                    'sortColumn' => 'id',
                ],
            ],
        ],
    ]);

    $tableOptions = $options->getAnonymizationOptionsForTable('users');
    expect($tableOptions)->not->toBeNull()
        ->and($tableOptions->columnMutations)->toHaveCount(2)
        ->and($tableOptions->columnMutations->first()->strategy)->toBe(ColumnMutationStrategyEnum::FAKE)
        ->and($tableOptions->rowSelection->strategy)->toBe(RowSelectionStrategyEnum::FirstX);
});

it('maintains backward compatibility with config without rowSelection', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'users',
                'columnMutations' => [
                    ['columnName' => 'email', 'strategy' => 'fake', 'options' => ['fakerMethod' => 'email']],
                ],
            ],
        ],
        'version' => '1.0',
    ]);

    $tableOptions = $options->getAnonymizationOptionsForTable('users');
    expect($tableOptions)->not->toBeNull()
        ->and($tableOptions->rowSelection)->toBeNull()
        ->and($tableOptions->columnMutations)->toHaveCount(1);
});

it('skips tables with no column mutations and no row selection', function (): void {
    $options = SynchronizationOptionsData::from([
        'tables' => [
            [
                'tableName' => 'empty_table',
                'columnMutations' => [
                    ['columnName' => 'id', 'strategy' => 'keep', 'options' => []],
                ],
            ],
        ],
    ]);

    expect($options->getAnonymizationOptionsForTable('empty_table'))->toBeNull();
});
