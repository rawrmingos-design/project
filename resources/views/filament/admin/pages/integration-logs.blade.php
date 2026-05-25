<x-filament-panels::page>
    <style>
        .integration-logs {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .integration-logs__intro,
        .integration-logs__panel,
        .integration-logs__summary-card {
            border: 1px solid rgba(71, 85, 105, .48);
            background: rgba(15, 23, 42, .72);
            border-radius: 16px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05);
        }

        .integration-logs__intro {
            padding: 1.25rem 1.5rem;
        }

        .integration-logs__title {
            margin: 0;
            font-size: 1.15rem;
            line-height: 1.4;
            font-weight: 700;
            color: #f8fafc;
        }

        .integration-logs__subtitle {
            margin: .45rem 0 0;
            max-width: 80ch;
            color: rgba(226, 232, 240, .9);
            font-size: .95rem;
            line-height: 1.6;
        }

        .integration-logs__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .65rem;
            margin-top: 1rem;
        }

        .integration-logs__action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .65rem .9rem;
            border-radius: 12px;
            background: rgba(30, 41, 59, .96);
            border: 1px solid rgba(71, 85, 105, .5);
            color: #f8fafc;
            text-decoration: none;
            font-size: .875rem;
            line-height: 1.2;
            transition: border-color .18s ease, transform .18s ease, background-color .18s ease;
        }

        .integration-logs__action:hover,
        .integration-logs__action:focus-visible {
            border-color: rgba(96, 165, 250, .55);
            background: rgba(30, 41, 59, 1);
            transform: translateY(-1px);
        }

        .integration-logs__summary-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: .85rem;
        }

        @media (min-width: 960px) {
            .integration-logs__summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .integration-logs__summary-card {
            padding: 1rem 1rem .95rem;
        }

        .integration-logs__summary-title {
            margin: 0;
            font-size: 1rem;
            line-height: 1.35;
            color: #f8fafc;
            font-weight: 700;
        }

        .integration-logs__summary-meta {
            margin-top: .3rem;
            color: rgba(226, 232, 240, .78);
            font-size: .85rem;
        }

        .integration-logs__summary-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .7rem;
            margin-top: .95rem;
        }

        .integration-logs__metric {
            background: rgba(2, 6, 23, .28);
            border: 1px solid rgba(71, 85, 105, .38);
            border-radius: 12px;
            padding: .8rem .85rem;
        }

        .integration-logs__metric-label {
            display: block;
            color: rgba(226, 232, 240, .72);
            font-size: .75rem;
            line-height: 1.2;
        }

        .integration-logs__metric-value {
            display: block;
            margin-top: .25rem;
            color: #f8fafc;
            font-size: 1rem;
            line-height: 1.2;
            font-weight: 700;
        }

        .integration-logs__panels {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }

        .integration-logs__panel {
            overflow: hidden;
        }

        .integration-logs__panel-head {
            padding: 1rem 1rem .85rem;
            border-bottom: 1px solid rgba(71, 85, 105, .35);
        }

        .integration-logs__panel-title {
            margin: 0;
            color: #f8fafc;
            font-size: 1rem;
            line-height: 1.35;
            font-weight: 700;
        }

        .integration-logs__panel-desc {
            margin-top: .3rem;
            color: rgba(226, 232, 240, .78);
            font-size: .85rem;
            line-height: 1.5;
        }

        .integration-logs__table-wrap {
            overflow-x: auto;
        }

        .integration-logs__table {
            width: 100%;
            border-collapse: collapse;
        }

        .integration-logs__table th,
        .integration-logs__table td {
            text-align: left;
            padding: .85rem 1rem;
            border-bottom: 1px solid rgba(71, 85, 105, .22);
            vertical-align: top;
            font-size: .875rem;
            line-height: 1.45;
        }

        .integration-logs__table th {
            color: rgba(226, 232, 240, .72);
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            background: rgba(15, 23, 42, .88);
        }

        .integration-logs__table td {
            color: #e2e8f0;
        }

        .integration-logs__empty {
            padding: 1rem;
            color: rgba(226, 232, 240, .72);
            font-size: .875rem;
        }

        .integration-logs__badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .2rem .55rem;
            font-size: .75rem;
            line-height: 1.2;
            font-weight: 600;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .integration-logs__badge--success {
            background: rgba(16, 185, 129, .16);
            border-color: rgba(16, 185, 129, .35);
            color: #6ee7b7;
        }

        .integration-logs__badge--warning {
            background: rgba(245, 158, 11, .16);
            border-color: rgba(245, 158, 11, .35);
            color: #fcd34d;
        }

        .integration-logs__badge--danger {
            background: rgba(239, 68, 68, .14);
            border-color: rgba(239, 68, 68, .35);
            color: #fca5a5;
        }

        .integration-logs__badge--gray {
            background: rgba(148, 163, 184, .14);
            border-color: rgba(148, 163, 184, .25);
            color: #cbd5e1;
        }

        .integration-logs__mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: .78rem;
        }

        .integration-logs__link {
            color: #93c5fd;
            text-decoration: none;
        }

        .integration-logs__link:hover,
        .integration-logs__link:focus-visible {
            text-decoration: underline;
        }
    </style>

    <div class="integration-logs">
        <div class="integration-logs__intro">
            <h2 class="integration-logs__title">Logs Hub</h2>
            <p class="integration-logs__subtitle">
                Satu tempat untuk melihat callback yang masuk ke sistem kita dan webhook yang keluar ke partner.
                Incoming berasal dari decision log whitelist, sedangkan outgoing berasal dari delivery log webhook live H2H.
            </p>
            <div class="integration-logs__actions">
                <a href="{{ $connectionsUrl }}" class="integration-logs__action">Open Connections</a>
                <a href="{{ $incomingRulesUrl }}" class="integration-logs__action">Open Incoming Rules</a>
                <a href="{{ $outgoingWebhooksUrl }}" class="integration-logs__action">Open Outgoing Webhooks</a>
            </div>
        </div>

        <div class="integration-logs__summary-grid">
            <section class="integration-logs__summary-card">
                <h3 class="integration-logs__summary-title">Incoming Activity</h3>
                <p class="integration-logs__summary-meta">Decision log terbaru dari middleware inbound whitelist.</p>
                <div class="integration-logs__summary-stats">
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Recent Events</span>
                        <span class="integration-logs__metric-value">{{ $incomingSummary['total'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Matched</span>
                        <span class="integration-logs__metric-value">{{ $incomingSummary['matched'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Denied</span>
                        <span class="integration-logs__metric-value">{{ $incomingSummary['denied'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Warnings</span>
                        <span class="integration-logs__metric-value">{{ $incomingSummary['warnings'] }}</span>
                    </div>
                </div>
            </section>

            <section class="integration-logs__summary-card">
                <h3 class="integration-logs__summary-title">Outgoing Activity</h3>
                <p class="integration-logs__summary-meta">Delivery log terbaru dari webhook outbound live H2H.</p>
                <div class="integration-logs__summary-stats">
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Recent Deliveries</span>
                        <span class="integration-logs__metric-value">{{ $outgoingSummary['total'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Delivered</span>
                        <span class="integration-logs__metric-value">{{ $outgoingSummary['delivered'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Failed</span>
                        <span class="integration-logs__metric-value">{{ $outgoingSummary['failed'] }}</span>
                    </div>
                    <div class="integration-logs__metric">
                        <span class="integration-logs__metric-label">Pending</span>
                        <span class="integration-logs__metric-value">{{ $outgoingSummary['pending'] }}</span>
                    </div>
                </div>
            </section>
        </div>

        <div class="integration-logs__panels">
            <section class="integration-logs__panel">
                <div class="integration-logs__panel-head">
                    <h3 class="integration-logs__panel-title">Incoming</h3>
                    <p class="integration-logs__panel-desc">
                        Callback masuk dari supplier atau payment gateway. Gunakan tabel ini untuk melihat source, IP, mode, decision, dan alasan singkatnya.
                    </p>
                </div>
                @if ($incomingEvents->isEmpty())
                    <div class="integration-logs__empty">Belum ada incoming event yang terekam.</div>
                @else
                    <div class="integration-logs__table-wrap">
                        <table class="integration-logs__table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Source</th>
                                    <th>Client IP</th>
                                    <th>Mode</th>
                                    <th>Decision</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($incomingEvents as $event)
                                    <tr>
                                        <td>{{ optional($event->created_at)->format('d M Y H:i:s') ?: '-' }}</td>
                                        <td>
                                            <div>{{ \App\Filament\Admin\Pages\IntegrationLogs::headline($event->source_domain, 'Unknown') }}</div>
                                            <div class="integration-logs__mono">{{ $event->source_name ?: '-' }}</div>
                                        </td>
                                        <td class="integration-logs__mono">
                                            <div>{{ $event->resolved_client_ip ?: '-' }}</div>
                                            @if (($event->normalized_client_ip ?? null) && $event->normalized_client_ip !== $event->resolved_client_ip)
                                                <div>{{ $event->normalized_client_ip }}</div>
                                            @endif
                                        </td>
                                        <td><span class="integration-logs__badge integration-logs__badge--gray">{{ $event->mode ?: '-' }}</span></td>
                                        <td>
                                            <span class="integration-logs__badge integration-logs__badge--{{ \App\Filament\Admin\Pages\IntegrationLogs::inboundDecisionColor($event->decision) }}">
                                                {{ \App\Filament\Admin\Pages\IntegrationLogs::headline($event->decision) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>{{ \App\Filament\Admin\Pages\IntegrationLogs::headline($event->reason) }}</div>
                                            <div class="integration-logs__mono">{{ $event->route_uri ?: '-' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="integration-logs__panel">
                <div class="integration-logs__panel-head">
                    <h3 class="integration-logs__panel-title">Outgoing</h3>
                    <p class="integration-logs__panel-desc">
                        Webhook keluar ke partner atau reseller. Gunakan tabel ini untuk melihat connection, invoice, URL tujuan, status delivery, dan error terakhir.
                    </p>
                </div>
                @if ($outgoingDeliveries->isEmpty())
                    <div class="integration-logs__empty">Belum ada outgoing delivery yang terekam.</div>
                @else
                    <div class="integration-logs__table-wrap">
                        <table class="integration-logs__table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Connection</th>
                                    <th>Invoice</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                    <th>HTTP</th>
                                    <th>Last Error</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($outgoingDeliveries as $delivery)
                                    <tr>
                                        <td>{{ optional($delivery->last_attempted_at ?? $delivery->created_at)->format('d M Y H:i:s') ?: '-' }}</td>
                                        <td>
                                            <div>{{ $delivery->integration?->integration_code ?: '-' }}</div>
                                            <div class="integration-logs__mono">{{ $delivery->integration?->user?->username ?: '-' }}</div>
                                        </td>
                                        <td>
                                            @if ($delivery->pembelian_id)
                                                <a
                                                    href="{{ \App\Filament\Admin\Resources\Pembelians\PembelianResource::getUrl('view', ['record' => $delivery->pembelian_id]) }}"
                                                    class="integration-logs__link integration-logs__mono"
                                                >
                                                    {{ $delivery->order_id ?: '-' }}
                                                </a>
                                            @else
                                                <span class="integration-logs__mono">{{ $delivery->order_id ?: '-' }}</span>
                                            @endif
                                        </td>
                                        <td class="integration-logs__mono">{{ $delivery->callback_url }}</td>
                                        <td>
                                            <span class="integration-logs__badge integration-logs__badge--{{ \App\Filament\Admin\Pages\IntegrationLogs::outboundStatusColor($delivery->status) }}">
                                                {{ \App\Filament\Admin\Pages\IntegrationLogs::headline($delivery->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $delivery->last_response_status ?? '-' }}</td>
                                        <td>{{ $delivery->last_error ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
