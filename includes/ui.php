<?php
/**
 * Shared SaaS shell components (Sidebar, Topbar, SVG Icons) for PharmaCare.
 */

function render_svg_icon($name, $class = 'icon') {
    $icons = [
        'logo' => '<svg class="' . $class . '" viewBox="0 0 28 28" fill="none"><rect x="3" y="3" width="22" height="22" rx="7" fill="rgba(255,255,255,0.16)"/><path d="M14 8v12M8 14h12" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/><circle cx="20.5" cy="7.5" r="2.5" fill="#14B8A6"/></svg>',
        'plus-circle' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>',
        'bar-chart' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><rect x="7" y="10" width="3" height="7" rx="1"/><rect x="12" y="6" width="3" height="11" rx="1"/><rect x="17" y="13" width="3" height="4" rx="1"/></svg>',
        'user' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'users' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'pill' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m10.5 20.5 10-10a4.95 4.95 0 1 0-7-7l-10 10a4.95 4.95 0 1 0 7 7Z"/><path d="m8.5 8.5 7 7"/></svg>',
        'receipt' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>',
        'wallet' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>',
        'search' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
        'menu' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>',
        'x' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        'check' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>',
        'alert' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>',
        'filter' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
        'rotate-ccw' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>',
        'sparkles' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg>',
        'calendar' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
        'package' => '<svg class="' . $class . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>'
    ];
    return $icons[$name] ?? '';
}

function render_app_shell_start($activePage, $pageTitle, $breadcrumb) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> — PharmaCare</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/../style.css'); ?>">
</head>
<body>
<div class="ambient-canvas" aria-hidden="true">
    <div class="ambient-orb orb-cobalt"></div>
    <div class="ambient-orb orb-teal"></div>
    <div class="ambient-orb orb-coral"></div>
    <div class="bg-texture"></div>
</div>
<div class="app-layout">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="appSidebar" aria-label="Primary Navigation">
        <div class="sidebar-header">
            <a href="sale.php" class="sidebar-brand">
                <span class="brand-mark"><?php echo render_svg_icon('logo', 'icon-lg'); ?></span>
                <span class="brand-text">
                    <strong>PharmaCare</strong>
                    <small>Sales &amp; Inventory</small>
                </span>
            </a>
            <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close navigation">
                <?php echo render_svg_icon('x'); ?>
            </button>
        </div>

        <div class="sidebar-section-label">Workspace</div>
        <nav class="sidebar-nav">
            <a href="sale.php" class="sidebar-link <?php echo $activePage === 'sale' ? 'active' : ''; ?>">
                <?php echo render_svg_icon('plus-circle'); ?>
                <span>New Sale</span>
            </a>
            <a href="report.php" class="sidebar-link <?php echo $activePage === 'report' ? 'active' : ''; ?>">
                <?php echo render_svg_icon('bar-chart'); ?>
                <span>Sales Reports</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="db-status-card">
                <span class="status-dot"></span>
                <div>
                    <strong>MySQL Connected</strong>
                    <small>DBMS Lab · Q13</small>
                </div>
            </div>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="mobile-menu-btn" id="sidebarToggle" aria-label="Open navigation">
                    <?php echo render_svg_icon('menu'); ?>
                </button>
                <div class="breadcrumb">
                    <span class="breadcrumb-root">PharmaCare</span>
                    <span class="breadcrumb-sep">/</span>
                    <span class="breadcrumb-current"><?php echo htmlspecialchars($breadcrumb); ?></span>
                </div>
            </div>

            <div class="topbar-right">
                <?php if ($activePage === 'report') : ?>
                    <div class="topbar-search">
                        <?php echo render_svg_icon('search', 'icon-sm'); ?>
                        <input type="text" id="topbarSearchMirror" placeholder="Quick search records..." aria-label="Quick search records">
                    </div>
                <?php endif; ?>

                <div class="user-profile" title="Blessen P Shaju · Roll No. 13">
                    <div class="avatar">BP</div>
                    <div class="user-meta">
                        <strong>Blessen P Shaju</strong>
                        <small>Pharmacist · #13</small>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
<?php
}

function render_app_shell_end() {
?>
        </main>
    </div>
</div>
<div class="toast-container" id="toastContainer" aria-live="polite"></div>
<script src="app.js?v=<?php echo filemtime(__DIR__ . '/../app.js'); ?>"></script>
</body>
</html>
<?php
}
