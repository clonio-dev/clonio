<?php

declare(strict_types=1);

use App\Data\ConstraintSchema;

it('can be instantiated with required parameters', function (): void {
    $constraint = new ConstraintSchema(
        name: 'chk_age_positive',
        type: 'check',
        column: 'age',
        expression: 'age > 0',
    );

    expect($constraint->name)->toBe('chk_age_positive')
        ->and($constraint->type)->toBe('check')
        ->and($constraint->column)->toBe('age')
        ->and($constraint->expression)->toBe('age > 0')
        ->and($constraint->metadata)->toBe([]);
});

it('can be instantiated with all parameters', function (): void {
    $constraint = new ConstraintSchema(
        name: 'chk_status',
        type: 'check',
        column: 'status',
        expression: "status IN ('active', 'inactive')",
        metadata: ['enforced' => true],
    );

    expect($constraint->metadata)->toBe(['enforced' => true]);
});

it('can be created from array', function (): void {
    $data = [
        'name' => 'chk_price',
        'type' => 'check',
        'column' => 'price',
        'expression' => 'price >= 0',
        'metadata' => ['comment' => 'Ensure positive price'],
    ];

    $constraint = ConstraintSchema::fromArray($data);

    expect($constraint->name)->toBe('chk_price')
        ->and($constraint->type)->toBe('check')
        ->and($constraint->column)->toBe('price')
        ->and($constraint->expression)->toBe('price >= 0')
        ->and($constraint->metadata)->toBe(['comment' => 'Ensure positive price']);
});

it('can be created from array with defaults', function (): void {
    $data = [
        'name' => 'chk_test',
        'type' => 'check',
        'column' => null,
        'expression' => null,
    ];

    $constraint = ConstraintSchema::fromArray($data);

    expect($constraint->column)->toBeNull()
        ->and($constraint->expression)->toBeNull()
        ->and($constraint->metadata)->toBe([]);
});

it('can be converted to array', function (): void {
    $constraint = new ConstraintSchema(
        name: 'chk_quantity',
        type: 'check',
        column: 'quantity',
        expression: 'quantity > 0',
        metadata: ['key' => 'value'],
    );

    $array = $constraint->toArray();

    expect($array)->toBeArray()
        ->and($array['name'])->toBe('chk_quantity')
        ->and($array['type'])->toBe('check')
        ->and($array['column'])->toBe('quantity')
        ->and($array['expression'])->toBe('quantity > 0')
        ->and($array['metadata'])->toBe(['key' => 'value']);
});

it('correctly identifies check constraint', function (): void {
    $check = new ConstraintSchema('chk_test', 'check', 'col', 'col > 0');
    $default = new ConstraintSchema('df_test', 'default', 'col', "'value'");
    $unique = new ConstraintSchema('uq_test', 'unique', 'col', null);

    expect($check->isCheck())->toBeTrue()
        ->and($default->isCheck())->toBeFalse()
        ->and($unique->isCheck())->toBeFalse();
});

it('correctly identifies column-level constraint', function (): void {
    $columnLevel = new ConstraintSchema('chk_test', 'check', 'age', 'age > 0');
    $tableLevel = new ConstraintSchema('chk_table', 'check', null, 'start_date < end_date');

    expect($columnLevel->isColumnLevel())->toBeTrue()
        ->and($tableLevel->isColumnLevel())->toBeFalse();
});

it('correctly identifies table-level constraint', function (): void {
    $tableLevel = new ConstraintSchema('chk_dates', 'check', null, 'start_date < end_date');
    $columnLevel = new ConstraintSchema('chk_age', 'check', 'age', 'age > 0');

    expect($tableLevel->isTableLevel())->toBeTrue()
        ->and($columnLevel->isTableLevel())->toBeFalse();
});

it('handles various constraint types', function (): void {
    $types = ['check', 'default', 'unique', 'not_null'];

    foreach ($types as $type) {
        $constraint = new ConstraintSchema("test_{$type}", $type, 'col', 'expr');
        expect($constraint->type)->toBe($type);
    }
});
