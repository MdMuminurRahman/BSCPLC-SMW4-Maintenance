#!/bin/bash

# Exit on error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
NC='\033[0m'

echo "BSCCL Maintenance System Installation Script"
echo "----------------------------------------"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root${NC}"
    exit 1
fi

# Function to print status
print_status() {
    echo -e "${GREEN}==>${NC} $1"
}

# Update system
print_status "Updating system packages..."
apt update && apt upgrade -y

# Install required packages
print_status "Installing required packages..."
apt install -y apache2 mysql-server php php-cli php-fpm php-mysql php-zip \
    php-gd php-mbstring php-curl php-xml php-bcmath php-json php-redis \
    composer git unzip

# Setup directories
print_status "Setting up project directories..."
cd /var/www/html
mkdir -p maintenance
cd maintenance

# Set permissions
print_status "Setting correct permissions..."
chown -R www-data:www-data .
chmod -R 755 .
mkdir -p uploads logs
chmod -R 777 uploads logs

# Install PHP dependencies
print_status "Installing PHP dependencies..."
sudo -u www-data composer install

# Setup environment file
print_status "Setting up environment file..."
if [ ! -f .env ]; then
    cp .env.example .env
    print_status "Please edit .env file with your configuration"
    read -p "Press enter to continue"
fi

# Configure Apache
print_status "Configuring Apache..."
cat > /etc/apache2/sites-available/maintenance.conf << EOF
<VirtualHost *:80>
    ServerName maintenance.local
    DocumentRoot /var/www/html/maintenance/public
    
    <Directory /var/www/html/maintenance/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/maintenance_error.log
    CustomLog \${APACHE_LOG_DIR}/maintenance_access.log combined
</VirtualHost>
EOF

# Enable Apache modules and site
a2enmod rewrite
a2ensite maintenance.conf
systemctl restart apache2

print_status "Installation completed!"
echo "Please:"
echo "1. Edit .env file with your database credentials"
echo "2. Import database schema using database/*.sql files"
echo "3. Create your first admin user using 'php setup.php --create-admin'"
echo "4. Access the application through your web browser"