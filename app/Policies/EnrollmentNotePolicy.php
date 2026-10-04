<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

/**
 * 受講生メモの管理の認可ルール。
 * メモの閲覧 / 追加 / 編集 / 削除はコーチ(担当資格)と管理者のみ。受講生は閲覧含めすべて拒否
 * 担当していない資格の受講登録に対するコーチのメモ操作は拒否
 */
class EnrollmentNotePolicy
{
    public function viewAny(User $auth, Enrollment $enrollment): bool
    {
        $enrollment->loadMissing('certification.coaches');

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $enrollment->certification?->coaches->contains('id', $auth->id) ?? false,
            UserRole::Student => false,
        };
    }

    public function create(User $auth, Enrollment $enrollment): bool
    {
        $enrollment->loadMissing('certification.coaches');

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $enrollment->certification?->coaches->contains('id', $auth->id) ?? false,
            UserRole::Student => false,
        };
    }

    public function update(User $auth, EnrollmentNote $note): bool
    {
        $enrollment = $note->enrollment;
        if (! $enrollment instanceof Enrollment) {
            return false;
        }

        $enrollment->loadMissing('certification.coaches');

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $enrollment->certification?->coaches->contains('id', $auth->id)
                && $note->user_id === $auth->id,
            UserRole::Student => false,
        };
    }

    public function delete(User $auth, EnrollmentNote $note): bool
    {
        $enrollment = $note->enrollment;
        if (! $enrollment instanceof Enrollment) {
            return false;
        }

        $enrollment->loadMissing('certification.coaches');

        return match ($auth->role) {
            UserRole::Admin => true,
            UserRole::Coach => $enrollment->certification?->coaches->contains('id', $auth->id)
                && $note->user_id === $auth->id,
            UserRole::Student => false,
        };
    }
}
