# PurifyCMS

PurifyCMS is a lightweight, flat-file content management system and eCommerce engine written in PHP. It operates without an SQL database, storing data in JSON files while leveraging built-in caching for fast response times.

## Key Features

- **Flat-File Architecture:** No MySQL or external database required.
- **Integrated eCommerce:** Support for both digital and physical products, checkout workflows, and payment gateway integration.
- **Admin Control Panel:** Manage content, products, orders, translations, and site settings.
- **PurifyVolt Cache:** Built-in caching layer with APCu support and static HTML file generation.
- **Multi-Language:** Interface and template translation via JSON locale dictionaries.
- **Security:** CSRF token validation, brute-force mitigation, and strict session handling.

## Requirements

- PHP 7.4 or higher (PHP 8.x recommended)
- Web Server: Apache (with `mod_rewrite`) or Nginx
- PHP Extensions: `json`, `session`, `curl` (optional: `apcu` for in-memory caching)

## Installation

1. Copy the project files to your web server document root or subfolder.
2. Ensure the web server has write permissions for `inc/data/` and `tmp/`.
3. Copy `inc/config.sample.php` to `inc/config.php` and set your site URL and admin credentials.
4. Access the administration panel at `/admin` (or the custom path defined in your configuration).

## License

This project is licensed under the MIT License.
