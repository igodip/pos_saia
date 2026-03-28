@extends('admin.layout', ['title' => 'Dettaglio fattura'])

@section('body')
    @include('admin.partials.header')

    <div class="shell">
        <div class="content">
            <div class="panel">
                <div class="panel-head">
                    <div>
                        <div class="badge">Fattura {{ $invoice->invoice_number }}</div>
                        <h2 style="margin-bottom: 6px;">Dettaglio documento</h2>
                        <div class="muted">Stato attuale: {{ $invoice->status->value }}</div>
                    </div>
                    <div class="actions">
                        @if ($invoice->status->value === 'draft')
                            <form method="POST" action="{{ route('admin.invoices.confirm', $invoice) }}" class="inline">
                                @csrf
                                <button class="btn success" type="submit">Conferma fattura</button>
                            </form>
                            <form method="POST" action="{{ route('admin.invoices.cancel', $invoice) }}" class="inline">
                                @csrf
                                <button class="btn secondary" type="submit">Annulla</button>
                            </form>
                            <form method="POST" action="{{ route('admin.invoices.destroy', $invoice) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn danger" type="submit">Elimina</button>
                            </form>
                        @endif
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.invoices.update', $invoice) }}" class="form-grid">
                    @csrf
                    @method('PATCH')
                    <label>Fornitore
                        <select name="supplier_id" required @disabled($invoice->status->value !== 'draft')>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected($supplier->id === $invoice->supplier_id)>{{ $supplier->company_name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Magazzino
                        <select name="warehouse_id" required @disabled($invoice->status->value !== 'draft')>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" @selected($warehouse->id === $invoice->warehouse_id)>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Numero fattura<input type="text" name="invoice_number" value="{{ $invoice->invoice_number }}" required @disabled($invoice->status->value !== 'draft')></label>
                    <label>Data fattura<input type="date" name="invoice_date" value="{{ optional($invoice->invoice_date)->toDateString() }}" required @disabled($invoice->status->value !== 'draft')></label>
                    <label>Scadenza<input type="date" name="due_date" value="{{ optional($invoice->due_date)->toDateString() }}" @disabled($invoice->status->value !== 'draft')></label>
                    <label>Valuta<input type="text" name="currency" value="{{ $invoice->currency }}" maxlength="3" required @disabled($invoice->status->value !== 'draft')></label>
                    <label>Imponibile<input type="number" step="0.01" name="taxable_amount" value="{{ $invoice->taxable_amount }}" required @disabled($invoice->status->value !== 'draft')></label>
                    <label>IVA<input type="number" step="0.01" name="vat_amount" value="{{ $invoice->vat_amount }}" required @disabled($invoice->status->value !== 'draft')></label>
                    <label>Totale<input type="number" step="0.01" name="total_amount" value="{{ $invoice->total_amount }}" required @disabled($invoice->status->value !== 'draft')></label>
                    <label class="cols-12">Note<textarea name="notes" @disabled($invoice->status->value !== 'draft')>{{ $invoice->notes }}</textarea></label>
                    @if ($invoice->status->value === 'draft')
                        <button class="btn" type="submit">Salva intestazione</button>
                    @endif
                </form>
            </div>

            <div class="panel">
                <h2>Righe fattura</h2>

                @if ($invoice->status->value === 'draft')
                    <form method="POST" action="{{ route('admin.invoices.items.store', $invoice) }}" class="form-grid" style="margin-bottom: 18px;">
                        @csrf
                        <label>Variante
                            <select name="product_variant_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->product?->name }} / {{ $variant->variant_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Descrizione<input type="text" name="description" required></label>
                        <label>Quantita'<input type="number" step="0.001" name="qty" required></label>
                        <label>Prezzo unitario<input type="number" step="0.01" name="unit_price" required></label>
                        <label>Sconto<input type="number" step="0.01" name="discount_amount" value="0"></label>
                        <label>IVA<input type="number" step="0.01" name="vat_rate" value="22" required></label>
                        <label>Totale riga<input type="number" step="0.01" name="line_total" required></label>
                        <button class="btn" type="submit">Aggiungi riga</button>
                    </form>
                @endif

                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Variante</th>
                            <th>Descrizione</th>
                            <th>Qty</th>
                            <th>Prezzo</th>
                            <th>Totale</th>
                            <th>Azioni</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($invoice->items as $item)
                            <tr>
                                <td>{{ $item->productVariant?->product?->name }} / {{ $item->productVariant?->variant_name }}</td>
                                <td>{{ $item->description }}</td>
                                <td>{{ number_format((float) $item->qty, 3, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->unit_price, 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $item->line_total, 2, ',', '.') }}</td>
                                <td>
                                    @if ($invoice->status->value === 'draft')
                                        <form method="POST" action="{{ route('admin.invoices.items.destroy', [$invoice, $item]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Elimina</button>
                                        </form>
                                    @else
                                        <span class="muted">Storico bloccato</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Nessuna riga presente.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
