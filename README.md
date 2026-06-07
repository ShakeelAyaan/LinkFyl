# LinkFyl - Your Brand. One Link.

## Overview
LinkFyl is a production-ready SaaS platform built with PHP 8.2+, MySQL, and Bootstrap 5. It's a Linktree-style platform for creating professional link-in-bio landing pages.

## Features

### User System
- User Registration & Login
- Email Verification
- Forgot Password Recovery
- User Dashboard
- Profile Settings
- Change Password
- Account Deletion

### Plans
- **Free Plan**: Profile Photo, Cover Photo, Bio, 5 Links, Social Icons, Basic Analytics
- **Premium Plan (₹1999/Year)**: Unlimited Links, Custom Themes, Landing Page Builder, Advanced Analytics, Premium Templates, Lead Forms, No Branding, Priority Support

### Landing Page Builder
- Hero Section
- About Section
- Services
- Gallery
- Testimonials
- FAQ
- Contact Form
- CTA
- Team
- Videos

### Industry Templates
- YouTubers (Creator Pro, Video Showcase, Influencer Hub)
- Doctors (Clinic Profile, Appointment Page, Specialist Profile)
- NGOs (NGO Profile, Donation Landing Page, Campaign Landing Page)

### Analytics
- Visitor Tracking
- Click Analytics
- Device Analytics
- Geographic Analytics
- Referrer Tracking
- Daily Reports

### Admin Panel
- User Management
- Subscription Management
- Payment Management
- Template Management
- Analytics Dashboard
- Coupon Management
- Support Tickets

### Monetization
- Referral Program
- Coupon Codes
- Affiliate System
- Premium Templates
- Featured Profiles

### Integrations
- Razorpay Payment Gateway
- Email Verification
- Analytics Tracking
- SEO Optimization

## Installation

### Requirements
- PHP 8.2+
- MySQL 8+
- Apache with mod_rewrite
- OpenSSL extension

### Setup Instructions

1. **Extract files to your hosting**
   ```bash
   unzip linkfyl.zip
   cd linkfyl
   ```

2. **Run Installation Wizard**
   - Visit: `https://yourdomain.com/install`
   - Follow the setup steps
   - Create admin account

3. **Configure Database**
   - Update `config/database.php` with your credentials
   - Import `database/linkfyl.sql`

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 cache/
   chmod 644 config/database.php
   ```

5. **Configure Razorpay**
   - Add your Razorpay Key ID and Key Secret in admin panel

## Project Structure

```
linkfyl/
├── admin/                 # Admin panel
├── assets/               # CSS, JS, Fonts
├── cache/                # Cache files
├── classes/              # Core classes
├── config/               # Configuration files
├── database/             # Database schema
├── includes/             # Helper functions
├── install/              # Installation wizard
├── public/               # Public files
├── uploads/              # User uploads
├── index.php             # Main entry point
├── install.php           # Installation entry point
└── README.md             # This file
```

## Database Configuration

```php
define('DB_HOST','localhost');
define('DB_NAME','u276588429_linkfyl');
define('DB_USER','u276588429_linkfyl');
define('DB_PASS','Shakeel@8505');
define('SITE_URL','https://linkfyl.in');
define('SITE_NAME','Linkfyl');
define('SITE_TAGLINE','Your Brand. One Link.');
```

## Razorpay Integration

```php
define('RAZORPAY_KEY_ID', 'rzp_live_SySk7kKjgDMnsL');
define('RAZORPAY_KEY_SECRET', '6CtGfE6wPju4guV3X4xB5HwI');
```

## File Permissions

- `uploads/` - 755 (writable)
- `cache/` - 755 (writable)
- `config/database.php` - 644 (readable)

## Security Features

- SQL Injection Protection (Prepared Statements)
- CSRF Token Protection
- XSS Protection
- Password Hashing (bcrypt)
- Session Security
- Rate Limiting
- Input Validation

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `POST /api/auth/forgot-password` - Password recovery

### Profile
- `GET /api/profile/{username}` - Get user profile
- `POST /api/profile/update` - Update profile
- `GET /api/{username}` - View public landing page

### Links
- `GET /api/links` - Get user links
- `POST /api/links/create` - Create link
- `PUT /api/links/{id}` - Update link
- `DELETE /api/links/{id}` - Delete link

### Analytics
- `GET /api/analytics/overview` - Analytics overview
- `GET /api/analytics/visitors` - Visitor data
- `GET /api/analytics/clicks` - Click data
- `GET /api/analytics/devices` - Device analytics
- `GET /api/analytics/countries` - Geographic analytics

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## License

Proprietary - LinkFyl

## Support

For support, email: support@linkfyl.in

## Version

Version 1.0.0 - Production Ready
