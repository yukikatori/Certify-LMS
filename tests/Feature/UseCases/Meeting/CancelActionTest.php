<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Meeting;

use App\Models\Enrollment;
use App\Models\Meeting;
use App\Models\User;
use App\UseCases\Meeting\CancelAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_database_notification_to_other_party_when_meeting_is_canceled(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $coach = User::factory()->coach()->inProgress()->create();
        $enrollment = Enrollment::factory()->for($student, 'user')->learning()->create();
        $meeting = Meeting::factory()
            ->reserved()
            ->forEnrollment($enrollment)
            ->forCoach($coach)
            ->create([
                'scheduled_at' => now()->addDays(3)->startOfHour(),
                'topic' => '学習計画について相談したい',
            ]);

        app(CancelAction::class)($student, $meeting);

        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => $student->getMorphClass(),
            'notifiable_id' => $student->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $coach->getMorphClass(),
            'notifiable_id' => $coach->id,
        ]);

        $notification = $coach->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame('meeting_canceled', $notification->data['notification_type']);
        $this->assertSame('meeting', $notification->data['related_type']);
        $this->assertSame((string) $meeting->id, $notification->data['related_id']);
        $this->assertSame(route('meetings.show', $meeting), $notification->data['action_url']);
    }
}
