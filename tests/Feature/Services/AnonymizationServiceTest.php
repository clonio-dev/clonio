<?php

declare(strict_types=1);

use App\Data\ColumnMutationData;
use App\Data\ColumnMutationDataOptions;
use App\Data\ColumnMutationStrategyEnum;
use App\Data\TableAnonymizationOptionsData;
use App\Services\AnonymizationService;

it('returns record unchanged when no table options provided', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $result = $service->anonymizeRecord($record, null);

    expect($result)->toBe($record);
});

it('applies fake strategy to generate fake data', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'name')),
            new ColumnMutationData('email', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'email')),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['name'])->not->toBe('John Doe')
        ->and($result['email'])->not->toBe('john@example.com');
});

it('applies fake strategy with arguments', function (): void {
    $service = new AnonymizationService();
    $record = ['description' => 'original'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'posts',
        columnMutations: collect([
            new ColumnMutationData('description', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'sentence', fakerMethodArguments: [5])),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['description'])->not->toBe('original')
        ->and($result['description'])->toBeString();
});

it('applies mask strategy to mask data', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => 'JohnDoe'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::MASK, new ColumnMutationDataOptions(visibleChars: 2)),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['name'])->toBe('Jo*****');
});

it('applies mask strategy with custom mask character', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => 'JohnDoe'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::MASK, new ColumnMutationDataOptions(visibleChars: 3, maskChar: '#')),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['name'])->toBe('Joh####');
});

it('applies mask strategy to email preserving format', function (): void {
    $service = new AnonymizationService();
    $record = ['email' => 'john.doe@example.com'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('email', ColumnMutationStrategyEnum::MASK, new ColumnMutationDataOptions(visibleChars: 2, preserveFormat: true)),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['email'])->toBe('jo******@example.com');
});

it('applies mask strategy to null values', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => null];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::MASK),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['name'])->toBe('');
});

it('applies hash strategy to hash data', function (): void {
    $service = new AnonymizationService();
    $record = ['password' => 'secret123'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('password', ColumnMutationStrategyEnum::HASH),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['password'])->not->toBe('secret123')
        ->and($result['password'])->toHaveLength(64);
});

it('applies hash strategy with custom algorithm', function (): void {
    $service = new AnonymizationService();
    $record = ['password' => 'secret123'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('password', ColumnMutationStrategyEnum::HASH, new ColumnMutationDataOptions(algorithm: 'md5')),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['password'])->not->toBe('secret123')
        ->and($result['password'])->toHaveLength(32);
});

it('applies hash strategy with salt', function (): void {
    $service = new AnonymizationService();
    $record = ['password' => 'secret123'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('password', ColumnMutationStrategyEnum::HASH, new ColumnMutationDataOptions(salt: 'mysalt')),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['password'])->toBe(hash('sha256', 'mysaltsecret123'));
});

it('applies hash strategy to null values', function (): void {
    $service = new AnonymizationService();
    $record = ['password' => null];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('password', ColumnMutationStrategyEnum::HASH),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['password'])->toBe('');
});

it('applies null strategy to set column to null', function (): void {
    $service = new AnonymizationService();
    $record = ['notes' => 'Some private notes'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('notes', ColumnMutationStrategyEnum::NULL),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['notes'])->toBeNull();
});

it('applies static strategy to set fixed value', function (): void {
    $service = new AnonymizationService();
    $record = ['password' => 'secret123'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('password', ColumnMutationStrategyEnum::STATIC, new ColumnMutationDataOptions(value: '********')),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['password'])->toBe('********');
});

it('applies multiple mutations to same record', function (): void {
    $service = new AnonymizationService();
    $record = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'secret123',
        'notes' => 'Private notes',
    ];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'name')),
            new ColumnMutationData('email', ColumnMutationStrategyEnum::MASK, new ColumnMutationDataOptions(visibleChars: 2, preserveFormat: true)),
            new ColumnMutationData('password', ColumnMutationStrategyEnum::STATIC, new ColumnMutationDataOptions(value: '********')),
            new ColumnMutationData('notes', ColumnMutationStrategyEnum::NULL),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result['name'])->not->toBe('John Doe')
        ->and($result['email'])->toStartWith('jo')
        ->and($result['email'])->toEndWith('@example.com')
        ->and($result['password'])->toBe('********')
        ->and($result['notes'])->toBeNull();
});

it('ignores columns not present in record', function (): void {
    $service = new AnonymizationService();
    $record = ['name' => 'John Doe'];

    $tableOptions = new TableAnonymizationOptionsData(
        tableName: 'users',
        columnMutations: collect([
            new ColumnMutationData('name', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'name')),
            new ColumnMutationData('email', ColumnMutationStrategyEnum::FAKE, new ColumnMutationDataOptions(fakerMethod: 'email')),
            new ColumnMutationData('phone', ColumnMutationStrategyEnum::NULL),
        ])
    );

    $result = $service->anonymizeRecord($record, $tableOptions);

    expect($result)->toHaveKey('name')
        ->and($result)->not->toHaveKey('email')
        ->and($result)->not->toHaveKey('phone');
});
