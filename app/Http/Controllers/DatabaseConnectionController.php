<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDatabaseConnectionRequest;
use App\Models\DatabaseConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

    public function store(StoreDatabaseConnectionRequest $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', DatabaseConnection::class);

        $connection = DatabaseConnection::query()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
            'password' => $request->string('password', ''),
            'is_production_stage' => $request->boolean('is_production_stage'),
        ]);

        // try to connect
        $connection->update(['last_tested_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json([
                'connection' => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'type' => $connection->type,
                    'is_production_stage' => $connection->is_production_stage,
                ],
            ], 201);
        }

        return to_route('connections.index');
    }

    public function destroy(DatabaseConnection $connection): RedirectResponse
    {
        Gate::authorize('delete', $connection);

        $connection->delete();

        return to_route('connections.index');
    }
}
