# PurifyCMS

PurifyCMS is a lightweight, flat-file content management system and eCommerce engine written in PHP. It operates entirely without an SQL database, storing data in atomic JSON files with built-in multi-tier caching (APCu & Static HTML).

## Key Features

- **Database-Free Architecture:** No MySQL or external database service required.
- **Built-in eCommerce:** Digital and physical products, checkout workflows, inventory management, and payment gateway support.
- **Admin Control Panel:** Manage content, products, orders, translations, and site settings with a clean interface.
- **PurifyVolt Cache:** High-performance caching layer with APCu in-memory storage and pre-rendered static HTML.
- **Multi-Language Support:** Dynamic JSON locale dictionaries for interface and template translations.
- **Security:** Built-in CSRF token protection, brute-force mitigation, and hardened session handling.

## System Requirements

- **PHP Version:** PHP 8.1 or higher (PHP 8.2+ recommended)
- **Web Server:** Apache (with `mod_rewrite` enabled) or Nginx
- **Required PHP Extensions:**
  - `json` (data encoding and decoding)
  - `session` (secure user and admin authentication)
  - `curl` (remote updates and external webhook handling)
  - `mbstring` (multibyte UTF-8 string manipulation)
  - `zip` (automated GitHub releases and full backup generation)
- **Optional PHP Extensions:**
  - `apcu` (high-throughput in-memory caching)
  - `gd` or `imagick` (automatic image processing and WebP conversion)

## Installation

### Method 1: Web Installation Wizard (Recommended)

1. Clone or download the repository to your web server document root or subfolder:
   ```bash
   git clone https://github.com/paimontwo/PurifyCMS.git
   ```
2. Ensure the web server user has write permissions for `inc/` and `inc/data/`:
   ```bash
   chmod -R 775 inc/ tmp/
   ```
3. Open your browser and navigate to the installation URL (e.g. `https://yourdomain.com/` or `https://yourdomain.com/subfolder/`).
4. Follow the step-by-step setup wizard to configure your site name, URL, and administrator account.
5. The installer will generate `inc/config.php` and finalize the environment.

### Method 2: Manual Configuration

1. Copy the sample configuration:
   ```bash
   cp inc/config.sample.php inc/config.php
   ```
2. Edit `inc/config.php` to define your `$cfg_mainurl`, admin path, and authentication credentials.
3. Access the administration panel at `/admin` (or the path defined in your configuration).

## License

This project is open-source software licensed under the MIT License.
