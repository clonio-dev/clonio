<?php

declare(strict_types=1);

use App\Data\ColumnSchema;

it('creates column schema with all properties', function () {
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

it('detects numeric types correctly', function () {
    $intColumn = new ColumnSchema('id', 'int', false, null);
    $textColumn = new ColumnSchema('name', 'varchar', false, null);

    expect($intColumn->isNumeric())->toBeTrue()
        ->and($textColumn->isNumeric())->toBeFalse();
});

it('detects string types correctly', function () {
    $varcharColumn = new ColumnSchema('name', 'varchar', false, null);
    $intColumn = new ColumnSchema('id', 'int', false, null);

    expect($varcharColumn->isString())->toBeTrue()
        ->and($intColumn->isString())->toBeFalse();
});

it('generates full type definition', function () {
    $varcharColumn = new ColumnSchema('name', 'varchar', false, null, 255);
    $decimalColumn = new ColumnSchema('price', 'decimal', false, null, 10, 2, unsigned: true);

    expect($varcharColumn->getFullType())->toBe('VARCHAR(255)')
        ->and($decimalColumn->getFullType())->toBe('DECIMAL(10,2) UNSIGNED');
});

it('serializes and deserializes correctly', function () {
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
