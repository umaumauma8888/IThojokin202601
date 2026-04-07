'use client'
import { useState } from 'react'
import AppLayout from '@/components/layout/AppLayout'
import { fmt, STATUS_LABELS } from '@/lib/supabase'
import { Search, Plus, Building2, Phone, Mail, TrendingUp } from 'lucide-react'

const CUSTOMERS = [
  { id: '1', company_name: '株式会社ABC商事',    contact_name: '田中太郎', email: 'tanaka@abc.co.jp',  phone: '03-1234-5678', industry: '製造業',     status: 'active',   total_sales: 2450000 },
  { id: '2', company_name: 'XYZ株式会社',        contact_name: '佐藤花子', email: 'sato@xyz.co.jp',    phone: '03-2345-6789', industry: 'IT・通信',   status: 'active',   total_sales: 1890000 },
  { id: '3', company_name: 'DEFコーポレーション', contact_name: '鈴木一郎', email: 'suzuki@def.com',    phone: '06-3456-7890', industry: '小売業',     status: 'inactive', total_sales: 0 },
  { id: '4', company_name: '株式会社GHI製作所',  contact_name: '高橋美咲', email: 'takahashi@ghi.jp',  phone: '052-456-7890', industry: '製造業',     status: 'active',   total_sales: 3200000 },
  { id: '5', company_name: 'JKLソリューションズ', contact_name: '伊藤健太', email: 'ito@jkl.net',       phone: '06-5678-9012', industry: 'サービス業', status: 'prospect', total_sales: 0 },
  { id: '6', company_name: 'MNOトレーディング',  contact_name: '山本直樹', email: 'yamamoto@mno.com',  phone: '03-6789-0123', industry: '卸売業',     status: 'active',   total_sales: 850000 },
  { id: '7', company_name: '株式会社PQR企画',    contact_name: '中村愛',   email: 'nakamura@pqr.co.jp', phone: '03-7890-1234', industry: '広告業',     status: 'active',   total_sales: 1650000 },
  { id: '8', company_name: 'STUインダストリーズ', contact_name: '小林誠',   email: 'kobayashi@stu.co',  phone: '045-890-1234', industry: '製造業',     status: 'active',   total_sales: 4120000 },
]

const STATUS_MAP = {
  active:   { label: 'アクティブ', color: 'bg-green-100 text-green-700' },
  inactive: { label: '休眠',       color: 'bg-gray-100 text-gray-500' },
  prospect: { label: '見込み',     color: 'bg-blue-100 text-blue-700' },
}

export default function CustomersPage() {
  const [search, setSearch] = useState('')
  const [filter, setFilter] = useState('all')

  const filtered = CUSTOMERS.filter(c => {
    const matchSearch = c.company_name.includes(search) || c.contact_name.includes(search)
    const matchFilter = filter === 'all' || c.status === filter
    return matchSearch && matchFilter
  })

  return (
    <AppLayout>
      <div className="p-6 fade-in">

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">顧客管理</h1>
            <p className="text-xs text-gray-400 mt-0.5">共P-01：顧客対応・販売支援（CRM）</p>
          </div>
          <button className="btn-primary flex items-center gap-2">
            <Plus size={14} />新規顧客登録
          </button>
        </div>

        {/* 検索・フィルター */}
        <div className="flex gap-3 mb-4">
          <div className="relative flex-1 max-w-xs">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input
              value={search}
              onChange={e => setSearch(e.target.value)}
              placeholder="顧客名・担当者名で検索..."
              className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-300"
            />
          </div>
          <div className="flex gap-1">
            {[['all','すべて'],['active','アクティブ'],['prospect','見込み'],['inactive','休眠']].map(([v,l]) => (
              <button key={v} onClick={() => setFilter(v)}
                className={`px-3 py-2 text-xs rounded-lg border transition-colors
                  ${filter === v ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`}>
                {l}
              </button>
            ))}
          </div>
        </div>

        {/* 顧客テーブル */}
        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                {['企業名', '担当者', '業種', '連絡先', '累計売上', 'ステータス', '操作'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 px-4 py-3">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {filtered.map(c => {
                const s = STATUS_MAP[c.status as keyof typeof STATUS_MAP]
                return (
                  <tr key={c.id} className="table-row">
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-2">
                        <div className="w-7 h-7 rounded-lg bg-brand-100 flex items-center justify-center flex-shrink-0">
                          <Building2 size={12} className="text-brand-600" />
                        </div>
                        <span className="font-medium text-gray-800">{c.company_name}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3 text-gray-600">{c.contact_name}</td>
                    <td className="px-4 py-3 text-gray-500 text-xs">{c.industry}</td>
                    <td className="px-4 py-3">
                      <div className="space-y-0.5">
                        <div className="flex items-center gap-1 text-xs text-gray-500">
                          <Mail size={11} />{c.email}
                        </div>
                        <div className="flex items-center gap-1 text-xs text-gray-500">
                          <Phone size={11} />{c.phone}
                        </div>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex items-center gap-1">
                        <TrendingUp size={12} className={c.total_sales > 0 ? 'text-green-500' : 'text-gray-300'} />
                        <span className="font-medium text-gray-800">{fmt.currency(c.total_sales)}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <span className={`badge ${s.color}`}>{s.label}</span>
                    </td>
                    <td className="px-4 py-3">
                      <div className="flex gap-2">
                        <button className="text-xs text-brand-600 hover:underline">詳細</button>
                        <button className="text-xs text-gray-400 hover:text-gray-600">編集</button>
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
