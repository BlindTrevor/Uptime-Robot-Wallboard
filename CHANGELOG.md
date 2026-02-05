# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-05

### Added
- 🚀 **Easy Setup** - Built-in installer wizard (`installer.php`) for first-time configuration
- 📊 **Real-time Monitoring** - Automatic refresh with live status updates from UptimeRobot API v3
- 🎨 **Dark/Light Themes** - Toggle between themes with system preference support
- 🔍 **Smart Filtering** - Show all monitors or only those with issues via toggle button
- ⏸️ **Paused Device Control** - Show/hide paused monitors with one click
- 🖥️ **Fullscreen Mode** - Auto-fullscreen support for kiosk displays
- 🎯 **Customizable** - Add your logo and custom title via configuration
- ⚙️ **Flexible Configuration** - URL parameters and config file options
- 🔄 **Auto-refresh** - Detects configuration changes automatically without manual reload
- 📱 **Responsive Design** - Works on desktop, tablet, and mobile devices
- 🔒 **Security Features** - `.htaccess` protection for config files, secure file permissions guidance
- ⚡ **Demo Mode** - Demo data mode for testing and screenshots

### Features
- **Installation Wizard**: Easy setup for first-time users with guided configuration
- **Theme Support**: Dark theme (default), light theme, and auto theme based on system preferences
- **Configuration Options**:
  - Custom wallboard title
  - Custom logo support (local file or URL)
  - Show problems only filter
  - Paused devices visibility control
  - Configurable refresh rates (data and config check)
  - Auto-fullscreen for kiosk mode
  - Query parameter override support
- **Status Indicators**: Color-coded status with intuitive visual feedback
  - 🟢 Green = Up/Operational
  - 🔴 Red = Down/Offline
  - 🟡 Yellow = Paused
- **Control Buttons**: Quick access to filters, theme toggle, fullscreen, and manual refresh
- **Auto-Detection**: Automatic config file detection in parent directories for enhanced security
- **Error Logging**: Built-in error logging to `uptime_errors.log`

### Technical
- PHP 7.4+ compatible
- Works with Apache (`.htaccess` included) and Nginx (example config provided)
- UptimeRobot API v3 integration
- Font Awesome 6.5.1 for icons
- No external dependencies beyond PHP and web server
- Secure configuration file handling with search in parent directories

### Security
- Config file protection via `.htaccess` (Apache) and example Nginx config
- Recommended file permissions (600) for sensitive files
- Option to store config outside webroot
- Security best practices documentation

### Documentation
- Comprehensive README with installation, configuration, and usage instructions
- Quick start guide
- Configuration options reference table
- Troubleshooting section
- Security best practices
- Advanced features documentation
- Kiosk mode setup guide

[1.0.0]: https://github.com/BlindTrevor/Uptime-Robot-Wallboard/releases/tag/v1.0.0
