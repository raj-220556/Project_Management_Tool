-- ================================================================
-- SprintDesk Database Migration v5
-- Adds Project Deletion Approval Workflow
-- ================================================================

USE sprintdesk_db;

CREATE TABLE IF NOT EXISTS tf_project_deletion_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    requester_id INT UNSIGNED NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (requester_id) REFERENCES tf_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tf_project_deletion_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    status ENUM('pending', 'approved', 'disapproved') DEFAULT 'pending',
    acted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (request_id) REFERENCES tf_project_deletion_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES tf_users(id) ON DELETE CASCADE
);
