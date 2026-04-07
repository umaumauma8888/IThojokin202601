'use client'
import { useState } from 'react'
import AppLayout from '@/components/layout/AppLayout'
import { fmt } from '@/lib/supabase'
import { Plus, TrendingUp, Target, Clock } from 'lucide-react'

const DEALS = [
  { id: '1', title: 'クラウド型CRM導入プロジェクト', customer: '株式会社ABC商事',     amount: 4800000, stage: 'closing',     probability: 85, follow: '2025/11/05', close: '2025/11/30', assignee: '山田太郎' },
  { id: '2', title: '業務システム刷新提案',           customer: 'XYZ株式会社',         amount: 6200000, stage: 'proposal',    probability: 60, follow: '2025/11/08', close: '2025/12/15', assignee: '佐藤花子' },
  { id: '3', title: 'AI自動応答システム導入',         customer: 'DEFコーポレーション',  amount: 3500000, stage: 'negotiation', probability: 45, follow: '2025/11/10', close: '2025/11/25', assignee: '山田太郎' },
  { id: '4', title: '基幹システム移行支援',           customer: '株式会社GHI製作所',   amount: 8900000, stage: 'proposal',    probability: 35, follow: '2025/11/12', close: '2026/01/31', assignee: '鈴木次郎' },
  { id: '5', title: 'セキュリティ監査対応ツール',     customer: 'STUインダストリーズ', amount: 1200000, stage: 'won',         probability: 100, follow: '-',         close: '2025/10/31', assignee: '高橋美咲' },
  { id: '6', title: 'マーケティング自動化基盤',       customer: 'MNOトレーディング',   amount: 2800000, stage: 'lead',        probability: 20, follow: '2025/11/15', close: '2026/02/28', assignee: '伊藤健太' },
]

const STAGES = {
  lead:        { label: 'リード',        color: 'bg-gray-100 text-gray-600',    width: 'w-1/6' },
  proposal:    { label: '提案中',        color: 'bg-blue-100 text-blue-700',    width: 'w-2/6' },
  negotiation: { label: '交渉中',        color: 'bg-yellow-100 text-yellow-700', width: 'w-3/6' },
  closing:     { label: 'クロージング',  color: 'bg-orange-100 text-orange-700', width: 'w-4/6' },
  won:         { label: '受注',          color: 'bg-green-100 text-green-700',  width: 'w-full' },
  lost:        { label: '失注',          color: 'bg-red-100 text-red-700',      width: 'w-full' },
}

export default function DealsPage() {
  const [filter, setFilter] = useState('all')

  const filtered = DEALS.filter(d => filter === 'all' || d.stage === filter)
  const totalPipeline = DEALS.filter(d => !['won','lost'].includes(d.stage))
                             .reduce((s, d) => s + d.amount * d.probability / 100, 0)
  const wonAmount = DEALS.filter(d => d.stage === 'won').reduce((s, d) => s + d.amount, 0)

  return (
    <AppLayout>
      <div className="p-6 fade-in">

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">商談管理</h1>
            <p className="text-xs text-gray-400 mt-0.5">共P-01：SFA（商談進捗・営業活動可視化）</p>
          </div>
          <button className="btn-primary flex items-center gap-2">
            <Plus size={14} />新規商談登録
          </button>
        </div>

        {/* KPI */}
        <div className="grid grid-cols-3 gap-4 mb-5">
          <div className="card">
            <div className="flex items-center gap-2 mb-2">
              <TrendingUp size={14} className="text-brand-600" />
              <p className="text-xs text-gray-500">パイプライン合計（加重）</p>
            </div>
            <p className="text-xl font-semibold text-gray-900">{fmt.currency(Math.round(totalPipeline))}</p>
            <p className="text-xs text-gray-400 mt-1">進行中 {DEALS.filter(d=>!['won','lost'].includes(d.stage)).length}件</p>
          </div>
          <div className="card">
            <div className="flex items-center gap-2 mb-2">
              <Target size={14} className="text-green-600" />
              <p className="text-xs text-gray-500">今月受注額</p>
            </div>
            <p className="text-xl font-semibold text-green-600">{fmt.currency(wonAmount)}</p>
            <p className="text-xs text-gray-400 mt-1">成約率 34.2%</p>
          </div>
          <div className="card">
            <div className="flex items-center gap-2 mb-2">
              <Clock size={14} className="text-orange-500" />
              <p className="text-xs text-gray-500">平均商談期間</p>
            </div>
            <p className="text-xl font-semibold text-gray-900">47日</p>
            <p className="text-xs text-gray-400 mt-1">前月比 -3日</p>
          </div>
        </div>

        {/* ステージフィルター */}
        <div className="flex gap-1 mb-4 flex-wrap">
          {[['all','すべて'], ...Object.entries(STAGES).map(([v,{label}]) => [v, label])].map(([v, l]) => (
            <button key={v} onClick={() => setFilter(v)}
              className={`px-3 py-1.5 text-xs rounded-lg border transition-colors
                ${filter === v ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`}>
              {l}
            </button>
          ))}
        </div>

        {/* 商談一覧 */}
        <div className="space-y-3">
          {filtered.map(deal => {
            const stage = STAGES[deal.stage as keyof typeof STAGES]
            return (
              <div key={deal.id} className="card hover:shadow-sm transition-shadow cursor-pointer">
                <div className="flex items-start justify-between mb-3">
                  <div>
                    <p className="font-semibold text-gray-800">{deal.title}</p>
                    <p className="text-xs text-gray-500 mt-0.5">{deal.customer}　担当: {deal.assignee}</p>
                  </div>
                  <div className="text-right">
                    <p className="font-semibold text-gray-800">{fmt.currency(deal.amount)}</p>
                    <span className={`badge ${stage.color} mt-1`}>{stage.label}</span>
                  </div>
                </div>

                {/* 進捗バー */}
                <div className="mb-3">
                  <div className="flex justify-between text-xs text-gray-500 mb-1">
                    <span>成約確度: {deal.probability}%</span>
                    <span>加重金額: {fmt.currency(Math.round(deal.amount * deal.probability / 100))}</span>
                  </div>
                  <div className="w-full bg-gray-100 rounded-full h-1.5">
                    <div className="bg-brand-500 h-1.5 rounded-full transition-all"
                         style={{ width: `${deal.probability}%` }} />
                  </div>
                </div>

                <div className="flex gap-4 text-xs text-gray-400">
                  <span>次回フォロー: <span className="text-gray-600">{deal.follow}</span></span>
                  <span>クロージング予定: <span className="text-gray-600">{deal.close}</span></span>
                </div>
              </div>
            )
          })}
        </div>

      </div>
    </AppLayout>
  )
}
