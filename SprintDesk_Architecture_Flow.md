# SprintDesk Architecture & Data Flow Documentation

This document provides a comprehensive analysis of how SprintDesk operates from end-to-end, including registration flows, role hierarchies, database triggers, email automation, and integrated security features.

---

## 1. The Core Hierarchy & Multi-Tenant Structure

SprintDesk operates on a strict **Multi-Tenant / Hierarchical model**. 
There are 4 distinct roles:
1. **Global Org Manager (`'org_manager'`)**: The supreme overseer who approves or rejects new companies. There is only one global manager.
2. **Admin (`'admin'`)**: The owner/IT-head of a specific organization. They manage the company's billing, configure the global Organization Key (`org_key`), create projects, and provision user accounts.
3. **Manager (`'manager'`)**: Project leads. They plan sprints, assign tasks, and build their project team from the pool of developers within the organization.
4. **Developer (`'developer'`)**: The workforce. They execute tasks, move Kanban cards, add comments, and push code.

*Every Admin, Manager, and Developer is isolated by an `org_id` tying them back to a specific company in `tf_organizations`.*

---

## 2. Step-by-Step Data Flows

### Flow A: Registering a New Organization
1. **The Request (`frontend/auth/register.php`)**: A user fills out the registration form from the landing page.
2. **Database Action**: The data (Company Name, Email, Password, Domain) is inserted into the `tf_org_requests` table with a status of `'pending'`. 
3. **Validation**: The system checks to ensure no duplicate requests or existing accounts share this email to prevent spam.

### Flow B: Global Approval
1. **The Action (`frontend/org_manager/organizations.php`)**: The Global Manager logs in and clicks "Approve" on a pending request.
2. **Database Action**:
   - A highly secure `ORG-KEY` (e.g., `ORG-A1B2C3D4`) is generated automatically.
   - The company is inserted into the `tf_organizations` table.
   - An Admin account is immediately created in the `tf_users` table for that organization using the requested credentials.
   - The `tf_org_requests` row is moved to `'approved'`.
3. **Automated Sub-Action**: `sendSystemEmail()` triggers, emailing the newly approved Admin their Organization ID and Organization Key, inviting them to log in.

### Flow C: Creating Users & Teams
1. **Admin creates Users (`frontend/admin/users.php`)**:
   - Admins manually provision Managers and Developers. They **do not** sign up themselves.
   - **Security Check**: The Admin *must* provide the exact `ORG-KEY` to authorize creation. Furthermore, the new user's email domain *must* match the company's registered domain (e.g., `@acme.com`).
   - **Database Action**: Insert into `tf_users` with the assigned role and the inherited `org_id`.
2. **Admin creates Projects (`frontend/admin/projects.php`)**:
   - The Admin creates a project (stored in `tf_projects`) and assigns a Manager (`manager_id`).
   - **Automated Sub-Action**: `sendSystemEmail()` emails the Manager that they are now leading a new project.
3. **Manager builds the Team (`frontend/manager/team.php`)**:
   - A Manager selects from available developers and adds them to their project.
   - **Database Action**: Inserts a mapping rule into `tf_project_members` (`project_id`, `user_id`).
   - **Automated Sub-Action**: An email alerts the developer they have been added to the team.

### Flow D: Sprints & Task Execution
1. **Planning Sprints (`frontend/manager/sprints.php`)**: Managers create Sprints (`tf_sprints`) linked to their project.
2. **Assigning Tasks (`backend/api/create_task.php`)**: 
   - A task is created and linked to a Sprint (`sprint_id`), Project (`project_id`), and User (`assigned_to`).
   - **Database Action**: Inserted into `tf_tasks`.
   - **Automated Sub-Actions**: An in-app notification is inserted into `tf_notifications`, an activity log is written to `tf_activity`, and an email is sent to the developer.
3. **Execution (`frontend/developer/kanban.php`)**:
   - Developers drag-and-drop tasks across the board (To Do -> In Progress -> Review -> Done).
   - They can leave updates stored in `tf_comments`.

### Flow E: GitHub Integration
1. **Configuration**: Admin links a `github_url` and a `github_pat` (Personal Access Token) to a project.
2. **Syncing (`backend/shared/includes/init.php`)**: When the dashboard is loaded, `getGitHubActivity()` fires.
3. **Database Action**: It uses cURL to hit the GitHub API, retrieves the latest commits using the token for authentication, and inserts the commit data using `INSERT IGNORE` into `tf_activity` as a `'pushed commit'` event, creating a central feed of both local task updates and remote code pushes.

### Flow F: Project Deletion Approval Workflow
1. **The Request (`frontend/admin/projects.php`)**: When an Admin clicks "Delete" on a project, they are presented with a modal to describe the `reason` for deletion. The submission sends an action `request_delete`.
2. **Database Action**: The script inserts the pending request into `tf_project_deletion_requests`. It fetches all the admins sharing the same `org_id`. It automatically logs an approval for the requester. For other admins, it creates pending rows in `tf_project_deletion_approvals` alongside securely generated cryptographic hex tokens.
3. **Automated Sub-Action**: `sendSystemEmail()` dispatches an email to all other admins displaying the project details and the reason. It embeds "Approve" and "Disapprove" HTML links mapping to `process_deletion.php?token=[TOKEN]`.
4. **Approval Processing (`frontend/admin/process_deletion.php`)**: When an admin clicks the link, the backend endpoint captures the secure token and validates the request. If action is `approve`, the total approval count increments. If the threshold dictates that `ceil(adminCount / 2)` (or `2` if two admins exist) approvals have been accumulated, the core project data is permanently purged and all relative data is `CASCADE` deleted.

---

## 3. Integrated Security Features

The platform employs multiple layers of security to ensure strict data isolation and protection:

1. **Strict Multi-Tenant SQL Isolation**: Every critical query where users alter or view data includes the clause `WHERE org_id = ?` to guarantee data from Company A never bleeds into Company B.
2. **Organization Key (2FA alternative)**: Admins cannot create projects or users without knowing the encrypted `org_key`. This acts as an internal secondary password to prevent rogue sessions from provisioning unauthorized accounts.
3. **Email Domain Validation**: When Admins provision users, the script strictly validates that the user's email (`user@domain.com`) matches the organization's verified domain. This prevents adding external unauthorized users.
4. **SQL Injection Prevention**: 100% of database queries utilize `PDO prepare()` and `execute()` bound parameters. There is no raw string concatenation used in queries.
5. **Cross-Site Scripting (XSS) Prevention**: Output rendered to the browser is passed through a custom `e()` helper function, which applies `htmlspecialchars` to sanitize potentially malicious inputs.
6. **Password Encryption**: All passwords are encrypted securely at rest using `password_hash()` (Bcrypt). They are impossible to reverse-engineer, even if the database is compromised.
7. **Role-Based Access Control (RBAC)**: Every page verifies the session via `requireLogin('role')`. If a developer tries to navigate to an admin URL (e.g., `admin/projects.php`), the script automatically kicks them out to the login screen with an unauthorized error.
8. **CSRF / Session protection**: Sessions are configured via `ini_set('session.cookie_httponly', 1)` in `init.php` preventing JavaScript from hijacking the session token.
