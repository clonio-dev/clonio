<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;

it('can return a connection name', function (): void {
    $connectionData = new ConnectionData('mysql', new SqliteDriverData());

    expect($connectionData->name)->toBe('mysql');
    expect($connectionData->connectionName())->toStartWith('dyn_')->toEndWith('_mysql');
});
