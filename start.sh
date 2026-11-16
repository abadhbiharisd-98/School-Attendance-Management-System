#!/bin/bash
set -e

# Default port to 10000 if not specified by Render
PORT="${PORT:-10000}"
echo "=========================================================="
echo " Starting School Attendance Management System on Port: $PORT"
echo "=========================================================="

# 1. Update Apache listening port dynamically
sed -i "s/Listen [0-9]*/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost \*:$PORT>/g" /etc/apache2/sites-available/000-default.conf

# 2. Configure DocumentRoot to serve attendance directly
sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/School_Attendance_Management_System/attendance|g' /etc/apache2/sites-available/000-default.conf

# 3. Apply Reverse Proxy & Port 10000 fixes to Apache configuration
cat << 'EOF' > /etc/apache2/conf-available/render-reverse-proxy.conf
ServerName localhost
UseCanonicalName Off
UseCanonicalPhysicalPort Off

<Directory /var/www/html>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory /var/www/html/School_Attendance_Management_System/attendance>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

# Alias support
Alias /attendance /var/www/html/School_Attendance_Management_System/attendance
Alias /School_Attendance_Management_System/attendance /var/www/html/School_Attendance_Management_System/attendance
EOF

# Ensure AllowOverride All in main apache2.conf
sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

a2enconf render-reverse-proxy

# 4. Ensure permissions for storage directories
mkdir -p /var/www/html/School_Attendance_Management_System/attendance/admin/backups
mkdir -p /var/www/html/School_Attendance_Management_System/attendance/admin/csv
mkdir -p /var/www/html/School_Attendance_Management_System/attendance/images
chown -R www-data:www-data /var/www/html/School_Attendance_Management_System/attendance
chmod -R 775 /var/www/html/School_Attendance_Management_System/attendance/admin/backups
chmod -R 775 /var/www/html/School_Attendance_Management_System/attendance/admin/csv
chmod -R 775 /var/www/html/School_Attendance_Management_System/attendance/images

# 5. Launch Apache in foreground
exec apache2-foreground
