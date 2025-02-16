# BSCCL Maintenance System Installation Guide

## Prerequisites

First, update your system and install required packages:

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y apache2 mysql-server php php-cli php-fpm php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath php-json php-redis composer git unzip
```

## Project Setup

1. Clone the repository:
```bash
cd /var/www/html
sudo git clone https://github.com/MdMuminurRahman/BSCPLC-SMW4-Maintenance.git maintenance
sudo chown -R www-data:www-data maintenance
cd maintenance
```

2. Install PHP dependencies:
```bash
sudo -u www-data composer install
```

3. Configure environment:
```bash
cp .env.example .env
```

4. Configure your .env file with your settings:
```bash
nano .env
```

Update these values:
```properties
APP_ENV=production
APP_DEBUG=false
APP_URL=your_domain
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=bsccl_maintenance
DB_USERNAME=your_mysql_username
DB_PASSWORD=your_secure_password
UPLOAD_MAX_SIZE=10
ALLOWED_EXTENSIONS=xlsx
DEFAULT_TIMEZONE=UTC
```

## Database Setup

1. Create MySQL database and user:
```bash
sudo mysql
```

In MySQL prompt:
```sql
CREATE DATABASE bsccl_maintenance;
CREATE USER 'bsccl_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON bsccl_maintenance.* TO 'bsccl_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

2. Import database schema:
```bash
mysql -u bsccl_user -p bsccl_maintenance < database/init.sql
mysql -u bsccl_user -p bsccl_maintenance < database/performance.sql
mysql -u bsccl_user -p bsccl_maintenance < database/setup.sql
```

## Apache Configuration

1. Create Apache virtual host configuration:
```bash
sudo nano /etc/apache2/sites-available/maintenance.conf
```

Add this configuration:
```apache
<VirtualHost *:80>
    ServerName maintenance.yourdomain.com
    DocumentRoot /var/www/html/maintenance/public
    
    <Directory /var/www/html/maintenance/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/maintenance_error.log
    CustomLog ${APACHE_LOG_DIR}/maintenance_access.log combined
</VirtualHost>
```

2. Enable required Apache modules and the site:
```bash
sudo a2enmod rewrite
sudo a2ensite maintenance.conf
sudo systemctl restart apache2
```

## Directory Permissions

Set proper permissions:
```bash
sudo chown -R www-data:www-data /var/www/html/maintenance
sudo chmod -R 755 /var/www/html/maintenance
sudo chmod -R 777 /var/www/html/maintenance/uploads
sudo chmod -R 777 /var/www/html/maintenance/logs
```

## Application Setup

1. Create required directories:
```bash
sudo -u www-data mkdir -p uploads logs
```

2. Generate first admin user:
```bash
php setup.php --create-admin
```

## File Permissions Summary
Make sure these critical files have proper permissions:
```bash
sudo chmod 644 .env
sudo chmod 644 .htaccess
sudo chmod -R 755 app
sudo chmod -R 755 public
sudo chmod -R 777 logs
sudo chmod -R 777 uploads
sudo chmod 755 install.sh
```

## Security Considerations

1. Configure PHP settings in php.ini:
```bash
sudo nano /etc/php/[version]/apache2/php.ini
```

Recommended settings:
```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
display_errors = Off
```

2. Enable automatic security updates:
```bash
sudo apt install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades
```

## Final Steps

1. Test the installation by visiting your domain in a web browser
2. Configure your firewall:
```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

3. Set up SSL (recommended):
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d maintenance.yourdomain.com
```

## Maintenance

1. Regular database backup:
```bash
mysqldump -u bsccl_user -p bsccl_maintenance > backup_$(date +%Y%m%d).sql
```

2. Log rotation:
```bash
sudo nano /etc/logrotate.d/maintenance
```

Add:
```
/var/www/html/maintenance/logs/*.log {
    daily
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

## First Login

After installation, you can log in with the admin user created during setup. Make sure to change the password immediately after first login.