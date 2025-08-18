# SMS WTF - SMS Webhook Receiver

A modern web application for receiving and displaying SMS messages via webhooks from Android SMS Gateway.

## Features

- 📱 **Multi-Phone Support** - Manage multiple phone numbers
- 💬 **Real-time SMS Display** - View received messages instantly
- 🔒 **Security Features** - Optional site password protection and admin panel
- 📊 **Admin Dashboard** - Complete management interface
- 🎨 **Modern UI/UX** - Responsive design with beautiful interface
- 🔍 **Search & Filter** - Find messages quickly
- 📡 **Webhook Integration** - Compatible with Android SMS Gateway

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Android device with SMS Gateway app

## Installation

### 1. Download and Setup

1. Clone or download this repository to your web server
2. Upload files to your web root directory (e.g., `/var/www/html/sms-wtf/`)

### 2. Database Setup

1. Create a MySQL database named `sms_wtf`
2. Import the database schema:
   ```bash
   mysql -u your_username -p sms_wtf < database/schema.sql
   ```

### 3. Configuration

1. Edit `config/config.php` and update database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sms_wtf');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

2. Set appropriate file permissions:
   ```bash
   chmod 755 sms-wtf/
   chmod 644 sms-wtf/*.php
   chmod 644 sms-wtf/.htaccess
   ```

### 4. Initial Access

1. Visit your website: `http://your-domain.com/sms-wtf/`
2. Access admin panel: `http://your-domain.com/sms-wtf/admin/`
3. Default admin credentials:
   - Username: `admin`
   - Password: `admin123`
4. **IMPORTANT:** Change the default admin password immediately!

## Android SMS Gateway Setup

### 1. Install SMS Gateway App

Download and install the Android SMS Gateway app on your Android device.

### 2. Configure the App

1. Open the SMS Gateway app
2. Set up authentication (username and password)
3. Note your device's local IP address
4. Start the SMS Gateway service

### 3. Register Phone Numbers

1. Log into the admin panel
2. Go to "Manage Phone Numbers"
3. Add your phone number(s) that will receive SMS

### 4. Register Webhook

Use curl to register the webhook with your SMS Gateway:

```bash
curl -X POST -u <username>:<password> \
  -H "Content-Type: application/json" \
  -d '{ "id": "sms-wtf-webhook", "url": "http://your-domain.com/sms-wtf/webhook.php", "event": "sms:received" }' \
  http://<device_local_ip>:8080/webhooks
```

Replace:
- `<username>` and `<password>` with your SMS Gateway credentials
- `<device_local_ip>` with your Android device's IP address
- `your-domain.com` with your actual domain

## Configuration Options

### Site Password Protection

1. Go to Admin Panel → Settings
2. Enable "Site Password Protection"
3. Set a password for public access
4. Users will need to enter this password to view SMS messages

### Admin Password

1. Go to Admin Panel → Settings
2. Change the default admin password
3. Use a strong password for security

## File Structure

```
sms-wtf/
├── admin/                  # Admin panel
│   ├── index.php          # Dashboard
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── phone_numbers.php  # Phone number management
│   ├── messages.php       # Message management
│   └── settings.php       # Site settings
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── app.js         # JavaScript functions
├── config/
│   └── config.php         # Configuration file
├── database/
│   └── schema.sql         # Database schema
├── includes/              # PHP classes
│   ├── Auth.php           # Authentication
│   ├── Database.php       # Database connection
│   └── SMSManager.php     # SMS management
├── templates/             # Page templates
│   ├── header.php         # Page header
│   ├── footer.php         # Page footer
│   ├── messages_partial.php # Message display
│   └── password_form.php  # Password form
├── index.php              # Main page
├── webhook.php            # Webhook endpoint
├── .htaccess              # Apache configuration
└── README.md              # This file
```

## API Documentation

### Webhook Endpoint

**URL:** `/webhook.php`
**Method:** POST
**Content-Type:** application/json

**Expected Payload:**
```json
{
  "event": "sms:received",
  "payload": {
    "message": "SMS message content",
    "phoneNumber": "+1234567890",
    "receivedAt": "2024-06-07T11:41:31.000+07:00",
    "sender": "+0987654321",
    "senderName": "Contact Name"
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "SMS received and stored successfully",
  "data": {
    "id": 123,
    "phone_number": "+1234567890",
    "received_at": "2024-06-07 04:41:31"
  },
  "timestamp": "2024-06-07T04:41:31+00:00"
}
```

## Security Features

- CSRF protection on all forms
- Password hashing with PHP's password_hash()
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping
- Session timeout for admin users
- Optional site-wide password protection
- Secure headers via .htaccess

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify database exists and user has permissions

2. **Webhook Not Receiving Messages**
   - Check phone number is registered in admin panel
   - Verify webhook URL is accessible from Android device
   - Check webhook logs in server error logs
   - Ensure phone number is active in the system

3. **Permission Denied Errors**
   - Set correct file permissions (755 for directories, 644 for files)
   - Ensure web server can read all files

4. **SMS Gateway Connection Issues**
   - Verify Android device and server are on same network
   - Check firewall settings
   - Ensure SMS Gateway app is running

### Debug Mode

To enable debug mode for troubleshooting:

1. Edit `config/config.php`
2. Change error reporting settings:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

### Webhook Logs

Check webhook activity in the server error logs or create a custom log file:

```bash
tail -f /path/to/error.log | grep "Webhook"
```

## Backup and Maintenance

### Database Backup

```bash
mysqldump -u username -p sms_wtf > sms_wtf_backup_$(date +%Y%m%d).sql
```

### File Backup

```bash
tar -czf sms_wtf_backup_$(date +%Y%m%d).tar.gz /path/to/sms-wtf/
```

### Regular Maintenance

1. Monitor disk space for message storage
2. Clean old messages if needed
3. Update admin passwords regularly
4. Keep PHP and MySQL updated
5. Monitor webhook logs for errors

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source. Feel free to modify and distribute as needed.

## Support

For issues and questions:
1. Check the troubleshooting section
2. Review server error logs
3. Verify SMS Gateway configuration
4. Test webhook endpoint manually

## Changelog

### Version 1.0.0
- Initial release
- Multi-phone support
- Admin panel
- Webhook integration
- Site password protection
- Modern responsive UI
