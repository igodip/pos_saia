# AGENTS.md

## Scopo

Questo repository contiene la fase 1 di un gestionale inventario/acquisti basato su Laravel.
Il focus reale del codice attuale e di ogni futura modifica e':

- anagrafiche prodotti e varianti
- fornitori
- magazzini
- fatture passive
- righe fattura
- allegati PDF alle fatture
- movimenti di magazzino
- rettifiche e conteggi stock
- report base di inventario
- audit log delle operazioni critiche

Fuori scope, salvo richiesta esplicita:

- POS banco
- scontrini e fiscalizzazione retail
- pagamenti
- fatture attive
- contabilita' generale
- multi-tenant

## Stack e baseline

- Laravel 12
- PHP 8.2+ richiesto da `composer.json`
- Laravel Sanctum per auth API
- PHPUnit per i test
- Vite lato frontend minimale
- Database relazionale progettato per PostgreSQL

## Stato attuale del repository

Il progetto non e' solo uno skeleton: esistono gia' dominio, API, migration e test.
Quando lavori qui devi partire dal codice presente, non da assunzioni generiche.

Gia' implementato:

- migration core inventario in [`database/migrations/2026_03_19_000100_create_inventory_core_tables.php`](/home/igor/workspace/pos_saia/database/migrations/2026_03_19_000100_create_inventory_core_tables.php)
- enum di dominio per ruoli, stati fattura e tipi movimento
- azioni applicative per conferma fattura, rettifiche e conteggi stock
- servizi per stock corrente, report valorizzazione e audit log
- API protette da Sanctum in [`routes/api.php`](/home/igor/workspace/pos_saia/routes/api.php)
- gate di autorizzazione in [`app/Providers/AppServiceProvider.php`](/home/igor/workspace/pos_saia/app/Providers/AppServiceProvider.php)
- test feature e unit di base

## Comandi utili

Setup locale:

- `composer install`
- `cp .env.example .env`
- `php artisan key:generate`
- configurare il DB
- `php artisan migrate`
- `npm install`

Comandi ricorrenti:

- `composer test`
- `php artisan test`
- `./vendor/bin/phpunit`
- `./vendor/bin/pint`
- `php artisan migrate:fresh --seed`

Script composer disponibili:

- `composer setup`
- `composer dev`
- `composer test`

## Architettura da rispettare

Struttura logica del progetto:

- `app/Actions`: workflow applicativi transazionali
- `app/Services`: query di dominio, report, servizi di supporto
- `app/Http/Controllers/Api`: controller sottili
- `app/Http/Requests`: validazione e authorize
- `app/Http/Resources`: shape JSON delle risposte
- `app/Models`: persistenza e relazioni
- `app/Enums`: enum PHP del dominio
- `app/Exceptions`: errori di dominio espliciti
- `tests/Feature`: API e autorizzazioni
- `tests/Unit`: regole di dominio e servizi

Regole architetturali:

- non spostare logica di dominio nei controller
- non introdurre logica business pesante nei model event
- ogni operazione che tocca stock o stato documento deve vivere in Action o Service dedicati
- usare Form Request per input HTTP
- usare Resource per output JSON quando l'endpoint restituisce un model o una collection strutturata

## Modello di dominio

Entita' principali:

- `products`
- `product_variants`
- `suppliers`
- `warehouses`
- `purchase_invoices`
- `purchase_invoice_items`
- `stock_movements`
- `stock_counts`
- `attachments`
- `audit_logs`
- `users`

Enum principali:

- [`PurchaseInvoiceStatus.php`](/home/igor/workspace/pos_saia/app/Enums/PurchaseInvoiceStatus.php)
- [`StockMovementType.php`](/home/igor/workspace/pos_saia/app/Enums/StockMovementType.php)
- [`StockMovementDirection.php`](/home/igor/workspace/pos_saia/app/Enums/StockMovementDirection.php)
- [`UserRole.php`](/home/igor/workspace/pos_saia/app/Enums/UserRole.php)

## Invarianti di dominio non negoziabili

### Stock

- la giacenza non e' una fonte primaria modificabile a mano
- la giacenza deriva sempre da `stock_movements`
- rettifiche e conteggi generano nuovi movimenti, non update diretti dello stock

Formula logica:

```text
SUM(IN) - SUM(OUT)
```

### Fatture passive

- una fattura `draft` non altera lo stock
- una fattura `confirmed` genera movimenti `purchase_load`
- una fattura confermata non va riconfermata
- una fattura confermata non va cancellata fisicamente
- `cancelled` e' uno stato logico; eventuali storni futuri devono passare da movimenti opposti, non da cancellazioni

### Conferma fattura

La conferma deve restare:

- esplicita
- transazionale
- idempotente
- auditabile

L'implementazione di riferimento e' [`ConfirmPurchaseInvoiceAction.php`](/home/igor/workspace/pos_saia/app/Actions/ConfirmPurchaseInvoiceAction.php).

Non introdurre mai una modifica che:

- crei movimenti fuori transazione
- permetta doppia conferma
- salti il lock del documento
- confermi una fattura senza righe

### Audit

Ogni operazione critica deve registrare almeno:

- tipo evento
- entita' coinvolta
- utente
- timestamp
- payload minimo utile alla ricostruzione

Servizio corrente: [`AuditLogService.php`](/home/igor/workspace/pos_saia/app/Services/AuditLogService.php)

## API attuali

Endpoint pubblici:

- `POST /api/login`

Endpoint autenticati:

- `POST /api/logout`
- `POST /api/products`
- `GET /api/products`
- `GET /api/products/{id}`
- `PATCH /api/products/{id}`
- `POST /api/product-variants`
- `GET /api/product-variants`
- `PATCH /api/product-variants/{id}`
- `POST /api/suppliers`
- `GET /api/suppliers`
- `GET /api/suppliers/{id}`
- `PATCH /api/suppliers/{id}`
- `POST /api/warehouses`
- `GET /api/warehouses`
- `PATCH /api/warehouses/{id}`
- `POST /api/purchase-invoices`
- `GET /api/purchase-invoices`
- `GET /api/purchase-invoices/{id}`
- `PATCH /api/purchase-invoices/{id}`
- `POST /api/purchase-invoices/{purchaseInvoice}/items`
- `PATCH /api/purchase-invoices/{purchaseInvoice}/items/{item}`
- `POST /api/purchase-invoices/{purchaseInvoice}/confirm`
- `POST /api/purchase-invoices/{purchaseInvoice}/cancel`
- `POST /api/purchase-invoices/{purchaseInvoice}/attachments`
- `GET /api/stock`
- `GET /api/stock-movements`
- `POST /api/stock-adjustments`
- `POST /api/stock-counts`
- `GET /api/reports/inventory-value`
- `GET /api/reports/stock-by-warehouse`
- `GET /api/reports/low-stock`
- `GET /api/reports/purchases-by-supplier`

Fonte canonica: [`routes/api.php`](/home/igor/workspace/pos_saia/routes/api.php)

## Autorizzazioni

Ruoli supportati:

- `admin`
- `warehouse`
- `accounting`
- `viewer`

Gate correnti:

- `view-master-data`
- `manage-master-data`
- `view-purchase-invoices`
- `manage-purchase-invoices`
- `confirm-purchase-invoices`
- `view-stock`
- `manage-stock`
- `view-reports`

Regola pratica:

- `admin` bypassa tramite `Gate::before`
- non affidarti alla UI
- ogni nuova action o endpoint deve esplicitare il gate corretto

Riferimento: [`app/Providers/AppServiceProvider.php`](/home/igor/workspace/pos_saia/app/Providers/AppServiceProvider.php)

## Configurazione

Le costanti variabili di dominio vanno in config, non hardcoded.
Configurazione attuale:

- [`config/inventory.php`](/home/igor/workspace/pos_saia/config/inventory.php)

Da mantenere configurabile:

- valuta di default
- metodo di valorizzazione stock
- disk allegati
- dimensione massima upload
- MIME consentiti

## Convenzioni di implementazione

### Migrations

- ogni modifica schema passa da migration versionata
- usare foreign key esplicite
- aggiungere indici coerenti con i filtri reali
- non alterare manualmente il DB come sostituto di una migration

### Model ed Eloquent

- cast tipizzati dove servono
- enum PHP per gli stati e i tipi chiusi
- eager loading consapevole per evitare N+1
- query di aggregazione e report centralizzate nei servizi

### Request validation

- importi >= 0
- quantita' > 0 dove richiesto
- controllare lo stato prima delle transizioni
- verificare l'esistenza dei riferimenti esterni
- gli upload devono essere limitati per MIME e size

### API output

- mantenere payload coerenti tra endpoint simili
- non esporre path interni di storage se non strettamente necessario
- se si introduce un nuovo endpoint, aggiungere Resource o shape JSON chiara e consistente

### Error handling

Preferire eccezioni di dominio dedicate quando il caso e' prevedibile.
Esempi gia' presenti:

- [`PurchaseInvoiceAlreadyConfirmedException.php`](/home/igor/workspace/pos_saia/app/Exceptions/PurchaseInvoiceAlreadyConfirmedException.php)
- [`PurchaseInvoiceHasNoItemsException.php`](/home/igor/workspace/pos_saia/app/Exceptions/PurchaseInvoiceHasNoItemsException.php)
- [`InvalidStockAdjustmentException.php`](/home/igor/workspace/pos_saia/app/Exceptions/InvalidStockAdjustmentException.php)

## Testing richiesto

Ogni modifica che tocca stock, fatture o permessi deve arrivare con test.

Copertura minima attesa:

- creazione anagrafiche
- creazione fattura draft
- aggiunta righe fattura
- conferma fattura
- blocco doppia conferma
- generazione movimenti stock
- rettifiche inventario
- conteggi stock
- autorizzazioni per ruolo
- servizi di calcolo giacenza e report

Test esistenti di riferimento:

- [`tests/Feature/InventoryApiTest.php`](/home/igor/workspace/pos_saia/tests/Feature/InventoryApiTest.php)
- [`tests/Unit/InventoryDomainTest.php`](/home/igor/workspace/pos_saia/tests/Unit/InventoryDomainTest.php)

Regola semplice:

- nessuna feature che impatta stock e' completa senza test automatici

## Performance e qualita' dati

- indicizzare chiavi di ricerca come SKU, barcode, stato, supplier, warehouse e variant
- paginare gli elenchi quando il dataset cresce
- evitare report sincroni costosi senza misurazione
- introdurre materializzazioni o aggregate solo dopo evidenza reale

## Cose da non fare

- non aggiungere una colonna `current_stock` come fonte primaria modificabile
- non confermare fatture in modo implicito durante create o update
- non usare job async per una parte di workflow che deve restare atomica
- non cancellare movimenti storici per "correggere" i dati
- non spostare tutta la logica nei controller per comodita'
- non introdurre funzionalita' POS o retail fuori scope
- non aggiungere pacchetti esterni se Laravel core copre gia' il caso

## Workflow per agenti e collaboratori

Quando modifichi questo progetto:

1. leggi prima migration, enum, action e test correlati
2. allinea il cambiamento agli invarianti di dominio sopra
3. aggiorna request, policy/gate, action/service e resource insieme
4. aggiungi o aggiorna i test nello stesso intervento
5. esegui almeno i test pertinenti
6. aggiorna questa documentazione se cambi workflow, API o regole di dominio

Se devi scegliere tra:

- una scorciatoia veloce che rende i dati meno affidabili
- una soluzione leggermente piu' rigorosa ma auditabile ed estendibile

scegli la seconda.

## Definition of Done

Una task e' finita solo se:

- codice implementato
- migration presenti se servono
- validazioni presenti
- autorizzazioni presenti
- test presenti e verdi almeno sul perimetro toccato
- nessun side effect su stock non tracciato
- output API coerente
- documentazione aggiornata se il comportamento cambia
