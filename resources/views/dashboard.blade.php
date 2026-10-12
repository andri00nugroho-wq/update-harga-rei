@extends('layouts.app')



@section('title', 'Dashboard — Gold Price REI')

@section('page-name', 'Dashboard')



@section('content')



@php



    $canvaEditUrl = session('canva_edit_url')

        ?: ($latestSync->edit_url ?? null);



    $canvaDesignId = $latestSync->design_id

        ?? config('services.canva.design_id')

        ?? '-';

@endphp



<div class="dashboard-page">



    {{-- HEADER --}}

    <div class="page-header">

        <div>

            <span class="eyebrow">RAJA EMAS INDONESIA</span>

            <h1>Dashboard</h1>

            <p>Monitoring harga emas dan sinkronisasi Canva.</p>

        </div>



        <div class="header-status">

            <span class="status-dot connected"></span>

            <span>System Online</span>

        </div>

    </div>



    {{-- NOTIFIKASI SUKSES --}}

    @if (session('canva_success'))

        <div class="alert alert-success">

            <div class="alert-icon">✓</div>

            <div class="alert-content">

                <strong>Sinkronisasi Berhasil</strong>

                <span>{{ session('canva_success') }}</span>

            </div>

        </div>

    @endif



    {{-- NOTIFIKASI GAGAL --}}

    @if (session('canva_error'))

        <div class="alert alert-error">

            <div class="alert-icon">!</div>

            <div class="alert-content">

                <strong>Sinkronisasi Gagal</strong>

                <span>{{ session('canva_error') }}</span>

            </div>

        </div>

    @endif



    {{-- HASIL DESAIN CANVA TERBARU --}}

    @if ($canvaEditUrl)

        <section class="card" style="margin-bottom: 24px;">

            <div class="card-body">

                <div style="

                    display: flex;

                    align-items: center;

                    gap: 16px;

                    flex-wrap: wrap;

                ">

                    <div style="

                        width: 48px;

                        height: 48px;

                        display: flex;

                        align-items: center;

                        justify-content: center;

                        border-radius: 12px;

                        background: #f3e8ff;

                        color: #7e22ce;

                        font-size: 24px;

                        flex-shrink: 0;

                    ">

                        C

                    </div>



                    <div style="flex: 1; min-width: 200px;">

                        <span class="section-label">

                            CANVA AUTOFILL

                        </span>



                        <h2 style="margin: 5px 0;">

                            Desain Canva Terbaru

                        </h2>



                        <p style="margin: 0;">

                            Desain hasil sinkronisasi harga berhasil dibuat.

                            Klik tombol untuk membuka desain.

                        </p>



                        @if (!empty($latestSync->design_id))

                            <small style="

                                display: block;

                                margin-top: 8px;

                                color: #64748b;

                                overflow-wrap: anywhere;

                            ">

                                Design ID:

                                {{ $latestSync->design_id }}

                            </small>

                        @endif

                    </div>



                    <a

                        href="{{ $canvaEditUrl }}"

                        target="_blank"

                        rel="noopener noreferrer"

                        class="button button-primary"

                    >

                        Buka Desain Canva ↗

                    </a>

                </div>

            </div>

        </section>

    @endif



    {{-- KARTU STATISTIK --}}

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

                <strong>{{ $totalRows ?? 0 }}</strong>

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

                    {{ $canvaDesignId }}

                </strong>

                <small>Desain/template Canva</small>

            </div>

        </div>



    </div>



    {{-- KONTEN DASHBOARD --}}

    <div class="dashboard-grid">



        <main>



            {{-- DATA HARGA --}}

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

                            <div class="source-card-icon">ID</div>



                            <div class="source-card-info">

                                <strong>Jawa / Nasional</strong>

                                <span>

                                    {{ count($sheets['jawa']['prices'] ?? []) }}

                                    data harga

                                </span>

                            </div>



                            <span class="source-arrow">→</span>

                        </a>



                        <a

                            href="{{ route('prices.kalimantan') }}"

                            class="source-card"

                        >

                            <div class="source-card-icon">ID</div>



                            <div class="source-card-info">

                                <strong>Kalimantan / Sulawesi</strong>

                                <span>

                                    {{ count($sheets['kalimantan']['prices'] ?? []) }}

                                    data harga

                                </span>

                            </div>



                            <span class="source-arrow">→</span>

                        </a>



                        <a

                            href="{{ route('prices.sumatera') }}"

                            class="source-card"

                        >

                            <div class="source-card-icon">ID</div>



                            <div class="source-card-info">

                                <strong>Sumatera / Bali / Lombok</strong>

                                <span>

                                    {{ count($sheets['sumatera']['prices'] ?? []) }}

                                    data harga

                                </span>

                            </div>



                            <span class="source-arrow">→</span>

                        </a>



                        <a

                            href="{{ route('prices.logam-mulia') }}"

                            class="source-card"

                        >

                            <div class="source-card-icon">LM</div>



                            <div class="source-card-info">

                                <strong>Logam Mulia</strong>

                                <span>

                                    {{ count($sheets['logam_mulia']['prices'] ?? []) }}

                                    data harga

                                </span>

                            </div>



                            <span class="source-arrow">→</span>

                        </a>



                    </div>



                </div>

            </section>



            {{-- ALUR SINKRONISASI --}}

            <section class="card">



                <div class="card-header">

                    <div>

                        <span class="section-label">AUTOMATION</span>

                        <h2>Alur Sinkronisasi</h2>

                        <p>

                            Proses perpindahan data dari Sheets ke Canva.

                        </p>

                    </div>

                </div>



                <div class="card-body">



                    <div class="process-flow">



                        <div class="process-step">

                            <div class="process-number">01</div>

                            <div>

                                <strong>Google Sheets</strong>

                                <span>Data harga terbaru</span>

                            </div>

                        </div>



                        <div class="process-line"></div>



                        <div class="process-step">

                            <div class="process-number">02</div>

                            <div>

                                <strong>Laravel</strong>

                                <span>Read &amp; mapping data</span>

                            </div>

                        </div>



                        <div class="process-line"></div>



                        <div class="process-step">

                            <div class="process-number">03</div>

                            <div>

                                <strong>Canva API</strong>

                                <span>Autofill template</span>

                            </div>

                        </div>



                        <div class="process-line"></div>



                        <div class="process-step">

                            <div class="process-number">04</div>

                            <div>

                                <strong>Result</strong>

                                <span>Desain Canva baru</span>

                            </div>

                        </div>



                    </div>



                </div>

            </section>



        </main>



        {{-- SIDEBAR --}}

        <aside class="sidebar">



            {{-- KONEKSI CANVA --}}

            <section class="card">



                <div class="card-header">

                    <div>

                        <span class="section-label">CANVA</span>

                        <h2>Connection</h2>

                        <p>Status dan koneksi Canva.</p>

                    </div>

                </div>



                <div class="card-body">



                    <div class="connection-box">

                        <div class="connection-box-icon">C</div>



                        <div>

                            <strong>Canva API</strong>

                            <p>

                                Hubungkan akun Canva untuk menjalankan

                                proses Autofill.

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



            {{-- UPDATE HARGA --}}

            <section class="card">



                <div class="card-header">

                    <div>

                        <span class="section-label">ACTION</span>

                        <h2>Update Harga</h2>

                        <p>Sinkronkan harga terbaru ke Canva.</p>

                    </div>

                </div>



                <div class="card-body">



                    <div class="info-list">



                        <div class="info-row">

                            <span>Total data</span>

                            <strong>{{ $totalRows ?? 0 }}</strong>

                        </div>



                        <div class="info-row">

                            <span>Target design</span>

                            <strong>

                                {{ config('services.canva.design_id') ?: '-' }}

                            </strong>

                        </div>



                        <div class="info-row">

                            <span>Source</span>

                            <strong>Google Sheets</strong>

                        </div>



                    </div>



                    <form

                        id="syncForm"

                        method="POST"

                        action="{{ route('sync') }}"

                    >

                        @csrf



                        
                        {{-- NOMOR CABANG: ANGKA SAJA --}}
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label for="nomor_cabang" style="display:block; margin-bottom:8px; font-weight:600;">
                                Cabang ke-berapa?
                            </label>
                            <input
                                type="text"
                                id="nomor_cabang"
                                name="nomor_cabang"
                                inputmode="numeric"
                                pattern="[0-9]+"
                                maxlength="5"
                                placeholder="Contoh: 12"
                                value="{{ old('nomor_cabang') }}"
                                autocomplete="off"
                                required
                                style="width:100%; padding:12px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box;"
                            >
                            <small style="display:block; margin-top:6px; color:#64748b;">
                                Masukkan nomor cabang menggunakan angka saja.
                            </small>
                        </div>

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



                    <p style="

                        margin-top: 10px;

                        font-size: 12px;

                        color: #64748b;

                    ">

                        Canva akan membuat desain hasil Autofill baru.

                    </p>



                </div>

            </section>



            {{-- STATUS SINKRONISASI TERAKHIR --}}

            @if (isset($latestSync) && $latestSync)



                <section class="card status-card">



                    <div class="card-header">

                        <div>

                            <span class="section-label">LAST SYNC</span>

                            <h2>Sinkronisasi Terakhir</h2>

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



                        {{-- LINK DESAIN CANVA --}}

                        @if (!empty($latestSync->edit_url))



                            <div class="result-links" style="margin-top: 18px;">

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



{{-- SUBMIT SINKRONISASI TANPA FULL PAGE RELOAD --}}

<script>

(function () {

    // Mencegah listener terpasang berkali-kali jika view diganti secara dinamis.

    if (window.__reiSyncHandlerInstalled) return;

    window.__reiSyncHandlerInstalled = true;



    // Input nomor cabang hanya menerima angka, termasuk setelah dashboard diganti.
    document.addEventListener('input', function (event) {
        if (event.target && event.target.id === 'nomor_cabang') {
            event.target.value = event.target.value.replace(/[^0-9]/g, '').slice(0, 5);
        }
    });

    document.addEventListener('submit', async function (event) {

        const form = event.target;



        if (!(form instanceof HTMLFormElement) || form.id !== 'syncForm') {

            return;

        }



        event.preventDefault();



        const button = form.querySelector('#syncButton');

        if (!button || button.disabled) return;



        const originalHtml = button.innerHTML;

        button.disabled = true;

        button.setAttribute('aria-busy', 'true');

        button.innerHTML = '<span class="button-icon">⏳</span> MEMPROSES AUTOFILL...';



        let status = document.getElementById('sync-request-status');

        if (!status) {

            status = document.createElement('p');

            status.id = 'sync-request-status';

            status.className = 'sync-request-status';

            status.setAttribute('role', 'status');

            status.setAttribute('aria-live', 'polite');

            form.insertAdjacentElement('afterend', status);

        }



        status.textContent = 'Mengirim permintaan sinkronisasi. Jangan tutup halaman ini.';



        try {

            const response = await fetch(form.action, {

                method: (form.method || 'POST').toUpperCase(),

                body: new FormData(form),

                credentials: 'same-origin',

                headers: {

                    'X-Requested-With': 'XMLHttpRequest',

                    'Accept': 'text/html, application/json'

                },

                redirect: 'follow'

            });



            if (!response.ok) {

                throw new Error('Permintaan gagal (HTTP ' + response.status + ').');

            }



            const contentType = response.headers.get('content-type') || '';



            // Jika endpoint sudah mendukung JSON, tampilkan hasilnya tanpa reload.

            if (contentType.includes('application/json')) {

                const result = await response.json();



                if (result.success === false) {

                    throw new Error(result.message || 'Sinkronisasi gagal.');

                }



                status.textContent = result.message || 'Permintaan sinkronisasi selesai.';

                if (result.edit_url) {

                    status.append(' ');

                    const link = document.createElement('a');

                    link.href = result.edit_url;

                    link.target = '_blank';

                    link.rel = 'noopener noreferrer';

                    link.textContent = 'Buka desain Canva';

                    status.append(link);

                }



                button.disabled = false;

                button.removeAttribute('aria-busy');

                button.innerHTML = originalHtml;

                return;

            }



            // Route Laravel yang melakukan redirect akan menghasilkan HTML akhir.

            // Ganti konten dashboard saja, bukan memuat ulang seluruh dokumen.

            const html = await response.text();

            const parsed = new DOMParser().parseFromString(html, 'text/html');

            const newDashboard = parsed.querySelector('.dashboard-page');

            const currentDashboard = document.querySelector('.dashboard-page');



            if (!newDashboard || !currentDashboard) {

                // Jika respons bukan halaman dashboard yang dikenali, gunakan fallback aman.

                window.location.assign(response.url || form.action);

                return;

            }



            currentDashboard.replaceWith(newDashboard);



            // Berikan pesan singkat jika halaman hasil tidak menampilkan flash message.

            const updatedStatus = document.getElementById('sync-request-status');

            if (updatedStatus) {

                updatedStatus.textContent = 'Respons sinkronisasi diterima.';

            }

        } catch (error) {

            console.error('Sinkronisasi Canva gagal:', error);

            status.textContent = error.message || 'Terjadi kesalahan. Silakan coba lagi.';

            button.disabled = false;

            button.removeAttribute('aria-busy');

            button.innerHTML = originalHtml;

        }

    });

})();

</script>



@endsection
