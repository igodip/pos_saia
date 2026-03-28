# POS Saia Backend

Backend Laravel per la gestione di:

- inventario e movimenti di magazzino
- prodotti e varianti
- fornitori
- magazzini
- fatture passive
- righe fattura
- allegati PDF fattura
- rettifiche e conteggi inventariali
- report base

## Requisiti

- PHP 8.2+
- Composer
- database configurato via `.env`
- Node.js solo se serve il frontend asset pipeline

## Setup rapido

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Opzionale:

```bash
npm install
npm run build
```

## Avvio locale

```bash
php artisan serve
```

API di default su `http://127.0.0.1:8000/api`.

## Login backend hardcoded

Endpoint:

```http
POST /api/login
```

Credenziali attuali:

- `username`: `admin`
- `password`: `admin123`

Esempio:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123","device_name":"local-dev"}'
```

La risposta restituisce un token Sanctum da usare come:

```http
Authorization: Bearer <token>
```

## Endpoint principali

### Auth

- `POST /api/login`
- `POST /api/logout`

### Prodotti

- `GET /api/products`
- `POST /api/products`
- `GET /api/products/{id}`
- `PATCH /api/products/{id}`
- `DELETE /api/products/{id}`

### Varianti prodotto

- `GET /api/product-variants`
- `POST /api/product-variants`
- `GET /api/product-variants/{id}`
- `PATCH /api/product-variants/{id}`
- `DELETE /api/product-variants/{id}`

### Fornitori

- `GET /api/suppliers`
- `POST /api/suppliers`
- `GET /api/suppliers/{id}`
- `PATCH /api/suppliers/{id}`
- `DELETE /api/suppliers/{id}`

### Magazzini

- `GET /api/warehouses`
- `POST /api/warehouses`
- `GET /api/warehouses/{id}`
- `PATCH /api/warehouses/{id}`
- `DELETE /api/warehouses/{id}`

### Fatture passive

- `GET /api/purchase-invoices`
- `POST /api/purchase-invoices`
- `GET /api/purchase-invoices/{id}`
- `PATCH /api/purchase-invoices/{id}`
- `DELETE /api/purchase-invoices/{id}`
- `POST /api/purchase-invoices/{purchaseInvoice}/items`
- `PATCH /api/purchase-invoices/{purchaseInvoice}/items/{item}`
- `DELETE /api/purchase-invoices/{purchaseInvoice}/items/{item}`
- `POST /api/purchase-invoices/{purchaseInvoice}/confirm`
- `POST /api/purchase-invoices/{purchaseInvoice}/cancel`
- `POST /api/purchase-invoices/{purchaseInvoice}/attachments`

### Stock

- `GET /api/stock`
- `GET /api/stock-movements`
- `POST /api/stock-adjustments`
- `POST /api/stock-counts`

### Report

- `GET /api/reports/inventory-value`
- `GET /api/reports/stock-by-warehouse`
- `GET /api/reports/low-stock`
- `GET /api/reports/purchases-by-supplier`

## Regole operative importanti

- lo stock deriva dai movimenti, non da una colonna aggiornata direttamente
- la conferma fattura genera movimenti `purchase_load`
- una fattura confermata non puo' essere riconfermata
- una fattura confermata non puo' essere eliminata
- le cancellazioni di anagrafiche gia' usate nello storico vengono bloccate

## Test

```bash
php artisan test
```

## Documentazione aggiuntiva

Guida API rapida:

- [`docs/api.md`](/home/igor/workspace/pos_saia/docs/api.md)

Linee guida per agenti e collaboratori:

- [`agents.md`](/home/igor/workspace/pos_saia/agents.md)
