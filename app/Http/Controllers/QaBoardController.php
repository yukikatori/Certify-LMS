<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\PublishedStatus;
use App\Http\Requests\QaBoard\IndexRequest;
use App\UseCases\QaBoard\IndexAction;
use Illuminate\View\View;

/**
 * 質問掲示板（受講生 / コーチ）のコントローラ
 * - student: 公開済資格すべての掲示板に質問を投稿し、コーチや他受講生から回答を得る。また、他受講生の質問・回答を閲覧する。
 * - coach: 担当資格のスレッドに対して返信を行う
 * 
 * 管理者モデレーションのためのコントローラは分離する（QaBoardManagementController）
 * 
 * アクセス制御(機能群共通)
 * - 受講生は公開済資格すべてのスレッドを閲覧・投稿できる
 * - コーチは担当資格のスレッドのみ閲覧・回答でき、担当外の資格は操作できない
 * - 公開停止中の資格のスレッドは受講生・コーチには見えない(管理者は閲覧できる)
 * - 受講中の受講生・コーチのみアクセスできる
 */

class QaBoardController extends Controller
{
    /**
     * 質問掲示板の一覧表示、フィルタ/ページネーションあり
     */
    public function index(IndexRequest $request, IndexAction $action): View 
    {
        $validated = $request->validated();

        $threads = $action(
            viewer: $request->user(),
            keyword: $validated['keyword'] ?? null,
            status: $validated['status'] ?? null,
            certificationId: $validated['certification_id'] ?? null,
        );

        return view('qa-thread.index', [
            'threads' => $threads,
            'filters' => [
                'keyword' => $validated['keyword'] ?? '',
                'status' => $validated['status'] ?? '',
                'certification_id' => $validated['certification_id'] ?? '',
            ],
            'certifications' => $action->certifications(), 
            'publishedStatus' => \App\Enums\CertificationStatus::Published,
        ]);
    }
}
