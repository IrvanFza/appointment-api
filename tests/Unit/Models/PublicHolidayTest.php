<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\PublicHoliday;

class PublicHolidayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_public_holiday(): void
    {
        $holiday = PublicHoliday::create([
            'name' => 'New Year',
            'date' => '2024-01-01',
        ]);

        $this->assertDatabaseHas('public_holidays', [
            'name' => 'New Year',
            'date' => '2024-01-01',
        ]);
    }

    public function test_cannot_create_duplicate_dates(): void
    {
        PublicHoliday::create([
            'name' => 'New Year',
            'date' => '2024-01-01',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        PublicHoliday::create([
            'name' => 'Another Holiday',
            'date' => '2024-01-01',
        ]);
    }

    public function test_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        PublicHoliday::create([]);
    }
}
