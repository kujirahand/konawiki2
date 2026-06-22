# Agent Guidelines & Project Structure for KonaWiki2

Welcome agent! This file outlines the system architecture, directory layouts, routing flow, database structure, and coding guidelines for the **KonaWiki2** project. Please refer to this file when exploring or adding new features.

---

## 1. Overview
**KonaWiki2** is a lightweight, self-hosted Wiki application built in PHP (7.x / 8.x compatibility) using SQLite3 as its primary storage. It runs on a custom micro-framework named `fw_simple`.

---

## 2. Directory Structure

```
/
├── index.php                 # Main entry point (loads configurations & routes requests)
├── go.php                    # Shortened URL redirection entry point
├── README.md                 # Project documentation
├── sample.htaccess           # Sample Apache configuration (mod_rewrite settings)
├── LICENSE                   # Project license
├── attach/                   # User-uploaded attachment storage (requires write permission)
├── cache/                    # Template cache directory (requires write permission)
├── data/                     # Data folder containing SQLite DBs & konawiki.ini.php (write perm)
├── skin/                     # Theme & design layouts (skins)
└── kona-engine/              # Core System Engine
    ├── action/               # Controller modules for handling queries
    ├── fw_simple/            # The micro-framework (php_fw_simple)
    ├── help/                 # Help documentations
    ├── lang/                 # Language translation assets (ja.inc.php, en.inc.php)
    ├── lib/                  # Helper functions and core system libraries
    ├── plugins/              # Wiki formatting plugins (includes Japanese-named files)
    ├── resource/             # Default resources (CSS, JS, images, icons)
    ├── sql/                  # SQL files for database table definitions
    ├── template/             # Default HTML templates
    └── test/                 # Test suites
```

---

## 3. Application Flow & Routing
KonaWiki2 processes requests via Query Strings:
1. `index.php` initializes basic variables and includes the configuration file from [konawiki.ini.php](file:///Users/kujirahand/repos/konawiki2/data/konawiki.ini.php) (or clones template if it doesn't exist).
2. It then loads [lib_kona.inc.php](file:///Users/kujirahand/repos/konawiki2/kona-engine/lib/lib_kona.inc.php) and executes `konawiki_init()`.
3. `konawiki_parseURI()` parses the query parameter structure:
   - Format: `index.php?[page_name]&[action_name]&[status_name]`
   - Default action is `show`. Default page is `FrontPage` (configurable).
4. `konawiki_execute_action()` routes the request:
   - It searches for `kona-engine/action/[action_name].inc.php`.
   - It executes the function `action_[action_name]_[status_name]()`.

---

## 4. Database Architecture (SQLite3)
To ensure easy maintenance and backup, the database is split into four distinct SQLite3 database files located under [data/](file:///Users/kujirahand/repos/konawiki2/data):
- **main**: Wiki pages, page configurations, and view/download counters.
- **sub**: Auxiliary database (used for custom plugins or structures).
- **backup**: Backup logs (`oldlogs`) and raw html cache (`cache_logs`).
- **users**: User authentication logs and credentials.

### Main Tables (Schema details in `kona-engine/sql/`)
- `logs`: Stores Wiki page body text (`id`, `name`, `body`, `freeze`, `private`, `ctime`, `mtime`).
- `attach`: Stores file attachment metadata (`id`, `log_id`, `name`, `ext`, `ctime`, `mtime`).
- `tags`: Connects pages (`log_id`) to tags (`tag`).
- `log_counters`: Page access counters.

---

## 5. Main APIs & Helper Functions

### 5.1 Database Operations ([fw_database.lib.php](file:///Users/kujirahand/repos/konawiki2/kona-engine/fw_simple/fw_database.lib.php))
Instead of using PDO directly, leverage these simplified wrapper functions:
- `db_get1($sql, $params = [], $dbname = 'main')`: Fetches a single row as an associative array.
- `db_get($sql, $params = [], $dbname = 'main')`: Fetches all matching rows as an array.
- `db_exec($sql, $params = [], $dbname = 'main')`: Executes an update/delete statement.
- `db_insert($sql, $params = [], $dbname = 'main')`: Inserts a record and returns the auto-increment ID.
- `db_begin($dbname)` / `db_commit($dbname)` / `db_rollback($dbname)`: Transaction management.

### 5.2 Application Configurations & Context ([lib_kona.inc.php](file:///Users/kujirahand/repos/konawiki2/kona-engine/lib/lib_kona.inc.php))
- `konawiki_param($name, $default_value)`: Safely fetches a POST or GET parameter.
- `konawiki_public($name, $default)` / `konawiki_private($name, $default)`: Access configuration variables.
- `konawiki_getPage()`: Returns the sanitized name of the current Wiki page.
- `konawiki_getPageId($page)`: Resolves the database ID of a given page name.
- `konawiki_getPageURL($page, $action, $stat)`: Constructs standard application URLs.

### 5.3 Template Rendering ([fw_template_engine.lib.php](file:///Users/kujirahand/repos/konawiki2/kona-engine/fw_simple/fw_template_engine.lib.php))
- `template_render($template_file_name, $vars = [])`: Renders HTML layouts using `$vars` to inject variables.

---

## 6. Coding & Development Rules
1. **Compatibility**: Maintain compatibility with PHP 7.4 through PHP 8.x. Avoid modern PHP features (e.g., PHP 8.1+ features like Enums) if they degrade support for PHP 7.4.
2. **Character Encoding**: The system runs strictly on `UTF-8`. Use multibyte string functions (`mb_strlen`, `mb_strpos`, etc.) for string manipulations.
3. **Database Security**: Always use prepared statements/placeholders (`?`) when constructing SQL queries to prevent SQL injections.
4. **Commenting Style**: Write inline/function comments in Japanese (`ja`), matching the legacy codebase language style.
5. **Write Permissions**: Ensure newly created features respect web service write permissions on `data/`, `cache/`, and `attach/` directories.
