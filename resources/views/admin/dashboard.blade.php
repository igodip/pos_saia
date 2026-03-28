@extends('admin.layout', ['title' => 'Dashboard'])

@section('body')
    @include('admin.partials.header')

    <div class="shell">
        <div class="content">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <div class="badge">Snapshot DB</div>
                        <h2 style="margin-bottom: 6px;">Stato attuale del backend</h2>
                        <div class="muted">Vista sintetica del database e dell'operativita' attuale.</div>
                    </div>
                    <div class="badge success">Valore inventario: {{ number_format($inventoryValue, 2, ',', '.') }} EUR</div>
                </div>

                <div class="kpi-grid">
                    @foreach ($summary as $label => $value)
                        <div class="kpi">
                            <div class="label">{{ str_replace('_', ' ', $label) }}</div>
                            <div class="value">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid">
                <div class="panel cols-4">
                    <h3>Stati fatture</h3>
                    <div class="content">
                        <div class="kpi">
                            <div class="label">Draft</div>
                            <div class="value">{{ $statusBreakdown['draft'] ?? 0 }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Confirmed</div>
                            <div class="value">{{ $statusBreakdown['confirmed'] ?? 0 }}</div>
                        </div>
                        <div class="kpi">
                            <div class="label">Cancelled</div>
                            <div class="value">{{ $statusBreakdown['cancelled'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="panel cols-8">
                    <div class="panel-head">
                        <div>
                            <h3>Ultime fatture</h3>
                            <div class="muted">Accesso rapido ai documenti piu' recenti.</div>
                        </div>
                        <a class="btn secondary" href="{{ route('admin.invoices.index') }}">Apri gestione fatture</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Fornitore</th>
                                <th>Magazzino</th>
                                <th>Data</th>
                                <th>Stato</th>
                                <th>Totale</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($recentInvoices as $invoice)
                                <tr>
                                    <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                    <td>{{ $invoice->supplier?->company_name }}</td>
                                    <td>{{ $invoice->warehouse?->name }}</td>
                                    <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                                    <td>{{ $invoice->status->value }}</td>
                                    <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="muted">Nessuna fattura registrata.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel cols-6">
                    <div class="panel-head">
                        <div>
                            <h3>Low stock</h3>
                            <div class="muted">Varianti sotto soglia di riordino.</div>
                        </div>
                        <a class="btn secondary" href="{{ route('admin.inventory') }}">Apri magazzino</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Magazzino</th>
                                <th>Prodotto</th>
                                <th>Variante</th>
                                <th>Qty</th>
                                <th>Soglia</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($lowStock as $row)
                                <tr>
                                    <td>{{ $row->warehouse_name }}</td>
                                    <td>{{ $row->product_name }}</td>
                                    <td>{{ $row->variant_name }}</td>
                                    <td>{{ number_format((float) $row->current_qty, 3, ',', '.') }}</td>
                                    <td>{{ number_format((float) $row->reorder_level, 3, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Nessuna criticita' rilevata.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel cols-6">
                    <h3>Ultimi movimenti</h3>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Quando</th>
                                <th>Tipo</th>
                                <th>Prodotto</th>
                                <th>Magazzino</th>
                                <th>Qty</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($recentMovements as $movement)
                                <tr>
                                    <td>{{ optional($movement->created_at)->format('d/m H:i') }}</td>
                                    <td>{{ $movement->movement_type->value }}</td>
                                    <td>{{ $movement->productVariant?->product?->name }} / {{ $movement->productVariant?->variant_name }}</td>
                                    <td>{{ $movement->warehouse?->name }}</td>
                                    <td>{{ $movement->direction->value }} {{ number_format((float) $movement->qty, 3, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="muted">Nessun movimento registrato.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
