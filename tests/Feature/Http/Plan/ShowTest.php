<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create(['name' => '詳細確認プラン']);

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertViewIs('plan.management.show');
        $response->assertViewHas('plan');
        $response->assertSee('詳細確認プラン');
    }

    public function test_detail_shows_related_students_and_meta_information(): void
    {
        $creator = User::factory()->admin()->create(['name' => '作成管理者']);
        $updater = User::factory()->admin()->create(['name' => '更新管理者']);
        $plan = Plan::factory()->published()->create([
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $updater->id,
        ]);
        $student = User::factory()->student()->inProgress()->withPlan($plan)->create([
            'name' => '受講 太郎',
            'email' => 'plan-student@example.test',
        ]);

        $response = $this->actingAs($creator)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertSee('受講 太郎');
        $response->assertSee('plan-student@example.test');
        $response->assertSee('作成管理者');
        $response->assertSee('更新管理者');
        $this->assertTrue($response->viewData('plan')->users->first()->is($student));
    }

    public function test_coach_cannot_view_plan_detail(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->published()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.show', $plan))
            ->assertForbidden();
    }

    public function test_student_cannot_view_plan_detail(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->published()->create();

        $this->actingAs($student)
            ->get(route('admin.plans.show', $plan))
            ->assertForbidden();
    }
}
