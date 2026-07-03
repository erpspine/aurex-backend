<?php

namespace Database\Seeders;

use App\Models\BodyPart;
use Illuminate\Database\Seeder;

class BodyPartSeeder extends Seeder
{
    public function run(): void
    {
        $bodyParts = [
            [
                'name' => 'Shoulders',
                'description' => 'Deltoids and supporting upper-shoulder muscle groups.',
            ],
            [
                'name' => 'Legs',
                'description' => 'Quadriceps, hamstrings, calves, and glute-dominant movement patterns.',
            ],
            [
                'name' => 'Back',
                'description' => 'Lats, traps, rhomboids, and posterior chain support muscles.',
            ],
            [
                'name' => 'Chest',
                'description' => 'Pectoral muscles with pressing-focused movement patterns.',
            ],
            [
                'name' => 'Abs',
                'description' => 'Core and abdominal stability-focused movements.',
            ],
            [
                'name' => 'Full Body',
                'description' => 'Compound and conditioning movements recruiting multiple muscle groups.',
            ],
        ];

        foreach ($bodyParts as $bodyPart) {
            BodyPart::updateOrCreate(
                ['name' => $bodyPart['name']],
                [
                    'description' => $bodyPart['description'],
                    'status' => 'Active',
                    'show_in_mobile_app' => true,
                    'publish_status' => 'Published',
                ],
            );
        }
    }
}