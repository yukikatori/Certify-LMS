<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Models\QaThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 質問掲示板の質問更新リクエスト。投稿者がタイトル、内容を更新できる。
 * `status`は解決状態遷移用のエンドポイント(resolve / umresolve)から別途行う。
 */

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('thread')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
