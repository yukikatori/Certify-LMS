<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * adminh用の面談パックマスタ一覧をフィルタ付きで取得するユースケース。
 * 公開中 → 下書き → アーカイブ の順で並び、同 status 内は最終更新の降順。
 */

final class IndexAction
{
    public function __invoke(
        User $viewer,
        ?string $keyword,
        ?MeetingPackStatus $status,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = MeetingPack::query()
            ->forUser($viewer);
        
        if ($keyword !== null) {
            $query->where('name', 'LIKE', '%' .$keyword. '%');
        }

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

}