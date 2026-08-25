<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingPackStatus;
use App\Http\Requests\MeetingPack\IndexRequest;
use App\Http\Requests\MeetingPack\StoreRequest;
use App\Http\Requests\MeetingPack\UpdateRequest;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\ArchiveAction;
use App\UseCases\MeetingPack\UnarchiveAction;
use App\UseCases\MeetingPack\IndexAction;
use App\UseCases\MeetingPack\DestroyAction;
use App\UseCases\MeetingPack\PublishAction;
use App\UseCases\MeetingPack\ShowAction;
use App\UseCases\MeetingPack\StoreAction;
use App\UseCases\MeetingPack\UpdateAction;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * admin 用の面談パックマスタ管理画面 Controller。CRUD と公開状態遷移（publish / unpublish / archive）を提供する。
 */

class MeetingPackController extends Controller
{
    /**
     * 一覧表示(パック名のキーワード検索 + 状態フィルタ + ページネーション)
     */
    public function index(IndexRequest $request, IndexAction $action): View
    {
        $validated = $request->validated();

        $plans = $action(
            viewer: $request->user(),
            keyword: $validated['keyword'] ?? null,
            status: isset($validated['status']) ? MeetingPackStatus::from($validated['status']) : null,
        );

        return view('meeting-pack.management.index', [
            'plans' => $plans,
            'keyword' => $validated['keyword'] ?? '',
            'status' => $validated['status'] ?? '',
        ]);
    }

    /**
     * 面談パック詳細ページ表示
     */
    public function show(MeetingPack $plan, ShowAction $action): View
    {
        $this->authorize('view', $plan);

        return view('meeting-pack.management.show', [
            'plan' => $action($plan),
        ]);
    }

    /**
     * 新規作成フォーム表示
     */
    public function create(): View
    {
        $this->authorize('create', MeetingPack::class);

        return view('meeting-pack.management.create');
    }

    /**
     * 新規面談パック作成
     */
    public function store(StoreRequest $request, StoreAction $action): RedirectResponse
    {
        $plan = $action($request->user(), $request->validated());

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを作成しました。');
    }

    /**
     * 編集フォーム表示
     */
    public function edit(MeetingPack $plan): View
    {
        $this->authorize('update', $plan);

        return view('meeting-pack.management.edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * 面談パック編集
     */
    public function update(MeetingPack $plan, UpdateRequest $request, UpdateAction $action): RedirectResponse
    {
        $action($plan, $request->user(), $request->validated());

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを更新しました。');
    }

    /**
     * 面談パック削除
     */
    public function destroy(MeetingPack $plan, DestroyAction $action): RedirectResponse
    {
        $this->authorize('delete', $plan);

        $action($plan);

        return redirect()
            ->route('admin.meeting-packs.index')
            ->with('success', '面談パックを削除しました。');
    }

    /**
     * 面談パックを公開中に遷移
     */
    public function publish(MeetingPack $plan, PublishAction $action): RedirectResponse
    {
        $this->authorize('publish', $plan);

        $action($plan, request()->user());

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックを公開しました。');
    }

    /**
     * 面談パックを下書きに遷移
     */
    public function unarchive(MeetingPack $plan, UnarchiveAction $action): RedirectResponse
    {
        $this->authorize('unarchive', $plan);

        $action($plan, request()->user());

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックの公開を停止しました。');
    }

    /**
     * 面談パックをアーカイブに遷移
     */
    public function archive(MeetingPack $plan, ArchiveAction $action): RedirectResponse
    {
        $this->authorize('archive', $plan);

        $action($plan, request()->user());

        return redirect()
            ->route('admin.meeting-packs.show', $plan)
            ->with('success', '面談パックをアーカイブしました。');
    }
}
