<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;

/**
 * 質問掲示板の一覧をフィルタ付きで取得するUseCase。
 * 解決済み → 未解決  の順で並び、同 status 内は最終更新の降順。
 */

final class IndexAction
{
    public function __invoke(
        User $viewer,
        ?string $keyword,
        ?string $status,
        ?string $certificationId,
        int $perPage = 20,
    ) : LengthAwarePaginator {

        // viewerが閲覧可能な資格IDの取得
        $allowedCertIds = match ($viewer->role) {
            UserRole::Student => Certification::published()->pluck('id'),
            UserRole::Coach   => $viewer->assignedCertifications()
                                    ->published()
                                    ->pluck('certifications.id'),
            default           => collect(),
        };

        $query = QaThread::query()
            ->with('certification')
            ->withCount('replies')
            ->whereIn('certification_id', $allowedCertIds);

        // フィルタ処理
        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('body', 'like', "%{$keyword}%");
            });
        }

        if ($status === QaThreadStatus::Resolved->value) {
            $query->where('status', QaThreadStatus::Resolved->value);
        } elseif ($status === QaThreadStatus::Unresolved->value) {
            $query->where('status', QaThreadStatus::Unresolved->value);
        }

        if ($certificationId !== null && $certificationId !== '') {
            $query->where('certification_id', $certificationId);
        }

        return $query
            ->orderByDesc('status')
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function certifications(): Collection
    {
        return Certification::published()->get(['id', 'name', 'status']);
    }
}