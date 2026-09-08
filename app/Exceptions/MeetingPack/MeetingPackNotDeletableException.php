<?php

declare(strict_types=1);

namespace App\Exceptions\MeetingPack;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 削除条件を満たさない面談パックを削除しようとした際の例外（HTTP 409）。
 * `MeetingPack\DestroyAction` が「下書き状態のみ削除可」のドメインルールから throw する。
 */
class MeetingPackNotDeletableException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('下書き状態の面談パックのみ削除できます。', $previous);
    }
}
