# Italy POS + Fiscal Receipt System Blueprint

## Goal

Build a custom POS platform for the Italian market where the application logic, catalog, pricing, reporting, and integrations are fully under your control, while the fiscal issuance layer is isolated behind a replaceable adapter.

This blueprint assumes:

* multi-store support
* tablet/desktop POS clients
* card/cash/split payments
* Italian VAT handling
* fiscal receipt issuance through either:

  * an RT / fiscal printer adapter, or
  * a certified fiscal API provider adapter
* PostgreSQL as primary database
* a backend-first architecture

## Non-goals

This blueprint does not attempt to make you a fiscal compliance provider from zero. The design keeps your system ready for that path, but the practical deployment target is:

* your POS application
* your own backend and data model
* a pluggable fiscal connector layer

---

# 1. High-level architecture

## Core components

### POS Client

Runs on tablet, desktop, or browser kiosk.

Responsibilities:

* operator login
* open/close shift
* cart management
* barcode scanning
* product search
* discounts and promotions
* payment collection
* receipt preview
* return flows
* reprint / resend flows
* offline queue for non-fiscal actions if needed

### POS Backend API

Owns the business domain and state machine.

Responsibilities:

* sales lifecycle
* cart finalization
* pricing engine
* tax/VAT calculation
* payment orchestration
* fiscal job creation
* reporting APIs
* audit trail
* device/store management

### Payment Service

Abstracts external payment providers.

Responsibilities:

* card terminal session start
* payment authorization/capture
* reversals where available
* storing acquirer references
* reconciliation export

### Fiscal Service

Isolates all fiscal logic from the rest of the system.

Responsibilities:

* canonical fiscal document model
* adapter selection by store/terminal
* payload transformation
* idempotency control
* retries and recovery
* closure management
* return / refund / storno flows
* raw request/response archival

### Fiscal Adapter(s)

Replaceable implementations.

Possible adapters:

* `rt_printer_adapter`
* `fiscal_api_adapter`
* `sandbox_adapter`

### Store Agent

Required if using local devices such as RT printers, cash drawers, local ESC/POS printers, scanners, display devices.

Responsibilities:

* local job polling or websocket subscription
* communication with RT/fiscal printer
* receipt print job handling
* health checks
* durable queue for transient outages

### Reporting / Reconciliation Service

Responsibilities:

* daily totals
* VAT summary
* sales by store/operator/terminal
* payment vs fiscal reconciliation
* closure report views
* exception dashboards

### Admin Console

Responsibilities:

* product catalog
* price lists
* operator permissions
* store/terminal provisioning
* fiscal adapter configuration
* monitoring failed fiscal jobs

---

# 2. Recommended deployment shape

## Central services

* Backend API
* Payment service
* Fiscal service
* PostgreSQL
* Redis
* Object storage for raw payload archives and rendered receipts

## Store-side services

Only needed if you use local hardware.

* Store Agent
* optional local cache
* optional local print service

## Suggested production stack

* Backend: Laravel
* Store Agent: Rust or Node.js
* Database: PostgreSQL
* Queue: Redis-backed queue or Postgres outbox + worker
* Frontend POS: Flutter, React, or Electron
* Infra: Docker Compose for dev, Kubernetes or plain containers/VPS for prod

---

# 3. Domain model

## Store

```sql
create table stores (
  id uuid primary key,
  code text unique not null,
  name text not null,
  country_code text not null default 'IT',
  vat_number text,
  fiscal_regime text,
  timezone text not null default 'Europe/Rome',
  is_active boolean not null default true,
  created_at timestamptz not null default now()
);
```

## Terminal

```sql
create table terminals (
  id uuid primary key,
  store_id uuid not null references stores(id),
  code text not null,
  name text,
  device_type text not null,
  fiscal_mode text not null,
  fiscal_adapter text not null,
  device_identifier text,
  rt_serial_number text,
  payment_terminal_id text,
  is_active boolean not null default true,
  created_at timestamptz not null default now(),
  unique(store_id, code)
);
```

## Operators

```sql
create table operators (
  id uuid primary key,
  store_id uuid references stores(id),
  username text unique not null,
  display_name text not null,
  pin_hash text,
  role text not null,
  is_active boolean not null default true,
  created_at timestamptz not null default now()
);
```

## Products

```sql
create table products (
  id uuid primary key,
  sku text unique not null,
  barcode text,
  name text not null,
  department_code text,
  unit_of_measure text not null default 'pcs',
  vat_code text not null,
  price_gross numeric(12,2) not null,
  is_active boolean not null default true,
  created_at timestamptz not null default now()
);
```

## VAT codes

```sql
create table vat_codes (
  code text primary key,
  description text not null,
  rate numeric(5,2) not null,
  nature_code text,
  is_active boolean not null default true
);
```

## Sales

```sql
create table sales (
  id uuid primary key,
  store_id uuid not null references stores(id),
  terminal_id uuid not null references terminals(id),
  operator_id uuid references operators(id),
  business_date date not null,
  status text not null,
  currency text not null default 'EUR',
  sale_type text not null default 'SALE',
  customer_id uuid,
  subtotal_gross numeric(12,2) not null default 0,
  discount_total numeric(12,2) not null default 0,
  total_gross numeric(12,2) not null default 0,
  total_net numeric(12,2) not null default 0,
  total_vat numeric(12,2) not null default 0,
  opened_at timestamptz not null default now(),
  finalized_at timestamptz,
  cancelled_at timestamptz,
  created_at timestamptz not null default now()
);
```

## Sale lines

```sql
create table sale_lines (
  id uuid primary key,
  sale_id uuid not null references sales(id) on delete cascade,
  line_no integer not null,
  product_id uuid references products(id),
  sku text,
  barcode text,
  description text not null,
  quantity numeric(12,3) not null,
  unit_price_gross numeric(12,4) not null,
  line_discount_gross numeric(12,2) not null default 0,
  line_total_gross numeric(12,2) not null,
  line_total_net numeric(12,2) not null,
  line_vat_amount numeric(12,2) not null,
  vat_code text not null references vat_codes(code),
  department_code text,
  metadata jsonb not null default '{}'::jsonb,
  unique(sale_id, line_no)
);
```

## Payments

```sql
create table payments (
  id uuid primary key,
  sale_id uuid not null references sales(id) on delete cascade,
  payment_type text not null,
  amount numeric(12,2) not null,
  status text not null,
  provider text,
  authorization_ref text,
  acquirer_ref text,
  external_tx_id text,
  terminal_ref text,
  raw_response jsonb,
  created_at timestamptz not null default now()
);
```

## Fiscal documents

```sql
create table fiscal_documents (
  id uuid primary key,
  sale_id uuid not null references sales(id),
  store_id uuid not null references stores(id),
  terminal_id uuid not null references terminals(id),
  adapter_name text not null,
  provider_document_id text,
  device_id text,
  receipt_number text,
  lottery_code text,
  issue_status text not null,
  issue_ts timestamptz,
  business_date date not null,
  total_gross numeric(12,2) not null,
  payload_hash text not null,
  idempotency_key text not null,
  raw_request jsonb,
  raw_response jsonb,
  rendered_receipt_url text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(adapter_name, idempotency_key)
);
```

## Fiscal jobs

```sql
create table fiscal_jobs (
  id uuid primary key,
  sale_id uuid not null references sales(id),
  fiscal_document_id uuid references fiscal_documents(id),
  adapter_name text not null,
  job_type text not null,
  status text not null,
  retry_count integer not null default 0,
  next_retry_at timestamptz,
  locked_at timestamptz,
  locked_by text,
  last_error_code text,
  last_error_message text,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
```

## Closures

```sql
create table fiscal_closures (
  id uuid primary key,
  store_id uuid not null references stores(id),
  terminal_id uuid references terminals(id),
  business_date date not null,
  adapter_name text not null,
  closure_type text not null,
  status text not null,
  z_report_id text,
  raw_request jsonb,
  raw_response jsonb,
  total_sales numeric(12,2),
  total_returns numeric(12,2),
  total_cash numeric(12,2),
  total_card numeric(12,2),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique(store_id, terminal_id, business_date, closure_type)
);
```

## Audit events

```sql
create table audit_events (
  id bigserial primary key,
  aggregate_type text not null,
  aggregate_id uuid not null,
  event_type text not null,
  actor_type text,
  actor_id text,
  payload jsonb not null,
  created_at timestamptz not null default now()
);
```

---

# 4. Core state machines

## Sale state machine

```text
DRAFT
  -> PAYMENT_PENDING
  -> PAYMENT_AUTHORIZED
  -> FISCAL_PENDING
  -> FISCAL_SUBMITTING
  -> FISCAL_ACCEPTED
  -> RECEIPT_DELIVERED
```

Failure states:

```text
PAYMENT_FAILED
FISCAL_ERROR_RETRYABLE
FISCAL_ERROR_MANUAL
CANCELLED
VOIDED
RETURNED
```

Rules:

* sale lines can change only in `DRAFT`
* once payment is authorized, cart becomes immutable
* after fiscal acceptance, do not edit; only void/return/correct through explicit operations
* every transition writes an audit event

## Fiscal job state machine

```text
QUEUED
  -> DISPATCHING
  -> ACKNOWLEDGED
  -> COMPLETED
```

Failure path:

```text
FAILED_RETRYABLE
FAILED_MANUAL
```

---

# 5. Canonical fiscal payload

Keep one internal receipt representation regardless of vendor.

```json
{
  "document_type": "commercial_receipt",
  "sale_id": "uuid",
  "business_date": "2026-03-10",
  "store": {
    "id": "uuid",
    "code": "ROMA01",
    "name": "Store Roma",
    "vat_number": "..."
  },
  "terminal": {
    "id": "uuid",
    "code": "POS01",
    "device_identifier": "..."
  },
  "operator": {
    "id": "uuid",
    "display_name": "Mario"
  },
  "lines": [
    {
      "line_no": 1,
      "sku": "SKU-001",
      "description": "Prodotto A",
      "qty": 1,
      "unit_price_gross": 10.00,
      "discount_gross": 0.00,
      "line_total_gross": 10.00,
      "vat_code": "22"
    }
  ],
  "totals": {
    "gross": 10.00,
    "net": 8.20,
    "vat": 1.80
  },
  "vat_breakdown": [
    {
      "vat_code": "22",
      "rate": 22.00,
      "taxable": 8.20,
      "tax": 1.80,
      "gross": 10.00
    }
  ],
  "payments": [
    {
      "type": "CARD",
      "amount": 10.00,
      "authorization_ref": "..."
    }
  ],
  "receipt_options": {
    "print_customer_copy": true,
    "email": null,
    "sms": null
  },
  "idempotency_key": "sale_id + version + adapter"
}
```

All adapters must accept this model and translate it to provider-specific formats.

---

# 6. REST API draft

## Sales

### Create sale

```http
POST /api/v1/sales
```

Request:

```json
{
  "store_id": "uuid",
  "terminal_id": "uuid",
  "operator_id": "uuid"
}
```

### Add line

```http
POST /api/v1/sales/{sale_id}/lines
```

```json
{
  "sku": "SKU-001",
  "quantity": 2,
  "override_price_gross": null
}
```

### Update line quantity

```http
PATCH /api/v1/sales/{sale_id}/lines/{line_id}
```

### Remove line

```http
DELETE /api/v1/sales/{sale_id}/lines/{line_id}
```

### Apply discount

```http
POST /api/v1/sales/{sale_id}/discounts
```

```json
{
  "type": "PERCENT",
  "value": 10,
  "reason": "PROMO"
}
```

### Finalize cart

```http
POST /api/v1/sales/{sale_id}/finalize
```

This locks the amounts and transitions to `PAYMENT_PENDING`.

## Payments

### Start payment

```http
POST /api/v1/sales/{sale_id}/payments
```

```json
{
  "payment_type": "CARD",
  "amount": 45.90,
  "provider": "nexi"
}
```

### Confirm cash payment

```http
POST /api/v1/sales/{sale_id}/payments/cash
```

```json
{
  "amount_tendered": 50.00
}
```

### Reverse payment

```http
POST /api/v1/payments/{payment_id}/reverse
```

## Fiscal issuance

### Fiscalize sale

```http
POST /api/v1/sales/{sale_id}/fiscalize
```

Response:

```json
{
  "sale_id": "uuid",
  "status": "FISCAL_PENDING",
  "job_id": "uuid"
}
```

### Get fiscal document

```http
GET /api/v1/sales/{sale_id}/fiscal-document
```

### Reprint / resend receipt

```http
POST /api/v1/fiscal-documents/{document_id}/deliver
```

```json
{
  "channel": "PRINT"
}
```

## Returns and voids

### Void unfiscalized sale

```http
POST /api/v1/sales/{sale_id}/void
```

### Return from original receipt

```http
POST /api/v1/returns
```

```json
{
  "original_fiscal_document_id": "uuid",
  "lines": [
    {
      "original_line_no": 1,
      "quantity": 1
    }
  ],
  "reason": "CUSTOMER_RETURN"
}
```

## Closures

### Open business day / shift

```http
POST /api/v1/closures/open
```

### Close business day / Z report equivalent

```http
POST /api/v1/closures/close
```

```json
{
  "store_id": "uuid",
  "terminal_id": "uuid",
  "business_date": "2026-03-10"
}
```

---

# 7. Adapter interface

Example Python interface:

```python
from dataclasses import dataclass
from typing import Protocol, Any

@dataclass
class FiscalResult:
    success: bool
    provider_document_id: str | None
    receipt_number: str | None
    issue_ts: str | None
    raw_request: dict
    raw_response: dict
    error_code: str | None = None
    error_message: str | None = None


class FiscalAdapter(Protocol):
    name: str

    def issue_receipt(self, payload: dict) -> FiscalResult:
        ...

    def issue_return(self, payload: dict) -> FiscalResult:
        ...

    def close_day(self, payload: dict) -> FiscalResult:
        ...

    def healthcheck(self, terminal_config: dict) -> dict:
        ...
```

Example selector:

```python
def get_fiscal_adapter(adapter_name: str) -> FiscalAdapter:
    if adapter_name == "sandbox_adapter":
        return SandboxAdapter()
    if adapter_name == "rt_printer_adapter":
        return RTPrinterAdapter()
    if adapter_name == "fiscal_api_adapter":
        return FiscalAPIAdapter()
    raise ValueError(f"unsupported adapter: {adapter_name}")
```

---

# 8. Example FastAPI skeleton

## Suggested project layout

```text
app/
  main.py
  api/
    sales.py
    payments.py
    fiscal.py
    closures.py
  domain/
    models.py
    enums.py
    services/
      sales_service.py
      payment_service.py
      fiscal_service.py
  adapters/
    fiscal/
      base.py
      sandbox.py
      rt_printer.py
      fiscal_api.py
    payments/
      nexi.py
      stripe_terminal.py
  repositories/
    sales_repo.py
    fiscal_repo.py
    payments_repo.py
  workers/
    fiscal_worker.py
  db/
    session.py
    migrations/
```

## `main.py`

```python
from fastapi import FastAPI
from app.api.sales import router as sales_router
from app.api.payments import router as payments_router
from app.api.fiscal import router as fiscal_router
from app.api.closures import router as closures_router

app = FastAPI(title="Italy POS Backend")
app.include_router(sales_router, prefix="/api/v1")
app.include_router(payments_router, prefix="/api/v1")
app.include_router(fiscal_router, prefix="/api/v1")
app.include_router(closures_router, prefix="/api/v1")
```

## `api/fiscal.py`

```python
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel
from app.domain.services.fiscal_service import FiscalService

router = APIRouter()
service = FiscalService()

class FiscalizeResponse(BaseModel):
    sale_id: str
    status: str
    job_id: str

@router.post("/sales/{sale_id}/fiscalize", response_model=FiscalizeResponse)
def fiscalize_sale(sale_id: str):
    result = service.enqueue_fiscalization(sale_id)
    if not result:
        raise HTTPException(status_code=400, detail="sale cannot be fiscalized")
    return FiscalizeResponse(**result)
```

## `domain/services/fiscal_service.py`

```python
import uuid

class FiscalService:
    def enqueue_fiscalization(self, sale_id: str) -> dict | None:
        # 1. load sale
        # 2. assert status == PAYMENT_AUTHORIZED
        # 3. build canonical payload
        # 4. persist fiscal_document draft
        # 5. enqueue fiscal_job
        # 6. update sale status -> FISCAL_PENDING
        return {
            "sale_id": sale_id,
            "status": "FISCAL_PENDING",
            "job_id": str(uuid.uuid4())
        }
```

## `workers/fiscal_worker.py`

```python
from app.adapters.fiscal.factory import get_fiscal_adapter

class FiscalWorker:
    def process_job(self, job: dict) -> None:
        adapter = get_fiscal_adapter(job["adapter_name"])
        payload = self._load_payload(job)
        result = adapter.issue_receipt(payload)

        if result.success:
            self._mark_completed(job, result)
        else:
            self._mark_failed(job, result)
```

---

# 9. Store Agent design

Use this only for local hardware scenarios.

## Responsibilities

* receive jobs from backend
* keep persistent local queue
* talk to RT printer or local print device
* send acknowledgements and final result back
* expose health endpoint

## Suggested local endpoints

```http
GET  /health
POST /jobs/receipt
POST /jobs/closure
POST /jobs/return
```

## Durable queue rules

* every job has `job_id`
* every job has `idempotency_key`
* on restart, replay only jobs not marked final
* printer acknowledgements must be persisted before telling backend success

## Agent config example

```json
{
  "store_code": "ROMA01",
  "terminal_code": "POS01",
  "backend_base_url": "https://pos.example.com",
  "fiscal_adapter": "rt_printer_adapter",
  "printer_host": "192.168.1.50",
  "printer_port": 9100,
  "api_key": "redacted"
}
```

---

# 10. Idempotency and failure handling

This part matters more than the happy path.

## Rules

* every fiscal issuance call must use a deterministic `idempotency_key`
* every payment record must store the provider transaction id
* every fiscal request must store a `payload_hash`
* retries must not generate duplicate receipts

## Typical failure cases

### Payment succeeded, fiscalization failed

Keep sale in `FISCAL_ERROR_RETRYABLE` or `FISCAL_ERROR_MANUAL`.
Do not let cashier silently restart a brand new sale.
Provide an admin recovery screen.

### Fiscal call timed out, unknown result

Treat as indeterminate.
First query status from adapter/provider if supported.
Do not blindly resubmit.

### Printer offline

Queue locally if policy allows, otherwise block checkout and raise an operator-visible error.

### Duplicate job dispatch

Ignore if a fiscal document already exists with the same idempotency key and final success state.

---

# 11. Security and audit

## Minimum controls

* per-operator authentication with PIN or SSO
* role-based permissions
* immutable audit events for sale mutations and fiscal operations
* encrypted secrets for payment/fiscal providers
* signed store-agent authentication tokens
* no raw PAN card storage

## Audit events to keep

* sale_created
* line_added
* line_removed
* discount_applied
* cart_finalized
* payment_started
* payment_authorized
* payment_failed
* fiscal_job_queued
* fiscal_submit_started
* fiscal_submit_completed
* fiscal_submit_failed
* receipt_reprinted
* return_created
* closure_started
* closure_completed

---

# 12. Reporting views

Create materialized or standard SQL views for:

* daily sales totals by store
* VAT summary by business date
* cash/card split
* failed fiscal jobs
* payment/fiscal mismatch report
* returns by original receipt
* operator performance

Example VAT summary view:

```sql
create view v_vat_summary as
select
  s.business_date,
  s.store_id,
  sl.vat_code,
  sum(sl.line_total_net) as taxable_total,
  sum(sl.line_vat_amount) as vat_total,
  sum(sl.line_total_gross) as gross_total
from sales s
join sale_lines sl on sl.sale_id = s.id
where s.status in ('FISCAL_ACCEPTED', 'RECEIPT_DELIVERED', 'RETURNED')
group by s.business_date, s.store_id, sl.vat_code;
```

---

# 13. Testing strategy

## Unit tests

* VAT calculations
* rounding rules
* discount allocation across lines
* idempotency key generation
* state transition guards

## Integration tests

* create sale -> payment -> fiscalize -> receipt success
* partial return from original receipt
* duplicate fiscalize request
* timeout from adapter with later reconciliation
* closure operation

## Sandbox adapter

Build a sandbox adapter first.
It should:

* accept the canonical payload
* return deterministic fake receipt numbers
* simulate retryable and non-retryable errors
* support timeout simulation

This lets you harden the workflow before touching a real RT/API vendor.

---

# 14. MVP scope

Build this first:

* single-store support
* product catalog
* barcode sales
* cash and card payments
* one fiscal adapter
* printed customer receipt
* daily closure
* return by original receipt
* admin page for failed fiscal jobs

Do not build in MVP:

* loyalty
* omnichannel orders
* advanced promotions engine
* gift cards
* invoicing edge cases
* multi-warehouse stock reservation

---

# 15. Implementation order

## Phase 1

* domain model
* sales/cart APIs
* VAT engine
* POS UI basic flow

## Phase 2

* payment adapter abstraction
* cash flow and one card provider
* audit events

## Phase 3

* sandbox fiscal adapter
* fiscal state machine
* receipt rendering
* admin recovery page

## Phase 4

* real fiscal adapter integration
* closure flows
* reconciliation reports

## Phase 5

* returns/refunds
* observability
* hardened store agent if local hardware is used

---

# 16. Straight recommendation

If the goal is real deployment with full software control:

* own the POS frontend
* own the backend and data model
* own the reporting and reconciliation
* isolate fiscal issuance behind a strict adapter boundary
* build the sandbox adapter first
* integrate one real fiscal path second

The two worst design mistakes are:

1. mixing fiscal logic directly into the cart/payment code
2. treating receipt issuance as a print action instead of a stateful regulated transaction

---

# 17. Next deliverables

Useful follow-ups after this blueprint:

1. OpenAPI spec for the REST endpoints
2. Full PostgreSQL migration set
3. FastAPI starter repo
4. Store Agent starter repo in Rust or Node.js
5. React or Flutter POS client skeleton
6. Admin dashboard for failed fiscal jobs and closures

