# Flight Search Aggregator

This project was built as part of the Asfar Group backend assessment. The idea is simple: instead of searching one flight supplier at a time, this API calls two suppliers at once, combines the results, and gives you everything in one clean response.

Think of it like a mini Skyscanner — but built from scratch in CodeIgniter 4.

**Live:** https://flight-aggregator.free.nf  
**GitHub:** https://github.com/nooralbonne/flight-aggregator

---

## Setup

### What you need
- PHP 8.1 or higher
- Composer

### Steps

```bash
# 1. clone the project
git clone https://github.com/your-username/flight-aggregator.git
cd flight-aggregator

# 2. install dependencies
composer install

# 3. copy the env file
cp env .env

# 4. start the server
php spark serve
```

Now open http://localhost:8080 and you'll see the search page.

> If you're on Windows with WAMP, skip `php spark serve` and just open
> http://localhost/flight-aggregator/public

---

## Environment config

Open the `.env` file and update these:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

# these are the two mock suppliers (they live inside the same app)
SUPPLIER_A_BASE_URL = http://localhost:8080/mock/supplier-a
SUPPLIER_B_BASE_URL = http://localhost:8080/mock/supplier-b

# how many seconds to wait for a supplier before giving up
SUPPLIER_TIMEOUT = 5

# leave these false normally — set to true to test what happens when a supplier fails
SUPPLIER_A_SIMULATE_SLOW = false
SUPPLIER_B_SIMULATE_FAIL = false

# rate limiting: max 30 requests per minute per IP
RATE_LIMIT_REQUESTS = 30
RATE_LIMIT_WINDOW = 60
```

---

## Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/` | Search UI (the frontend page) |
| GET | `/health` | Check if the service is running |
| GET | `/api/flights/search` | The main search API |
| GET | `/mock/supplier-a` | Mock Supplier A |
| GET | `/mock/supplier-b` | Mock Supplier B |

---

## Search API

**Required:**

| Param | Example |
|-------|---------|
| `origin` | `DXB` |
| `destination` | `LHR` |
| `departure_date` | `2026-09-15` |

**Optional filters:**

| Param | Example | What it does |
|-------|---------|-------------|
| `passengers` | `2` | number of passengers, default is 1 |
| `cabin_class` | `economy` | economy / business / first / premium_economy |
| `max_price` | `600` | hide anything above this price (USD) |
| `min_price` | `200` | hide anything below this price |
| `airlines` | `EK,QR` | show only these airlines |
| `max_stops` | `0` | 0 = direct flights only, 1 = max one stop |
| `refundable_only` | `true` | show only refundable tickets |
| `max_duration_minutes` | `480` | hide flights longer than this |
| `sort_by` | `price` | price / duration / departure / arrival / stops / airline |
| `sort_order` | `asc` | asc or desc |

---

## Example requests

Basic search:
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2026-09-15"
```

Direct flights only, sorted by price:
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2026-09-15&max_stops=0&sort_by=price"
```

Business class under $1200:
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2026-09-15&cabin_class=business&max_price=1200"
```

Refundable tickets sorted by duration:
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2026-09-15&refundable_only=true&sort_by=duration"
```

Health check:
```bash
curl "https://flight-aggregator.free.nf/health"
```

---

## What the response looks like

When both suppliers work fine (HTTP 200):
```json
{
  "status": "success",
  "meta": {
    "total_results": 9,
    "cache_hit": false,
    "total_duration_ms": 83,
    "suppliers": {
      "supplier_a": { "status": "ok", "count": 4, "duration_ms": 40 },
      "supplier_b": { "status": "ok", "count": 5, "duration_ms": 38 }
    }
  },
  "results": [
    {
      "id": "SA-SA001",
      "supplier": "supplier_a",
      "airline": "Emirates",
      "airline_code": "EK",
      "flight_number": "EK512",
      "origin": "DXB",
      "destination": "LHR",
      "departure_at": "2026-09-15T08:00:00Z",
      "arrival_at": "2026-09-15T14:00:00Z",
      "duration_minutes": 360,
      "stops": 0,
      "price": 430.50,
      "currency": "USD",
      "cabin_class": "economy",
      "seats_available": 8,
      "refundable": true,
      "baggage": { "cabin": "7kg", "checked": "23kg" }
    }
  ]
}
```

If one supplier fails, you get HTTP 206 and `"status": "partial"` — results still come from the working one.

If both fail, you get HTTP 503 and `"status": "error"`.

---

## How to simulate supplier failure

There are three ways to test this.

**Option 1 — query param (just for one request):**
```bash
# Supplier A will respond slowly (4 second delay)
curl "https://flight-aggregator.free.nf/mock/supplier-a?simulate_slow=1"

# Supplier B will return a 503 error
curl "https://flight-aggregator.free.nf/mock/supplier-b?simulate_fail=1"
```

After that, run a normal search and you'll see `"status": "partial"` in the response with the error logged under `meta.suppliers`.

**Option 2 — env file (stays on until you change it back):**
```ini
SUPPLIER_A_SIMULATE_SLOW = true
SUPPLIER_B_SIMULATE_FAIL = true
```

**Option 3 — edit the config directly:**

In `app/Config/Suppliers.php`:
```php
public array $supplierA = [
    'simulate_slow' => true,
    'slow_delay'    => 4,
];

public array $supplierB = [
    'simulate_fail' => true,
];
```

---

## A note on the two suppliers

The whole point of this project is handling suppliers that return data in completely different formats. Here's what I mean:

Supplier A sends:
```json
{ "carrier": "Emirates", "from": "DXB", "fare": { "amount": 450 }, "num_stops": 0 }
```

Supplier B sends the exact same info but like this:
```json
{ "airline_name": "Emirates", "departure_airport": "DXB", "total_price": { "value": 450 }, "connection_count": 0 }
```

The `SupplierAClient` and `SupplierBClient` classes each handle their own format and map it to a single `NormalizedFlight` object — so by the time results reach the service layer, everything looks the same regardless of where it came from.

---

## Caching

Results are cached for 120 seconds. The cache key is built from the search parameters, so the same search within 2 minutes returns instantly without hitting the suppliers again. You can tell it's cached when the response has `"cache_hit": true`.

---

## Rate limiting

The search endpoint allows 30 requests per minute per IP. If you go over that you get HTTP 429. The response includes a `Retry-After` header so you know when to try again.

---

*Noor Albonne — Asfar Group Backend Assessment 2026*
