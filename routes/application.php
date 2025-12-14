<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\SynchronizeDatabase;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::get('queue', function () {

        $synchronizationConfigData = new SynchronizationOptionsData(
            migrationTableName: 'migrations',
        );

        $connectionDataSource = new ConnectionData(
            'database',
            new SqliteDriverData(
                database_path('database.sqlite'),
            ),
        );

        $connectionDataTarget = new ConnectionData(
            'test',
            new SqliteDriverData(
                database_path('test.sqlite'),
            ),
        );

        $batch = Bus::batch([
            new SynchronizeDatabase($synchronizationConfigData, $connectionDataSource,
                collect([$connectionDataTarget])),
        ])
            ->name('Synchronize database ' . $connectionDataSource->name)
            ->before(function (Batch $batch) {
                // store the batch status or notify
            })
            ->then(function (Batch $batch) {
                // store the batch status or notify
//                $batch;
            })
            ->dispatch();

        return redirect()->route('dashboard', ['batch' => $batch->id]);
    });

    Route::get('/batch/{batchId}', function (string $batchId) {
        return Bus::findBatch($batchId);
    });
});
