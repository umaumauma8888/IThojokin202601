<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentReceiptRequest;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\ReceivableMatching;
use App\Services\PaymentMatchingService;
use App\Services\DunningService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PaymentReceiptController extends Controller
{
    public function __construct(
        private readonly PaymentMatchingService $matchingService,
        private readonly DunningService         $dunningService
    ) {}

    /**
     * 入金消込ダッシュボード
     */
    public function index(Request $request): View
    {
        $summary = $this->matchingService->getReceivableSummary();

        $invoices = Invoice::with(['customer', 'paymentReceipts', 'dunningRecords'])
            ->where('receivable_balance', '>', 0)
            ->when($request->status, fn($q, $s) => $q->where('collection_status', $s))
            ->when($request->customer_id, fn($q, $c) => $q->where('customer_id', $c))
            ->when($request->overdue_only, fn($q) => $q->where('collection_status', 'overdue'))
            ->orderByDesc('overdue_days')
            ->orderByDesc('receivable_balance')
            ->paginate(20);

        $overdueList = $this->dunningService->getOverdueList();

        return view('payments.index', compact('summary', 'invoices', 'overdueList'));
    }

    /**
     * 入金登録フォーム
     */
    public function create(Invoice $invoice): View
    {
        $invoice->load(['customer', 'paymentReceipts.receivableMatchings']);
        return view('payments.create', compact('invoice'));
    }

    /**
     * 入金登録・消込実行
     */
    public function store(PaymentReceiptRequest $request): JsonResponse
    {
        $receipt = $this->matchingService->recordAndMatch($request->validated());
        $invoice = Invoice::find($request->invoice_id);

        return response()->json([
            'success'          => true,
            'message'          => '入金を登録し、売掛金を消込しました。',
            'receipt_id'       => $receipt->id,
            'new_balance'      => $invoice->fresh()->receivable_balance,
            'collection_status' => $invoice->fresh()->collection_status,
        ]);
    }

    /**
     * 入金詳細
     */
    public function show(PaymentReceipt $paymentReceipt): View
    {
        $paymentReceipt->load(['invoice.customer', 'receivableMatchings', 'recorder']);
        return view('payments.show', compact('paymentReceipt'));
    }

    /**
     * 消込の取り消し
     */
    public function reverse(ReceivableMatching $matching): JsonResponse
    {
        $this->authorize('manage-payments');
        $this->matchingService->reverseMatching($matching);

        return response()->json([
            'success' => true,
            'message' => '消込を取り消しました。',
        ]);
    }

    /**
     * 売掛残高サマリー API（ダッシュボードウィジェット用）
     */
    public function summary(): JsonResponse
    {
        return response()->json($this->matchingService->getReceivableSummary());
    }

    /**
     * 期日超過リスト API
     */
    public function overdueList(Request $request): JsonResponse
    {
        $minDays = $request->integer('min_days', 0);
        return response()->json($this->dunningService->getOverdueList($minDays));
    }

    /**
     * 月次回収レポート API
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $year  = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        return response()->json(
            $this->dunningService->generateMonthlyReport($year, $month)
        );
    }
}
