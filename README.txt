# Online Case Management System (OCMS) - Setup Guide

This guide will help you set up and run the OCMS project on your local XAMPP server.

## Prerequisites
- XAMPP installed (with Apache and MySQL).
- A web browser.

## Step 1: Project Placement
1. Navigate to your XAMPP installation directory (usually `C:\xampp` or `D:\xampp`).
2. Open the `htdocs` folder.
3. Keep the `ocms` folder inside `htdocs`. 
   - Path should be: `D:\Xampp\htdocs\Project\ocms` 
   - Note: If your localhost points to `htdocs`, you access it via `http://localhost/Project/ocms`.

## Step 2: Database Setup
1. Open XAMPP Control Panel and Start **Apache** and **MySQL**.
2. Open your browser and go to `http://localhost/phpmyadmin`.
3. Click on **New** to create a new database.
4. Name the database `ocms` and click **Create**.
5. Click on the `ocms` database on the left sidebar.
6. Click on the **Import** tab.
7. Click **Choose File** and select the `database.sql` file located in the `ocms` folder.
8. Click **Import** (or Go) at the bottom.
9. You should see a success message indicating tables `users` and `cases` were created.

## Step 3: Configuration (Optional)
The database connection is configured in `config/db.php`.
Default settings are:
- Host: localhost
- User: root
- Password: (empty)
- DB Name: ocms

If your MySQL password is different, open `config/db.php` and update the `$pass` variable.

## Step 4: Running the Project
1. Open your browser.
2. Go to `http://localhost/Project/ocms` (adjust path if your folder structure is different).
3. You should see the Welcome Landing Page.

## Step 5: How to Use
1. **Register**: Click "Register" to create a new account.
   - Select "Student" to create a student account (Roll No required).
   - Select "Staff" to create a staff account.
2. **Login**: Use your email and password to login.
3. **Student Features**:
   - Add Case: Submit a new case.
   - My Cases: View status of your cases.
   - Case History: View past approved/rejected cases.
4. **Staff Features**:
   - Received Cases: Approve or Reject pending cases with remarks.
   - Approved/Rejected Cases: View history.

## Troubleshooting
- **Database Connection Error**: Check `config/db.php` credentials.
- **404 Not Found**: Ensure the URL matches your folder modification in `htdocs`.
