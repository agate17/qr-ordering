QR Table Ordering System - Setup & Migration Guide
===================================================

This document explains how to set up and move the QR Table Ordering System to a new computer or network.

1. Copy Project Files
---------------------
- Copy the entire 'restaurant-demo' folder (and all its files) to the new computer's web server directory (e.g., C:/xampp/htdocs/restaurant-demo).

2. Set Up the Database
----------------------
- Install XAMPP (or similar) on the new computer if not already installed.
- Open phpMyAdmin or use the MySQL command line.
- Import the 'data.sql' file to create the database and tables:
    - In phpMyAdmin: Select 'Import', choose 'data.sql', and execute.
    - In command line: mysql -u root -p < data.sql
- Make sure the database credentials in 'db.php' match your MySQL setup (default: user 'root', no password).

3. Update the Local IP Address in QR Code Generator
---------------------------------------------------
- Find the new computer's local IP address:
    - Open Command Prompt and type: ipconfig
    - Look for 'IPv4 Address' (e.g., 192.168.1.50)
- Open 'generate_qr.php' in a text editor.
- Find the line:
    $url = "http://.../restaurant-demo/menu.php?table=$table";
- Replace the IP address with the new computer's IP (e.g., 192.168.1.50).

4. Regenerate QR Codes
----------------------
- Open 'qr-codes.html' in your browser to view the new QR codes with the correct IP.
- Print or display the new QR codes for each table.

5. Allow Apache/XAMPP Through Firewall
--------------------------------------
- Make sure Apache (httpd.exe) is allowed through Windows Firewall for both private and public networks.
- Restart Apache from the XAMPP Control Panel if needed.

6. Access the System
--------------------
- On any device connected to the same Wi-Fi, open a browser and go to:
    http://[NEW_IP]/restaurant-demo/menu.php?table=1
    (Replace [NEW_IP] with your computer's IP address)
- The kitchen and register views are at:
    http://[NEW_IP]/restaurant-demo/kitchen.php
    http://[NEW_IP]/restaurant-demo/register.php
- The admin menu management page is at:
    http://[NEW_IP]/restaurant-demo/admin_menu.php
    (Default password: demo123)

Notes
-----
- If you move to a public server or use a domain name, update the QR code URLs to use that domain.
- All devices must be on the same local network to access the system.
- For any issues, check firewall settings and database connection in 'db.php'. 