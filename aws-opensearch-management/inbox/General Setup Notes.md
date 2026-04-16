# OpenSearch IAM Role Authentication Guide

This guide outlines the steps to grant an existing EC2 IAM role access to specific indexes within an AWS OpenSearch domain.

***
## Part 1: Create the Access Role in OpenSearch

This role exists *inside* OpenSearch and defines what the application is allowed to do (e.g., read/write to specific indexes).

1.  Log in to **OpenSearch Dashboards** as your master user.
2.  Navigate to the main menu (☰) -> **Security** -> **Roles**.
3.  Click **"Create role"** and give it a name (e.g., `crm_app_role`).
4.  Go to the **"Index Permissions"** tab.
    * Click **"Add index permissions"**.
    * In **Index Patterns**, enter the indexes your app needs (e.g., `customers*`).
    * In **Permissions: Action Groups**, select the required permissions (e.g., `read`, `write`, `delete`).
5.  Save the role.

***
## Part 2: Map the IAM Role to the OpenSearch Role

This final step links your existing AWS IAM role to the OpenSearch role.

1.  While viewing the `crm_app_role` in Dashboards, go to the **"Mapped users"** tab.
2.  Click **"Manage mapping"**.
3.  In the **Backend roles** section, paste the **ARN** of your existing EC2 instance role.
4.  Click **"Map"**.

Your EC2 instances with the assigned IAM role now have the permissions defined in `crm_app_role` without needing any passwords.