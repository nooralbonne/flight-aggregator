<?php

namespace App\Libraries;

use Config\Suppliers;

/**
 * SupplierBClient
 *
 * Calls Supplier B directly (internal call to avoid single-thread deadlock).
 * Different format from Supplier A — simulates a real heterogeneous supplier.
 */
class SupplierBClient
{
    protected Suppliers $config;

    protected array $cabinMap = [
        'Y' => 'economy',
        'W' => 'premium_economy',
        'C' => 'business',
        'J' => 'business',
        'F' => 'first',
    ];

    public function __construct()
    {
        $this->config = new Suppliers();
    }

    public function search(array $params): array
    {
        $name      = $this->config->supplierB['name'];
        $startTime = microtime(true);

        log_message('info', '[SupplierB] Request | params: {params}', [
            'params' => json_encode($params),
        ]);

        try {
            // Simulate hard failure if configured
            if ($this->config->supplierB['simulate_fail']) {
                throw new \RuntimeException('Supplier B simulated failure (503)');
            }

            $body = $this->getDirectData($params);
            $ms   = $this->ms($startTime);

            $raw     = $body['data']['results'] ?? [];
            $flights = $this->normalize($raw);

            log_message('info', '[SupplierB] OK — {n} flights in {ms}ms', [
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
            log_message('error', '[SupplierB] Error: {msg}', ['msg' => $e->getMessage()]);
            return $this->fail($e->getMessage(), $ms, $name);
        }
    }

    /**
     * Direct internal call — generates mock data in Supplier B's format.
     * Completely different structure from Supplier A on purpose.
     */
    protected function getDirectData(array $params): array
    {
        $origin      = strtoupper($params['origin']      ?? 'DXB');
        $destination = strtoupper($params['destination'] ?? 'LHR');
        $date        = $params['departure_date']          ?? date('Y-m-d', strtotime('+30 days'));

        $airlines = [
            ['iata' => 'QR', 'name' => 'Qatar Airways'],
            ['iata' => 'SV', 'name' => 'Saudi Arabian Airlines'],
            ['iata' => 'MS', 'name' => 'EgyptAir'],
            ['iata' => 'TK', 'name' => 'Turkish Airlines'],
            ['iata' => 'RJ', 'name' => 'Royal Jordanian'],
        ];

        $cabinCodes = ['Y', 'W', 'C', 'F'];
        $results    = [];
        $count      = rand(3, 7);

        for ($i = 1; $i <= $count; $i++) {
            $airline  = $airlines[array_rand($airlines)];
            $depHour  = rand(4, 23);
            $depMin   = [0, 20, 40][rand(0, 2)];
            $duration = rand(200, 900);
            $depTs    = mktime($depHour, $depMin, 0,
                (int) date('m', strtotime($date)),
                (int) date('d', strtotime($date)),
                (int) date('Y', strtotime($date))
            );
            $arrTs    = $depTs + ($duration * 60);
            $stops    = rand(0, 2);

            $via = [];
            if ($stops > 0) {
                $viaOptions = [
                    ['airport' => 'IST', 'duration_minutes' => rand(60, 240)],
                    ['airport' => 'CAI', 'duration_minutes' => rand(60, 180)],
                ];
                $via = array_slice($viaOptions, 0, $stops);
            }

            $results[] = [
                'ref'               => 'SB' . strtoupper(substr(md5($i . $date . rand(0,999)), 0, 6)),
                'airline_name'      => $airline['name'],
                'iata'              => $airline['iata'],
                'flt_num'           => $airline['iata'] . rand(200, 999),
                'departure_airport' => $origin,
                'arrival_airport'   => $destination,
                'departure_time'    => date('Y-m-d\TH:i:s', $depTs) . '+03:00',
                'arrival_time'      => date('Y-m-d\TH:i:s', $arrTs) . '+00:00',
                'total_minutes'     => $duration,
                'connection_count'  => $stops,
                'total_price'       => [
                    'value'        => round(rand(250, 1500) + rand(0, 99) / 100, 2),
                    'iso_currency' => 'USD',
                ],
                'travel_class'      => $cabinCodes[rand(0, 3)],
                'remaining_seats'   => rand(1, 25),
                'cancellable'       => (bool) rand(0, 1),
                'carry_on_kg'       => rand(0, 1) ? 10 : 7,
                'checked_kg'        => rand(0, 1) ? 23 : 0,
                'via'               => $via,
            ];
        }

        return ['responseCode' => 200, 'data' => ['results' => $results]];
    }

    protected function normalize(array $raw): array
    {
        $out = [];
        foreach ($raw as $r) {
            try {
                $cabin    = $this->cabinMap[$r['travel_class'] ?? 'Y'] ?? 'economy';
                $layovers = null;

                if (! empty($r['via'])) {
                    $layovers = array_map(fn($v) => [
                        'airport'          => $v['airport']           ?? '',
                        'duration_minutes' => (int) ($v['duration_minutes'] ?? 0),
                    ], $r['via']);
                }

                $out[] = new NormalizedFlight([
                    'id'              => 'SB-' . ($r['ref'] ?? uniqid()),
                    'supplier'        => 'supplier_b',
                    'airline'         => $r['airline_name']                 ?? '',
                    'airlineCode'     => $r['iata']                         ?? '',
                    'flightNumber'    => $r['flt_num']                      ?? '',
                    'origin'          => $r['departure_airport']            ?? '',
                    'destination'     => $r['arrival_airport']              ?? '',
                    'departureAt'     => $r['departure_time']               ?? '',
                    'arrivalAt'       => $r['arrival_time']                 ?? '',
                    'durationMinutes' => (int)   ($r['total_minutes']       ?? 0),
                    'stops'           => (int)   ($r['connection_count']    ?? 0),
                    'price'           => (float) ($r['total_price']['value'] ?? 0),
                    'currency'        => $r['total_price']['iso_currency']  ?? 'USD',
                    'cabinClass'      => $cabin,
                    'seatsAvailable'  => (int)   ($r['remaining_seats']     ?? 0),
                    'refundable'      => (bool)  ($r['cancellable']         ?? false),
                    'baggage'         => [
                        'cabin'   => ($r['carry_on_kg'] ?? 0) . 'kg',
                        'checked' => ($r['checked_kg']  ?? 0) . 'kg',
                    ],
                    'layovers'        => $layovers,
                    'deepLink'        => 'https://supplier-b.mock/book/' . ($r['ref'] ?? ''),
                ]);
            } catch (\Exception $e) {
                log_message('warning', '[SupplierB] Normalize error: {e}', ['e' => $e->getMessage()]);
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