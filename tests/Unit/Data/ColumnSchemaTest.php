<?php

declare(strict_types=1);

use App\Data\ColumnSchema;

it('creates column schema with all properties', function (): void {
    $column = new ColumnSchema(
        name: 'email',
        type: 'varchar',
        nullable: false,
        default: null,
        length: 255,
        scale: null,
        autoIncrement: false,
        unsigned: false,
        charset: 'utf8mb4',
        collation: 'utf8mb4_unicode_ci',
        comment: 'User email address'
    );

    expect($column->name)->toBe('email')
        ->and($column->type)->toBe('varchar')
        ->and($column->nullable)->toBe(false)
        ->and($column->length)->toBe(255)
        ->and($column->comment)->toBe('User email address');
});

it('detects numeric types correctly', function (): void {
    $intColumn = new ColumnSchema('id', 'int', false, null);
    $textColumn = new ColumnSchema('name', 'varchar', false, null);

    expect($intColumn->isNumeric())->toBeTrue()
        ->and($textColumn->isNumeric())->toBeFalse();
});

it('detects string types correctly', function (): void {
    $varcharColumn = new ColumnSchema('name', 'varchar', false, null);
    $intColumn = new ColumnSchema('id', 'int', false, null);

    expect($varcharColumn->isString())->toBeTrue()
        ->and($intColumn->isString())->toBeFalse();
});

it('generates full type definition', function (): void {
    $varcharColumn = new ColumnSchema('name', 'varchar', false, null, 255);
    $decimalColumn = new ColumnSchema('price', 'decimal', false, null, 10, 2, unsigned: true);

    expect($varcharColumn->getFullType())->toBe('VARCHAR(255)')
        ->and($decimalColumn->getFullType())->toBe('DECIMAL(10,2) UNSIGNED');
});

it('serializes and deserializes correctly', function (): void {
    $original = new ColumnSchema(
        name: 'id',
        type: 'bigint',
        nullable: false,
        default: null,
        autoIncrement: true
    );

    $array = $original->toArray();
    $restored = ColumnSchema::fromArray($array);

    expect($restored->name)->toBe($original->name)
        ->and($restored->type)->toBe($original->type)
        ->and($restored->autoIncrement)->toBe($original->autoIncrement);
});

it('detects datetime types correctly', function (): void {
    $datetimeTypes = ['date', 'datetime', 'timestamp', 'time', 'year'];

    foreach ($datetimeTypes as $type) {
        $column = new ColumnSchema('test', $type, false, null);
        expect($column->isDateTime())->toBeTrue();
    }
});

it('detects non-datetime types correctly', function (): void {
    $nonDatetimeTypes = ['int', 'varchar', 'text', 'decimal'];

    foreach ($nonDatetimeTypes as $type) {
        $column = new ColumnSchema('test', $type, false, null);
        expect($column->isDateTime())->toBeFalse();
    }
});

it('can be created with default values for optional parameters', function (): void {
    $column = new ColumnSchema(
        name: 'id',
        type: 'int',
        nullable: false,
        default: null,
    );

    expect($column->length)->toBeNull()
        ->and($column->scale)->toBeNull()
        ->and($column->autoIncrement)->toBeFalse()
        ->and($column->unsigned)->toBeFalse()
        ->and($column->charset)->toBeNull()
        ->and($column->collation)->toBeNull()
        ->and($column->comment)->toBeNull()
        ->and($column->metadata)->toBe([]);
});

it('creates from array with minimal data', function (): void {
    $data = [
        'name' => 'status',
        'type' => 'varchar',
        'nullable' => true,
        'default' => 'active',
    ];

    $column = ColumnSchema::fromArray($data);

    expect($column->name)->toBe('status')
        ->and($column->nullable)->toBeTrue()
        ->and($column->default)->toBe('active')
        ->and($column->length)->toBeNull()
        ->and($column->metadata)->toBe([]);
});

it('returns full type without length', function (): void {
    $column = new ColumnSchema('id', 'int', false, null);

    expect($column->getFullType())->toBe('INT');
});

it('toArray includes all fields', function (): void {
    $column = new ColumnSchema(
        name: 'test',
        type: 'varchar',
        nullable: true,
        default: 'value',
        length: 100,
        scale: null,
        autoIncrement: false,
        unsigned: false,
        charset: 'utf8',
        collation: 'utf8_general_ci',
        comment: 'Test column',
        metadata: ['key' => 'value'],
    );

    $array = $column->toArray();

    expect($array)->toHaveKeys([
        'name',
        'type',
        'nullable',
        'default',
        'length',
        'scale',
        'auto_increment',
        'unsigned',
        'charset',
        'collation',
        'comment',
        'metadata',
    ]);
});
