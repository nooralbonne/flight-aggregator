<?php

namespace App\Libraries;

/**
 * NormalizedFlight
 *
 * A unified Data Transfer Object representing a single flight result
 * regardless of which supplier provided it.
 */
class NormalizedFlight
{
    public string  $id;
    public string  $supplier;           // 'supplier_a' | 'supplier_b'
    public string  $airline;
    public string  $airlineCode;
    public string  $flightNumber;
    public string  $origin;             // IATA code
    public string  $destination;        // IATA code
    public string  $departureAt;        // ISO 8601
    public string  $arrivalAt;          // ISO 8601
    public int     $durationMinutes;
    public int     $stops;
    public float   $price;
    public string  $currency;
    public string  $cabinClass;         // economy | business | first
    public int     $seatsAvailable;
    public bool    $refundable;
    public array   $baggage;            // ['cabin' => ..., 'checked' => ...]
    public ?array  $layovers;           // null if direct
    public string  $deepLink;           // booking URL (mocked)

    public function __construct(array $data)
    {
        $this->id               = $data['id']               ?? uniqid('FL_');
        $this->supplier         = $data['supplier']         ?? 'unknown';
        $this->airline          = $data['airline']          ?? '';
        $this->airlineCode      = $data['airlineCode']      ?? '';
        $this->flightNumber     = $data['flightNumber']     ?? '';
        $this->origin           = strtoupper($data['origin']      ?? '');
        $this->destination      = strtoupper($data['destination'] ?? '');
        $this->departureAt      = $data['departureAt']      ?? '';
        $this->arrivalAt        = $data['arrivalAt']        ?? '';
        $this->durationMinutes  = (int) ($data['durationMinutes'] ?? 0);
        $this->stops            = (int) ($data['stops']           ?? 0);
        $this->price            = (float) ($data['price']         ?? 0.0);
        $this->currency         = strtoupper($data['currency']    ?? 'USD');
        $this->cabinClass       = strtolower($data['cabinClass']  ?? 'economy');
        $this->seatsAvailable   = (int) ($data['seatsAvailable']  ?? 0);
        $this->refundable       = (bool) ($data['refundable']     ?? false);
        $this->baggage          = $data['baggage']          ?? ['cabin' => '7kg', 'checked' => '0kg'];
        $this->layovers         = $data['layovers']         ?? null;
        $this->deepLink         = $data['deepLink']         ?? '#';
    }

    /**
     * Convert to array for JSON response.
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->id,
            'supplier'         => $this->supplier,
            'airline'          => $this->airline,
            'airline_code'     => $this->airlineCode,
            'flight_number'    => $this->flightNumber,
            'origin'           => $this->origin,
            'destination'      => $this->destination,
            'departure_at'     => $this->departureAt,
            'arrival_at'       => $this->arrivalAt,
            'duration_minutes' => $this->durationMinutes,
            'stops'            => $this->stops,
            'price'            => $this->price,
            'currency'         => $this->currency,
            'cabin_class'      => $this->cabinClass,
            'seats_available'  => $this->seatsAvailable,
            'refundable'       => $this->refundable,
            'baggage'          => $this->baggage,
            'layovers'         => $this->layovers,
            'deep_link'        => $this->deepLink,
        ];
    }
}
