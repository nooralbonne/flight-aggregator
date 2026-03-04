<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asfar — Flight Search</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --ink:     #0a0a0f;
    --paper:   #f5f3ee;
    --cream:   #ede9e0;
    --gold:    #c9a84c;
    --gold-lt: #e8d49a;
    --rust:    #c0442a;
    --sky:     #1a3a5c;
    --sky-lt:  #2e5f8a;
    --mist:    #8a8a96;
    --line:    #d8d4cb;
    --card:    #ffffff;
    --radius:  6px;
    --shadow:  0 2px 20px rgba(10,10,15,.07);
  }

  html { font-size: 16px; }
  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--paper);
    color: var(--ink);
    min-height: 100vh;
    line-height: 1.5;
  }

  /* ── HEADER ── */
  header {
    background: var(--sky);
    padding: 0 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 62px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 rgba(255,255,255,.08);
  }
  .logo {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.4rem;
    color: #fff;
    letter-spacing: -.02em;
    display: flex;
    align-items: center;
    gap: .5rem;
  }
  .logo span { color: var(--gold); }
  .logo-badge {
    font-family: 'DM Sans', sans-serif;
    font-weight: 400;
    font-size: .65rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: rgba(255,255,255,.45);
    border: 1px solid rgba(255,255,255,.15);
    padding: .15rem .5rem;
    border-radius: 2px;
  }
  .header-links { display: flex; gap: 1.5rem; align-items: center; }
  .header-links a {
    color: rgba(255,255,255,.6);
    text-decoration: none;
    font-size: .82rem;
    transition: color .2s;
  }
  .header-links a:hover { color: #fff; }
  .api-badge {
    background: rgba(201,168,76,.15);
    border: 1px solid rgba(201,168,76,.35);
    color: var(--gold-lt);
    font-size: .72rem;
    font-weight: 500;
    padding: .2rem .6rem;
    border-radius: 2px;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  /* ── HERO SEARCH ── */
  .hero {
    background: var(--sky);
    padding: 3rem 2rem 0;
  }
  .hero-inner { max-width: 900px; margin: 0 auto; }
  .hero h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 700;
    color: #fff;
    letter-spacing: -.03em;
    line-height: 1.15;
    margin-bottom: .5rem;
  }
  .hero h1 em { color: var(--gold); font-style: normal; }
  .hero-sub {
    color: rgba(255,255,255,.5);
    font-size: .9rem;
    margin-bottom: 2rem;
  }

  /* ── SEARCH CARD ── */
  .search-card {
    background: var(--card);
    border-radius: var(--radius) var(--radius) 0 0;
    padding: 1.75rem 2rem;
    box-shadow: 0 -1px 0 rgba(255,255,255,.06);
  }
  .search-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1.2fr auto auto;
    gap: .75rem;
    align-items: end;
  }
  @media (max-width: 780px) {
    .search-row { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 480px) {
    .search-row { grid-template-columns: 1fr; }
  }

  .field { display: flex; flex-direction: column; gap: .35rem; }
  .field label {
    font-size: .7rem;
    font-weight: 500;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--mist);
  }
  .field input, .field select {
    height: 44px;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
    padding: 0 .85rem;
    font-family: 'DM Sans', sans-serif;
    font-size: .9rem;
    color: var(--ink);
    background: #fff;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
  }
  .field input:focus, .field select:focus {
    border-color: var(--sky);
    box-shadow: 0 0 0 3px rgba(26,58,92,.1);
  }
  .field input::placeholder { color: #bbb; }

  .btn-search {
    height: 44px;
    padding: 0 1.8rem;
    background: var(--sky);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-family: 'Syne', sans-serif;
    font-weight: 600;
    font-size: .88rem;
    letter-spacing: .04em;
    cursor: pointer;
    transition: background .18s, transform .1s;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: .5rem;
  }
  .btn-search:hover { background: var(--sky-lt); }
  .btn-search:active { transform: scale(.98); }
  .btn-search svg { flex-shrink: 0; }

  /* ── FILTERS ROW ── */
  .filters-row {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--line);
    align-items: flex-end;
  }
  .filter-chip {
    display: flex;
    flex-direction: column;
    gap: .25rem;
  }
  .filter-chip label {
    font-size: .65rem;
    font-weight: 500;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--mist);
  }
  .filter-chip select, .filter-chip input {
    height: 34px;
    padding: 0 .65rem;
    border: 1.5px solid var(--line);
    border-radius: var(--radius);
    font-family: 'DM Sans', sans-serif;
    font-size: .82rem;
    color: var(--ink);
    background: var(--paper);
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    min-width: 110px;
    transition: border-color .18s;
  }
  .filter-chip select:focus, .filter-chip input:focus {
    border-color: var(--sky);
  }

  /* ── MAIN CONTENT ── */
  main { max-width: 900px; margin: 0 auto; padding: 2rem; }

  /* ── STATUS BAR ── */
  .status-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    flex-wrap: wrap;
    gap: .5rem;
  }
  .status-count {
    font-family: 'Syne', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: var(--ink);
  }
  .status-count span { color: var(--mist); font-weight: 400; font-family: 'DM Sans', sans-serif; font-size: .85rem; }
  .supplier-badges { display: flex; gap: .5rem; flex-wrap: wrap; }
  .sup-badge {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    font-size: .72rem;
    font-weight: 500;
    padding: .2rem .6rem;
    border-radius: 20px;
    letter-spacing: .02em;
  }
  .sup-badge.ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
  .sup-badge.err { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
  .sup-badge.partial { background: #fff8e1; color: #e65100; border: 1px solid #ffe082; }
  .sup-badge::before { content: '●'; font-size: .55rem; }

  /* ── FLIGHT CARD ── */
  .flight-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 1.25rem 1.5rem;
    margin-bottom: .75rem;
    display: grid;
    grid-template-columns: 1fr auto 1fr auto;
    gap: 1rem;
    align-items: center;
    transition: box-shadow .18s, border-color .18s, transform .15s;
    position: relative;
    overflow: hidden;
  }
  .flight-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: var(--sky);
    opacity: 0;
    transition: opacity .2s;
  }
  .flight-card:hover {
    box-shadow: var(--shadow);
    border-color: #c5c0b5;
    transform: translateY(-1px);
  }
  .flight-card:hover::before { opacity: 1; }

  @media (max-width: 600px) {
    .flight-card { grid-template-columns: 1fr; }
  }

  /* Airline */
  .airline-col { display: flex; flex-direction: column; gap: .2rem; }
  .airline-logo {
    width: 36px; height: 36px;
    background: var(--cream);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Syne', sans-serif;
    font-size: .65rem;
    font-weight: 700;
    color: var(--sky);
    border: 1px solid var(--line);
    margin-bottom: .3rem;
    letter-spacing: .05em;
  }
  .airline-name {
    font-size: .85rem;
    font-weight: 500;
    color: var(--ink);
  }
  .flight-num {
    font-size: .72rem;
    color: var(--mist);
    font-weight: 400;
  }
  .supplier-tag {
    font-size: .65rem;
    color: var(--mist);
    border: 1px solid var(--line);
    padding: .05rem .35rem;
    border-radius: 3px;
    display: inline-block;
    margin-top: .2rem;
    letter-spacing: .04em;
  }

  /* Route */
  .route-col {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
  }
  .airport {
    text-align: center;
    min-width: 64px;
  }
  .airport-code {
    font-family: 'Syne', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -.02em;
    line-height: 1;
  }
  .airport-time {
    font-size: .8rem;
    color: var(--mist);
    margin-top: .2rem;
  }
  .route-line {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .2rem;
  }
  .route-line-bar {
    width: 100%;
    height: 1px;
    background: var(--line);
    position: relative;
  }
  .route-line-bar::before,
  .route-line-bar::after {
    content: '';
    position: absolute;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: var(--mist);
    top: -2px;
  }
  .route-line-bar::before { left: 0; }
  .route-line-bar::after { right: 0; }
  .stops-label {
    font-size: .68rem;
    color: var(--mist);
    white-space: nowrap;
  }
  .stops-label.direct { color: #2e7d32; font-weight: 500; }
  .duration-label {
    font-size: .72rem;
    color: var(--mist);
  }

  /* Tags */
  .tags-col {
    display: flex;
    flex-direction: column;
    gap: .3rem;
    align-items: flex-start;
  }
  .tag {
    font-size: .68rem;
    font-weight: 500;
    padding: .18rem .5rem;
    border-radius: 3px;
    letter-spacing: .02em;
  }
  .tag-cabin { background: #e3f2fd; color: #1565c0; }
  .tag-refund { background: #e8f5e9; color: #2e7d32; }
  .tag-bag { background: var(--cream); color: var(--mist); }
  .seats-left {
    font-size: .7rem;
    color: var(--rust);
    font-weight: 500;
  }
  .seats-ok { color: var(--mist); }

  /* Price */
  .price-col {
    text-align: right;
    min-width: 110px;
  }
  .price-amount {
    font-family: 'Syne', sans-serif;
    font-size: 1.55rem;
    font-weight: 700;
    color: var(--ink);
    letter-spacing: -.03em;
    line-height: 1;
  }
  .price-currency {
    font-size: .72rem;
    color: var(--mist);
    margin-bottom: .3rem;
  }
  .btn-select {
    display: inline-block;
    margin-top: .5rem;
    padding: .45rem 1rem;
    background: var(--sky);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-family: 'Syne', sans-serif;
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .04em;
    cursor: pointer;
    text-decoration: none;
    transition: background .18s;
  }
  .btn-select:hover { background: var(--sky-lt); }

  /* ── META INFO ── */
  .meta-panel {
    background: var(--cream);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    align-items: center;
  }
  .meta-item { display: flex; flex-direction: column; gap: .1rem; }
  .meta-key {
    font-size: .65rem;
    font-weight: 500;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--mist);
  }
  .meta-val {
    font-size: .85rem;
    font-weight: 500;
    color: var(--ink);
  }
  .cache-hit { color: var(--gold); }

  /* ── EMPTY / ERROR STATES ── */
  .state-box {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: var(--radius);
    padding: 3rem;
    text-align: center;
  }
  .state-icon { font-size: 2.5rem; margin-bottom: 1rem; }
  .state-title {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: .5rem;
    color: var(--ink);
  }
  .state-msg { color: var(--mist); font-size: .9rem; }
  .state-box.error .state-title { color: var(--rust); }

  /* ── LOADER ── */
  .loader {
    display: none;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 3rem;
    color: var(--mist);
    font-size: .9rem;
  }
  .loader.active { display: flex; }
  .spinner {
    width: 36px; height: 36px;
    border: 2.5px solid var(--line);
    border-top-color: var(--sky);
    border-radius: 50%;
    animation: spin .7s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  /* ── WELCOME ── */
  #welcome {
    padding: 4rem 0;
    text-align: center;
    color: var(--mist);
  }
  #welcome .big { font-size: 3rem; margin-bottom: .75rem; }
  #welcome p { font-size: .9rem; max-width: 380px; margin: 0 auto; }

  /* ── VALIDATION MSG ── */
  .validation-err {
    background: #fce4ec;
    border: 1px solid #ef9a9a;
    color: #c62828;
    border-radius: var(--radius);
    padding: .75rem 1rem;
    font-size: .85rem;
    margin-bottom: 1rem;
    display: none;
  }
  .validation-err.show { display: block; }

  /* ── FOOTER ── */
  footer {
    text-align: center;
    padding: 2rem;
    color: var(--mist);
    font-size: .78rem;
    border-top: 1px solid var(--line);
    margin-top: 3rem;
  }

  /* Animations */
  .flight-card { animation: fadeUp .25s ease both; }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }
</style>
</head>
<body>

<!-- HEADER -->
<header>
  <div class="logo">
    ✈ Asfar <span>Flights</span>
    <div class="logo-badge">Aggregator</div>
  </div>
  <div class="header-links">
    <span class="api-badge">REST API</span>
    <a href="/health" target="_blank">Health</a>
    <a href="/" target="_blank">API Docs</a>
  </div>
</header>

<!-- HERO + SEARCH -->
<div class="hero">
  <div class="hero-inner">
    <h1>Find the <em>best</em> flight<br>across all suppliers</h1>
    <p class="hero-sub">Live aggregation from Supplier A & B — normalized, filtered, sorted.</p>

    <div class="search-card">
      <div class="search-row">
        <div class="field">
          <label>From</label>
          <input type="text" id="origin" placeholder="DXB" maxlength="3" value="DXB" style="text-transform:uppercase;font-weight:600;font-size:1rem;letter-spacing:.05em">
        </div>
        <div class="field">
          <label>To</label>
          <input type="text" id="destination" placeholder="LHR" maxlength="3" value="LHR" style="text-transform:uppercase;font-weight:600;font-size:1rem;letter-spacing:.05em">
        </div>
        <div class="field">
          <label>Departure Date</label>
          <input type="date" id="departure_date">
        </div>
        <div class="field">
          <label>Passengers</label>
          <select id="passengers">
            <option value="1">1 Pax</option>
            <option value="2">2 Pax</option>
            <option value="3">3 Pax</option>
            <option value="4">4 Pax</option>
          </select>
        </div>
        <button class="btn-search" id="searchBtn" onclick="doSearch()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          Search
        </button>
      </div>

      <!-- Filters -->
      <div class="filters-row">
        <div class="filter-chip">
          <label>Cabin</label>
          <select id="cabin_class">
            <option value="">Any Class</option>
            <option value="economy">Economy</option>
            <option value="premium_economy">Premium Economy</option>
            <option value="business">Business</option>
            <option value="first">First</option>
          </select>
        </div>
        <div class="filter-chip">
          <label>Max Stops</label>
          <select id="max_stops">
            <option value="">Any</option>
            <option value="0">Direct Only</option>
            <option value="1">Max 1 Stop</option>
            <option value="2">Max 2 Stops</option>
          </select>
        </div>
        <div class="filter-chip">
          <label>Max Price (USD)</label>
          <input type="number" id="max_price" placeholder="No limit" min="0" step="50" style="min-width:110px">
        </div>
        <div class="filter-chip">
          <label>Sort By</label>
          <select id="sort_by">
            <option value="price">Price</option>
            <option value="duration">Duration</option>
            <option value="departure">Departure</option>
            <option value="stops">Stops</option>
            <option value="airline">Airline</option>
          </select>
        </div>
        <div class="filter-chip">
          <label>Order</label>
          <select id="sort_order">
            <option value="asc">↑ Ascending</option>
            <option value="desc">↓ Descending</option>
          </select>
        </div>
        <div class="filter-chip">
          <label>Refundable</label>
          <select id="refundable_only">
            <option value="">All</option>
            <option value="true">Refundable Only</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MAIN -->
<main>
  <div class="validation-err" id="validErr"></div>
  <div class="loader" id="loader"><div class="spinner"></div> Searching all suppliers…</div>

  <div id="results"></div>

  <div id="welcome">
    <div class="big">✈️</div>
    <p>Enter your origin, destination, and departure date to search flights from all suppliers.</p>
  </div>
</main>

<footer>
  Asfar Group — Flight Aggregator API &nbsp;·&nbsp; CodeIgniter 4 &nbsp;·&nbsp; Backend Assessment
</footer>

<script>
// Set default date to tomorrow
const tomorrow = new Date();
tomorrow.setDate(tomorrow.getDate() + 30);
document.getElementById('departure_date').value = tomorrow.toISOString().split('T')[0];
document.getElementById('departure_date').min = new Date().toISOString().split('T')[0];

// Upper-case IATA inputs
['origin','destination'].forEach(id => {
  document.getElementById(id).addEventListener('input', e => {
    e.target.value = e.target.value.toUpperCase().replace(/[^A-Z]/g,'');
  });
});

// Enter key triggers search
document.querySelectorAll('input').forEach(el => {
  el.addEventListener('keydown', e => { if (e.key === 'Enter') doSearch(); });
});

function showError(msg) {
  const el = document.getElementById('validErr');
  el.textContent = msg;
  el.classList.add('show');
}
function hideError() {
  document.getElementById('validErr').classList.remove('show');
}

async function doSearch() {
  hideError();

  const origin      = document.getElementById('origin').value.trim().toUpperCase();
  const destination = document.getElementById('destination').value.trim().toUpperCase();
  const date        = document.getElementById('departure_date').value;

  if (!origin || origin.length < 2) return showError('Please enter a valid origin airport code (e.g. DXB).');
  if (!destination || destination.length < 2) return showError('Please enter a valid destination airport code (e.g. LHR).');
  if (!date) return showError('Please select a departure date.');
  if (origin === destination) return showError('Origin and destination cannot be the same.');

  const params = new URLSearchParams({
    origin, destination, departure_date: date,
    passengers: document.getElementById('passengers').value,
    sort_by: document.getElementById('sort_by').value,
    sort_order: document.getElementById('sort_order').value,
  });

  const cabin    = document.getElementById('cabin_class').value;
  const maxStops = document.getElementById('max_stops').value;
  const maxPrice = document.getElementById('max_price').value;
  const refund   = document.getElementById('refundable_only').value;

  if (cabin)    params.set('cabin_class', cabin);
  if (maxStops !== '') params.set('max_stops', maxStops);
  if (maxPrice) params.set('max_price', maxPrice);
  if (refund)   params.set('refundable_only', refund);

  document.getElementById('welcome').style.display = 'none';
  document.getElementById('results').innerHTML = '';
  document.getElementById('loader').classList.add('active');
  document.getElementById('searchBtn').disabled = true;

  try {
    const res  = await fetch('/api/flights/search?' + params.toString());
    const data = await res.json();
    renderResults(data, res.status);
  } catch (err) {
    renderNetworkError(err);
  } finally {
    document.getElementById('loader').classList.remove('active');
    document.getElementById('searchBtn').disabled = false;
  }
}

function renderResults(data, httpStatus) {
  const container = document.getElementById('results');

  // ── Meta panel ──
  if (data.meta) {
    const m = data.meta;
    const sup = m.suppliers || {};
    const badges = Object.entries(sup).map(([k, v]) => {
      const cls = v.status === 'ok' ? 'ok' : 'err';
      const label = k.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase());
      const info  = v.status === 'ok'
        ? `${v.count} flights · ${v.duration_ms}ms`
        : v.error;
      return `<span class="sup-badge ${cls}" title="${info}">${label}: ${v.status === 'ok' ? v.count + ' flights' : 'failed'}</span>`;
    }).join('');

    const cacheHit = m.cache_hit ? `<span class="cache-hit">⚡ cached</span>` : `<span>fresh</span>`;

    container.innerHTML += `
      <div class="meta-panel">
        <div class="meta-item"><div class="meta-key">Results</div><div class="meta-val">${m.total_results} flights</div></div>
        <div class="meta-item"><div class="meta-key">Duration</div><div class="meta-val">${m.total_duration_ms}ms</div></div>
        <div class="meta-item"><div class="meta-key">Cache</div><div class="meta-val">${cacheHit}</div></div>
        <div class="meta-item"><div class="meta-key">Status</div><div class="meta-val">${data.status}</div></div>
        <div class="supplier-badges">${badges}</div>
      </div>`;
  }

  // ── Status bar ──
  if (data.results && data.results.length > 0) {
    const total = data.results.length;
    container.innerHTML += `
      <div class="status-bar">
        <div class="status-count">${total} flights found <span>— sorted by ${document.getElementById('sort_by').value}</span></div>
      </div>`;
  }

  // ── Flight cards ──
  if (!data.results || data.results.length === 0) {
    const isErr = data.status === 'error';
    container.innerHTML += `
      <div class="state-box ${isErr ? 'error' : ''}">
        <div class="state-icon">${isErr ? '⚠️' : '🔍'}</div>
        <div class="state-title">${isErr ? 'Supplier Error' : 'No Flights Found'}</div>
        <div class="state-msg">${isErr ? (data.meta?.suppliers ? 'Both suppliers failed to respond.' : 'Service unavailable.') : 'Try adjusting your filters or choosing different dates.'}</div>
      </div>`;
    return;
  }

  data.results.forEach((f, i) => {
    const dep   = formatTime(f.departure_at);
    const arr   = formatTime(f.arrival_at);
    const dur   = formatDuration(f.duration_minutes);
    const stops = f.stops === 0 ? '<span class="stops-label direct">Direct</span>' : `<span class="stops-label">${f.stops} stop${f.stops>1?'s':''}</span>`;
    const seats = f.seats_available <= 5
      ? `<div class="seats-left">Only ${f.seats_available} left!</div>`
      : `<div class="seats-left seats-ok">${f.seats_available} seats</div>`;
    const cabin = f.cabin_class.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase());
    const sup   = f.supplier.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase());

    container.innerHTML += `
      <div class="flight-card" style="animation-delay:${i * 0.04}s">
        <div class="airline-col">
          <div class="airline-logo">${f.airline_code}</div>
          <div class="airline-name">${f.airline}</div>
          <div class="flight-num">${f.flight_number}</div>
          <span class="supplier-tag">${sup}</span>
        </div>

        <div class="route-col">
          <div class="airport">
            <div class="airport-code">${f.origin}</div>
            <div class="airport-time">${dep.time}</div>
            <div class="airport-time" style="font-size:.67rem">${dep.date}</div>
          </div>
          <div class="route-line">
            <div class="route-line-bar"></div>
            ${stops}
            <div class="duration-label">${dur}</div>
          </div>
          <div class="airport">
            <div class="airport-code">${f.destination}</div>
            <div class="airport-time">${arr.time}</div>
            <div class="airport-time" style="font-size:.67rem">${arr.date}</div>
          </div>
        </div>

        <div class="tags-col">
          <span class="tag tag-cabin">${cabin}</span>
          ${f.refundable ? '<span class="tag tag-refund">Refundable</span>' : ''}
          <span class="tag tag-bag">✈ ${f.baggage.cabin} cabin · ${f.baggage.checked} checked</span>
          ${seats}
        </div>

        <div class="price-col">
          <div class="price-currency">${f.currency}</div>
          <div class="price-amount">${formatPrice(f.price)}</div>
          <a href="${f.deep_link}" target="_blank" class="btn-select">Select →</a>
        </div>
      </div>`;
  });
}

function renderNetworkError(err) {
  document.getElementById('results').innerHTML = `
    <div class="state-box error">
      <div class="state-icon">🔌</div>
      <div class="state-title">Connection Error</div>
      <div class="state-msg">Could not reach the API. Make sure the server is running.<br><small style="opacity:.6">${err.message}</small></div>
    </div>`;
}

function formatTime(iso) {
  if (!iso) return { time: '--:--', date: '' };
  try {
    const d = new Date(iso);
    return {
      time: d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'UTC' }),
      date: d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', timeZone: 'UTC' }),
    };
  } catch { return { time: iso, date: '' }; }
}

function formatDuration(min) {
  const h = Math.floor(min / 60);
  const m = min % 60;
  return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function formatPrice(p) {
  return p.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
</script>
</body>
</html>
