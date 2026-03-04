<?php

namespace App\Libraries;

use Config\Suppliers;
use App\Controllers\MockSupplierController;

/**
 * SupplierAClient
 *
 * Calls Supplier A directly (internal call to avoid single-thread deadlock).
 * In production, replace getDirectData() with a real cURL/HTTP call.
 */
class SupplierAClient
{
    protected Suppliers $config;

    public function __construct()
    {
        $this->config = new Suppliers();
    }

    public function search(array $params): array
    {
        $name      = $this->config->supplierA['name'];
        $startTime = microtime(true);

        log_message('info', '[SupplierA] Request | params: {params}', [
            'params' => json_encode($params),
        ]);

        try {
            // Simulate slow response if configured
            if ($this->config->supplierA['simulate_slow']) {
                sleep((int) ($this->config->supplierA['slow_delay'] ?? 4));
            }

            $body = $this->getDirectData($params);
            $ms   = $this->ms($startTime);

            if (! isset($body['flights'])) {
                return $this->fail('Invalid response structure', $ms, $name);
            }

            $flights = $this->normalize($body['flights']);

            log_message('info', '[SupplierA] OK — {n} flights in {ms}ms', [
                'n' => count($flights), 'ms' => $ms,
            ]);

            return [
                'flights'     => $flights,
                'count'       => count($flights),
                'supplier'    => $name,
                'error'       => null,
                'duration_ms' => $ms,
            ];

        } catch (\Exception $e) {
            $ms = $this->ms($startTime);
            log_message('error', '[SupplierA] Error: {msg}', ['msg' => $e->getMessage()]);
            return $this->fail($e->getMessage(), $ms, $name);
        }
    }

    /**
     * Direct internal call — generates mock data without HTTP.
     * Mirrors exactly what /mock/supplier-a returns.
     */
    protected function getDirectData(array $params): array
    {
        $origin      = strtoupper($params['origin']         ?? 'DXB');
        $destination = strtoupper($params['destination']    ?? 'LHR');
        $date        = $params['departure_date']             ?? date('Y-m-d', strtotime('+30 days'));

        $airlines = [
            ['code' => 'EK', 'name' => 'Emirates'],
            ['code' => 'FZ', 'name' => 'flydubai'],
            ['code' => 'EY', 'name' => 'Etihad Airways'],
            ['code' => 'G9', 'name' => 'Air Arabia'],
            ['code' => 'WY', 'name' => 'Oman Air'],
        ];

        $flights = [];
        $count   = rand(3, 6);

        for ($i = 1; $i <= $count; $i++) {
            $airline  = $airlines[array_rand($airlines)];
            $depHour  = rand(5, 22);
            $depMin   = [0, 15, 30, 45][rand(0, 3)];
            $duration = rand(180, 780);
            $depTs    = mktime($depHour, $depMin, 0,
                (int) date('m', strtotime($date)),
                (int) date('d', strtotime($date)),
                (int) date('Y', strtotime($date))
            );
            $arrTs    = $depTs + ($duration * 60);
            $stops    = rand(0, 2);

            $layovers = null;
            if ($stops > 0) {
                $via = [
                    ['airport' => 'DOH', 'duration_minutes' => rand(60, 180)],
                    ['airport' => 'AUH', 'duration_minutes' => rand(60, 180)],
                ];
                $layovers = array_slice($via, 0, $stops);
            }

            $flights[] = [
                'flight_id'         => 'SA' . str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'carrier'           => $airline['name'],
                'carrier_code'      => $airline['code'],
                'flight_no'         => $airline['code'] . rand(100, 999),
                'from'              => $origin,
                'to'                => $destination,
                'departs'           => date('Y-m-d\TH:i:s\Z', $depTs),
                'arrives'           => date('Y-m-d\TH:i:s\Z', $arrTs),
                'flight_duration'   => $duration,
                'num_stops'         => $stops,
                'fare'              => [
                    'amount'   => round(rand(200, 1200) + rand(0, 99) / 100, 2),
                    'currency' => 'USD',
                ],
                'class'             => ['economy', 'business', 'first'][rand(0, 2)],
                'available_seats'   => rand(1, 30),
                'is_refundable'     => (bool) rand(0, 1),
                'baggage_allowance' => [
                    'carry_on' => '7kg',
                    'hold'     => rand(0, 1) ? '23kg' : '0kg',
                ],
                'layovers'          => $layovers,
            ];
        }

        return ['status' => 'ok', 'flights' => $flights];
    }

    protected function normalize(array $raw): array
    {
        $out = [];
        foreach ($raw as $r) {
            try {
                $out[] = new NormalizedFlight([
                    'id'              => 'SA-' . ($r['flight_id'] ?? uniqid()),
                    'supplier'        => 'supplier_a',
                    'airline'         => $r['carrier']                     ?? '',
                    'airlineCode'     => $r['carrier_code']                ?? '',
                    'flightNumber'    => $r['flight_no']                   ?? '',
                    'origin'          => $r['from']                        ?? '',
                    'destination'     => $r['to']                          ?? '',
                    'departureAt'     => $r['departs']                     ?? '',
                    'arrivalAt'       => $r['arrives']                     ?? '',
                    'durationMinutes' => (int)   ($r['flight_duration']    ?? 0),
                    'stops'           => (int)   ($r['num_stops']          ?? 0),
                    'price'           => (float) ($r['fare']['amount']     ?? 0),
                    'currency'        => $r['fare']['currency']            ?? 'USD',
                    'cabinClass'      => $r['class']                       ?? 'economy',
                    'seatsAvailable'  => (int)   ($r['available_seats']    ?? 0),
                    'refundable'      => (bool)  ($r['is_refundable']      ?? false),
                    'baggage'         => [
                        'cabin'   => $r['baggage_allowance']['carry_on'] ?? '7kg',
                        'checked' => $r['baggage_allowance']['hold']     ?? '0kg',
                    ],
                    'layovers'        => $r['layovers'] ?? null,
                    'deepLink'        => 'https://supplier-a.mock/book/' . ($r['flight_id'] ?? ''),
                ]);
            } catch (\Exception $e) {
                log_message('warning', '[SupplierA] Normalize error: {e}', ['e' => $e->getMessage()]);
            }
        }
        return $out;
    }

    protected function fail(string $msg, int $ms, string $name): array
    {
        return ['flights' => [], 'count' => 0, 'supplier' => $name, 'error' => $msg, 'duration_ms' => $ms];
    }

    protected function ms(float $start): int
    {
        return (int) ((microtime(true) - $start) * 1000);
    }
}