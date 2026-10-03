<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Certification;
use App\Models\CoachAvailability;
use App\Models\Enrollment;
use App\Models\User;
use App\UseCases\Meeting\StoreAction;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_database_notification_to_student_and_coach_when_meeting_is_reserved(): void
    {
        $student = User::factory()->student()->inProgress()->create(['max_meetings' => 3]);
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->inProgress()->create([
            'meeting_url' => 'https://meet.example.com/coach-room',
        ]);
        $certification = Certification::factory()->published()->create();
        $this->attachCoach($certification, $coach, $admin);
        CoachAvailability::factory()->forCoach($coach)->onDay(1)->timeRange('09:00:00', '18:00:00')->create();

        $enrollment = Enrollment::factory()->for($student, 'user')->for($certification)->learning()->create();
        $scheduledAt = now()->startOfDay()->next(Carbon::MONDAY)->setTime(10, 0); // 次の月曜 10:00(未来)

        $meeting = app(StoreAction::class)($student, $enrollment, [
            'scheduled_at' => $scheduledAt->toDateTimeString(),
            'topic' => '学習計画について相談したい',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $student->getMorphClass(),
            'notifiable_id' => $student->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $coach->getMorphClass(),
            'notifiable_id' => $coach->id,
        ]);

        $studentNotification = $student->notifications()->first();
        $coachNotification = $coach->notifications()->first();

        $this->assertNotNull($studentNotification);
        $this->assertNotNull($coachNotification);

        foreach ([$studentNotification, $coachNotification] as $notification) {
            $this->assertSame('meeting_reserved', $notification->data['notification_type']);
            $this->assertSame('meeting', $notification->data['related_type']);
            $this->assertSame((string) $meeting->id, $notification->data['related_id']);
            $this->assertSame(route('meetings.show', $meeting), $notification->data['action_url']);
        }
    }

    private function attachCoach(Certification $certification, User $coach, User $admin): void
    {
        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'unassigned_at' => null,
        ]);
    }
}
