<?php

declare(strict_types=1);

use App\Data\ColumnSchema;
use App\Services\SchemaReplicator\SQLServerSchemaBuilder;

$builder = new SQLServerSchemaBuilder();

$col = fn (string $type, ?int $length = null): ColumnSchema => new ColumnSchema(
    name: 'col',
    type: $type,
    nullable: false,
    default: null,
    length: $length,
);

it('maps datetime to DATETIME2', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('datetime')))->toBe('DATETIME2');
});

it('maps timestamp to DATETIME2', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('timestamp')))->toBe('DATETIME2');
});

it('maps tinytext to VARCHAR(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinytext')))->toBe('VARCHAR(MAX)');
});

it('maps mediumtext to VARCHAR(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('mediumtext')))->toBe('VARCHAR(MAX)');
});

it('maps longtext to VARCHAR(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('longtext')))->toBe('VARCHAR(MAX)');
});

it('maps blob to VARBINARY(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('blob')))->toBe('VARBINARY(MAX)');
});

it('maps longblob to VARBINARY(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('longblob')))->toBe('VARBINARY(MAX)');
});

it('maps mediumint to INT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('mediumint')))->toBe('INT');
});

it('maps tinyint(1) to BIT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinyint', 1)))->toBe('BIT');
});

it('maps tinyint to TINYINT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinyint')))->toBe('TINYINT');
});

it('maps double to FLOAT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('double')))->toBe('FLOAT');
});

it('maps year to SMALLINT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('year')))->toBe('SMALLINT');
});

it('maps enum to VARCHAR', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('enum')))->toBe('VARCHAR');
});

it('maps set to VARCHAR(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('set')))->toBe('VARCHAR(MAX)');
});

it('maps json to NVARCHAR(MAX)', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('json')))->toBe('NVARCHAR(MAX)');
});

it('does not append length to DATE or TIME', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('date', 10)))->toBe('DATE');
    expect($builder->buildDataType($col('time', 10)))->toBe('TIME');
});

it('does not append length to DATETIME2', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('datetime', 255)))->toBe('DATETIME2');
});

it('does not append length for blob/text MAX types', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('longtext', 4294967295)))->toBe('VARCHAR(MAX)');
    expect($builder->buildDataType($col('longblob', 4294967295)))->toBe('VARBINARY(MAX)');
});

it('appends length for varchar', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('varchar', 255)))->toBe('VARCHAR(255)');
});
