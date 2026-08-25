<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use Illuminate\Support\Facades\DB;

/**
 * 面談パックを削除するユースケース。下書き状態の面談パックのみ削除可能。
 */
final class DestroyAction
{
    /**
     * @throws MeetingPackNotDeletableException 下書き状態以外の資格は削除不可
     */
    public function __invoke(MeetingPack $plan): void
    {
        if ($plan->status !== MeetingPackStatus::Draft) {
            throw new MeetingPackNotDeletableException;
        }

        DB::transaction(fn () => $plan->delete());
    }
}