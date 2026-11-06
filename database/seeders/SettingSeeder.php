<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Paramètres du cabinet (maquette 17).
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create([
            'cabinet_name' => 'Cabinet médical',
            'address' => 'Avenue Habib Bourguiba, Midoun, Djerba',
            'phone' => '75 600 600',
            'opening_time' => '08:00',
            'closing_time' => '17:00',
            'saturday_opening_time' => '08:00',
            'saturday_closing_time' => '12:00',
            'default_slot_minutes' => 30,
            'cancellation_hours' => 24,
            'booking_days_ahead' => 60,
            'reminder_hours' => 24,
        ]);
    }
}
