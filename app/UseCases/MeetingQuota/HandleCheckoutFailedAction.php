<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Stripe\Event;

/**
 * 追加面談の残面談回数を加算するユースケース。
 * checkout.session.expired を受けたときに status を Failed にする。
 */
final class HandleCheckoutFailedAction
{
    public function __invoke(Event $event): void
    {
        $session = $event->data->object;

        $paymentId = $session->metadata->payment_id ?? null;

        if (! is_string($paymentId) || $paymentId === '') {
            return;
        }

        DB::transaction(function () use ($event, $session, $paymentId): void {
            $payment = Payment::query()
                ->where('id', $paymentId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return;
            }

            // すでに成功済み、または残数加算済みなら失敗に戻さない
            if (
                $payment->status === PaymentStatus::Succeeded
                || $payment->quota_granted_at !== null
            ) {
                return;
            }

            // 別の Checkout Session のイベントなら触らない
            if (
                $payment->stripe_checkout_session_id !== null
                && $payment->stripe_checkout_session_id !== $session->id
            ) {
                return;
            }

            // 同じイベントを再受信しただけなら何もしない
            if ($payment->stripe_event_id === $event->id) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'stripe_checkout_session_id' => $session->id,
                'stripe_event_id' => $event->id,
                'failed_at' => now(),
            ]);
        });
    }
}