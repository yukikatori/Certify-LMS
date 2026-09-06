<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MeetingPack;
use Illuminate\View\View;

/**
 * 受講生用の追加面談購入 Controller。
 */
class MeetingQuotaController extends Controller
{
    public function checkout(): View
    {
        // Blade の既存変数名に合わせ、公開中の MeetingPack を plans として渡す。
        $meetingPacks = MeetingPack::published()->ordered()->get();

        return view('meeting-quota.checkout-select', [
            'plans' => $meetingPacks,
        ]);
    }
}
