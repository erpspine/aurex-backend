<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\BodyPart;
use App\Models\Equipment;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private function headersFor(string $role): array
    {
        $user = User::create([
            'name' => $role, 'email' => strtolower($role).'@example.test', 'password' => 'Password123',
            'role' => $role, 'user_type' => $role, 'status' => 'Active',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $token = Str::random(80);
        ApiToken::create([
            'user_id' => $user->id, 'name' => 'test', 'token_type' => 'dashboard',
            'token_hash' => hash('sha256', $token),
        ]);

        return ['Authorization' => 'Bearer '.$token];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $chest = BodyPart::create(['name' => 'Chest', 'image_url' => '/storage/body-parts/chest.png']);
        BodyPart::create(['name' => 'Hidden', 'show_in_mobile_app' => false]);
        BodyPart::create(['name' => 'Draft', 'publish_status' => 'Draft']);
        BodyPart::create(['name' => 'Retired', 'status' => 'Inactive']);

        Exercise::create(['name' => 'Bench Press', 'body_part_id' => $chest->id, 'body_part' => 'Chest']);
        Exercise::create(['name' => 'Push Up', 'body_part_id' => $chest->id, 'body_part' => 'Chest']);
        Exercise::create(['name' => 'Draft Fly', 'body_part_id' => $chest->id, 'publish_status' => 'Draft']);

        Equipment::create([
            'name' => 'Leg Press', 'category' => 'Machine', 'serial_number' => 'SN-1',
            'purchase_price' => 4500000, 'location' => 'Back room', 'image_url' => '/storage/equipment/leg-press.png',
        ]);
        Equipment::create(['name' => 'Broken Rower', 'category' => 'Cardio', 'show_in_mobile_app' => false]);
    }

    public function test_members_get_published_body_parts_with_app_exercise_counts(): void
    {
        $this->getJson('/api/body-parts', $this->headersFor('Member'))
            ->assertOk()
            ->assertJsonCount(1, 'body_parts')
            ->assertJsonPath('body_parts.0.name', 'Chest')
            ->assertJsonPath('body_parts.0.image_url', '/storage/body-parts/chest.png')
            ->assertJsonPath('body_parts.0.exercises_count', 2);
    }

    public function test_members_get_published_equipment_without_internal_fields(): void
    {
        $response = $this->getJson('/api/equipment', $this->headersFor('Member'))
            ->assertOk()
            ->assertJsonCount(1, 'equipment')
            ->assertJsonPath('equipment.0.name', 'Leg Press')
            ->assertJsonPath('equipment.0.image_url', '/storage/equipment/leg-press.png');

        foreach (['serial_number', 'purchase_price', 'location', 'purchase_date', 'next_service_date'] as $field) {
            $response->assertJsonMissingPath("equipment.0.$field");
        }
    }

    public function test_staff_still_see_everything(): void
    {
        $headers = $this->headersFor('Administrator');

        $this->getJson('/api/body-parts', $headers)->assertOk()->assertJsonCount(4, 'body_parts');
        $equipment = $this->getJson('/api/equipment', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'equipment')
            ->json('equipment');
        $this->assertSame('SN-1', collect($equipment)->firstWhere('name', 'Leg Press')['serial_number']);
    }
}
