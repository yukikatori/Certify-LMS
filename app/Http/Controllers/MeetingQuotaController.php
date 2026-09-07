<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MeetingPack;
use App\Models\Payment;
use App\Http\Requests\MeetingQuota\StoreRequest;
use App\UseCases\MeetingQuota\StoreAction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 受講生用の追加面談購入 Controller。
 */
class MeetingQuotaController extends Controller
{
    public function checkout(): View
    {
        $plans = MeetingPack::published()->ordered()->get();

        return view('meeting-quota.checkout-select', [
            'plans' => $plans,
        ]);
    }

    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $checkoutUrl = $action($request->user(), $request->validated('meeting_pack_id'));

        return redirect()->away($checkoutUrl);
    }

    public function success(Request $request): View
    {
        $payment = Payment::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $request->query('payment'))
            ->first();

        return view('meeting-quota.success', [
            'payment' => $payment,
        ]);
    }
}
