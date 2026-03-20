-- ================================================================
-- SprintDesk Database Migration v2
-- Adds Organization Manager role and Organization-based isolation
-- ================================================================

USE sprintdesk_db;

-- 1. Create Organizations Table
CREATE TABLE IF NOT EXISTS tf_organizations (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(150) NOT NULL,
    org_key    VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Insert Default Organization
-- All existing data will be moved here initially.
INSERT INTO tf_organizations (name) VALUES ('Default Organization');
SET @default_org_id = LAST_INSERT_ID();

-- 3. Modify Users Table
-- Add org_id and update role enum
ALTER TABLE tf_users
    ADD COLUMN org_id INT UNSIGNED DEFAULT NULL AFTER id,
    MODIFY COLUMN role ENUM('admin','manager','developer','org_manager') DEFAULT 'developer',
    ADD CONSTRAINT fk_user_org FOREIGN KEY (org_id) REFERENCES tf_organizations(id) ON DELETE SET NULL;

-- Assign all existing users to the default organization
UPDATE tf_users SET org_id = @default_org_id WHERE org_id IS NULL;

-- 4. Modify Projects Table
-- Add org_id
ALTER TABLE tf_projects
    ADD COLUMN org_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD CONSTRAINT fk_project_org FOREIGN KEY (org_id) REFERENCES tf_organizations(id) ON DELETE CASCADE;

-- Assign all existing projects to the default organization
UPDATE tf_projects SET org_id = @default_org_id WHERE org_id IS NULL;

-- 5. Create the unique Organization Manager (one per app)
-- We will create a placeholder or update an existing one if needed.
-- For now, let's create a new one:
INSERT INTO tf_users (name, email, password, role, theme, org_id) VALUES
('Global Org Manager', 'orgmanager@sprintdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'org_manager', 'light', NULL);
-- Note: Global Org Manager has org_id = NULL because they manage ALL orgs.
