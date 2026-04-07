'use client'
import AppLayout from '@/components/layout/AppLayout'
import { fmt } from '@/lib/supabase'
import {
  AreaChart, Area, BarChart, Bar, XAxis, YAxis,
  CartesianGrid, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, Legend
} from 'recharts'

const MONTHLY = [
  { month: '10月', revenue: 3200000, cost: 1800000, profit: 1400000, cashflow: 900000 },
  { month: '11月', revenue: 4100000, cost: 2100000, profit: 2000000, cashflow: 1400000 },
  { month: '12月', revenue: 5200000, cost: 2600000, profit: 2600000, cashflow: 2100000 },
  { month: '1月',  revenue: 3800000, cost: 2000000, profit: 1800000, cashflow: 1200000 },
  { month: '2月',  revenue: 4600000, cost: 2300000, profit: 2300000, cashflow: 1700000 },
  { month: '3月',  revenue: 5280000, cost: 2500000, profit: 2780000, cashflow: 2200000 },
]

const CASHFLOW_FORECAST = [
  { month: '4月', inflow: 4800000, outflow: 2200000, balance: 2600000 },
  { month: '5月', inflow: 5200000, outflow: 2400000, balance: 2800000 },
  { month: '6月', inflow: 4600000, outflow: 2300000, balance: 2300000 },
]

const CUSTOMER_PIE = [
  { name: 'STUインダストリーズ', value: 4120000 },
  { name: '株式会社GHI製作所',   value: 3200000 },
  { name: '株式会社ABC商事',     value: 2450000 },
  { name: 'XYZ株式会社',         value: 1890000 },
  { name: 'その他',              value: 3540000 },
]
const COLORS = ['#0c9069','#0b7356','#16b382','#38ce9b','#d1fae5']

export default function AnalyticsPage() {
  const latestProfit = MONTHLY[MONTHLY.length - 1].profit
  const profitRate = Math.round(latestProfit / MONTHLY[MONTHLY.length - 1].revenue * 100)

  return (
    <AppLayout>
      <div className="p-6 fade-in">
        <div className="mb-6">
          <h1 className="text-xl font-semibold text-gray-900">経営分析・管理会計</h1>
          <p className="text-xs text-gray-400 mt-0.5">共P-04：予算統制・資金繰り計画・管理会計・経営分析</p>
        </div>

        {/* AIインサイト */}
        <div className="mb-5 p-4 bg-gradient-to-r from-brand-600 to-brand-700 rounded-xl text-white">
          <p className="text-xs font-semibold mb-2 opacity-80">AIによる経営インサイト</p>
          <p className="text-sm leading-relaxed">
            過去6ヶ月の売上は継続的に増加傾向にあり、特に10月以降の伸びが顕著です。製造業からの受注が全体の45%を占めており、この業種への営業強化が効果を示しています。
            粗利率が若干低下傾向にあります。AI自動応答の活用により、初期対応のスピードを改善することで、商談化率の向上が見込まれます。
          </p>
        </div>

        {/* KPI */}
        <div className="grid grid-cols-4 gap-4 mb-5">
          {[
            { label: '今月売上',   value: fmt.currency(5280000), sub: '前月比 +14.8%', color: 'text-brand-600' },
            { label: '粗利',       value: fmt.currency(latestProfit), sub: `粗利率 ${profitRate}%`, color: 'text-blue-600' },
            { label: '月次CF',     value: fmt.currency(2200000), sub: 'キャッシュフロー', color: 'text-purple-600' },
            { label: '未収入金',   value: fmt.currency(363000), sub: '期日超過含む', color: 'text-orange-600' },
          ].map(({ label, value, sub, color }) => (
            <div key={label} className="card">
              <p className="text-xs text-gray-500 mb-1">{label}</p>
              <p className={`text-xl font-semibold ${color}`}>{value}</p>
              <p className="text-xs text-gray-400 mt-1">{sub}</p>
            </div>
          ))}
        </div>

        <div className="grid grid-cols-2 gap-4 mb-4">
          {/* 売上・粗利推移 */}
          <div className="card">
            <h2 className="text-sm font-semibold text-gray-800 mb-1">売上・粗利推移</h2>
            <p className="text-xs text-gray-400 mb-4">管理会計・経営分析</p>
            <ResponsiveContainer width="100%" height={200}>
              <BarChart data={MONTHLY}>
                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                <XAxis dataKey="month" tick={{ fontSize: 11 }} axisLine={false} tickLine={false} />
                <YAxis tick={{ fontSize: 11 }} axisLine={false} tickLine={false}
                       tickFormatter={v => `¥${(v/1000000).toFixed(1)}M`} />
                <Tooltip formatter={(v: number) => fmt.currency(v)} />
                <Bar dataKey="revenue" name="売上" fill="#d1fae5" radius={[4,4,0,0]} />
                <Bar dataKey="profit"  name="粗利" fill="#0c9069" radius={[4,4,0,0]} />
              </BarChart>
            </ResponsiveContainer>
          </div>

          {/* 資金繰り予測（共P-04の核心） */}
          <div className="card">
            <h2 className="text-sm font-semibold text-gray-800 mb-1">資金繰り計画（3ヶ月予測）</h2>
            <p className="text-xs text-gray-400 mb-4">共P-04：資金繰り計画・CMS</p>
            <div className="space-y-3 mb-4">
              {CASHFLOW_FORECAST.map(m => (
                <div key={m.month} className="flex items-center gap-3">
                  <span className="text-xs text-gray-500 w-8">{m.month}</span>
                  <div className="flex-1">
                    <div className="flex justify-between text-xs mb-1">
                      <span className="text-green-600">入金: {fmt.currency(m.inflow)}</span>
                      <span className="text-red-500">支出: {fmt.currency(m.outflow)}</span>
                    </div>
                    <div className="w-full bg-gray-100 rounded-full h-2">
                      <div className="bg-brand-500 h-2 rounded-full"
                        style={{ width: `${Math.round(m.balance / m.inflow * 100)}%` }} />
                    </div>
                  </div>
                  <span className="text-xs font-medium text-brand-600 w-20 text-right">
                    {fmt.currency(m.balance)}
                  </span>
                </div>
              ))}
            </div>
            <div className="p-2 bg-brand-50 rounded-lg text-xs text-brand-700 text-center">
              3ヶ月後推定手元資金: <span className="font-semibold">¥7,700,000</span>
            </div>
          </div>
        </div>

        {/* 顧客別売上 */}
        <div className="card">
          <h2 className="text-sm font-semibold text-gray-800 mb-4">顧客別売上構成（累計）</h2>
          <div className="flex items-center gap-8">
            <ResponsiveContainer width={200} height={200}>
              <PieChart>
                <Pie data={CUSTOMER_PIE} cx="50%" cy="50%" innerRadius={50} outerRadius={80}
                     dataKey="value" paddingAngle={2}>
                  {CUSTOMER_PIE.map((_, i) => <Cell key={i} fill={COLORS[i]} />)}
                </Pie>
                <Tooltip formatter={(v: number) => fmt.currency(v)} />
              </PieChart>
            </ResponsiveContainer>
            <div className="flex-1 space-y-2">
              {CUSTOMER_PIE.map((item, i) => (
                <div key={item.name} className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <div className="w-2.5 h-2.5 rounded-sm" style={{ background: COLORS[i] }} />
                    <span className="text-xs text-gray-700">{item.name}</span>
                  </div>
                  <div className="text-xs text-right">
                    <span className="font-medium text-gray-800">{fmt.currency(item.value)}</span>
                    <span className="text-gray-400 ml-2">
                      {Math.round(item.value / CUSTOMER_PIE.reduce((s,c)=>s+c.value,0) * 100)}%
                    </span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

      </div>
    </AppLayout>
  )
}
