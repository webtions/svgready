# Architecture

## Overview

SVG Ready is a PHP-based web application that converts SVG markup to CSS Data URIs. The architecture follows a simple MVC-like pattern with clear separation of concerns.

## Entry Points

- **`index.php`** - Main web interface entry point
- **`ajax.php`** - AJAX handler for live conversion (returns HTML fragments)

## Core Components

### Core (`inc/classes/SVGReady.php`)
- Bootstrap and environment initialization
- Maintenance mode check
- 404 handling
- Request processing and context building

### SVGConverter (`inc/classes/SVGConverter.php`)
- SVG validation and sanitization
- DOM-based XML parsing with XXE protection
- Whitelist-based tag/attribute filtering
- Data URI generation (percent-encoded and base64)
- Error handling with user-friendly messages

### View (`inc/classes/View.php`)
- Template rendering with context injection
- Default variable guarantees
- Safe variable extraction

### Logger (`inc/classes/Logger.php`)
- Error and debug logging to `debug.log` file
- Automatic log rotation when file exceeds 5MB
- Backup file cleanup (keeps 10 most recent backups)
- Log injection prevention (sanitizes messages)
- Secure file permissions (0600 for log file, 0700 for directory)
- Path traversal protection
- Fallback to PHP error log if file writing fails

## Directory Structure

```
/
├── index.php              # Main entry point
├── ajax.php               # AJAX handler
├── inc/
│   ├── classes/          # Core classes
│   │   ├── SVGReady.php  # Bootstrap & request handling
│   │   ├── SVGConverter.php  # SVG processing
│   │   ├── View.php      # Template renderer
│   │   └── Logger.php    # Error logging system
│   ├── functions/        # Helper functions
│   │   ├── functions.php # Escaping utilities
│   │   └── errors.php    # Error message definitions
│   └── templates/        # View templates
│       ├── header.php    # HTML head & header
│       ├── footer.php    # Footer & scripts
│       ├── form.php      # Input form
│       ├── output.php    # Results display
│       ├── ads.php       # Ad banner
│       ├── maintenance.php  # Maintenance page
│       └── 404.html      # 404 page
└── assets/               # Static assets (CSS, JS)
```

## Request Flow

### Standard Request (`index.php`)
1. Bootstrap: `Core::checkMaintenance()` → `Core::check404()` → `Core::initEnvironment()`
2. Request handling: `Core::handleRequest()` processes POST data
3. Context building: Creates context array with results/errors
4. Template rendering: `View::render()` injects context into templates
5. Output: Full HTML page with header, form, output, footer

### AJAX Request (`ajax.php`)
1. Environment setup: `Core::initEnvironment()`
2. Request handling: `Core::handleRequest()` (same as standard)
3. Context flagging: Sets `isAjax = true`
4. Fragment rendering: Only `output.php` template
5. Output: HTML fragment for live preview

## Template System

- Templates located in `inc/templates/`
- Context variables injected via `View::render()`
- Default values guaranteed for all template variables
- Safe escaping via helper functions (`svgreadyEscapeHtml()`, `svgreadyEscapeJs()`, `svgreadyEscapeDataAttr()`)

## Security Layer

1. **Input Validation**: Size limits (250KB), empty checks
2. **XML Sanitization**: DOMDocument with XXE protection (`LIBXML_NONET`)
3. **Whitelist Filtering**: Allowed tags and attributes only
4. **Dangerous Tag Removal**: Scripts, foreignObject, iframe, etc.
5. **Event Handler Blocking**: Removes `on*` attributes
6. **Href Validation**: Safe URL checking for links
7. **Nesting Limits**: Prevents excessive `<use>` element nesting
8. **Output Escaping**: All user content escaped before display
9. **Log File Protection**: `.htaccess` blocks web access to log files, restrictive file permissions (0600)

## Logging System

The application includes a comprehensive logging system for debugging and error tracking:

- **Log File**: `debug.log` in the project root
- **Automatic Rotation**: Logs are rotated when file exceeds 5MB
- **Backup Management**: Keeps 10 most recent backup files (`.bak` extension)
- **Error Logging**: All errors, exceptions, and warnings are logged
- **Security**: Log files protected from web access via `.htaccess`, restrictive file permissions
- **Log Injection Prevention**: All log messages are sanitized to prevent injection attacks

## Dependencies

- **PHP 8+** with strict types
- **Composer** for development tools (PHPStan, PHPCS)
- **No runtime dependencies** - pure PHP implementation

