@extends('layouts.app')

@section('title', $title . ' — Gold Price Sync')

@section('page-name', $title)

@section('content')

<div class="page-header">

    <div>

        <span class="eyebrow">
            GOOGLE SHEETS DATA
        </span>

        <h1>
            {{ $title }}
        </h1>

        <p>
            {{ $subtitle }}
        </p>

    </div>


    <div class="page-header-actions">

        <span class="live-indicator">

            <span></span>

            LIVE DATA

        </span>

    </div>

</div>


<div class="panel">

    <div class="panel-header">

        <div>

            <span class="section-label">
                SOURCE
            </span>

            <h2>
                {{ $title }}
            </h2>

            <p>
                {{ $sheetName }}
            </p>

        </div>


        <div class="data-count">

            {{ count($prices) }} data

        </div>

    </div>


    @if ($error)

        <div class="alert alert-error">

            <div class="alert-icon">
                !
            </div>

            <div class="alert-content">

                <strong>
                    Google Sheets Error
                </strong>

                <span>
                    {{ $error }}
                </span>

            </div>

        </div>

    @elseif (empty($prices))

        <div class="empty-state">

            <div class="empty-icon">
                #
            </div>

            <strong>
                Belum ada data
            </strong>

            <span>
                Tidak ada data yang berhasil dibaca
                dari Google Sheets.
            </span>

        </div>

    @else

        <div class="table-wrapper">

            <table>

                @if ($type === 'logam_mulia')

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Gramasi
                            </th>

                            <th>
                                Harga
                            </th>

                            <th>
                                Buyback
                            </th>

                            <th>
                                Tanggal
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach ($prices as $index => $price)

                            <tr>

                                <td class="number-cell">
                                    {{ $index + 1 }}
                                </td>

                                <td>

                                    <span class="karat-badge">
                                        {{ $price['gramasi'] ?? '-' }}
                                    </span>

                                </td>

                                <td class="price-cell">
                                    {{ $price['harga'] ?? '-' }}
                                </td>

                                <td class="price-cell">
                                    {{ $price['buyback'] ?? '-' }}
                                </td>

                                <td class="date-cell">
                                    {{ $price['tanggal'] ?? '-' }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                @else

                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Karat
                            </th>

                            <th>
                                Harga / gr
                            </th>

                            <th>
                                Tanggal
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach ($prices as $index => $price)

                            <tr>

                                <td class="number-cell">
                                    {{ $index + 1 }}
                                </td>

                                <td>

                                    <span class="karat-badge">
                                        {{ $price['karat'] ?? '-' }}
                                    </span>

                                </td>

                                <td class="price-cell">
                                    {{ $price['harga/gr'] ?? '-' }}
                                </td>

                                <td class="date-cell">
                                    {{ $price['tanggal'] ?? '-' }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                @endif

            </table>

        </div>

    @endif

</div>

@endsection