<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Telemetry Report</title>
    <style>
        @page {
            margin: 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #eef2f7;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }

        .toolbar {
            display: flex;
            justify-content: center;
            gap: 12px;
            padding: 18px;
        }

        .toolbar a,
        .toolbar button {
            border: 0;
            border-radius: 6px;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 10px 14px;
            text-decoration: none;
        }

        .toolbar button {
            background: #2563eb;
            color: #ffffff;
        }

        .toolbar a {
            background: #ffffff;
            color: #1f2937;
        }

        .page {
            width: min(100%, 960px);
            margin: 0 auto 28px;
            background: #ffffff;
            padding: 36px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 18px;
        }

        h1 {
            margin: 0;
            color: #0f172a;
            font-size: 28px;
            line-height: 1.1;
        }

        h2 {
            margin: 28px 0 10px;
            color: #111827;
            font-size: 16px;
        }

        .muted {
            color: #6b7280;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 24px;
            margin-top: 22px;
        }

        .meta-item,
        .summary-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
        }

        .label {
            color: #6b7280;
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .value {
            display: block;
            font-size: 14px;
            font-weight: 700;
            margin-top: 4px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 14px;
        }

        table {
            border-collapse: collapse;
            margin-top: 12px;
            width: 100%;
        }

        thead {
            display: table-header-group;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 9px 10px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            color: #374151;
            font-size: 11px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        tr {
            break-inside: avoid;
            page-break-inside: avoid;
        }

        @media print {
            body {
                background: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .page {
                box-shadow: none;
                margin: 0;
                padding: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @php
        $exportedAt = now();
        $fishTypes = collect($pond->fish_type ?? [])->filter()->implode(', ');
    @endphp

    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save PDF</button>
        <a href="{{ route('telemetrylog', ['pond_id' => $pond->id, 'period' => $period, 'filter_date' => $filterDate]) }}">Back to Telemetry</a>
    </div>

    <main class="page">
        <section class="header">
            <div>
                <h1>Telemetry Report</h1>
                <p class="muted">Water quality telemetry records for the selected pond and period.</p>
            </div>
            <div>
                <span class="label">Exported</span>
                <span class="value">{{ $exportedAt->format('M d, Y h:i A') }}</span>
            </div>
        </section>

        <section class="meta">
            <div class="meta-item">
                <span class="label">Report Period</span>
                <span class="value">{{ ucfirst($period) }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Covered Date Range</span>
                <span class="value">{{ $dateRange['start']->format('M d, Y h:i A') }} - {{ $dateRange['end']->format('M d, Y h:i A') }}</span>
            </div>
            <div class="meta-item">
                <span class="label">Generated For</span>
                <span class="value">{{ $user->name }} ({{ $user->email }})</span>
            </div>
            <div class="meta-item">
                <span class="label">Pond Information</span>
                <span class="value">
                    Pond #{{ $pond->id }} - {{ number_format((float) $pond->hectares, 2) }} ha{{ $fishTypes ? ' - ' . $fishTypes : '' }}
                </span>
            </div>
        </section>

        <h2>Summary</h2>
        <section class="summary">
            <div class="summary-card">
                <span class="label">Records</span>
                <span class="value">{{ number_format($telemetrySummary['count']) }}</span>
            </div>
            <div class="summary-card">
                <span class="label">Avg Temperature</span>
                <span class="value">{{ $telemetrySummary['avg_temperature'] !== null ? number_format($telemetrySummary['avg_temperature'], 1) . ' deg C' : '-' }}</span>
            </div>
            <div class="summary-card">
                <span class="label">Avg pH</span>
                <span class="value">{{ $telemetrySummary['avg_ph'] !== null ? number_format($telemetrySummary['avg_ph'], 2) : '-' }}</span>
            </div>
            <div class="summary-card">
                <span class="label">Avg Ammonia</span>
                <span class="value">{{ $telemetrySummary['avg_ammonia'] !== null ? number_format($telemetrySummary['avg_ammonia'], 3) : '-' }}</span>
            </div>
        </section>

        <h2>Telemetry Records</h2>
        <table>
            <thead>
                <tr>
                    <th>Date / Time Recorded</th>
                    <th>Pond</th>
                    <th>Temperature</th>
                    <th>pH Level</th>
                    <th>Ammonia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payloads as $log)
                    @php
                        $payload = is_array($log->payload) ? $log->payload : [];
                        $temperature = $payload['water_temp'] ?? $payload['temperature'] ?? null;
                        $ammonia = $payload['mq_ratio'] ?? $payload['ammonia'] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $log->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                        <td>Pond #{{ $log->pond?->id ?? $pond->id }}</td>
                        <td>{{ $temperature !== null && $temperature !== '' ? $temperature . ' deg C' : '-' }}</td>
                        <td>{{ $payload['ph'] ?? '-' }}</td>
                        <td>{{ $ammonia ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No telemetry records were found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </main>

    @if(request()->boolean('print'))
        <script>
            window.addEventListener('load', () => {
                window.setTimeout(() => window.print(), 250);
            });
        </script>
    @endif
</body>
</html>
