<?php

namespace Database\Seeders;

use App\Models\BodyPart;
use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseBodyPartMappingSeeder extends Seeder
{
    public function run(): void
    {
        $bodyPartIdsByName = BodyPart::query()
            ->pluck('id', 'name')
            ->all();

        Exercise::query()
            ->whereNull('body_part_id')
            ->get(['id', 'body_part'])
            ->each(function (Exercise $exercise) use ($bodyPartIdsByName): void {
                $bodyPartId = $bodyPartIdsByName[$exercise->body_part] ?? null;

                if ($bodyPartId) {
                    $exercise->update([
                        'body_part_id' => $bodyPartId,
                    ]);
                }
            });
    }
}