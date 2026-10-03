<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $override
     *
     * @return array<string, mixed>
     */
    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => '新規プラン',
            'description' => '説明文',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
            'sort_order' => 20,
        ], $override);
    }

    public function test_admin_can_view_create_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.create'));

        $response->assertOk();
        $response->assertViewIs('plan.management.create');
    }

    public function test_admin_can_create_plan_as_draft(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), $this->payload());

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'name' => '新規プラン',
            'description' => '説明文',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
            'sort_order' => 20,
            'status' => 'draft',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_sort_order_defaults_to_zero_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();
        unset($payload['sort_order']);

        $response = $this->actingAs($admin)->post(route('admin.plans.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'name' => '新規プラン',
            'sort_order' => 0,
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.plans.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->post(route('admin.plans.store'), $this->payload(['duration_days' => '']))
            ->assertSessionHasErrors('duration_days');

        $this->actingAs($admin)
            ->post(route('admin.plans.store'), $this->payload(['default_meeting_quota' => '']))
            ->assertSessionHasErrors('default_meeting_quota');
    }

    public function test_coach_cannot_view_create_form(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.create'))
            ->assertForbidden();
    }

    public function test_coach_cannot_create_plan(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->post(route('admin.plans.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_student_cannot_view_create_form(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.plans.create'))
            ->assertForbidden();
    }

    public function test_student_cannot_create_plan(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('admin.plans.store'), $this->payload())
            ->assertForbidden();
    }
}
