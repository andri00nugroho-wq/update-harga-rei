
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Gold Price Sync')
    </title>

    @vite([
        'resources/css/dashboard.css',
        'resources/js/dashboard.js',
    ])
</head>

<body>

<div class="app-shell">

    {{-- =========================================================
         MOBILE SIDEBAR OVERLAY
    ========================================================== --}}
    <div
        id="sidebarOverlay"
        class="sidebar-overlay"
    ></div>


    {{-- =========================================================
         SIDEBAR
    ========================================================== --}}
    <aside
        id="sidebar"
        class="sidebar-navigation"
    >

        {{-- SIDEBAR HEADER --}}
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


            {{-- SYSTEM STATUS --}}
            <div class="sidebar-system">

                <span class="system-pulse"></span>

                <span>LIVE SYSTEM</span>

            </div>

        </div>


        {{-- =====================================================
             SIDEBAR MENU
        ====================================================== --}}
        <nav class="sidebar-menu">

            {{-- MAIN --}}
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


            {{-- DATA HARGA --}}
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


            {{-- AUTOMATION --}}
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


        {{-- =====================================================
             SIDEBAR FOOTER
        ====================================================== --}}
        <div class="sidebar-footer">

            <div class="sidebar-footer-card">

                <div class="sidebar-footer-icon">
                    C
                </div>


                <div class="sidebar-footer-info">

                    <div class="sidebar-footer-heading">

                        <span>
                            Canva
                        </span>

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


    {{-- =========================================================
         MAIN APPLICATION AREA
    ========================================================== --}}
    <div class="app-main">


        {{-- =====================================================
             TOP NAVBAR
        ====================================================== --}}
        <header class="top-navbar">

            <div class="navbar-left">

                {{-- MOBILE MENU --}}
                <button
                    type="button"
                    id="sidebarToggle"
                    class="sidebar-toggle"
                    aria-label="Toggle sidebar"
                >
                    ☰
                </button>


                {{-- BREADCRUMB --}}
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


            {{-- NAVBAR RIGHT --}}
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


        {{-- =====================================================
             PAGE CONTENT
        ====================================================== --}}
        <main class="page-content">

            @yield('content')

        </main>


        {{-- =====================================================
             FOOTER
        ====================================================== --}}
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

