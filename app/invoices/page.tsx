'use client'
import { useState } from 'react'
import AppLayout from '@/components/layout/AppLayout'
import { fmt, STATUS_LABELS } from '@/lib/supabase'
import { Plus, Search, FileText, Download, Send, CheckCircle, AlertTriangle } from 'lucide-react'

const INVOICES = [
  { id: 'INV-2025-0187', customer: '株式会社ABC商事',     issue: '2025/11/01', due: '2025/11/30', amount: 165000, tax10: 15000, tax8: 0, received: 165000, balance: 0,      status: 'paid',    t_number: 'T1234567890123' },
  { id: 'INV-2025-0186', customer: 'XYZ株式会社',         issue: '2025/11/01', due: '2025/11/25', amount: 88000,  tax10: 8000,  tax8: 0, received: 0,       balance: 88000,  status: 'overdue', t_number: 'T1234567890123' },
  { id: 'INV-2025-0185', customer: '株式会社GHI製作所',   issue: '2025/11/01', due: '2025/11/20', amount: 165000, tax10: 15000, tax8: 0, received: 165000, balance: 0,      status: 'paid',    t_number: 'T1234567890123' },
  { id: 'INV-2025-0184', customer: 'STUインダストリーズ', issue: '2025/11/01', due: '2025/11/30', amount: 132000, tax10: 12000, tax8: 0, received: 0,       balance: 132000, status: 'unpaid',  t_number: 'T1234567890123' },
  { id: 'INV-2025-0183', customer: '株式会社PQR企画',     issue: '2025/09/20', due: '2025/10/31', amount: 88000,  tax10: 8000,  tax8: 0, received: 0,       balance: 88000,  status: 'dunning', t_number: 'T1234567890123' },
  { id: 'INV-2025-0182', customer: 'MNOトレーディング',   issue: '2025/09/25', due: '2025/10/31', amount: 55000,  tax10: 5000,  tax8: 0, received: 55000,  balance: 0,      status: 'paid',    t_number: 'T1234567890123' },
]

const STATUS_CONFIG: Record<string, { label: string; color: string }> = {
  paid:    { label: '入金済',   color: 'bg-green-100 text-green-700' },
  unpaid:  { label: '未入金',   color: 'bg-gray-100 text-gray-600' },
  overdue: { label: '期日超過', color: 'bg-red-100 text-red-700' },
  dunning: { label: '督促中',   color: 'bg-orange-100 text-orange-700' },
  draft:   { label: '下書き',   color: 'bg-blue-100 text-blue-700' },
}

export default function InvoicesPage() {
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('all')

  const filtered = INVOICES.filter(i => {
    const matchSearch = i.customer.includes(search) || i.id.includes(search)
    const matchFilter = filter === 'all' || i.status === filter
    return matchSearch && matchFilter
  })

  const totalBalance = INVOICES.reduce((s, i) => s + i.balance, 0)
  const totalPaid    = INVOICES.reduce((s, i) => s + i.received, 0)
  const overdueCount = INVOICES.filter(i => i.status === 'overdue' || i.status === 'dunning').length

  return (
    <AppLayout>
      <div className="p-6 fade-in">

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">請求書管理</h1>
            <p className="text-xs text-gray-400 mt-0.5">共P-02：受発注機能（インボイス対応・適格請求書）</p>
          </div>
          <button className="btn-primary flex items-center gap-2">
            <Plus size={14} />新規請求書作成
          </button>
        </div>

        {/* インボイス対応バナー */}
        <div className="mb-5 p-3 bg-brand-50 border border-brand-200 rounded-xl flex items-center gap-3">
          <CheckCircle size={16} className="text-brand-600 flex-shrink-0" />
          <div className="text-xs text-brand-700">
            <span className="font-semibold">インボイス制度完全対応</span>
            　適格請求書発行事業者登録番号（T番号）・税率区分（10%/8%）・税額・適用税率を自動記載。電磁的記録による保存に対応。
          </div>
        </div>

        {/* サマリー */}
        <div className="grid grid-cols-3 gap-4 mb-5">
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">未収入金合計</p>
            <p className="text-xl font-semibold text-red-600">{fmt.currency(totalBalance)}</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">回収済合計</p>
            <p className="text-xl font-semibold text-green-600">{fmt.currency(totalPaid)}</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">期日超過件数</p>
            <p className="text-xl font-semibold text-orange-600">{overdueCount}件</p>
          </div>
        </div>

        {/* 検索・フィルター */}
        <div className="flex gap-3 mb-4">
          <div className="relative flex-1 max-w-xs">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input value={search} onChange={e => setSearch(e.target.value)}
              placeholder="請求書番号・顧客名で検索..."
              className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300" />
          </div>
          <div className="flex gap-1">
            {[['all','すべて'],['unpaid','未入金'],['overdue','期日超過'],['paid','入金済']].map(([v,l]) => (
              <button key={v} onClick={() => setFilter(v)}
                className={`px-3 py-2 text-xs rounded-lg border transition-colors
                  ${filter === v ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`}>
                {l}
              </button>
            ))}
          </div>
        </div>

        {/* 請求書テーブル */}
        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                {['請求書番号','T番号','顧客名','発行日','期日','請求額','消費税','売掛残高','ステータス','操作'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 px-3 py-3">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {filtered.map(inv => {
                const s = STATUS_CONFIG[inv.status]
                return (
                  <tr key={inv.id} className="table-row">
                    <td className="px-3 py-3 font-mono text-xs text-blue-600 font-medium">{inv.id}</td>
                    <td className="px-3 py-3 font-mono text-xs text-gray-500">{inv.t_number}</td>
                    <td className="px-3 py-3 text-gray-700">{inv.customer}</td>
                    <td className="px-3 py-3 text-xs text-gray-500">{inv.issue}</td>
                    <td className="px-3 py-3 text-xs text-gray-500">{inv.due}</td>
                    <td className="px-3 py-3 font-medium text-gray-800">{fmt.currency(inv.amount)}</td>
                    <td className="px-3 py-3 text-xs text-gray-500">
                      <div>10%: {fmt.currency(inv.tax10)}</div>
                    </td>
                    <td className="px-3 py-3">
                      <span className={`font-semibold ${inv.balance > 0 ? 'text-red-600' : 'text-gray-400'}`}>
                        {fmt.currency(inv.balance)}
                      </span>
                    </td>
                    <td className="px-3 py-3">
                      <span className={`badge ${s?.color}`}>{s?.label}</span>
                    </td>
                    <td className="px-3 py-3">
                      <div className="flex gap-2">
                        <button className="text-xs text-brand-600 hover:underline flex items-center gap-1">
                          <Download size={11} />PDF
                        </button>
                        <button className="text-xs text-gray-400 hover:text-gray-600 flex items-center gap-1">
                          <Send size={11} />送信
                        </button>
                      </div>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>

      </div>
    </AppLayout>
  )
}
