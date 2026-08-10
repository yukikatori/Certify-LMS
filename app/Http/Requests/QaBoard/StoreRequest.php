<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use App\Models\QaThread;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 質問掲示板の質問新規作成リクエスト。受講生が資格名、タイトル、内容を入力する。
 */

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QaThread::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_id' => ['required', 'ulid', 'exists:certifications,id'],
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
            'certification_id' => '資格',
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
