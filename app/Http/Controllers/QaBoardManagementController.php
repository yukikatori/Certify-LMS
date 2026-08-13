<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\QaBoard\IndexRequest;
use App\UseCases\QaBoard\IndexAction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 質問掲示板（管理者）のコントローラ
 * - admin : 管理者は専用画面から、公開停止中の資格を含む全資格のスレッドを横断的に閲覧、管理者は任意のスレッド・回答を削除できる
 * 
 * 受講生 / コーチによる質問の作成/回答のためのコントローラは分離する（QaBoardController）
 * 
 * アクセス制御(機能群共通)
 * - 受講生は公開済資格すべてのスレッドを閲覧・投稿できる
 * - コーチは担当資格のスレッドのみ閲覧・回答でき、担当外の資格は操作できない
 * - 公開停止中の資格のスレッドは受講生・コーチには見えない(管理者は閲覧できる)
 * - 受講中の受講生・コーチのみアクセスできる
 */

class QaBoardManagementController extends Controller
{
    /**
     * 質問掲示板の一覧表示、フィルタ/ページネーションあり (管理者)
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
            'certifications' => $action->certifications($request->user()), 
            'publishedStatus' => $action->publishedStatus($request->user()),
        ]);
    }
}
