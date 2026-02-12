<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Clonio\ExecuteCloning;
use App\Http\Controllers\Controller;
use App\Models\Cloning;
use Illuminate\Http\JsonResponse;

class TriggerCloningController extends Controller
{
    public function __invoke(string $token, ExecuteCloning $executeCloning): JsonResponse
    {
        $cloning = Cloning::query()
            ->where('api_trigger_token', $token)
            ->first();

        abort_unless($cloning, 404);

        $apiTriggerEnabled = $cloning->trigger_config?->apiTrigger->enabled ?? false;

        if (! $apiTriggerEnabled) {
            return response()->json(['message' => 'API trigger is not enabled for this cloning.'], 403);
        }

        $run = $executeCloning->start($cloning);
        $run->log('api_triggered', ['message' => 'Cloning triggered via API']);

        return response()->json([
            'message' => 'Cloning execution started.',
            'run_id' => $run->id,
        ], 202);
    }
}
