# ✈️ Flight Search Aggregator — Asfar Group Backend Assessment

A **CodeIgniter 4** backend service that aggregates flight search results from two mock suppliers, normalizes their different response formats into a unified schema, and returns merged, filtered, and sorted results.

**Live Demo:** https://flight-aggregator.free.nf  
**GitHub:** https://github.com/your-username/flight-aggregator

---

## 📐 How It Works

```
User Request
     │
     ▼
┌─────────────────────────────────┐
│  Rate Limiter (30 req/60s)      │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  FlightController               │
│  • Validates input              │
│  • Calls the service            │
└──────────────┬──────────────────┘
               │
               ▼
┌─────────────────────────────────┐
│  FlightAggregatorService        │
│  • Checks cache (120s TTL)      │
│  • Calls both suppliers         │
│  • Merges + filters + sorts     │
│  • Returns partial if one fails │
└────────┬────────────────────────┘
         │               │
         ▼               ▼
  Supplier A         Supplier B
  (mock endpoint)    (mock endpoint)
  Different format   Different format
         │               │
         └───────┬────────┘
                 ▼
         NormalizedFlight DTO
         (unified schema)
```

---

## 🚀 Setup Instructions

### Requirements
- PHP 8.1+
- Composer
- Extensions: `curl`, `mbstring`, `xml`, `intl`

### Local Installation (WAMP / XAMPP)

```bash
# 1. Clone the repository
git clone https://github.com/your-username/flight-aggregator.git
cd flight-aggregator

# 2. Install dependencies
composer install

# 3. Copy environment file
copy env .env        # Windows
cp env .env          # Linux/Mac

# 4. Create writable directories
mkdir -p writable/cache writable/logs writable/session

# 5. Set permissions (Linux/Mac only)
chmod -R 777 writable/

# 6. Start server
php spark serve
```

**Then open:** http://localhost:8080

---

## ⚙️ Environment Configuration

Edit the `env` file (rename to `.env` for local):

```ini
# ─── App ──────────────────────────────────────────────────────
CI_ENVIRONMENT = development
app.baseURL = 'http://localhost:8080/'

# ─── Supplier URLs ────────────────────────────────────────────
# Point to the mock endpoints (same app)
SUPPLIER_A_BASE_URL = http://localhost:8080/mock/supplier-a
SUPPLIER_B_BASE_URL = http://localhost:8080/mock/supplier-b

# Timeout in seconds before giving up on a supplier
SUPPLIER_TIMEOUT = 5

# ─── Simulate failures (for testing) ─────────────────────────
SUPPLIER_A_SIMULATE_SLOW = false   # Makes Supplier A wait 4s
SUPPLIER_B_SIMULATE_FAIL = false   # Makes Supplier B return 503

# ─── Rate Limiting ────────────────────────────────────────────
RATE_LIMIT_REQUESTS = 30           # Max requests per window
RATE_LIMIT_WINDOW = 60             # Window size in seconds
```

---

## 📡 Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/` | Frontend UI — Search page |
| `GET` | `/health` | Service health check |
| `GET` | `/api/flights/search` | **Main search API** |
| `GET` | `/mock/supplier-a` | Mock Supplier A |
| `GET` | `/mock/supplier-b` | Mock Supplier B |

---

## 🔍 Search API — `/api/flights/search`

### Required Parameters

| Parameter | Type | Example |
|-----------|------|---------|
| `origin` | string (IATA) | `DXB` |
| `destination` | string (IATA) | `LHR` |
| `departure_date` | YYYY-MM-DD | `2025-09-15` |

### Optional Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `passengers` | integer | Number of passengers (default: 1) |
| `cabin_class` | string | `economy` / `business` / `first` / `premium_economy` |
| `max_price` | float | Max price filter in USD |
| `min_price` | float | Min price filter in USD |
| `airlines` | string | Comma-separated IATA codes e.g. `EK,QR` |
| `max_stops` | integer | `0` = direct only, `1`, `2` |
| `refundable_only` | string | `true` or `false` |
| `max_duration_minutes` | integer | Max flight duration |
| `sort_by` | string | `price` / `duration` / `departure` / `stops` / `airline` |
| `sort_order` | string | `asc` or `desc` (default: `asc`) |

---

## 📋 Example Requests

### Basic Search
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2025-09-15"
```

### Economy + Max $600 + Direct Only
```bash
curl "https://flight-aggregator.free.nf/api/flights/search\
?origin=DXB\
&destination=LHR\
&departure_date=2025-09-15\
&cabin_class=economy\
&max_price=600\
&max_stops=0\
&sort_by=price\
&sort_order=asc"
```

### Filter by Specific Airlines
```bash
curl "https://flight-aggregator.free.nf/api/flights/search\
?origin=DXB\
&destination=LHR\
&departure_date=2025-09-15\
&airlines=EK,QR"
```

### Sort by Duration (Fastest First)
```bash
curl "https://flight-aggregator.free.nf/api/flights/search\
?origin=DXB\
&destination=LHR\
&departure_date=2025-09-15\
&sort_by=duration\
&sort_order=asc"
```

### Refundable Tickets Only
```bash
curl "https://flight-aggregator.free.nf/api/flights/search\
?origin=DXB\
&destination=LHR\
&departure_date=2025-09-15\
&refundable_only=true"
```

### Health Check
```bash
curl "https://flight-aggregator.free.nf/health"
```

---

## 📦 Response Format

### Success (HTTP 200)
```json
{
    "status": "success",
    "meta": {
        "total_results": 9,
        "filtered_from": 9,
        "cache_hit": false,
        "cached_until": "2025-09-15T10:02:00+00:00",
        "total_duration_ms": 87,
        "suppliers": {
            "supplier_a": { "status": "ok", "count": 4, "duration_ms": 42 },
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
            "departure_at": "2025-09-15T08:00:00Z",
            "arrival_at": "2025-09-15T14:00:00Z",
            "duration_minutes": 360,
            "stops": 0,
            "price": 430.50,
            "currency": "USD",
            "cabin_class": "economy",
            "seats_available": 8,
            "refundable": true,
            "baggage": { "cabin": "7kg", "checked": "23kg" },
            "layovers": null,
            "deep_link": "https://supplier-a.mock/book/SA001"
        }
    ]
}
```

### Partial Response (HTTP 206) — One supplier failed
```json
{
    "status": "partial",
    "meta": {
        "suppliers": {
            "supplier_a": { "status": "ok", "count": 4, "duration_ms": 42 },
            "supplier_b": { "status": "error", "error": "Supplier B returned HTTP 503", "duration_ms": 10 }
        }
    },
    "results": [ ... ]
}
```

### Both Failed (HTTP 503)
```json
{
    "status": "error",
    "meta": {
        "suppliers": {
            "supplier_a": { "status": "error", "error": "Supplier A timed out after 5s" },
            "supplier_b": { "status": "error", "error": "Supplier B returned HTTP 503" }
        }
    },
    "results": []
}
```

### Validation Error (HTTP 422)
```json
{
    "status": "error",
    "message": "Validation failed",
    "errors": {
        "origin": "Origin airport IATA code is required.",
        "departure_date": "Departure date must be in YYYY-MM-DD format."
    }
}
```

### Rate Limit Exceeded (HTTP 429)
```json
{
    "status": "error",
    "message": "Too many requests. Please try again later.",
    "retry_after_seconds": 45
}
```

---

## 🧪 How to Simulate Supplier Failure / Timeout

### Method 1 — Query Parameter (one request only)

```bash
# Simulate Supplier A slow response (4 second delay)
curl "https://flight-aggregator.free.nf/mock/supplier-a?simulate_slow=1"

# Simulate Supplier B hard failure (returns HTTP 503)
curl "https://flight-aggregator.free.nf/mock/supplier-b?simulate_fail=1"
```

### Method 2 — Environment Variable (permanent until changed)

Edit the `env` file:
```ini
# Supplier A will always be slow (triggers timeout)
SUPPLIER_A_SIMULATE_SLOW = true

# Supplier B will always fail
SUPPLIER_B_SIMULATE_FAIL = true
```

Then call the search endpoint — you will get **HTTP 206 Partial Content**
with results from only the working supplier, and the failed supplier
will appear in `meta.suppliers` with `"status": "error"`.

### Method 3 — Edit Config Directly

In `app/Config/Suppliers.php`:
```php
public array $supplierA = [
    'simulate_slow' => true,  // ← enable
    'slow_delay'    => 4,     // ← seconds to delay
];

public array $supplierB = [
    'simulate_fail' => true,  // ← enable
];
```

### Expected Partial Response When Supplier B Fails:
```bash
curl "https://flight-aggregator.free.nf/api/flights/search?origin=DXB&destination=LHR&departure_date=2025-09-15"

# Response: HTTP 206
{
  "status": "partial",
  "meta": {
    "suppliers": {
      "supplier_a": { "status": "ok", "count": 4 },
      "supplier_b": { "status": "error", "error": "Supplier B returned HTTP 503" }
    }
  }
}
```

---

## 🏗️ Project Structure

```
flight-aggregator/
├── app/
│   ├── Config/
│   │   ├── App.php              # Base URL, charset, etc.
│   │   ├── Cache.php            # Cache handler (file-based)
│   │   ├── Filters.php          # Register rate limiter
│   │   ├── Logger.php           # Log level config
│   │   ├── Paths.php            # Directory paths
│   │   ├── Routes.php           # URL → Controller mapping
│   │   └── Suppliers.php        # Supplier A & B settings
│   ├── Controllers/
│   │   ├── FlightController.php        # Search API + UI
│   │   └── MockSupplierController.php  # Fake supplier endpoints
│   ├── Filters/
│   │   ├── RateLimitFilter.php         # 30 req/60s per IP
│   │   └── JsonResponseFilter.php      # Force JSON headers
│   ├── Libraries/
│   │   ├── NormalizedFlight.php        # Unified flight DTO
│   │   ├── SupplierAClient.php         # Supplier A HTTP client
│   │   └── SupplierBClient.php         # Supplier B HTTP client
│   ├── Services/
│   │   └── FlightAggregatorService.php # Core business logic
│   └── Views/
│       └── search.php                  # Frontend UI
├── public/
│   ├── index.php                # Entry point
│   └── .htaccess                # URL rewriting
├── writable/
│   ├── cache/                   # Cached search results
│   └── logs/                    # Application logs
├── env                          # Environment config
└── README.md
```

---

## 🔐 Rate Limiting

- **30 requests** per **60 seconds** per IP address
- Storage: file-based cache (no Redis needed)
- Response headers on every API call:
  - `X-RateLimit-Limit: 30`
  - `X-RateLimit-Remaining: 27`
  - `X-RateLimit-Reset: 1726394520`
- When exceeded: **HTTP 429** with `Retry-After` header

---

## 💾 Caching

- **TTL:** 120 seconds
- **Handler:** File-based (works everywhere, no Redis)
- **Key:** MD5 hash of sorted search parameters
- Same search within 120s returns cached result instantly
- `meta.cache_hit: true` indicates a cached response

---

## 📊 Logging

All supplier activity is logged to `writable/logs/log-YYYY-MM-DD.log`:

```
INFO  [SupplierA] Sending request {"origin":"DXB","destination":"LHR"}
INFO  [SupplierA] Response received {"status":200,"duration_ms":42}
INFO  [SupplierA] Normalized 4 flights
INFO  [SupplierB] Request timed out after 5s        ← timeout example
WARN  [Aggregator] Supplier B failed: timeout
INFO  [Aggregator] Cached 120s
INFO  [Aggregator] Done {"total":4,"status":"partial"}
```

---

## 🔄 Supplier Format Differences

This is the core of the normalization layer:

| Field | Supplier A | Supplier B | Normalized |
|-------|-----------|-----------|-----------|
| ID | `flight_id` | `ref` | `id` |
| Airline | `carrier` | `airline_name` | `airline` |
| Code | `carrier_code` | `iata` | `airline_code` |
| Origin | `from` | `departure_airport` | `origin` |
| Destination | `to` | `arrival_airport` | `destination` |
| Departure | `departs` (UTC) | `departure_time` (+03:00) | `departure_at` |
| Duration | `flight_duration` | `total_minutes` | `duration_minutes` |
| Stops | `num_stops` | `connection_count` | `stops` |
| Price | `fare.amount` | `total_price.value` | `price` |
| Cabin | `class` (text) | `travel_class` (Y/C/F) | `cabin_class` |
| Seats | `available_seats` | `remaining_seats` | `seats_available` |
| Refund | `is_refundable` | `cancellable` | `refundable` |

---

## 👩‍💻 Author

**Noor Albonne**  
Backend Developer Assessment — Asfar Group  
CodeIgniter 4 · Flight Search Aggregator · 2026
