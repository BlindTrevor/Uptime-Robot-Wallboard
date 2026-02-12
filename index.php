<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Status Wallboard – UptimeRobot (v3)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="Cache-Control" content="no-store" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
  <style>
    /* Dark theme (default) */
    :root {
      --bg: #0b1220;
      --card: #121c33;
      --border: #213155;
      --accent: #3a569c;
      --text: #e8eefc;
      --subtle: #9fb0d1;
      --muted: #b9c6e2;
      --ok: #3ad29f;
      --warn: #ffd27a;
      --bad: #ff6b6b;
      --bg-offline: #2a1515;
      --card-offline: #331a1a;
      --border-offline: #5e2323;
      --warning-bg: #3b2a1a;
      --warning-border: #5e4123;
    }

    /* Light theme */
    [data-theme="light"] {
      --bg: #f5f7fa;
      --card: #ffffff;
      --border: #d1d9e6;
      --accent: #4a6fa5;
      --text: #1a2332;
      --subtle: #5a6c85;
      --muted: #6b7b95;
      --ok: #28a745;
      --warn: #fd7e14;
      --bad: #dc3545;
      --bg-offline: #fff5f5;
      --card-offline: #ffe5e5;
      --border-offline: #ffcccc;
    }
    * { box-sizing: border-box; }
    html, body { height: 100%; }
    body {
      margin: 0; padding: 1rem;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background: var(--bg); color: var(--text);
      transition: background 0.3s ease;
      will-change: background;
    }
    body.has-offline { background: var(--bg-offline); }
    header { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .header-content { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    .logo { height: 50px; max-width: 400px; width: auto; object-fit: contain; }
    h1 { margin: 0 1rem 0 0; font-size: 1.25rem; }
    .meta { color: var(--subtle); }
    .row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .controls { margin: 0.6rem 0 0.8rem; display: flex; gap: 10px; flex-wrap: wrap; }
    button {
      background: var(--border); color: var(--text);
      border: 1px solid var(--accent); border-radius: 8px;
      padding: 6px 10px; cursor: pointer; font-weight: 600;
    }
    button:hover { background: #274071; }
    .pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 2px 10px; border-radius: 999px; font-size: 0.85rem;
      border: 1px solid var(--border);
    }
    .pill.warn { background: var(--warning-bg); color: var(--warn); border-color: var(--warning-border); }
    .pill.ok { background: #163327; color: #8af0c9; border-color: #184836; }
    .pill.paused { background: var(--warning-bg); color: var(--warn); border-color: var(--warning-border); }
    
    /* Down tags display */
    #down-tags {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      flex-wrap: wrap;
    }
    #down-tags .tag-pill {
      font-size: 0.7rem;
      padding: 2px 8px;
      margin: 2px;
    }
    
    /* Tag pills */
    .tag-pill {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      margin: 2px 4px 2px 0;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      border: 1px solid;
      transition: all 0.2s ease;
    }
    
    /* Tag filter section */
    .tag-filter-section {
      margin: 0.8rem 0;
      padding: 12px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
    }
    .tag-filter-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .tag-filter-title {
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text);
    }
    .tag-filter-clear {
      background: var(--bad);
      color: white;
      border: none;
      padding: 4px 12px;
      font-size: 0.8rem;
    }
    .tag-filter-clear:hover {
      opacity: 0.8;
    }
    .tag-filter-list {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .tag-filter-pill {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      border: 2px solid;
      cursor: pointer;
      transition: all 0.2s ease;
      opacity: 0.6;
    }
    .tag-filter-pill:hover {
      opacity: 0.8;
      transform: translateY(-1px);
    }
    .tag-filter-pill.selected {
      opacity: 1;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    .tags-container {
      display: flex;
      flex-wrap: wrap;
      margin-top: 6px;
    }
    .tags-container.hidden {
      display: none;
    }
    .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 12px; }
    .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
    .card.offline { background: var(--card-offline); border-color: var(--border-offline); }
    .name { font-weight: 700; font-size: 1.05rem; margin-bottom: 6px; }
    .status { margin-top: 8px; font-weight: 800; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; }
    .status i { font-size: 1.1em; }
    .status.up { color: var(--ok); }
    .status.seems_down, .status.down { color: var(--bad); }
    .status.paused { color: var(--warn); }
    .status.not_checked { color: var(--subtle); }
    .kv { font-size: 0.86rem; color: var(--muted); margin-top: 6px; }
    .small { font-size: 0.78rem; color: var(--subtle); margin-top: 6px; }
    .err { color: var(--bad); margin: 0.4rem 0; white-space: pre-wrap; }
    .footer { color: var(--subtle); margin-top: 0.5rem; font-size: 0.85rem; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
    
    /* Rate limit display */
    .rate-limit-info {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.8rem;
      background: var(--card);
      border: 1px solid var(--border);
      cursor: help;
      transition: all 0.2s ease;
    }
    .rate-limit-info:hover {
      background: var(--border);
      border-color: var(--accent);
    }
    .rate-limit-info i {
      font-size: 0.9em;
    }
    .rate-limit-info.warning {
      background: var(--warning-bg);
      border-color: var(--warning-border);
      color: var(--warn);
      animation: pulse-warning 2s ease-in-out infinite;
    }
    @keyframes pulse-warning {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    /* Rate limit tooltip */
    .rate-limit-tooltip {
      position: relative;
    }
    .rate-limit-tooltip .tooltip-content {
      visibility: hidden;
      position: absolute;
      bottom: 120%;
      left: 50%;
      transform: translateX(-50%);
      background: var(--card);
      border: 1px solid var(--accent);
      color: var(--text);
      padding: 8px 12px;
      border-radius: 6px;
      font-size: 0.75rem;
      white-space: nowrap;
      z-index: 1000;
      opacity: 0;
      transition: opacity 0.2s ease;
      pointer-events: none;
    }
    .rate-limit-tooltip:hover .tooltip-content {
      visibility: visible;
      opacity: 1;
    }
    .rate-limit-tooltip .tooltip-content::after {
      content: "";
      position: absolute;
      top: 100%;
      left: 50%;
      margin-left: -5px;
      border-width: 5px;
      border-style: solid;
      border-color: var(--accent) transparent transparent transparent;
    }
    
    /* Fullscreen prompt overlay */
    .fullscreen-prompt {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.9);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    .fullscreen-prompt.visible {
      opacity: 1;
      pointer-events: all;
    }
    .fullscreen-prompt-content {
      background: var(--card);
      border: 2px solid var(--accent);
      border-radius: 12px;
      padding: 2rem;
      max-width: 500px;
      text-align: center;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    }
    .fullscreen-prompt-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      color: var(--accent);
    }
    .fullscreen-prompt h2 {
      margin: 0 0 1rem;
      font-size: 1.5rem;
      color: var(--text);
    }
    .fullscreen-prompt p {
      margin: 0 0 1.5rem;
      color: var(--subtle);
      line-height: 1.5;
    }
    .fullscreen-prompt button {
      font-size: 1.1rem;
      padding: 12px 32px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 700;
      transition: all 0.2s ease;
    }
    .fullscreen-prompt button:hover {
      background: #274071;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(58, 86, 156, 0.4);
    }
    
    /* Red alert bar for offline services */
    .alert-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #FF0000;
      color: #ffffff;
      padding: 12px 20px;
      font-weight: 700;
      font-size: 1rem;
      text-align: center;
      box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.4);
      z-index: 10001;
      display: none;
      animation: slideUp 0.3s ease-out;
    }
    .alert-bar.visible {
      display: block;
    }
    .alert-bar-icon {
      margin-right: 8px;
      font-size: 1.2em;
    }
    .alert-bar-text {
      display: inline-block;
    }
    @keyframes slideUp {
      from {
        transform: translateY(100%);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    
    /* Event Viewer Sidebar */
    .event-sidebar {
      position: fixed;
      top: 0;
      right: -400px;
      width: 400px;
      height: 100vh;
      background: var(--card);
      border-left: 2px solid var(--border);
      box-shadow: -4px 0 12px rgba(0, 0, 0, 0.3);
      z-index: 10000;
      transition: right 0.3s ease;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      /* Hide scrollbar while maintaining scroll functionality */
      scrollbar-width: none; /* Firefox */
      -ms-overflow-style: none; /* IE and Edge */
    }
    .event-sidebar::-webkit-scrollbar {
      display: none; /* Chrome, Safari, Opera */
    }
    .event-sidebar.visible {
      right: 0;
    }
    .event-sidebar-header {
      position: sticky;
      top: 0;
      background: var(--card);
      border-bottom: 2px solid var(--border);
      padding: 16px;
      z-index: 1;
    }
    .event-sidebar-title {
      font-weight: 700;
      font-size: 1.1rem;
      color: var(--text);
      margin: 0 0 8px 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .event-sidebar-close {
      background: var(--border);
      border: none;
      color: var(--text);
      padding: 6px 10px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s ease;
    }
    .event-sidebar-close:hover {
      background: var(--accent);
    }
    .event-sidebar-content {
      padding: 16px;
      flex: 1;
    }
    .event-item {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 12px;
      margin-bottom: 12px;
      transition: all 0.2s ease;
    }
    .event-item:hover {
      border-color: var(--accent);
      transform: translateX(-2px);
    }
    .event-item-header {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .event-item-icon {
      font-size: 1.2em;
    }
    .event-item-icon.up { color: var(--ok); }
    .event-item-icon.down { color: var(--bad); }
    .event-item-icon.paused { color: var(--warn); }
    .event-item-icon.error { color: var(--bad); }
    .event-item-icon.transient { color: var(--warn); }
    .event-item-name {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--text);
      flex: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .event-item-type {
      font-size: 0.75rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 999px;
      text-transform: uppercase;
    }
    .event-item-type.up {
      background: #163327;
      color: #8af0c9;
      border: 1px solid #184836;
    }
    .event-item-type.down {
      background: #2a1515;
      color: #ff6b6b;
      border: 1px solid #5e2323;
    }
    .event-item-type.paused {
      background: var(--warning-bg);
      color: var(--warn);
      border: 1px solid var(--warning-border);
    }
    .event-item-type.error, .event-item-type.transient {
      background: var(--warning-bg);
      color: var(--warn);
      border: 1px solid var(--warning-border);
    }
    .event-item-details {
      font-size: 0.8rem;
      color: var(--muted);
      margin-top: 6px;
    }
    .event-item-time {
      font-size: 0.75rem;
      color: var(--subtle);
      margin-top: 4px;
    }
    /* Recent event highlighting */
    .event-item.recent {
      background: linear-gradient(135deg, rgba(58, 210, 159, 0.08) 0%, rgba(58, 86, 156, 0.08) 100%);
      border: 2px solid var(--accent);
      box-shadow: 0 0 12px rgba(58, 210, 159, 0.2);
    }
    [data-theme="light"] .event-item.recent {
      background: linear-gradient(135deg, rgba(40, 167, 69, 0.08) 0%, rgba(74, 111, 165, 0.08) 100%);
      border: 2px solid var(--accent);
      box-shadow: 0 0 12px rgba(40, 167, 69, 0.15);
    }
    .event-item.recent:hover {
      box-shadow: 0 0 16px rgba(58, 210, 159, 0.3);
    }
    
    /* Event Type Filter Pills */
    .event-type-filters {
      padding: 10px 16px;
      border-bottom: 1px solid var(--border);
      background: var(--bg);
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      align-items: center;
      justify-content: center;
    }
    .event-type-filter-pill {
      display: inline-flex;
      align-items: center;
      gap: 3px;
      padding: 4px 10px;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 600;
      border: 2px solid;
      cursor: pointer;
      transition: all 0.2s ease;
      text-transform: uppercase;
      user-select: none;
    }
    .event-type-filter-pill.down {
      color: var(--bad);
      border-color: var(--bad);
      background: rgba(255, 107, 107, 0.1);
    }
    .event-type-filter-pill.up {
      color: var(--ok);
      border-color: var(--ok);
      background: rgba(58, 210, 159, 0.1);
    }
    .event-type-filter-pill.paused {
      color: var(--warn);
      border-color: var(--warn);
      background: rgba(255, 210, 122, 0.1);
    }
    .event-type-filter-pill.error {
      color: var(--bad);
      border-color: var(--bad);
      background: rgba(255, 107, 107, 0.1);
    }
    .event-type-filter-pill:hover {
      opacity: 0.8;
      transform: translateY(-1px);
    }
    .event-type-filter-pill.inactive {
      opacity: 0.3;
      background: transparent;
    }
    .event-type-filter-pill.inactive:hover {
      opacity: 0.5;
    }
    
    .event-pagination {
      padding: 16px;
      border-top: 1px solid var(--border);
      background: var(--card);
      position: sticky;
      bottom: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .event-pagination button {
      padding: 6px 12px;
      font-size: 0.85rem;
    }
    .event-pagination button:disabled {
      opacity: 0.4;
      cursor: not-allowed;
    }
    .event-pagination-info {
      font-size: 0.8rem;
      color: var(--subtle);
    }
    .event-empty {
      text-align: center;
      padding: 40px 20px;
      color: var(--subtle);
      font-size: 0.9rem;
    }
    .event-empty i {
      font-size: 3em;
      margin-bottom: 16px;
      opacity: 0.3;
    }
    
    /* Adjust main content when sidebar is visible */
    body.event-sidebar-open {
      margin-right: 400px;
      transition: margin-right 0.3s ease;
    }
    
    @media (max-width: 768px) {
      .event-sidebar {
        width: 100%;
        right: -100%;
      }
      body.event-sidebar-open {
        margin-right: 0;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="header-content">
      <img id="logo" class="logo" style="display:none" alt="Logo" />
      <h1 id="title">UptimeRobot – Current Status</h1>
    </div>
    <div class="meta row">
      <span id="last-updated">Last updated: —</span>
      <span id="problem-pill" class="pill" style="display:none"></span>
      <span id="down-tags" style="display:none"></span>
    </div>
  </header>

  <div class="controls">
    <button id="toggle-problems">Show Only Problems</button>
    <button id="toggle-paused">Show Paused</button>
    <button id="toggle-filter" style="display: none;"><i class="fas fa-eye"></i> Show Filter</button>
    <button id="toggle-tags">Hide Tags</button>
    <button id="toggle-event-viewer" style="display: none;"><i class="fas fa-history"></i> Events</button>
    <button id="refresh-btn">Refresh Now</button>
    <button id="theme-toggle"><i class="fas fa-sun"></i> Light Mode</button>
    <button id="fullscreen-toggle"><i class="fas fa-expand"></i> Fullscreen</button>
  </div>

  <div id="tag-filter-section" class="tag-filter-section" style="display: none;">
    <div class="tag-filter-header">
      <span class="tag-filter-title"><i class="fas fa-filter"></i> Filter by Tags</span>
      <button id="clear-tag-filter" class="tag-filter-clear">Clear Filter</button>
    </div>
    <div id="tag-filter-list" class="tag-filter-list"></div>
  </div>

  <div id="error" class="err"></div>
  <div id="grid" class="grid"></div>
  <div id="footer" class="footer"></div>
  
  <!-- Fullscreen prompt overlay -->
  <div id="fullscreen-prompt" class="fullscreen-prompt">
    <div class="fullscreen-prompt-content">
      <div class="fullscreen-prompt-icon">
        <i class="fas fa-expand-arrows-alt"></i>
      </div>
      <h2>Fullscreen Mode Requested</h2>
      <p>Click the button below to enter fullscreen mode. This is required by your browser's security policy.</p>
      <button id="fullscreen-prompt-btn">
        <i class="fas fa-expand"></i> Enter Fullscreen
      </button>
    </div>
  </div>

  <!-- Red alert bar for offline services -->
  <div id="alert-bar" class="alert-bar" role="alert" aria-live="assertive">
    <i class="fas fa-exclamation-triangle alert-bar-icon" aria-hidden="true"></i>
    <span class="alert-bar-text" id="alert-bar-text"></span>
  </div>

  <!-- Event Viewer Sidebar -->
  <div id="event-sidebar" class="event-sidebar">
    <div class="event-sidebar-header">
      <div class="event-sidebar-title">
        <span><i class="fas fa-history"></i> Event History</span>
        <button id="event-sidebar-close" class="event-sidebar-close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div id="event-type-filters" class="event-type-filters" style="display: none;">
        <button class="event-type-filter-pill down" data-event-type="down">
          <i class="fas fa-times-circle"></i> Down
        </button>
        <button class="event-type-filter-pill up" data-event-type="up">
          <i class="fas fa-check-circle"></i> Up
        </button>
        <button class="event-type-filter-pill paused" data-event-type="paused">
          <i class="fas fa-pause-circle"></i> Paused
        </button>
        <button class="event-type-filter-pill error" data-event-type="error">
          <i class="fas fa-exclamation-triangle"></i> Error
        </button>
      </div>
    </div>
    <div id="event-sidebar-content" class="event-sidebar-content">
      <div class="event-empty">
        <div><i class="fas fa-clock"></i></div>
        <div>No events recorded yet</div>
      </div>
    </div>
    <div id="event-pagination" class="event-pagination" style="display: none;">
      <button id="event-prev-page" disabled><i class="fas fa-chevron-left"></i> Prev</button>
      <span id="event-pagination-info" class="event-pagination-info">Page 1 of 1</span>
      <button id="event-next-page" disabled>Next <i class="fas fa-chevron-right"></i></button>
    </div>
  </div>

  <script>
    // --- Configuration ---
    const ENDPOINT = '/status/uptimerobot_status.php'; // adjust if you host elsewhere
    const CONFIG_VERSION_ENDPOINT = '/status/config_version.php'; // endpoint to check config changes
    const EVENT_LOGGER_ENDPOINT = '/status/event-logger.php';
    const EVENT_VIEWER_ENDPOINT = '/status/event-viewer.php';
    
    // Time conversion constant
    const MS_PER_MINUTE = 60 * 1000;
    
    // Debounce delays (in milliseconds)
    // These are separate constants to allow independent tuning in the future
    const REFRESH_DEBOUNCE_DELAY = 500; // Delay for API refresh debouncing
    const RERENDER_DEBOUNCE_DELAY = 500; // Delay for re-render debouncing in norefresh mode
    
    // Default configuration (will be overridden by server config and/or query string)
    let config = {
      refreshRate: 20,
      configCheckRate: 5,
      showProblemsOnly: false,
      showPausedDevices: false,
      allowQueryOverride: true,
      theme: 'dark', // 'dark', 'light', or 'auto'
      autoFullscreen: false,
      showTags: true,
      rateLimitWarningThreshold: 3,
      eventViewerDefault: 'hidden',
      eventLoggingMode: 'circular',
      eventLoggingMaxEvents: 1000,
      eventViewerItemsPerPage: 50,
      recentEventWindowMinutes: 60,
      eventTypeFilterEnabled: true,
      eventTypeFilterDefaultDown: true,
      eventTypeFilterDefaultUp: true,
      eventTypeFilterDefaultPaused: true,
      eventTypeFilterDefaultError: true,
      norefresh: false,
    };

    // --- Theme Management ---
    // Cookie utilities
    const COOKIE_EXPIRY_DAYS = 365;
    const MS_PER_DAY = 864e5; // Milliseconds in a day
    
    function setCookie(name, value, days = COOKIE_EXPIRY_DAYS) {
      const expires = new Date(Date.now() + days * MS_PER_DAY).toUTCString();
      document.cookie = name + '=' + encodeURIComponent(value) + '; expires=' + expires + '; path=/; SameSite=Lax';
    }

    function getCookie(name) {
      return document.cookie.split('; ').reduce((r, v) => {
        const parts = v.split('=');
        return parts[0] === name ? decodeURIComponent(parts[1]) : r;
      }, '');
    }

    // Get system preference
    function getSystemTheme() {
      return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    // Apply theme to document
    function applyTheme(theme) {
      const resolvedTheme = theme === 'auto' ? getSystemTheme() : theme;
      document.documentElement.setAttribute('data-theme', resolvedTheme);
      
      // Update button text and icon
      const themeBtn = document.getElementById('theme-toggle');
      if (themeBtn) {
        if (resolvedTheme === 'light') {
          themeBtn.innerHTML = '<i class="fas fa-moon"></i> Dark Mode';
        } else {
          themeBtn.innerHTML = '<i class="fas fa-sun"></i> Light Mode';
        }
      }
      
      return resolvedTheme;
    }

    // Initialize theme from cookie, config, or system preference
    function initializeTheme() {
      // Priority: cookie > query string > config > auto (system preference)
      const cookieTheme = getCookie('theme');
      const queryTheme = new URLSearchParams(window.location.search).get('theme');
      
      let selectedTheme = config.theme || 'auto';
      
      // Apply query string if allowed and present
      if (config.allowQueryOverride && queryTheme && ['dark', 'light', 'auto'].includes(queryTheme)) {
        selectedTheme = queryTheme;
      }
      
      // Cookie overrides everything (user's explicit choice)
      if (cookieTheme && ['dark', 'light', 'auto'].includes(cookieTheme)) {
        selectedTheme = cookieTheme;
      }
      
      applyTheme(selectedTheme);
    }

    // Toggle theme
    function toggleTheme() {
      const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      applyTheme(newTheme);
      setCookie('theme', newTheme);
    }

    // Listen for system theme changes when in auto mode
    if (window.matchMedia) {
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const cookieTheme = getCookie('theme');
        if (!cookieTheme || cookieTheme === 'auto') {
          applyTheme('auto');
        }
      });
    }
    
    // --- Fullscreen Management ---
    function requestFullscreen() {
      const elem = document.documentElement;
      if (elem.requestFullscreen) {
        return elem.requestFullscreen().catch(err => {
          console.warn('Failed to enter fullscreen:', err);
          return Promise.reject(err);
        });
      } else if (elem.webkitRequestFullscreen) { // Safari
        elem.webkitRequestFullscreen();
        return Promise.resolve();
      } else if (elem.msRequestFullscreen) { // IE11
        elem.msRequestFullscreen();
        return Promise.resolve();
      }
      return Promise.reject(new Error('Fullscreen not supported'));
    }

    function exitFullscreen() {
      if (document.exitFullscreen) {
        document.exitFullscreen().catch(err => {
          console.warn('Failed to exit fullscreen:', err);
        });
      } else if (document.webkitExitFullscreen) { // Safari
        document.webkitExitFullscreen();
      } else if (document.msExitFullscreen) { // IE11
        document.msExitFullscreen();
      }
    }

    function isFullscreen() {
      return !!(document.fullscreenElement || document.webkitFullscreenElement || 
                document.msFullscreenElement);
    }

    function toggleFullscreen() {
      if (isFullscreen()) {
        exitFullscreen();
      } else {
        requestFullscreen();
      }
    }

    function updateFullscreenButton() {
      const btn = document.getElementById('fullscreen-toggle');
      if (btn) {
        if (isFullscreen()) {
          btn.innerHTML = '<i class="fas fa-compress"></i> Exit Fullscreen';
        } else {
          btn.innerHTML = '<i class="fas fa-expand"></i> Fullscreen';
        }
      }
    }
    
    function showFullscreenPrompt() {
      const prompt = document.getElementById('fullscreen-prompt');
      if (prompt) {
        prompt.classList.add('visible');
      }
    }
    
    function hideFullscreenPrompt() {
      const prompt = document.getElementById('fullscreen-prompt');
      if (prompt) {
        prompt.classList.remove('visible');
      }
    }

    // Listen for fullscreen changes
    ['fullscreenchange', 'webkitfullscreenchange', 'msfullscreenchange'].forEach(event => {
      document.addEventListener(event, () => {
        updateFullscreenButton();
        // Hide prompt when fullscreen is entered
        if (isFullscreen()) {
          hideFullscreenPrompt();
        }
      });
    });
    
    // Parse query string parameters
    function parseQueryString() {
      const params = new URLSearchParams(window.location.search);
      const queryConfig = {};
      
      // Parse showProblemsOnly
      if (params.has('showProblemsOnly')) {
        queryConfig.showProblemsOnly = params.get('showProblemsOnly') === 'true';
      }
      
      // Parse theme
      if (params.has('theme')) {
        const theme = params.get('theme');
        if (['dark', 'light', 'auto'].includes(theme)) {
          queryConfig.theme = theme;
        }
      }
      
      // Parse refreshRate (in seconds, minimum 10)
      if (params.has('refreshRate')) {
        const rate = parseInt(params.get('refreshRate'), 10);
        if (!isNaN(rate) && rate >= 10) {
          queryConfig.refreshRate = rate;
        }
      }
      
      // Parse configCheckRate (in seconds, minimum 1)
      if (params.has('configCheckRate')) {
        const rate = parseInt(params.get('configCheckRate'), 10);
        if (!isNaN(rate) && rate >= 1) {
          queryConfig.configCheckRate = rate;
        }
      }
      
      // Parse autoFullscreen
      if (params.has('autoFullscreen')) {
        queryConfig.autoFullscreen = params.get('autoFullscreen') === 'true';
      }
      
      // Parse showPausedDevices
      if (params.has('showPausedDevices')) {
        queryConfig.showPausedDevices = params.get('showPausedDevices') === 'true';
      }
      
      // Parse showTags
      if (params.has('showTags')) {
        queryConfig.showTags = params.get('showTags') === 'true';
      }
      
      // Parse eventViewer
      if (params.has('eventViewer')) {
        const eventViewer = params.get('eventViewer');
        if (['visible', 'hidden'].includes(eventViewer)) {
          queryConfig.eventViewer = eventViewer;
        }
      }
      
      // Parse norefresh (case-insensitive, accepts 'true' or '1')
      if (params.has('norefresh')) {
        const value = params.get('norefresh').toLowerCase();
        queryConfig.norefresh = (value === 'true' || value === '1');
      }
      
      return queryConfig;
    }
    
    // Apply configuration from server and query string
    function applyConfiguration(serverConfig) {
      // Start with server config
      if (serverConfig) {
        if (typeof serverConfig.showProblemsOnly === 'boolean') {
          config.showProblemsOnly = serverConfig.showProblemsOnly;
        }
        if (typeof serverConfig.showPausedDevices === 'boolean') {
          config.showPausedDevices = serverConfig.showPausedDevices;
        }
        if (typeof serverConfig.refreshRate === 'number') {
          config.refreshRate = serverConfig.refreshRate;
        }
        if (typeof serverConfig.configCheckRate === 'number') {
          config.configCheckRate = serverConfig.configCheckRate;
        }
        if (typeof serverConfig.allowQueryOverride === 'boolean') {
          config.allowQueryOverride = serverConfig.allowQueryOverride;
        }
        if (typeof serverConfig.theme === 'string' && ['dark', 'light', 'auto'].includes(serverConfig.theme)) {
          config.theme = serverConfig.theme;
        }
        if (typeof serverConfig.autoFullscreen === 'boolean') {
          config.autoFullscreen = serverConfig.autoFullscreen;
        }
        if (typeof serverConfig.showTags === 'boolean') {
          config.showTags = serverConfig.showTags;
        }
        if (serverConfig.tagColors) {
          config.tagColors = serverConfig.tagColors;
        }
        if (typeof serverConfig.rateLimitWarningThreshold === 'number') {
          config.rateLimitWarningThreshold = serverConfig.rateLimitWarningThreshold;
        }
        if (typeof serverConfig.eventViewerDefault === 'string' && ['visible', 'hidden', 'disabled'].includes(serverConfig.eventViewerDefault)) {
          config.eventViewerDefault = serverConfig.eventViewerDefault;
        }
        if (typeof serverConfig.eventLoggingMode === 'string') {
          config.eventLoggingMode = serverConfig.eventLoggingMode;
        }
        if (typeof serverConfig.eventLoggingMaxEvents === 'number') {
          config.eventLoggingMaxEvents = serverConfig.eventLoggingMaxEvents;
        }
        if (typeof serverConfig.eventViewerItemsPerPage !== 'undefined') {
          config.eventViewerItemsPerPage = serverConfig.eventViewerItemsPerPage;
        }
        if (typeof serverConfig.recentEventWindowMinutes === 'number' && serverConfig.recentEventWindowMinutes > 0 && isFinite(serverConfig.recentEventWindowMinutes)) {
          config.recentEventWindowMinutes = serverConfig.recentEventWindowMinutes;
        }
        if (typeof serverConfig.eventTypeFilterEnabled === 'boolean') {
          config.eventTypeFilterEnabled = serverConfig.eventTypeFilterEnabled;
        }
        if (typeof serverConfig.eventTypeFilterDefaultDown === 'boolean') {
          config.eventTypeFilterDefaultDown = serverConfig.eventTypeFilterDefaultDown;
        }
        if (typeof serverConfig.eventTypeFilterDefaultUp === 'boolean') {
          config.eventTypeFilterDefaultUp = serverConfig.eventTypeFilterDefaultUp;
        }
        if (typeof serverConfig.eventTypeFilterDefaultPaused === 'boolean') {
          config.eventTypeFilterDefaultPaused = serverConfig.eventTypeFilterDefaultPaused;
        }
        if (typeof serverConfig.eventTypeFilterDefaultError === 'boolean') {
          config.eventTypeFilterDefaultError = serverConfig.eventTypeFilterDefaultError;
        }
      }
      
      // Apply query string overrides if allowed
      if (config.allowQueryOverride) {
        const queryConfig = parseQueryString();
        Object.assign(config, queryConfig);
      }
      
      // Re-initialize theme after server configuration is applied
      // This respects the priority: cookie > query string > server config
      initializeTheme();
    }

    // State
    let onlyProblems = false;
    let showPaused = false; // Toggle for showing paused devices
    let showTags = true; // Toggle for showing tags
    let lastStatuses = new Map(); // id -> previous status (for change detection)
    let currentConfigVersion = null; // Track config file version
    let refreshInterval = null;
    let configCheckInterval = null;
    let initialConfigApplied = false; // Flag to prevent race conditions on initial load
    let onlyProblemsSetByQuery = false; // Track if query string set onlyProblems
    let showPausedSetByQuery = false; // Track if query string set showPaused
    let showTagsSetByQuery = false; // Track if query string set showTags
    let selectedTags = new Set(); // Selected tags for filtering
    let allTags = new Set(); // All available tags
    let filterVisible = false; // Track filter section visibility
    let lastData = null; // Store last fetched data for re-rendering in norefresh mode
    
    // Event viewer state
    let eventViewerVisible = false;
    let eventViewerEnabled = true; // Can be disabled via config
    let eventViewerSetByQuery = false;
    let eventCurrentPage = 1;
    let eventTotalPages = 1;
    let eventRefreshInterval = null;
    let eventTypeFilters = {
      down: true,
      up: true,
      paused: true,
      error: true
    };
    let allEvents = []; // Store all events for client-side filtering
    let currentPagination = null; // Store current pagination info
    
    // Rate limiting state
    let refreshInProgress = false; // Prevent concurrent API requests
    let refreshDebounceTimer = null; // Timer for debouncing rapid refresh calls
    let lastRefreshTime = Date.now(); // Timestamp of last successful refresh
    let apiCallCount = 0; // Counter for debugging API call frequency
    let pendingRefreshAfterComplete = false; // Flag to trigger refresh after current request completes
    
    // Initialize onlyProblems from query string early (before first refresh)
    // This ensures the first data load has the correct filter applied
    (function initializeOnlyProblems() {
      const queryConfig = parseQueryString();
      if (config.allowQueryOverride && typeof queryConfig.showProblemsOnly === 'boolean') {
        onlyProblems = queryConfig.showProblemsOnly;
        onlyProblemsSetByQuery = true;
      }
      if (config.allowQueryOverride && typeof queryConfig.showPausedDevices === 'boolean') {
        showPaused = queryConfig.showPausedDevices;
        showPausedSetByQuery = true;
      }
      if (config.allowQueryOverride && typeof queryConfig.showTags === 'boolean') {
        showTags = queryConfig.showTags;
        showTagsSetByQuery = true;
      }
      if (config.allowQueryOverride && typeof queryConfig.eventViewer === 'string') {
        eventViewerSetByQuery = true;
        // Will be processed after config is loaded
      }
    })();

    // Utilities
    function epochToLocal(epoch) {
      const n = Number(epoch);
      if (!n) return '—';
      const d = new Date(n * 1000);
      return isNaN(d.getTime()) ? '—' : d.toLocaleString();
    }
    const toClass = s => (s || 'unknown').toLowerCase().replace(/\s+/g, '_');

    /**
     * Escape HTML special characters to prevent XSS
     * @param {string} str - String to escape
     * @returns {string} Escaped string
     */
    function escapeHtml(str) {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    }

    /**
     * Update or create paused pill element
     * @param {number} totalCount - Total count of paused monitors
     * @param {number} visibleCount - Count of visible paused monitors
     * @param {boolean} showPaused - Whether paused devices are being shown
     * @param {HTMLElement} referencePill - Pill element to insert after
     */
    function updatePausedPill(totalCount, visibleCount, showPaused, referencePill) {
      const pausedPill = document.getElementById('paused-pill');
      
      // Remove pill if no paused devices or shouldn't be shown
      if (totalCount === 0) {
        pausedPill?.remove();
        return;
      }
      
      // Create pill if it doesn't exist
      let pill = pausedPill;
      if (!pill) {
        pill = document.createElement('span');
        pill.id = 'paused-pill';
        pill.className = 'pill paused';
        referencePill.parentNode.insertBefore(pill, referencePill.nextSibling);
      }
      
      // Update pill content based on visibility
      pill.style.display = 'inline-flex';
      pill.className = 'pill paused';
      if (showPaused) {
        pill.innerHTML = `<i class="fas fa-pause-circle"></i><span>${visibleCount} paused</span>`;
      } else {
        pill.innerHTML = `<i class="fas fa-eye-slash"></i><span>${totalCount} paused hidden</span>`;
      }
    }

    /**
     * Update button text based on state
     * @param {string} elementId - ID of button element
     * @param {boolean} state - Current state
     * @param {string} trueText - Text when state is true
     * @param {string} falseText - Text when state is false
     */
    function updateButtonText(elementId, state, trueText, falseText) {
      const element = document.getElementById(elementId);
      if (element) {
        element.textContent = state ? trueText : falseText;
      }
    }

    function formatDuration(epoch) {
      const n = Number(epoch);
      if (!n) return '';
      const seconds = Math.floor((Date.now() / 1000) - n);
      if (seconds < 0) return '';
      
      const days = Math.floor(seconds / 86400);
      const hours = Math.floor((seconds % 86400) / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;
      
      const parts = [];
      if (days > 0) parts.push(`${days}d`);
      if (hours > 0) parts.push(`${hours}h`);
      if (minutes > 0) parts.push(`${minutes}m`);
      if (parts.length === 0) parts.push(`${secs}s`); // Only show seconds if no other units
      
      return parts.slice(0, 2).join(' '); // Show max 2 units
    }

    function getStatusIcon(status) {
      const s = (status || '').toLowerCase();
      switch (s) {
        case 'up':
          return '<i class="fas fa-check-circle"></i>';
        case 'down':
        case 'seems_down':
          return '<i class="fas fa-times-circle"></i>';
        case 'paused':
          return '<i class="fas fa-pause-circle"></i>';
        default:
          return '<i class="fas fa-question-circle"></i>';
      }
    }

    function formatTags(tags) {
      if (!Array.isArray(tags) || !tags.length) return '';
      return tags
        .map(t => typeof t === 'object' && t !== null ? (t.name || '') : t)
        .filter(Boolean)
        .join(', ');
    }

    /**
     * Generate a deterministic, accessible color for a tag
     * Uses configured colors if available, otherwise generates from tag name
     * @param {string} tag - Tag name
     * @returns {object} Color object with background, text, and border colors
     */
    function getTagColor(tag) {
      // Check if we have tag color configuration
      const tagColors = config.tagColors;
      
      // First, check for specific tag mapping
      if (tagColors && tagColors.tags && tagColors.tags[tag]) {
        return convertColorToHSL(tagColors.tags[tag]);
      }
      
      // If acceptable colors are configured, pick one deterministically
      if (tagColors && tagColors.acceptable && tagColors.acceptable.length > 0) {
        // Use hash to pick a color from the acceptable list
        let hash = 0;
        for (let i = 0; i < tag.length; i++) {
          hash = (tag.charCodeAt(i) + ((hash << 5) - hash)) | 0;
        }
        const index = Math.abs(hash) % tagColors.acceptable.length;
        const selectedColor = tagColors.acceptable[index];
        return convertColorToHSL(selectedColor);
      }
      
      // Fallback: generate color from tag name (original behavior)
      // Simple hash function for deterministic colors
      // Using bitwise OR with 0 to keep result within 32-bit integer bounds
      let hash = 0;
      for (let i = 0; i < tag.length; i++) {
        hash = (tag.charCodeAt(i) + ((hash << 5) - hash)) | 0;
      }
      
      // Generate HSL color with fixed saturation and lightness for accessibility
      const hue = Math.abs(hash) % 360;
      const saturation = 65; // Good saturation for visibility
      const lightness = 45; // Balanced lightness
      
      // For dark theme
      const bgColor = `hsl(${hue}, ${saturation}%, ${lightness}%)`;
      const textColor = `hsl(${hue}, ${saturation}%, 95%)`; // Light text
      const borderColor = `hsl(${hue}, ${saturation}%, ${lightness + 10}%)`;
      
      return { bgColor, textColor, borderColor };
    }
    
    /**
     * Convert a color (hex, CSS name, or HSL) to HSL-based color object
     * Uses canvas for efficient color parsing without DOM manipulation
     * @param {string} color - Color value (hex, CSS name, or HSL string)
     * @returns {object} Color object with background, text, and border colors
     */
    function convertColorToHSL(color) {
      // Use canvas context for efficient color parsing
      const canvas = document.createElement('canvas');
      canvas.width = canvas.height = 1;
      const ctx = canvas.getContext('2d');
      
      // Set the color and read it back as rgba
      ctx.fillStyle = color;
      const computedColor = ctx.fillStyle;
      
      // Parse RGB from computed color (format: "#rrggbb" or "rgba(r, g, b, a)")
      let r, g, b;
      
      if (computedColor.startsWith('#')) {
        // Hex format
        const hex = computedColor.slice(1);
        r = parseInt(hex.substr(0, 2), 16) / 255;
        g = parseInt(hex.substr(2, 2), 16) / 255;
        b = parseInt(hex.substr(4, 2), 16) / 255;
      } else {
        // rgba format
        const rgbMatch = computedColor.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (!rgbMatch) {
          // Fallback to a default color if parsing fails
          return {
            bgColor: color,
            textColor: '#ffffff',
            borderColor: color
          };
        }
        r = parseInt(rgbMatch[1]) / 255;
        g = parseInt(rgbMatch[2]) / 255;
        b = parseInt(rgbMatch[3]) / 255;
      }
      
      // Convert RGB to HSL
      const max = Math.max(r, g, b);
      const min = Math.min(r, g, b);
      let h, s, l = (max + min) / 2;
      
      if (max === min) {
        h = s = 0; // achromatic
      } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
          case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
          case g: h = ((b - r) / d + 2) / 6; break;
          case b: h = ((r - g) / d + 4) / 6; break;
        }
      }
      
      h = Math.round(h * 360);
      s = Math.round(s * 100);
      l = Math.round(l * 100);
      
      // Use the calculated HSL values to create consistent theme-appropriate colors
      const bgColor = `hsl(${h}, ${s}%, ${l}%)`;
      const textColor = `hsl(${h}, ${s}%, ${l > 50 ? 10 : 95}%)`; // Dark text for light colors, light text for dark colors
      const borderColor = `hsl(${h}, ${s}%, ${Math.min(l + 10, 100)}%)`;
      
      return { bgColor, textColor, borderColor };
    }

    /**
     * Format tags as colored HTML pills
     * @param {Array} tags - Array of tag objects or strings
     * @returns {string} HTML string of tag pills
     */
    function formatTagPills(tags) {
      if (!Array.isArray(tags) || !tags.length) return '';
      
      const tagNames = tags
        .map(t => typeof t === 'object' && t !== null ? (t.name || '') : t)
        .filter(Boolean);
      
      return tagNames.map(tag => {
        const colors = getTagColor(tag);
        const escapedTag = escapeHtml(tag);
        return `<span class="tag-pill" style="background-color: ${colors.bgColor}; color: ${colors.textColor}; border-color: ${colors.borderColor};">${escapedTag}</span>`;
      }).join('');
    }

    /**
     * Extract all unique tags from monitors
     * @param {Array} monitors - Array of monitor objects
     * @returns {Array} Sorted array of unique tag names
     */
    function extractAllTags(monitors) {
      const tagsSet = new Set();
      monitors.forEach(monitor => {
        if (Array.isArray(monitor.tags)) {
          monitor.tags.forEach(tag => {
            const tagName = typeof tag === 'object' && tag !== null ? (tag.name || '') : tag;
            if (tagName) {
              tagsSet.add(tagName);
            }
          });
        }
      });
      return Array.from(tagsSet).sort();
    }

    /**
     * Extract unique tags from down/problematic monitors only
     * @param {Array} monitors - Array of monitor objects
     * @returns {Array} Sorted array of unique tag names from down monitors
     */
    function extractDownTags(monitors) {
      const tagsSet = new Set();
      monitors.forEach(monitor => {
        const status = (monitor.status || '').toLowerCase();
        // Only include tags from monitors that are down (not up and not paused)
        if (status !== 'up' && status !== 'paused' && Array.isArray(monitor.tags)) {
          monitor.tags.forEach(tag => {
            const tagName = typeof tag === 'object' && tag !== null ? (tag.name || '') : tag;
            if (tagName) {
              tagsSet.add(tagName);
            }
          });
        }
      });
      return Array.from(tagsSet).sort();
    }

    /**
     * Check if a monitor has any of the selected tags
     * @param {object} monitor - Monitor object
     * @param {Set} selectedTags - Set of selected tag names
     * @returns {boolean} True if monitor has any selected tag
     */
    function monitorHasSelectedTag(monitor, selectedTags) {
      if (!selectedTags.size) return true; // No filter active
      
      if (!Array.isArray(monitor.tags) || !monitor.tags.length) return false;
      
      const monitorTagNames = monitor.tags
        .map(t => typeof t === 'object' && t !== null ? (t.name || '') : t)
        .filter(Boolean);
      
      return monitorTagNames.some(tag => selectedTags.has(tag));
    }

    /**
     * Render the tag filter section
     * @param {Array} tags - Array of all available tags
     */
    function renderTagFilter(tags) {
      const filterSection = document.getElementById('tag-filter-section');
      const filterList = document.getElementById('tag-filter-list');
      const toggleButton = document.getElementById('toggle-filter');
      
      if (!tags.length) {
        filterSection.style.display = 'none';
        toggleButton.style.display = 'none';
        return;
      }
      
      // Show the toggle button if there are tags
      toggleButton.style.display = 'inline-block';
      
      // Respect the manual visibility state
      if (filterVisible) {
        filterSection.style.display = 'block';
      } else {
        filterSection.style.display = 'none';
      }
      
      filterList.innerHTML = tags.map(tag => {
        const colors = getTagColor(tag);
        const isSelected = selectedTags.has(tag);
        const selectedClass = isSelected ? 'selected' : '';
        const escapedTag = escapeHtml(tag);
        const escapedDataTag = escapeHtml(tag);
        return `<span class="tag-filter-pill ${selectedClass}" data-tag="${escapedDataTag}" style="background-color: ${colors.bgColor}; color: ${colors.textColor}; border-color: ${colors.borderColor};">${escapedTag}</span>`;
      }).join('');
    }

    /**
     * Toggle the visibility of the tag filter section
     */
    function toggleFilterVisibility() {
      const filterSection = document.getElementById('tag-filter-section');
      const toggleButton = document.getElementById('toggle-filter');
      
      filterVisible = !filterVisible;
      
      // Clear button content
      toggleButton.textContent = '';
      
      // Create icon element
      const icon = document.createElement('i');
      
      if (filterVisible) {
        filterSection.style.display = 'block';
        icon.className = 'fas fa-eye-slash';
        toggleButton.appendChild(icon);
        toggleButton.appendChild(document.createTextNode(' Hide Filter'));
      } else {
        filterSection.style.display = 'none';
        icon.className = 'fas fa-eye';
        toggleButton.appendChild(icon);
        toggleButton.appendChild(document.createTextNode(' Show Filter'));
      }
    }

    /**
     * Update the visibility of all tag elements based on showTags state
     */
    function updateTagVisibility() {
      const tagsContainers = document.querySelectorAll('.tags-container');
      const downTags = document.getElementById('down-tags');
      
      if (showTags) {
        tagsContainers.forEach(container => container.classList.remove('hidden'));
        if (downTags) {
          downTags.classList.remove('hidden');
          // Remove inline display override to allow natural display value
          // The render function will set the correct display value
        }
      } else {
        tagsContainers.forEach(container => container.classList.add('hidden'));
        if (downTags) {
          downTags.classList.add('hidden');
          // Override inline display with none
          downTags.style.display = 'none';
        }
      }
    }

    function isProblem(m) {
      const s = (m.status || '').toLowerCase();
      // Monitors with status !== 'up' are problems
      // Paused monitors are filtered by backend when showPausedDevices=false
      // When shown, they are counted separately from other issues
      return s !== 'up';
    }

    // Fetch
    async function loadData() {
      const params = new URLSearchParams();
      if (onlyProblems) params.set('only_problems', '1');
      
      // Pass showPausedDevices based on current state (button or query string)
      params.set('showPausedDevices', showPaused ? 'true' : 'false');

      const url = ENDPOINT + (params.toString() ? ('?' + params.toString()) : '');
      const res = await fetch(url, { cache: 'no-store' });
      const text = await res.text(); // read once
      
      // Try to parse JSON even on error responses
      let parsedData;
      try {
        parsedData = JSON.parse(text);
      } catch (e) {
        // If JSON parsing fails, throw with the raw text
        const errorDetail = e && e.message ? e.message : 'Unknown parsing error';
        throw new Error(`Invalid JSON: ${errorDetail}\nBody: ${text.slice(0, 400)}${text.length > 400 ? '…' : ''}`);
      }
      
      // If HTTP error but we have parsed JSON, return wrapper with status
      // Use wrapper to avoid mutating API response object
      if (!res.ok) {
        return {
          ...parsedData,
          _httpStatus: res.status
        };
      }
      
      return parsedData;
    }

    // Render
    function render(data) {
      const grid = document.getElementById('grid');
      const err = document.getElementById('error');
      const last = document.getElementById('last-updated');
      const pill = document.getElementById('problem-pill');
      const downTags = document.getElementById('down-tags');
      const footer = document.getElementById('footer');
      const title = document.getElementById('title');
      const logo = document.getElementById('logo');

      // Apply configuration from server
      if (data.config) {
        applyConfiguration(data.config);
        
        // Apply showProblemsOnly from config on first load only (if not already set by query string)
        if (!initialConfigApplied) {
          initialConfigApplied = true;
          // Only override onlyProblems if query string didn't set it
          if (!onlyProblemsSetByQuery) {
            onlyProblems = config.showProblemsOnly;
          }
          // Only override showPaused if query string didn't set it
          if (!showPausedSetByQuery) {
            showPaused = config.showPausedDevices;
          }
          // Only override showTags if query string didn't set it
          if (!showTagsSetByQuery) {
            showTags = config.showTags;
          }
          updateButtonText('toggle-problems', onlyProblems, 'Show All', 'Show Only Problems');
          updateButtonText('toggle-paused', showPaused, 'Hide Paused', 'Show Paused');
          updateButtonText('toggle-tags', showTags, 'Hide Tags', 'Show Tags');
          
          // Initialize event viewer on first config load
          initializeEventViewer();
        }
        
        if (data.config.title) {
          // Use textContent (not innerHTML) to prevent XSS
          title.textContent = data.config.title;
          document.title = data.config.title;
        }
        if (data.config.logo) {
          // Validate logo URL/path before setting
          const logoPath = data.config.logo;
          // Allow: relative paths ending in image extensions, http/https URLs, data URIs
          // Block: javascript:, file:, and other potentially dangerous schemes
          const isValidUrl = /^https?:\/\/.+\.(png|jpg|jpeg|gif|svg|webp)$/i.test(logoPath);
          const isValidPath = /^[a-zA-Z0-9_\/.-]+\.(png|jpg|jpeg|gif|svg|webp)$/i.test(logoPath) && !logoPath.includes('..');
          // Data URIs are limited to 100KB to prevent abuse
          const isDataUri = /^data:image\/(png|jpg|jpeg|gif|svg\+xml|webp);base64,/i.test(logoPath) && logoPath.length < 102400;
          
          if (isValidUrl || isValidPath || isDataUri) {
            logo.src = logoPath;
            logo.style.display = 'block';
            // Handle image load errors
            logo.onerror = function() {
              console.warn('Failed to load logo:', logoPath);
              logo.style.display = 'none';
            };
          } else {
            console.warn('Invalid logo path:', logoPath);
          }
        } else {
          logo.style.display = 'none';
        }
      }

      err.textContent = '';
      last.textContent = `Last updated: ${new Date().toLocaleString()}`;

      let mons = Array.isArray(data.monitors) ? data.monitors.slice() : [];
      
      // Detect status changes and log events using ALL monitors (including filtered ones)
      // This ensures we capture paused and offline device events even when they're not displayed
      const allMons = Array.isArray(data.all_monitors) ? data.all_monitors : mons;
      detectStatusChanges(allMons);

      // Extract all tags from monitors
      const allTagsList = extractAllTags(mons);
      allTags = new Set(allTagsList);
      
      // Render tag filter section
      renderTagFilter(allTagsList);
      
      // Apply tag filtering if tags are selected
      if (selectedTags.size > 0) {
        mons = mons.filter(m => monitorHasSelectedTag(m, selectedTags));
      }

      // Sort: problems first, then by name
      mons.sort((a, b) => {
        const ap = isProblem(a), bp = isProblem(b);
        if (ap !== bp) return ap ? -1 : 1;
        return (a.friendly_name || '').localeCompare(b.friendly_name || '');
      });

      // Problem count pill and paused count
      const pausedCount = mons.filter(m => (m.status || '').toLowerCase() === 'paused').length;
      const problemCount = mons.filter(m => {
        const s = (m.status || '').toLowerCase();
        return s !== 'up' && s !== 'paused';
      }).length;
      
      if (problemCount > 0) {
        pill.style.display = 'inline-flex';
        pill.className = 'pill warn';
        pill.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>${problemCount} issue${problemCount === 1 ? '' : 's'}</span>`;
      } else {
        pill.style.display = 'inline-flex';
        pill.className = 'pill ok';
        pill.innerHTML = '<i class="fas fa-check"></i><span>All good</span>';
      }
      
      // Update paused pill display
      const totalPausedCount = data.paused_count || pausedCount; // Use backend count if available
      updatePausedPill(totalPausedCount, pausedCount, config.showPausedDevices, pill);

      // Display tags for down items
      if (problemCount > 0) {
        const downTagNames = extractDownTags(mons);
        if (downTagNames.length > 0 && showTags) {
          downTags.style.display = 'inline-flex';
          downTags.innerHTML = downTagNames.map(tag => {
            const colors = getTagColor(tag);
            const escapedTag = escapeHtml(tag);
            return `<span class="tag-pill" style="background-color: ${colors.bgColor}; color: ${colors.textColor}; border-color: ${colors.borderColor};">${escapedTag}</span>`;
          }).join('');
        } else {
          downTags.style.display = 'none';
        }
      } else {
        downTags.style.display = 'none';
      }

      // Update body background based on offline status
      if (problemCount > 0) {
        document.body.classList.add('has-offline');
      } else {
        document.body.classList.remove('has-offline');
      }

      // Update red alert bar for offline services
      const alertBar = document.getElementById('alert-bar');
      const alertBarText = document.getElementById('alert-bar-text');
      if (problemCount > 0 && alertBar && alertBarText) {
        // Get list of offline services
        const offlineServices = mons.filter(m => isProblem(m))
          .map(m => m.friendly_name || m.url || `Service #${m.id}`)
          .slice(0, 5); // Show max 5 services
        
        // Build alert message
        let message = `${problemCount} service${problemCount > 1 ? 's' : ''} offline: `;
        if (offlineServices.length > 0) {
          message += offlineServices.join(', ');
          if (problemCount > 5) {
            message += `, and ${problemCount - 5} more`;
          }
        }
        
        alertBarText.textContent = message;
        alertBar.classList.add('visible');
      } else if (alertBar) {
        alertBar.classList.remove('visible');
      }

      // Build cards
      grid.innerHTML = mons.map(m => {
        const cls = `status ${toClass(m.status)}`;
        const statusIcon = getStatusIcon(m.status);
        const isOffline = isProblem(m);
        const cardClass = isOffline ? 'card offline' : 'card';
        const tagPills = formatTagPills(m.tags);

        // Determine the status label based on current status
        const status = (m.status || '').toLowerCase();
        let statusLabel = '';
        if (status === 'up') {
          const duration = formatDuration(m.last_check);
          statusLabel = `Up since: ${epochToLocal(m.last_check)}${duration ? ` (${duration})` : ''}`;
        } else if (status === 'down' || status === 'seems_down') {
          const duration = formatDuration(m.last_check);
          statusLabel = `Down since: ${epochToLocal(m.last_check)}${duration ? ` (${duration})` : ''}`;
        } else if (status === 'paused') {
          statusLabel = ''; // No status time for paused monitors
        } else {
          statusLabel = `Last check: ${epochToLocal(m.last_check)}`;
        }

        return `
          <div class="${cardClass}">
            <div class="name">${m.friendly_name || '—'}</div>
            <div class="${cls}">${statusIcon}${(m.status || 'UNKNOWN').toUpperCase()}</div>
            <div class="kv">${statusLabel}</div>
            ${tagPills ? `<div class="tags-container">${tagPills}</div>` : ''}
          </div>
        `;
      }).join('');

      // Footer - show rate limit info and optionally pagination meta
      let footerContent = '';
      
      // Rate limit display
      if (data.rateLimit) {
        const { limit, remaining, reset } = data.rateLimit;
        const threshold = config.rateLimitWarningThreshold || 3;
        
        if (remaining !== null && limit !== null) {
          const isLowQuota = remaining <= threshold;
          const warningClass = isLowQuota ? ' warning' : '';
          const icon = isLowQuota ? 'fa-exclamation-triangle' : 'fa-tachometer-alt';
          
          // Format reset time if available
          let resetTime = '';
          if (reset) {
            const resetDate = new Date(reset * 1000);
            resetTime = resetDate.toLocaleTimeString();
          }
          
          const tooltipText = `API Rate Limit: ${remaining}/${limit} requests remaining` + 
            (resetTime ? ` (resets at ${resetTime})` : '') +
            (isLowQuota ? ` - Consider increasing REFRESH_RATE in config.env` : '');
          
          footerContent += `
            <div class="rate-limit-tooltip">
              <span class="rate-limit-info${warningClass}" title="${tooltipText}" aria-label="${tooltipText}">
                <i class="fas ${icon}" aria-hidden="true"></i>
                <span>${remaining}/${limit} requests</span>
              </span>
              <div class="tooltip-content">${tooltipText}</div>
            </div>
          `;
        }
      }
      
      // Pagination info (if any)
      if (data.meta && (data.meta.next_cursor || data.meta.prev_cursor)) {
        footerContent += `<span>Page size: ${mons.length} — Cursor: next=${data.meta.next_cursor || '∅'}</span>`;
      }
      
      footer.innerHTML = footerContent;

      // Track status changes (you can add audio/visual cues here)
      const changes = [];
      for (const m of mons) {
        const prev = lastStatuses.get(m.id);
        if (prev && prev !== m.status) {
          changes.push({ id: m.id, name: m.friendly_name, from: prev, to: m.status });
        }
        lastStatuses.set(m.id, m.status);
      }
      // Example: simple console log on state change
      if (changes.length) {
        console.log('State changes:', changes);
      }
      
      // Update tag visibility based on current state
      updateTagVisibility();
    }

    // Re-render using last fetched data (for filter changes in norefresh mode)
    function rerender() {
      if (lastData) {
        render(lastData);
      } else {
        console.warn('[No Refresh Mode] No data available to re-render yet');
      }
    }

    // Debounced refresh function to prevent rapid successive API calls
    // This wraps the actual refresh logic with debouncing and request coalescing
    function debouncedRefresh() {
      // In norefresh mode, debounce re-renders with existing data instead of fetching
      if (config.norefresh) {
        // Clear any pending debounce timer
        if (refreshDebounceTimer) {
          clearTimeout(refreshDebounceTimer);
        }
        
        // Set new debounce timer for re-render
        refreshDebounceTimer = setTimeout(() => {
          console.log('[No Refresh Mode] Re-rendering with existing data');
          rerender();
        }, RERENDER_DEBOUNCE_DELAY);
        return;
      }
      
      // If a refresh is currently in progress, mark that we need another refresh after it completes
      if (refreshInProgress) {
        pendingRefreshAfterComplete = true;
        console.log('[API Rate Limit] Refresh in progress, scheduling refresh after completion');
        return;
      }
      
      // Clear any pending debounce timer
      if (refreshDebounceTimer) {
        clearTimeout(refreshDebounceTimer);
      }
      
      // Set new debounce timer
      // Multiple rapid calls will only result in one actual API call
      refreshDebounceTimer = setTimeout(() => {
        refresh();
      }, REFRESH_DEBOUNCE_DELAY);
    }

    async function refresh() {
      // Request coalescing: if a refresh is already in progress, skip this call
      if (refreshInProgress) {
        console.log('[API Rate Limit] Refresh already in progress, skipping duplicate request');
        return;
      }
      
      // Prevent concurrent requests
      refreshInProgress = true;
      apiCallCount++;
      
      const now = Date.now();
      const timeSinceLastRefresh = now - lastRefreshTime;
      console.log(`[API Call #${apiCallCount}] Time since last refresh: ${(timeSinceLastRefresh / 1000).toFixed(1)}s`);
      
      const err = document.getElementById('error');
      let errorAlreadyLogged = false; // Track if error was already logged
      
      try {
        const data = await loadData();
        if (!data.ok) {
          // Check if error is about missing config
          const errorMsg = data.error || 'Unknown error';
          if (errorMsg.includes('Missing UPTIMEROBOT_API_TOKEN') || errorMsg.includes('config.env')) {
            // Redirect to installer
            window.location.href = 'installer.php';
            return;
          }
          // Include HTTP status in error message if available
          const statusMsg = data._httpStatus ? `HTTP ${data._httpStatus}: ` : '';
          const fullErrorMsg = statusMsg + errorMsg;
          
          // Log error to event viewer
          await logSystemError(fullErrorMsg);
          errorAlreadyLogged = true;
          
          throw new Error(fullErrorMsg);
        }
        lastData = data; // Store data for re-rendering in norefresh mode
        render(data);
        lastRefreshTime = Date.now(); // Update timestamp on successful refresh
      } catch (e) {
        err.textContent = 'Error: ' + e.message;
        
        // Log error to event viewer only if not already logged
        if (!errorAlreadyLogged) {
          await logSystemError(e.message);
        }
      } finally {
        // Always reset the in-progress flag, even if error occurred
        refreshInProgress = false;
        
        // If a refresh was requested while we were in progress, trigger it now
        if (pendingRefreshAfterComplete) {
          pendingRefreshAfterComplete = false;
          console.log('[API Rate Limit] Executing pending refresh request');
          debouncedRefresh();
        }
      }
    }

    // Check if config has changed
    async function checkConfigVersion() {
      try {
        const res = await fetch(CONFIG_VERSION_ENDPOINT, { cache: 'no-store' });
        if (!res.ok) return; // Silently fail, don't disrupt user experience
        
        const data = await res.json();
        if (data.ok && data.version) {
          if (currentConfigVersion === null) {
            // First load, just store the version
            currentConfigVersion = data.version;
          } else if (currentConfigVersion !== data.version) {
            // Config has changed! Use debounced refresh to avoid immediate API call
            console.log('Config file changed, scheduling refresh...');
            currentConfigVersion = data.version;
            debouncedRefresh();
          }
        }
      } catch (e) {
        // Silently fail - config checking is a convenience feature
        // Don't disrupt the main wallboard functionality
        console.warn('Config version check failed:', e.message);
      }
    }

    // Update intervals based on configuration
    function updateIntervals() {
      // Clear existing intervals
      if (refreshInterval) {
        clearInterval(refreshInterval);
        refreshInterval = null;
      }
      if (configCheckInterval) {
        clearInterval(configCheckInterval);
        configCheckInterval = null;
      }
      if (eventRefreshInterval) {
        clearInterval(eventRefreshInterval);
        eventRefreshInterval = null;
      }
      
      // Skip setting up intervals if norefresh is enabled
      // This disables: periodic API refresh, config change detection, and event viewer refresh
      if (config.norefresh) {
        console.log('[No Refresh Mode] Automatic refresh disabled via querystring parameter');
        return;
      }
      
      // Set new intervals based on config
      refreshInterval = setInterval(refresh, config.refreshRate * 1000);
      configCheckInterval = setInterval(checkConfigVersion, config.configCheckRate * 1000);
      
      // Refresh events every 30 seconds if viewer is visible
      if (eventViewerVisible && eventViewerEnabled) {
        eventRefreshInterval = setInterval(loadEvents, 30000);
      }
    }

    // --- Event Viewer Functions ---
    
    // Initialize event viewer based on config
    function initializeEventViewer() {
      const toggleBtn = document.getElementById('toggle-event-viewer');
      const queryConfig = parseQueryString();
      
      // Check if disabled
      if (config.eventViewerDefault === 'disabled') {
        eventViewerEnabled = false;
        if (toggleBtn) toggleBtn.style.display = 'none';
        return;
      }
      
      eventViewerEnabled = true;
      if (toggleBtn) toggleBtn.style.display = 'inline-block';
      
      // Initialize event type filters from config
      eventTypeFilters.down = config.eventTypeFilterDefaultDown;
      eventTypeFilters.up = config.eventTypeFilterDefaultUp;
      eventTypeFilters.paused = config.eventTypeFilterDefaultPaused;
      eventTypeFilters.error = config.eventTypeFilterDefaultError;
      
      // Show or hide event type filter pills based on config
      const filterPillsEl = document.getElementById('event-type-filters');
      if (filterPillsEl) {
        filterPillsEl.style.display = config.eventTypeFilterEnabled ? 'flex' : 'none';
        
        // Apply initial inactive state to pills based on config
        if (config.eventTypeFilterEnabled) {
          const pills = filterPillsEl.querySelectorAll('.event-type-filter-pill');
          pills.forEach(pill => {
            const eventType = pill.getAttribute('data-event-type');
            if (eventType && !eventTypeFilters[eventType]) {
              pill.classList.add('inactive');
            }
          });
        }
      }
      
      // Determine initial visibility
      if (eventViewerSetByQuery && config.allowQueryOverride && queryConfig.eventViewer) {
        eventViewerVisible = queryConfig.eventViewer === 'visible';
      } else {
        eventViewerVisible = config.eventViewerDefault === 'visible';
      }
      
      // Apply initial state
      setEventViewerVisibility(eventViewerVisible);
      
      // Load events immediately if visible, and set up auto-refresh
      if (eventViewerVisible) {
        loadEvents();
        if (eventRefreshInterval) clearInterval(eventRefreshInterval);
        eventRefreshInterval = setInterval(loadEvents, 30000);
      }
    }
    
    // Toggle event viewer visibility
    function toggleEventViewer() {
      if (!eventViewerEnabled) return;
      eventViewerVisible = !eventViewerVisible;
      setEventViewerVisibility(eventViewerVisible);
      
      // Start/stop auto-refresh based on visibility
      if (eventViewerVisible) {
        loadEvents();
        if (eventRefreshInterval) clearInterval(eventRefreshInterval);
        eventRefreshInterval = setInterval(loadEvents, 30000);
      } else {
        if (eventRefreshInterval) {
          clearInterval(eventRefreshInterval);
          eventRefreshInterval = null;
        }
      }
    }
    
    // Set event viewer visibility
    function setEventViewerVisibility(visible) {
      const sidebar = document.getElementById('event-sidebar');
      const toggleBtn = document.getElementById('toggle-event-viewer');
      
      if (visible) {
        sidebar.classList.add('visible');
        document.body.classList.add('event-sidebar-open');
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-history"></i> Hide Events';
      } else {
        sidebar.classList.remove('visible');
        document.body.classList.remove('event-sidebar-open');
        if (toggleBtn) toggleBtn.innerHTML = '<i class="fas fa-history"></i> Events';
      }
    }
    
    // Load events from API
    async function loadEvents(page = null) {
      if (!eventViewerEnabled) return;
      
      try {
        const currentPage = page || eventCurrentPage;
        const url = `${EVENT_VIEWER_ENDPOINT}?page=${currentPage}&perPage=${config.eventViewerItemsPerPage}`;
        const res = await fetch(url, { cache: 'no-store' });
        
        if (!res.ok) {
          console.error('Failed to load events:', res.status);
          return;
        }
        
        const data = await res.json();
        if (data.ok) {
          allEvents = data.events;
          currentPagination = data.pagination;
          renderEvents();
        }
      } catch (e) {
        console.error('Error loading events:', e);
      }
    }
    
    // Render events in sidebar
    function renderEvents() {
      const content = document.getElementById('event-sidebar-content');
      const paginationEl = document.getElementById('event-pagination');
      
      if (!allEvents || allEvents.length === 0) {
        content.innerHTML = `
          <div class="event-empty">
            <div><i class="fas fa-clock"></i></div>
            <div>No events recorded yet</div>
          </div>
        `;
        paginationEl.style.display = 'none';
        return;
      }
      
      // Filter events based on active event types
      const filteredEvents = allEvents.filter(event => {
        // Normalize event type - handle 'transient' as 'error'
        const eventType = event.eventType === 'transient' ? 'error' : event.eventType;
        return eventTypeFilters[eventType] !== false;
      });
      
      // Show message if no events match filter
      if (filteredEvents.length === 0) {
        content.innerHTML = `
          <div class="event-empty">
            <div><i class="fas fa-filter"></i></div>
            <div>No events match the selected filters</div>
          </div>
        `;
        paginationEl.style.display = 'none';
        return;
      }
      
      // Render filtered events
      content.innerHTML = filteredEvents.map(event => {
        const icon = getEventIcon(event.eventType);
        const compactDuration = formatCompactDuration(event.timestamp);
        const absoluteTime = new Date(event.timestamp).toLocaleString();
        const details = formatEventDetails(event);
        
        // Check if event is recent (within configured time window)
        const eventTime = new Date(event.timestamp).getTime();
        const now = Date.now();
        const windowMs = config.recentEventWindowMinutes * MS_PER_MINUTE;
        const isRecent = (now - eventTime) <= windowMs;
        const recentClass = isRecent ? ' recent' : '';
        
        return `
          <div class="event-item${recentClass}">
            <div class="event-item-header">
              <i class="event-item-icon ${event.eventType} ${icon}"></i>
              <span class="event-item-name" title="${escapeHtml(event.monitorName)}">${escapeHtml(event.monitorName)}</span>
              <span class="event-item-type ${event.eventType}">${event.eventType}</span>
            </div>
            ${details ? `<div class="event-item-details">${details}</div>` : ''}
            <div class="event-item-time">${absoluteTime}${compactDuration ? ` (${compactDuration})` : ''}</div>
          </div>
        `;
      }).join('');
      
      // Update pagination
      if (currentPagination && currentPagination.totalPages > 1) {
        eventCurrentPage = currentPagination.page;
        eventTotalPages = currentPagination.totalPages;
        
        const prevBtn = document.getElementById('event-prev-page');
        const nextBtn = document.getElementById('event-next-page');
        const info = document.getElementById('event-pagination-info');
        
        prevBtn.disabled = currentPagination.page <= 1;
        nextBtn.disabled = currentPagination.page >= currentPagination.totalPages;
        info.textContent = `Page ${currentPagination.page} of ${currentPagination.totalPages}`;
        
        paginationEl.style.display = 'flex';
      } else {
        paginationEl.style.display = 'none';
      }
    }
    
    // Get icon for event type
    function getEventIcon(eventType) {
      const icons = {
        up: 'fas fa-check-circle',
        down: 'fas fa-times-circle',
        paused: 'fas fa-pause-circle',
        error: 'fas fa-exclamation-triangle',
        transient: 'fas fa-exclamation-circle'
      };
      return icons[eventType] || 'fas fa-circle';
    }
    
    // Format event details
    function formatEventDetails(event) {
      const parts = [];
      
      if (event.outageDuration) {
        const duration = formatDurationFromSeconds(event.outageDuration);
        parts.push(`Duration: ${duration}`);
      }
      
      if (event.url) {
        parts.push(`URL: ${escapeHtml(event.url)}`);
      }
      
      if (event.message) {
        parts.push(escapeHtml(event.message));
      }
      
      return parts.join(' • ');
    }
    
    // Format duration from seconds
    function formatDurationFromSeconds(seconds) {
      if (!seconds || seconds < 0) return '—';
      
      const days = Math.floor(seconds / 86400);
      const hours = Math.floor((seconds % 86400) / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      
      const parts = [];
      if (days > 0) parts.push(`${days}d`);
      if (hours > 0) parts.push(`${hours}h`);
      if (minutes > 0) parts.push(`${minutes}m`);
      
      return parts.length > 0 ? parts.join(' ') : '< 1m';
    }
    
    // Format time as relative (e.g., "5 minutes ago")
    function formatTimeAgo(timestamp) {
      const now = Date.now();
      const eventTime = new Date(timestamp).getTime();
      const diffMs = now - eventTime;
      const diffSeconds = Math.floor(diffMs / 1000);
      
      if (diffSeconds < 0) return 'just now';
      if (diffSeconds < 60) return `${diffSeconds} second${diffSeconds !== 1 ? 's' : ''} ago`;
      
      const diffMinutes = Math.floor(diffSeconds / 60);
      if (diffMinutes < 60) return `${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''} ago`;
      
      const diffHours = Math.floor(diffMinutes / 60);
      if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
      
      const diffDays = Math.floor(diffHours / 24);
      if (diffDays < 30) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
      
      const diffMonths = Math.floor(diffDays / 30);
      if (diffMonths < 12) return `${diffMonths} month${diffMonths !== 1 ? 's' : ''} ago`;
      
      const diffYears = Math.floor(diffMonths / 12);
      return `${diffYears} year${diffYears !== 1 ? 's' : ''} ago`;
    }
    
    // Format compact duration from timestamp (like "1h 14m" on tiles)
    function formatCompactDuration(timestamp) {
      const now = Date.now();
      const eventTime = new Date(timestamp).getTime();
      const diffMs = now - eventTime;
      
      if (diffMs < 0) return ''; // Event is in the future
      
      const seconds = Math.floor(diffMs / 1000);
      const days = Math.floor(seconds / 86400);
      const hours = Math.floor((seconds % 86400) / 3600);
      const minutes = Math.floor((seconds % 3600) / 60);
      const secs = seconds % 60;
      
      const parts = [];
      if (days > 0) parts.push(`${days}d`);
      if (hours > 0) parts.push(`${hours}h`);
      if (minutes > 0) parts.push(`${minutes}m`);
      if (parts.length === 0) parts.push(`${secs}s`); // Only show seconds if no other units
      
      return parts.slice(0, 2).join(' '); // Show max 2 units
    }
    
    // Log event to backend
    async function logEvent(event) {
      if (!eventViewerEnabled) return;
      
      try {
        const res = await fetch(EVENT_LOGGER_ENDPOINT, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(event),
          cache: 'no-store'
        });
        
        if (!res.ok) {
          console.error('Failed to log event:', res.status);
        }
      } catch (e) {
        console.error('Error logging event:', e);
      }
    }
    
    // Log system error to event viewer
    async function logSystemError(errorMessage) {
      if (!eventViewerEnabled) return;
      
      const event = {
        monitorId: 0, // System error, not specific to a monitor
        monitorName: 'System',
        url: '',
        eventType: 'error',
        timestamp: new Date().toISOString(),
        message: errorMessage
      };
      
      await logEvent(event);
    }
    
    // Detect and log status changes
    function detectStatusChanges(monitors) {
      if (!monitors || !eventViewerEnabled) return;
      
      monitors.forEach(m => {
        const currentStatus = (m.status || '').toLowerCase();
        const previousStatus = lastStatuses.get(m.id);
        
        // Skip if this is the first time we're seeing this monitor
        if (previousStatus === undefined) {
          lastStatuses.set(m.id, currentStatus);
          return;
        }
        
        // Detect status change
        if (previousStatus !== currentStatus) {
          const event = {
            monitorId: m.id,
            monitorName: m.friendly_name || m.url || `Monitor #${m.id}`,
            url: m.url || '',
            eventType: currentStatus,
            timestamp: new Date().toISOString(),
            previousStatus: previousStatus
          };
          
          // Note: Accurate outage duration would require tracking when the monitor went down,
          // which we don't have from the API. The m.last_check field represents the most
          // recent check time, not the downtime start. We'll let the event viewer calculate
          // duration from event timestamps if needed.
          
          logEvent(event);
          lastStatuses.set(m.id, currentStatus);
        }
      });
    }

    // Events
    document.getElementById('refresh-btn').addEventListener('click', () => {
      // Manual refresh button should trigger immediate refresh (not debounced)
      // Clear any pending debounce timer first to prevent conflicts
      if (refreshDebounceTimer) {
        clearTimeout(refreshDebounceTimer);
        refreshDebounceTimer = null;
      }
      refresh();
    });
    document.getElementById('toggle-problems').addEventListener('click', () => {
      onlyProblems = !onlyProblems;
      updateButtonText('toggle-problems', onlyProblems, 'Show All', 'Show Only Problems');
      debouncedRefresh();
    });
    document.getElementById('toggle-paused').addEventListener('click', () => {
      showPaused = !showPaused;
      updateButtonText('toggle-paused', showPaused, 'Hide Paused', 'Show Paused');
      debouncedRefresh();
    });
    document.getElementById('toggle-tags').addEventListener('click', () => {
      showTags = !showTags;
      updateButtonText('toggle-tags', showTags, 'Hide Tags', 'Show Tags');
      updateTagVisibility();
    });
    document.getElementById('toggle-filter').addEventListener('click', toggleFilterVisibility);
    document.getElementById('toggle-event-viewer').addEventListener('click', toggleEventViewer);
    document.getElementById('event-sidebar-close').addEventListener('click', () => {
      eventViewerVisible = false;
      setEventViewerVisibility(false);
      if (eventRefreshInterval) {
        clearInterval(eventRefreshInterval);
        eventRefreshInterval = null;
      }
    });
    document.getElementById('event-prev-page').addEventListener('click', () => {
      if (eventCurrentPage > 1) {
        loadEvents(eventCurrentPage - 1);
      }
    });
    document.getElementById('event-next-page').addEventListener('click', () => {
      if (eventCurrentPage < eventTotalPages) {
        loadEvents(eventCurrentPage + 1);
      }
    });
    
    // Event type filter pills - using event delegation
    const eventTypeFiltersEl = document.getElementById('event-type-filters');
    if (eventTypeFiltersEl) {
      eventTypeFiltersEl.addEventListener('click', (e) => {
        const pill = e.target.closest('.event-type-filter-pill');
        if (!pill) return;
        
        const eventType = pill.getAttribute('data-event-type');
        if (!eventType) return;
        
        // Toggle the filter state
        eventTypeFilters[eventType] = !eventTypeFilters[eventType];
        
        // Update pill appearance
        if (eventTypeFilters[eventType]) {
          pill.classList.remove('inactive');
        } else {
          pill.classList.add('inactive');
        }
        
        // Re-render events with new filter
        renderEvents();
      });
    }
    
    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);
    document.getElementById('fullscreen-toggle').addEventListener('click', toggleFullscreen);
    document.getElementById('fullscreen-prompt-btn').addEventListener('click', () => {
      requestFullscreen()
        .then(() => {
          hideFullscreenPrompt();
        })
        .catch(err => {
          console.error('Failed to enter fullscreen from prompt:', err);
          alert('Unable to enter fullscreen mode. Please try using the Fullscreen button in the controls.');
          hideFullscreenPrompt();
        });
    });

    // Tag filter events - using event delegation
    document.getElementById('tag-filter-list').addEventListener('click', (e) => {
      const pill = e.target.closest('.tag-filter-pill');
      if (!pill) return;
      
      const tag = pill.getAttribute('data-tag');
      if (!tag) return;
      
      // Toggle tag selection
      if (selectedTags.has(tag)) {
        selectedTags.delete(tag);
        pill.classList.remove('selected');
      } else {
        selectedTags.add(tag);
        pill.classList.add('selected');
      }
      
      // Re-render with current data
      debouncedRefresh();
    });

    document.getElementById('clear-tag-filter').addEventListener('click', () => {
      selectedTags.clear();
      // Remove selected class from all pills
      document.querySelectorAll('.tag-filter-pill').forEach(pill => {
        pill.classList.remove('selected');
      });
      debouncedRefresh();
    });

    // Auto fullscreen on load if enabled
    function handleAutoFullscreen() {
      if (config.autoFullscreen) {
        // Delay to ensure DOM is fully rendered and browser security policies allow fullscreen request
        // Most browsers require user interaction or slight delay after page load for fullscreen API
        setTimeout(() => {
          requestFullscreen()
            .catch(err => {
              // If automatic fullscreen fails (browser security policy), show prompt
              console.log('Auto fullscreen blocked by browser, showing user prompt');
              showFullscreenPrompt();
            });
        }, 500);
      }
    }

    // Initial load + polling
    refresh().finally(() => {
      // After first refresh (success or failure), update intervals based on loaded config
      updateIntervals();
      // Handle auto fullscreen after config is loaded
      handleAutoFullscreen();
    });
    
    // Initialize config version checking
    checkConfigVersion();

    // Initialize theme early (before first data load) with default config
    initializeTheme();
  </script>
</body>
</html>
