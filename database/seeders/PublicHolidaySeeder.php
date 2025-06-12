<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PublicHoliday;

class PublicHolidaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = now()->year;

        $holidays = [
            [
                'name' => 'New Year\'s Day',
                'date' => "{$currentYear}-01-01",
            ],
            [
                'name' => 'International Women\'s Day',
                'date' => "{$currentYear}-03-08",
            ],
            [
                'name' => 'International Workers\' Day',
                'date' => "{$currentYear}-05-01",
            ],
            [
                'name' => 'World Environment Day',
                'date' => "{$currentYear}-06-05",
            ],
            [
                'name' => 'International Day of Peace',
                'date' => "{$currentYear}-09-21",
            ],
            [
                'name' => 'World Teachers\' Day',
                'date' => "{$currentYear}-10-05",
            ],
            [
                'name' => 'International Human Rights Day',
                'date' => "{$currentYear}-12-10",
            ],
            [
                'name' => 'Christmas Day',
                'date' => "{$currentYear}-12-25",
            ],
        ];

        foreach ($holidays as $holiday) {
            PublicHoliday::create($holiday);
        }
    }
}
