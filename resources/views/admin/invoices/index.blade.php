@extends('admin.layout', ['title' => 'Fatture'])

@section('body')
    @include('admin.partials.header')

    <div class="shell">
        <div class="content">
            <div class="panel">
                <h2>Nuova fattura passiva</h2>
                <form method="POST" action="{{ route('admin.invoices.store') }}" class="form-grid">
                    @csrf
                    <label>Fornitore
                        <select name="supplier_id" required>
                            <option value="">Seleziona</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->company_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Magazzino
                        <select name="warehouse_id" required>
                            <option value="">Seleziona</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Numero fattura<input type="text" name="invoice_number" required></label>
                    <label>Data fattura<input type="date" name="invoice_date" required></label>
                    <label>Scadenza<input type="date" name="due_date"></label>
                    <label>Valuta<input type="text" name="currency" value="EUR" maxlength="3"></label>
                    <label>Imponibile<input type="number" step="0.01" name="taxable_amount" required></label>
                    <label>IVA<input type="number" step="0.01" name="vat_amount" required></label>
                    <label>Totale<input type="number" step="0.01" name="total_amount" required></label>
                    <label class="cols-12">Note<textarea name="notes"></textarea></label>
                    <button class="btn" type="submit">Crea fattura draft</button>
                </form>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Fatture registrate</h2>
                        <div class="muted">Apri il dettaglio per modificare righe, confermare o annullare.</div>
                    </div>
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
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td>{{ $invoice->supplier?->company_name }}</td>
                                <td>{{ $invoice->warehouse?->name }}</td>
                                <td>{{ optional($invoice->invoice_date)->format('d/m/Y') }}</td>
                                <td>{{ $invoice->status->value }}</td>
                                <td>{{ number_format((float) $invoice->total_amount, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Nessuna fattura.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 16px;">{{ $invoices->links() }}</div>
            </div>
        </div>
    </div>
@endsection
