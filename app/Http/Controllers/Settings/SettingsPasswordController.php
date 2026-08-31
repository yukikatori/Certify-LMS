<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PasswordUpdateRequest;
use App\UseCases\Settings\PasswordUpdateAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * パスワード変更(全ロール)のController
 */
class SettingsPasswordController extends Controller
{
    public function update(PasswordUpdateRequest $request, PasswordUpdateAction $action): RedirectResponse
    {
        $action($request->user(), $request->validated());

        return redirect()
            ->route('settings.profile.edit', ['tab' => 'password'])
            ->with('success', 'パスワードを更新しました。');
    }
}
