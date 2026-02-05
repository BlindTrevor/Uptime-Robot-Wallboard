# Uptime-Robot-Wallboard

A real-time status wallboard for monitoring UptimeRobot services using their API v3.

## Features

- Real-time monitoring of all your UptimeRobot monitors
- Visual status indicators (up, down, paused)
- Filter view to show only problematic services
- Automatic refresh every 20 seconds
- **Auto-refresh on config changes** - Front-end automatically reloads when configuration is updated
- **Auto Fullscreen Mode** - Automatically enter fullscreen on load for kiosk displays
- **Dark/Light Theme Toggle** - Switch between dark and light themes with user preference persistence
- **Customizable wallboard title** - Set your own title for branding
- **Optional logo display** - Upload and display your company logo
- **Query String Configuration** - Override settings via URL parameters (e.g., `?showProblemsOnly=true&refreshRate=30`)
- **Flexible Configuration** - Control whether users can modify settings via query string

## Dark/Light Theme

The wallboard supports both dark and light themes with multiple ways to control the appearance:

### Theme Toggle Button

Click the theme toggle button in the controls section to instantly switch between dark and light modes. Your selection is automatically saved in a cookie and will persist across browser sessions.

### Theme Configuration Options

1. **User Toggle** (Highest Priority): Click the theme button to manually switch themes. The selection is stored in a browser cookie.

2. **Query String Parameter**: Set the theme via URL parameter
   ```
   ?theme=dark   # Force dark theme
   ?theme=light  # Force light theme
   ?theme=auto   # Follow system preference
   ```

3. **Config File**: Set the default theme in `config.env`
   ```bash
   THEME=dark   # Default: dark theme
   THEME=light  # Default: light theme
   THEME=auto   # Default: follow system preference
   ```

4. **System Preference** (Lowest Priority): When set to `auto` or no preference is stored, the wallboard automatically detects your system's dark/light mode preference using the `prefers-color-scheme` media query.

### Theme Screenshots

**Dark Theme (Default)**

![Dark Theme](https://github.com/user-attachments/assets/df95564d-da7b-46c5-b008-2f990d67ea62)

**Light Theme**

![Light Theme](https://github.com/user-attachments/assets/cb4b3114-5a31-4e83-96f5-ab27f446fc75)

### Accessibility

Both themes have been designed with accessibility in mind:
- High contrast ratios for text readability
- Clear visual distinction between status indicators
- Smooth transitions between themes

## Auto Fullscreen Mode

The wallboard supports automatic fullscreen mode, ideal for kiosk displays, public monitors, and unattended deployments.

### Fullscreen Toggle Button

Click the fullscreen toggle button in the controls section to enter or exit fullscreen mode at any time. The button shows:
- **Fullscreen** icon when not in fullscreen - click to enter fullscreen
- **Exit Fullscreen** icon when in fullscreen - click to exit fullscreen

### Auto Fullscreen Configuration Options

1. **Query String Parameter** (Recommended for Kiosks): Set auto fullscreen via URL parameter
   ```
   ?autoFullscreen=true   # Automatically enter fullscreen on load
   ?autoFullscreen=false  # Normal mode (default)
   ```

2. **Config File**: Set the default auto fullscreen mode in `config.env`
   ```bash
   AUTO_FULLSCREEN=true   # Automatically enter fullscreen on load
   AUTO_FULLSCREEN=false  # Normal mode (default)
   ```

### Use Cases

- **Kiosk Displays**: Set `?autoFullscreen=true` in the kiosk browser URL to automatically enter fullscreen
- **Public Monitors**: Configure `AUTO_FULLSCREEN=true` in config.env for persistent fullscreen behavior
- **Unattended Displays**: Combine with browser kiosk mode for completely immersive display
- **Manual Control**: Use the fullscreen button for temporary fullscreen viewing

### Browser Compatibility

The fullscreen feature is compatible with modern browsers including:
- Chrome/Edge (desktop and mobile)
- Firefox (desktop and mobile)
- Safari (desktop and mobile)
- Opera

### How Auto Fullscreen Works

Due to browser security policies, fullscreen mode requires a user interaction (click/tap). When you use `?autoFullscreen=true`:

1. **Automatic Attempt**: The wallboard first attempts to enter fullscreen automatically after page load
2. **User Prompt**: If blocked by browser security (most common), a prominent overlay appears with an "Enter Fullscreen" button
3. **One Click**: Simply click the button to enter fullscreen mode
4. **Seamless Experience**: Once clicked, the wallboard enters fullscreen and the prompt disappears

This approach ensures compliance with browser security policies while providing the best user experience for kiosk deployments.

**Browser Security Note**: Modern browsers (Chrome, Edge, Firefox, Safari) require explicit user interaction before entering fullscreen mode. This is a security feature to prevent malicious websites from hijacking the screen. The prompt overlay provides this required interaction in a user-friendly way.

### Exiting Fullscreen

You can exit fullscreen mode by:
- Clicking the "Exit Fullscreen" button in the controls
- Pressing the `Esc` (Escape) key
- Using your browser's fullscreen exit shortcut

## Query String Configuration

The wallboard supports runtime configuration through URL query parameters, allowing you to customize behavior without editing files.

### Available Query Parameters

- `showProblemsOnly` - Show only monitors with problems (values: `true` or `false`)
- `refreshRate` - Set page refresh interval in seconds (minimum: 10)
- `configCheckRate` - Set config file check interval in seconds (minimum: 1)
- `theme` - Set the theme (values: `dark`, `light`, or `auto`)
- `autoFullscreen` - Automatically enter fullscreen mode on load (values: `true` or `false`)

### Examples

```
# Show only monitors with problems, refresh every 30 seconds
https://your-domain.com/status/?showProblemsOnly=true&refreshRate=30

# Show all monitors, refresh every 60 seconds
https://your-domain.com/status/?showProblemsOnly=false&refreshRate=60

# Check config changes every 10 seconds instead of default 5
https://your-domain.com/status/?configCheckRate=10

# Use light theme
https://your-domain.com/status/?theme=light

# Use auto theme (follows system preference)
https://your-domain.com/status/?theme=auto

# Auto fullscreen mode for kiosk displays
https://your-domain.com/status/?autoFullscreen=true

# Combine multiple parameters for kiosk setup
https://your-domain.com/status/?autoFullscreen=true&showProblemsOnly=true&theme=dark
```

### Security: Controlling Query String Overrides

By default, query string parameters are **enabled** to provide flexibility. However, you can disable them for security:

1. Edit your `config.env` file:
   ```bash
   ALLOW_QUERY_OVERRIDE=false
   ```

2. Save the file - the wallboard will automatically reload within 5 seconds

When `ALLOW_QUERY_OVERRIDE=false`, all query string parameters are ignored and only the `config.env` settings apply. This is useful for:
- Public displays where you don't want users modifying settings
- Controlled environments where consistency is important
- Security-sensitive deployments

### Configuration Priority

Settings are applied in this order (later overrides earlier):
1. **Default values** (built into the application)
2. **config.env file** (your persistent configuration)
3. **Query string parameters** (if `ALLOW_QUERY_OVERRIDE=true`)

## Setup

### 1. Get Your UptimeRobot API Token

1. Log in to your [UptimeRobot account](https://uptimerobot.com)
2. Navigate to Settings → API Settings
3. Generate or copy your API token

### 2. Configure the Application (IMPORTANT - Security Best Practices)

The application uses a single `config.env` file for all configuration, including your UptimeRobot API token. Follow these steps for secure storage:

#### Option A: Store Outside Webroot (MOST SECURE - Recommended)

1. Create the `config.env` file **one directory above** your webroot:
   ```bash
   # Example: If your webroot is /var/www/html/status
   # Create the config.env file at /var/www/html/config.env (one level up)
   
   # Secure method (avoids shell history):
   cat > /var/www/html/config.env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   WALLBOARD_TITLE=UptimeRobot – Current Status
   WALLBOARD_LOGO=
   SHOW_PROBLEMS_ONLY=false
   REFRESH_RATE=20
   CONFIG_CHECK_RATE=5
   ALLOW_QUERY_OVERRIDE=true
   THEME=dark
   EOF
   
   # Set restrictive permissions
   chmod 600 /var/www/html/config.env
   chown www-data:www-data /var/www/html/config.env  # Adjust user/group as needed
   ```

2. Or if your webroot is `/var/www/html` (the application is at root):
   ```bash
   # Create the config.env file at /var/www/config.env (one level up)
   
   # Secure method (avoids shell history):
   cat > /var/www/config.env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   WALLBOARD_TITLE=UptimeRobot – Current Status
   WALLBOARD_LOGO=
   SHOW_PROBLEMS_ONLY=false
   REFRESH_RATE=20
   CONFIG_CHECK_RATE=5
   ALLOW_QUERY_OVERRIDE=true
   THEME=dark
   EOF
   
   # Set restrictive permissions
   chmod 600 /var/www/config.env
   chown www-data:www-data /var/www/config.env  # Adjust user/group as needed
   ```

#### Option B: Store in Webroot (Fallback)

If you cannot store files outside the webroot:

1. Copy the example file and add your configuration:
   ```bash
   # Navigate to the application directory first
   cd /path/to/your/webroot/status  # Adjust to your actual path
   
   # Create the config.env file (secure method to avoid shell history)
   cp config.env.example config.env
   cat > config.env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   WALLBOARD_TITLE=My Company Status Dashboard
   WALLBOARD_LOGO=logo.png
   SHOW_PROBLEMS_ONLY=false
   REFRESH_RATE=20
   CONFIG_CHECK_RATE=5
   ALLOW_QUERY_OVERRIDE=true
   THEME=dark
   EOF
   
   # Set restrictive file permissions
   chmod 600 config.env
   chown www-data:www-data config.env  # Adjust user/group as needed
   ```

2. Verify `.htaccess` is working to block HTTP access to the file:
   ```bash
   curl http://your-domain.com/status/config.env
   # Should return 403 Forbidden (works for both HTTP and HTTPS)
   ```

3. **For NGINX Users**: If you're using NGINX instead of Apache, the `.htaccess` file won't work. Use the provided `nginx.conf.example` file:
   ```bash
   # Copy the NGINX configuration directives from nginx.conf.example
   # and add them to your NGINX server block configuration
   
   # Then test and reload NGINX
   sudo nginx -t
   sudo systemctl reload nginx
   
   # Verify protection is working
   curl http://your-domain.com/status/config.env
   # Should return 403 Forbidden
   ```

### 3. Deploy the Application

1. Upload all files to your web server
2. Ensure `.htaccess` file is present and Apache `mod_authz_core` is enabled
3. Access `index.html` in your browser

**Note:** The wallboard will automatically refresh when you modify the `config.env` file (within 5 seconds), so you can update your title or logo without manually reloading the page.

### 4. Customize Your Wallboard (Optional)

You can personalize the wallboard with various settings by editing the `config.env` file you created earlier:

1. Edit `config.env` to set your preferences:
   ```bash
   # UptimeRobot API Token (REQUIRED)
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   
   # Custom wallboard title (optional)
   WALLBOARD_TITLE=My Company Status Dashboard
   
   # Custom logo path (optional)
   # Can be a relative path, absolute path, or external URL
   WALLBOARD_LOGO=logo.png
   # Examples:
   #   WALLBOARD_LOGO=images/company-logo.svg
   #   WALLBOARD_LOGO=https://example.com/logo.png
   
   # Display options
   SHOW_PROBLEMS_ONLY=false      # Show only monitors with problems by default
   
   # Theme options
   THEME=dark                    # Theme: dark, light, or auto (follows system preference)
   
   # Auto fullscreen options
   AUTO_FULLSCREEN=false         # Automatically enter fullscreen on load (true/false)
   
   # Refresh intervals (in seconds)
   REFRESH_RATE=20               # How often to refresh data from API (min: 10)
   CONFIG_CHECK_RATE=5           # How often to check for config changes (min: 1)
   
   # Security
   ALLOW_QUERY_OVERRIDE=true     # Allow URL parameters to override settings
   ```

2. If using a logo, upload your logo file to the application directory (or use an external URL)

3. Save the file - the wallboard will automatically refresh within 5 seconds to show your changes!

**Notes:**
- All configuration options are optional except `UPTIMEROBOT_API_TOKEN`
- If no `config.env` file exists, default values will be used
- Logo should be reasonably sized (recommended max: 200x50 pixels)
- Supported logo formats: PNG, SVG, JPG, GIF
- The logo will be displayed in the wallboard header alongside the title
- Changes to `config.env` are detected automatically and the wallboard refreshes within 5 seconds

### 5. Verify Security

**Critical Security Checks:**

- [ ] `config.env` is **NOT** accessible via HTTP (should return 403 Forbidden)
- [ ] File permissions are set to `600` (readable only by owner)
- [ ] `config.env` is listed in `.gitignore`
- [ ] Never commit `config.env` to version control

## Security Notes

### Why These Security Measures Matter

Storing API keys in plain text files presents several risks:

- **Accidental exposure**: Files may be committed to version control
- **Web access**: Files in webroot may be served over HTTP
- **Server access**: Anyone with filesystem access can read the key
- **Backups**: Unencrypted backups may expose the key

### Security Layers Implemented

1. **`.htaccess` protection**: Blocks HTTP access to `.env` files (Apache only)
2. **File permissions**: Restricts filesystem access to web server user only
3. **`.gitignore`**: Prevents accidental commits to version control
4. **External storage**: Supports storing `config.env` file outside webroot
5. **Example template**: Provides `config.env.example` as safe reference

### For Production Environments

If you have access to more advanced secret management:

- Use environment variables (via `getenv()` in PHP)
- Use a secrets manager (AWS Secrets Manager, HashiCorp Vault, etc.)
- Use encrypted key storage with hardware security modules (HSM)

### References

- [OWASP Secrets Management Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html)
- [PHP: Keeping Secrets](https://www.php.net/manual/en/security.secrets.php)

## Troubleshooting

### "Missing UPTIMEROBOT_API_TOKEN" Error

- Check that `config.env` file exists in the correct location
- Verify the file contains `UPTIMEROBOT_API_TOKEN=your-token` format
- Check file permissions allow the web server to read it

### 403 Forbidden on PHP Script

- Verify `.htaccess` is not blocking PHP files (only `.env` files should be blocked)
- Check Apache configuration allows `.htaccess` overrides

### No Data Showing

- Check browser console for errors
- Verify your API token is valid
- Check that `uptimerobot_status.php` is accessible
- Review error logs (check `uptime_errors.log`)

## License

This project is open source and available for use and modification.
