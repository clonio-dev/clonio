<?php

declare(strict_types=1);

namespace App\Http\Controllers\DatabaseConnections;

use App\Http\Controllers\Controller;
use App\Models\DatabaseConnection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndexController extends Controller
{
    public function __invoke(Request $request): Response
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
}
