<pre>{{-- resources/views/reports/audit-trail.blade.php --}}

═══════════════════════════════════════════════════
CLONIO - AUDIT TRAIL
Transfer Run #{{ $run->id }}
═══════════════════════════════════════════════════

Config:           {{ $run->title }}
User:             {{ $run->user->email }}
Started:          {{ $run->started_at->format('Y-m-d H:i:s T') }}
Finished:         {{ $run->finished_at->format('Y-m-d H:i:s T') }}
Duration:         {{ $run->started_at->diffForHumans($run->finished_at, true) }}
Status:           {{ ucfirst($run->status->getLabel()) }}

───────────────────────────────────────────────────
DIGITAL SIGNATURE
───────────────────────────────────────────────────
Status:           @if($verification['valid']) ✅ VERIFIED @else ❌ TAMPERED @endif

Algorithm:        HMAC-SHA256
Signed At:        {{ $verification['signed_at']->format('Y-m-d H:i:s T') }}
Hash:             {{ substr($verification['hash'], 0, 32) }}...
Signature:        {{ substr($verification['signature'], 0, 32) }}...

@if($verification['valid'])
    ✅ This audit trail has been cryptographically verified.
    No modifications detected since completion.
@else
    ⚠️  WARNING: This audit trail has been MODIFIED after completion!
    Data integrity cannot be guaranteed.
@endif

───────────────────────────────────────────────────
TRANSFERRED TABLES
───────────────────────────────────────────────────
@foreach($logs->where('event_type', 'data_copy_completed') as $log)
    {{ $loop->iteration }}. {{ $log->data['table'] }}
    Rows:          {{ isset($log->data['rows_processed']) ? number_format($log->data['rows_processed'], 0, ',', '.') : 'Unknown' }}
    Duration:      {{ isset($log->data['duration_seconds']) ? number_format($log->data['duration_seconds'], 0, ',', '.') . 's' : 'Unknown' }}
    Status:        Success
@endforeach

───────────────────────────────────────────────────
COMPLIANCE NOTES
───────────────────────────────────────────────────
- All PII anonymized according to GDPR
- Digital signature verified: {{ $verification['valid'] ? 'YES' : 'NO' }}
- Audit trail tamper-proof via HMAC-SHA256
- No reversible data stored

Generated: {{ now()->format('Y-m-d H:i:s T') }}
</pre>
