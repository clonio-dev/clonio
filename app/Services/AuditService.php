<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CloningRun;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditService
{
    public function signRun(CloningRun $run): void
    {
        $run->loadMissing(['cloning', 'logs']);

        $logs = $run->logs()
            ->orderBy('id')
            ->get(['event_type', 'level', 'message', 'data', 'created_at'])
            ->toArray();

        $auditData = $this->createAuditRecord($run, $logs);

        // 3. Deterministische JSON-Serialisierung
        $json = json_encode($auditData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // 4. Hash erstellen (SHA-256)
        $hash = hash('sha256', $json);

        // 5. Signatur erstellen (HMAC-SHA256)
        $signature = hash_hmac(
            'sha256',
            $hash,
            (string) config('clonio.audit_secret')
        );

        // 6. In DB speichern
        try {
            $run->updateQuietly([
                'config_snapshot' => $run->cloning->anonymization_config,
                'audit_hash' => $hash,
                'audit_signature' => $signature,
                'audit_signed_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to save audit signature for run ' . $run->id . ': ' . $e->getMessage());
        }
    }

    /**
     * Verifiziert die Signatur eines Runs
     */
    public function verifyRun(CloningRun $run): bool
    {
        if (! $run->audit_signature) {
            return false;
        }

        // 1. Hash neu berechnen
        $logs = $run->logs()
            ->orderBy('id')
            ->get(['event_type', 'level', 'message', 'data', 'created_at'])
            ->toArray();

        $auditData = $this->createAuditRecord($run, $logs);

        $json = json_encode($auditData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $currentHash = hash('sha256', $json);

        // 2. Hash-Vergleich
        if (! hash_equals($run->audit_hash, $currentHash)) {
            return false; // Logs wurden verändert!
        }

        // 3. Signatur-Verifikation
        $expectedSignature = hash_hmac(
            'sha256',
            $currentHash,
            (string) config('clonio.audit_secret')
        );

        return hash_equals($run->audit_signature, $expectedSignature);
    }

    /**
     * Gibt Verifikations-Details zurück
     */
    public function getVerificationDetails(CloningRun $run): array
    {
        $valid = $this->verifyRun($run);

        return [
            'valid' => $valid,
            'signed_at' => $run->audit_signed_at,
            'signature' => $run->audit_signature,
            'hash' => $run->audit_hash,
            'status' => $valid ? 'verified' : 'tampered',
        ];
    }

    private function createAuditRecord(CloningRun $run, array $logs): array
    {
        return [
            'run_id' => $run->id,
            'snapshot' => $run->cloning->anonymization_config,
            'source' => $run->cloning->source_connection_id,
            'target' => $run->cloning->target_connection_id,
            'started_at' => $run->started_at->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'status' => $run->status,
            'logs' => $logs,
        ];
    }
}
