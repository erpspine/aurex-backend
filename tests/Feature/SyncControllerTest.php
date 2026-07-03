<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SyncControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_snapshot_only_contains_members_with_access_codes(): void
    {
        [$headers] = $this->agentHeaders();
        $this->member(['access_code' => 'CARD-001']);
        $this->member(['access_code' => null, 'email' => 'second@example.test']);

        $this->getJson('/api/sync/members', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'members')
            ->assertJsonPath('members.0.access_code', 'CARD-001')
            ->assertJsonStructure(['members', 'server_time']);
    }

    public function test_attendance_upload_is_idempotent(): void
    {
        [$headers] = $this->agentHeaders();
        $member = $this->member();
        $payload = [
            'source_event_id' => (string) Str::uuid(),
            'agent_id' => 'test-agent',
            'member_id' => $member->id,
            'occurred_at' => now()->toISOString(),
            'entry_method' => 'Turnstile',
            'gym_zone' => 'Main Gym Floor',
        ];

        $this->postJson('/api/sync/attendance', $payload, $headers)->assertCreated();
        $this->postJson('/api/sync/attendance', $payload, $headers)->assertOk();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('turnstile_events', 1);
    }

    public function test_controller_entry_and_exit_create_one_completed_attendance_session(): void
    {
        [$headers] = $this->agentHeaders();
        $member = $this->member(['access_code' => '100246']);
        $entryTime = now()->subMinutes(10);
        $base = [
            'agent_id' => 'test-agent',
            'member_id' => $member->id,
            'card_number' => '100246',
            'entry_method' => 'Turnstile',
            'gym_zone' => 'Main Gym Floor',
            'controller_serial' => '1G0095',
            'door' => 1,
            'event_type' => 1,
            'controller_allowed' => true,
        ];

        $this->postJson('/api/sync/attendance', [
            ...$base,
            'source_event_id' => (string) Str::uuid(),
            'occurred_at' => $entryTime->toISOString(),
            'direction' => 'In',
            'reader' => 0,
        ], $headers)->assertCreated();

        $this->postJson('/api/sync/attendance', [
            ...$base,
            'source_event_id' => (string) Str::uuid(),
            'occurred_at' => now()->toISOString(),
            'direction' => 'Out',
            'reader' => 1,
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('attendance.status', 'Checked Out');

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('turnstile_events', 2);
        $this->assertDatabaseHas('attendances', [
            'member_id' => $member->id,
            'status' => 'Checked Out',
        ]);
    }

    public function test_remote_gate_command_can_be_collected_and_acknowledged(): void
    {
        [$headers] = $this->agentHeaders();
        $agentHeaders = [...$headers, 'X-Aurex-Agent' => 'test-agent'];

        $commandId = $this->postJson('/api/turnstile/commands', [
            'agent_id' => 'test-agent',
            'reason' => 'Front desk request',
        ], $headers)
            ->assertCreated()
            ->json('command.id');

        $this->getJson('/api/sync/commands', $agentHeaders)
            ->assertOk()
            ->assertJsonPath('commands.0.id', $commandId);

        $this->postJson("/api/sync/commands/{$commandId}/ack", [
            'status' => 'Completed',
            'message' => 'Gate opened.',
        ], $agentHeaders)
            ->assertOk()
            ->assertJsonPath('command.status', 'Completed');

        $this->getJson('/api/sync/commands', $agentHeaders)
            ->assertOk()
            ->assertJsonCount(0, 'commands');
    }

    public function test_a_card_can_be_linked_to_only_one_member_and_unlinked(): void
    {
        [$headers] = $this->agentHeaders();
        $first = $this->member();
        $second = $this->member(['email' => 'other@example.test']);

        $this->putJson("/api/members/{$first->id}/card", [
            'access_code' => '100245',
        ], $headers)
            ->assertOk()
            ->assertJsonPath('member.access_code', '100245');

        $this->putJson("/api/members/{$second->id}/card", [
            'access_code' => '100245',
        ], $headers)->assertUnprocessable();

        $this->putJson("/api/members/{$first->id}/card", [
            'access_code' => null,
        ], $headers)
            ->assertOk()
            ->assertJsonPath('member.access_code', null);
    }

    /**
     * @return array{array<string, string>, User}
     */
    private function agentHeaders(): array
    {
        $user = User::create([
            'name' => 'Sync Agent',
            'email' => 'sync-agent@example.test',
            'user_type' => 'Admin',
            'role' => 'Administrator',
            'status' => 'Active',
            'password' => 'test-password',
        ]);
        $plainToken = Str::random(80);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'test-agent',
            'token_hash' => hash('sha256', $plainToken),
        ]);

        return [['Authorization' => "Bearer {$plainToken}"], $user];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function member(array $attributes = []): Member
    {
        return Member::create([
            'full_name' => 'Test Member',
            'phone' => '+254700000001',
            'email' => 'member@example.test',
            'membership_status' => 'Active',
            'amount_paid' => 0,
            'payment_status' => 'Paid',
            ...$attributes,
        ]);
    }
}
