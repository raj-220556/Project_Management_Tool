# End-to-End Setup Guide for SprintDesk 🚀

This is the complete, step-by-step guide to installing and running the SprintDesk project from scratch on your local machine.

## Phase 1: Install Prerequisites

Before starting, your machine needs a local web server to run PHP and host MySQL databases. The easiest way to get this is by installing XAMPP.

1. **Download XAMPP**: Go to [apachefriends.org](https://www.apachefriends.org/) and download the latest XAMPP installer for your OS (Windows, Mac, or Linux). Ensure it includes PHP version 8.0+.
2. **Install XAMPP**: Run the installer and accept the default settings. 
3. **Enable cURL (Windows/Mac)**: SprintDesk requires the PHP cURL extension to communicate with GitHub.
   - Go to your XAMPP installation folder.
   - Find `php.ini` (usually in `C:\xampp\php\php.ini` or `/opt/lampp/etc/php.ini`).
   - Open it, search for `;extension=curl`, and remove the semicolon `;` at the front to enable it.
   - Save the file.

## Phase 2: Start Your Servers

1. Open the **XAMPP Control Panel**.
2. Click **Start** next to **Apache** (your web server).
3. Click **Start** next to **MySQL** (your database server).
4. *Verify:* Both services should turn green. You should now be able to visit `http://localhost/` in your browser and see the XAMPP dashboard.

## Phase 3: Clone the Repository

SprintDesk strictly expects its root directory to be named `sprintdesk` because of relative routing paths in the application.

1. Open your terminal or git bash.
2. Navigate to your XAMPP web root folder:
   - **Windows:** `cd C:\xampp\htdocs\`
   - **Mac:** `cd /Applications/XAMPP/htdocs/`
   - **Linux:** `cd /opt/lampp/htdocs/`
3. Clone the repository directly into a folder named `sprintdesk`:
   ```bash
   git clone <your-repository-url-here> sprintdesk
   ```
   *(If you downloaded a ZIP instead, extract it and rename the folder to `sprintdesk`, then drag it into the `htdocs` folder.)*

## Phase 4: Setting up the Database

1. Open your browser and go to **http://localhost/phpmyadmin**.
2. Click the **Databases** tab at the top.
3. Under *Create database*, enter `sprintdesk_db` and click **Create**.
4. Now, we need to inject the tables and initial data. Click on your newly created `sprintdesk_db` database in the left sidebar.
5. Click the **Import** tab at the top.
6. Click **Choose File** and navigate to your project folder: `htdocs/sprintdesk/database/schema.sql`.
7. Click **Import** at the bottom of the page.
8. Once successful, repeat the Import process for the GitHub migration file: `htdocs/sprintdesk/database/migration_v3_github.sql` (to ensure the Git Activity tables are created).

## Phase 5: Environment Variables (.env)

SprintDesk securely manages credentials via an environment configuration file.

1. Navigate to the root of your project folder (`htdocs/sprintdesk`).
2. You will see a file named `.env.example`.
3. Rename this file to exactly `.env` (make sure there is no `.txt` hidden at the end).
4. Open the `.env` file in a text editor (like VSCode or Notepad).
5. Ensure the database credentials match your XAMPP setup (XAMPP by default has `root` as the username and an empty password):
   ```env
   # Database Configuration
   DB_HOST=127.0.0.1
   DB_NAME=sprintdesk_db
   DB_USER=root
   DB_PASS=

   # App Secrets
   APP_ENV=development
   
   # GitHub Integration (Optional but recommended)
   # Generate a Classic PAT at https://github.com/settings/tokens
   GITHUB_TOKEN=ghp_your_secret_pat_here
   ```

## Phase 6: Run the Application!

Everything is set up!

1. Open your browser and navigate to: **http://localhost/sprintdesk/**
2. You will see the beautiful landing page.
3. Click **Log In** in the top right.

### Default Login Credentials
Since you imported the `schema.sql`, default users have been created for testing:
- **Global Organiser Role:** `sysadmin@sprintdesk.com` | Password: `password123`
- **Admin Role:** `admin@example.com` | Password: `password123`
- **Manager Role:** `manager@example.com` | Password: `password123`
- **Developer Role:** `dev1@example.com` | Password: `password123`

To test the **Git Activity** features, login as an Admin, navigate to **Projects**, create a new project with a valid GitHub repository URL, and enter your Personal Access Token in the modal (or rely on the `.env` token). Then explore the "Git Activity" tab in the sidebar!
