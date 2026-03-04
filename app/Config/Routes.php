<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── Search API ───────────────────────────────────────────────
$routes->get('api/flights/search', 'FlightController::search');

// ─── Mock Suppliers ───────────────────────────────────────────
$routes->get('mock/supplier-a', 'MockSupplierController::supplierA');
$routes->get('mock/supplier-b', 'MockSupplierController::supplierB');

// ─── Health Check ─────────────────────────────────────────────
$routes->get('health', 'FlightController::health');

// ─── Frontend UI ──────────────────────────────────────────────
$routes->get('/', 'FlightController::ui');

// ─── API Docs (JSON) ──────────────────────────────────────────
$routes->get('api', 'FlightController::index');
