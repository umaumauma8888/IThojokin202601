'use client'
import AppLayout from '@/components/layout/AppLayout'
import { fmt, STATUS_LABELS } from '@/lib/supabase'
import {
  TrendingUp, Users, FileText, CreditCard,
  AlertCircle, ArrowUpRight, ArrowDownRight
} from 'lucide-react'
import {
  AreaChart, Area, XAxis, YAxis, CartesianGrid,
  Tooltip, ResponsiveContainer, BarChart, Bar
} from 'recharts'

// ダミーデータ（Supabase接続後に差し替え）
const SALES_DATA = [
  { month: '10月', sales: 3200000, collected: 2800000 },
  { month: '11月', sales: 4100000, collected: 3600000 },
  { month: '12月', sales: 5200000, collected: 4100000 },
  { month: '1月',  sales: 3800000, collected: 3500000 },
  { month: '2月',  sales: 4600000, collected: 4200000 },
  { month: '3月',  sales: 5280000, collected: 4800000 },
]

const METRICS = [
  { label: '今月売上',     value: '¥5,280,000', change: +15.2, icon: TrendingUp,  color: 'text-brand-600' },
  { label: '有効顧客数',   value: '124社',       change: +3,    icon: Users,       color: 'text-blue-600' },
  { label: '未収入金',     value: '¥950,000',   change: -8.1,  icon: CreditCard,  color: 'text-orange-600' },
  { label: '期日超過',     value: '4件',         change: -2,    icon: AlertCircle, color: 'text-red-600' },
]

const RECENT_INVOICES = [
  { id: 'INV-2025-0187', customer: '株式会社ABC商事', amount: 165000, status: 'paid',    due: '2025/11/30' },
  { id: 'INV-2025-0186', customer: 'XYZ株式会社',     amount: 88000,  status: 'overdue', due: '2025/11/25' },
  { id: 'INV-2025-0185', customer: '株式会社GHI製作所', amount: 165000, status: 'paid',   due: '2025/11/20' },
  { id: 'INV-2025-0184', customer: 'STUインダストリーズ', amount: 132000, status: 'unpaid', due: '2025/11/30' },
  { id: 'INV-2025-0183', customer: '株式会社PQR企画',  amount: 88000,  status: 'dunning', due: '2025/10/31' },
]

const PENDING_WORKFLOWS = [
  { id: 'WF-2025-0234', title: '新規サーバー購入申請', amount: 3500000, requester: '田中太郎', days: 2 },
  { id: 'WF-2025-0232', title: '株式会社ABC商事との年間保守契約', amount: null, requester: '鈴木一郎', days: 1 },
  { id: 'WF-2025-0228', title: '取引先接待費精算', amount: 45000, requester: '中村雄介', days: 3 },
]

export default function DashboardPage() {
  return (
    <AppLayout>
      <div className="p-6 fade-in">

        {/* ヘッダー */}
        <div className="mb-6">
          <h1 className="text-xl font-semibold text-gray-900">ダッシュボード</h1>
          <p className="text-sm text-gray-500 mt-0.5">
            {new Date().toLocaleDateString('ja-JP', { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' })}
          </p>
        </div>

        {/* KPIカード */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          {METRICS.map(({ label, value, change, icon: Icon, color }) => (
            <div key={label} className="card">
              <div className="flex items-start justify-between mb-3">
                <p className="text-xs text-gray-500">{label}</p>
                <Icon size={16} className={color} />
              </div>
              <p className="text-2xl font-semibold text-gray-900">{value}</p>
              <div className={`flex items-center gap-1 mt-1 text-xs ${change >= 0 ? 'text-green-600' : 'text-red-500'}`}>
                {change >= 0
                  ? <ArrowUpRight size={12} />
                  : <ArrowDownRight size={12} />}
                <span>{Math.abs(change)}{typeof change === 'number' && change % 1 !== 0 ? '%' : ''} 前月比</span>
              </div>
            </div>
          ))}
        </div>

        <div className="grid grid-cols-3 gap-4 mb-4">
          {/* 売上グラフ（共P-04：経営分析） */}
          <div className="card col-span-2">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-sm font-semibold text-gray-800">売上・回収推移</h2>
                <p className="text-xs text-gray-400 mt-0.5">共P-04：管理会計・経営分析</p>
              </div>
              <span className="text-xs px-2 py-1 bg-brand-50 text-brand-700 rounded-full">過去6ヶ月</span>
            </div>
            <ResponsiveContainer width="100%" height={200}>
              <AreaChart data={SALES_DATA}>
                <defs>
                  <linearGradient id="sales" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%"  stopColor="#0c9069" stopOpacity={0.15} />
                    <stop offset="95%" stopColor="#0c9069" stopOpacity={0} />
                  </linearGradient>
                  <linearGradient id="collected" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="5%"  stopColor="#3b82f6" stopOpacity={0.1} />
                    <stop offset="95%" stopColor="#3b82f6" stopOpacity={0} />
                  </linearGradient>
                </defs>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                <XAxis dataKey="month" tick={{ fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11 }} axisLine={false} tickLine={false}
                       tickFormatter={v => `¥${(v / 1000000).toFixed(1)}M`} />
                <Tooltip formatter={(v: number) => fmt.currency(v)} />
                <Area type="monotone" dataKey="sales" name="売上" stroke="#0c9069" fill="url(#sales)" strokeWidth={2} />
                <Area type="monotone" dataKey="collected" name="回収済" stroke="#3b82f6" fill="url(#collected)" strokeWidth={2} />
              </AreaChart>
            </ResponsiveContainer>
          </div>

          {/* 承認待ちワークフロー（共P-05） */}
          <div className="card">
            <div className="mb-4">
              <h2 className="text-sm font-semibold text-gray-800">承認待ち</h2>
              <p className="text-xs text-gray-400 mt-0.5">共P-05：ワークフロー</p>
            </div>
            <div className="space-y-3">
              {PENDING_WORKFLOWS.map(wf => (
                <div key={wf.id} className="p-3 bg-yellow-50 border border-yellow-100 rounded-lg">
                  <p className="text-xs font-medium text-gray-800 leading-tight">{wf.title}</p>
                  <div className="flex items-center justify-between mt-1.5">
                    <span className="text-xs text-gray-500">{wf.requester}</span>
                    <span className="text-xs text-yellow-700 font-medium">{wf.days}日前</span>
                  </div>
                  {wf.amount && (
                    <p className="text-xs text-gray-600 mt-1">{fmt.currency(wf.amount)}</p>
                  )}
                </div>
              ))}
            </div>
            <a href="/workflow" className="block text-center text-xs text-brand-600 hover:underline mt-3">
              すべて表示 →
            </a>
          </div>
        </div>

        {/* 最近の請求書（共P-02：受発注・決済機能） */}
        <div className="card">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h2 className="text-sm font-semibold text-gray-800">最近の請求書</h2>
              <p className="text-xs text-gray-400 mt-0.5">共P-02：受発注機能・決済機能（インボイス対応）</p>
            </div>
            <a href="/invoices" className="text-xs text-brand-600 hover:underline">すべて表示</a>
          </div>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-gray-100">
                {['請求書番号', '顧客名', '金額', '期日', 'ステータス'].map(h => (
                  <th key={h} className="text-left text-xs font-medium text-gray-500 pb-2 pr-4">{h}</th>
                ))}
              </tr>
            </thead>
            <tbody>
              {RECENT_INVOICES.map(inv => {
                const s = STATUS_LABELS.collection[inv.status as keyof typeof STATUS_LABELS.collection]
                return (
                  <tr key={inv.id} className="table-row border-b border-gray-50">
                    <td className="py-2.5 pr-4 font-mono text-xs text-blue-600">{inv.id}</td>
                    <td className="py-2.5 pr-4 text-gray-700">{inv.customer}</td>
                    <td className="py-2.5 pr-4 font-medium">{fmt.currency(inv.amount)}</td>
                    <td className="py-2.5 pr-4 text-gray-500 text-xs">{inv.due}</td>
                    <td className="py-2.5">
                      <span className={`badge ${s?.color}`}>{s?.label}</span>
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
