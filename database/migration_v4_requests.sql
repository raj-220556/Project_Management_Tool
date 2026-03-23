-- ================================================================
-- SprintDesk Database Migration v4
-- Adds Company Registration Requests and Organisation Info
-- ================================================================

USE sprintdesk_db;

-- 1. Add columns to tf_organizations
ALTER TABLE tf_organizations
    ADD COLUMN address TEXT DEFAULT NULL,
    ADD COLUMN domain VARCHAR(150) DEFAULT NULL;

-- 2. Create tf_org_requests table
CREATE TABLE IF NOT EXISTS tf_org_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    org_name    VARCHAR(150) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    address     TEXT DEFAULT NULL,
    domain      VARCHAR(150) DEFAULT NULL,
    status      ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
