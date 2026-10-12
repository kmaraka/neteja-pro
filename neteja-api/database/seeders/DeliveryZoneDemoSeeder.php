<?php

namespace Database\Seeders;

use App\Modules\Ecommerce\Models\DeliveryZone;
use Illuminate\Database\Seeder;

class DeliveryZoneDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Commune => [frais FCFA, délai]
        $zones = [
            'Plateau' => [1500, '24 à 48 h'],
            'Cocody' => [2000, '24 à 48 h'],
            'Marcory' => [1500, '24 à 48 h'],
            'Treichville' => [1500, '24 à 48 h'],
            'Adjamé' => [2000, '24 à 48 h'],
            'Attécoubé' => [2000, '24 à 48 h'],
            'Koumassi' => [2000, '24 à 48 h'],
            'Port-Bouët' => [2500, '24 à 48 h'],
            'Yopougon' => [2500, '24 à 48 h'],
            'Abobo' => [2500, '24 à 48 h'],
            'Bingerville' => [3500, '48 à 72 h'],
            'Anyama' => [3500, '48 à 72 h'],
            'Songon' => [4000, '48 à 72 h'],
        ];

        foreach ($zones as $commune => [$fee, $delay]) {
            DeliveryZone::updateOrCreate(
                ['commune' => $commune],
                ['fee_fcfa' => $fee, 'estimated_delay' => $delay, 'is_active' => true],
            );
        }
    }
}
