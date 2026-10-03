<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_list(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertViewIs('plan.management.index');
        $response->assertViewHas('plans');
    }

    public function test_coach_cannot_access_plan_list(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    public function test_student_cannot_access_plan_list(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    public function test_keyword_filter_matches_plan_name(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->create(['name' => '短期集中プラン']);
        Plan::factory()->published()->create(['name' => '長期伴走プラン']);

        $response = $this->actingAs($admin)->get(route('admin.plans.index', ['keyword' => '短期']));

        $response->assertOk();
        $response->assertSee('短期集中プラン');
        $response->assertDontSee('長期伴走プラン');
    }

    public function test_status_filter_returns_matching_published_status(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->create(['name' => 'Published Plan']);
        Plan::factory()->draft()->create(['name' => 'Draft Plan']);
        Plan::factory()->archived()->create(['name' => 'Archived Plan']);

        $response = $this->actingAs($admin)->get(route('admin.plans.index', ['status' => 'published']));

        $response->assertOk();
        $response->assertSee('Published Plan');
        $response->assertDontSee('Draft Plan');
        $response->assertDontSee('Archived Plan');
    }

    public function test_status_filter_returns_matching_draft_status(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->create(['name' => 'Published Plan']);
        Plan::factory()->draft()->create(['name' => 'Draft Plan']);
        Plan::factory()->archived()->create(['name' => 'Archived Plan']);

        $response = $this->actingAs($admin)->get(route('admin.plans.index', ['status' => 'draft']));

        $response->assertOk();
        $response->assertSee('Draft Plan');
        $response->assertDontSee('Published Plan');
        $response->assertDontSee('Archived Plan');
    }

    public function test_status_filter_returns_matching_archived_status(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->create(['name' => 'Published Plan']);
        Plan::factory()->draft()->create(['name' => 'Draft Plan']);
        Plan::factory()->archived()->create(['name' => 'Archived Plan']);

        $response = $this->actingAs($admin)->get(route('admin.plans.index', ['status' => 'archived']));

        $response->assertOk();
        $response->assertSee('Archived Plan');
        $response->assertDontSee('Published Plan');
        $response->assertDontSee('Draft Plan');
    }

    public function test_list_counts_only_in_progress_students(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create(['name' => 'Count Target Plan']);
        User::factory()->student()->inProgress()->withPlan($plan)->create();
        User::factory()->student()->graduated()->withPlan($plan)->create();
        User::factory()->student()->invited()->withPlan($plan)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $plans = $response->viewData('plans');
        $target = $plans->getCollection()->firstWhere('id', $plan->id);
        $this->assertNotNull($target);
        $this->assertSame(1, $target->users_count);
    }

    public function test_paginates_20_per_page(): void
    {
        $admin = User::factory()->admin()->create();
        Plan::factory()->published()->count(22)->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.index'));

        $response->assertOk();
        $plans = $response->viewData('plans');
        $this->assertSame(20, $plans->perPage());
        $this->assertSame(22, $plans->total());
    }
}
