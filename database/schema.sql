-- ========================================
-- CLAUDE SUITE データベーススキーマ
-- Supabase (PostgreSQL) 用
-- ========================================
-- Supabaseダッシュボード > SQL Editor で実行してください

-- 顧客テーブル（共P-01：CRM）
CREATE TABLE customers (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  company_name VARCHAR(200) NOT NULL,
  contact_name VARCHAR(100),
  email VARCHAR(200),
  phone VARCHAR(50),
  industry VARCHAR(100),
  status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active','inactive','prospect')),
  total_sales BIGINT DEFAULT 0,
  memo TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 商談テーブル（共P-01：SFA）
CREATE TABLE deals (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  customer_id UUID REFERENCES customers(id) ON DELETE CASCADE,
  title VARCHAR(300) NOT NULL,
  amount BIGINT DEFAULT 0,
  stage VARCHAR(20) DEFAULT 'lead' CHECK (stage IN ('lead','proposal','negotiation','closing','won','lost')),
  probability INTEGER DEFAULT 0 CHECK (probability BETWEEN 0 AND 100),
  expected_close_date DATE,
  follow_date DATE,
  assignee VARCHAR(100),
  memo TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 請求書テーブル（共P-02：受発注機能・インボイス対応）
CREATE TABLE invoices (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_number VARCHAR(50) UNIQUE NOT NULL,
  customer_id UUID REFERENCES customers(id),
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  -- インボイス制度対応フィールド
  invoice_number_t VARCHAR(20) DEFAULT 'T1234567890123', -- 適格請求書発行事業者登録番号
  subtotal BIGINT DEFAULT 0,
  tax_rate_10 BIGINT DEFAULT 0,  -- 10%対象額
  tax_rate_8  BIGINT DEFAULT 0,  -- 8%対象額（軽減税率）
  tax_amount_10 BIGINT DEFAULT 0, -- 消費税額（10%）
  tax_amount_8  BIGINT DEFAULT 0, -- 消費税額（8%）
  total_amount BIGINT DEFAULT 0,
  -- 入金消込管理フィールド（共P-02：決済機能）
  total_received BIGINT DEFAULT 0,
  receivable_balance BIGINT DEFAULT 0,
  collection_status VARCHAR(20) DEFAULT 'unpaid'
    CHECK (collection_status IN ('unpaid','partial','paid','overdue','dunning','uncollectible')),
  overdue_date DATE,
  overdue_days INTEGER DEFAULT 0,
  last_dunning_at TIMESTAMPTZ,
  status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft','sent','paid','cancelled')),
  memo TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 請求書明細テーブル
CREATE TABLE invoice_items (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_id UUID REFERENCES invoices(id) ON DELETE CASCADE,
  description VARCHAR(300) NOT NULL,
  quantity NUMERIC(10,2) DEFAULT 1,
  unit_price BIGINT DEFAULT 0,
  tax_type VARCHAR(5) DEFAULT '10' CHECK (tax_type IN ('10','8','0')),
  amount BIGINT DEFAULT 0,
  sort_order INTEGER DEFAULT 0
);

-- 入金記録テーブル（共P-02：決済機能・入金消込）
CREATE TABLE payment_receipts (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_id UUID REFERENCES invoices(id) ON DELETE CASCADE,
  customer_id UUID REFERENCES customers(id),
  received_amount BIGINT NOT NULL,
  received_date DATE NOT NULL,
  payment_method VARCHAR(20) DEFAULT 'bank_transfer'
    CHECK (payment_method IN ('bank_transfer','credit_card','cash','other')),
  bank_name VARCHAR(100),
  bank_account VARCHAR(100),
  memo TEXT,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- 売掛消込記録テーブル
CREATE TABLE receivable_matchings (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  payment_receipt_id UUID REFERENCES payment_receipts(id) ON DELETE CASCADE,
  invoice_id UUID REFERENCES invoices(id),
  matched_amount BIGINT NOT NULL,
  status VARCHAR(20) DEFAULT 'matched' CHECK (status IN ('matched','partial','unmatched')),
  matched_at TIMESTAMPTZ DEFAULT NOW()
);

-- 督促記録テーブル（共P-02：決済機能・債権管理）
CREATE TABLE dunning_records (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  invoice_id UUID REFERENCES invoices(id) ON DELETE CASCADE,
  customer_id UUID REFERENCES customers(id),
  dunning_type VARCHAR(20) CHECK (dunning_type IN ('first','second','final','legal')),
  method VARCHAR(20) CHECK (method IN ('email','phone','mail','visit')),
  dunning_date DATE DEFAULT CURRENT_DATE,
  next_action_date DATE,
  content TEXT,
  response TEXT,
  result VARCHAR(20) DEFAULT 'pending'
    CHECK (result IN ('pending','promised','paid','disputed','uncollectible')),
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ワークフロー申請テーブル（共P-05：承認・決裁）
CREATE TABLE workflow_requests (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  request_type VARCHAR(30) CHECK (request_type IN ('purchase','expense','leave','contract','other')),
  amount BIGINT,
  content TEXT NOT NULL,
  requester_name VARCHAR(100),
  current_approver VARCHAR(100),
  status VARCHAR(20) DEFAULT 'pending'
    CHECK (status IN ('pending','approved','rejected','withdrawn')),
  created_at TIMESTAMPTZ DEFAULT NOW(),
  updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- 承認ステップテーブル
CREATE TABLE workflow_approvals (
  id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
  request_id UUID REFERENCES workflow_requests(id) ON DELETE CASCADE,
  step INTEGER NOT NULL,
  approver_name VARCHAR(100) NOT NULL,
  status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending','approved','rejected')),
  comment TEXT,
  acted_at TIMESTAMPTZ,
  created_at TIMESTAMPTZ DEFAULT NOW()
);

-- ========================================
-- インデックス
-- ========================================
CREATE INDEX idx_invoices_customer ON invoices(customer_id);
CREATE INDEX idx_invoices_collection_status ON invoices(collection_status);
CREATE INDEX idx_invoices_due_date ON invoices(due_date);
CREATE INDEX idx_deals_customer ON deals(customer_id);
CREATE INDEX idx_deals_stage ON deals(stage);
CREATE INDEX idx_payment_receipts_invoice ON payment_receipts(invoice_id);
CREATE INDEX idx_dunning_records_invoice ON dunning_records(invoice_id);
CREATE INDEX idx_workflow_requests_status ON workflow_requests(status);

-- ========================================
-- サンプルデータ
-- ========================================
INSERT INTO customers (company_name, contact_name, email, phone, industry, status, total_sales) VALUES
('株式会社ABC商事',    '田中太郎', 'tanaka@abc.co.jp',    '03-1234-5678', '製造業',     'active',   2450000),
('XYZ株式会社',        '佐藤花子', 'sato@xyz.co.jp',      '03-2345-6789', 'IT・通信',   'active',   1890000),
('DEFコーポレーション','鈴木一郎', 'suzuki@def.com',       '06-3456-7890', '小売業',     'inactive', 0),
('株式会社GHI製作所',  '高橋美咲', 'takahashi@ghi.jp',    '052-456-7890', '製造業',     'active',   3200000),
('STUインダストリーズ','小林誠',   'kobayashi@stu.co',    '045-890-1234', '製造業',     'active',   4120000);

-- ========================================
-- Row Level Security（本番環境では必ず設定）
-- ========================================
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices  ENABLE ROW LEVEL SECURITY;
ALTER TABLE deals     ENABLE ROW LEVEL SECURITY;

-- 開発中は全アクセス許可（本番前に要変更）
CREATE POLICY "allow_all" ON customers FOR ALL USING (true);
CREATE POLICY "allow_all" ON invoices  FOR ALL USING (true);
CREATE POLICY "allow_all" ON deals     FOR ALL USING (true);
CREATE POLICY "allow_all" ON payment_receipts  FOR ALL USING (true);
CREATE POLICY "allow_all" ON dunning_records   FOR ALL USING (true);
CREATE POLICY "allow_all" ON workflow_requests FOR ALL USING (true);
CREATE POLICY "allow_all" ON workflow_approvals FOR ALL USING (true);
