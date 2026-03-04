<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Config\Suppliers;

/**
 * MockSupplierController
 *
 * Provides mock HTTP endpoints that simulate external supplier APIs.
 *
 * Supplier A: /mock/supplier-a  → format A (flight_id, carrier, from/to, fare...)
 * Supplier B: /mock/supplier-b  → format B (ref, airline_name, departure_airport, total_price...)
 *
 * Both support simulation flags via query params or .env:
 *   ?simulate_slow=1   → Adds a 4-second delay (Supplier A)
 *   ?simulate_fail=1   → Returns 503 (Supplier B)
 */
class MockSupplierController extends BaseController
{
    protected Suppliers $config;

    public function __construct()
    {
        $this->config = new Suppliers();
    }

    // ─────────────────────────────────────────────────────────────
    // Supplier A  —  /mock/supplier-a
    // ─────────────────────────────────────────────────────────────

    public function supplierA(): ResponseInterface
    {
        $origin      = strtoupper($this->request->getGet('origin')      ?? 'DXB');
        $destination = strtoupper($this->request->getGet('destination') ?? 'LHR');
        $date        = $this->request->getGet('departure_date')          ?? date('Y-m-d', strtotime('+30 days'));

        // ── Simulate slow response ────────────────────────────────
        $simulateSlow = $this->request->getGet('simulate_slow') === '1'
            || $this->config->supplierA['simulate_slow'];

        if ($simulateSlow) {
            sleep($this->config->supplierA['slow_delay'] ?? 4);
        }

        // ── Generate mock data ────────────────────────────────────
        $airlines = [
            ['code' => 'EK', 'name' => 'Emirates'],
            ['code' => 'FZ', 'name' => 'flydubai'],
            ['code' => 'EY', 'name' => 'Etihad Airways'],
            ['code' => 'G9', 'name' => 'Air Arabia'],
            ['code' => 'WY', 'name' => 'Oman Air'],
        ];

        $flights = [];
        $flightCount = rand(3, 6);

        for ($i = 1; $i <= $flightCount; $i++) {
            $airline    = $airlines[array_rand($airlines)];
            $depHour    = rand(5, 22);
            $depMin     = [0, 15, 30, 45][rand(0, 3)];
            $duration   = rand(180, 780);
            $arrTime    = strtotime("{$date} {$depHour}:{$depMin}") + ($duration * 60);
            $stops      = rand(0, 2);
            $price      = round(rand(200, 1200) + (rand(0, 99) / 100), 2);
            $seats      = rand(1, 30);

            $layovers = null;
            if ($stops > 0) {
                $layovers = [];
                $stopovers = [
                    ['airport' => 'DOH', 'duration_minutes' => rand(60, 180)],
                    ['airport' => 'AUH', 'duration_minutes' => rand(60, 180)],
                    ['airport' => 'MCT', 'duration_minutes' => rand(45, 120)],
                ];
                for ($s = 0; $s < $stops && $s < 2; $s++) {
                    $layovers[] = $stopovers[$s];
                }
            }

            $flights[] = [
                'flight_id'         => 'SA' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'carrier'           => $airline['name'],
                'carrier_code'      => $airline['code'],
                'flight_no'         => $airline['code'] . rand(100, 999),
                'from'              => $origin,
                'to'                => $destination,
                'departs'           => date('Y-m-d\TH:i:s\Z', mktime($depHour, $depMin, 0, (int)date('m', strtotime($date)), (int)date('d', strtotime($date)), (int)date('Y', strtotime($date)))),
                'arrives'           => date('Y-m-d\TH:i:s\Z', $arrTime),
                'flight_duration'   => $duration,
                'num_stops'         => $stops,
                'fare'              => [
                    'amount'   => $price,
                    'currency' => 'USD',
                ],
                'class'             => ['economy', 'business', 'first'][rand(0, 2)],
                'available_seats'   => $seats,
                'is_refundable'     => (bool) rand(0, 1),
                'baggage_allowance' => [
                    'carry_on' => '7kg',
                    'hold'     => (rand(0, 1) ? '23kg' : '0kg'),
                ],
                'layovers'          => $layovers,
            ];
        }

        return $this->response->setJSON([
            'status'  => 'ok',
            'flights' => $flights,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Supplier B  —  /mock/supplier-b
    // ─────────────────────────────────────────────────────────────

    public function supplierB(): ResponseInterface
    {
        $origin      = strtoupper($this->request->getGet('origin')      ?? 'DXB');
        $destination = strtoupper($this->request->getGet('destination') ?? 'LHR');
        $date        = $this->request->getGet('departure_date')          ?? date('Y-m-d', strtotime('+30 days'));

        // ── Simulate failure ──────────────────────────────────────
        $simulateFail = $this->request->getGet('simulate_fail') === '1'
            || $this->config->supplierB['simulate_fail'];

        if ($simulateFail) {
            return $this->response
                ->setStatusCode(503)
                ->setJSON([
                    'responseCode' => 503,
                    'message'      => 'Supplier B service temporarily unavailable',
                ]);
        }

        // ── Generate mock data in Supplier B's format ─────────────
        $airlines = [
            ['iata' => 'QR', 'name' => 'Qatar Airways'],
            ['iata' => 'SV', 'name' => 'Saudi Arabian Airlines'],
            ['iata' => 'MS', 'name' => 'EgyptAir'],
            ['iata' => 'TK', 'name' => 'Turkish Airlines'],
            ['iata' => 'RJ', 'name' => 'Royal Jordanian'],
        ];

        $cabinCodes = ['Y', 'W', 'C', 'F'];
        $results    = [];
        $flightCount = rand(3, 7);

        for ($i = 1; $i <= $flightCount; $i++) {
            $airline  = $airlines[array_rand($airlines)];
            $depHour  = rand(4, 23);
            $depMin   = [0, 20, 40][rand(0, 2)];
            $duration = rand(200, 900);
            $arrTime  = strtotime("{$date} {$depHour}:{$depMin}") + ($duration * 60);
            $stops    = rand(0, 2);
            $price    = round(rand(250, 1500) + (rand(0, 99) / 100), 2);

            // Supplier B uses timezone offsets in its dates
            $depOffset  = '+03:00';
            $arrOffset  = '+00:00';
            $depTs      = mktime($depHour, $depMin, 0, (int)date('m', strtotime($date)), (int)date('d', strtotime($date)), (int)date('Y', strtotime($date)));

            $via = [];
            if ($stops > 0) {
                $viaOptions = [
                    ['airport' => 'IST', 'duration_minutes' => rand(60, 240)],
                    ['airport' => 'CAI', 'duration_minutes' => rand(60, 180)],
                    ['airport' => 'AMM', 'duration_minutes' => rand(45, 120)],
                ];
                for ($s = 0; $s < $stops && $s < 2; $s++) {
                    $via[] = $viaOptions[$s];
                }
            }

            $results[] = [
                'ref'               => 'SB' . strtoupper(substr(md5($i . $date), 0, 6)),
                'airline_name'      => $airline['name'],
                'iata'              => $airline['iata'],
                'flt_num'           => $airline['iata'] . rand(200, 999),
                'departure_airport' => $origin,
                'arrival_airport'   => $destination,
                'departure_time'    => date('Y-m-d\TH:i:s', $depTs) . $depOffset,
                'arrival_time'      => date('Y-m-d\TH:i:s', $arrTime) . $arrOffset,
                'total_minutes'     => $duration,
                'connection_count'  => $stops,
                'total_price'       => [
                    'value'        => $price,
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

        return $this->response->setJSON([
            'responseCode' => 200,
            'data'         => [
                'results' => $results,
            ],
        ]);
    }
}
