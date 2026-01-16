# Uptime-Robot-Wallboard

A real-time status wallboard for monitoring UptimeRobot services using their API v3.

## Features

- Real-time monitoring of all your UptimeRobot monitors
- Visual status indicators (up, down, paused)
- Filter view to show only problematic services
- Automatic refresh every 20 seconds
- Clean, modern UI with dark theme

## Setup

### 1. Get Your UptimeRobot API Token

1. Log in to your [UptimeRobot account](https://uptimerobot.com)
2. Navigate to Settings → API Settings
3. Generate or copy your API token

### 2. Configure the API Token (IMPORTANT - Security Best Practices)

The application needs your UptimeRobot API token to fetch monitor data. Follow these steps for secure storage using a `.env` file:

#### Option A: Store Outside Webroot (MOST SECURE - Recommended)

1. Create the `.env` file **one directory above** your webroot:
   ```bash
   # Example: If your webroot is /var/www/html/status
   # Create the .env file at /var/www/html/.env (one level up)
   
   # Secure method (avoids shell history):
   cat > /var/www/html/.env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   EOF
   
   # Set restrictive permissions
   chmod 600 /var/www/html/.env
   chown www-data:www-data /var/www/html/.env  # Adjust user/group as needed
   ```

2. Or if your webroot is `/var/www/html` (the application is at root):
   ```bash
   # Create the .env file at /var/www/.env (one level up)
   
   # Secure method (avoids shell history):
   cat > /var/www/.env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   EOF
   
   # Set restrictive permissions
   chmod 600 /var/www/.env
   chown www-data:www-data /var/www/.env  # Adjust user/group as needed
   ```

#### Option B: Store in Webroot (Fallback)

If you cannot store files outside the webroot:

1. Copy the example file and add your token:
   ```bash
   # Navigate to the application directory first
   cd /path/to/your/webroot/status  # Adjust to your actual path
   
   # Create the .env file (secure method to avoid shell history)
   cp .env.example .env
   cat > .env << 'EOF'
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   EOF
   
   # Set restrictive file permissions
   chmod 600 .env
   chown www-data:www-data .env  # Adjust user/group as needed
   ```

2. Verify `.htaccess` is working to block HTTP access to the file:
   ```bash
   curl http://your-domain.com/status/.env
   # Should return 403 Forbidden (works for both HTTP and HTTPS)
   ```

### 3. Deploy the Application

1. Upload all files to your web server
2. Ensure `.htaccess` file is present and Apache `mod_authz_core` is enabled
3. Access `index.html` in your browser

### 4. Verify Security

**Critical Security Checks:**

- [ ] `.env` is **NOT** accessible via HTTP (should return 403 Forbidden)
- [ ] File permissions are set to `600` (readable only by owner)
- [ ] `.env` is listed in `.gitignore`
- [ ] Never commit `.env` to version control

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
4. **External storage**: Supports storing `.env` file outside webroot
5. **Example template**: Provides `.env.example` as safe reference

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

- Check that `.env` file exists in the correct location
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