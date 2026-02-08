<?php

declare(strict_types=1);

use App\Models\CloningRun;
use App\Services\AuditService;

it('signs completed run correctly', function (): void {
    $run = CloningRun::factory()->create(['status' => 'completed']);

    // Add some logs
    $run->log('batch_started', ['batch_id' => 'test-123']);
    $run->log('table_completed', ['table' => 'users', 'rows_processed' => 100]);

    // Sign
    resolve(AuditService::class)->signRun($run);

    expect($run->fresh())
        ->audit_hash->not->toBeNull()
        ->audit_signature->not->toBeNull()
        ->audit_signed_at->not->toBeNull();
});

it('verifies unmodified run as valid', function (): void {
    $run = CloningRun::factory()->create(['status' => 'completed']);
    $run->log('batch_started', ['batch_id' => 'test-123']);

    resolve(AuditService::class)->signRun($run);

    expect(resolve(AuditService::class)->verifyRun($run))->toBeTrue();
});

it('detects tampering when logs are modified', function (): void {
    $run = CloningRun::factory()->create(['status' => 'completed']);
    $run->log('batch_started', ['batch_id' => 'test-123']);

    resolve(AuditService::class)->signRun($run);

    // 🔥 TAMPER WITH LOGS
    $run->logs()->first()->update(['message' => 'HACKED!']);

    expect(resolve(AuditService::class)->verifyRun($run))->toBeFalse();
});

it('detects tampering when run metadata is modified', function (): void {
    $run = CloningRun::factory()->create(['status' => 'completed']);
    $run->log('batch_started', ['batch_id' => 'test-123']);

    resolve(AuditService::class)->signRun($run);

    // 🔥 TAMPER WITH RUN
    $run->update(['status' => 'failed']);

    expect(resolve(AuditService::class)->verifyRun($run))->toBeFalse();
});
