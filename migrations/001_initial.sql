CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','manager','viewer') NOT NULL DEFAULT 'viewer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(160),
  phone VARCHAR(40),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  cpf_cnpj VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(160),
  phone VARCHAR(40),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cost_centers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255),
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS account_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  kind ENUM('receita','despesa') NOT NULL,
  cost_center_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_account_type_cost_center FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id)
);

CREATE TABLE IF NOT EXISTS accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_type_id INT NOT NULL,
  party_type ENUM('customer','supplier') NOT NULL,
  party_id INT NOT NULL,
  description VARCHAR(255),
  total_amount DECIMAL(15,2) NOT NULL,
  due_start_date DATE NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  direction ENUM('receita','despesa') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_accounts_type FOREIGN KEY (account_type_id) REFERENCES account_types(id),
  INDEX idx_accounts_party (party_type, party_id)
);

CREATE TABLE IF NOT EXISTS installments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  number INT NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  status ENUM('pending','paid') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  payment_method VARCHAR(50) NULL,
  CONSTRAINT fk_installments_account FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_account_installment (account_id, number)
);

