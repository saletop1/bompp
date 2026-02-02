<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAP BOM Uploader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        body{background-image:url("{{asset('images/ainun.jpg')}}");background-size:cover;background-position:center;background-attachment:fixed;min-height:100vh;padding-top:1rem;padding-bottom:1rem}.card{background:rgba(75,74,74,.33);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);border:1px solid rgba(255,255,255,.3);box-shadow:0 8px 32px 0 rgba(0,0,0,.1)}.converter-container{max-width:800px;margin-top:0;margin-bottom:1rem}#drop-zone{border:2px dashed rgba(255,255,255,.6);border-radius:.5rem;color:#343a40;transition:all .3s ease-in-out;background-color:rgba(255,255,255,.2)}#drop-zone.dragover{border-color:#0d6efd;background-color:rgba(13,110,253,.1)}.form-label{font-weight:600;color:#fff;text-shadow:1px 1px 3px rgba(0,0,0,.5)}.form-control,.form-select{background-color:rgba(255,255,255,.5);border:1px solid rgba(0,0,0,.1)}.form-control:focus,.form-select:focus{background-color:rgba(255,255,255,.8);border-color:#0d6efd;box-shadow:0 0 0 .25rem rgba(13,110,253,.25)}.btn-primary{background-color:#0d6efd;border-color:#0d6efd}.btn .spinner-border{width:1rem;height:1rem}.page-title-container{text-align:center}.page-title-container .main-title{color:#fff;text-shadow:1px 1px 3px rgba(0,0,0,.3);margin-bottom:0}.page-title-container .subtitle{color:#fff;text-shadow:1px 1px 2px rgba(0,0,0,.2);font-size:.9rem}.sap-logo-header{height:80px;width:auto;margin-left:20px}.upload-details{max-height:200px;overflow-y:auto;text-align:left;font-size:.85rem;background-color:rgba(0,0,0,.1);padding:10px;border-radius:5px;margin-top:15px}.upload-details ul{color:#fff;text-shadow:1px 1px 2px rgba(0,0,0,.5);list-style-type:none;padding-left:0}.modal-content.frosted-glass{background:rgba(30,30,30,.4);-webkit-backdrop-filter:blur(8px);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.2);box-shadow:0 8px 32px 0 rgba(0,0,0,.3);border-radius:.75rem;color:#fff}.input-group-underline{position:relative}.input-underline{background-color:transparent!important;border:none!important;border-bottom:2px solid rgba(255,255,255,.4)!important;border-radius:0!important;padding-left:35px!important;color:#fff!important;transition:border-color .3s ease}.input-underline::-moz-placeholder{color:rgba(255,255,255,.6);opacity:1}.input-underline::placeholder{color:rgba(255,255,255,.6);opacity:1}.input-underline:focus{box-shadow:none!important;border-bottom-color:#fff!important}.input-group-underline .bi{position:absolute;left:5px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.6);font-size:1.1rem}.frosted-glass .modal-header,.frosted-glass .modal-footer{border-bottom:none;border-top:none}.frosted-glass .modal-title{font-weight:300}.nav-pills .nav-link{background-color:rgba(255,255,255,.2);color:#f0f0f0;margin:0 5px;transition:all .3s ease;border:1px solid transparent}.nav-pills .nav-link.active,.nav-pills .show>.nav-link{background-color:#ffffff1c;color:#a6ff00ff;font-weight:700;box-shadow:0 8px 15px rgba(0,0,0,.76)}.nav-pills .nav-link:hover:not(.active){background-color:rgba(255,255,255,.4);color:#fff;border-color:rgba(255,255,255,.5)}.download-area-header{display:flex;justify-content:center;align-items:center;text-align:left}.download-area-header dotlottie-player{flex-shrink:0}.download-area-title{color:#fff;font-weight:700;text-shadow:2px 2px 4px rgba(0,0,0,.6)}.download-area-text{color:#d1d5db;font-size:1.1rem;text-shadow:1px 1px 2px rgba(0,0,0,.5)}#process-another-btn-bom{background-color:#198754!important;border-color:#198754!important;color:#fff!important}#process-another-btn-bom:hover{background-color:#157347!important;border-color:#146c43!important}.alert.alert-success-frosted{background:rgba(25,135,126,.4);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);border:1px solid rgba(255,255,255,.2);color:#fff;text-shadow:1px 1px 2px rgba(0,0,0,.5)}.alert.alert-danger{background:rgba(220,53,69,.5);-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);border:1px solid rgba(255,255,255,.2);color:#fff;text-shadow:1px 1px 2px rgba(0,0,0,.5)}#progress-text{color:#e9ecef;text-shadow:1px 1px 2px rgba(0,0,0,.7);font-weight:500;margin-top:1rem}.main-container{display:flex;justify-content:center;align-items:center;height:100%;width:100%}.loader{width:100%;max-width:400px}.trace-bg{stroke:#333;stroke-width:1.8;fill:none}.trace-flow{stroke-width:1.8;fill:none;stroke-dasharray:40 400;stroke-dashoffset:438;filter:drop-shadow(0 0 6px currentColor);animation:flow 3s cubic-bezier(.5,0,.9,1) infinite}.yellow{stroke:#ffea00;color:#ffea00}.blue{stroke:#00ccff;color:#00ccff}.green{stroke:#00ff15;color:#00ff15}.purple{stroke:#9900ff;color:#9900ff}.red{stroke:#ff3300;color:#ff3300}@keyframes flow{to{stroke-dashoffset:0}}.chip-body{rx:5;ry:5}.chip-text{font-weight:700;letter-spacing:1px;font-size:14px}.chip-pin{stroke:#444;stroke-width:.5;filter:drop-shadow(0 0 2px rgba(0,0,0,.6))}
        #drop-zone dotlottie-player {
            pointer-events: none;
        }
        /* [PERBAIKAN WARNA] Menambahkan style untuk 'Not Found' */
        .upload-details ul li.result-success {
            color: #c8e6c9; /* Hijau muda */
        }
        .upload-details ul li.result-not-found {
            color: #ffe082; /* Kuning muda */
        }
        .upload-details ul li.result-failed {
            color: #ffcdd2; /* Merah muda */
        }

        /* [BARU] Animasi Floating Particles untuk BOM */
        .particles-container-bom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .particle-bom {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float-bom 15s infinite ease-in-out;
        }

        @keyframes float-bom {
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

        /* [BARU] Pastikan konten utama berada di atas particles */
        .container.converter-container {
            position: relative;
            z-index: 1;
        }

        .card {
            position: relative;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- [BARU] Floating Particles Animation untuk BOM -->
    <div class="particles-container-bom" id="particles-bom"></div>

    @auth
    <style>
        .logout-floating { position: fixed; top: 14px; right: 14px; z-index: 1100; }
        .logout-floating form { display: inline; }
        .logout-btn { background: rgba(0,0,0,0.35); border: 1px solid rgba(255,255,255,0.3); color: #fff; }
        .logout-btn:hover { background: rgba(0,0,0,0.55); }
    </style>
    <div class="logout-floating">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sm logout-btn" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
    @endauth
    <div class="container converter-container">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Bill of Materials (BOM) Uploader</h1>
                <p class="subtitle mb-0">Accelerate Engineering-to-ERP Sync: Automate BOM Uploads to SAP</p>
            </div>
            <img src="{{ asset('images/saplogo.png') }}" alt="SAP Logo" class="sap-logo-header">
        </div>

        <div class="card p-4 p-lg-5">
            <div class="card-body">
                @if (!session('processed_filename'))
                    <ul class="nav nav-pills nav-fill mb-4">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('converter.index') ? 'active' : '' }}" href="{{ route('converter.index') }}">
                                <i class="bi bi-box-seam me-2"></i>Material Master
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}" href="{{ route('bom.index') }}">
                                <i class="bi bi-diagram-3 me-2"></i>BOM Master
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('routing.index') ? 'active' : '' }}" href="{{ route('routing.index') }}">
                                <i class="bi bi-signpost-split me-2"></i>Routing Master
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
                    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif

                @if (session('processed_filename'))
                <div id="result-area" class="text-center">

                    @if (session('header_error'))
                        <div class="alert alert-danger">
                            <h4><i class="bi bi-exclamation-triangle-fill"></i> Header File Tidak Valid</h4>
                            <p class="mb-0">{{ session('header_error') }}</p>
                        </div>
                    @endif

                    <div class="download-area-header">
                        <div class="ms-3">
                            <h4 class="download-area-title mb-0">File Processed!</h4>
                            <p class="download-area-text mt-0">{{ session('success') }}</p>
                        </div>
                    </div>

                    <div id="progress-container" class="text-center mt-4 d-none">
                        <div class="main-container" style="height: 200px;">
                            <svg class="loader" viewBox="0 0 400 200" xmlns="http://www.w3.org/2000/svg">
                                <path class="trace-bg" d="M145 100 C 50 100, 50 20, 20 20"></path>
                                <path class="trace-flow blue" d="M145 100 C 50 100, 50 20, 20 20"></path>
                                <path class="trace-bg" d="M145 110 C 60 110, 60 180, 20 180"></path>
                                <path class="trace-flow green" d="M145 110 C 60 110, 60 180, 20 180" style="animation-delay: 0.2s;"></path>
                                <path class="trace-bg" d="M145 90 C 80 90, 80 50, 40 50"></path>
                                <path class="trace-flow yellow" d="M145 90 C 80 90, 80 50, 40 50" style="animation-delay: 0.4s;"></path>
                                <path class="trace-bg" d="M255 100 C 350 100, 350 20, 380 20"></path>
                                <path class="trace-flow red" d="M255 100 C 350 100, 350 20, 380 20" style="animation-delay: 0.6s;"></path>
                                <path class="trace-bg" d="M255 110 C 340 110, 340 180, 380 180"></path>
                                <path class="trace-flow purple" d="M255 110 C 340 110, 340 180, 380 180" style="animation-delay: 0.8s;"></path>
                                <path class="trace-bg" d="M255 90 C 320 90, 320 50, 360 50"></path>
                                <path class="trace-flow blue" d="M255 90 C 320 90, 320 50, 360 50" style="animation-delay: 1s;"></path>
                                <g>
                                    <rect x="150" y="80" width="100" height="50" class="chip-body" fill="#222" stroke="#555" stroke-width="1"></rect>
                                    <text x="200" y="108" text-anchor="middle" class="chip-text" fill="#00ff15">SAP</text>
                                    <rect x="145" y="85" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="145" y="95" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="145" y="105" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="145" y="115" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="250" y="85" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="250" y="95" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="250" y="105" width="5" height="3" class="chip-pin"></rect>
                                    <rect x="250" y="115" width="5" height="3" class="chip-pin"></rect>
                                </g>
                            </svg>
                        </div>
                        <p id="progress-text">Uploading...</p>
                    </div>
                    <div id="result-display" class="mt-3"></div>
                    <div id="email-result" class="mt-3"></div>
                    <div id="email-notification-area" class="mt-4 d-none">
                        <div class="input-group mb-3 mx-auto" style="max-width: 450px;">
                            <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                            <input type="text" id="email-recipient-bom" class="form-control" placeholder="Masukkan email, pisahkan dengan koma...">
                        </div>
                    </div>
                    <!-- [PERBAIKAN 2-KLIK] Mengembalikan tombol "Generate" dan memodifikasi "Upload" -->
                    <div id="action-buttons" class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <button type="button" id="generate-codes-btn"
                                class="btn btn-warning btn-lg px-4 @if(session('header_error')) disabled @endif"
                                data-filename="{{ session('processed_filename') }}"
                                @if(session('header_error')) disabled title="Tombol dinonaktifkan karena header file tidak valid." @endif>
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>
                            <i class="bi bi-stars"></i> Generate Missing Codes
                        </button>
                        <button type="button" id="upload-bom-btn" class="btn btn-primary btn-lg px-4 d-none" data-filename="{{ session('processed_filename') }}">
                            <span class="spinner-border spinner-border-sm d-none me-2"></span>
                            <i class="bi bi-cloud-upload"></i> Upload BOM to SAP
                        </button>
                        <a href="{{ route('bom.download', ['filename' => session('processed_filename')]) }}" id="download-processed-btn" class="btn btn-success btn-lg px-4 d-none">
                            <i class="bi bi-download"></i> Download Processed File
                        </a>
                        <a href="{{ route('bom.download_routing_template', ['filename' => session('processed_filename')]) }}" id="download-routing-btn" class="btn btn-info btn-lg px-4 d-none">
                            <i class="bi bi-signpost-split"></i> Download Routing
                        </a>
                        <button type="button" id="send-email-btn-bom" class="btn btn-danger btn-lg px-4 d-none">
                            <span class="spinner-border spinner-border-sm d-none"></span>
                            <i class="bi bi-envelope-at-fill"></i> Send Notification
                        </button>
                        <a href="{{ route('bom.index') }}" id="process-another-btn-bom" class="btn btn-secondary btn-lg px-4"><i class="bi bi-arrow-repeat"></i> Process Another</a>
                    </div>
                </div>
                @else
                <form action="{{ route('bom.upload') }}" method="post" enctype="multipart/form-data" id="upload-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="plant" class="form-label">Plant</label>
                            <input type="text" name="plant" id="plant" class="form-control" required placeholder="Contoh: 1000">
                        </div>
                        <div class="col-12 mt-4">
                            <input type="file" name="file" id="file-input" accept=".xls,.xlsx,.csv" required class="d-none">
                            <div id="drop-zone" class="p-4 text-center" style="cursor: pointer;">
                                <dotlottie-player src="{{ asset('animations/Greenish arrow down.lottie') }}" background="transparent" speed="1" style="width: 150px; height: 150px; margin: 0 auto;" loop autoplay></dotlottie-player>
                                <p class="mb-0 mt-2 fw-bold" id="main-text">Drag & drop your BOM Excel file here</p>
                                <p class="text-muted small" id="file-info-text">or click to browse</p>
                            </div>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" id="submit-button" class="btn btn-primary btn-lg d-none">
                                <span class="spinner-border spinner-border-sm me-2 d-none"></span>
                                <i class="bi bi-gear-fill"></i>
                                <span id="submit-button-text">Process File</span>
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
                        <input type="text" class="form-control input-underline" id="sap-username" placeholder="Username" autocomplete="username">
                    </div>
                    <div class="input-group-underline mb-3">
                        <i class="bi bi-shield-lock"></i>
                        <input type="password" class="form-control input-underline" id="sap-password" placeholder="Password" autocomplete="current-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-action-btn">
                        <i class="bi bi-cloud-upload"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * [BARU] Floating Particles Animation untuk BOM
         */
        function createParticlesBom() {
            const container = document.getElementById('particles-bom');
            if (!container) return;

            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle-bom';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // [BARU] Inisialisasi particles untuk BOM
            createParticlesBom();

            let finalBomUploadResults = [];
            let generatedCodeResults = {}; // Simpan hasil generate code di sini
            const processedPlant = @json(session('processed_plant'));
            const uploadMusic = document.getElementById('upload-music');

            const getHeaders = () => ({
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            });
            const getAuthBody = (additionalData) => JSON.stringify({
                username: document.getElementById('sap-username').value,
                password: document.getElementById('sap-password').value,
                ...additionalData
            });
            function setLoadingState(button, isLoading) {
                if (!button) return;
                const spinner = button.querySelector('.spinner-border');
                button.disabled = isLoading;
                if (spinner) isLoading ? spinner.classList.remove('d-none') : spinner.classList.add('d-none');
            }

            /**
             * [FUNGSI DIPERBARUI]
             * Menampilkan hasil dengan format yang berbeda untuk 'Generate Code' vs 'Upload BOM'
             */
            function showResult(div, isSuccess, message, details = null, isFinalSummary = false) {
                if (!div) return;

                // Tentukan alert class hanya untuk summary box
                if (isFinalSummary) {
                    const alertClass = isSuccess ? 'alert-success-frosted' : 'alert-danger';
                    let html = `<div class="alert ${alertClass}">${message || (isSuccess ? 'Process successful.' : 'An error occurred.')}</div>`;
                    div.innerHTML = html;
                    return;
                }

                // Logika untuk menampilkan detail (bukan summary box)
                let html = '';
                if (details && Array.isArray(details)) {
                    html += '<div class="upload-details"><ul>';
                    details.forEach(item => {
                        // Logika untuk hasil 'Upload BOM' (memiliki 'message')
                        if (item.message !== undefined) {
                            const isSuccessUpload = item.status === 'Success';
                            const icon = isSuccessUpload ? '✅' : '❌';
                            const liClass = isSuccessUpload ? 'result-success' : 'result-failed';

                            let originalMessage = item.message;
                            if (isSuccessUpload && originalMessage.includes('berhasil dibuat untuk material')) {
                                originalMessage = originalMessage.split(' untuk material')[0];
                            }
                            const cleanMessage = originalMessage.replace(/^✅\s*|^\s*/, '').replace(/0*(\d+)/g, "$1");

                            html += `<li class="${liClass}">${icon} ${cleanMessage}</li>`;
                        }
                        // Logika untuk hasil 'Generate Code' (memiliki 'code')
                        else if (item.code !== undefined) {
                            const isSuccessGenerate = item.code !== '#NOT_FOUND#' && item.code !== '#ERROR#';
                            // [PERBAIKAN IKON] Ganti '❌' menjadi '⚠️' untuk 'Not Found'
                            const icon = isSuccessGenerate ? '✅' : (item.code === '#NOT_FOUND#' ? '⚠️' : '❌');
                            const liClass = isSuccessGenerate ? 'result-success' : (item.code === '#NOT_FOUND#' ? 'result-not-found' : 'result-failed');

                            const cleanCode = (item.code.match(/^[a-zA-Z]/) || item.code.startsWith('#')) ? item.code : parseInt(item.code, 10);
                            html += `<li class="${liClass}">${icon} <strong>${item.description}:</strong> ${cleanCode}</li>`;
                        }
                    });
                    html += '</ul></div>';
                    div.innerHTML = html; // Ganti isi div dengan detail
                } else if (message) {
                    // Fallback jika hanya ada pesan (bukan summary)
                    const alertClass = isSuccess ? 'alert-success-frosted' : 'alert-danger';
                    div.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
                }
            }

            function showProgressBar(text) {
                const progressContainer = document.getElementById('progress-container');
                const progressText = document.getElementById('progress-text');
                if (progressContainer && progressText) {
                    progressText.textContent = text;
                    progressContainer.classList.remove('d-none');
                }
            }
            function hideProgressBar() {
                const progressContainer = document.getElementById('progress-container');
                if (progressContainer) {
                    progressContainer.classList.add('d-none');
                }
            }
            const form = document.getElementById('upload-form');
            if (form) {
                const dropZone = document.getElementById('drop-zone');
                const fileInput = document.getElementById('file-input');
                const submitButton = document.getElementById('submit-button');
                const fileInfoText = document.getElementById('file-info-text');
                const mainText = document.getElementById('main-text');
                function preventDefaults(e) { e.preventDefault(); e.stopPropagation(); }
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                    document.body.addEventListener(eventName, preventDefaults, false);
                });
                ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false));
                ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false));
                dropZone.addEventListener('drop', (event) => {
                    if (event.dataTransfer.files.length > 0) handleFile(event.dataTransfer.files[0]);
                }, false);
                dropZone.addEventListener('click', () => fileInput.click());
                fileInput.addEventListener('change', () => { if (fileInput.files.length > 0) handleFile(fileInput.files[0]); });
                function handleFile(file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                    mainText.textContent = "File Selected:";
                    fileInfoText.textContent = file.name;
                    submitButton.classList.remove('d-none');
                }
                submitButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (form.checkValidity()) {
                        setLoadingState(submitButton, true);
                        form.submit();
                    } else {
                        form.reportValidity();
                    }
                });
            }
            const resultArea = document.getElementById('result-area');
            if (resultArea) {
                const generateBtn = document.getElementById('generate-codes-btn');
                const uploadBomBtn = document.getElementById('upload-bom-btn');
                const downloadBtn = document.getElementById('download-processed-btn');
                const downloadRoutingBtn = document.getElementById('download-routing-btn');
                const resultDiv = document.getElementById('result-display');
                const sapLoginModal = new bootstrap.Modal(document.getElementById('sapLoginModal'));
                const confirmBtn = document.getElementById('confirm-action-btn');
                const emailNotificationArea = document.getElementById('email-notification-area');
                const sendEmailBtn = document.getElementById('send-email-btn-bom');
                const emailResultDiv = document.getElementById('email-result');

                // [PERBAIKAN 2-KLIK] Event listener dikembalikan ke tombol "Generate"
                generateBtn.addEventListener('click', handleGenerateCodes);
                uploadBomBtn.addEventListener('click', () => {
                    // Cek jika generate code belum dijalankan
                    if (Object.keys(generatedCodeResults).length === 0) {
                        alert('Harap jalankan "Generate Missing Codes" terlebih dahulu.');
                        return;
                    }
                    sapLoginModal.show();
                });
                confirmBtn.addEventListener('click', () => {
                    sapLoginModal.hide();
                    handleBomUpload();
                });
                sendEmailBtn.addEventListener('click', handleSendBomEmail);


                /**
                 * [FUNGSI DIPERBARUI - LIVE PROGRESS 1/3]
                 * Meng-handle proses "Generate Missing Codes"
                 */
                async function handleGenerateCodes() {
                    setLoadingState(generateBtn, true);
                    resultDiv.innerHTML = ''; // Kosongkan hasil sebelumnya
                    showProgressBar('Mempersiapkan daftar material...');
                    generatedCodeResults = {}; // Reset hasil
                    let hasNotFound = false;
                    let hasError = false;

                    try {
                        // LANGKAH 1: Dapatkan daftar deskripsi yang perlu dicari
                        const listResponse = await fetch("{{ route('api.bom.generate_codes') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({ filename: generateBtn.dataset.filename })
                        });

                        // [FIX 419] Menangani session timeout
                        if (listResponse.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                            window.location.reload();
                            return;
                        }
                        if (!listResponse.ok) {
                            throw new Error('Gagal mendapatkan daftar material dari server.');
                        }

                        const listResult = await listResponse.json();

                        if (listResult.status !== 'success' || !listResult.descriptions) {
                            throw new Error(listResult.message || 'Server tidak mengembalikan daftar material.');
                        }

                        const descriptions = listResult.descriptions;
                        if (descriptions.length === 0) {
                            showProgressBar('Tidak ada kode material yang perlu dicari.');
                            showResult(resultDiv, true, 'Semua material sudah memiliki kode. Siap untuk di-upload.', null, true);
                            generateBtn.classList.add('d-none'); // Sembunyikan generate
                            uploadBomBtn.classList.remove('d-none'); // Tampilkan upload
                            downloadBtn.classList.remove('d-none');
                            downloadRoutingBtn.classList.remove('d-none');
                            hideProgressBar();
                            setLoadingState(generateBtn, false);
                            // Set hasil kosong agar upload bisa lanjut
                            generatedCodeResults = { status: 'empty' };
                            return;
                        }

                        // Buat kontainer untuk hasil live
                        resultDiv.innerHTML = '<div class="upload-details"><ul></ul></div>';
                        const resultList = resultDiv.querySelector('ul');

                        // LANGKAH 2: Loop dan cari setiap deskripsi
                        for (let i = 0; i < descriptions.length; i++) {
                            const description = descriptions[i];
                            showProgressBar(`Mencari kode (${i + 1}/${descriptions.length}): "${description}"`);

                            try {
                                const findResponse = await fetch("{{ route('api.bom.api_find_material') }}", {
                                    method: 'POST',
                                    headers: getHeaders(),
                                    body: JSON.stringify({ description: description })
                                });

                                // [FIX 419] Menangani session timeout
                                if (findResponse.status === 419) {
                                    alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                                    window.location.reload();
                                    return;
                                }

                                const result = await findResponse.json();
                                let icon = '❌';
                                let liClass = 'result-failed';
                                let foundCode = result.code || '#ERROR#';

                                if (result.status === 'success') {
                                    icon = '✅';
                                    liClass = 'result-success';
                                    foundCode = result.code;
                                } else if (result.code === '#NOT_FOUND#') {
                                    icon = '⚠️';
                                    liClass = 'result-not-found';
                                    hasNotFound = true;
                                } else {
                                    hasError = true;
                                }

                                generatedCodeResults[description] = foundCode; // Simpan hasil
                                const cleanCode = (foundCode.match(/^[a-zA-Z]/) || foundCode.startsWith('#')) ? foundCode : parseInt(foundCode, 10);
                                resultList.innerHTML += `<li class="${liClass}">${icon} <strong>${description}:</strong> ${cleanCode}</li>`;
                                resultList.scrollTop = resultList.scrollHeight; // Auto-scroll

                            } catch (loopError) {
                                hasError = true;
                                generatedCodeResults[description] = '#ERROR#'; // Tandai sebagai error
                                resultList.innerHTML += `<li class="result-failed">❌ <strong>${description}:</strong> Network Error</li>`;
                                resultList.scrollTop = resultList.scrollHeight;
                            }
                        }

                        // LANGKAH 3: Simpan semua hasil ke server (file JSON)
                        showProgressBar('Menyimpan kode yang ditemukan...');
                        const saveResponse = await fetch("{{ route('api.bom.save_generated_codes') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                filename: generateBtn.dataset.filename,
                                results: generatedCodeResults
                            })
                        });

                        // [FIX 419] Menangani session timeout
                        if (saveResponse.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                            window.location.reload();
                            return;
                        }

                        const saveResult = await saveResponse.json();

                        // Tampilkan box summary
                        const summaryDiv = document.createElement('div');
                        resultDiv.prepend(summaryDiv);
                        showResult(summaryDiv, saveResult.status === 'success', saveResult.message, null, true);

                        // Sembunyikan tombol Generate, tampilkan sisanya
                        generateBtn.classList.add('d-none');
                        uploadBomBtn.classList.remove('d-none');
                        downloadBtn.classList.remove('d-none');
                        downloadRoutingBtn.classList.remove('d-none');

                        // Nonaktifkan tombol Upload jika ada "Not Found" atau Error
                        if (hasNotFound || hasError) {
                            uploadBomBtn.disabled = true;
                            const errorMsg = hasError ? 'Upload dinonaktifkan karena ada error API.' : 'Upload dinonaktifkan karena ada kode material yang tidak ditemukan.';
                            uploadBomBtn.setAttribute('title', errorMsg);
                            uploadBomBtn.classList.remove('btn-primary');
                            uploadBomBtn.classList.add('btn-secondary');
                        }

                    } catch (error) {
                        showResult(resultDiv, false, `Proses Generate Gagal: ${error.message}`, null, true);
                    } finally {
                        setLoadingState(generateBtn, false);
                        hideProgressBar();
                    }
                }

                /**
                 * [FUNGSI DIPERBARUI - LIVE PROGRESS 2/3]
                 * Meng-handle proses "Upload BOM to SAP"
                 */
                async function handleBomUpload() {
                    setLoadingState(uploadBomBtn, true);
                    resultDiv.innerHTML = ''; // Kosongkan hasil generate code
                    showProgressBar('Mempersiapkan daftar BOM untuk di-upload...');
                    if (uploadMusic) uploadMusic.play();

                    finalBomUploadResults = []; // Reset hasil upload
                    let bomsToUpload = [];
                    let descriptionMap = {};

                    try {
                        // LANGKAH 1 (Upload): Dapatkan daftar BOM yang sudah siap (dari JSON)
                        const listResponse = await fetch("{{ route('api.bom.upload') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: getAuthBody({ filename: uploadBomBtn.dataset.filename })
                        });

                        // [FIX 419] Menangani session timeout
                        if (listResponse.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                            window.location.reload();
                            return;
                        }

                        const listResult = await listResponse.json();

                        if (listResult.status !== 'success' || !listResult.boms_to_upload) {
                            throw new Error(listResult.message || 'Server tidak mengembalikan daftar BOM untuk di-upload.');
                        }

                        bomsToUpload = listResult.boms_to_upload;
                        descriptionMap = listResult.description_map;

                        if (bomsToUpload.length === 0) {
                            showResult(resultDiv, true, 'Tidak ada BOM valid yang perlu di-upload.', null, true);
                            uploadBomBtn.classList.add('d-none'); // Sembunyikan tombol upload
                            setLoadingState(uploadBomBtn, false);
                            hideProgressBar();
                            return;
                        }

                        // Buat kontainer untuk hasil live
                        resultDiv.innerHTML = '<div class="upload-details"><ul></ul></div>';
                        const resultList = resultDiv.querySelector('ul');

                        // LANGKAH 2 (Upload): Loop dan kirim setiap BOM
                        for (let i = 0; i < bomsToUpload.length; i++) {
                            const bom = bomsToUpload[i];
                            const materialCode = bom.IV_MATNR.replace(/^0+/, ''); // Kode bersih
                            showProgressBar(`Proses BOM (${i + 1}/${bomsToUpload.length}): '${materialCode}'`);

                            try {
                                const uploadResponse = await fetch("{{ route('api.bom.upload_single') }}", {
                                    method: 'POST',
                                    headers: getHeaders(),
                                    body: getAuthBody({ bom: bom })
                                });

                                // [FIX 419] Menangani session timeout
                                if (uploadResponse.status === 419) {
                                    alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                                    window.location.reload();
                                    return;
                                }

                                const result = await uploadResponse.json();

                                // Ambil hasil spesifik dari array 'results' (karena API /upload_bom mengembalikan array)
                                const bomResult = result.results ? result.results[0] : null;

                                if (bomResult) {
                                    // Tambahkan deskripsi untuk notifikasi email
                                    bomResult.description = descriptionMap[ltrim(bomResult.material_code, '0')] || 'N/A';
                                    finalBomUploadResults.push(bomResult); // Kumpulkan hasil

                                    // Tampilkan di UI
                                    const icon = bomResult.status === 'Success' ? '✅' : '❌';
                                    const liClass = bomResult.status === 'Success' ? 'result-success' : 'result-failed';
                                    const cleanMessage = bomResult.message.replace(/^✅\s*|^\s*/, '').replace(/0*(\d+)/g, "$1");
                                    resultList.innerHTML += `<li class="${liClass}">${icon} ${cleanMessage}</li>`;
                                } else {
                                    // Jika format respons tidak terduga
                                    throw new Error(result.message || 'Respons upload tidak valid');
                                }

                            } catch (loopError) {
                                const errorResult = {
                                    status: 'Failed',
                                    material_code: bom.IV_MATNR,
                                    message: `❌ Network Error: ${loopError.message}`,
                                    description: descriptionMap[ltrim(bom.IV_MATNR, '0')] || 'N/A'
                                };
                                finalBomUploadResults.push(errorResult);
                                resultList.innerHTML += `<li class="result-failed">${errorResult.message}</li>`;
                            }
                            resultList.scrollTop = resultList.scrollHeight; // Auto-scroll
                        }

                        // LANGKAH 3 (Upload): Tampilkan tombol notifikasi jika ada yang sukses
                        const successfulUploads = finalBomUploadResults.filter(r => r.status === 'Success');
                        if (successfulUploads.length > 0) {
                            emailNotificationArea.classList.remove('d-none');
                            sendEmailBtn.classList.remove('d-none');
                        }

                        // Sembunyikan tombol upload jika semua sukses
                        const hasFailures = finalBomUploadResults.some(r => r.status === 'Failed');
                        if (!hasFailures) {
                            uploadBomBtn.classList.add('d-none');
                        }

                        // Tampilkan box summary
                        const summaryDiv = document.createElement('div');
                        resultDiv.prepend(summaryDiv);
                        showResult(summaryDiv, true, `Proses upload selesai. Sukses: ${successfulUploads.length}, Gagal: ${finalBomUploadResults.length - successfulUploads.length}`, null, true);


                    } catch (error) {
                        showResult(resultDiv, false, `Proses Upload Gagal: ${error.message}`, null, true);
                    } finally {
                        setLoadingState(uploadBomBtn, false);
                        hideProgressBar();
                        if (uploadMusic) {
                            uploadMusic.pause();
                            uploadMusic.currentTime = 0;
                        }
                    }
                }

                async function handleSendBomEmail() {
                    setLoadingState(sendEmailBtn, true);
                    emailResultDiv.innerHTML = '';
                    showProgressBar('Sending email notification...');
                    try {
                        const recipientInput = document.getElementById('email-recipient-bom');
                        const recipientsString = recipientInput.value.trim();
                        if (!recipientsString) {
                            alert('Silakan masukkan setidaknya satu alamat email penerima.');
                            setLoadingState(sendEmailBtn, false); hideProgressBar(); return;
                        }
                        if (!finalBomUploadResults || finalBomUploadResults.length === 0) {
                            alert('Tidak ada hasil untuk dikirim.');
                            setLoadingState(sendEmailBtn, false); hideProgressBar(); return;
                        }
                        const recipients = recipientsString.split(',').map(email => email.trim()).filter(email => email);
                        if (recipients.length === 0) {
                            alert('Format email tidak valid.');
                            setLoadingState(sendEmailBtn, false); hideProgressBar(); return;
                        }

                        const response = await fetch("{{ route('api.notification.sendBom') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                recipients: recipients,
                                results: finalBomUploadResults,
                                plant: processedPlant
                            })
                        });

                        // [FIX 419] Menangani session timeout
                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();
                        showResult(emailResultDiv, response.ok, result.message, null, true); // Tampilkan sbg summary box
                        if (response.ok) {
                            emailNotificationArea.classList.add('d-none');
                            sendEmailBtn.classList.add('d-none');
                        }
                    } catch (error) {
                        showResult(emailResultDiv, false, 'Network error while sending email.', null, true);
                    } finally {
                        setLoadingState(sendEmailBtn, false);
                        hideProgressBar();
                    }
                }

                // Fungsi helper
                function ltrim(str, chars) {
                    chars = chars || "\\s";
                    return str.replace(new RegExp("^[" + chars + "]+", "g"), "");
                }
            }
        });
    </script>

    <audio id="upload-music" loop>
        <source src="{{ asset('audio/Mozart_Allegro.mp3') }}" type="audio/mpeg">
        Browser Anda tidak mendukung elemen audio.
    </audio>

</body>
</html>
