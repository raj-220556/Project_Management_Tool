# SprintDesk ⚡

SprintDesk is a modern, lightweight Project Management and Issue Tracking application built for agile software teams. Designed with a premium "Nordic Glassmorphism" aesthetic, it acts as a streamlined alternative to cumbersome enterprise tools like Jira, bringing your code and project management under one unified roof.

## Why SprintDesk?
Managing software projects often means hopping between a Kanban board and GitHub to understand what's actually happening in the codebase. SprintDesk bridges that gap by seamlessly integrating high-level project management with deep Git activity insights.

It's perfect for:
- **Small to Medium Engineering Teams** looking to streamline agile rituals (sprints, tasks).
- **Managers & PMs** who want immediate visibility into pull requests, deployments, and developer velocity without digging into the terminal.
- **Developers** who want their tools to feel incredibly fast, responsive, and beautiful.

## Key Features

### 🏢 Organization & Access Control
- **Multi-Tenant Architecture:** Support for entire organizations containing multiple projects.
- **Role-Based Access:** First-class roles for *Global Organiser, Admin, Manager,* and *Developer*, ensuring data security and proper visibility boundaries.

### 📋 Agile Project Management
- **Boards & Sprints:** Organize tasks into iterative sprints or leave them in a general backlog.
- **Kanban Flow:** Drag-and-drop Kanban boards tailored for fluid workflow management.
- **Rich Task Context:** Every task tracks Priority, Story Points, Due Dates, and Assignees.

### 🐙 Deep Git Integration
- **Global Project Pulse:** View a realtime feed of Commits, Pull Requests, and Branch creations synced directly from GitHub via PAT tokens.
- **Task-Level Git Context:** Tasks automatically detect Linked Branches, Code Diffs, and display module "Ownership" (Blame) based on developer contributions.
- **Actionable Triggers:** Create branches, revert commits, or simulate cherry-picks directly from the dashboard.
- **Insights & Health:** Identify file "Hotspots" (high churn areas) and view Contributor Leaderboards.

### 🎨 State of the Art UI
- **Nordic Glassmorphism:** A breathtaking design system leveraging backdrop blurs, subtle gradients, and dark/light mode toggles.
- **Lightning Fast Live Search:** Find tasks, projects, or users instantly via global and localized search.
- **Micro-Animations:** Fluid transitions, toast notifications, and even celebration confetti plugins for an engaging user experience!

---

## Architecture
- **Frontend:** Vanilla HTML5, CSS3 Variables, JavaScript (ES6+). No heavy JavaScript frameworks needed!
- **Backend:** Pure PHP handles secure routing, lightweight API endpoints, and authentication sessions.
- **Database:** MySQL relational mapping for seamless joins across Orgs, Projects, Tasks, and Github metrics.

---
*Ready to get started? Check out the [SETUP.md](SETUP.md) guide.*