# Swipe Nest

**Swipe Nest** is an innovative, high-performance category-based social media and short-video platform built natively with Vanilla technologies to ensure maximum speed without unnecessary bloat. 

It introduces a unique **2D Navigation Engine**: users swipe horizontally to switch between categories (e.g., Gaming, Music, Travel), and vertically to scroll through endless reels inside that specific category.

## Core Features
- **2D Navigation Grid**: Dual-axis `Swiper.js` nested layout.
- **Independent Position Memory**: The app natively remembers the user's exact scroll position in each category using isolated `sessionStorage`.
- **Smart Playback Engine**: Prevents audio overlap and lag by strictly autoplaying only the visible video and pausing all others automatically.
- **Robust Security**: Entire backend validates user actions against immutable server-side PHP `$_SESSION` data. No data leakage or unauthorized profile edits are possible.
- **Complete Social Suite**: Registration, Auth, Likes, Saves, Following, Sliding Comments Modal, and a comprehensive unified Search Engine.

## Tech Stack
- **Frontend**: Vanilla HTML5, CSS3, JavaScript (Mobile-first Grid/Flexbox architecture)
- **UI Libraries**: `Swiper.js` (for gesture tracking), `Lucide Icons`
- **Backend**: PHP 8.0+
- **Database**: MySQL (using PDO Prepared Statements)

## Local Installation (XAMPP)

Follow these instructions to run Swipe Nest locally on your machine.

### 1. File Placement
1. Install [XAMPP](https://www.apachefriends.org/index.html).
2. Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
3. Move this entire project folder into your XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\Swipe_Nest`).

### 2. Database Configuration
1. Open your browser and navigate to `http://localhost/phpmyadmin`.
2. Click **New** on the left sidebar to create a new database.
3. Name the database exactly: `zyva_db` (or update `config/db.php` if you choose a different name).
4. Select the `zyva_db` database, click the **Import** tab at the top.
5. Choose the `swipe_nest.sql` file included in the root of this project folder.
6. Click **Import** (or Go) at the bottom. This will automatically generate all required tables and populate default categories.

*(Note: The database configuration inside `config/db.php` assumes `root` user with no password, which is the XAMPP default. Adjust if your local server uses credentials).*

### 3. Usage
1. Open your browser and navigate to `http://localhost/Swipe_Nest`.
2. Create an account via the Sign Up page.
3. Enjoy the platform!

---

*Swipe Nest was architected with a strict adherence to performance, security, and modern design aesthetics.*
