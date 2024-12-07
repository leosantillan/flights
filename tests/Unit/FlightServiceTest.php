<?php

namespace Tests\Unit;

use App\Services\FlightService;
use Tests\TestCase;

class FlightServiceTest extends TestCase
{
    public function testFindJourneys()
    {
        $service = new FlightService();
        $journeys = $service->findJourneys('BUE', 'AMS', '2024-12-06');

        $this->assertNotEmpty($journeys);
        $this->assertEquals('BUE', $journeys[0]['path'][0]['from']);
        $this->assertEquals('MAD', $journeys[0]['path'][0]['to']);
    }
}
