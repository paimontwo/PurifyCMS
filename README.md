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

## Deployment & Installation

### Option 1: Docker / Docker Compose (Fastest)

PurifyCMS runs out-of-the-box with Docker, Coolify, Portainer, or any container environment:

1. Clone the repository:
   ```bash
   git clone https://github.com/paimontwo/PurifyCMS.git
   cd PurifyCMS
   ```
2. Start the container:
   ```bash
   docker compose up -d
   ```
3. Open your browser at `http://localhost:8080`.

> **Coolify Deployment:** In Coolify, create a new service, connect this repository, and deploy using the Dockerfile. Set port `80` as the internal port.

### Option 2: Web Installation Wizard (Standard Web Hosting)

1. Clone or upload the project files to your web server document root or subfolder:
   ```bash
   git clone https://github.com/paimontwo/PurifyCMS.git
   ```
2. Ensure the web server user has write permissions for `inc/` and `inc/data/`:
   ```bash
   chmod -R 775 inc/ tmp/
   ```
3. Navigate to your domain or subfolder in a browser (e.g. `https://yourdomain.com/`).
4. Complete the web wizard steps to configure your site name, URL, and administrator account.

### Option 3: Manual Configuration

1. Copy the sample configuration:
   ```bash
   cp inc/config.sample.php inc/config.php
   ```
2. Edit `inc/config.php` to define your `$cfg_mainurl`, admin path, and credentials.
3. Access the administration panel at `/admin`.

## License

This project is open-source software licensed under the MIT License.
