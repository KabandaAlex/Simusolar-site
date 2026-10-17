# Tulima Solar – Dynamic Website

PHP + MySQL website for Tulima Solar with admin portal. Content is loaded from the database; colors and fonts follow `COLORS-AND-FONTS.txt`. Uses images from `static/images/` and is responsive.

## Requirements

- PHP 7.4+ (with PDO MySQL)
- MySQL 5.7+ or MariaDB
- WAMP/XAMPP or similar (or PHP built-in server + MySQL)

## Install

1. **Database**
   - Create a database (or use the install script to create it).
   - Edit `config/database.php` with your DB host, name, user, and password.

2. **Run install (browser)**
   - Open: `http://localhost/tulima_php/install/install.php`
   - This creates the `tulima_solar` database, tables, and seed data.
   - **Default admin:** username `admin`, password `password` — change after first login.
   - **Existing database?** Run `database/migration_hero.sql` in MySQL first to add hero columns, then run `database/seed.sql` (or re-run install) to set hero content.

3. **Base path**
   - If the site is not in a subfolder, set `BASE_PATH` to `''` in `config/path.php`.

## Site structure

- **Public:** `index.php?page=home|about|products|solar-pumps|contact`
- **Admin:** `admin/login.php` → Dashboard, Pages, Sections, Products, Settings, Contact info.

## Pages (from tulimasolar.com)

- **Home** – Hero, full-service, support, benefits of solar irrigation.
- **About** – Intro and mission.
- **Products** – Product cards (from `products` table) + link to Solar Pumps.
- **Solar Water Pumps** – All sections (solar pumps, animal keeping, fish, water selling, components, surface/submersible pumps, panels, considerations, design, advantages, Simusolar services, financing).
- **Contact** – Contact info from DB + WhatsApp CTA.

## Admin portal

- **Pages** – Edit page title, meta description, sort order, active.
- **Sections** – Edit title, content, and content_extra for each page section.
- **Products** – Add/edit products (title, slug, short description, description, image path, sort, active).
- **Settings** – Site name, tagline, footer text, WhatsApp number, default WhatsApp message.
- **Contact info** – Phone, email, address, WhatsApp.

## Hero and video

- Each page has an optional **hero** (title, subtitle, primary CTA, secondary CTA, and optional **hero video path**). Edit in **Admin → Pages → Edit** (hero section at bottom).
- **Home page:** If you set a **hero video path** (e.g. `static/videos/hero.mp4`), the video plays full-screen in the hero and the **navbar is transparent** over the video. Place your MP4 in `static/videos/` (e.g. `hero.mp4`).

## Resources used

- **Colors & fonts:** `COLORS-AND-FONTS.txt` (mapped in `static/css/style.css`).
- **Images:** `static/images/` (tulima-logo-dark.svg, tulima-logo-white.svg, background-pattern.svg, energy-flow.svg, geometric-pattern.svg, wave-pattern.svg).
- **Videos:** `static/videos/` — add e.g. `hero.mp4` and set **Hero video path** in Admin for the home page.

## Responsive design

- Navbar collapses to a hamburger menu on small screens.
- Container, cards, and typography scale for mobile/tablet/desktop.
- Touch-friendly buttons and links.

## Security

- Change the default admin password after install.
- Restrict or remove the `install/` folder in production.
- Keep `config/database.php` out of version control if it contains secrets.
