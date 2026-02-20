<?php

declare(strict_types=1);

use App\Data\ColumnSchema;
use App\Services\SchemaReplicator\PostgreSQLSchemaBuilder;

$builder = new PostgreSQLSchemaBuilder();

$col = fn (string $type, ?int $length = null, bool $autoIncrement = false): ColumnSchema => new ColumnSchema(
    name: 'col',
    type: $type,
    nullable: false,
    default: null,
    length: $length,
    autoIncrement: $autoIncrement,
);

it('maps datetime to TIMESTAMP', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('datetime')))->toBe('TIMESTAMP');
});

it('maps tinytext to TEXT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinytext')))->toBe('TEXT');
});

it('maps mediumtext to TEXT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('mediumtext')))->toBe('TEXT');
});

it('maps longtext to TEXT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('longtext')))->toBe('TEXT');
});

it('maps tinyblob to BYTEA', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinyblob')))->toBe('BYTEA');
});

it('maps blob to BYTEA', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('blob')))->toBe('BYTEA');
});

it('maps mediumblob to BYTEA', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('mediumblob')))->toBe('BYTEA');
});

it('maps longblob to BYTEA', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('longblob')))->toBe('BYTEA');
});

it('maps int to INTEGER', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('int')))->toBe('INTEGER');
});

it('maps mediumint to INTEGER', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('mediumint')))->toBe('INTEGER');
});

it('maps tinyint to SMALLINT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinyint')))->toBe('SMALLINT');
});

it('maps tinyint(1) to BOOLEAN', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('tinyint', 1)))->toBe('BOOLEAN');
});

it('maps double to DOUBLE PRECISION', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('double')))->toBe('DOUBLE PRECISION');
});

it('maps float to REAL', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('float')))->toBe('REAL');
});

it('maps year to SMALLINT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('year')))->toBe('SMALLINT');
});

it('maps enum to VARCHAR', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('enum')))->toBe('VARCHAR');
});

it('maps set to TEXT', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('set')))->toBe('TEXT');
});

it('maps json to JSONB', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('json')))->toBe('JSONB');
});

it('passes through native postgres types unchanged', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('varchar', 255)))->toBe('VARCHAR(255)');
    expect($builder->buildDataType($col('bigint')))->toBe('BIGINT');
    expect($builder->buildDataType($col('text')))->toBe('TEXT');
    expect($builder->buildDataType($col('timestamp')))->toBe('TIMESTAMP');
});

it('maps bigint autoIncrement to BIGSERIAL', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('bigint', null, true)))->toBe('BIGSERIAL');
});

it('maps int autoIncrement to SERIAL', function () use ($builder, $col): void {
    expect($builder->buildDataType($col('int', null, true)))->toBe('SERIAL');
});

it('does not append length for TEXT and BYTEA', function () use ($builder, $col): void {
    // MySQL often reports a length for text/blob types; it must be ignored for PG
    expect($builder->buildDataType($col('longtext', 4294967295)))->toBe('TEXT');
    expect($builder->buildDataType($col('longblob', 4294967295)))->toBe('BYTEA');
});
