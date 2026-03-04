<?php

namespace App\Services;

use App\Libraries\SupplierAClient;
use App\Libraries\SupplierBClient;
use App\Libraries\NormalizedFlight;
use CodeIgniter\Cache\CacheInterface;
use Config\Suppliers;

/**
 * FlightAggregatorService
 *
 * Orchestrates parallel-like calls to both suppliers,
 * merges results, applies filtering/sorting, and caches.
 */
class FlightAggregatorService
{
    protected SupplierAClient $supplierA;
    protected SupplierBClient $supplierB;
    protected CacheInterface  $cache;
    protected Suppliers       $config;
    protected \Psr\Log\LoggerInterface $logger;

    public function __construct()
    {
        $this->supplierA = new SupplierAClient();
        $this->supplierB = new SupplierBClient();
        $this->cache     = service('cache');
        $this->config    = new Suppliers();
        $this->logger    = service('logger');
    }

    /**
     * Search flights from both suppliers, merge, filter, sort, and cache.
     *
     * @param array $params Validated search parameters
     * @return array Complete aggregated response
     */
    public function search(array $params): array
    {
        $cacheKey = $this->buildCacheKey($params);
        $ttl      = $this->config->cacheTTL;

        // ── Cache HIT ──────────────────────────────────────────────
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->info("[Aggregator] Cache HIT", ['key' => $cacheKey]);
            $cached['meta']['cache_hit'] = true;
            return $cached;
        }

        $this->logger->info("[Aggregator] Cache MISS - fetching from suppliers", [
            'key'    => $cacheKey,
            'params' => $params,
        ]);

        $globalStart = microtime(true);

        // ── Call both suppliers (sequential but with independent error handling) ──
        $resultA = $this->supplierA->search($params);
        $resultB = $this->supplierB->search($params);

        $globalDuration = (int) ((microtime(true) - $globalStart) * 1000);

        // ── Merge flights ─────────────────────────────────────────
        /** @var NormalizedFlight[] $allFlights */
        $allFlights = array_merge($resultA['flights'], $resultB['flights']);

        // ── Apply filters ─────────────────────────────────────────
        $filtered = $this->applyFilters($allFlights, $params);

        // ── Apply sorting ─────────────────────────────────────────
        $sorted = $this->applySort($filtered, $params['sort_by'] ?? 'price', $params['sort_order'] ?? 'asc');

        // ── Build supplier summary ────────────────────────────────
        $supplierMeta = [];

        if ($resultA['error']) {
            $this->logger->warning("[Aggregator] Supplier A failed: {$resultA['error']}");
            $supplierMeta['supplier_a'] = ['status' => 'error', 'error' => $resultA['error'], 'duration_ms' => $resultA['duration_ms']];
        } else {
            $supplierMeta['supplier_a'] = ['status' => 'ok', 'count' => $resultA['count'], 'duration_ms' => $resultA['duration_ms']];
        }

        if ($resultB['error']) {
            $this->logger->warning("[Aggregator] Supplier B failed: {$resultB['error']}");
            $supplierMeta['supplier_b'] = ['status' => 'error', 'error' => $resultB['error'], 'duration_ms' => $resultB['duration_ms']];
        } else {
            $supplierMeta['supplier_b'] = ['status' => 'ok', 'count' => $resultB['count'], 'duration_ms' => $resultB['duration_ms']];
        }

        // ── Determine overall status ──────────────────────────────
        $bothFailed = $resultA['error'] !== null && $resultB['error'] !== null;
        $partial    = ($resultA['error'] !== null || $resultB['error'] !== null) && !$bothFailed;

        $response = [
            'status' => $bothFailed ? 'error' : ($partial ? 'partial' : 'success'),
            'meta'   => [
                'search_params'     => $params,
                'total_results'     => count($sorted),
                'filtered_from'     => count($allFlights),
                'cache_hit'         => false,
                'cached_until'      => date('c', time() + $ttl),
                'total_duration_ms' => $globalDuration,
                'suppliers'         => $supplierMeta,
            ],
            'results' => array_values(array_map(fn(NormalizedFlight $f) => $f->toArray(), $sorted)),
        ];

        // ── Cache only if at least one supplier succeeded ─────────
        if (!$bothFailed) {
            $this->cache->save($cacheKey, $response, $ttl);
            $this->logger->info("[Aggregator] Cached {$ttl}s", ['key' => $cacheKey]);
        }

        $this->logger->info("[Aggregator] Done", [
            'total'       => count($sorted),
            'duration_ms' => $globalDuration,
            'status'      => $response['status'],
        ]);

        return $response;
    }

    // ────────────────────────────────────────────────────────────
    // FILTERING
    // ────────────────────────────────────────────────────────────

    /**
     * @param NormalizedFlight[] $flights
     * @return NormalizedFlight[]
     */
    protected function applyFilters(array $flights, array $params): array
    {
        return array_filter($flights, function (NormalizedFlight $f) use ($params): bool {

            // Filter by max price
            if (!empty($params['max_price']) && $f->price > (float) $params['max_price']) {
                return false;
            }

            // Filter by min price
            if (!empty($params['min_price']) && $f->price < (float) $params['min_price']) {
                return false;
            }

            // Filter by airline code(s)
            if (!empty($params['airlines'])) {
                $airlines = array_map('strtoupper', explode(',', $params['airlines']));
                if (!in_array(strtoupper($f->airlineCode), $airlines)) {
                    return false;
                }
            }

            // Filter by max stops
            if (isset($params['max_stops']) && $params['max_stops'] !== '') {
                if ($f->stops > (int) $params['max_stops']) {
                    return false;
                }
            }

            // Filter by cabin class
            if (!empty($params['cabin_class'])) {
                if (strtolower($f->cabinClass) !== strtolower($params['cabin_class'])) {
                    return false;
                }
            }

            // Filter by refundable only
            if (!empty($params['refundable_only']) && $params['refundable_only'] === 'true') {
                if (!$f->refundable) {
                    return false;
                }
            }

            // Filter by max duration
            if (!empty($params['max_duration_minutes'])) {
                if ($f->durationMinutes > (int) $params['max_duration_minutes']) {
                    return false;
                }
            }

            return true;
        });
    }

    // ────────────────────────────────────────────────────────────
    // SORTING
    // ────────────────────────────────────────────────────────────

    /**
     * @param NormalizedFlight[] $flights
     * @return NormalizedFlight[]
     */
    protected function applySort(array $flights, string $sortBy, string $sortOrder): array
    {
        $flights = array_values($flights);

        usort($flights, function (NormalizedFlight $a, NormalizedFlight $b) use ($sortBy, $sortOrder): int {
            $result = match ($sortBy) {
                'price'            => $a->price           <=> $b->price,
                'duration'         => $a->durationMinutes <=> $b->durationMinutes,
                'departure'        => $a->departureAt     <=> $b->departureAt,
                'arrival'          => $a->arrivalAt       <=> $b->arrivalAt,
                'stops'            => $a->stops           <=> $b->stops,
                'airline'          => $a->airline         <=> $b->airline,
                'seats_available'  => $a->seatsAvailable  <=> $b->seatsAvailable,
                default            => $a->price           <=> $b->price,
            };

            return $sortOrder === 'desc' ? -$result : $result;
        });

        return $flights;
    }

    // ────────────────────────────────────────────────────────────
    // CACHE KEY
    // ────────────────────────────────────────────────────────────

    protected function buildCacheKey(array $params): string
    {
        ksort($params);
        return 'flight_search_' . md5(json_encode($params));
    }
}
