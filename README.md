# CLAUDE SUITE
## 統合業務管理システム（デジタル化・AI導入補助金2026 対応）

---

## 補助金対応プロセス一覧

### インボイス対応類型：2機能対応
| 機能 | 根拠 | 実装箇所 |
|------|------|---------|
| **受発注機能** | 登録要領P.11(イ)：適格請求書①〜⑥全要件・電磁的保存 | `app/invoices/` |
| **決済機能** | 登録要領P.11(ウ)：商品売買に伴う債権債務管理業務の負担解消 | `app/payments/` `app/dunning/` |

### 通常枠：4プロセス対応
| プロセス | 機能 | 実装箇所 |
|---------|------|---------|
| **共P-01** | 顧客管理（CRM）・商談管理（SFA） | `app/customers/` `app/deals/` |
| **共P-02** | 請求管理・入金消込・督促・債権管理 | `app/invoices/` `app/payments/` `app/dunning/` |
| **共P-04** | 資金繰り計画・管理会計・経営分析 | `app/analytics/` |
| **共P-05** | ワークフロー承認・社内統制 | `app/workflow/` |

---

## 技術スタック
- **フロントエンド**: Next.js 14 (App Router) + TypeScript + Tailwind CSS
- **データベース**: Supabase (PostgreSQL)
- **デプロイ**: Vercel

---

## セットアップ手順

### 1. Supabaseプロジェクトの作成
1. https://supabase.com にアクセス
2. 「New project」でプロジェクト作成
3. `database/schema.sql` の内容を **SQL Editor** で実行

### 2. 環境変数の設定
```bash
cp .env.local.example .env.local
```
`.env.local` を開いて以下を設定：
```
NEXT_PUBLIC_SUPABASE_URL=https://your-project.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=your-anon-key
```
（Supabaseダッシュボード > Settings > API から取得）

### 3. ローカル起動
```bash
npm install
npm run dev
```
http://localhost:3000 にアクセス

### 4. Vercelデプロイ
```bash
# Vercelに環境変数を設定
vercel env add NEXT_PUBLIC_SUPABASE_URL
vercel env add NEXT_PUBLIC_SUPABASE_ANON_KEY

# デプロイ
vercel --prod
```

または GitHub連携で自動デプロイ：
1. Vercelダッシュボード > 「Add New Project」
2. GitHubリポジトリを選択
3. Environment Variables に上記2つを設定
4. 「Deploy」

---

## 画面一覧
| URL | 画面名 | プロセス |
|-----|--------|---------|
| `/dashboard`  | ダッシュボード | 全体 |
| `/customers`  | 顧客管理（CRM） | 共P-01 |
| `/deals`      | 商談管理（SFA） | 共P-01 |
| `/invoices`   | 請求書管理（インボイス対応） | 共P-02 |
| `/payments`   | 入金消込・債権管理 | 共P-02 |
| `/dunning`    | 督促管理 | 共P-02 |
| `/workflow`   | ワークフロー承認 | 共P-05 |
| `/analytics`  | 経営分析・資金繰り計画 | 共P-04 |
