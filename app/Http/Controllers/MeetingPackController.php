<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MeetingPackStatus;
use App\Http\Requests\MeetingPack\IndexRequest;
use App\UseCases\MeetingPack\IndexAction;
use Illuminate\Http\Request;
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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
