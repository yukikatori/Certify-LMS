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
        $paymentIntentId = $session->payment_intent ?? null;

        if (
            ! is_string($paymentId)
            || $paymentId === ''
            || ! is_string($paymentIntentId)
            || $paymentIntentId === ''
        ) {
            return;
        }

        DB::transaction(function () use ($session, $paymentId, $paymentIntentId): void {
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

            if (
                $payment->quota_granted_at !== null
                || (
                    $payment->stripe_payment_intent_id !== null
                    && $payment->stripe_payment_intent_id !== $paymentIntentId
                )
            ) {
                return;
            }

            $alreadyHandled = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->whereKeyNot($payment->id)
                ->exists();

            if ($alreadyHandled) {
                return;
            }

            $payment->update([
                'status' => PaymentStatus::Succeeded,
                'stripe_checkout_session_id' => $session->id,
                'stripe_payment_intent_id' => $paymentIntentId,
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
