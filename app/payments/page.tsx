'use client'
import { useState } from 'react'
import AppLayout from '@/components/layout/AppLayout'
import { fmt } from '@/lib/supabase'
import { CreditCard, CheckCircle, AlertTriangle, X, TrendingDown } from 'lucide-react'

const RECEIVABLES = [
  { id: 'INV-2025-0186', customer: 'XYZ株式会社',         amount: 88000,  received: 0,     balance: 88000,  due: '2025/11/25', overdue_days: 12, status: 'overdue' },
  { id: 'INV-2025-0184', customer: 'STUインダストリーズ', amount: 132000, received: 0,     balance: 132000, due: '2025/11/30', overdue_days: 7,  status: 'overdue' },
  { id: 'INV-2025-0183', customer: '株式会社PQR企画',     amount: 88000,  received: 0,     balance: 88000,  due: '2025/10/31', overdue_days: 37, status: 'dunning' },
  { id: 'INV-2025-0180', customer: 'MNOトレーディング',   amount: 110000, received: 55000, balance: 55000,  due: '2025/11/15', overdue_days: 22, status: 'partial' },
]

export default function PaymentsPage() {
  const [modal, setModal] = useState<null | typeof RECEIVABLES[0]>(null)
  const [amount, setAmount] = useState('')
  const [date, setDate] = useState(new Date().toISOString().split('T')[0])
  const [method, setMethod] = useState('bank_transfer')
  const [success, setSuccess] = useState(false)

  const totalBalance = RECEIVABLES.reduce((s, r) => s + r.balance, 0)
  const overdueBalance = RECEIVABLES.filter(r => r.status === 'overdue' || r.status === 'dunning').reduce((s, r) => s + r.balance, 0)

  const afterBalance = modal ? Math.max(0, modal.balance - (parseInt(amount) || 0)) : 0

  const handleSubmit = () => {
    setSuccess(true)
    setTimeout(() => { setSuccess(false); setModal(null); setAmount('') }, 1500)
  }

  const STATUS = {
    overdue: { label: '期日超過', color: 'bg-red-100 text-red-700' },
    dunning: { label: '督促中',   color: 'bg-orange-100 text-orange-700' },
    partial: { label: '一部入金', color: 'bg-blue-100 text-blue-700' },
    unpaid:  { label: '未入金',   color: 'bg-gray-100 text-gray-600' },
  }

  return (
    <AppLayout>
      <div className="p-6 fade-in">

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">入金消込・債権管理</h1>
            <p className="text-xs text-gray-400 mt-0.5">共P-02：決済機能（商品売買に伴う債権債務管理業務の負担解消）</p>
          </div>
        </div>

        {/* 機能説明バナー */}
        <div className="mb-5 p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
          <CreditCard size={16} className="text-blue-600 flex-shrink-0 mt-0.5" />
          <div className="text-xs text-blue-700">
            <span className="font-semibold">決済機能（インボイス対応類型②）</span>　
            入金登録→売掛金自動消込→残高リアルタイム更新。商品売買に伴う金銭の授受による債権債務管理業務を自動化し、経理負担を解消します。
          </div>
        </div>

        {/* サマリー */}
        <div className="grid grid-cols-3 gap-4 mb-5">
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">売掛残高合計</p>
            <p className="text-xl font-semibold text-gray-900">{fmt.currency(totalBalance)}</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">期日超過残高</p>
            <p className="text-xl font-semibold text-red-600">{fmt.currency(overdueBalance)}</p>
            <p className="text-xs text-red-400 mt-1">{RECEIVABLES.filter(r=>r.status==='overdue'||r.status==='dunning').length}件超過中</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">回収率</p>
            <p className="text-xl font-semibold text-green-600">
              {Math.round((1 - totalBalance / RECEIVABLES.reduce((s,r)=>s+r.amount,0)) * 100)}%
            </p>
            <div className="mt-2 w-full bg-gray-100 rounded-full h-1.5">
              <div className="bg-green-500 h-1.5 rounded-full"
                style={{ width: `${Math.round((1 - totalBalance / RECEIVABLES.reduce((s,r)=>s+r.amount,0)) * 100)}%` }} />
            </div>
          </div>
        </div>

        {/* 未収入金一覧 */}
        <div className="card p-0 overflow-hidden">
          <div className="px-5 py-3 border-b border-gray-100">
            <h2 className="text-sm font-medium text-gray-700">未収入金一覧</h2>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['請求書番号','顧客名','請求額','入金済','売掛残高','期日','超過日数','ステータス','操作'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 px-4 py-3">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {RECEIVABLES.map(r => {
                const s = STATUS[r.status as keyof typeof STATUS]
                return (
                  <tr key={r.id} className={`table-row ${r.overdue_days > 30 ? 'bg-red-50/30' : ''}`}>
                    <td className="px-4 py-3 font-mono text-xs text-blue-600">{r.id}</td>
                    <td className="px-4 py-3 text-gray-700">{r.customer}</td>
                    <td className="px-4 py-3 text-gray-700">{fmt.currency(r.amount)}</td>
                    <td className="px-4 py-3 text-green-600">{fmt.currency(r.received)}</td>
                    <td className="px-4 py-3 font-semibold text-red-600">{fmt.currency(r.balance)}</td>
                    <td className="px-4 py-3 text-xs text-gray-500">{r.due}</td>
                    <td className="px-4 py-3">
                      {r.overdue_days > 0
                        ? <span className="text-red-500 font-medium text-xs">{r.overdue_days}日</span>
                        : <span className="text-gray-300 text-xs">-</span>}
                    </td>
                    <td className="px-4 py-3">
                      <span className={`badge ${s.color}`}>{s.label}</span>
                    </td>
                    <td className="px-4 py-3">
                      <button
                        onClick={() => { setModal(r); setAmount(String(r.balance)) }}
                        className="text-xs px-3 py-1 bg-green-50 text-green-700 border border-green-200 rounded hover:bg-green-100 transition">
                        入金登録
                      </button>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* 入金登録モーダル */}
      {modal && (
        <div className="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
          <div className="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6 fade-in">
            <div className="flex items-center justify-between mb-5">
              <h3 className="text-base font-semibold text-gray-800">入金登録・売掛消込</h3>
              <button onClick={() => setModal(null)} className="text-gray-400 hover:text-gray-600"><X size={18} /></button>
            </div>

            {success ? (
              <div className="text-center py-8">
                <CheckCircle size={40} className="text-green-500 mx-auto mb-3" />
                <p className="font-semibold text-gray-800">消込が完了しました</p>
                <p className="text-sm text-gray-500 mt-1">売掛残高が更新されました</p>
              </div>
            ) : (
              <>
                <div className="mb-4 p-3 bg-gray-50 rounded-lg">
                  <p className="text-xs text-gray-500">{modal.id}　{modal.customer}</p>
                  <p className="text-sm text-gray-700 mt-1">売掛残高: <span className="font-semibold text-red-600">{fmt.currency(modal.balance)}</span></p>
                </div>

                <div className="grid grid-cols-2 gap-3 mb-3">
                  <div>
                    <label className="text-xs font-medium text-gray-600 block mb-1">入金額 <span className="text-red-500">*</span></label>
                    <input type="number" value={amount} onChange={e => setAmount(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300" />
                  </div>
                  <div>
                    <label className="text-xs font-medium text-gray-600 block mb-1">入金日 <span className="text-red-500">*</span></label>
                    <input type="date" value={date} onChange={e => setDate(e.target.value)}
                      className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300" />
                  </div>
                </div>

                <div className="mb-4">
                  <label className="text-xs font-medium text-gray-600 block mb-1">入金方法</label>
                  <select value={method} onChange={e => setMethod(e.target.value)}
                    className="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-300">
                    <option value="bank_transfer">銀行振込</option>
                    <option value="credit_card">クレジットカード</option>
                    <option value="cash">現金</option>
                  </select>
                </div>

                {/* 消込プレビュー */}
                <div className={`p-3 rounded-lg mb-4 text-xs ${afterBalance === 0 ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-yellow-50 border border-yellow-200 text-yellow-700'}`}>
                  <p className="font-medium mb-1">消込後の状態</p>
                  <p>売掛残高: <span className="font-semibold">{fmt.currency(afterBalance)}</span>
                    {afterBalance === 0 && ' ✓ 全額消込'}
                  </p>
                </div>

                <div className="flex gap-2">
                  <button onClick={() => setModal(null)} className="btn-secondary flex-1">キャンセル</button>
                  <button onClick={handleSubmit} className="btn-primary flex-1">入金登録・消込実行</button>
                </div>
              </>
            )}
          </div>
        </div>
      )}
    </AppLayout>
  )
}
