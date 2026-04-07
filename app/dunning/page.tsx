'use client'
import { useState } from 'react'
import AppLayout from '@/components/layout/AppLayout'
import { fmt } from '@/lib/supabase'
import { AlertCircle, Mail, Phone, CheckCircle, X } from 'lucide-react'

const OVERDUE = [
  { id: 'INV-2025-0183', customer: '株式会社PQR企画',     balance: 88000,  due: '2025/10/31', overdue_days: 37, last_dunning: '初回督促', last_dunning_at: '2025/11/07', status: 'dunning' },
  { id: 'INV-2025-0186', customer: 'XYZ株式会社',         balance: 88000,  due: '2025/11/25', overdue_days: 12, last_dunning: null, last_dunning_at: null, status: 'overdue' },
  { id: 'INV-2025-0184', customer: 'STUインダストリーズ', balance: 132000, due: '2025/11/30', overdue_days: 7,  last_dunning: null, last_dunning_at: null, status: 'overdue' },
]

export default function DunningPage() {
  const [sent, setSent] = useState<string | null>(null)

  const handleSend = (id: string, customer: string) => {
    if (confirm(`${customer}に督促メールを送信しますか？`)) {
      setSent(id)
      setTimeout(() => setSent(null), 2000)
    }
  }

  return (
    <AppLayout>
      <div className="p-6 fade-in">
        <div className="mb-6">
          <h1 className="text-xl font-semibold text-gray-900">督促管理</h1>
          <p className="text-xs text-gray-400 mt-0.5">共P-02：決済機能（未収債権の督促・回収管理）</p>
        </div>

        <div className="grid grid-cols-3 gap-4 mb-5">
          <div className="card border-l-4 border-l-red-400">
            <p className="text-xs text-gray-500 mb-1">期日超過（未督促）</p>
            <p className="text-2xl font-semibold text-red-600">{OVERDUE.filter(o=>o.status==='overdue').length}件</p>
          </div>
          <div className="card border-l-4 border-l-orange-400">
            <p className="text-xs text-gray-500 mb-1">督促中</p>
            <p className="text-2xl font-semibold text-orange-600">{OVERDUE.filter(o=>o.status==='dunning').length}件</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">自動督促スケジュール</p>
            <p className="text-2xl font-semibold text-gray-700">3件</p>
            <p className="text-xs text-gray-400 mt-1">毎朝07:00 自動実行</p>
          </div>
        </div>

        <div className="card p-0 overflow-hidden">
          <div className="px-5 py-3 border-b border-gray-100">
            <h2 className="text-sm font-medium text-gray-700">期日超過一覧</h2>
          </div>
          <table className="w-full text-sm">
            <thead className="bg-gray-50">
              <tr>
                {['顧客名','請求書番号','売掛残高','期日','超過日数','督促状況','操作'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 px-4 py-3">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {OVERDUE.map(o => (
                <tr key={o.id} className={`table-row ${o.overdue_days >= 30 ? 'bg-red-50/30' : ''}`}>
                  <td className="px-4 py-3 font-medium text-gray-800">{o.customer}</td>
                  <td className="px-4 py-3 font-mono text-xs text-blue-600">{o.id}</td>
                  <td className="px-4 py-3 font-semibold text-red-600">{fmt.currency(o.balance)}</td>
                  <td className="px-4 py-3 text-xs text-gray-500">{o.due}</td>
                  <td className="px-4 py-3">
                    <span className={`font-semibold ${o.overdue_days >= 30 ? 'text-red-600' : 'text-orange-500'}`}>
                      {o.overdue_days}日
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    {o.last_dunning
                      ? <span className="text-xs text-orange-600">{o.last_dunning}（{o.last_dunning_at}）</span>
                      : <span className="text-xs text-gray-400">未督促</span>}
                  </td>
                  <td className="px-4 py-3">
                    {sent === o.id
                      ? <span className="flex items-center gap-1 text-xs text-green-600"><CheckCircle size={12} />送信済</span>
                      : (
                        <div className="flex gap-2">
                          <button onClick={() => handleSend(o.id, o.customer)}
                            className="flex items-center gap-1 text-xs px-2 py-1 bg-orange-50 text-orange-700 border border-orange-200 rounded hover:bg-orange-100">
                            <Mail size={11} />{o.last_dunning ? '二次督促' : '初回督促'}
                          </button>
                          <button className="flex items-center gap-1 text-xs px-2 py-1 bg-gray-50 text-gray-600 border border-gray-200 rounded hover:bg-gray-100">
                            <Phone size={11} />電話
                          </button>
                        </div>
                      )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  )
}
