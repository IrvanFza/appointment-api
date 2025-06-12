<?php

namespace Tests\Unit\Models;

use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_can_be_created(): void
    {
        $location = Location::create(['name' => 'Google Meet']);

        $this->assertModelExists($location);
        $this->assertEquals('Google Meet', $location->name);
    }

    public function test_location_name_must_be_unique(): void
    {
        Location::create(['name' => 'Google Meet']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Location::create(['name' => 'Google Meet']);
    }
} 