import { createBrowserClient } from '@supabase/ssr'

export function createClient() {
  return createBrowserClient(
    process.env.NEXT_PUBLIC_SUPABASE_URL!,
    process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY!
  )
}

// ==================== 型定義 ====================

export type Customer = {
  id: string
  company_name: string
  contact_name: string
  email: string
  phone: string | null
  industry: string | null
  status: 'active' | 'inactive' | 'prospect'
  total_sales: number
  created_at: string
}

export type Deal = {
  id: string
  customer_id: string
  title: string
  amount: number
  stage: 'lead' | 'proposal' | 'negotiation' | 'closing' | 'won' | 'lost'
  probability: number
  expected_close_date: string
  assigned_to: string | null
  created_at: string
  customer?: Customer
}

export type Invoice = {
  id: string
  invoice_number: string
  customer_id: string
  issue_date: string
  due_date: string
  subtotal: number
  tax_rate_10: number
  tax_rate_8: number
  tax_amount_10: number
  tax_amount_8: number
  total_amount: number
  total_received: number
  receivable_balance: number
  collection_status: 'unpaid' | 'partial' | 'paid' | 'overdue' | 'dunning'
  overdue_days: number
  invoice_number_t: string  // 適格請求書発行事業者登録番号
  status: 'draft' | 'sent' | 'paid' | 'cancelled'
  created_at: string
  customer?: Customer
}

export type InvoiceItem = {
  id: string
  invoice_id: string
  description: string
  quantity: number
  unit_price: number
  tax_type: '10' | '8' | '0'
  amount: number
}

export type PaymentReceipt = {
  id: string
  invoice_id: string
  customer_id: string
  received_amount: number
  received_date: string
  payment_method: 'bank_transfer' | 'credit_card' | 'cash' | 'other'
  bank_name: string | null
  memo: string | null
  created_at: string
  invoice?: Invoice
}

export type DunningRecord = {
  id: string
  invoice_id: string
  customer_id: string
  dunning_type: 'first' | 'second' | 'final'
  method: 'email' | 'phone' | 'mail'
  dunning_date: string
  result: 'pending' | 'promised' | 'paid' | 'disputed'
  response: string | null
  created_at: string
}

export type WorkflowRequest = {
  id: string
  title: string
  request_type: 'purchase' | 'expense' | 'leave' | 'other'
  amount: number | null
  content: string
  status: 'pending' | 'approved' | 'rejected' | 'withdrawn'
  current_approver: string | null
  requester_id: string
  created_at: string
  approvals?: WorkflowApproval[]
}

export type WorkflowApproval = {
  id: string
  request_id: string
  approver_name: string
  step: number
  status: 'pending' | 'approved' | 'rejected'
  comment: string | null
  acted_at: string | null
}

// ==================== フォーマット関数 ====================

export const fmt = {
  currency: (n: number) => `¥${n.toLocaleString('ja-JP')}`,
  date: (s: string) => new Date(s).toLocaleDateString('ja-JP'),
  percent: (n: number) => `${n}%`,
}

export const STATUS_LABELS = {
  collection: {
    unpaid:  { label: '未入金',   color: 'bg-gray-100 text-gray-700' },
    partial: { label: '一部入金', color: 'bg-blue-100 text-blue-700' },
    paid:    { label: '入金済',   color: 'bg-green-100 text-green-700' },
    overdue: { label: '期日超過', color: 'bg-red-100 text-red-700' },
    dunning: { label: '督促中',   color: 'bg-orange-100 text-orange-700' },
  },
  deal: {
    lead:        { label: 'リード',   color: 'bg-gray-100 text-gray-700' },
    proposal:    { label: '提案中',   color: 'bg-blue-100 text-blue-700' },
    negotiation: { label: '交渉中',   color: 'bg-yellow-100 text-yellow-700' },
    closing:     { label: 'クロージング', color: 'bg-orange-100 text-orange-700' },
    won:         { label: '受注',     color: 'bg-green-100 text-green-700' },
    lost:        { label: '失注',     color: 'bg-red-100 text-red-700' },
  },
  workflow: {
    pending:   { label: '承認待ち', color: 'bg-yellow-100 text-yellow-700' },
    approved:  { label: '承認済',   color: 'bg-green-100 text-green-700' },
    rejected:  { label: '否認',     color: 'bg-red-100 text-red-700' },
    withdrawn: { label: '取下げ',   color: 'bg-gray-100 text-gray-700' },
  },
}
