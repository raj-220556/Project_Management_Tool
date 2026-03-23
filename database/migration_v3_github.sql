-- ================================================================
-- SprintDesk Database Migration v3 (GitHub Integration)
-- ================================================================

USE sprintdesk_db;

-- 1. Modify Projects Table
-- Add github_pat
ALTER TABLE tf_projects
    ADD COLUMN github_pat VARCHAR(255) DEFAULT NULL AFTER github_url;

-- 2. Create GitHub Commits Table (for detailed insights like hotspots and leaderboards)
CREATE TABLE IF NOT EXISTS tf_github_commits (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id    INT UNSIGNED NOT NULL,
    sha           VARCHAR(40) NOT NULL,
    message       TEXT,
    author_name   VARCHAR(150),
    author_email  VARCHAR(150),
    author_avatar VARCHAR(500),
    commit_date   DATETIME,
    additions     INT DEFAULT 0,
    deletions     INT DEFAULT 0,
    files_changed JSON,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_commit (project_id, sha),
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE
);

-- 3. Create GitHub Branches Table
CREATE TABLE IF NOT EXISTS tf_github_branches (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id      INT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,
    last_commit_sha VARCHAR(40),
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_branch (project_id, name),
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE
);

-- 4. Create GitHub Pull Requests Table
CREATE TABLE IF NOT EXISTS tf_github_prs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id    INT UNSIGNED NOT NULL,
    pr_number     INT UNSIGNED NOT NULL,
    title         VARCHAR(255),
    state         ENUM('open', 'closed', 'merged', 'draft') DEFAULT 'open',
    author_name   VARCHAR(150),
    author_avatar VARCHAR(500),
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pr (project_id, pr_number),
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE
);
