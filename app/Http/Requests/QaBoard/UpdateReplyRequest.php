<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Models\QaReply;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 質問掲示板の質問への回答の更新リクエスト。投稿者が内容を更新する。
 */

class UpdateReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reply = QaReply::find($this->route('reply'));
        return $this->user()?->can('update', $reply) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '本文',
        ];
    }
}
