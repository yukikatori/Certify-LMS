<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 面談パックの公開を停止（archived → draft）するユースケース。
 * 公開中以外の状態からの呼出は MeetingPackInvalidTransitionException（409）。
 */
final class UnarchiveAction
{
    /**
     * @throws MeetingPackInvalidTransitionException 公開中以外からの呼出
     */
    public function __invoke(MeetingPack $plan, User $admin): MeetingPack
    {
        if ($plan->status !== MeetingPackStatus::Archived) {
            throw MeetingPackInvalidTransitionException::forUnarchive();
        }

        return DB::transaction(function () use ($plan, $admin) {
            $plan->update([
                'status' => MeetingPackStatus::Draft->value,
                'updated_by_user_id' => $admin->id,
            ]);

            return $plan->fresh();
        });
    }
}