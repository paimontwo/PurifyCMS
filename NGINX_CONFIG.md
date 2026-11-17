# Nginx Configuration Guidelines for PurifyCMS v3.6.0

Since PurifyCMS is a Zero-Database system with pretty URL routing, Nginx requires a specific server block to handle requests and ensure security. Use the following configuration:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/purifycms;
    index index.php;

    # --- Security: Block Direct Access to Sensitive Core Folders ---
    location ~* ^/(inc|admin_core|themes/[^/]+/src)/ {
        deny all;
        return 403;
    }

    # --- Security: Block Sensitive File Extensions ---
    location ~* \.(sqlite|log|ini|json|md|dat|tmp)$ {
        deny all;
        return 403;
    }

    # --- Routing: Pretty URLs & Admin Panel ---
    location / {
        # 1. If physical file exists, serve it (Assets, Images, CSS)
        # 2. Otherwise, route everything to index.php
        try_files $uri $uri/ /index.php?$args;
    }

    # --- PHP Processing ---
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        
        # Change this to your actual PHP-FPM socket or port
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # --- Static Cache Optimization (Optional) ---
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|webp)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

## Why is this necessary?
Unlike Apache which uses `.htaccess` locally, Nginx requires these rules at the server-level configuration. Without this:
1. **Security Risk**: Users could directly download your `inc/config.php` (if not properly handled by PHP) or read your `inc/data/*.json` files.
2. **Broken Navigation**: Moving between pages or accessing the admin panel would return a 404 error because Nginx wouldn't know to send those requests to `index.php`.
