<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1on1 面談予約テーブル。
 *
 * 受講生が時刻スロットを選んだ瞬間、過去 30 日の実施数が最少の担当コーチを自動割当し
 * `status=reserved` で即時確定する(コーチによる承認フローはない)。
 * scheduled_at は開始時刻のみ保持し、終了時刻は常に `scheduled_at + 60 分` とする運用(NFR で 60 分固定)。
 *
 * (coach_id, scheduled_at) UNIQUE で同コーチ × 同時刻の二重予約に制約を与えると予約キャンセル時に再度予約ができなくなる。
 * active_coach_idカラムを追加し、予約ステータスが'reserved', 'completed'の時にcoach_idと同じ値を入れる。
 * (active_coach_id, scheduled_at) UNIQUEとしてキャンセル時は制約がかからない設計とする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->char('active_coach_id', 26)
                ->nullable()
                ->storedAs(
                    "CASE
                        WHEN status IN ('reserved', 'completed')
                        THEN coach_id
                        ELSE NULL
                    END"
                );

            $table->unique(
                ['active_coach_id', 'scheduled_at'],
                'meetings_active_coach_slot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropUnique('meetings_active_coach_slot_unique');
            $table->dropColumn('active_coach_id');
        });
    }
};
