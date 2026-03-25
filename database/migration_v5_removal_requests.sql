USE sprintdesk_db;

CREATE TABLE IF NOT EXISTS tf_member_removal_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    manager_id  INT UNSIGNED NOT NULL,
    status      ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES tf_users(id)    ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES tf_users(id)    ON DELETE CASCADE
);
