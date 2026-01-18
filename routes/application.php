<?php

declare(strict_types=1);

use App\Data\ConnectionData;
use App\Data\SqliteDriverData;
use App\Data\SynchronizationOptionsData;
use App\Jobs\SynchronizeDatabase;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('queue', function (): Illuminate\Http\RedirectResponse {

        $synchronizationConfigData = new SynchronizationOptionsData();

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
            ->before(function (Batch $batch): void {
                // store the batch status or notify
            })
            ->progress(function (Batch $batch): void {
                // dispatch progress events
            })
            ->then(function (Batch $batch): void {
                // store the batch status or notify
            })
            ->catch(function (Batch $batch, Throwable $exception): void {
                // store the failed state
            })
            ->finally(function (Batch $batch): void {
                // cleanup, even on error
            })
            ->dispatch();

        return to_route('dashboard', ['batch' => $batch->id]);
    });

    Route::get('/batch/{batch}', fn (string $batchId) => Bus::findBatch($batchId))
        ->name('batch.show');

    include __DIR__ . '/application/connections.php';
    include __DIR__ . '/application/transfers.php';
});
