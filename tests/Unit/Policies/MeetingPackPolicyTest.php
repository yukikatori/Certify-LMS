<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\MeetingPack;
use App\Models\User;
use App\Policies\MeetingPackPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeetingPackPolicy の判定を検証する Unit テスト。
 * admin は全件 CRUD 、coach / student はアクセス不可。
 */
class MeetingPackPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertFalse($policy->viewAny($coach));
        $this->assertFalse($policy->viewAny($student));
    }

    public function test_view_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->view($admin, $plan));
        $this->assertFalse($policy->view($coach, $plan));
        $this->assertFalse($policy->view($student, $plan));
    }

    public function test_create_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($coach));
        $this->assertFalse($policy->create($student));
    }

    public function test_update_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->update($admin, $plan));
        $this->assertFalse($policy->update($coach, $plan));
        $this->assertFalse($policy->update($student, $plan));
    }

    public function test_delete_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->delete($admin, $plan));
        $this->assertFalse($policy->delete($coach, $plan));
        $this->assertFalse($policy->delete($student, $plan));
    }

    public function test_publish_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->draft()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->publish($admin, $plan));
        $this->assertFalse($policy->publish($coach, $plan));
        $this->assertFalse($policy->publish($student, $plan));
    }

    public function test_archive_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->published()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->archive($admin, $plan));
        $this->assertFalse($policy->archive($coach, $plan));
        $this->assertFalse($policy->archive($student, $plan));
    }

    public function test_unarchive_only_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $plan = MeetingPack::factory()->archived()->create();

        $policy = new MeetingPackPolicy;

        $this->assertTrue($policy->unarchive($admin, $plan));
        $this->assertFalse($policy->unarchive($coach, $plan));
        $this->assertFalse($policy->unarchive($student, $plan));
    }
}
