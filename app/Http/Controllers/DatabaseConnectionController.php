<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDatabaseConnectionRequest;
use App\Jobs\TestConnection;
use App\Models\DatabaseConnection;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
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
                'last_tested_at' => $connection->last_tested_at?->diffForHumans(),
                'last_tested_at_label' => $connection->last_tested_at?->diffForHumans() ?? 'No logs available',
                'is_connectable' => $connection->is_connectable,
                'last_test_result' => $connection->last_test_result ?? 'Untested',
            ]);

        return Inertia::render('connections/Index', [
            'connections' => $connections,
        ]);
    }

    public function store(StoreDatabaseConnectionRequest $request): RedirectResponse
    {
        Gate::authorize('create', DatabaseConnection::class);

        $connection = DatabaseConnection::query()->create([
            'user_id' => $request->user()->id,
            ...$request->validated(),
            'password' => $request->string('password', ''),
            'is_production_stage' => $request->boolean('is_production_stage'),
        ]);

        // Flash the created connection for on-the-fly creation flows
        session()->flash('created_connection', [
            'id' => $connection->id,
            'name' => $connection->name,
            'type' => $connection->type->value,
            'is_production_stage' => $connection->is_production_stage,
        ]);

        // Redirect back if coming from a different page, otherwise to index
        $previousUrl = url()->previous();
        $indexUrl = route('connections.index');

        if ($previousUrl !== $indexUrl && ! str_contains($previousUrl, '/connections')) {
            return back();
        }

        return to_route('connections.index');
    }

    public function testConnection(DatabaseConnection $connection): RedirectResponse
    {
        Gate::authorize('create', $connection);

        TestConnection::dispatchSync($connection);

        return back();
    }

    public function testAllConnections(#[CurrentUser] User $user): RedirectResponse
    {
        DatabaseConnection::query()
            ->forUser($user->id)
            ->get()
            ->each(function (DatabaseConnection $connection) {
                if (Gate::allows('create', $connection)) {
                    TestConnection::dispatchSync($connection);
                }
            });

        return back();
    }

    public function destroy(DatabaseConnection $connection): RedirectResponse
    {
        Gate::authorize('delete', $connection);

        $connection->delete();

        return to_route('connections.index');
    }
}
