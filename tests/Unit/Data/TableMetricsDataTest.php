<?php

declare(strict_types=1);

use App\Data\TableMetricsData;

it('can be instantiated', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 1000,
        dataSizeInBytes: 1048576,
    );

    expect($metrics->rowsCount)->toBe(1000)
        ->and($metrics->dataSizeInBytes)->toBe(1048576);
});

it('calculates data size in MB', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 1000,
        dataSizeInBytes: 1048576, // 1 MB
    );

    expect($metrics->dataSizeInMB())->toBe(1.0);
});

it('calculates data size in MB for larger sizes', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 10000,
        dataSizeInBytes: 10485760, // 10 MB
    );

    expect($metrics->dataSizeInMB())->toBe(10.0);
});

it('returns human readable size in bytes', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 10,
        dataSizeInBytes: 500,
    );

    expect($metrics->humanReadableDataSize())->toBe('500 B');
});

it('returns human readable size in KB', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 100,
        dataSizeInBytes: 2048, // 2 KB
    );

    expect($metrics->humanReadableDataSize())->toBe('2 KB');
});

it('returns human readable size in MB', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 1000,
        dataSizeInBytes: 5242880, // 5 MB
    );

    expect($metrics->humanReadableDataSize())->toBe('5 MB');
});

it('returns human readable size in GB', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 100000,
        dataSizeInBytes: 2147483648, // 2 GB
    );

    expect($metrics->humanReadableDataSize())->toBe('2 GB');
});

it('calculates estimated duration with default rows per second', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 5000,
        dataSizeInBytes: 1000000,
    );

    expect($metrics->estimatedDurationInSeconds())->toBe(5.0);
});

it('calculates estimated duration with custom rows per second', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 10000,
        dataSizeInBytes: 1000000,
    );

    expect($metrics->estimatedDurationInSeconds(500))->toBe(20.0);
});

it('handles zero rows count', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 0,
        dataSizeInBytes: 0,
    );

    expect($metrics->dataSizeInMB())->toBe(0.0)
        ->and($metrics->humanReadableDataSize())->toBe('0 B')
        ->and($metrics->estimatedDurationInSeconds())->toBe(0.0);
});

it('handles fractional KB values', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 50,
        dataSizeInBytes: 1536, // 1.5 KB
    );

    expect($metrics->humanReadableDataSize())->toBe('1.5 KB');
});

it('handles fractional MB values', function (): void {
    $metrics = new TableMetricsData(
        rowsCount: 500,
        dataSizeInBytes: 1572864, // 1.5 MB
    );

    expect($metrics->humanReadableDataSize())->toBe('1.5 MB');
});
