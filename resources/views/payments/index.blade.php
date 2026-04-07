@extends('layouts.app')

@section('title', '入金消込・債権管理')

@section('content')
<div class="px-6 py-4">

    {{-- ページヘッダー --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">入金消込・債権管理</h1>
            <p class="text-sm text-gray-500 mt-1">商品売買に伴う売掛金の消込・督促を一元管理します</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('payments.dunning.index') }}"
               class="px-4 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                督促管理
            </a>
            <button onclick="openReceiptModal()"
                    class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                + 入金登録
            </button>
        </div>
    </div>

    {{-- サマリーカード --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">売掛残高合計</p>
            <p class="text-2xl font-semibold text-gray-800">
                ¥{{ number_format($summary['total_balance']) }}
            </p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">期日超過残高</p>
            <p class="text-2xl font-semibold text-red-600">
                ¥{{ number_format($summary['overdue_balance']) }}
            </p>
            <p class="text-xs text-red-500 mt-1">{{ $summary['overdue_count'] }}件超過中</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">督促中残高</p>
            <p class="text-2xl font-semibold text-orange-500">
                ¥{{ number_format($summary['dunning_balance']) }}
            </p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">回収率</p>
            <p class="text-2xl font-semibold text-green-600">{{ $summary['collection_rate'] }}%</p>
            <div class="mt-2 w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $summary['collection_rate'] }}%"></div>
            </div>
        </div>
    </div>

    {{-- 請求書一覧（未入金・一部入金・期日超過）--}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-gray-700">未収入金一覧</h2>
            <div class="flex gap-2">
                <select onchange="filterByStatus(this.value)"
                        class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 text-gray-600">
                    <option value="">すべてのステータス</option>
                    <option value="unpaid">未入金</option>
                    <option value="partial">一部入金</option>
                    <option value="overdue">期日超過</option>
                    <option value="dunning">督促中</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">請求書番号</th>
                        <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">顧客名</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">請求額</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500">入金済</th>
                        <th class="text-right px-4 py-3 text-xs font-medium text-gray-500 text-red-500">売掛残高</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">入金期限</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">超過日数</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">ステータス</th>
                        <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">操作</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition" data-invoice-id="{{ $invoice->id }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}"
                               class="text-blue-600 hover:underline font-mono text-xs">
                                {{ $invoice->invoice_number }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $invoice->customer->company_name }}</td>
                        <td class="px-4 py-3 text-right text-gray-700">
                            ¥{{ number_format($invoice->total_amount) }}
                        </td>
                        <td class="px-4 py-3 text-right text-green-600">
                            ¥{{ number_format($invoice->total_received) }}
                        </td>
                        <td class="px-4 py-3 text-right font-semibold
                            {{ $invoice->overdue_days > 0 ? 'text-red-600' : 'text-gray-800' }}">
                            ¥{{ number_format($invoice->receivable_balance) }}
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $invoice->due_date }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($invoice->overdue_days > 0)
                                <span class="text-red-500 font-medium">{{ $invoice->overdue_days }}日</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusConfig = [
                                    'unpaid'  => ['label' => '未入金',   'class' => 'bg-gray-100 text-gray-600'],
                                    'partial' => ['label' => '一部入金', 'class' => 'bg-blue-100 text-blue-600'],
                                    'overdue' => ['label' => '期日超過', 'class' => 'bg-red-100 text-red-600'],
                                    'dunning' => ['label' => '督促中',   'class' => 'bg-orange-100 text-orange-600'],
                                    'paid'    => ['label' => '入金済',   'class' => 'bg-green-100 text-green-600'],
                                ];
                                $config = $statusConfig[$invoice->collection_status] ?? $statusConfig['unpaid'];
                            @endphp
                            <span class="inline-block px-2 py-0.5 text-xs rounded-full {{ $config['class'] }}">
                                {{ $config['label'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openReceiptModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', {{ $invoice->receivable_balance }})"
                                        class="text-xs px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded hover:bg-green-100 transition">
                                    入金登録
                                </button>
                                @if(in_array($invoice->collection_status, ['overdue', 'dunning']))
                                <button onclick="openDunningModal({{ $invoice->id }}, '{{ $invoice->customer->company_name }}')"
                                        class="text-xs px-3 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded hover:bg-orange-100 transition">
                                    督促
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-gray-400 text-sm">
                            未収入金はありません
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
        <div class="px-4 py-3 border-t border-gray-100">
            {{ $invoices->links() }}
        </div>
        @endif
    </div>

</div>

{{-- 入金登録モーダル --}}
<div id="receipt-modal" class="fixed inset-0 bg-black bg-opacity-30 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-base font-semibold text-gray-800">入金登録・売掛消込</h3>
            <button onclick="closeReceiptModal()" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form id="receipt-form" onsubmit="submitReceipt(event)">
            @csrf
            <input type="hidden" id="receipt-invoice-id" name="invoice_id">

            <div class="mb-4">
                <p class="text-xs text-gray-500 mb-1">対象請求書</p>
                <p id="receipt-invoice-number" class="text-sm font-medium text-gray-700"></p>
                <p class="text-xs text-gray-500 mt-1">売掛残高: <span id="receipt-balance" class="font-semibold text-red-500"></span></p>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3">
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">入金額 <span class="text-red-500">*</span></label>
                    <input type="number" name="received_amount" id="received-amount"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none"
                           placeholder="0" min="1" required>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">入金日 <span class="text-red-500">*</span></label>
                    <input type="date" name="received_date"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none"
                           value="{{ date('Y-m-d') }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="text-xs font-medium text-gray-600 block mb-1">入金方法</label>
                <select name="payment_method"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none">
                    <option value="bank_transfer">銀行振込</option>
                    <option value="credit_card">クレジットカード</option>
                    <option value="cash">現金</option>
                    <option value="other">その他</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="text-xs font-medium text-gray-600 block mb-1">振込元銀行名</label>
                <input type="text" name="bank_name"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none"
                       placeholder="例: 三菱UFJ銀行">
            </div>

            <div class="mb-4">
                <label class="text-xs font-medium text-gray-600 block mb-1">備考</label>
                <textarea name="memo" rows="2"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-300 focus:outline-none"
                          placeholder="備考があれば入力"></textarea>
            </div>

            {{-- 消込プレビュー --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4 text-xs text-green-700">
                <p class="font-medium mb-1">消込後の状態（プレビュー）</p>
                <p>売掛残高: <span id="balance-after" class="font-semibold">-</span></p>
            </div>

            <div class="flex gap-2">
                <button type="button" onclick="closeReceiptModal()"
                        class="flex-1 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                    キャンセル
                </button>
                <button type="submit"
                        class="flex-1 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    入金登録・消込実行
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentBalance = 0;

function openReceiptModal(invoiceId = null, invoiceNumber = null, balance = null) {
    document.getElementById('receipt-invoice-id').value = invoiceId || '';
    document.getElementById('receipt-invoice-number').textContent = invoiceNumber || '請求書を選択してください';
    document.getElementById('receipt-balance').textContent = balance ? '¥' + balance.toLocaleString() : '-';
    currentBalance = balance || 0;
    document.getElementById('receipt-modal').classList.remove('hidden');
}

function closeReceiptModal() {
    document.getElementById('receipt-modal').classList.add('hidden');
    document.getElementById('receipt-form').reset();
}

// 入金額入力時に消込後残高をリアルタイム表示
document.getElementById('received-amount')?.addEventListener('input', function () {
    const received = parseInt(this.value) || 0;
    const afterBalance = Math.max(0, currentBalance - received);
    const el = document.getElementById('balance-after');
    el.textContent = '¥' + afterBalance.toLocaleString();
    el.className = afterBalance === 0 ? 'font-semibold text-green-600' : 'font-semibold text-orange-600';
});

async function submitReceipt(e) {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));

    try {
        const res = await fetch('{{ route("payments.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': data._token,
            },
            body: JSON.stringify(data),
        });
        const result = await res.json();

        if (result.success) {
            closeReceiptModal();
            // 対象行のステータスを更新してページリロード
            location.reload();
        } else {
            alert('エラーが発生しました: ' + (result.message || '不明なエラー'));
        }
    } catch (err) {
        alert('通信エラーが発生しました。');
    }
}

function filterByStatus(status) {
    const url = new URL(window.location);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location = url.toString();
}

function openDunningModal(invoiceId, customerName) {
    if (confirm(`${customerName}宛に督促を送信しますか？`)) {
        fetch(`/invoices/${invoiceId}/dunning`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ dunning_type: 'first', method: 'email' }),
        })
        .then(r => r.json())
        .then(result => {
            if (result.success) {
                alert('督促メールを送信しました。');
                location.reload();
            }
        });
    }
}
</script>
@endpush
