@extends('layouts.app')

@section('title', '督促管理')

@section('content')
<div class="px-6 py-4">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">督促管理</h1>
            <p class="text-sm text-gray-500 mt-1">期日超過の売掛金に対する督促アクションを管理します</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('payments.dunning.schedules') }}"
               class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                スケジュール設定
            </a>
            <a href="{{ route('payments.index') }}"
               class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                ← 入金消込に戻る
            </a>
        </div>
    </div>

    {{-- 統計バー --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-xs text-red-500 mb-1">期日超過（未督促）</p>
            <p class="text-2xl font-semibold text-red-600">
                {{ $overdueInvoices->where('collection_status', 'overdue')->count() }}件
            </p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
            <p class="text-xs text-orange-500 mb-1">督促中</p>
            <p class="text-2xl font-semibold text-orange-600">
                {{ $overdueInvoices->where('collection_status', 'dunning')->count() }}件
            </p>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">自動督促スケジュール</p>
            <p class="text-2xl font-semibold text-gray-700">
                {{ $schedules->count() }}件設定済
            </p>
            <p class="text-xs text-gray-400 mt-1">毎朝07:00 自動実行</p>
        </div>
    </div>

    {{-- 期日超過一覧 --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-700">期日超過一覧（未入金）</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">顧客名</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">請求書番号</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">売掛残高</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">入金期限</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">超過日数</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">督促履歴</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">最終督促</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($overdueInvoices as $invoice)
                    @php
                        $latestDunning = $invoice->dunningRecords->first();
                        $urgencyClass = match(true) {
                            $invoice->overdue_days >= 60 => 'bg-red-50',
                            $invoice->overdue_days >= 30 => 'bg-orange-50',
                            default => '',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50 transition {{ $urgencyClass }}">
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $invoice->customer->company_name }}
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}"
                               class="text-blue-600 hover:underline font-mono text-xs">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">
                            ¥{{ number_format($invoice->receivable_balance) }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $invoice->due_date }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold {{ $invoice->overdue_days >= 60 ? 'text-red-600' : 'text-orange-500' }}">
                                {{ $invoice->overdue_days }}日
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @forelse($invoice->dunningRecords->take(3) as $dr)
                                <span class="inline-block px-1.5 py-0.5 text-xs rounded
                                    {{ $dr->dunning_type === 'first' ? 'bg-yellow-100 text-yellow-700' :
                                      ($dr->dunning_type === 'second' ? 'bg-orange-100 text-orange-700' :
                                      'bg-red-100 text-red-700') }}">
                                    {{ $dr->dunning_type_label }}
                                </span>
                            @empty
                                <span class="text-gray-400 text-xs">未督促</span>
                            @endforelse
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">
                            {{ $invoice->last_dunning_at?->format('m/d') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                {{-- 次の督促種別を自動判定 --}}
                                @php
                                    $nextType = match($invoice->dunningRecords->last()?->dunning_type) {
                                        'first'  => 'second',
                                        'second' => 'final',
                                        'final'  => 'legal',
                                        default  => 'first',
                                    };
                                    $typeLabels = ['first' => '初回督促', 'second' => '二次督促', 'final' => '最終督促', 'legal' => '法的措置'];
                                @endphp
                                <button onclick="sendDunning({{ $invoice->id }}, '{{ $nextType }}', '{{ $invoice->customer->company_name }}')"
                                        class="text-xs px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded hover:bg-orange-100 transition">
                                    {{ $typeLabels[$nextType] }}送信
                                </button>
                                <button onclick="openResultModal({{ $invoice->dunningRecords->last()?->id }})"
                                        class="text-xs px-3 py-1 bg-gray-50 text-gray-600 border border-gray-200 rounded hover:bg-gray-100 transition
                                        {{ !$invoice->dunningRecords->count() ? 'opacity-50 cursor-not-allowed' : '' }}"
                                        {{ !$invoice->dunningRecords->count() ? 'disabled' : '' }}>
                                    結果記録
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-gray-400 text-sm">
                            期日超過の請求書はありません
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- 督促結果記録モーダル --}}
<div id="result-modal" class="fixed inset-0 bg-black bg-opacity-30 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-gray-800">督促結果の記録</h3>
            <button onclick="closeResultModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="result-form" onsubmit="submitResult(event)">
            @csrf
            <input type="hidden" id="dunning-record-id">

            <div class="mb-4">
                <label class="text-xs font-medium text-gray-600 block mb-1">結果 <span class="text-red-500">*</span></label>
                <select name="result" id="result-select"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none">
                    <option value="pending">対応中</option>
                    <option value="promised">支払約束あり</option>
                    <option value="paid">入金確認済</option>
                    <option value="disputed">支払拒否</option>
                    <option value="uncollectible">回収不能</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="text-xs font-medium text-gray-600 block mb-1">相手先の回答・メモ</label>
                <textarea name="response" rows="3"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                          placeholder="電話で確認。〇月〇日までに振込予定とのこと。"></textarea>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="closeResultModal()"
                        class="flex-1 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">
                    キャンセル
                </button>
                <button type="submit"
                        class="flex-1 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                    保存
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
async function sendDunning(invoiceId, dunningType, customerName) {
    const typeLabels = { first: '初回督促', second: '二次督促', final: '最終督促', legal: '法的措置' };
    if (!confirm(`${customerName}に${typeLabels[dunningType]}メールを送信しますか？`)) return;

    const res = await fetch(`/payments/dunning/invoices/${invoiceId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ dunning_type: dunningType, method: 'email' }),
    });

    const result = await res.json();
    if (result.success) {
        alert('督促メールを送信しました。');
        location.reload();
    } else {
        alert('エラー: ' + (result.message || '送信に失敗しました'));
    }
}

let currentDunningRecordId = null;

function openResultModal(recordId) {
    if (!recordId) return;
    currentDunningRecordId = recordId;
    document.getElementById('result-modal').classList.remove('hidden');
}

function closeResultModal() {
    document.getElementById('result-modal').classList.add('hidden');
}

async function submitResult(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    const res = await fetch(`/payments/dunning/${currentDunningRecordId}/result`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token,
        },
        body: JSON.stringify({ result: data.result, response: data.response }),
    });

    const result = await res.json();
    if (result.success) {
        closeResultModal();
        location.reload();
    }
}
</script>
@endpush
