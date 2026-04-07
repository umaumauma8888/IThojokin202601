@extends('layouts.app')

@section('title', '督促スケジュール設定')

@section('content')
<div class="px-6 py-4 max-w-2xl">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">督促スケジュール設定</h1>
            <p class="text-sm text-gray-500 mt-1">期日超過後の自動督促タイミングを設定します（毎朝07:00実行）</p>
        </div>
        <a href="{{ route('payments.dunning.index') }}"
           class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50">
            ← 督促管理に戻る
        </a>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-gray-100">
            <h2 class="text-sm font-medium text-gray-700">現在のスケジュール</h2>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left px-4 py-3 text-xs font-medium text-gray-500">スケジュール名</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">種別</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">超過後日数</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">方法</th>
                    <th class="text-center px-4 py-3 text-xs font-medium text-gray-500">状態</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($schedules as $schedule)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-700">{{ $schedule->name }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full
                            {{ $schedule->dunning_type === 'first' ? 'bg-yellow-100 text-yellow-700' :
                              ($schedule->dunning_type === 'second' ? 'bg-orange-100 text-orange-700' :
                              'bg-red-100 text-red-700') }}">
                            {{ $schedule->dunning_type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $schedule->days_after_due }}日後</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $schedule->method_label }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full
                            {{ $schedule->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $schedule->is_active ? '有効' : '無効' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">
                        スケジュールが設定されていません
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- スケジュール追加・編集フォーム --}}
    <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-sm font-medium text-gray-700 mb-4">スケジュールを追加・更新</h2>

        <form id="schedule-form" onsubmit="saveSchedule(event)">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="col-span-2">
                    <label class="text-xs font-medium text-gray-600 block mb-1">スケジュール名</label>
                    <input type="text" name="name"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                           placeholder="例: 初回督促（7日超過）" required>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">督促種別</label>
                    <select name="dunning_type"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none" required>
                        <option value="first">初回督促</option>
                        <option value="second">二次督促</option>
                        <option value="final">最終督促</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">期日超過後の日数</label>
                    <input type="number" name="days_after_due" min="1" max="365"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none"
                           placeholder="例: 7" required>
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 block mb-1">督促方法</label>
                    <select name="method"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-300 focus:outline-none" required>
                        <option value="email">メール自動送信</option>
                        <option value="phone">電話（手動）</option>
                        <option value="mail">郵送（手動）</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 mt-5">
                    <input type="checkbox" name="is_active" id="is-active" value="1" checked class="rounded">
                    <label for="is-active" class="text-sm text-gray-600">有効にする</label>
                </div>
            </div>

            <button type="submit"
                    class="px-6 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                保存する
            </button>
        </form>
    </div>

    {{-- 説明 --}}
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-700">
        <p class="font-medium mb-1">自動督促の動作について</p>
        <p>毎朝07:00に <code class="bg-blue-100 px-1 rounded">php artisan receivable:daily</code> が実行され、設定した日数を超過した未入金請求書に対して自動でメールを送信します。同一種別の督促は1回のみ送信されます。</p>
    </div>

</div>
@endsection

@push('scripts')
<script>
async function saveSchedule(e) {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target));
    data.is_active = document.getElementById('is-active').checked ? 1 : 0;

    const res = await fetch('{{ route("payments.dunning.schedules.save") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': data._token,
        },
        body: JSON.stringify(data),
    });

    const result = await res.json();
    if (result.success) {
        alert('保存しました。');
        location.reload();
    } else {
        alert('エラー: ' + (result.message || '保存に失敗しました'));
    }
}
</script>
@endpush
