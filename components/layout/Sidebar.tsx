'use client'
import Link from 'next/link'
import { usePathname } from 'next/navigation'
import {
  LayoutDashboard, Users, Briefcase, FileText,
  CreditCard, AlertCircle, GitPullRequest, BarChart3,
  Settings, ChevronRight
} from 'lucide-react'

const NAV = [
  { href: '/dashboard',  icon: LayoutDashboard, label: 'ダッシュボード' },
  { href: '/customers',  icon: Users,            label: '顧客管理',   badge: '共P-01' },
  { href: '/deals',      icon: Briefcase,        label: '商談管理',   badge: '共P-01' },
  { href: '/invoices',   icon: FileText,         label: '請求書管理', badge: '共P-02' },
  { href: '/payments',   icon: CreditCard,       label: '入金消込',   badge: '共P-02' },
  { href: '/dunning',    icon: AlertCircle,      label: '督促管理',   badge: '共P-02' },
  { href: '/workflow',   icon: GitPullRequest,   label: 'ワークフロー', badge: '共P-05' },
  { href: '/analytics',  icon: BarChart3,        label: '経営分析',   badge: '共P-04' },
]

export default function Sidebar() {
  const path = usePathname()

  return (
    <aside style={{ width: 'var(--sidebar-w)' }}
      className="fixed left-0 top-0 h-full bg-white border-r border-gray-100 flex flex-col z-40">

      {/* ロゴ */}
      <div className="px-5 py-5 border-b border-gray-100">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 bg-brand-600 rounded-lg flex items-center justify-center">
            <span className="text-white text-xs font-bold">CS</span>
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-900 leading-tight">CLAUDE SUITE</p>
            <p className="text-[10px] text-gray-400">統合業務管理</p>
          </div>
        </div>
      </div>

      {/* ナビゲーション */}
      <nav className="flex-1 py-3 overflow-y-auto">
        {NAV.map(({ href, icon: Icon, label, badge }) => {
          const active = path.startsWith(href)
          return (
            <Link key={href} href={href}
              className={`flex items-center gap-3 px-4 py-2.5 mx-2 rounded-lg text-sm transition-colors group
                ${active
                  ? 'bg-brand-50 text-brand-700 font-medium'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'}`}>
              <Icon size={16} className={active ? 'text-brand-600' : 'text-gray-400 group-hover:text-gray-600'} />
              <span className="flex-1">{label}</span>
              {badge && (
                <span className={`text-[9px] px-1.5 py-0.5 rounded font-mono
                  ${active ? 'bg-brand-100 text-brand-600' : 'bg-gray-100 text-gray-400'}`}>
                  {badge}
                </span>
              )}
            </Link>
          )
        })}
      </nav>

      {/* 設定 */}
      <div className="border-t border-gray-100 p-3">
        <Link href="/settings"
          className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-colors">
          <Settings size={15} />
          <span>設定</span>
        </Link>
      </div>
    </aside>
  )
}
