<?php

declare(strict_types=1);

namespace App\Http\Requests\AiChat;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gemini AI チャットボット会話の新規作成リクエスト。
 */
class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'source' => ['required', 'string', Rule::in(['full-screen', 'widget'])],
            'message' => ['nullable', 'string', 'max:2000'],
            'section_id' => ['nullable', 'ulid', 'exists:sections,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'message' => 'メッセージ',
            'section_id' => 'セクション',
        ];
    }
}
