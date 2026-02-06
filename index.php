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
    .pill.warn { background: #3b2a1a; color: var(--warn); border-color: #5e4123; }
    .pill.ok { background: #163327; color: #8af0c9; border-color: #184836; }
    .pill.paused { background: #3b2a1a; color: var(--warn); border-color: #5e4123; }
    
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
    .footer { color: var(--subtle); margin-top: 0.5rem; font-size: 0.85rem; }
    
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

  <script>
    // --- Configuration ---
    const ENDPOINT = '/status/uptimerobot_status.php'; // adjust if you host elsewhere
    const CONFIG_VERSION_ENDPOINT = '/status/config_version.php'; // endpoint to check config changes
    
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

      // Footer (optionally show pagination meta if sent)
      if (data.meta && (data.meta.next_cursor || data.meta.prev_cursor)) {
        footer.textContent = `Page size: ${mons.length} — Cursor: next=${data.meta.next_cursor || '∅'}`;
      } else {
        footer.textContent = '';
      }

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

    async function refresh() {
      const err = document.getElementById('error');
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
          throw new Error(statusMsg + errorMsg);
        }
        render(data);
      } catch (e) {
        err.textContent = 'Error: ' + e.message;
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
            // Config has changed! Refresh the wallboard
            console.log('Config file changed, refreshing wallboard...');
            currentConfigVersion = data.version;
            await refresh();
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
      
      // Set new intervals based on config
      refreshInterval = setInterval(refresh, config.refreshRate * 1000);
      configCheckInterval = setInterval(checkConfigVersion, config.configCheckRate * 1000);
    }

    // Events
    document.getElementById('refresh-btn').addEventListener('click', refresh);
    document.getElementById('toggle-problems').addEventListener('click', () => {
      onlyProblems = !onlyProblems;
      updateButtonText('toggle-problems', onlyProblems, 'Show All', 'Show Only Problems');
      refresh();
    });
    document.getElementById('toggle-paused').addEventListener('click', () => {
      showPaused = !showPaused;
      updateButtonText('toggle-paused', showPaused, 'Hide Paused', 'Show Paused');
      refresh();
    });
    document.getElementById('toggle-tags').addEventListener('click', () => {
      showTags = !showTags;
      updateButtonText('toggle-tags', showTags, 'Hide Tags', 'Show Tags');
      updateTagVisibility();
    });
    document.getElementById('toggle-filter').addEventListener('click', toggleFilterVisibility);
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
      refresh();
    });

    document.getElementById('clear-tag-filter').addEventListener('click', () => {
      selectedTags.clear();
      // Remove selected class from all pills
      document.querySelectorAll('.tag-filter-pill').forEach(pill => {
        pill.classList.remove('selected');
      });
      refresh();
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
