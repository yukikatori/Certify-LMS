<?php

declare(strict_types=1);

namespace App\UseCases\MeetingQuota;

use App\Enums\MeetingQuotaTransactionType;
use App\Enums\PaymentStatus;
use App\Models\MeetingQuotaTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Stripe\Event;

/**
 * 追加面談の残面談回数を加算するユースケース。
 * 決済サービスからの通知が重複して届いても、残数の会計が崩れないように `quota_granted_at` で検証する。
 */
final class HandleCheckoutCompletedAction
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

            // 残数加算前の整合性チェック
            if ($payment === null) {
                return;
            }

            if (($session->payment_status ?? null) !== 'paid') {
                return;
            }

            if (
                $payment->stripe_checkout_session_id !== null
                && $payment->stripe_checkout_session_id !== $session->id
            ) {
                return;
            }

            if ($payment->stripe_event_id === $event->id || $payment->quota_granted_at !== null) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $session->payment_intent ?? null,
                'stripe_event_id' => $event->id,
                'paid_at' => now(),
            ]);

            MeetingQuotaTransaction::create([
                'user_id' => $payment->user_id,
                'type' => MeetingQuotaTransactionType::Purchased,
                'amount' => $payment->quantity,
                'related_payment_id' => $payment->id,
                'note' => 'Stripe Checkout による追加面談購入',
                'occurred_at' => now(),
            ]);

            $payment->update([
                'quota_granted_at' => now(),
            ]);
        });
    }
}