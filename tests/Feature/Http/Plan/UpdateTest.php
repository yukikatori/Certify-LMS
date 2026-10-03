<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
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
            'name' => '更新後プラン',
            'description' => '更新後の説明',
            'duration_days' => 120,
            'default_meeting_quota' => 16,
            'sort_order' => 50,
        ], $override);
    }

    public function test_admin_can_view_edit_form(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('plan.management.edit');
        $response->assertViewHas('plan');
    }

    public function test_admin_can_update_plan_basic_information(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $plan), $this->payload());

        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => '更新後プラン',
            'description' => '更新後の説明',
            'duration_days' => 120,
            'default_meeting_quota' => 16,
            'sort_order' => 50,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_update_does_not_change_status(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $plan), $this->payload([
            'status' => 'archived',
        ]));

        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_sort_order_keeps_current_value_when_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create(['sort_order' => 33]);
        $payload = $this->payload();
        unset($payload['sort_order']);

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $plan), $payload);

        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertSame(33, $plan->fresh()->sort_order);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->payload(['duration_days' => '']))
            ->assertSessionHasErrors('duration_days');

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), $this->payload(['default_meeting_quota' => '']))
            ->assertSessionHasErrors('default_meeting_quota');
    }

    public function test_coach_cannot_view_edit_form(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.edit', $plan))
            ->assertForbidden();
    }

    public function test_coach_cannot_update_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($coach)
            ->put(route('admin.plans.update', $plan), $this->payload())
            ->assertForbidden();
    }

    public function test_student_cannot_view_edit_form(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($student)
            ->get(route('admin.plans.edit', $plan))
            ->assertForbidden();
    }

    public function test_student_cannot_update_plan(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->draft()->create();

        $this->actingAs($student)
            ->put(route('admin.plans.update', $plan), $this->payload())
            ->assertForbidden();
    }
}
