# mrpack-depot

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

A simple, self-hosted, single-file PHP application for browsing and managing a personal collection of Minecraft modpacks. Styled with the Catppuccin Mocha theme.

---

## ✨ Features

- **Modern UI:** A clean, responsive interface inspired by the Catppuccin "Ports" page.
- **Dynamic Filtering:** Instantly filter modpacks by mod loader and Minecraft version.
- **Live Search:** Quickly find the modpack you're looking for.
- **Detailed Mod Lists:** Click any modpack to view a formatted list of all its mods, parsed from Prism Launcher's HTML export.
- **Powerful Admin Panel:**
  - Drag-and-drop uploading for new `.mrpack` files.
  - Drag-and-drop uploading for mod list `.html` files.
  - Consistent metadata management with autocomplete suggestions.
- **Lightweight & Simple:** No database required. Runs on a standard PHP server using a simple `metadata.json` file for storage.

## 💻 Tech Stack

- **Backend:** PHP (no external libraries required)
- **Frontend:** Plain HTML, CSS, and JavaScript (no frameworks)

## 🚀 Requirements & Installation

You'll need a local or remote web server with PHP support.

- A server environment like [Laragon](https://laragon.org/) (Windows), [XAMPP](https://www.apachefriends.org/) (Cross-platform), or a standard Linux server with Nginx/Apache and PHP.
- PHP 7.4 or higher.

**To set up the project:**

1.  **Clone the repository** into your web server's public directory (e.g., `C:/laragon/www/mrpack-depot`).
    ```bash
    git clone https://github.com/chisato04/mrpack-depot.git
    ```
2.  **Create required directories:** Inside the project folder, make sure these two directories exist (they should be included in the repo with a `.gitkeep` file):
    - `/modpacks/` - This is where you will upload your `.mrpack` files.
    - `/modlists/` - This is where the associated mod list `.html` files will be stored.
3.  **Set permissions (if on Linux):** Ensure your web server has permission to write to the `modpacks`, `modlists`, and `metadata.json` files.
    ```bash
    # Example for a typical Linux setup
    sudo chown -R www-data:www-data .
    sudo chmod -R 775 modpacks modlists metadata.json
    ```
4.  **Access the site:**
    - Navigate to your project's URL (e.g., `http://your-repo-name.test`).
    - Access the admin panel at `http://your-repo-name.test/admin.php`.

## 🛠️ Usage

1.  Go to the **Admin Panel** (`/admin.php`).
2.  Use the "Upload New Modpack" section to add your `.mrpack` files.
3.  The page will refresh, showing your new pack in the "Manage Existing Modpacks" table.
4.  Fill in the **Mod Loader** and **Version** for the pack.
5.  Drag-and-drop the corresponding mod list `.html` file (exported from Prism Launcher) onto the "Modlist HTML" drop zone.
6.  Click **"Save All Changes"**.
7.  Go back to the main site to see your new modpack in the browser!

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

-   Theme and color palette heavily inspired by the amazing [Catppuccin](https://github.com/catppuccin/catppuccin) team.
-   Font used is [Inter](https://fonts.google.com/specimen/Inter).
