@extends('admin.layout', ['title' => 'Magazzino'])

@section('body')
    @include('admin.partials.header')

    <div class="shell">
        <div class="content">
            <div class="grid">
                <div class="panel cols-6">
                    <h2>Rettifica stock</h2>
                    <form method="POST" action="{{ route('admin.stock-adjustments.store') }}" class="form-grid">
                        @csrf
                        <label>Magazzino
                            <select name="warehouse_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Variante
                            <select name="product_variant_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->product?->name }} / {{ $variant->variant_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Tipo
                            <select name="movement_type" required>
                                <option value="adjustment_in">adjustment_in</option>
                                <option value="adjustment_out">adjustment_out</option>
                            </select>
                        </label>
                        <label>Quantita'
                            <input type="number" step="0.001" name="qty" required>
                        </label>
                        <label>Costo unitario
                            <input type="number" step="0.01" name="unit_cost">
                        </label>
                        <label class="cols-12">Note
                            <textarea name="notes"></textarea>
                        </label>
                        <button class="btn" type="submit">Registra rettifica</button>
                    </form>
                </div>

                <div class="panel cols-6">
                    <h2>Conteggio inventariale</h2>
                    <form method="POST" action="{{ route('admin.stock-counts.store') }}" class="form-grid">
                        @csrf
                        <label>Magazzino
                            <select name="warehouse_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Variante
                            <select name="product_variant_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->product?->name }} / {{ $variant->variant_name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Quantita' rilevata
                            <input type="number" step="0.001" name="counted_qty" required>
                        </label>
                        <label>Data conteggio
                            <input type="datetime-local" name="counted_at">
                        </label>
                        <label>Costo unitario
                            <input type="number" step="0.01" name="unit_cost">
                        </label>
                        <label class="cols-12">Note
                            <textarea name="notes"></textarea>
                        </label>
                        <button class="btn success" type="submit">Registra conteggio</button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <div>
                        <h2>Stock corrente</h2>
                        <div class="muted">Snapshot aggregato per magazzino e variante.</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Magazzino</th>
                            <th>Prodotto</th>
                            <th>Variante</th>
                            <th>SKU</th>
                            <th>Qty</th>
                            <th>Ultimo movimento</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($snapshot as $row)
                            <tr>
                                <td>{{ $row->warehouse_name }}</td>
                                <td>{{ $row->product_name }}</td>
                                <td>{{ $row->variant_name }}</td>
                                <td>{{ $row->variant_sku }}</td>
                                <td>{{ number_format((float) $row->current_qty, 3, ',', '.') }}</td>
                                <td>{{ $row->last_movement_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Nessun dato stock disponibile.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="split">
                <div class="panel">
                    <h2>Prodotti</h2>
                    <form method="POST" action="{{ route('admin.products.store') }}" class="form-grid" style="margin-bottom: 16px;">
                        @csrf
                        <label>SKU<input type="text" name="sku" required></label>
                        <label>Nome<input type="text" name="name" required></label>
                        <label>Barcode<input type="text" name="barcode"></label>
                        <label>IVA<input type="number" step="0.01" name="vat_rate" value="22" required></label>
                        <label>Costo<input type="number" step="0.01" name="default_cost"></label>
                        <label>Prezzo<input type="number" step="0.01" name="default_price"></label>
                        <label>Soglia riordino<input type="number" step="0.001" name="reorder_level"></label>
                        <label>Categoria<input type="text" name="category"></label>
                        <label>Brand<input type="text" name="brand"></label>
                        <label class="cols-12">Descrizione<textarea name="description"></textarea></label>
                        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Attivo</label>
                        <button class="btn" type="submit">Aggiungi prodotto</button>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Prodotto</th><th>SKU</th><th>Varianti</th><th>Azioni</th></tr></thead>
                            <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->sku }}</td>
                                    <td>{{ $product->variants->count() }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">Nessun prodotto.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <h2>Varianti</h2>
                    <form method="POST" action="{{ route('admin.variants.store') }}" class="form-grid" style="margin-bottom: 16px;">
                        @csrf
                        <label>Prodotto
                            <select name="product_id" required>
                                <option value="">Seleziona</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>SKU<input type="text" name="sku" required></label>
                        <label>Nome variante<input type="text" name="variant_name" required></label>
                        <label>Barcode<input type="text" name="barcode"></label>
                        <label>Costo<input type="number" step="0.01" name="default_cost"></label>
                        <label>Prezzo<input type="number" step="0.01" name="default_price"></label>
                        <label class="cols-12">Attributi JSON<textarea name="attributes_json" placeholder='{"size":"1kg"}'></textarea></label>
                        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Attiva</label>
                        <button class="btn" type="submit">Aggiungi variante</button>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Variante</th><th>Prodotto</th><th>SKU</th><th>Azioni</th></tr></thead>
                            <tbody>
                            @forelse ($variants as $variant)
                                <tr>
                                    <td>{{ $variant->variant_name }}</td>
                                    <td>{{ $variant->product?->name }}</td>
                                    <td>{{ $variant->sku }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.variants.destroy', $variant) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">Nessuna variante.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="split">
                <div class="panel">
                    <h2>Fornitori</h2>
                    <form method="POST" action="{{ route('admin.suppliers.store') }}" class="form-grid" style="margin-bottom: 16px;">
                        @csrf
                        <label>Ragione sociale<input type="text" name="company_name" required></label>
                        <label>P.IVA<input type="text" name="vat_number"></label>
                        <label>Codice fiscale<input type="text" name="tax_code"></label>
                        <label>Email<input type="email" name="email"></label>
                        <label>Telefono<input type="text" name="phone"></label>
                        <label>Condizioni pagamento<input type="text" name="payment_terms"></label>
                        <label class="cols-12">Indirizzo<textarea name="address"></textarea></label>
                        <label class="cols-12">Note<textarea name="notes"></textarea></label>
                        <button class="btn" type="submit">Aggiungi fornitore</button>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Fornitore</th><th>P.IVA</th><th>Email</th><th>Azioni</th></tr></thead>
                            <tbody>
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td>{{ $supplier->company_name }}</td>
                                    <td>{{ $supplier->vat_number }}</td>
                                    <td>{{ $supplier->email }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.suppliers.destroy', $supplier) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">Nessun fornitore.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <h2>Magazzini</h2>
                    <form method="POST" action="{{ route('admin.warehouses.store') }}" class="form-grid" style="margin-bottom: 16px;">
                        @csrf
                        <label>Nome<input type="text" name="name" required></label>
                        <label>Codice<input type="text" name="code" required></label>
                        <label>Indirizzo<input type="text" name="address"></label>
                        <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" checked> Attivo</label>
                        <button class="btn" type="submit">Aggiungi magazzino</button>
                    </form>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Magazzino</th><th>Codice</th><th>Attivo</th><th>Azioni</th></tr></thead>
                            <tbody>
                            @forelse ($warehouses as $warehouse)
                                <tr>
                                    <td>{{ $warehouse->name }}</td>
                                    <td>{{ $warehouse->code }}</td>
                                    <td>{{ $warehouse->is_active ? 'si' : 'no' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.warehouses.destroy', $warehouse) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn danger" type="submit">Elimina</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">Nessun magazzino.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Movimenti recenti</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Quando</th><th>Tipo</th><th>Prodotto</th><th>Magazzino</th><th>Qty</th><th>Note</th></tr></thead>
                        <tbody>
                        @forelse ($recentMovements as $movement)
                            <tr>
                                <td>{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $movement->movement_type->value }}</td>
                                <td>{{ $movement->productVariant?->product?->name }} / {{ $movement->productVariant?->variant_name }}</td>
                                <td>{{ $movement->warehouse?->name }}</td>
                                <td>{{ $movement->direction->value }} {{ number_format((float) $movement->qty, 3, ',', '.') }}</td>
                                <td>{{ $movement->notes }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">Nessun movimento.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
