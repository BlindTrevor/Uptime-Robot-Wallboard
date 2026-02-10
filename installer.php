<?php
declare(strict_types=1);

// Installer script for creating config.env file
// Runs automatically when no config file is detected

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load shared configuration utilities
require_once __DIR__ . '/config-utils.php';

// Check if config already exists
$existingConfigPath = findConfigPath();
$configExists = ($existingConfigPath !== null);

// If config exists, redirect to main application
if ($configExists) {
    header('Location: index.php');
    exit;
}

// Function to find secure config path outside webroot
function findSecureConfigPath() {
    $currentDir = __DIR__;
    
    // Try to detect document root from server variables
    $documentRoot = null;
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    }
    
    // If we have document root, find first directory outside of it
    if ($documentRoot !== null && $documentRoot !== false) {
        $testPath = $currentDir;
        $maxLevels = 10; // Safety limit
        
        for ($i = 0; $i < $maxLevels; $i++) {
            $parentPath = dirname($testPath);
            
            // Stop if we've reached root or can't go further
            if ($parentPath === $testPath || $parentPath === '/') {
                break;
            }
            
            // Check if this path is outside document root
            if (strpos($parentPath, $documentRoot) === false) {
                // Found a path outside document root
                $configPath = $parentPath . '/config.env';
                // Check if we can write here
                if (is_writable($parentPath)) {
                    return [
                        'path' => $configPath,
                        'writable' => true,
                        'reason' => 'Outside webroot (most secure)'
                    ];
                } else {
                    return [
                        'path' => $configPath,
                        'writable' => false,
                        'reason' => 'Outside webroot but not writable'
                    ];
                }
            }
            
            $testPath = $parentPath;
        }
    }
    
    // Fallback: just use parent directory
    $parentPath = __DIR__ . '/../config.env';
    return [
        'path' => $parentPath,
        'writable' => is_writable(dirname($parentPath)),
        'reason' => 'One level up'
    ];
}

// Config path options
$currentDirPath = __DIR__ . '/config.env';     // Current directory (default)
$securePathInfo = findSecureConfigPath();      // Find secure path outside webroot
$parentDirPath = $securePathInfo['path'];      // Use secure path
$canWriteToParent = $securePathInfo['writable'];
$securePathReason = $securePathInfo['reason'];
$defaultConfigLocation = 'current';             // Default location

// Handle form submission
$errors = [];
$success = false;
$targetConfigPath = $currentDirPath; // Default to current directory

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiToken = trim($_POST['api_token'] ?? '');
    $title = trim($_POST['title'] ?? 'UptimeRobot – Current Status');
    $logo = trim($_POST['logo'] ?? '');
    $showProblemsOnly = isset($_POST['show_problems_only']) ? 'true' : 'false';
    $showPausedDevices = isset($_POST['show_paused_devices']) ? 'true' : 'false';
    $showTags = isset($_POST['show_tags']) ? 'true' : 'false';
    $refreshRate = trim($_POST['refresh_rate'] ?? '20');
    $configCheckRate = trim($_POST['config_check_rate'] ?? '5');
    $allowQueryOverride = isset($_POST['allow_query_override']) ? 'true' : 'false';
    $theme = $_POST['theme'] ?? 'dark';
    $autoFullscreen = isset($_POST['auto_fullscreen']) ? 'true' : 'false';
    $configLocation = $_POST['config_location'] ?? $defaultConfigLocation;
    $tagColors = trim($_POST['tag_colors'] ?? '');
    $eventViewerDefault = $_POST['event_viewer_default'] ?? 'hidden';
    $eventLoggingMode = $_POST['event_logging_mode'] ?? 'circular';
    $eventLoggingMaxEvents = trim($_POST['event_logging_max_events'] ?? '1000');
    $eventViewerItemsPerPage = trim($_POST['event_viewer_items_per_page'] ?? '50');
    
    // Handle logo file upload
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $uploadFile = $_FILES['logo_file'];
        $uploadName = $uploadFile['name'];
        $uploadSize = $uploadFile['size'];
        $uploadTmpPath = $uploadFile['tmp_name'];
        
        // Validate file type
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp'];
        $fileType = mime_content_type($uploadTmpPath);
        
        if (!in_array($fileType, $allowedTypes, true)) {
            $errors[] = 'Invalid logo file type. Please upload a PNG, JPG, GIF, SVG, or WebP image.';
        }
        
        // Validate file size (max 2MB)
        if ($uploadSize > 2 * 1024 * 1024) {
            $errors[] = 'Logo file is too large. Maximum size is 2MB.';
        }
        
        // Generate safe filename
        $fileExtension = pathinfo($uploadName, PATHINFO_EXTENSION);
        $safeFilename = 'logo_' . time() . '.' . preg_replace('/[^a-z0-9]/i', '', $fileExtension);
        $uploadDestination = __DIR__ . '/' . $safeFilename;
        
        // Move uploaded file if no errors so far
        if (empty($errors)) {
            if (move_uploaded_file($uploadTmpPath, $uploadDestination)) {
                // Set logo to the uploaded filename (relative path)
                $logo = $safeFilename;
                // Set secure permissions
                @chmod($uploadDestination, 0644);
            } else {
                $errors[] = 'Failed to save uploaded logo file. Please check permissions.';
            }
        }
    }
    
    // Determine target path based on user selection
    if ($configLocation === 'parent') {
        if ($canWriteToParent) {
            $targetConfigPath = $parentDirPath;
        } else {
            $errors[] = 'Cannot write to parent directory. Please check permissions or select current directory.';
        }
    } else {
        $targetConfigPath = $currentDirPath;
    }
    
    // Validate required fields
    if (empty($apiToken)) {
        $errors[] = 'UptimeRobot API Token is required.';
    }
    
    // Validate numeric fields
    if (!is_numeric($refreshRate) || (int)$refreshRate < 10) {
        $errors[] = 'Refresh Rate must be a number and at least 10 seconds.';
    }
    
    if (!is_numeric($configCheckRate) || (int)$configCheckRate < 1) {
        $errors[] = 'Config Check Rate must be a number and at least 1 second.';
    }
    
    // Validate theme
    if (!in_array($theme, ['dark', 'light', 'auto'], true)) {
        $errors[] = 'Invalid theme selected.';
    }
    
    // Validate tag colors (optional - only if provided)
    if (!empty($tagColors)) {
        $tagColorsData = @json_decode($tagColors, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Tag colors must be valid JSON format.';
        }
    }
    
    // Validate event viewer settings
    if (!in_array($eventViewerDefault, ['visible', 'hidden', 'disabled'], true)) {
        $errors[] = 'Invalid event viewer default setting.';
    }
    
    if (!in_array($eventLoggingMode, ['circular', 'forever'], true)) {
        $errors[] = 'Invalid event logging mode.';
    }
    
    if (!is_numeric($eventLoggingMaxEvents) || (int)$eventLoggingMaxEvents < 10) {
        $errors[] = 'Event logging max events must be a number and at least 10.';
    }
    
    if ($eventViewerItemsPerPage !== 'all' && (!is_numeric($eventViewerItemsPerPage) || (int)$eventViewerItemsPerPage < 10)) {
        $errors[] = 'Event viewer items per page must be a number at least 10, or "all".';
    }
    
    // If no errors, create the config file
    if (empty($errors)) {
        $configContent = "# Wallboard Configuration\n";
        $configContent .= "# Generated by installer on " . date('Y-m-d H:i:s') . "\n\n";
        $configContent .= "# UptimeRobot API Token (REQUIRED)\n";
        $configContent .= "# Get your API token from: https://uptimerobot.com → Settings → API Settings\n";
        $configContent .= "UPTIMEROBOT_API_TOKEN=$apiToken\n\n";
        $configContent .= "# Custom wallboard title (optional)\n";
        $configContent .= "WALLBOARD_TITLE=$title\n\n";
        $configContent .= "# Custom logo path (optional)\n";
        $configContent .= "WALLBOARD_LOGO=$logo\n\n";
        $configContent .= "# Display Options\n";
        $configContent .= "SHOW_PROBLEMS_ONLY=$showProblemsOnly\n";
        $configContent .= "SHOW_PAUSED_DEVICES=$showPausedDevices\n";
        $configContent .= "SHOW_TAGS=$showTags\n\n";
        $configContent .= "# Refresh Intervals (in seconds)\n";
        $configContent .= "REFRESH_RATE=$refreshRate\n";
        $configContent .= "CONFIG_CHECK_RATE=$configCheckRate\n\n";
        $configContent .= "# Query String Override Control\n";
        $configContent .= "ALLOW_QUERY_OVERRIDE=$allowQueryOverride\n\n";
        $configContent .= "# Theme Configuration\n";
        $configContent .= "THEME=$theme\n\n";
        $configContent .= "# Auto Fullscreen Mode\n";
        $configContent .= "AUTO_FULLSCREEN=$autoFullscreen\n\n";
        $configContent .= "# Tag Color Configuration (optional)\n";
        $configContent .= "TAG_COLORS=$tagColors\n\n";
        $configContent .= "# Event Viewer Configuration\n";
        $configContent .= "EVENT_VIEWER_DEFAULT=$eventViewerDefault\n";
        $configContent .= "EVENT_LOGGING_MODE=$eventLoggingMode\n";
        $configContent .= "EVENT_LOGGING_MAX_EVENTS=$eventLoggingMaxEvents\n";
        $configContent .= "EVENT_VIEWER_ITEMS_PER_PAGE=$eventViewerItemsPerPage\n";
        
        // Try to write the config file
        $writeResult = @file_put_contents($targetConfigPath, $configContent);
        
        if ($writeResult === false) {
            $errors[] = "Failed to write config file to: $targetConfigPath. Please check file permissions.";
        } else {
            // Set secure file permissions (readable/writable only by owner)
            @chmod($targetConfigPath, 0600);
            $success = true;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration Installer - UptimeRobot Wallboard</title>
    <style>
        :root {
            --bg: #0b1220;
            --card: #121c33;
            --border: #213155;
            --accent: #3a569c;
            --text: #e8eefc;
            --subtle: #9fb0d1;
            --ok: #3ad29f;
            --bad: #ff6b6b;
        }
        
        * { box-sizing: border-box; }
        
        body {
            margin: 0;
            padding: 2rem;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            color: var(--text);
        }
        
        .subtitle {
            color: var(--subtle);
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text);
        }
        
        .label-description {
            font-weight: normal;
            color: var(--subtle);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        select:focus {
            outline: none;
            border-color: var(--accent);
        }
        
        input[type="checkbox"],
        input[type="radio"] {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        
        .checkbox-group,
        .radio-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .checkbox-group label,
        .radio-group label {
            margin: 0;
        }
        
        .radio-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .radio-option {
            background: var(--bg);
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .radio-option:hover {
            border-color: var(--accent);
        }
        
        .radio-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .radio-option input[type="radio"]:checked + .radio-content {
            color: var(--ok);
        }
        
        .radio-option input[type="radio"]:checked ~ label::before {
            background: var(--ok);
        }
        
        .radio-content {
            flex: 1;
        }
        
        .radio-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .radio-description {
            font-size: 0.85rem;
            color: var(--subtle);
            line-height: 1.4;
        }
        
        .security-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 0.5rem;
        }
        
        .badge-recommended {
            background: rgba(58, 210, 159, 0.2);
            color: var(--ok);
        }
        
        .badge-default {
            background: rgba(58, 86, 156, 0.2);
            color: var(--accent);
        }
        
        button {
            background: var(--accent);
            color: var(--text);
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        button:hover {
            background: #4a6fa5;
        }
        
        .error {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid var(--bad);
            color: var(--bad);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .error ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }
        
        .success {
            background: rgba(58, 210, 159, 0.1);
            border: 1px solid var(--ok);
            color: var(--ok);
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .success h2 {
            margin: 0 0 1rem 0;
        }
        
        .info-box {
            background: rgba(58, 86, 156, 0.1);
            border: 1px solid var(--accent);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .required {
            color: var(--bad);
        }
        
        a {
            color: var(--accent);
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🚀 Configuration Installer</h1>
            <p class="subtitle">Welcome! Let's set up your UptimeRobot Wallboard.</p>
            
            <?php if ($success): ?>
                <div class="success">
                    <h2>✓ Configuration Created Successfully!</h2>
                    <p>Your config file has been created at:</p>
                    <p><strong><?php echo htmlspecialchars($targetConfigPath); ?></strong></p>
                    <p>You can now use your wallboard.</p>
                    <p><a href="index.php" style="color: var(--ok); font-weight: bold;">→ Go to Wallboard</a></p>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="info-box">
                    <strong>Before you begin:</strong> Get your UptimeRobot API token from 
                    <a href="https://uptimerobot.com" target="_blank">UptimeRobot</a> → Settings → API Settings
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>
                            UptimeRobot API Token <span class="required">*</span>
                            <div class="label-description">Your API token from UptimeRobot (required)</div>
                        </label>
                        <input type="text" name="api_token" required value="<?php echo htmlspecialchars($_POST['api_token'] ?? ''); ?>" placeholder="ur123456-abcdef1234567890">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Config File Location
                            <div class="label-description">Choose where to save your configuration file</div>
                        </label>
                        <div class="radio-options">
                            <label class="radio-option">
                                <div class="radio-group">
                                    <input type="radio" name="config_location" value="current" <?php echo (!isset($_POST['config_location']) || $_POST['config_location'] === $defaultConfigLocation) ? 'checked' : ''; ?>>
                                    <div class="radio-content">
                                        <div class="radio-title">
                                            Current Directory (Inside Webroot)
                                            <span class="security-badge badge-default">DEFAULT</span>
                                        </div>
                                        <div class="radio-description">
                                            Save to: <code><?php echo htmlspecialchars($currentDirPath); ?></code><br>
                                            Easier to manage but less secure. Protected by .htaccess rules.
                                        </div>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="radio-option <?php echo !$canWriteToParent ? 'disabled' : ''; ?>">
                                <div class="radio-group">
                                    <input 
                                        type="radio" 
                                        name="config_location" 
                                        value="parent" 
                                        <?php echo $canWriteToParent ? '' : 'disabled'; ?> 
                                        <?php echo isset($_POST['config_location']) && $_POST['config_location'] === 'parent' ? 'checked' : ''; ?>
                                        <?php if (!$canWriteToParent): ?>
                                            aria-label="Secure directory option - not available due to insufficient write permissions"
                                            aria-describedby="parent-dir-disabled-reason"
                                        <?php endif; ?>
                                    >
                                    <div class="radio-content">
                                        <div class="radio-title">
                                            Secure Directory (Outside Webroot)
                                            <span class="security-badge badge-recommended">RECOMMENDED</span>
                                        </div>
                                        <div class="radio-description">
                                            Save to: <code><?php echo htmlspecialchars($parentDirPath); ?></code><br>
                                            <strong>Best Security:</strong> <?php echo htmlspecialchars($securePathReason); ?>. 
                                            Config file cannot be accessed via web browser even if .htaccess fails.
                                            <?php if (!$canWriteToParent): ?>
                                                <br><span id="parent-dir-disabled-reason" style="color: var(--bad);">⚠ Not available: Cannot write to this directory. Check permissions.</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Wallboard Title
                            <div class="label-description">Custom title for your wallboard (optional)</div>
                        </label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? 'UptimeRobot – Current Status'); ?>" placeholder="UptimeRobot – Current Status">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Logo Path or URL
                            <div class="label-description">Enter a path/URL or upload a logo file (optional)</div>
                        </label>
                        <input type="text" name="logo" id="logo-input" value="<?php echo htmlspecialchars($_POST['logo'] ?? ''); ?>" placeholder="logo.png or https://example.com/logo.png">
                        <div style="margin-top: 0.75rem;">
                            <label for="logo-upload" style="display: inline-block; cursor: pointer; padding: 0.5rem 1rem; background: var(--border); border: 1px solid var(--accent); border-radius: 6px; font-size: 0.9rem;">
                                📁 Upload Logo File
                            </label>
                            <input type="file" name="logo_file" id="logo-upload" accept="image/*" style="display: none;">
                            <span id="upload-filename" style="margin-left: 0.75rem; color: var(--subtle); font-size: 0.9rem;"></span>
                        </div>
                        <div class="label-description" style="margin-top: 0.5rem;">
                            Uploaded files will be saved to the application directory. Supported formats: PNG, JPG, GIF, SVG, WebP
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Theme</label>
                        <select name="theme">
                            <option value="dark" <?php echo ($_POST['theme'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Dark</option>
                            <option value="light" <?php echo ($_POST['theme'] ?? '') === 'light' ? 'selected' : ''; ?>>Light</option>
                            <option value="auto" <?php echo ($_POST['theme'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto (System Preference)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Refresh Rate (seconds)
                            <div class="label-description">How often to refresh data (minimum: 10 seconds)</div>
                        </label>
                        <input type="number" name="refresh_rate" min="10" value="<?php echo htmlspecialchars($_POST['refresh_rate'] ?? '20'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Config Check Rate (seconds)
                            <div class="label-description">How often to check for config changes (minimum: 1 second)</div>
                        </label>
                        <input type="number" name="config_check_rate" min="1" value="<?php echo htmlspecialchars($_POST['config_check_rate'] ?? '5'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="show_problems_only" id="show_problems_only" <?php echo isset($_POST['show_problems_only']) ? 'checked' : ''; ?>>
                            <label for="show_problems_only">Show only monitors with problems by default</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="show_paused_devices" id="show_paused_devices" <?php echo isset($_POST['show_paused_devices']) ? 'checked' : ''; ?>>
                            <label for="show_paused_devices">Show paused monitors</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="show_tags" id="show_tags" <?php echo ($_SERVER['REQUEST_METHOD'] !== 'POST') || isset($_POST['show_tags']) ? 'checked' : ''; ?>>
                            <label for="show_tags">Show tags on monitor cards</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Tag Colors Configuration (optional)
                            <div class="label-description" id="tag-colors-description">Configure colors for tags. Leave empty for automatic colors.</div>
                        </label>
                        
                        <!-- Hidden input to store final JSON -->
                        <input type="hidden" name="tag_colors" id="tag_colors_json" value="<?php echo htmlspecialchars($_POST['tag_colors'] ?? ''); ?>">
                        
                        <div style="background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 1rem; margin-top: 0.5rem;">
                            <div style="margin-bottom: 1.5rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                    <strong style="color: var(--text);">Acceptable Colors</strong>
                                    <button type="button" onclick="addAcceptableColor()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--accent); color: var(--text); border: none; border-radius: 4px; cursor: pointer;">+ Add Color</button>
                                </div>
                                <div class="label-description" style="margin-bottom: 0.75rem;">Colors to randomly assign to tags (hex codes or CSS names)</div>
                                <div id="acceptable-colors-list"></div>
                            </div>
                            
                            <div>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                    <strong style="color: var(--text);">Specific Tag Colors</strong>
                                    <button type="button" onclick="addTagMapping()" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: var(--accent); color: var(--text); border: none; border-radius: 4px; cursor: pointer;">+ Add Mapping</button>
                                </div>
                                <div class="label-description" style="margin-bottom: 0.75rem;">Map specific tag names to specific colors</div>
                                <div id="tag-mappings-list"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="allow_query_override" id="allow_query_override" <?php echo ($_SERVER['REQUEST_METHOD'] !== 'POST') || isset($_POST['allow_query_override']) ? 'checked' : ''; ?>>
                            <label for="allow_query_override">Allow URL query parameters to override settings</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="auto_fullscreen" id="auto_fullscreen" <?php echo isset($_POST['auto_fullscreen']) ? 'checked' : ''; ?>>
                            <label for="auto_fullscreen">Auto-enter fullscreen mode on load</label>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--text);">Event Viewer Settings</h3>
                    
                    <div class="form-group">
                        <label>
                            Event Viewer Default State
                            <div class="label-description">Choose how the event viewer sidebar appears by default</div>
                        </label>
                        <select name="event_viewer_default">
                            <option value="hidden" <?php echo (!isset($_POST['event_viewer_default']) || $_POST['event_viewer_default'] === 'hidden') ? 'selected' : ''; ?>>Hidden (can be toggled)</option>
                            <option value="visible" <?php echo isset($_POST['event_viewer_default']) && $_POST['event_viewer_default'] === 'visible' ? 'selected' : ''; ?>>Visible</option>
                            <option value="disabled" <?php echo isset($_POST['event_viewer_default']) && $_POST['event_viewer_default'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Event Logging Mode
                            <div class="label-description">Choose whether to keep all events or only recent ones</div>
                        </label>
                        <select name="event_logging_mode">
                            <option value="circular" <?php echo (!isset($_POST['event_logging_mode']) || $_POST['event_logging_mode'] === 'circular') ? 'selected' : ''; ?>>Circular (keep recent events only)</option>
                            <option value="forever" <?php echo isset($_POST['event_logging_mode']) && $_POST['event_logging_mode'] === 'forever' ? 'selected' : ''; ?>>Forever (never delete events)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Maximum Events (Circular Mode)
                            <div class="label-description">Maximum number of events to keep when using circular logging (minimum 10)</div>
                        </label>
                        <input type="number" name="event_logging_max_events" min="10" value="<?php echo htmlspecialchars($_POST['event_logging_max_events'] ?? '1000'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            Events Per Page
                            <div class="label-description">Number of events to display per page (minimum 10, or "all")</div>
                        </label>
                        <input type="text" name="event_viewer_items_per_page" value="<?php echo htmlspecialchars($_POST['event_viewer_items_per_page'] ?? '50'); ?>" placeholder="50 or all">
                    </div>
                    
                    <button type="submit">Create Configuration</button>
                </form>
                
                <script>
                    // Handle logo file upload
                    document.getElementById('logo-upload').addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        const filenameDisplay = document.getElementById('upload-filename');
                        const logoInput = document.getElementById('logo-input');
                        
                        if (file) {
                            filenameDisplay.textContent = '✓ ' + file.name + ' selected';
                            filenameDisplay.style.color = 'var(--ok)';
                            // Clear the text input since we're uploading
                            logoInput.value = '';
                            logoInput.placeholder = 'Upload selected, or enter path/URL here to override';
                        } else {
                            filenameDisplay.textContent = '';
                            logoInput.placeholder = 'logo.png or https://example.com/logo.png';
                        }
                    });
                    
                    // Tag Colors Configuration UI
                    let colorIdCounter = 0;
                    
                    // HTML escape helper function
                    function escapeHtml(text) {
                        const div = document.createElement('div');
                        div.textContent = text;
                        return div.innerHTML;
                    }
                    
                    // Add a new acceptable color input
                    function addAcceptableColor(value = '') {
                        const id = 'acceptable-' + Date.now() + '-' + (colorIdCounter++);
                        const container = document.getElementById('acceptable-colors-list');
                        const div = document.createElement('div');
                        div.id = id;
                        div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
                        
                        // Create input element safely
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.value = value;
                        input.placeholder = '#FF0000 or red';
                        input.style.cssText = 'flex: 1; padding: 0.5rem; background: var(--card); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 0.9rem;';
                        input.onchange = updateTagColorsJSON;
                        
                        // Create remove button safely
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = 'Remove';
                        button.style.cssText = 'padding: 0.5rem 0.75rem; background: rgba(255, 107, 107, 0.2); color: var(--bad); border: 1px solid var(--bad); border-radius: 4px; cursor: pointer; font-size: 0.85rem;';
                        button.onclick = function() { removeAcceptableColor(id); };
                        
                        div.appendChild(input);
                        div.appendChild(button);
                        container.appendChild(div);
                        updateTagColorsJSON();
                    }
                    
                    // Remove an acceptable color
                    function removeAcceptableColor(id) {
                        const element = document.getElementById(id);
                        if (element) {
                            element.remove();
                            updateTagColorsJSON();
                        }
                    }
                    
                    // Add a new tag mapping
                    function addTagMapping(tagName = '', color = '') {
                        const id = 'mapping-' + Date.now() + '-' + (colorIdCounter++);
                        const container = document.getElementById('tag-mappings-list');
                        const div = document.createElement('div');
                        div.id = id;
                        div.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center;';
                        
                        // Create tag name input safely
                        const tagInput = document.createElement('input');
                        tagInput.type = 'text';
                        tagInput.value = tagName;
                        tagInput.placeholder = 'Tag name (e.g., critical)';
                        tagInput.style.cssText = 'flex: 1; padding: 0.5rem; background: var(--card); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 0.9rem;';
                        tagInput.onchange = updateTagColorsJSON;
                        
                        // Create color input safely
                        const colorInput = document.createElement('input');
                        colorInput.type = 'text';
                        colorInput.value = color;
                        colorInput.placeholder = '#FF0000 or red';
                        colorInput.style.cssText = 'flex: 1; padding: 0.5rem; background: var(--card); border: 1px solid var(--border); border-radius: 4px; color: var(--text); font-size: 0.9rem;';
                        colorInput.onchange = updateTagColorsJSON;
                        
                        // Create remove button safely
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = 'Remove';
                        button.style.cssText = 'padding: 0.5rem 0.75rem; background: rgba(255, 107, 107, 0.2); color: var(--bad); border: 1px solid var(--bad); border-radius: 4px; cursor: pointer; font-size: 0.85rem;';
                        button.onclick = function() { removeTagMapping(id); };
                        
                        div.appendChild(tagInput);
                        div.appendChild(colorInput);
                        div.appendChild(button);
                        container.appendChild(div);
                        updateTagColorsJSON();
                    }
                    
                    // Remove a tag mapping
                    function removeTagMapping(id) {
                        const element = document.getElementById(id);
                        if (element) {
                            element.remove();
                            updateTagColorsJSON();
                        }
                    }
                    
                    // Update the hidden JSON input field
                    function updateTagColorsJSON() {
                        const config = {};
                        
                        // Collect acceptable colors
                        const acceptableInputs = document.querySelectorAll('#acceptable-colors-list input[type="text"]');
                        const acceptable = [];
                        acceptableInputs.forEach(input => {
                            const value = input.value.trim();
                            if (value) acceptable.push(value);
                        });
                        if (acceptable.length > 0) {
                            config.acceptable = acceptable;
                        }
                        
                        // Collect tag mappings
                        const mappingDivs = document.querySelectorAll('#tag-mappings-list > div');
                        const tags = {};
                        mappingDivs.forEach(div => {
                            const inputs = div.querySelectorAll('input[type="text"]');
                            if (inputs.length === 2) {
                                const tagName = inputs[0].value.trim();
                                const color = inputs[1].value.trim();
                                if (tagName && color) {
                                    tags[tagName] = color;
                                }
                            }
                        });
                        if (Object.keys(tags).length > 0) {
                            config.tags = tags;
                        }
                        
                        // Update hidden input
                        const jsonInput = document.getElementById('tag_colors_json');
                        if (Object.keys(config).length > 0) {
                            jsonInput.value = JSON.stringify(config);
                        } else {
                            jsonInput.value = '';
                        }
                    }
                    
                    // Initialize from existing JSON (for error cases where form is resubmitted)
                    function initializeTagColors() {
                        const jsonInput = document.getElementById('tag_colors_json');
                        const existingValue = jsonInput.value;
                        
                        if (existingValue) {
                            try {
                                const config = JSON.parse(existingValue);
                                
                                // Load acceptable colors
                                if (config.acceptable && Array.isArray(config.acceptable)) {
                                    config.acceptable.forEach(color => addAcceptableColor(color));
                                }
                                
                                // Load tag mappings
                                if (config.tags && typeof config.tags === 'object') {
                                    Object.entries(config.tags).forEach(([tag, color]) => {
                                        addTagMapping(tag, color);
                                    });
                                }
                            } catch (e) {
                                console.error('Failed to parse existing tag colors JSON:', e);
                            }
                        }
                    }
                    
                    // Initialize on page load
                    document.addEventListener('DOMContentLoaded', function() {
                        initializeTagColors();
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
