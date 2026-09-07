<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;

/**
 * 面談パックマスタの認可ルール。admin は全件 CRUD 
 * purchaseは学習中の受講生かつ、公開済み面談パックのみ可。
 */
class MeetingPackPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function view(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function create(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function update(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function delete(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function publish(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function archive(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function unarchive(User $auth, MeetingPack $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function purchase(User $user, MeetingPack $plan): bool
    {
        return $user->role === UserRole::Student
            && $user->status === UserStatus::InProgress
            && $plan->status === MeetingPackStatus::Published;
    }
}
