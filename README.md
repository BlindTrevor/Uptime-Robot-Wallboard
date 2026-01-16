# Uptime-Robot-Wallboard

A real-time status wallboard for monitoring UptimeRobot services using their API v3.

## Features

- Real-time monitoring of all your UptimeRobot monitors
- Visual status indicators (up, down, paused)
- Filter view to show only problematic services
- Automatic refresh every 20 seconds
- **Auto-refresh on config changes** - Front-end automatically reloads when configuration is updated
- Clean, modern UI with dark theme
- **Customizable wallboard title** - Set your own title for branding
- **Optional logo display** - Upload and display your company logo

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

### 3. Deploy the Application

1. Upload all files to your web server
2. Ensure `.htaccess` file is present and Apache `mod_authz_core` is enabled
3. Access `index.html` in your browser

**Note:** The wallboard will automatically refresh when you modify the `config.env` file (within 5 seconds), so you can update your title or logo without manually reloading the page.

### 4. Customize Your Wallboard (Optional)

You can personalize the wallboard with a custom title and logo by editing the `config.env` file you created earlier:

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
   ```

2. If using a logo, upload your logo file to the application directory (or use an external URL)

3. Save the file - the wallboard will automatically refresh within 5 seconds to show your changes!

**Notes:**
- Both title and logo are optional
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