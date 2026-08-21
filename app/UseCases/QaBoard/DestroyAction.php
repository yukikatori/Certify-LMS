<?php

declare(strict_types=1);

namespace App\UseCases\QaBoard;

use App\Models\QaThread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 質問掲示板の質問を削除するユースケース。
 */
final class DestroyAction
{
    public function __invoke(QaThread $thread): void
    {
        DB::transaction(fn () => $thread->delete());
    }
}