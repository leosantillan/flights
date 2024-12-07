<?php

namespace App\Http\Controllers;

use App\Services\FlightService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class JourneyController extends Controller
{
    private FlightService $flightService;

    public function __construct(FlightService $flightService)
    {
        $this->flightService = $flightService;
    }

    /**
     * Search for journeys between two cities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'date' => 'required|date_format:Y-m-d'
        ]);

        $from = $request->input('from');
        $to = $request->input('to');
        $date = $request->input('date');

        try {
            $journeys = $this->flightService->findJourneys($from, $to, $date);

            return response()->json($journeys, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
