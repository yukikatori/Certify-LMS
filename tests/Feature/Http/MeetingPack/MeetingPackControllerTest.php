<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingPackControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, int|string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '5回パック',
            'description' => '追加面談用のパックです。',
            'meeting_count' => 5,
            'price' => 12000,
            'stripe_price_id' => 'price_meeting_pack_test',
            'sort_order' => 10,
        ], $overrides);
    }

    /**
     * for index method
     */
    public function test_index_lists_meeting_packs_with_empty_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $plans = MeetingPack::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.index');
        $response->assertViewHas('plans', fn ($viewPlans) => $plans->every(
            fn (MeetingPack $plan) => $viewPlans->contains('id', $plan->id)
        ));
        $response->assertViewHas('keyword', '');
        $response->assertViewHas('status', '');
    }

    public function test_index_filters_meeting_packs_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $published = MeetingPack::factory()->published()->create();
        $draft = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', [
            'status' => MeetingPackStatus::Published->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('plans', fn ($plans) => $plans->contains('id', $published->id)
            && ! $plans->contains('id', $draft->id));
        $response->assertViewHas('status', MeetingPackStatus::Published->value);
    }

    public function test_index_filters_meeting_packs_by_keyword(): void
    {
        $admin = User::factory()->admin()->create();
        $matched = MeetingPack::factory()->create(['name' => '集中面談パック']);
        $notMatched = MeetingPack::factory()->create(['name' => '通常プラン']);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index', [
            'keyword' => '集中面談',
        ]));

        $response->assertOk();
        $response->assertViewHas('plans', fn ($plans) => $plans->contains('id', $matched->id)
            && ! $plans->contains('id', $notMatched->id));
        $response->assertViewHas('keyword', '集中面談');
    }

    public function test_index_orders_meeting_packs_by_status_then_latest_updated(): void
    {
        $admin = User::factory()->admin()->create();
        $olderPublished = MeetingPack::factory()->published()->create(['updated_at' => now()->subMinutes(2)]);
        $newerPublished = MeetingPack::factory()->published()->create(['updated_at' => now()->subMinute()]);
        $draft = MeetingPack::factory()->draft()->create(['updated_at' => now()]);
        $archived = MeetingPack::factory()->archived()->create(['updated_at' => now()->addMinute()]);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewHas('plans', fn ($plans) => $plans->pluck('id')->all() === [
            $newerPublished->id,
            $olderPublished->id,
            $draft->id,
            $archived->id,
        ]);
    }

    public function test_index_paginates_meeting_packs(): void
    {
        $admin = User::factory()->admin()->create();
        MeetingPack::factory()->count(21)->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.index'));

        $response->assertOk();
        $response->assertViewHas('plans', fn ($plans) => $plans->count() === 20
            && $plans->total() === 21
            && $plans->lastPage() === 2);
    }

    public function test_student_cannot_access_index_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();
    }

    public function test_coach_cannot_access_index_page(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.index'))
            ->assertForbidden();
    }

    /**
     * for show method
     */
    public function test_admin_can_show_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.show', $plan));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.show');
        $response->assertViewHas('plan', fn (MeetingPack $viewPlan) => $viewPlan->is($plan)
            && $viewPlan->relationLoaded('payments')
            && $viewPlan->relationLoaded('createdBy')
            && $viewPlan->relationLoaded('updatedBy'));
    }

    public function test_student_cannot_show_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.show', $plan))
            ->assertForbidden();
    }

    public function test_coach_cannot_show_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.show', $plan))
            ->assertForbidden();
    }

    /**
     * for create method
     */
    public function test_admin_can_access_create_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.meeting-packs.create'))
            ->assertOk()
            ->assertViewIs('meeting-pack.management.create');
    }

    public function test_student_cannot_access_create_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.create'))
            ->assertForbidden();
    }

    public function test_coach_cannot_access_create_page(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.create'))
            ->assertForbidden();
    }

    /**
     * for store method
     */
    public function test_admin_can_store_meeting_pack_and_redirect_to_show_page(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(
            route('admin.meeting-packs.store'),
            $this->validPayload(),
        );

        $plan = MeetingPack::query()->where('name', '5回パック')->firstOrFail();

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを作成しました。');
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '5回パック',
            'meeting_count' => 5,
            'price' => 12000,
            'status' => MeetingPackStatus::Draft->value,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_student_cannot_store_meeting_pack(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('admin.meeting-packs.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('meeting_packs', ['name' => '5回パック']);
    }

    public function test_coach_cannot_store_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.store'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('meeting_packs', ['name' => '5回パック']);
    }

    /**
     * for edit method
     */
    public function test_admin_can_access_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.edit', $plan));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.edit');
        $response->assertViewHas('plan', fn (MeetingPack $viewPlan) => $viewPlan->is($plan));
    }

    public function test_student_cannot_access_edit_page(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.edit', $plan))
            ->assertForbidden();
    }

    public function test_coach_cannot_access_edit_page(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.edit', $plan))
            ->assertForbidden();
    }

    /**
     * for update method
     */
    public function test_admin_can_update_meeting_pack_and_redirect_to_show_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->create([
            'name' => '更新前パック',
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.meeting-packs.update', $plan),
            $this->validPayload(['name' => '更新後パック']),
        );

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを更新しました。');
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '更新後パック',
            'meeting_count' => 5,
            'price' => 12000,
            'status' => MeetingPackStatus::Draft->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_student_cannot_update_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create(['name' => '更新前パック']);

        $this->actingAs($student)
            ->patch(
                route('admin.meeting-packs.update', $plan),
                $this->validPayload(['name' => '不正な更新']),
            )
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '更新前パック',
        ]);
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->create(['name' => '更新前パック']);

        $this->actingAs($coach)
            ->patch(
                route('admin.meeting-packs.update', $plan),
                $this->validPayload(['name' => '不正な更新']),
            )
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'name' => '更新前パック',
        ]);
    }

    /**
     * for delete method
     */
    public function test_admin_can_delete_draft_meeting_pack_and_redirect_to_index_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->delete(route('admin.meeting-packs.destroy', $plan));

        $response->assertRedirect(route('admin.meeting-packs.index'));
        $response->assertSessionHas('success', '面談パックを削除しました。');
        $this->assertDatabaseMissing('meeting_packs', ['id' => $plan->id]);
    }

    public function test_admin_cannot_delete_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->actingAs($admin)
            ->deleteJson(route('admin.meeting-packs.destroy', $plan))
            ->assertStatus(409);

        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id]);
    }

    public function test_admin_cannot_delete_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->actingAs($admin)
            ->deleteJson(route('admin.meeting-packs.destroy', $plan))
            ->assertStatus(409);

        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id]);
    }

    public function test_student_cannot_delete_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($student)
            ->delete(route('admin.meeting-packs.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id]);
    }

    public function test_coach_cannot_delete_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($coach)
            ->delete(route('admin.meeting-packs.destroy', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', ['id' => $plan->id]);
    }

    /**
     * for publish method
     */
    public function test_admin_can_publish_meeting_pack_and_redirect_to_show_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.publish', $plan));

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを公開しました。');
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Published->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_cannot_publish_non_draft_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([MeetingPackStatus::Published, MeetingPackStatus::Archived] as $status) {
            $plan = MeetingPack::factory()->create(['status' => $status->value]);

            $this->actingAs($admin)
                ->postJson(route('admin.meeting-packs.publish', $plan))
                ->assertStatus(409);

            $this->assertDatabaseHas('meeting_packs', [
                'id' => $plan->id,
                'status' => $status->value,
            ]);
        }
    }

    public function test_student_cannot_publish_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($student)
            ->post(route('admin.meeting-packs.publish', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Draft->value,
        ]);
    }

    public function test_coach_cannot_publish_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.publish', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Draft->value,
        ]);
    }

    /**
     * for unarchive method
     */
    public function test_admin_can_unarchive_meeting_pack_and_redirect_to_show_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.unarchive', $plan));

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックを下書きに戻しました。');
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Draft->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_cannot_unarchive_non_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([MeetingPackStatus::Draft, MeetingPackStatus::Published] as $status) {
            $plan = MeetingPack::factory()->create(['status' => $status->value]);

            $this->actingAs($admin)
                ->postJson(route('admin.meeting-packs.unarchive', $plan))
                ->assertStatus(409);

            $this->assertDatabaseHas('meeting_packs', [
                'id' => $plan->id,
                'status' => $status->value,
            ]);
        }
    }

    public function test_student_cannot_unarchive_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->actingAs($student)
            ->post(route('admin.meeting-packs.unarchive', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Archived->value,
        ]);
    }

    public function test_coach_cannot_unarchive_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.unarchive', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Archived->value,
        ]);
    }

    /**
     * for archive method
     */
    public function test_admin_can_archive_meeting_pack_and_redirect_to_show_page(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.archive', $plan));

        $response->assertRedirect(route('admin.meeting-packs.show', $plan));
        $response->assertSessionHas('success', '面談パックをアーカイブしました。');
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Archived->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_cannot_archive_non_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([MeetingPackStatus::Draft, MeetingPackStatus::Archived] as $status) {
            $plan = MeetingPack::factory()->create(['status' => $status->value]);

            $this->actingAs($admin)
                ->postJson(route('admin.meeting-packs.archive', $plan))
                ->assertStatus(409);

            $this->assertDatabaseHas('meeting_packs', [
                'id' => $plan->id,
                'status' => $status->value,
            ]);
        }
    }

    public function test_student_cannot_archive_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->actingAs($student)
            ->post(route('admin.meeting-packs.archive', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Published->value,
        ]);
    }

    public function test_coach_cannot_archive_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = MeetingPack::factory()->published()->create();

        $this->actingAs($coach)
            ->post(route('admin.meeting-packs.archive', $plan))
            ->assertForbidden();

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $plan->id,
            'status' => MeetingPackStatus::Published->value,
        ]);
    }
}
