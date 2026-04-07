'use client'
import AppLayout from '@/components/layout/AppLayout'
import { fmt } from '@/lib/supabase'
import { GitPullRequest, CheckCircle, XCircle, Clock, ChevronRight } from 'lucide-react'
import { useState } from 'react'

const REQUESTS = [
  { id: 'WF-2025-0234', title: '新規サーバー購入申請',           type: '稟議申請', amount: 3500000, requester: '田中太郎（システム部）', status: 'pending', days: 2,  step: '部長承認待ち' },
  { id: 'WF-2025-0233', title: '出張経費精算（東京→大阪）',     type: '経費精算', amount: 45000,   requester: '佐藤花子',               status: 'pending', days: 1,  step: '課長承認待ち' },
  { id: 'WF-2025-0232', title: '株式会社ABC商事との年間保守契約', type: '契約承認', amount: null,    requester: '鈴木一郎',               status: 'pending', days: 1,  step: '部長承認待ち' },
  { id: 'WF-2025-0231', title: '開発PC 5台購入申請',             type: '稟議申請', amount: 750000,  requester: '高橋健',                 status: 'approved', days: 3, step: '承認完了' },
  { id: 'WF-2025-0230', title: '有給休暇取得（11/10-11/12）',    type: '休暇申請', amount: null,    requester: '伊藤美咲',               status: 'approved', days: 4, step: '承認完了' },
  { id: 'WF-2025-0229', title: '新規採用に伴う人件費増額申請',   type: '稟議申請', amount: 2400000, requester: '渡辺翼',                  status: 'rejected', days: 5, step: '否認' },
]

const STATUS_CONFIG: Record<string, {label:string; color:string; icon:any}> = {
  pending:  { label: '承認待ち', color: 'bg-yellow-100 text-yellow-700', icon: Clock },
  approved: { label: '承認済',   color: 'bg-green-100 text-green-700',   icon: CheckCircle },
  rejected: { label: '否認',     color: 'bg-red-100 text-red-700',       icon: XCircle },
}

export default function WorkflowPage() {
  const [filter, setFilter] = useState('all')
  const filtered = REQUESTS.filter(r => filter === 'all' || r.status === filter)
  const pendingCount = REQUESTS.filter(r => r.status === 'pending').length

  return (
    <AppLayout>
      <div className="p-6 fade-in">
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">ワークフロー管理</h1>
            <p className="text-xs text-gray-400 mt-0.5">共P-05：入力フォーム設計・承認・決裁・通知・社内統制</p>
          </div>
          <button className="btn-primary flex items-center gap-2">
            <GitPullRequest size={14} />新規申請
          </button>
        </div>

        <div className="grid grid-cols-3 gap-4 mb-5">
          <div className="card border-l-4 border-l-yellow-400">
            <p className="text-xs text-gray-500 mb-1">承認待ち（あなた）</p>
            <p className="text-2xl font-semibold text-yellow-600">{pendingCount}件</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">今月承認済</p>
            <p className="text-2xl font-semibold text-green-600">45件</p>
          </div>
          <div className="card">
            <p className="text-xs text-gray-500 mb-1">平均承認日数</p>
            <p className="text-2xl font-semibold text-gray-700">1.8日</p>
          </div>
        </div>

        <div className="flex gap-1 mb-4">
          {[['all','すべて'],['pending','承認待ち'],['approved','承認済'],['rejected','否認']].map(([v,l]) => (
            <button key={v} onClick={() => setFilter(v)}
              className={`px-3 py-2 text-xs rounded-lg border transition-colors
                ${filter === v ? 'bg-brand-600 text-white border-brand-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'}`}>
              {l}
            </button>
          ))}
        </div>

        <div className="card p-0 overflow-hidden">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b border-gray-100">
              <tr>
                {['申請番号','申請種別','申請内容','申請者','申請金額','現在のステップ','ステータス','操作'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 px-4 py-3">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-50">
              {filtered.map(r => {
                const s = STATUS_CONFIG[r.status]
                const Icon = s.icon
                return (
                  <tr key={r.id} className="table-row">
                    <td className="px-4 py-3 font-mono text-xs text-blue-600">{r.id}</td>
                    <td className="px-4 py-3 text-xs text-gray-500">{r.type}</td>
                    <td className="px-4 py-3 text-gray-800 font-medium max-w-xs truncate">{r.title}</td>
                    <td className="px-4 py-3 text-gray-600 text-xs">{r.requester}</td>
                    <td className="px-4 py-3 text-gray-700">{r.amount ? fmt.currency(r.amount) : '-'}</td>
                    <td className="px-4 py-3 text-xs text-gray-500">{r.step}</td>
                    <td className="px-4 py-3">
                      <span className={`badge ${s.color} flex items-center gap-1 w-fit`}>
                        <Icon size={10} />{s.label}
                      </span>
                    </td>
                    <td className="px-4 py-3">
                      {r.status === 'pending' && (
                        <div className="flex gap-2">
                          <button className="text-xs px-2 py-1 bg-green-50 text-green-700 border border-green-200 rounded hover:bg-green-100">承認</button>
                          <button className="text-xs px-2 py-1 bg-red-50 text-red-600 border border-red-200 rounded hover:bg-red-100">否認</button>
                        </div>
                      )}
                      {r.status !== 'pending' && (
                        <button className="text-xs text-gray-400 hover:text-gray-600">詳細</button>
                      )}
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
