<?php

namespace App\Controllers;

use App\Services\FlightAggregatorService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FlightController
 *
 * Handles flight search API requests and serves the frontend UI.
 */
class FlightController extends BaseController
{
    protected FlightAggregatorService $aggregator;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->aggregator = new FlightAggregatorService();
    }

    // ── GET /  → Frontend UI ─────────────────────────────────────
    public function ui(): ResponseInterface
    {
        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody(view('search'));
    }

    // ── GET /api/flights/search ───────────────────────────────────
    public function search(): ResponseInterface
    {
        $rules = [
            'origin'          => 'required|min_length[2]|max_length[3]|alpha',
            'destination'     => 'required|min_length[2]|max_length[3]|alpha',
            'departure_date'  => 'required|valid_date[Y-m-d]',
            'passengers'      => 'permit_empty|is_natural_no_zero|less_than[10]',
            'cabin_class'     => 'permit_empty|in_list[economy,premium_economy,business,first]',
            'max_price'       => 'permit_empty|decimal',
            'min_price'       => 'permit_empty|decimal',
            'max_stops'       => 'permit_empty|in_list[0,1,2,3]',
            'sort_by'         => 'permit_empty|in_list[price,duration,departure,arrival,stops,airline,seats_available]',
            'sort_order'      => 'permit_empty|in_list[asc,desc]',
            'refundable_only' => 'permit_empty|in_list[true,false]',
        ];

        if (! $this->validateData($this->request->getGet(), $rules)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $this->validator->getErrors(),
                ]);
        }

        $origin      = strtoupper($this->request->getGet('origin'));
        $destination = strtoupper($this->request->getGet('destination'));
        $departureDate = $this->request->getGet('departure_date');

        if ($departureDate < date('Y-m-d')) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Departure date cannot be in the past.',
            ]);
        }

        if ($origin === $destination) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Origin and destination cannot be the same.',
            ]);
        }

        $params = [
            'origin'          => $origin,
            'destination'     => $destination,
            'departure_date'  => $departureDate,
            'passengers'      => (int) ($this->request->getGet('passengers') ?? 1),
            'sort_by'         => $this->request->getGet('sort_by')    ?? 'price',
            'sort_order'      => $this->request->getGet('sort_order') ?? 'asc',
        ];

        // Optional params — only add if provided
        $optional = ['cabin_class','max_price','min_price','airlines','max_stops','refundable_only','max_duration_minutes','return_date'];
        foreach ($optional as $key) {
            $val = $this->request->getGet($key);
            if ($val !== null && $val !== '') {
                $params[$key] = $val;
            }
        }

        $result = $this->aggregator->search($params);

        $httpStatus = match ($result['status']) {
            'partial' => 206,
            'error'   => 503,
            default   => 200,
        };

        return $this->response->setStatusCode($httpStatus)->setJSON($result);
    }

    // ── GET /health ───────────────────────────────────────────────
    public function health(): ResponseInterface
    {
        return $this->response->setJSON([
            'status'    => 'ok',
            'service'   => 'Flight Aggregator API',
            'version'   => '1.0.0',
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'php_version' => PHP_VERSION,
            'timestamp' => date('c'),
        ]);
    }

    // ── GET /api → JSON docs ──────────────────────────────────────
    public function index(): ResponseInterface
    {
        return $this->response->setJSON([
            'service'   => 'Asfar Flight Aggregator API',
            'version'   => '1.0.0',
            'endpoints' => [
                ['GET', '/api/flights/search', 'Search flights (required: origin, destination, departure_date)'],
                ['GET', '/health',             'Health check'],
                ['GET', '/mock/supplier-a',    'Mock Supplier A (simulate_slow=1)'],
                ['GET', '/mock/supplier-b',    'Mock Supplier B (simulate_fail=1)'],
            ],
            'example' => 'http://localhost:8080/api/flights/search?origin=DXB&destination=LHR&departure_date=2025-09-15',
        ]);
    }
}
