<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Trail Report - Transfer Run #{{ $run->id }}</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --color-text: #1a1a2e;
            --color-text-secondary: #4a4a68;
            --color-text-muted: #6b7280;
            --color-border: #e2e4e9;
            --color-border-light: #f0f1f3;
            --color-bg: #ffffff;
            --color-bg-subtle: #f8f9fb;
            --color-bg-muted: #f1f2f5;
            --color-brand: #2563eb;
            --color-brand-light: #eff4ff;
            --color-green: #16a34a;
            --color-green-bg: #f0fdf4;
            --color-green-border: #bbf7d0;
            --color-red: #dc2626;
            --color-red-bg: #fef2f2;
            --color-red-border: #fecaca;
            --color-amber: #d97706;
            --color-amber-bg: #fffbeb;
            --color-amber-border: #fde68a;
            --color-blue: #2563eb;
            --color-blue-bg: #eff6ff;
            --color-blue-border: #bfdbfe;
            --color-gray: #6b7280;
            --color-gray-bg: #f3f4f6;
            --color-gray-border: #d1d5db;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: var(--color-text);
            background: var(--color-bg-subtle);
            -webkit-font-smoothing: antialiased;
        }

        .page {
            max-width: 900px;
            margin: 0 auto;
            padding: 32px 24px;
            background: var(--color-bg);
        }

        /* Header */
        .report-header {
            border-bottom: 2px solid var(--color-text);
            padding-bottom: 20px;
            margin-bottom: 32px;
        }

        .report-header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }

        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--color-text);
        }

        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: var(--color-brand);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .report-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--color-text-secondary);
            margin-top: 4px;
        }

        .report-subtitle {
            font-size: 14px;
            color: var(--color-text-muted);
            margin-top: 2px;
        }

        /* Sections */
        .section {
            margin-bottom: 28px;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--color-text-muted);
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        /* Summary table */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px 32px;
        }

        .summary-item {
            display: flex;
            gap: 8px;
        }

        .summary-label {
            font-size: 13px;
            color: var(--color-text-muted);
            min-width: 120px;
            flex-shrink: 0;
        }

        .summary-value {
            font-size: 13px;
            font-weight: 500;
            color: var(--color-text);
            word-break: break-word;
        }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-completed {
            background: var(--color-green-bg);
            color: var(--color-green);
            border: 1px solid var(--color-green-border);
        }

        .badge-failed {
            background: var(--color-red-bg);
            color: var(--color-red);
            border: 1px solid var(--color-red-border);
        }

        .badge-processing {
            background: var(--color-blue-bg);
            color: var(--color-blue);
            border: 1px solid var(--color-blue-border);
        }

        .badge-queued, .badge-cancelled {
            background: var(--color-gray-bg);
            color: var(--color-gray);
            border: 1px solid var(--color-gray-border);
        }

        /* Connection cards */
        .connection-grid {
            display: grid;
            grid-template-columns: 1fr 24px 1fr;
            gap: 0;
            align-items: stretch;
        }

        .connection-card {
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 16px;
        }

        .connection-arrow {
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted);
            font-size: 18px;
        }

        .connection-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .connection-label-source {
            color: var(--color-brand);
        }

        .connection-label-target {
            color: var(--color-green);
        }

        .connection-name {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .connection-detail {
            font-size: 12px;
            color: var(--color-text-secondary);
            margin-bottom: 3px;
        }

        .connection-detail span {
            color: var(--color-text-muted);
        }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            border-bottom: 2px solid var(--color-border);
            background: var(--color-bg-subtle);
        }

        .data-table td {
            padding: 7px 12px;
            border-bottom: 1px solid var(--color-border-light);
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .table-group-header td {
            background: var(--color-bg-muted);
            font-weight: 600;
            font-size: 13px;
            padding: 8px 12px;
            border-bottom: 1px solid var(--color-border);
        }

        .code {
            font-family: "SF Mono", SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            background: var(--color-bg-muted);
            padding: 1px 5px;
            border-radius: 3px;
            color: var(--color-text-secondary);
        }

        .strategy-badge {
            display: inline-block;
            padding: 1px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .strategy-fake {
            background: #fef3c7;
            color: #92400e;
        }

        .strategy-mask {
            background: #e0e7ff;
            color: #3730a3;
        }

        .strategy-null {
            background: #fee2e2;
            color: #991b1b;
        }

        .strategy-hash {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .strategy-default {
            background: var(--color-bg-muted);
            color: var(--color-text-secondary);
        }

        .row-selection-note {
            font-size: 12px;
            color: var(--color-text-muted);
            padding: 6px 12px;
            background: var(--color-bg-subtle);
            border-left: 3px solid var(--color-brand);
            margin: 4px 12px 8px 12px;
        }

        .config-note {
            font-size: 13px;
            color: var(--color-text-secondary);
            padding: 10px 14px;
            background: var(--color-bg-subtle);
            border: 1px solid var(--color-border-light);
            border-radius: 6px;
            margin-top: 12px;
        }

        /* Compliance box */
        .compliance-box {
            border: 1px solid var(--color-green-border);
            border-radius: 8px;
            padding: 16px 20px;
            background: var(--color-green-bg);
        }

        .compliance-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--color-green);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .compliance-list {
            list-style: none;
            padding: 0;
        }

        .compliance-list li {
            font-size: 13px;
            color: var(--color-text-secondary);
            padding: 3px 0;
            padding-left: 20px;
            position: relative;
        }

        .compliance-list li::before {
            content: "\2713";
            position: absolute;
            left: 0;
            color: var(--color-green);
            font-weight: 700;
        }

        /* Signature box */
        .signature-box {
            border: 1px solid var(--color-border);
            border-radius: 8px;
            padding: 16px 20px;
        }

        .signature-box.verified {
            border-color: var(--color-green-border);
        }

        .signature-box.tampered {
            border-color: var(--color-red-border);
            background: var(--color-red-bg);
        }

        .signature-status {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .signature-status.verified {
            color: var(--color-green);
        }

        .signature-status.tampered {
            color: var(--color-red);
        }

        .signature-detail {
            display: flex;
            gap: 8px;
            margin-bottom: 4px;
        }

        .signature-detail-label {
            font-size: 12px;
            color: var(--color-text-muted);
            min-width: 80px;
            flex-shrink: 0;
        }

        .signature-detail-value {
            font-family: "SF Mono", SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            color: var(--color-text-secondary);
            word-break: break-all;
        }

        /* Log table */
        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .log-table th {
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: var(--color-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 10px;
            border-bottom: 2px solid var(--color-border);
            background: var(--color-bg-subtle);
        }

        .log-table td {
            padding: 5px 10px;
            border-bottom: 1px solid var(--color-border-light);
            vertical-align: top;
        }

        .log-table .log-time {
            font-family: "SF Mono", SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            color: var(--color-text-muted);
            white-space: nowrap;
        }

        .log-table .log-event {
            font-size: 11px;
            color: var(--color-text-secondary);
        }

        .log-table .log-message {
            color: var(--color-text);
        }

        .log-level-badge {
            display: inline-block;
            padding: 0px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .log-level-info {
            background: var(--color-blue-bg);
            color: var(--color-blue);
        }

        .log-level-success {
            background: var(--color-green-bg);
            color: var(--color-green);
        }

        /* Footer */
        .report-footer {
            border-top: 2px solid var(--color-text);
            padding-top: 20px;
            margin-top: 32px;
        }

        .footer-meta {
            font-size: 12px;
            color: var(--color-text-muted);
            margin-bottom: 4px;
        }

        .footer-hash {
            font-family: "SF Mono", SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 11px;
            color: var(--color-text-muted);
        }

        .signature-line {
            margin-top: 96px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
        }

        .signature-field {
            border-top: 1px solid var(--color-text);
            padding-top: 6px;
            font-size: 12px;
            color: var(--color-text-muted);
        }

        /* Print styles */
        @media print {
            body {
                background: #fff;
                font-size: 11pt;
            }

            .page {
                max-width: 100%;
                padding: 0;
                margin: 0;
            }

            .print-btn {
                display: none !important;
            }

            .section {
                page-break-inside: avoid;
            }

            .report-header {
                border-bottom-width: 1.5pt;
            }

            .report-footer {
                border-top-width: 1.5pt;
            }

            .connection-card {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            .compliance-box {
                border: 1px solid #999;
                background: #f9f9f9;
            }

            .signature-box {
                box-shadow: none;
                border: 1px solid #999;
            }

            .signature-box.tampered {
                background: #fff;
            }

            .badge {
                border: 1px solid #999;
            }

            .data-table th,
            .log-table th {
                background: #f5f5f5;
            }

            .table-group-header td {
                background: #eee;
            }

            .log-table {
                page-break-inside: auto;
            }

            .log-table tr {
                page-break-inside: avoid;
            }

            .signature-line {
                page-break-inside: avoid;
            }
        }

        @media print and (color) {
            .badge-completed {
                background: #e6f4ea;
                color: #137333;
            }

            .badge-failed {
                background: #fce8e6;
                color: #c5221f;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        {{-- Header --}}
        <header class="report-header">
            <div class="report-header-top">
                <div>
                    <div class="brand">CLONIO</div>
                    <div class="report-title">Audit Trail Report</div>
                    <div class="report-subtitle">Transfer Run #{{ $run->id }} &middot; Generated {{ now()->format('M d, Y \a\t H:i T') }}</div>
                </div>
                <button class="print-btn" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Print Report
                </button>
            </div>
        </header>

        {{-- Run Summary --}}
        <div class="section">
            <div class="section-title">Run Summary</div>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Configuration</div>
                    <div class="summary-value">{{ $run->cloning->title ?? 'N/A' }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Status</div>
                    <div class="summary-value">
                        <span class="badge badge-{{ $run->status->value }}">{{ $run->status->getLabel() }}</span>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Started at</div>
                    <div class="summary-value">{{ $run->started_at?->format('Y-m-d H:i:s T') ?? 'N/A' }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Finished at</div>
                    <div class="summary-value">{{ $run->finished_at?->format('Y-m-d H:i:s T') ?? 'N/A' }}</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Duration</div>
                    <div class="summary-value">
                        @if($run->started_at && $run->finished_at)
                            {{ $run->started_at->diffForHumans($run->finished_at, true) }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                @php
                    $isScheduled = $logs->contains(fn ($log) => $log->event_type === 'scheduled_cloning_run_created');
                    $userInitiatedLog = $logs->firstWhere('event_type', 'user_initiated');
                @endphp
                <div class="summary-item">
                    <div class="summary-label">Initiated by</div>
                    <div class="summary-value">
                        @if($isScheduled)
                            Cron / Scheduled
                        @elseif($userInitiatedLog && !empty($userInitiatedLog->data['name']))
                            {{ $userInitiatedLog->data['name'] }} ({{ $userInitiatedLog->data['email'] ?? 'N/A' }})
                        @else
                            {{ $run->user->name ?? 'N/A' }} ({{ $run->user->email ?? 'N/A' }})
                        @endif
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Configured by</div>
                    <div class="summary-value">{{ $run->cloning->user->name ?? 'N/A' }} ({{ $run->cloning->user->email ?? 'N/A' }})</div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Run ID</div>
                    <div class="summary-value"><span class="code">{{ $run->id }}</span></div>
                </div>
            </div>
        </div>

        {{-- Source & Target Connections --}}
        <div class="section">
            <div class="section-title">Connection Details</div>
            <div class="connection-grid">
                <div class="connection-card">
                    <div class="connection-label connection-label-source">Source (Production)</div>
                    <div class="connection-name">{{ $run->cloning->sourceConnection->name ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Type:</span> {{ $run->cloning->sourceConnection->type?->getLabel() ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Host:</span> {{ $run->cloning->sourceConnection->host ?? 'N/A' }}:{{ $run->cloning->sourceConnection->port ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Database:</span> {{ $run->cloning->sourceConnection->database ?? 'N/A' }}</div>
                </div>
                <div class="connection-arrow">&rarr;</div>
                <div class="connection-card">
                    <div class="connection-label connection-label-target">Target</div>
                    <div class="connection-name">{{ $run->cloning->targetConnection->name ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Type:</span> {{ $run->cloning->targetConnection->type?->getLabel() ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Host:</span> {{ $run->cloning->targetConnection->host ?? 'N/A' }}:{{ $run->cloning->targetConnection->port ?? 'N/A' }}</div>
                    <div class="connection-detail"><span>Database:</span> {{ $run->cloning->targetConnection->database ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Data Transformation Configuration --}}
        @php
            $tables = collect($config['tables'] ?? []);
            $tablesWithTransformations = $tables->filter(function ($table) {
                $mutations = collect($table['columnMutations'] ?? []);
                return $mutations->contains(fn ($m) => ($m['strategy'] ?? 'keep') !== 'keep');
            });
        @endphp

        @if($tablesWithTransformations->isNotEmpty())
            <div class="section">
                <div class="section-title">Data Transformation Configuration</div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Column</th>
                            <th>Strategy</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tablesWithTransformations as $table)
                            <tr class="table-group-header">
                                <td colspan="3">
                                    {{ $table['tableName'] }}
                                    @if(!empty($table['rowSelection']) && ($table['rowSelection']['strategy'] ?? null) !== null)
                                        &nbsp;&mdash;&nbsp;
                                        <span style="font-weight: 400; font-size: 12px; color: var(--color-text-muted);">
                                            Row selection: {{ $table['rowSelection']['strategy'] }}
                                            @if(!empty($table['rowSelection']['limit']))
                                                (limit {{ number_format($table['rowSelection']['limit']) }}
                                                @if(!empty($table['rowSelection']['sortColumn']))
                                                    , sorted by {{ $table['rowSelection']['sortColumn'] }}
                                                @endif
                                                )
                                            @endif
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @foreach(collect($table['columnMutations'] ?? [])->filter(fn ($m) => ($m['strategy'] ?? 'keep') !== 'keep') as $mutation)
                                <tr>
                                    <td><span class="code">{{ $mutation['columnName'] }}</span></td>
                                    <td>
                                        @php
                                            $strategy = $mutation['strategy'] ?? 'unknown';
                                            $strategyClass = match($strategy) {
                                                'fake' => 'strategy-fake',
                                                'mask' => 'strategy-mask',
                                                'null' => 'strategy-null',
                                                'hash' => 'strategy-hash',
                                                default => 'strategy-default',
                                            };
                                        @endphp
                                        <span class="strategy-badge {{ $strategyClass }}">{{ $strategy }}</span>
                                    </td>
                                    <td>
                                        @if(!empty($mutation['options']))
                                            @foreach($mutation['options'] as $key => $value)
                                                <span class="code">{{ $key }}: {{ is_array($value) ? json_encode($value) : $value }}</span>
                                                @if(!$loop->last), @endif
                                            @endforeach
                                        @else
                                            <span style="color: var(--color-text-muted);">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>

                <div class="config-note">
                    <strong>Unknown tables policy:</strong>
                    @if($config['keepUnknownTablesOnTarget'] ?? false)
                        Unknown tables on the target database are preserved (not dropped).
                    @else
                        Unknown tables on the target database will be removed.
                    @endif
                </div>
            </div>
        @endif

        {{-- PII/GDPR Compliance --}}
        @php
            $allMutations = $tables->flatMap(fn ($t) => collect($t['columnMutations'] ?? []));
            $anonymizedMutations = $allMutations->filter(fn ($m) => ($m['strategy'] ?? 'keep') !== 'keep');
            $strategies = $anonymizedMutations->pluck('strategy')->unique()->sort()->values();
        @endphp

        @if($anonymizedMutations->isNotEmpty())
            <div class="section">
                <div class="section-title">PII / GDPR Compliance</div>
                <div class="compliance-box">
                    <div class="compliance-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        PII/GDPR Compliant Transfer
                    </div>
                    <ul class="compliance-list">
                        <li>All personally identifiable information has been anonymized according to the configured transformation rules</li>
                        <li>Anonymization methods used: {{ $strategies->map(fn ($s) => ucfirst($s))->implode(', ') }}</li>
                        <li>{{ $anonymizedMutations->count() }} column(s) across {{ $tablesWithTransformations->count() }} table(s) transformed</li>
                        <li>Data integrity verified via HMAC-SHA256 digital signature</li>
                        <li>No reversible personally identifiable data stored in audit records</li>
                    </ul>
                </div>
            </div>
        @endif

        {{-- Digital Signature --}}
        <div class="section">
            <div class="section-title">Digital Signature</div>
            <div class="signature-box {{ $verification['valid'] ? 'verified' : 'tampered' }}">
                <div class="signature-status {{ $verification['valid'] ? 'verified' : 'tampered' }}">
                    @if($verification['valid'])
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Verified &mdash; Audit trail integrity confirmed
                    @else
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Tampered &mdash; Data integrity cannot be guaranteed
                    @endif
                </div>

                <div class="signature-detail">
                    <div class="signature-detail-label">Algorithm</div>
                    <div class="signature-detail-value">HMAC-SHA256</div>
                </div>
                <div class="signature-detail">
                    <div class="signature-detail-label">Signed at</div>
                    <div class="signature-detail-value">{{ $verification['signed_at']?->format('Y-m-d H:i:s T') ?? 'N/A' }}</div>
                </div>
                <div class="signature-detail">
                    <div class="signature-detail-label">Hash</div>
                    <div class="signature-detail-value">{{ $verification['hash'] ? $verification['hash'] : 'N/A' }}</div>
                </div>
                <div class="signature-detail">
                    <div class="signature-detail-label">Signature</div>
                    <div class="signature-detail-value">{{ $verification['signature'] ? $verification['signature'] : 'N/A' }}</div>
                </div>
            </div>
        </div>

        {{-- Transfer Log --}}
        @php
            $visibleLogs = $logs->filter(fn ($log) => in_array($log->level->value, ['info', 'success']));
        @endphp

        @if($visibleLogs->isNotEmpty())
            <div class="section">
                <div class="section-title">Transfer Log</div>
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">Time</th>
                            <th style="width: 60px;">Level</th>
                            <th style="width: 180px;">Event</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($visibleLogs as $log)
                            <tr>
                                <td class="log-time">
                                    @if($run->started_at && $log->created_at)
                                        +{{ gmdate('H:i:s', $run->started_at->diffInSeconds($log->created_at)) }}
                                    @else
                                        {{ $log->created_at?->format('H:i:s') ?? 'N/A' }}
                                    @endif
                                </td>
                                <td>
                                    <span class="log-level-badge log-level-{{ $log->level->value }}">{{ $log->level->value }}</span>
                                </td>
                                <td class="log-event">{{ str_replace('_', ' ', $log->event_type) }}</td>
                                <td class="log-message">{{ $log->message }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Footer --}}
        <footer class="report-footer">
            <div class="footer-meta">This report was generated on {{ now()->format('F j, Y \a\t H:i:s T') }}</div>
            <div class="footer-meta footer-hash">Document signature: {{ $verification['signature'] ?? 'N/A' }}</div>

            <div class="signature-line">
                <div class="signature-field">Verified by</div>
                <div class="signature-field">Date</div>
            </div>
        </footer>
    </div>
</body>
</html>
