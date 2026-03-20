-- ================================================================
-- SprintDesk Database Schema
-- DB Name: sprintdesk_db  |  All tables prefixed tf_
-- ================================================================
-- HOW TO IMPORT:
-- 1. Open phpMyAdmin → http://localhost/phpmyadmin
-- 2. Click "New" on left sidebar → name it: sprintdesk_db → Create
-- 3. Click sprintdesk_db → click "SQL" tab
-- 4. Paste this entire file → click Go
-- ================================================================

CREATE DATABASE IF NOT EXISTS sprintdesk_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE sprintdesk_db;

-- ---- USERS ----
CREATE TABLE tf_users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100)  NOT NULL,
    email          VARCHAR(150)  NOT NULL UNIQUE,
    password       VARCHAR(255)  DEFAULT NULL,
    role           ENUM('admin','manager','developer') DEFAULT 'developer',
    oauth_provider ENUM('local','google','github')     DEFAULT 'local',
    oauth_id       VARCHAR(255)  DEFAULT NULL,
    avatar         VARCHAR(500)  DEFAULT NULL,
    theme          ENUM('light','dark') DEFAULT 'light',
    is_active      TINYINT(1)    DEFAULT 1,
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ---- PROJECTS ----
CREATE TABLE tf_projects (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    description TEXT,
    code        VARCHAR(10)  NOT NULL UNIQUE,
    color       VARCHAR(7)   DEFAULT '#00C896',
    status      ENUM('active','completed','archived') DEFAULT 'active',
    created_by  INT UNSIGNED DEFAULT NULL,
    manager_id  INT UNSIGNED DEFAULT NULL,
    start_date  DATE DEFAULT NULL,
    end_date    DATE DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES tf_users(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES tf_users(id) ON DELETE SET NULL
);

-- ---- PROJECT MEMBERS ----
CREATE TABLE tf_project_members (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    joined_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES tf_users(id)    ON DELETE CASCADE
);

-- ---- SPRINTS ----
CREATE TABLE tf_sprints (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id  INT UNSIGNED NOT NULL,
    name        VARCHAR(100) NOT NULL,
    goal        TEXT,
    status      ENUM('planning','active','completed') DEFAULT 'planning',
    start_date  DATE DEFAULT NULL,
    end_date    DATE DEFAULT NULL,
    created_by  INT UNSIGNED DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES tf_users(id)    ON DELETE SET NULL
);

-- ---- TASKS ----
CREATE TABLE tf_tasks (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id   INT UNSIGNED NOT NULL,
    sprint_id    INT UNSIGNED DEFAULT NULL,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    type         ENUM('story','bug','task','epic') DEFAULT 'task',
    status       ENUM('todo','inprogress','review','done') DEFAULT 'todo',
    priority     ENUM('low','medium','high','critical') DEFAULT 'medium',
    assigned_to  INT UNSIGNED DEFAULT NULL,
    created_by   INT UNSIGNED DEFAULT NULL,
    story_points TINYINT UNSIGNED DEFAULT 0,
    due_date     DATE DEFAULT NULL,
    position     SMALLINT DEFAULT 0,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id)  REFERENCES tf_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (sprint_id)   REFERENCES tf_sprints(id)  ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES tf_users(id)    ON DELETE SET NULL,
    FOREIGN KEY (created_by)  REFERENCES tf_users(id)    ON DELETE SET NULL
);

-- ---- COMMENTS ----
CREATE TABLE tf_comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    content    TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tf_tasks(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES tf_users(id) ON DELETE CASCADE
);

-- ---- ACTIVITY ----
CREATE TABLE tf_activity (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED DEFAULT NULL,
    project_id  INT UNSIGNED DEFAULT NULL,
    task_id     INT UNSIGNED DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50)  NOT NULL,
    entity_id   INT UNSIGNED DEFAULT NULL,
    old_value   TEXT,
    new_value   TEXT,
    ip          VARCHAR(45)  DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES tf_users(id)    ON DELETE SET NULL,
    FOREIGN KEY (project_id) REFERENCES tf_projects(id) ON DELETE CASCADE
);

-- ---- NOTIFICATIONS ----
CREATE TABLE tf_notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(255) NOT NULL,
    message    TEXT,
    type       ENUM('task','sprint','comment','system') DEFAULT 'task',
    is_read    TINYINT(1) DEFAULT 0,
    link       VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES tf_users(id) ON DELETE CASCADE
);

-- ================================================================
-- DEMO DATA  (password = "password" for all accounts)
-- ================================================================
INSERT INTO tf_users (name, email, password, role, theme) VALUES
('Alex Admin',    'admin@sprintdesk.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',     'light'),
('Maria Manager', 'manager@sprintdesk.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager',   'light'),
('Dev One',       'dev1@sprintdesk.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'developer', 'light'),
('Dev Two',       'dev2@sprintdesk.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'developer', 'dark');

INSERT INTO tf_projects (name, description, code, created_by, manager_id, color, status) VALUES
('E-Commerce Platform', 'Full-stack shopping app with cart and payments', 'ECP', 1, 2, '#00C896', 'active'),
('Analytics Dashboard',  'Real-time charts and reporting module',          'ADB', 1, 2, '#FF5757', 'active'),
('Mobile App API',        'REST API backend for iOS and Android apps',      'MAA', 1, 2, '#FFB020', 'active');

INSERT INTO tf_project_members (project_id, user_id) VALUES
(1,1),(1,2),(1,3),(1,4),(2,1),(2,2),(2,3),(3,1),(3,2),(3,4);

INSERT INTO tf_sprints (project_id, name, goal, status, start_date, end_date, created_by) VALUES
(1, 'Sprint 1 — Foundation',    'Auth, DB schema and base UI',           'completed', '2025-01-01', '2025-01-14', 2),
(1, 'Sprint 2 — Core Features', 'Product listing, cart and checkout',    'active',    '2025-01-15', '2025-01-28', 2),
(2, 'Sprint 1 — Charts',        'Build chart components and data layer', 'active',    '2025-01-10', '2025-01-24', 2);

INSERT INTO tf_tasks (project_id, sprint_id, title, type, status, priority, assigned_to, created_by, story_points) VALUES
(1, 2, 'Product listing page',    'story', 'inprogress', 'high',     3, 2, 5),
(1, 2, 'Shopping cart API',       'task',  'todo',       'high',     4, 2, 3),
(1, 2, 'Stripe payment gateway',  'story', 'todo',       'critical', 3, 2, 8),
(1, 2, 'Fix login redirect bug',  'bug',   'review',     'high',     3, 2, 2),
(1, 2, 'User profile page',       'story', 'done',       'medium',   4, 2, 3),
(2, 3, 'Bar chart component',     'task',  'inprogress', 'high',     3, 2, 5),
(2, 3, 'Date range filter',       'story', 'todo',       'medium',   4, 2, 3),
(2, 3, 'Export to PDF',           'task',  'done',       'low',      3, 2, 4);

INSERT INTO tf_activity (user_id, project_id, action, entity_type) VALUES
(2,1,'created sprint','sprint'),
(3,1,'updated status to inprogress','task'),
(4,1,'completed task','task'),
(2,2,'created project','project');
