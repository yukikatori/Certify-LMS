<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\QaThread;

/**
 * 質問掲示板（受講生 / コーチ）一覧の絞り込みリクエスト
 */
class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', QaThread::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>
     */
    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:resolved,unresolved'],
            'certification_id' => ['nullable', 'ulid', 'exists:certifications,id'],
        ];
    }
}
