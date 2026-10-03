<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentGoal;

use App\Models\EnrollmentGoal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 個人目標を更新するユースケース。`status` は本 Action では更新せず、状態遷移用 Action に責務分離する。
 */
final class UpdateAction
{
    /**
     * @param array{
     *     title: string,
     *     target_date: string,
     *     description?: ?string
     * } $validated
     */
    public function __invoke(EnrollmentGoal $goal, User $user, array $validated): EnrollmentGoal
    {
        return DB::transaction(function () use ($goal, $user, $validated) {
            $goal->update([
                'title' => $validated['title'],
                'target_date' => $validated['target_date'],
                'description' => $validated['description'] ?? null,
            ]);

            return $goal->fresh();
        });
    }
}