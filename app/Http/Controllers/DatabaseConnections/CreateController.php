<?php

declare(strict_types=1);

namespace App\Http\Controllers\DatabaseConnections;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDatabaseConnectionRequest;
use App\Models\DatabaseConnection;
use Illuminate\Http\RedirectResponse;

class CreateController extends Controller
{
    public function __invoke(StoreDatabaseConnectionRequest $request): RedirectResponse
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
}
