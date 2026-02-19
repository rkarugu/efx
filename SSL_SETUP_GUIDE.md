# SSL Configuration Guide for Production

## Prerequisites
- Domain name pointing to your server
- Root/sudo access to your server
- Web server (Apache/Nginx) running

## Option 1: Free SSL Certificate with Let's Encrypt (Recommended)

### For Apache Server:
```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Generate SSL certificate
sudo certbot --apache -d kaninichapchap.efficentrix.co.ke

# Auto-renewal (add to crontab)
sudo crontab -e
# Add this line:
0 12 * * * /usr/bin/certbot renew --quiet
```

### For Nginx Server:
```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-nginx

# Generate SSL certificate
sudo certbot --nginx -d kaninichapchap.efficentrix.co.ke
```

## Option 2: Manual SSL Certificate Installation

### 1. Purchase SSL Certificate
- Buy from providers like Namecheap, GoDaddy, or Cloudflare

### 2. Generate Certificate Signing Request (CSR)
```bash
openssl req -new -newkey rsa:2048 -nodes -keyout kaninichapchap.efficentrix.co.ke.key -out kaninichapchap.efficentrix.co.ke.csr
```

### 3. Apache Virtual Host Configuration
Create/edit: `/etc/apache2/sites-available/kaninichapchap-ssl.conf`
```apache
<VirtualHost *:443>
    ServerName kaninichapchap.efficentrix.co.ke
    DocumentRoot /var/www/kaninichapchap/public
    
    SSLEngine on
    SSLCertificateFile /path/to/kaninichapchap.efficentrix.co.ke.crt
    SSLCertificateKeyFile /path/to/kaninichapchap.efficentrix.co.ke.key
    SSLCertificateChainFile /path/to/intermediate.crt
    
    <Directory /var/www/kaninichapchap/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName kaninichapchap.efficentrix.co.ke
    Redirect permanent / https://kaninichapchap.efficentrix.co.ke/
</VirtualHost>
```

### 4. Nginx Configuration
Edit: `/etc/nginx/sites-available/kaninichapchap`
```nginx
server {
    listen 80;
    server_name kaninichapchap.efficentrix.co.ke;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kaninichapchap.efficentrix.co.ke;
    root /var/www/kaninichapchap/public;
    
    ssl_certificate /path/to/kaninichapchap.efficentrix.co.ke.crt;
    ssl_certificate_key /path/to/kaninichapchap.efficentrix.co.ke.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    
    index index.php index.html index.htm;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Laravel Configuration Updates

### 1. Update .env for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kaninichapchap.efficentrix.co.ke
```

### 2. Enable HTTPS Redirect in .htaccess
Uncomment these lines in your .htaccess:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 3. Laravel HTTPS Configuration
Add to `app/Providers/AppServiceProvider.php`:
```php
public function boot()
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

## Security Headers (Optional but Recommended)

Add to your .htaccess:
```apache
# Security Headers
Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
```

## Testing SSL Configuration

1. **SSL Labs Test**: https://www.ssllabs.com/ssltest/
2. **Check certificate expiry**: 
   ```bash
   openssl x509 -in /path/to/certificate.crt -text -noout
   ```

## Troubleshooting

### Common Issues:
- **Mixed Content**: Ensure all resources (CSS, JS, images) use HTTPS
- **Certificate Chain**: Make sure intermediate certificates are properly installed
- **Firewall**: Ensure port 443 is open
- **DNS**: Verify domain points to correct server IP

### Laravel Specific:
- Clear config cache: `php artisan config:clear`
- Clear route cache: `php artisan route:clear`
- Generate application key: `php artisan key:generate`
