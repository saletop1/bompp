<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAP Material Master Converter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        /* CSS Utama untuk Layout */
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --glass-bg: rgba(75, 74, 74, 0.33);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
            --text-light: #ffffff;
            --text-muted: #d7d7d7;
        }

        body {
            background-image: url("{{ asset('images/ainun.jpg') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding-top: 1rem;
            padding-bottom: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* [BARU] Animasi Floating Particles untuk Converter */
        .particles-container-converter {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .particle-converter {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float-converter 15s infinite ease-in-out;
        }

        @keyframes float-converter {
            0%, 100% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            position: relative; /* [BARU] Pastikan card di atas particles */
            z-index: 1; /* [BARU] Pastikan card di atas particles */
        }

        .converter-container {
            max-width: 800px;
            margin: 0 auto 1rem;
            position: relative; /* [BARU] Pastikan container di atas particles */
            z-index: 1; /* [BARU] Pastikan container di atas particles */
        }

        #drop-zone {
            border: 2px dashed rgba(255, 255, 255, 0.6);
            border-radius: 0.5rem;
            color: #343a40;
            transition: all 0.1s ease-in-out;
            background-color: rgba(255, 255, 255, 0.2);
            cursor: pointer;
            padding: 2rem 1rem;
        }

        #drop-zone.dragover {
            border-color: var(--primary-color);
            background-color: rgba(21, 62, 124, 0.21);
            transform: scale(1.02);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-light);
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.1s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.8);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.1s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .btn .spinner-border {
            width: 1rem;
            height: 1rem;
        }

        .page-title-container {
            text-align: center;
            flex-grow: 1;
        }

        .page-title-container .main-title {
            color: var(--text-light);
            text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
            margin-bottom: 0.25rem;
            font-weight: 700;
        }

        .page-title-container .subtitle {
            color: var(--text-muted);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .sap-logo-header {
            height: 80px;
            width: auto;
            margin-left: 20px;
        }

        .download-area-header {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .download-area-header dotlottie-player {
            flex-shrink: 0;
        }

        .download-area-title {
            color: #d1d5db;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
        }

        .download-area-text {
            color: #d1d5db;
            font-size: 1.1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .alert {
            border-radius: 8px;
            padding: 1rem 1.5rem;
        }

        .alert.alert-success-frosted {
            background: rgba(25, 135, 126, 0.4);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .alert.alert-danger {
            background: rgba(220, 53, 69, 0.4);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-light);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .upload-details {
            max-height: 200px;
            overflow-y: auto;
            text-align: left;
            font-size: 0.85rem;
            background-color: rgba(0,0,0,0.1);
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }

        .upload-details ul {
            margin-bottom: 0;
            padding-left: 1rem;
        }

        .upload-details ul li {
            color: #e2e0e0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            margin-bottom: 0.5rem;
        }

        .nav-pills .nav-link {
            background-color: rgba(255, 255, 255, 0);
            color: #f0f0f0;
            margin: 0 5px;
            transition: all 0.1s ease;
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
            background-color: #ffffff1c;
            color: #a6ff00;
            font-weight: bold;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.76);
        }

        .nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.4);
            color: #ffffff;
            border-color: rgba(255,255,255,0.5);
        }

        .modal-content.frosted-glass {
            background: rgba(30, 30, 30, 0.4);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3);
            border-radius: 0.75rem;
            color: #ffffff;
        }

        .input-group-underline {
            position: relative;
        }

        .input-underline {
            background-color: transparent !important;
            border: none !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.4) !important;
            border-radius: 0 !important;
            padding-left: 35px !important;
            color: #ffffff !important;
            transition: border-color 0.1s ease;
        }

        .input-underline::placeholder {
            color: rgba(7, 226, 255, 0.8);
            opacity: 1;
        }

        .input-underline:focus {
            box-shadow: none !important;
            border-bottom-color: #05e6ff !important;
        }

        .input-group-underline .bi {
            position: absolute;
            left: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
            z-index: 5;
        }

        .frosted-glass .modal-header, .frosted-glass .modal-footer {
            border-bottom: none;
            border-top: none;
        }

        .frosted-glass .modal-title {
            font-weight: 300;
        }

        #confirmationModal .modal-body {
            max-height: 40vh;
            overflow-y: auto;
        }

        #process-another-btn {
            background-color: var(--success-color) !important;
            border-color: var(--success-color) !important;
            color: white !important;
        }

        #process-another-btn:hover {
            background-color: #157347 !important;
            border-color: #146c43 !important;
        }

        #progress-text {
            color: #e9ecef;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7);
            font-weight: 500;
            margin-top: -20px;
            font-size: 1rem;
            line-height: 1.5;
        }

        /* [PERBAIKAN] Progress Bar untuk Loading State */
        .progress-container-enhanced {
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }

        .progress-bar-enhanced {
            height: 8px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.2);
            overflow: hidden;
            position: relative;
        }

        .progress-bar-enhanced .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #0b5ed7);
            border-radius: 4px;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-bar-enhanced .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            background-size: 1rem 1rem;
            animation: progress-animation 1s linear infinite;
        }

        @keyframes progress-animation {
            0% {
                background-position: 0 0;
            }
            100% {
                background-position: 1rem 0;
            }
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .progress-step {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            position: relative;
        }

        .progress-step.active {
            color: var(--text-light);
            font-weight: 600;
        }

        .progress-step.completed::before {
            content: '✓';
            display: block;
            color: var(--success-color);
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        /* [PERBAIKAN] Focus states untuk keyboard navigation */
        .btn:focus-visible,
        .form-control:focus-visible,
        .form-select:focus-visible,
        .nav-link:focus-visible {
            outline: 3px solid rgba(13, 110, 253, 0.5);
            outline-offset: 2px;
        }

        /* [PERBAIKAN] Skip to content link untuk accessibility */
        .skip-to-content {
            position: absolute;
            top: -40px;
            left: 0;
            background: var(--primary-color);
            color: white;
            padding: 8px;
            text-decoration: none;
            z-index: 100;
        }

        .skip-to-content:focus {
            top: 0;
        }

        /* [PERBAIKAN] CSS untuk Timer di Pojok Kanan Atas */
        #lock-countdown-timer {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 251, 0, 1);
            border: 1px solid #000000ff;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: sans-serif;
            font-size: 0.9rem;
            color: #333;
            z-index: 1000;
            display: none; /* Sembunyi secara default */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        /* [BARU] CSS untuk Ikon Logout */
        .logout-icon {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 1.75rem; /* Ukuran ikon */
            color: var(--danger-color); /* Merah menyala */
            text-shadow: 0 1px 3px rgba(0,0,0,0.5);
            transition: color 0.3s ease, transform 0.3s ease;
            z-index: 2000;
            text-decoration: none;
        }
        .logout-icon:hover {
            color: var(--text-light); /* Putih saat hover */
            transform: scale(1.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .sap-logo-header {
                height: 60px;
                margin-left: 10px;
            }

            .page-title-container .main-title {
                font-size: 1.5rem;
            }

            .page-title-container .subtitle {
                font-size: 0.8rem;
            }

            .card {
                padding: 1.5rem !important;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .nav-pills .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }

            /* [PERBAIKAN] Nav pills menjadi lebih responsif */
            .nav-pills {
                flex-direction: column;
                gap: 0.5rem;
            }

            .nav-pills .nav-item {
                width: 100%;
            }

            .nav-pills .nav-link {
                width: 100%;
                margin: 0;
            }

            .download-area-header {
                flex-direction: column;
                text-align: center;
            }

            #action-buttons {
                flex-direction: column;
                gap: 0.75rem !important;
            }

            #action-buttons .btn {
                width: 100%;
            }

            /* [PERBAIKAN] Drop zone di mobile */
            #drop-zone {
                padding: 1.5rem 0.5rem;
            }

            #drop-zone dotlottie-player {
                width: 100px !important;
                height: 100px !important;
            }

            /* [PERBAIKAN] Input group di email notification */
            #email-notification-area .input-group {
                max-width: 100% !important;
            }
        }

        @media (max-width: 576px) {
            .converter-container {
                padding-left: 10px;
                padding-right: 10px;
            }

            .d-flex.align-items-center.justify-content-center {
                flex-direction: column;
            }

            .sap-logo-header {
                height: 50px;
                margin-left: 0;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .page-title-container .main-title {
                font-size: 1.25rem;
            }

            .page-title-container .subtitle {
                font-size: 0.75rem;
            }

            .form-label {
                font-size: 0.9rem;
            }

            .input-group > .form-control {
                font-size: 0.9rem;
            }

            .modal-dialog {
                margin: 0.5rem;
            }

            /* [PERBAIKAN] Modal content di mobile */
            .modal-content {
                padding: 0.5rem;
            }

            /* [PERBAIKAN] Timer di mobile */
            #lock-countdown-timer {
                top: 5px;
                right: 5px;
                padding: 8px 12px;
                font-size: 0.8rem;
                max-width: calc(100% - 10px);
            }

            /* [PERBAIKAN] Drop zone text di mobile */
            #main-text {
                font-size: 0.9rem;
            }

            #file-info-text {
                font-size: 0.8rem;
            }

            /* [PERBAIKAN] Card padding di mobile */
            .card {
                padding: 1rem !important;
            }

            /* [PERBAIKAN] Download area text di mobile */
            .download-area-title {
                font-size: 1.1rem;
            }

            .download-area-text {
                font-size: 0.95rem;
            }
        }

        /* [PERBAIKAN] Responsif untuk layar sangat kecil */
        @media (max-width: 360px) {
            .page-title-container .main-title {
                font-size: 1.1rem;
            }

            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }

            .nav-pills .nav-link {
                padding: 0.4rem 0.5rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

    <!-- [BARU] Floating Particles Animation untuk Converter -->
    <div class="particles-container-converter" id="particles-converter"></div>

    @auth
    <!-- [PERUBAHAN] Form Logout (tersembunyi) -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>

    <!-- [PERUBAHAN] Ikon Logout di Kiri Atas -->
    <a href="#" class="logout-icon" title="Logout"
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
        <i class="bi bi-box-arrow-left"></i>
    </a>
    @endauth

    <!-- [PERBAIKAN] Elemen HTML untuk Timer -->
    <div id="lock-countdown-timer">
        Sistem terkunci, coba lagi dalam: <strong id="countdown-time">5:00</strong>
    </div>

    <div class="container converter-container" id="main-content">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-center mb-4">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Material Master Converter</h1>
                <p class="subtitle mb-0">Automate Your CAD-to-ERP Workflow: From Inventor to SAP Material Master</p>
            </div>
            <img src="{{ asset('images/saplogo.png') }}" alt="SAP Logo" class="sap-logo-header">
        </div>

        <div class="card p-4 p-lg-5">
            <div class="card-body">
                @if (!session('download_filename') && !session('processed_filename'))
                    {{-- NAVIGASI UTAMA --}}
                    <ul class="nav nav-pills nav-fill mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ request()->routeIs('converter.index') ? 'active' : '' }}"
                               href="{{ route('converter.index') }}" role="tab" aria-selected="{{ request()->routeIs('converter.index') ? 'true' : 'false' }}">
                                <i class="bi bi-box-seam me-2" aria-hidden="true"></i>Material Master
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}"
                               href="{{ route('bom.index') }}" role="tab" aria-selected="{{ request()->routeIs('bom.index') ? 'true' : 'false' }}">
                                <i class="bi bi-diagram-3 me-2" aria-hidden="true"></i>BOM Master
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ request()->routeIs('routing.index') ? 'active' : '' }}"
                               href="{{ route('routing.index') }}" role="tab" aria-selected="{{ request()->routeIs('routing.index') ? 'true' : 'false' }}">
                                <i class="bi bi-signpost-split me-2" aria-hidden="true"></i>Routing Master
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('shop_drawings.index') ? 'active' : '' }}" 
                            href="{{ route('shop_drawings.index') }}">
                                <i class="bi bi-file-earmark-pdf"></i> Shop Drawing
                            </a>
                        </li>
                    </ul>
                    <hr style="border-color: rgba(255,255,255,0.3);">
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('download_filename'))
                {{-- TAMPILAN AREA HASIL --}}
                <div id="download-area" role="region" aria-labelledby="result-title">
                    <div class="download-area-header">
                        <div class="ms-3">
                            <h4 class="download-area-title mb-0" id="result-title">Processing Complete!</h4>
                            <p class="download-area-text mt-0">{{ session('success') }}</p>
                        </div>
                    </div>

                    <div id="progress-container" class="text-center mt-4 d-none" role="region" aria-live="polite">
                        <div class="progress-container-enhanced">
                            <div class="progress-bar-enhanced" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" aria-label="Processing progress">
                                <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="progress-steps" id="progress-steps">
                                <div class="progress-step" data-step="1">Preparing</div>
                                <div class="progress-step" data-step="2">Staging</div>
                                <div class="progress-step" data-step="3">Uploading</div>
                                <div class="progress-step" data-step="4">Activating</div>
                            </div>
                        </div>
                        <dotlottie-player src="{{ asset('animations/Bird.lottie') }}" background="transparent" speed="1" style="width: 200px; height: 200px; margin: 0 auto;" loop autoplay aria-hidden="true"></dotlottie-player>
                        <p id="progress-text" class="text-center">Processing...</p>
                    </div>

                    <div id="upload-result" class="mt-3" role="alert" aria-live="assertive"></div>
                    <div id="inspection-plan-result" class="mt-3" role="alert" aria-live="assertive"></div>
                    <div id="email-result" class="mt-3" role="alert" aria-live="assertive"></div>

                    <div id="email-notification-area" class="mt-4 d-none">
                        <div class="input-group mb-3 mx-auto" style="max-width: 450px;">
                            <span class="input-group-text"><i class="bi bi-envelope-at" aria-hidden="true"></i></span>
                            <input type="email" id="email-recipient" class="form-control" placeholder="pisahkan dengan koma untuk banyak email..." aria-label="Recipient emails">
                        </div>
                    </div>

                    <div id="action-buttons" class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <button type="button" id="upload-sap-btn" class="btn btn-primary btn-lg px-4 gap-3" data-filename="{{ session('download_filename') }}" aria-label="Upload materials to SAP system">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-cloud-upload" aria-hidden="true"></i> Upload to SAP
                        </button>

                        <button type="button" id="send-email-btn" class="btn btn-danger btn-lg px-4 gap-3 d-none" aria-label="Send email notification">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-envelope-at-fill" aria-hidden="true"></i> Send Notification
                        </button>

                        <a href="{{ route('converter.download', ['filename' => session('download_filename')]) }}" id="download-only-btn" class="btn btn-outline-light btn-lg px-4" aria-label="Download processed file">
                            <i class="bi bi-download" aria-hidden="true"></i> Download Only
                        </a>
                        <a href="{{ route('converter.index') }}" id="process-another-btn" class="btn btn-secondary btn-lg px-4" aria-label="Process another file">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Process Another
                        </a>
                    </div>
                </div>
                @else
                {{-- FORM UPLOAD AWAL --}}
                <form action="{{ route('converter.upload') }}" method="POST" enctype="multipart/form-data" id="upload-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="material_type" class="form-label">Material Type</label>
                            <select name="material_type" id="material_type" class="form-select" required aria-required="true" aria-describedby="material_type_help">
                                <option value="" selected disabled>Pilih Material Type...</option>
                                <option value="FERT">FERT Finish Good</option>
                                <option value="HALB">HALB Semi Finish</option>
                                <option value="HALM">HALM Semi Finish MTL</option>
                                <option value="VERP">VERP Packaging</option>
                            </select>
                            <small id="material_type_help" class="form-text text-muted d-none">Pilih tipe material yang akan dibuat</small>
                        </div>

                        <div id="fert-options" class="row g-3 d-none">
                            <div class="col-md-8">
                                <label for="division" class="form-label">Division</label>
                                <select name="division" id="division" class="form-select">
                                    <option value="" selected disabled>Pilih Division...</option>
                                    <option value="00">00 Common Division</option>
                                    <option value="01">01 Case Goods</option>
                                    <option value="02">02 Bed</option>
                                    <option value="03">03 Dining Table</option>
                                    <option value="04">04 Sport Game</option>
                                    <option value="05">05 Chair</option>
                                    <option value="06">06 Building Component</option>
                                    <option value="07">07 Metal Furniture</option>
                                    <option value="08">08 Door</option>
                                    <option value="09">09 Parts (HW & Comp)</option>
                                    <option value="10">10 Metal Accesories</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="distribution_channel" class="form-label">Distribution Channel</label>
                                <select name="distribution_channel" id="distribution_channel" class="form-select">
                                    <option value="" selected disabled>Pilih Channel...</option>
                                    <option value="10">10 Export</option>
                                    <option value="20">20 Local</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label for="start_material_code" class="form-label">Starting Material Code</label>
                            <div class="input-group">
                                <input type="text" name="start_material_code" id="start_material_code" class="form-control" placeholder="Klik 'Generate' ->" required>
                                <button type="button" class="btn btn-dark" id="generate-btn" aria-label="Generate Material Code" aria-describedby="generate_help">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-stars" aria-hidden="true"></i> Generate
                                </button>
                            </div>
                            <small id="generate_help" class="form-text text-muted d-none">Tekan untuk generate kode material otomatis</small>
                        </div>
                        <div class="col-md-4">
                            <label for="plant" class="form-label">Plant</label>
                            <select name="plant" id="plant" class="form-select" required>
                                <option value="" selected disabled>Pilih Plant...</option>
                                <option value="3000">3000</option>
                                <option value="2000">2000</option>
                                <option value="1000">1000</option>
                                <option value="1001">1001</option>
                            </select>
                        </div>
                        <div class="col-12 mt-4">
                            <!-- [PERBAIKAN SELULER] Hapus 'accept' -->
                            <input type="file" name="file" id="file-input" required class="d-none" aria-label="Upload Excel file">
                            <div id="drop-zone" class="p-4 text-center" role="button" tabindex="0" aria-label="File upload area" aria-describedby="drop-zone-help">
                                <dotlottie-player src="{{ asset('animations/Greenish arrow down.lottie') }}" background="transparent" speed="1" style="width: 150px; height: 150px; margin: 0 auto;" loop autoplay aria-hidden="true"></dotlottie-player>
                                <p class="mb-0 mt-2 fw-bold" id="main-text">Drag & drop your Excel file here</p>
                                <p class="text-muted small" id="file-info-text">or click to browse</p>
                            </div>
                            <small id="drop-zone-help" class="form-text text-muted text-center mt-2 d-block">Format file: .xlsx atau .xls</small>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" id="submit-button" class="btn btn-primary btn-lg d-none" aria-label="Process and convert file">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="bi bi-gear-fill" aria-hidden="true"></i>
                                <span id="submit-button-text">Process Now</span>
                            </button>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
        <footer class="text-center text-white mt-4">
            <small style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">© PT. Kayu Mebel Indonesia, 2025</small>
        </footer>
    </div>

    {{-- Modal Login SAP --}}
    <div class="modal fade" id="sapLoginModal" tabindex="-1" aria-labelledby="sapLoginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content frosted-glass">
                <div class="modal-header">
                    <h5 class="modal-title" id="sapLoginModalLabel">SAP Authentication</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-center mb-4">Enter your credentials to continue.</p>
                    <div class="input-group-underline mb-4">
                        <i class="bi bi-person"></i>
                        <input type="text" class="form-control input-underline" id="sap-username" placeholder="Username" autocomplete="username" aria-label="SAP Username" aria-required="true">
                    </div>
                    <div class="input-group-underline mb-3">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" class="form-control input-underline" id="sap-password" placeholder="Password" autocomplete="current-password" aria-label="SAP Password" aria-required="true">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-action-btn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Upload --}}
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content frosted-glass">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Upload & Activate QM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmation-modal-body">
                    {{-- Konten diisi oleh JavaScript --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirm-activate-btn">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-shield-check"></i> Confirm & Activate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Variabel global untuk timer --}}
    <script>
        let countdownInterval;
    </script>

    <script>
        /**
         * [BARU] Floating Particles Animation untuk Converter
         */
        function createParticlesConverter() {
            const container = document.getElementById('particles-converter');
            if (!container) return;

            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle-converter';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        /**
         * [PERBAIKAN] Memulai timer countdown visual
         * @param {number} expiresAtTimestamp - Timestamp (dalam detik) kapan kunci berakhir
         */
        function startLockCountdown(expiresAtTimestamp) {
            // Hentikan timer lama jika ada
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }

            const timerElement = document.getElementById('lock-countdown-timer');
            const timeDisplay = document.getElementById('countdown-time');

            if (!timerElement || !timeDisplay) return;

            timerElement.style.display = 'block'; // Tampilkan timer

            const updateTimer = () => {
                const now = Math.floor(Date.now() / 1000); // Waktu saat ini dalam detik
                const remainingSeconds = expiresAtTimestamp - now;

                if (remainingSeconds <= 0) {
                    clearInterval(countdownInterval);
                    timerElement.style.display = 'none'; // Sembunyikan timer

                    // [PERBAIKAN] Aktifkan kembali tombol generate untuk semua user
                    const generateButton = document.getElementById('generate-btn');
                    if (generateButton) {
                        generateButton.disabled = false;
                        const btnIcon = generateButton.querySelector('.bi-stars');
                        const spinner = generateButton.querySelector('.spinner-border');
                        if(btnIcon) btnIcon.classList.remove('d-none');
                        if(spinner) spinner.classList.add('d-none');
                    }
                    return;
                }

                const minutes = Math.floor(remainingSeconds / 60);
                const seconds = remainingSeconds % 60;

                // Format waktu (MM:SS)
                timeDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            };

            updateTimer(); // Panggil sekali agar langsung update
            countdownInterval = setInterval(updateTimer, 1000); // Update setiap detik
        }

        document.addEventListener('DOMContentLoaded', function () {
            // [BARU] Inisialisasi particles untuk converter
            createParticlesConverter();

            // --- [PERBAIKAN] Keyboard Navigation ---

            // ESC key untuk close modal
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modal => {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    });
                }
            });

            // Enter key untuk submit form (tapi bukan di textarea)
            const formKeyboard = document.getElementById('upload-form');
            if (formKeyboard) {
                formKeyboard.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                        const submitButton = document.getElementById('submit-button');
                        if (submitButton && !submitButton.classList.contains('d-none') && !submitButton.disabled) {
                            e.preventDefault();
                            submitButton.click();
                        }
                    }
                });
            }

            // Enter key untuk generate button
            const generateBtnKeyboard = document.getElementById('generate-btn');
            const materialCodeInput = document.getElementById('start_material_code');
            if (materialCodeInput && generateBtnKeyboard) {
                materialCodeInput.addEventListener('keydown', function(e) {
                    // [PERBAIKAN] Pastikan hanya trigger jika bukan di dalam form submit
                    if (e.key === 'Enter' && !generateBtnKeyboard.disabled && e.target === materialCodeInput) {
                        e.preventDefault();
                        e.stopPropagation(); // Mencegah trigger form submit
                        generateBtnKeyboard.click();
                    }
                });
            }

            // Enter key di drop zone untuk trigger file input
            const dropZone = document.getElementById('drop-zone');
            if (dropZone) {
                dropZone.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const fileInput = document.getElementById('file-input');
                        if (fileInput) fileInput.click();
                    }
                });
            }

            // Enter key untuk confirm di modal
            const confirmLoginBtn = document.getElementById('confirm-action-btn');
            const confirmActivateBtn = document.getElementById('confirm-activate-btn');
            if (confirmLoginBtn) {
                document.getElementById('sap-password')?.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && !confirmLoginBtn.disabled) {
                        e.preventDefault();
                        confirmLoginBtn.click();
                    }
                });
            }

            // --- [AKHIR PERBAIKAN] Keyboard Navigation ---

            // --- [PERBAIKAN] Pengecekan Timer saat Halaman Dimuat ---
            @if(isset($lockExpiresAt) && $lockExpiresAt > now()->timestamp)
                const expiresAt = {{ $lockExpiresAt }};
                const generateButton = document.getElementById('generate-btn');

                // Langsung kunci tombol generate dan mulai timer
                if (generateButton) {
                    generateButton.disabled = true;
                    const btnIcon = generateButton.querySelector('.bi-stars');
                    if(btnIcon) btnIcon.classList.add('d-none');
                }

                // Tampilkan timer (tanpa alert agar tidak mengganggu)
                startLockCountdown(expiresAt);
            @endif
            // --- [AKHIR PERBAIKAN] ---

            // --- VARIABEL GLOBAL ---
            let stagedMaterials = [];
            let finalUploadResults = [];
            let sapUsername = '';
            let sapPassword = '';

            // [PERBAIKAN] Event listener untuk Material Type - dipindahkan keluar dari form condition
            const materialTypeSelect = document.getElementById('material_type');
            const fertOptionsContainer = document.getElementById('fert-options');
            const divisionSelect = document.getElementById('division');
            const channelSelect = document.getElementById('distribution_channel');

            if (materialTypeSelect && fertOptionsContainer) {
                console.log('Material Type event listener attached');
                materialTypeSelect.addEventListener('change', function () {
                    const isFert = this.value === 'FERT';
                    console.log('Material Type changed:', this.value, 'isFert:', isFert);

                    // [PERBAIKAN] Toggle class dengan cara eksplisit dan style display
                    if (isFert) {
                        fertOptionsContainer.classList.remove('d-none');
                        fertOptionsContainer.style.display = '';
                        console.log('FERT options container shown');
                    } else {
                        fertOptionsContainer.classList.add('d-none');
                        fertOptionsContainer.style.display = 'none';
                        console.log('FERT options container hidden');
                    }

                    // [PERBAIKAN] Null check untuk division dan channel
                    if (divisionSelect) {
                        divisionSelect.required = isFert;
                        if (!isFert) {
                            divisionSelect.value = '';
                        }
                    }
                    if (channelSelect) {
                        channelSelect.required = isFert;
                        if (!isFert) {
                            channelSelect.value = '';
                        }
                    }
                });
            } else {
                console.error('Material Type Select or FERT Options Container not found!', {
                    materialTypeSelect: !!materialTypeSelect,
                    fertOptionsContainer: !!fertOptionsContainer
                });
            }

            // --- LOGIKA FORM AWAL ---
            const form = document.getElementById('upload-form');
            if (form) {
                const dropZone = document.getElementById('drop-zone');
                const generateBtn = document.getElementById('generate-btn');
                const fileInput = document.getElementById('file-input');
                const submitButton = document.getElementById('submit-button');
                const fileInfoText = document.getElementById('file-info-text');
                const mainText = document.getElementById('main-text');

                function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }

                // [PERBAIKAN] Null check untuk dropZone
                if (dropZone) {
                    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                        dropZone.addEventListener(eventName, preventDefaults, false);
                        document.body.addEventListener(eventName, preventDefaults, false);
                    });
                    ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false));
                    ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false));

                    dropZone.addEventListener('drop', (event) => {
                        if (event.dataTransfer.files.length > 0) handleFile(event.dataTransfer.files[0]);
                    }, false);

                    dropZone.addEventListener('click', () => {
                        if (fileInput) fileInput.click();
                    });

                    // Handler untuk perangkat sentuh
                    dropZone.addEventListener('touchstart', (e) => {
                        e.preventDefault();
                        if (fileInput) fileInput.click();
                    });
                }

                if (fileInput) {
                    fileInput.addEventListener('change', () => {
                        if (fileInput.files.length > 0) handleFile(fileInput.files[0]);
                    });
                }

                function handleFile(file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    if (fileInput) {
                        fileInput.files = dataTransfer.files;
                    }
                    if (mainText) mainText.textContent = "File Selected:";
                    if (fileInfoText) fileInfoText.textContent = file.name;
                    if (submitButton) submitButton.classList.remove('d-none');
                }

                // [PERBAIKAN] Event listener untuk Generate Button dengan null check
                if (generateBtn && materialTypeSelect) {
                    generateBtn.addEventListener('click', async () => {
                        const materialCodeInput = document.getElementById('start_material_code');
                        if (!materialCodeInput) return;

                        const spinner = generateBtn.querySelector('.spinner-border');
                        const btnIcon = generateBtn.querySelector('.bi-stars');
                        const selectedType = materialTypeSelect.value;

                        if (!selectedType) {
                            alert('Please select a Material Type first.');
                            return;
                        }

                        if (spinner) spinner.classList.remove('d-none');
                        if (btnIcon) btnIcon.classList.add('d-none');
                        generateBtn.disabled = true;

                        try {
                            // [PERBAIKAN] Mengubah dari GET ke POST
                            const response = await fetch(`{{ route('api.material.generate') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    material_type: selectedType
                                })
                            });

                            // [PERBAIKAN] Cek session_expiry (419)
                            if (response.status === 419) {
                                alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk login.');
                                window.location.reload();
                                return;
                            }

                            const data = await response.json();

                            if (response.ok) {
                                materialCodeInput.value = data.next_material_code || '';
                            } else {
                                // [PERBAIKAN] Tangani error 423 (Terkunci)
                                if (response.status === 423 && data.lock_expires_at) {
                                    alert(`Error: ${data.error || 'Failed to retrieve data from server.'}`);
                                    // Mulai timer jika terkunci
                                    startLockCountdown(data.lock_expires_at);
                                } else {
                                    alert(`Error: ${data.error || 'Failed to retrieve data from server.'}`);
                                }
                            }
                        } catch (error) {
                            console.error('Generate error:', error);
                            alert('Network error. Please ensure the SAP service (Python) is running.');
                        } finally {
                            // Hanya setel ulang tombol jika TIDAK terkunci
                            if (!countdownInterval) {
                                if (spinner) spinner.classList.add('d-none');
                                if (btnIcon) btnIcon.classList.remove('d-none');
                                generateBtn.disabled = false;
                            }
                        }
                    });
                }

                // [PERBAIKAN] Event listener untuk Submit Button dengan null check
                if (submitButton) {
                    submitButton.addEventListener('click', function(event) {
                        event.preventDefault();
                        if (form && form.checkValidity()) {
                            const spinner = submitButton.querySelector('.spinner-border');
                            const text = document.getElementById('submit-button-text');
                            if (spinner) spinner.classList.remove('d-none');
                            if (text) text.textContent = 'Processing...';
                            submitButton.disabled = true;
                            form.submit();
                        } else if (form) {
                            form.reportValidity();
                        }
                    });
                }
            }

            // --- LOGIKA HALAMAN HASIL (WORKFLOW BARU) ---
            const downloadArea = document.getElementById('download-area');
            if (downloadArea) {
                const uploadSapBtn = document.getElementById('upload-sap-btn');
                const downloadOnlyBtn = document.getElementById('download-only-btn');
                const sapLoginModalEl = document.getElementById('sapLoginModal');
                const sapLoginModal = new bootstrap.Modal(sapLoginModalEl);
                const confirmationModalEl = document.getElementById('confirmationModal');
                const confirmationModal = new bootstrap.Modal(confirmationModalEl);
                const confirmLoginBtn = document.getElementById('confirm-action-btn');
                const confirmActivateBtn = document.getElementById('confirm-activate-btn');
                const uploadResultDiv = document.getElementById('upload-result');
                const progressContainer = document.getElementById('progress-container');
                const sendEmailBtn = document.getElementById('send-email-btn');
                const emailNotificationArea = document.getElementById('email-notification-area');
                const emailRecipientInput = document.getElementById('email-recipient');
                const emailResultDiv = document.getElementById('email-result');
                const inspectionPlanResultDiv = document.getElementById('inspection-plan-result');

                if(uploadSapBtn) uploadSapBtn.addEventListener('click', () => sapLoginModal.show());
                if(confirmLoginBtn) confirmLoginBtn.addEventListener('click', handleStaging);
                if(confirmActivateBtn) confirmActivateBtn.addEventListener('click', handleFinalActivation);
                if(sendEmailBtn) sendEmailBtn.addEventListener('click', handleSendEmail);

                async function handleStaging() {
                    sapUsername = document.getElementById('sap-username').value;
                    sapPassword = document.getElementById('sap-password').value;
                    if (!sapUsername || !sapPassword) {
                        alert('Username and Password are required.');
                        return;
                    }
                    sapLoginModal.hide();
                    setLoadingState(uploadSapBtn, true, 'Staging...');
                    showProgressBar('Preparing data for SAP...', 1); // Step 1

                    try {
                        const response = await fetch("{{ route('api.sap.stage') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                username: sapUsername,
                                password: sapPassword,
                                filename: uploadSapBtn.dataset.filename
                            })
                        });

                        // [PERBAIKAN] Cek session_expiry (419)
                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk login.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();
                        if (response.ok && result.status === 'staged') {
                            stagedMaterials = result.results;
                            const totalMaterials = stagedMaterials.length;
                            showProgressBar(`Data prepared successfully - ${totalMaterials} material(s) ready`, 2, totalMaterials, totalMaterials); // Step 2
                            setTimeout(() => showConfirmationModal(stagedMaterials), 500);
                        } else {
                            showResult(uploadResultDiv, false, result.message || 'Staging process failed.');
                        }

                    } catch (error) {
                        showResult(uploadResultDiv, false, 'Network Error during staging. Check the browser console for details.');
                    } finally {
                        setLoadingState(uploadSapBtn, false);
                        hideProgressBar();
                    }
                }

                function showConfirmationModal(materials) {
                    const modalBody = document.getElementById('confirmation-modal-body');
                    let materialListHtml = `<p>The following ${materials.length} material(s) will be uploaded and activated:</p><ul class="list-group">`;
                    materials.forEach(mat => {
                        materialListHtml += `<li class="list-group-item bg-transparent text-white border-secondary">${mat.Material}: ${mat['Material Description']}</li>`;
                    });
                    materialListHtml += '</ul>';
                    modalBody.innerHTML = materialListHtml;
                    confirmationModal.show();
                }

                async function handleFinalActivation() {
                    confirmationModal.hide();
                    setLoadingState(confirmActivateBtn, true);
                    uploadResultDiv.innerHTML = '';
                    inspectionPlanResultDiv.innerHTML = '';

                    const totalMaterials = stagedMaterials.length;
                    showProgressBar('Uploading materials to SAP...', 3, 0, totalMaterials); // Step 3

                    try {
                        const response = await fetch("{{ route('api.sap.activate_and_upload') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                username: sapUsername,
                                password: sapPassword,
                                materials: stagedMaterials
                            })
                        });

                        // [PERBAIKAN] Cek session_expiry (419)
                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk login.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();

                        if (response.ok && result.status === 'success') {
                            finalUploadResults = result.results.map(res => {
                                const staged = stagedMaterials.find(s => s.Material === res.material_code);
                                return {
                                    ...res,
                                    description: staged ? staged['Material Description'] : 'N/A'
                                };
                            });

                            const successfulMaterials = finalUploadResults.filter(r => r.status === 'Success');
                            const successCount = successfulMaterials.length;

                            // [PERBAIKAN] Update progress dengan jumlah material berhasil
                            showProgressBar('Activating Quality Management...', 4, successCount, totalMaterials); // Step 4
                            showResult(uploadResultDiv, true, result.message, result.results);

                            if (successfulMaterials.length > 0) {
                                await handleInspectionPlanCreation(successfulMaterials, sapUsername, sapPassword);
                                if(uploadSapBtn) uploadSapBtn.classList.add('d-none');
                                if(downloadOnlyBtn) downloadOnlyBtn.classList.add('d-none');
                                if(emailNotificationArea) emailNotificationArea.classList.remove('d-none');
                                if(sendEmailBtn) sendEmailBtn.classList.remove('d-none');
                            }
                        } else {
                            showProgressBar('Upload failed', 3, 0, totalMaterials);
                            showResult(uploadResultDiv, false, result.message || 'Upload process failed.');
                        }

                    } catch (error) {
                        showResult(uploadResultDiv, false, 'Network Error during final activation.');
                    } finally {
                        setLoadingState(confirmActivateBtn, false);
                        hideProgressBar();
                    }
                }

                async function handleInspectionPlanCreation(successfulMaterials, username, password) {
                    const totalForInspection = successfulMaterials.length;
                    showProgressBar('Creating Inspection Task Lists...', 4, 0, totalForInspection); // Step 4
                    try {
                        const planDetails = {
                            task_usage: '5',
                            task_status: '4',
                            control_key: 'QM01',
                            inspchar: 'THICKNESS'
                        };

                        const response = await fetch("{{ route('api.sap.create_inspection_plan') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                username: username,
                                password: password,
                                materials: successfulMaterials,
                                plan_details: planDetails
                            })
                        });

                        // [PERBAIKAN] Cek session_expiry (419)
                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk login.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();
                        if (response.ok && result.status === 'success' && result.results) {
                            const successCount = result.results.filter(r => r.status === 'Success').length;
                            showProgressBar('Inspection Task Lists created successfully', 4, successCount, totalForInspection);
                        }
                        showResult(inspectionPlanResultDiv, response.ok && result.status === 'success', result.message, result.results);

                    } catch (error) {
                        showResult(inspectionPlanResultDiv, false, 'Network Error during Inspection Plan creation.');
                    } finally {
                        hideProgressBar();
                    }
                }

                async function handleSendEmail() {
                    const emailInput = emailRecipientInput.value.trim();
                    if (!emailInput) {
                        alert('Please enter at least one recipient email address.');
                        return;
                    }
                    if (!finalUploadResults || finalUploadResults.length === 0) {
                        alert('There are no results to send.');
                        return;
                    }

                    let plant = '';
                    // Coba cari plant dari salah satu hasil
                    const resultWithPlant = finalUploadResults.find(r => r.plant);
                    if (resultWithPlant) {
                        plant = resultWithPlant.plant;
                    } else if (stagedMaterials.length > 0 && stagedMaterials[0].Plant) {
                        // Fallback ke data staged jika ada
                        plant = stagedMaterials[0].Plant;
                    } else {
                        // Fallback ke session jika ada (meskipun ini halaman hasil, mungkin berguna)
                        plant = "{{ session('processed_plant', '') }}";
                    }

                    if (!plant) {
                        alert('Plant information could not be found. Cannot send email.');
                        return;
                    }

                    const recipientsArray = emailInput.split(',')
                                                    .map(email => email.trim())
                                                    .filter(email => email !== '');

                    setLoadingState(sendEmailBtn, true);
                    if(emailResultDiv) emailResultDiv.innerHTML = '';
                    showProgressBar('Sending email notification...');

                    try {
                        const response = await fetch("{{ route('api.notification.send') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                recipients: recipientsArray,
                                results: finalUploadResults,
                                plant: plant
                            })
                        });

                        // [PERBAIKAN] Cek session_expiry (419)
                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk login.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();
                        if(response.ok) {
                            showResult(emailResultDiv, true, result.message || 'Email notification sent successfully!');
                            if(emailNotificationArea) emailNotificationArea.classList.add('d-none');
                            if(sendEmailBtn) sendEmailBtn.classList.add('d-none');
                        } else {
                            let errorMessages = result.message || 'Unknown server error';
                            if (result.errors) {
                                errorMessages = Object.values(result.errors).flat().join('<br>');
                            }
                            let finalErrorMessage = `Failed to send email: ${errorMessages}. <br><small>Please check the mail configuration in your application's <code>.env</code> file.</small>`;
                            showResult(emailResultDiv, false, finalErrorMessage);
                        }
                    } catch (error) {
                        let networkErrorMessage = 'Network error while sending email. Please ensure the application server is running and check the browser console for more details.';
                        showResult(emailResultDiv, false, networkErrorMessage);
                    } finally {
                        setLoadingState(sendEmailBtn, false);
                        hideProgressBar();
                    }
                }

                function showProgressBar(text, step = 0, materialCount = null, totalMaterials = null) {
                    const progressText = document.getElementById('progress-text');
                    const progressContainer = document.getElementById('progress-container');
                    const progressFill = document.getElementById('progress-fill');
                    const progressSteps = document.getElementById('progress-steps');

                    if (progressContainer && progressText) {
                        // [PERBAIKAN] Tambahkan informasi jumlah material berhasil jika tersedia
                        let displayText = text;
                        if (materialCount !== null && totalMaterials !== null && totalMaterials > 0) {
                            displayText = `${text} (${materialCount} dari ${totalMaterials} material berhasil)`;
                        }
                        progressText.textContent = displayText;
                        progressContainer.classList.remove('d-none');

                        // Update progress bar
                        if (progressFill) {
                            // Jika ada informasi material, gunakan persentase berdasarkan material
                            let progressPercentage;
                            if (materialCount !== null && totalMaterials !== null && totalMaterials > 0) {
                                progressPercentage = (materialCount / totalMaterials) * 100;
                            } else {
                                progressPercentage = (step / 4) * 100;
                            }
                            progressFill.style.width = `${progressPercentage}%`;
                            const progressBar = progressFill.closest('.progress-bar-enhanced');
                            if (progressBar) {
                                progressBar.setAttribute('aria-valuenow', progressPercentage);
                            }
                        }

                        // Update progress steps
                        if (progressSteps) {
                            const steps = progressSteps.querySelectorAll('.progress-step');
                            steps.forEach((stepEl, index) => {
                                stepEl.classList.remove('active', 'completed');
                                if (index < step) {
                                    stepEl.classList.add('completed');
                                } else if (index === step) {
                                    stepEl.classList.add('active');
                                }
                            });
                        }
                    }
                }

                function hideProgressBar() {
                    const progressContainer = document.getElementById('progress-container');
                    if (progressContainer) {
                        progressContainer.classList.add('d-none');
                        // Reset progress
                        const progressFill = document.getElementById('progress-fill');
                        if (progressFill) {
                            progressFill.style.width = '0%';
                        }
                        const progressSteps = document.getElementById('progress-steps');
                        if (progressSteps) {
                            progressSteps.querySelectorAll('.progress-step').forEach(step => {
                                step.classList.remove('active', 'completed');
                            });
                        }
                    }
                }

                const getHeaders = () => ({
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                });

                function setLoadingState(button, isLoading, text = null) {
                    if(!button) return;
                    const spinner = button.querySelector('.spinner-border');
                    const buttonTextEl = button.querySelector('i') ? button.childNodes[button.childNodes.length - 1] : null;
                    const originalText = button.dataset.originalText || (buttonTextEl ? buttonTextEl.textContent.trim() : '');
                    if(!button.dataset.originalText) button.dataset.originalText = originalText;

                    button.disabled = isLoading;
                    if(spinner) isLoading ? spinner.classList.remove('d-none') : spinner.classList.add('d-none');

                    if(buttonTextEl) {
                        buttonTextEl.textContent = isLoading && text ? ` ${text}` : ` ${originalText}`;
                    }
                }

                function showResult(div, isSuccess, message, details = null) {
                    if(!div) return;
                    const alertClass = isSuccess ? 'alert-success-frosted' : 'alert-danger';
                    let html = `<div class="alert ${alertClass}">${message || (isSuccess ? 'Process successful.' : 'An error occurred.')}</div>`;
                    if (details && Array.isArray(details)) {
                        html += '<div class="upload-details"><ul>';
                        details.forEach(item => {
                            const icon = item.status === 'Success' ? '✅' : '❌';
                            const plantInfo = item.plant ? `(Plant: ${item.plant})` : '';
                            html += `<li>${icon} <strong>${item.material_code}</strong> ${plantInfo}: ${item.message}</li>`;
                        });
                        html += '</ul></div>';
                    }
                    div.innerHTML = html;
                }
            }
        });
    </script>

</body>
</html>
