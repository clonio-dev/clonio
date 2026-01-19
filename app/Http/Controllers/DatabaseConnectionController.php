<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDatabaseConnectionRequest;
use App\Http\Requests\UpdateDatabaseConnectionRequest;
use App\Models\DatabaseConnection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        $connections = DatabaseConnection::query()
            ->forUser($request->user()->id)
            ->paginate()
            ->through(fn (DatabaseConnection $connection): array => [
                'id' => $connection->id,
                'name' => $connection->name,
                'type' => $connection->type,
                'host' => $connection->host,
                'port' => $connection->port,
                'database' => $connection->database,
                'username' => $connection->username,
                'is_production_stage' => $connection->is_production_stage,
            ]);

        return Inertia::render('connections/Index', [
            'connections' => $connections,
        ]);
    }

    public function store(StoreDatabaseConnectionRequest $request): RedirectResponse
    {
        $connection = DatabaseConnection::query()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
            'password' => $request->string('password', ''),
            'is_production_stage' => $request->boolean('is_production_stage'),
        ]);

        // try to connect
        $connection->update(['last_tested_at' => now()]);

        return to_route('connections.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DatabaseConnection $databaseConnection): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDatabaseConnectionRequest $request, DatabaseConnection $databaseConnection): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DatabaseConnection $databaseConnection): void
    {
        //
    }
}
