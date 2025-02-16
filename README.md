# BSCCL Maintenance System

BSCCL SMW4 Maintenance System is a web-based application designed to manage and track maintenance schedules for submarine cable circuits.

## Features

- Circuit Management
- Maintenance Schedule Planning
- Affected Circuit Analysis
- Excel File Import/Export
- User Authentication and Authorization
- Performance Monitoring
- Secure File Handling
- Automated Report Generation

## Quick Install

For quick installation, run:

```bash
git clone https://github.com/MdMuminurRahman/BSCPLC-SMW4-Maintenance.git
cd BSCPLC-SMW4-Maintenance
chmod +x install.sh
sudo ./install.sh
```

For detailed installation instructions, please see [INSTALL.md](INSTALL.md)

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache 2.4 or higher
- Composer
- PHP Extensions:
  - php-mysql
  - php-zip
  - php-gd
  - php-mbstring
  - php-curl
  - php-xml
  - php-bcmath
  - php-json
  - php-redis

## Security

- Built-in CSRF protection
- SQL injection prevention
- XSS protection
- File upload validation
- Rate limiting
- Session security
- Input sanitization

## Contributing

Please read CONTRIBUTING.md for details on our code of conduct and the process for submitting pull requests.

## License

This project is licensed under the MIT License - see the LICENSE file for details.