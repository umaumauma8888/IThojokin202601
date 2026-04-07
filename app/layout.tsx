import type { Metadata } from 'next'
import './globals.css'

export const metadata: Metadata = {
  title: 'CLAUDE SUITE | 統合業務管理システム',
  description: 'インボイス対応・CRM・SFA・請求・入金消込・ワークフロー統合システム',
}

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ja">
      <body>{children}</body>
    </html>
  )
}
