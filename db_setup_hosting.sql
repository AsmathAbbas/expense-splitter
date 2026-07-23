-- ============================================
-- Group Expense Splitter - Database Schema
-- FOR REMOTE HOSTING (InfinityFree, ByetHost, GoogieHost, etc.)
-- ============================================

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE expense_groups (
  group_id INT AUTO_INCREMENT PRIMARY KEY,
  group_name VARCHAR(100) NOT NULL,
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE group_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES expense_groups(group_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY unique_membership (group_id, user_id)
);

CREATE TABLE expenses (
  expense_id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  paid_by INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  category VARCHAR(50) DEFAULT 'General',
  split_type ENUM('equal','percentage','custom') DEFAULT 'equal',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES expense_groups(group_id) ON DELETE CASCADE,
  FOREIGN KEY (paid_by) REFERENCES users(user_id)
);

CREATE TABLE expense_splits (
  split_id INT AUTO_INCREMENT PRIMARY KEY,
  expense_id INT NOT NULL,
  user_id INT NOT NULL,
  share_amount DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (expense_id) REFERENCES expenses(expense_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE group_invites (
  invite_id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  invited_user_id INT NOT NULL,
  invited_by INT NOT NULL,
  status ENUM('pending','accepted','declined') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES expense_groups(group_id) ON DELETE CASCADE,
  FOREIGN KEY (invited_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (invited_by) REFERENCES users(user_id),
  UNIQUE KEY unique_invite (group_id, invited_user_id)
);

CREATE TABLE settlements (
  settlement_id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  paid_by INT NOT NULL,
  paid_to INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','completed') DEFAULT 'completed',
  settled_at TIMESTAMP NULL,
  FOREIGN KEY (group_id) REFERENCES expense_groups(group_id) ON DELETE CASCADE,
  FOREIGN KEY (paid_by) REFERENCES users(user_id),
  FOREIGN KEY (paid_to) REFERENCES users(user_id)
);