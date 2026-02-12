# UptimeRobot Wallboard

![Last Commit](https://img.shields.io/github/last-commit/BlindTrevor/Uptime-Robot-Wallboard)
![Issues](https://img.shields.io/github/issues/BlindTrevor/Uptime-Robot-Wallboard)
![Repo Size](https://img.shields.io/github/repo-size/BlindTrevor/Uptime-Robot-Wallboard)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/BlindTrevor/Uptime-Robot-Wallboard?display_name=release)

A real-time status wallboard for monitoring your UptimeRobot services. Display service health, uptime status, and alerts on a clean, customizable dashboard perfect for NOC displays, office monitors, or personal dashboards.

![Status Wallboard](https://github.com/user-attachments/assets/9dc7fbde-3e3e-471d-8cd0-2f2fc2296fa4)

## ✨ Key Features

- 🚀 **Easy Setup** - Built-in installer wizard for first-time configuration
- 📊 **Real-time Monitoring** - Automatic refresh with live status updates
- 🎨 **Dark/Light Themes** - Toggle between themes with system preference support
- 🔍 **Smart Filtering** - Show all monitors or only those with issues
- 🏷️ **Tag Filtering** - Filter monitors by tags with colored pills and multi-select
- ⏸️ **Paused Device Control** - Show/hide paused monitors with one click
- 🖥️ **Fullscreen Mode** - Auto-fullscreen support for kiosk displays
- 🎯 **Customizable** - Add your logo and custom title
- ⚙️ **Flexible Configuration** - URL parameters and config file options
- 🔄 **Auto-refresh** - Detects configuration changes automatically

## 🚀 Quick Start

### Prerequisites

- Web server with PHP 7.4+ (Apache or Nginx)
- UptimeRobot account with API access
- Basic knowledge of file permissions and web hosting

### Installation

1. **Clone or download** this repository to your web server:
   ```bash
   cd /var/www/html
   git clone https://github.com/BlindTrevor/Uptime-Robot-Wallboard.git status
   cd status
   ```

2. **Access the installer** by navigating to the application in your browser. If no configuration exists, you'll be automatically redirected to `installer.php`.

3. **Enter your UptimeRobot API token** (Get it from: [UptimeRobot Settings → API Settings](https://uptimerobot.com/dashboard#mySettings))

4. **Configure your preferences** in the installer form and click "Create Configuration"

5. **Done!** The wallboard will automatically load and display your monitors

### Manual Configuration (Alternative)

If you prefer manual setup:

1. Copy the example configuration:
   ```bash
   cp config.env.example config.env
   ```

2. Edit `config.env` and add your UptimeRobot API token:
   ```bash
   UPTIMEROBOT_API_TOKEN=your-api-token-here
   WALLBOARD_TITLE=My Status Dashboard
   SHOW_PROBLEMS_ONLY=false
   SHOW_PAUSED_DEVICES=false
   REFRESH_RATE=20
   THEME=dark
   ```

3. Set secure file permissions:
   ```bash
   chmod 600 config.env
   chown www-data:www-data config.env
   ```

4. Access `index.php` in your browser

## 📖 Configuration Options

Edit `config.env` to customize your wallboard:

| Option | Description | Default |
|--------|-------------|---------|
| `UPTIMEROBOT_API_TOKEN` | Your UptimeRobot API token (required) | - |
| `WALLBOARD_TITLE` | Custom title for your wallboard | `UptimeRobot – Current Status` |
| `WALLBOARD_LOGO` | Path to logo image or URL | (empty) |
| `SHOW_PROBLEMS_ONLY` | Show only monitors with issues | `false` |
| `SHOW_PAUSED_DEVICES` | Display paused monitors | `false` |
| `SHOW_TAGS` | Display tags on monitor cards | `true` |
| `REFRESH_RATE` | Data refresh interval (seconds) | `20` |
| `CONFIG_CHECK_RATE` | Config file check interval (seconds) | `5` |
| `THEME` | Theme: `dark`, `light`, or `auto` | `dark` |
| `AUTO_FULLSCREEN` | Auto-enter fullscreen on load | `false` |
| `ALLOW_QUERY_OVERRIDE` | Allow URL parameter overrides | `true` |
| `TAG_COLORS` | Custom tag color configuration (JSON format) | (empty) |
| `RECENT_EVENT_WINDOW_MINUTES` | Time window (in minutes) for highlighting recent events | `60` |

## 🎯 Usage

### Basic Usage

Simply open the wallboard in your browser. It will automatically:
- Load and display all your UptimeRobot monitors
- Refresh every 20 seconds (configurable)
- Show status with color-coded indicators:
  - 🟢 Green = Up/Operational
  - 🔴 Red = Down/Offline
  - 🟡 Yellow = Paused

### URL Parameters

Override settings temporarily using URL parameters:

```
# Show only problems, refresh every 30 seconds
https://your-domain.com/status/?showProblemsOnly=true&refreshRate=30

# Use light theme and auto-fullscreen for kiosk
https://your-domain.com/status/?theme=light&autoFullscreen=true

# Show paused devices and hide tags
https://your-domain.com/status/?showPausedDevices=true&showTags=false
```

### Control Buttons

- **Show Only Problems** - Toggle between all monitors and problem-only view
- **Show/Hide Paused** - Quickly toggle paused monitor visibility
- **Show/Hide Tags** - Toggle tag visibility on monitor cards
- **Show/Hide Filter** - Toggle the tag filter section visibility
- **Theme Toggle** - Switch between dark and light themes
- **Fullscreen** - Enter/exit fullscreen mode
- **Refresh Now** - Manually trigger data refresh

## 🔒 Security Best Practices

### Store Config Outside Webroot (Recommended)

The most secure approach is to store `config.env` **outside** your web-accessible directory:

```bash
# If your webroot is /var/www/html/status
# Store config at /var/www/html/config.env (one level up)

cat > /var/www/html/config.env << 'EOF'
UPTIMEROBOT_API_TOKEN=your-token-here
# ... other settings ...
EOF

chmod 600 /var/www/html/config.env
chown www-data:www-data /var/www/html/config.env
```

The application automatically searches parent directories for the config file.

### If Storing in Webroot

If you must store `config.env` in the webroot:

1. **Set restrictive permissions**:
   ```bash
   chmod 600 config.env
   ```

2. **Verify `.htaccess` protection** (Apache):
   ```bash
   curl http://your-domain.com/status/config.env
   # Should return 403 Forbidden
   ```

3. **For Nginx users**, add to your server block:
   ```nginx
   location ~ /config\.env {
       deny all;
       return 403;
   }
   ```

### Security Checklist

- ✅ `config.env` returns 403 when accessed via HTTP
- ✅ File permissions set to `600` (owner read/write only)
- ✅ `config.env` is in `.gitignore` (never commit secrets!)
- ✅ Consider storing outside webroot for maximum security

## 🎨 Themes

### Dark Theme (Default)
![Dark Theme](https://github.com/user-attachments/assets/dccafa9b-ad72-40fd-80ee-630e3270773a)

### Light Theme
![Light Theme](https://github.com/user-attachments/assets/cae35529-41ab-482f-a4d9-96bac8e7b38e)

Switch themes using:
- The theme toggle button in the UI
- `?theme=dark`, `?theme=light`, or `?theme=auto` URL parameter
- `THEME` setting in `config.env`

## 🏷️ Tag Filtering

Filter your monitors by tags for better organization and focused monitoring. Each monitor displays its assigned tags as colored pills, making it easy to visually identify and categorize services.

### Features

- **Colored Tag Pills** - Each tag displays with a unique, deterministic color for easy visual identification
- **Multi-Tag Filtering** - Select one or more tags to filter monitors (OR logic - shows monitors with ANY selected tag)
- **Collapsible Filter** - Toggle filter visibility with "Show Filter" / "Hide Filter" button
- **Clear Filters** - One-click to reset all tag selections
- **Real-time Updates** - Instant filtering as you select/deselect tags

### Filter Section

![Tag Filter Section](https://github.com/user-attachments/assets/c1ec9ab2-d3ad-45c4-9329-892301bb2818)

Click "Show Filter" to reveal all available tags. Click any tag to filter monitors:

### Filtered View

![Filtered by Production Tag](https://github.com/user-attachments/assets/041aa20a-ca42-4377-8287-0a2a03de19a6)

When tags are selected, only monitors with those tags are displayed. The selected tags appear highlighted with increased opacity and a shadow effect.

### How to Use

1. **View Tags** - All monitors display their tags as colored pills at the bottom of each card
2. **Show Filter** - Click the "Show Filter" button in the controls to reveal the tag filter section
3. **Select Tags** - Click one or more tags to filter monitors. Selected tags are highlighted
4. **Clear Filter** - Click "Clear Filter" to remove all selections and show all monitors
5. **Hide Filter** - Click "Hide Filter" to collapse the filter section

### Benefits

- **Quick Organization** - Instantly filter to Production, Staging, API, Database, or any custom tags
- **Multi-Environment** - Separate views for different environments or regions
- **Team Workflows** - Filter by team ownership, criticality, or service type
- **Visual Clarity** - Color-coded tags make it easy to scan and identify services at a glance

### Custom Tag Colors

You can customize tag colors using the `TAG_COLORS` configuration option in `config.env`. This accepts a JSON object with two optional configurations:

**1. Define acceptable color palette:**
```bash
TAG_COLORS={"acceptable":["#FF0000","#00FF00","blue","orange"]}
```

**2. Map specific tags to specific colors:**
```bash
TAG_COLORS={"tags":{"production":"red","staging":"yellow","development":"#3ad29f"}}
```

**3. Combine both approaches:**
```bash
TAG_COLORS={"acceptable":["#FF0000","#00FF00","blue"],"tags":{"critical":"red","warning":"yellow","info":"#abf7b1"}}
```

Supported color formats include:
- Hex codes: `#FF0000`, `#abf7b1`
- CSS color names: `red`, `blue`, `orange`, `green`
- RGB/HSL formats: `rgb(255,0,0)`, `hsl(120,100%,50%)`

If not specified, colors are automatically generated from tag names using a deterministic algorithm for consistent coloring across page reloads.

## 📜 Event History & Recent Event Highlighting

The wallboard includes an event history viewer that tracks all monitor status changes and system events. Recent events are automatically highlighted to improve visibility of recent activity.

### Features

- **Event Tracking** - Automatically logs all monitor state changes (up, down, paused)
- **Recent Event Highlighting** - Events within the configured time window are displayed with a prominent color and visual effects
- **Configurable Time Window** - Customize how long events are considered "recent" (default: 1 hour)
- **Event Sidebar** - View comprehensive event history with pagination
- **Circular Logging** - Optionally limit event history to a maximum number of events

### Recent Event Highlighting

Events that occurred within the configured time window are automatically highlighted with:
- Distinctive gradient background
- Prominent border color
- Subtle glow effect
- Enhanced hover state

This makes it easy to spot recent activity at a glance, especially useful for:
- Identifying ongoing issues
- Tracking recent deployments or changes
- Monitoring system stability over recent hours

### Configuration

Control the recent event time window in `config.env`:

```bash
# Highlight events from the last 60 minutes (default)
RECENT_EVENT_WINDOW_MINUTES=60

# Or customize to your needs:
# RECENT_EVENT_WINDOW_MINUTES=30   # 30 minutes
# RECENT_EVENT_WINDOW_MINUTES=120  # 2 hours
# RECENT_EVENT_WINDOW_MINUTES=1440 # 24 hours
```

If not configured, the default is 60 minutes (1 hour) to maintain backwards compatibility.

### How to Use

1. Click the **Events** button in the controls to open the event history sidebar
2. Recent events (within your configured time window) will appear with prominent highlighting
3. Older events appear with standard styling
4. Use pagination controls to browse through historical events

## 🖥️ Kiosk Mode

Perfect for dedicated monitoring displays:

1. Set up auto-fullscreen:
   ```bash
   # In config.env
   AUTO_FULLSCREEN=true
   ```

2. Or use URL parameter:
   ```
   https://your-domain.com/status/?autoFullscreen=true&showProblemsOnly=true
   ```

3. Combine with browser kiosk mode for an immersive experience

## 🔧 Troubleshooting

### No data showing

- Check browser console for errors (F12)
- Verify API token is correct in `config.env`
- Ensure `uptimerobot_status.php` is accessible
- Check `uptime_errors.log` for PHP errors

### Configuration not loading

- Verify `config.env` file exists and has correct permissions
- Check file format: `KEY=value` (no spaces around `=`)
- Ensure web server can read the file (`chmod 600` and correct ownership)

### 403 Forbidden errors

- For `.env` files: This is expected and correct (security protection)
- For PHP files: Check Apache/Nginx configuration allows PHP execution
- Verify `.htaccess` is not blocking legitimate requests

### Installer redirects immediately

- Config file already exists - use the main application
- To reconfigure, delete or rename existing `config.env`

## 📚 Advanced Features

### Paused Device Control

Control visibility of paused monitors:
- **Default**: Paused monitors are hidden
- **Show Paused**: Display paused monitors with warning indicators
- **Toggle Button**: Quick show/hide via UI button
- **URL Override**: `?showPausedDevices=true` or `false`

When shown, paused monitors:
- Display "PAUSED" status with ⏸️ icon
- Use warning (yellow/orange) color
- Are counted separately in the header
- Don't trigger the red "issues" background

### Query String Parameters

All configuration options can be overridden via URL (when `ALLOW_QUERY_OVERRIDE=true`):

```
?showProblemsOnly=true          # Show only problematic monitors
?showPausedDevices=true         # Show/hide paused monitors
?showTags=false                 # Show/hide tags on monitor cards
?refreshRate=30                 # Set refresh interval (seconds)
?theme=light                    # Set theme (dark/light/auto)
?autoFullscreen=true            # Auto-enter fullscreen
?configCheckRate=10             # Config check interval
?norefresh=true                 # Disable automatic refresh (accepts 'true' or '1')
```

#### Disable Automatic Refresh

Use the `?norefresh=true` parameter to disable all automatic API refreshes:

```
# Load data once without any automatic refreshes
https://your-domain.com/status/?norefresh=true

# Also accepts '1' as a value
https://your-domain.com/status/?norefresh=1

# Combine with other parameters
https://your-domain.com/status/?norefresh=true&showProblemsOnly=true
```

When `norefresh` is enabled:
- ✅ Initial API call loads data normally
- ⛔ No scheduled refreshes occur (no API polling)
- ⛔ Config change detection is disabled
- ✅ Manual "Refresh Now" button still works

**Use cases:**
- Viewing status briefly without triggering API quota usage
- Kiosk displays that don't need real-time updates
- Demos or screenshots where static data is sufficient
- Reducing server load when monitoring multiple tabs

### Auto-Refresh on Config Changes

The wallboard automatically detects changes to `config.env` and refreshes the display within 5 seconds. No manual reload needed when updating:
- Title or logo
- Theme settings
- Display filters
- Refresh rates

### Logo Display

Add your company logo to the wallboard:

1. Upload your logo file to the application directory
2. Edit `config.env`:
   ```bash
   WALLBOARD_LOGO=logo.png
   # Or use a URL:
   # WALLBOARD_LOGO=https://example.com/logo.png
   ```
3. Recommended size: 200x50 pixels or similar aspect ratio
4. Supported formats: PNG, SVG, JPG, GIF

## 🤝 Contributing

Contributions are welcome! Please feel free to:
- Report bugs or issues
- Suggest new features
- Submit pull requests
- Improve documentation

## 📄 License

This project is open source and available for use and modification.

## 🙏 Acknowledgments

- Built for [UptimeRobot](https://uptimerobot.com) API v3
- Font Awesome for icons
- Community contributions and feedback

## 📞 Support

For issues, questions, or feature requests, please use the [GitHub Issues](https://github.com/BlindTrevor/Uptime-Robot-Wallboard/issues) page.

---

**Made with ❤️ for monitoring enthusiasts**
