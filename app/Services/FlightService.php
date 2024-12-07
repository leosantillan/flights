<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class FlightService
{
    const MAX_WAIT_HOURS = 4;
    private $apiUrl;

    public function __construct()
    {
        $this->apiUrl = env('FLIGHT_EVENTS_API_URL');
    }

    /**
     * Get flight events from the API
     *
     * @return array
     * @throws \Exception
     */
    public function getFlightEvents(): array
    {
        $response = Http::get($this->apiUrl . '/flight-events');

        if ($response->failed()) {
            throw new \Exception("Error getting flight events");
        }

        return $response->json();
    }

    /**
     * Find journeys between two cities
     *
     * @param string $from
     * @param string $to
     * @param string $date
     * @return array
     * @throws \Exception
     */
    public function findJourneys(string $from, string $to, string $date): array
    {
        $events = $this->getFlightEvents();

        $flights = collect($events)->filter(function ($event) use ($from, $date) {
            return $event['departure_city'] === $from && Carbon::parse($event['departure_datetime'])->isSameDay($date);
        });

        $journeys = [];

        foreach ($flights as $flight) {
            if ($flight['arrival_city'] === $to) {
                $journeys[] = $this->formatJourney([$flight]);
            } else {
                $connections = $this->findConnections($flight, $to, $events);

                if ($connections) {
                    $journeys[] = $this->formatJourney([$flight, $connections]);
                }
            }
        }

        return $journeys;
    }

    /**
     * Find connections between two flights
     *
     * @param array $flight
     * @param string $to
     * @param array $events
     * @return array|null
     */
    private function findConnections(array $flight, string $to, array $events): ?array
    {
        $arrivalTime = Carbon::parse($flight['arrival_datetime']);

        $connections = collect($events)->filter(function ($event) use ($flight, $to, $arrivalTime) {
            $departureTime = Carbon::parse($event['departure_datetime']);

            return $event['departure_city'] === $flight['arrival_city'] &&
                $event['arrival_city'] === $to &&
                $departureTime->diffInHours($arrivalTime) <= self::MAX_WAIT_HOURS &&
                $departureTime->greaterThan($arrivalTime);
        });

        return $connections->first();
    }

    /**
     * Format the journey
     *
     * @param array $flights
     * @return array
     */
    private function formatJourney(array $flights): array
    {
        return [
            'connections' => count($flights),
            'path' => collect($flights)->map(function ($flight) {
                return [
                    'flight_number' => $flight['flight_number'],
                    'from' => $flight['departure_city'],
                    'to' => $flight['arrival_city'],
                    'departure_time' => $flight['departure_datetime'],
                    'arrival_time' => $flight['arrival_datetime']
                ];
            })->toArray()
        ];
    }
}
