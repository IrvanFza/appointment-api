<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'Google Meet',
            'Zoom',
            'Microsoft Teams',
            'Offline',
            'Phone Call',
        ];

        foreach ($locations as $location) {
            Location::create(['name' => $location]);
        }
    }
} 