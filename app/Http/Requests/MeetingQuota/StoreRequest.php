<?php

declare(strict_types=1);

namespace App\Http\Requests\MeetingQuota;

use Illuminate\Foundation\Http\FormRequest;

/**
 * 追加面談の支払い作成リクエスト。受講生が面談パックIDを入力する。
 */
class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'meeting_pack_id' => ['required', 'ulid', 'exists:meeting_packs,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'meeting_pack_id' => '追加面談',
        ];
    }
}
