<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\QaThread;

/**
 * 質問掲示板の認可ルール
 * - 受講生は公開済資格すべてのスレッドを閲覧・投稿できる
 * - コーチは担当資格のスレッドのみ閲覧・回答でき、担当外の資格は操作できない
 * - 公開停止中の資格のスレッドは受講生・コーチには見えない(管理者は閲覧できる)
 * - 受講中の受講生・コーチのみアクセスできる
 * - 管理者は専用画面から、公開停止中の資格を含む全資格のスレッドを横断的に閲覧できる
 */

class QaBoardPolicy
{
    /**
     * 質問掲示板の一覧表示、受講生 / コーチがアクセス可
     */
    public function viewAny(User $auth): bool
    {
        return in_array($auth->role, [UserRole::Student, UserRole::Coach], true);
    }
}
