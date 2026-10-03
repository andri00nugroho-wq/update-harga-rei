
@extends('layouts.app')

@section('title', 'Dashboard — Gold Price REI')

@section('page-name', 'Dashboard')

@section('content')

<div class="dashboard-page">

    <div class="page-header">
        <div>
            <span class="eyebrow">RAJA EMAS INDONESIA</span>

            <h1>Dashboard</h1>

            <p>
                Monitoring harga emas dan sinkronisasi Canva.
            </p>
        </div>

        <div class="header-status">
            <span class="status-dot connected"></span>
            <span>System Online</span>
        </div>
    </div>

    @if (session('canva_success'))
        <div class="alert alert-success">
            <div class="alert-icon">✓</div>

            <div class="alert-content">
                <strong>Sinkronisasi Berhasil</strong>

                <span>
                    {{ session('canva_success') }}
                </span>
            </div>
        </div>
    @endif

    @if (session('canva_error'))
        <div class="alert alert-error">
            <div class="alert-icon">!</div>

            <div class="alert-content">
                <strong>Sinkronisasi Gagal</strong>

                <span>
                    {{ session('canva_error') }}
                </span>
            </div>
        </div>
    @endif

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-icon">G</div>

            <div>
                <span>DATA SOURCE</span>
                <strong>Google Sheets</strong>
                <small>4 Sheet</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">#</div>

            <div>
                <span>TOTAL DATA</span>

                <strong>
                    {{ $totalRows ?? 0 }}
                </strong>

                <small>Data harga</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">C</div>

            <div>
                <span>TARGET</span>
                <strong>Canva API</strong>
                <small>Autofill</small>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">✓</div>

            <div>
                <span>DESIGN</span>

                <strong class="stat-design-id">
                    {{ config('services.canva.design_id') ?: '-' }}
                </strong>

                <small>Template Canva</small>
            </div>
        </div>

    </div>

    <div class="dashboard-grid">

        <main>

            <section class="card" id="automation">

                <div class="card-header">
                    <div>
                        <span class="section-label">
                            GOOGLE SHEETS
                        </span>

                        <h2>Data Harga</h2>

                        <p>
                            Data harga berdasarkan wilayah dan produk.
                        </p>
                    </div>
                </div>

                <div class="card-body">

                    <div class="source-grid">

                        <a
                            href="{{ route('prices.jawa') }}"
                            class="source-card"
                        >
                            <div class="source-card-icon">
                                ID
                            </div>

                            <div class="source-card-info">
                                <strong>Jawa / Nasional</strong>

                                <span>
                                    {{ count($sheets['jawa']['prices'] ?? []) }}
                                    data harga
                                </span>
                            </div>

                            <span class="source-arrow">
                                →
                            </span>
                        </a>

                        <a
                            href="{{ route('prices.kalimantan') }}"
                            class="source-card"
                        >
                            <div class="source-card-icon">
                                ID
                            </div>

                            <div class="source-card-info">
                                <strong>
                                    Kalimantan / Sulawesi
                                </strong>

                                <span>
                                    {{ count($sheets['kalimantan']['prices'] ?? []) }}
                                    data harga
                                </span>
                            </div>

                            <span class="source-arrow">
                                →
                            </span>
                        </a>

                        <a
                            href="{{ route('prices.sumatera') }}"
                            class="source-card"
                        >
                            <div class="source-card-icon">
                                ID
                            </div>

                            <div class="source-card-info">
                                <strong>
                                    Sumatera / Bali / Lombok
                                </strong>

                                <span>
                                    {{ count($sheets['sumatera']['prices'] ?? []) }}
                                    data harga
                                </span>
                            </div>

                            <span class="source-arrow">
                                →
                            </span>
                        </a>

                        <a
                            href="{{ route('prices.logam-mulia') }}"
                            class="source-card"
                        >
                            <div class="source-card-icon">
                                LM
                            </div>

                            <div class="source-card-info">
                                <strong>
                                    Logam Mulia
                                </strong>

                                <span>
                                    {{ count($sheets['logam_mulia']['prices'] ?? []) }}
                                    data harga
                                </span>
                            </div>

                            <span class="source-arrow">
                                →
                            </span>
                        </a>

                    </div>

                </div>

            </section>

            <section class="card">

                <div class="card-header">
                    <div>
                        <span class="section-label">
                            AUTOMATION
                        </span>

                        <h2>Alur Sinkronisasi</h2>

                        <p>
                            Proses perpindahan data dari Sheets ke Canva.
                        </p>
                    </div>
                </div>

                <div class="card-body">

                    <div class="process-flow">

                        <div class="process-step">
                            <div class="process-number">
                                01
                            </div>

                            <div>
                                <strong>Google Sheets</strong>
                                <span>Data harga terbaru</span>
                            </div>
                        </div>

                        <div class="process-line"></div>

                        <div class="process-step">
                            <div class="process-number">
                                02
                            </div>

                            <div>
                                <strong>Laravel</strong>
                                <span>Read &amp; mapping data</span>
                            </div>
                        </div>

                        <div class="process-line"></div>

                        <div class="process-step">
                            <div class="process-number">
                                03
                            </div>

                            <div>
                                <strong>Canva API</strong>
                                <span>Autofill template</span>
                            </div>
                        </div>

                        <div class="process-line"></div>

                        <div class="process-step">
                            <div class="process-number">
                                04
                            </div>

                            <div>
                                <strong>Result</strong>
                                <span>Hasil desain Canva</span>
                            </div>
                        </div>

                    </div>

                </div>

            </section>

        </main>

        <aside class="sidebar">

            <section class="card">

                <div class="card-header">
                    <div>
                        <span class="section-label">
                            CANVA
                        </span>

                        <h2>Connection</h2>

                        <p>
                            Status koneksi Canva.
                        </p>
                    </div>
                </div>

                <div class="card-body">

                    <div class="connection-box">

                        <div class="connection-box-icon">
                            C
                        </div>

                        <div>
                            <strong>Canva API</strong>

                            <p>
                                Gunakan koneksi Canva untuk menjalankan
                                proses autofill.
                            </p>
                        </div>

                    </div>

                    <a
                        href="{{ route('canva.connect') }}"
                        class="button button-secondary"
                    >
                        Hubungkan Canva
                    </a>

                </div>

            </section>

            <section class="card">

                <div class="card-header">
                    <div>
                        <span class="section-label">
                            ACTION
                        </span>

                        <h2>Update Harga</h2>

                        <p>
                            Sinkronkan harga terbaru ke Canva.
                        </p>
                    </div>
                </div>

                <div class="card-body">

                    <div class="info-list">

                        <div class="info-row">
                            <span>Total data</span>

                            <strong>
                                {{ $totalRows ?? 0 }}
                            </strong>
                        </div>

                        <div class="info-row">
                            <span>Target design</span>

                            <strong>
                                {{ config('services.canva.design_id') ?: '-' }}
                            </strong>
                        </div>

                        <div class="info-row">
                            <span>Source</span>

                            <strong>
                                Google Sheets
                            </strong>
                        </div>

                    </div>

                    <form
                        id="syncForm"
                        method="POST"
                        action="{{ route('sync') }}"
                    >
                        @csrf

                        <button
                            id="syncButton"
                            type="submit"
                            class="button button-primary"
                            @disabled(($totalRows ?? 0) == 0)
                        >
                            <span class="button-icon">↗</span>
                            UPDATE HARGA KE CANVA
                        </button>
                    </form>

                </div>

            </section>

            @if (isset($latestSync))

                <section class="card status-card">

                    <div class="card-header">
                        <div>
                            <span class="section-label">
                                LAST SYNC
                            </span>

                            <h2>
                                Sinkronisasi Terakhir
                            </h2>
                        </div>
                    </div>

                    <div class="card-body">

                        <div class="status-list">

                            <div class="status-row">
                                <span>Status</span>

                                <strong class="status-value">
                                    {{ strtoupper($latestSync->status ?? '-') }}
                                </strong>
                            </div>

                            <div class="status-row">
                                <span>Data</span>

                                <strong class="status-value">
                                    {{ $latestSync->total_data ?? 0 }}
                                </strong>
                            </div>

                            <div class="status-row">
                                <span>Job ID</span>

                                <strong class="status-value">
                                    {{ $latestSync->job_id ?? '-' }}
                                </strong>
                            </div>

                            <div class="status-row">
                                <span>Design ID</span>

                                <strong class="status-value">
                                    {{ $latestSync->design_id ?? '-' }}
                                </strong>
                            </div>

                        </div>

                        @if (!empty($latestSync->edit_url))

                            <div class="result-links">

                                <a
                                    href="{{ $latestSync->edit_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="button button-primary"
                                >
                                    Buka Canva ↗
                                </a>

                            </div>

                        @endif

                    </div>

                </section>

            @endif

        </aside>

    </div>

</div>

@endsection

