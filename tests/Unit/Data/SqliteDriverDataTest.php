<?php

declare(strict_types=1);

use App\Data\SqliteDriverData;

it('can be created', function (): void {
    $sqliteDriverData = new SqliteDriverData('database.sqlite');

    expect($sqliteDriverData->database)->toBe('database.sqlite');
    expect($sqliteDriverData->toArray())->toBe([
        'driver' => 'sqlite',
        'database' => 'database.sqlite',
    ]);
});
