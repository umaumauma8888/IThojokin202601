<?php

namespace App\Http\Controllers;

use App\Http\Requests\DunningRequest;
use App\Models\Invoice;
use App\Models\DunningRecord;
use App\Models\DunningSchedule;
use App\Services\DunningService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DunningController extends Controller
{
    public function __construct(
        private readonly DunningService $dunningService
    ) {}

    /**
     * 督促管理一覧
     */
    public function index(Request $request): View
    {
        $overdueInvoices = Invoice::with(['customer', 'dunningRecords' => fn($q) => $q->latest()])
            ->whereIn('collection_status', ['overdue', 'dunning'])
            ->where('receivable_balance', '>', 0)
            ->orderByDesc('overdue_days')
            ->get();

        $schedules = DunningSchedule::where('is_active', true)->orderBy('days_after_due')->get();

        return view('payments.dunning.index', compact('overdueInvoices', 'schedules'));
    }

    /**
     * 個別督促の手動実行
     */
    public function store(DunningRequest $request, Invoice $invoice): JsonResponse
    {
        $record = $this->dunningService->sendDunning($invoice, $request->validated());

        return response()->json([
            'success' => true,
            'message' => '督促を送信しました。',
            'record'  => [
                'id'           => $record->id,
                'dunning_type' => $record->dunning_type,
                'method'       => $record->method,
                'dunning_date' => $record->dunning_date,
            ],
        ]);
    }

    /**
     * 督促結果の更新
     */
    public function updateResult(Request $request, DunningRecord $dunningRecord): JsonResponse
    {
        $request->validate([
            'result'   => 'required|in:pending,promised,paid,disputed,uncollectible',
            'response' => 'nullable|string|max:1000',
        ]);

        $this->dunningService->updateDunningResult(
            $dunningRecord,
            $request->result,
            $request->response ?? ''
        );

        return response()->json([
            'success' => true,
            'message' => '督促結果を更新しました。',
        ]);
    }

    /**
     * 督促スケジュール設定
     */
    public function schedules(): View
    {
        $schedules = DunningSchedule::orderBy('days_after_due')->get();
        return view('payments.dunning.schedules', compact('schedules'));
    }

    /**
     * 督促スケジュールの保存
     */
    public function saveSchedule(Request $request): JsonResponse
    {
        $request->validate([
            'name'           => 'required|string|max:100',
            'days_after_due' => 'required|integer|min:1|max:365',
            'dunning_type'   => 'required|in:first,second,final',
            'method'         => 'required|in:email,phone,mail',
        ]);

        DunningSchedule::updateOrCreate(
            ['dunning_type' => $request->dunning_type],
            $request->only(['name', 'days_after_due', 'method', 'is_active'])
        );

        return response()->json(['success' => true, 'message' => '督促スケジュールを保存しました。']);
    }
}
