<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Gold Price Sync')</title>

    @vite([
        'resources/css/dashboard.css',
        'resources/js/dashboard.js',
    ])
</head>

<body>
    <div class="app-shell">

        <div
            id="sidebarOverlay"
            class="sidebar-overlay"
        ></div>

        <aside
            id="sidebar"
            class="sidebar-navigation"
        >
            <div class="sidebar-top">
                <div class="sidebar-brand">
                    <div class="sidebar-logo">
                        G
                    </div>

                    <div class="sidebar-brand-text">
                        <strong>Gold Price</strong>
                        <span>Sync</span>
                    </div>

                    <button
                        type="button"
                        id="sidebarCollapse"
                        class="sidebar-collapse"
                        aria-label="Collapse sidebar"
                    >
                        ‹
                    </button>
                </div>

                <div class="sidebar-system">
                    <span class="system-pulse"></span>
                    <span>LIVE SYSTEM</span>
                </div>
            </div>

            <nav class="sidebar-menu">
                <div class="menu-section">
                    <span class="menu-section-title">
                        MAIN
                    </span>

                    <a
                        href="{{ route('dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        <span class="sidebar-link-icon">⌂</span>

                        <span class="sidebar-link-text">
                            Dashboard
                        </span>
                    </a>
                </div>

                <div class="menu-section">
                    <span class="menu-section-title">
                        DATA HARGA
                    </span>

                    <a
                        href="{{ route('prices.jawa') }}"
                        class="sidebar-link {{ request()->routeIs('prices.jawa') ? 'active' : '' }}"
                    >
                        <span class="sidebar-link-icon">◉</span>

                        <span class="sidebar-link-text">
                            Jawa / Nasional
                        </span>
                    </a>

                    <a
                        href="{{ route('prices.kalimantan') }}"
                        class="sidebar-link {{ request()->routeIs('prices.kalimantan') ? 'active' : '' }}"
                    >
                        <span class="sidebar-link-icon">◉</span>

                        <span class="sidebar-link-text">
                            Kalimantan / Sulawesi
                        </span>
                    </a>

                    <a
                        href="{{ route('prices.sumatera') }}"
                        class="sidebar-link {{ request()->routeIs('prices.sumatera') ? 'active' : '' }}"
                    >
                        <span class="sidebar-link-icon">◉</span>

                        <span class="sidebar-link-text">
                            Sumatera / Bali / Lombok
                        </span>
                    </a>

                    <a
                        href="{{ route('prices.logam-mulia') }}"
                        class="sidebar-link {{ request()->routeIs('prices.logam-mulia') ? 'active' : '' }}"
                    >
                        <span class="sidebar-link-icon">◆</span>

                        <span class="sidebar-link-text">
                            Logam Mulia
                        </span>
                    </a>
                </div>

                <div class="menu-section">
                    <span class="menu-section-title">
                        AUTOMATION
                    </span>

                    <a
                        href="{{ route('dashboard') }}#automation"
                        class="sidebar-link"
                    >
                        <span class="sidebar-link-icon">↗</span>

                        <span class="sidebar-link-text">
                            Sinkronisasi Canva
                        </span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-footer">
                <div class="sidebar-footer-card">
                    <div class="sidebar-footer-icon">
                        C
                    </div>

                    <div class="sidebar-footer-info">
                        <div class="sidebar-footer-heading">
                            <span>Canva</span>

                            <span
                                id="sidebarCanvaDot"
                                class="status-dot checking"
                            ></span>
                        </div>

                        <strong id="sidebarCanvaStatus">
                            Checking
                        </strong>
                    </div>
                </div>

                <small>
                    Gold Price Sync
                </small>
            </div>
        </aside>

        <div class="app-main">
            <header class="top-navbar">
                <div class="navbar-left">
                    <button
                        type="button"
                        id="sidebarToggle"
                        class="sidebar-toggle"
                        aria-label="Toggle sidebar"
                    >
                        ☰
                    </button>

                    <div class="breadcrumb">
                        <span class="breadcrumb-product">
                            Gold Price Sync
                        </span>

                        <span class="breadcrumb-separator">
                            /
                        </span>

                        <strong>
                            @yield('page-name', 'Dashboard')
                        </strong>
                    </div>
                </div>

                <div class="navbar-right">
                    <div class="navbar-status">
                        <span
                            id="canvaStatusDot"
                            class="status-dot checking"
                        ></span>

                        <span id="canvaStatusText">
                            Memeriksa Canva...
                        </span>
                    </div>

                    <div class="navbar-avatar">
                        G
                    </div>
                </div>
            </header>

            <main class="page-content">
                @yield('content')
            </main>

            <footer class="app-footer">
                <span>
                    © {{ date('Y') }} Gold Price Sync
                </span>

                <span>
                    Google Sheets
                    <b>→</b>
                    Laravel
                    <b>→</b>
                    Canva
                </span>
            </footer>
        </div>
    </div>
</body>
</html>

