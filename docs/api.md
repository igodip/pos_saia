# API Guide

## Base URL

`/api`

In locale:

`http://127.0.0.1:8000/api`

## Autenticazione

Login con credenziali hardcoded:

- `username`: `admin`
- `password`: `admin123`

Request:

```http
POST /api/login
Content-Type: application/json

{
  "username": "admin",
  "password": "admin123",
  "device_name": "local-dev"
}
```

Response esempio:

```json
{
  "token": "1|sanctum-token",
  "user": {
    "id": 1,
    "name": "Backend Admin",
    "email": "admin@pos-saia.local",
    "username": "admin",
    "role": "admin"
  }
}
```

Usare poi:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Esempi rapidi

### Creare un prodotto

```bash
curl -X POST http://127.0.0.1:8000/api/products \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "sku":"PRD-001",
    "name":"Arabica Coffee",
    "vat_rate":22,
    "default_cost":4.50,
    "default_price":9.90,
    "reorder_level":5,
    "is_active":true
  }'
```

### Creare una variante

```bash
curl -X POST http://127.0.0.1:8000/api/product-variants \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id":1,
    "sku":"PRD-001-1KG",
    "variant_name":"1kg",
    "default_cost":4.50,
    "default_price":9.90,
    "is_active":true
  }'
```

### Creare una fattura passiva draft

```bash
curl -X POST http://127.0.0.1:8000/api/purchase-invoices \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "supplier_id":1,
    "warehouse_id":1,
    "invoice_number":"INV-100",
    "invoice_date":"2026-03-28",
    "taxable_amount":100,
    "vat_amount":22,
    "total_amount":122
  }'
```

### Aggiungere una riga fattura

```bash
curl -X POST http://127.0.0.1:8000/api/purchase-invoices/1/items \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "product_variant_id":1,
    "description":"Coffee 1kg",
    "qty":10,
    "unit_price":5,
    "discount_amount":0,
    "vat_rate":22,
    "line_total":50
  }'
```

### Confermare la fattura

```bash
curl -X POST http://127.0.0.1:8000/api/purchase-invoices/1/confirm \
  -H "Authorization: Bearer <token>"
```

### Rettifica inventario

```bash
curl -X POST http://127.0.0.1:8000/api/stock-adjustments \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "warehouse_id":1,
    "product_variant_id":1,
    "movement_type":"adjustment_in",
    "qty":4,
    "unit_cost":3,
    "notes":"Initial correction"
  }'
```

### Snapshot stock

```bash
curl http://127.0.0.1:8000/api/stock \
  -H "Authorization: Bearer <token>"
```

## Filtri utili

### `GET /api/products`

Query supportata:

- `search`

### `GET /api/product-variants`

Query supportata:

- `search`

### `GET /api/suppliers`

Query supportata:

- `search`

### `GET /api/warehouses`

Query supportata:

- `search`

### `GET /api/purchase-invoices`

Query supportata:

- `supplier_id`
- `warehouse_id`
- `status`
- `date_from`
- `date_to`

## Vincoli di cancellazione

- un prodotto non puo' essere eliminato se ha varianti
- una variante non puo' essere eliminata se usata in righe fattura, movimenti o conteggi
- un fornitore non puo' essere eliminato se collegato a fatture
- un magazzino non puo' essere eliminato se collegato a fatture, movimenti o conteggi
- una fattura `confirmed` non puo' essere eliminata

## Codici attesi

- `200` per letture e update
- `201` per creazione
- `204` per delete e logout
- `401` se manca o e' invalido il token
- `403` se il ruolo non ha il permesso
- `422` se la validazione fallisce o una regola di dominio blocca l'operazione
