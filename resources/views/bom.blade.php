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
    </style>
</head>
<body>
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
                            <input type="file" name="file" id="file-input" required class="d-none" accept=".xls,.xlsx,.csv">
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
                        <i class="bi bi-cloud-upload"></i> Confirm & Upload
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let finalBomUploadResults = [];
            let finalGeneratedCodeResults = [];
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
             * Menampilkan hasil. Bisa append=true untuk menambah hasil (live progress).
             */
            function showResult(div, isSuccess, message, details = null, append = false) {
                if (!div) return;
                const alertClass = isSuccess ? 'alert-success-frosted' : 'alert-danger';
                let html = '';

                // Jika tidak append, tambahkan header message
                if (!append || div.innerHTML === '') {
                    html = `<div class="alert ${alertClass}">${message || (isSuccess ? 'Process successful.' : 'An error occurred.')}</div>`;
                }

                // Siapkan detail list
                let detailHtml = '';
                if (details && Array.isArray(details)) {
                    detailHtml += '<div class="upload-details"><ul>';
                    details.forEach(item => {
                        // [PERBAIKAN] Cek 'item.message' untuk hasil Upload
                        // Cek 'item.code' untuk hasil Generate Code
                        if (item.message !== undefined) {
                            // Format untuk hasil UPLOAD (Success/Failed)
                            const icon = item.status === 'Success' ? '✅' : '❌';
                            let originalMessage = item.message;
                            if (item.status === 'Success' && originalMessage.includes('berhasil dibuat untuk material')) {
                                originalMessage = originalMessage.split(' untuk material')[0];
                            }
                            const cleanMessage = originalMessage.replace(/^✅\s*|^\s*/, '').replace(/0*(\d+)/g, "$1");
                            detailHtml += `<li>${icon} ${cleanMessage}</li>`;

                        } else if (item.code !== undefined) {
                            // Format untuk hasil GENERATE CODE (deskripsi + kode)
                            const icon = (item.code !== '#NOT_FOUND#' && item.code !== '#ERROR#') ? '✅' : '❌';
                            const cleanCode = (item.code.match(/^[a-zA-Z]/) || item.code.startsWith('#')) ? item.code : parseInt(item.code, 10);
                            detailHtml += `<li>${icon} <strong>${item.description}:</strong> ${cleanCode}</li>`;
                        }
                    });
                    detailHtml += '</ul></div>';
                }

                // Gabungkan
                if (append) {
                    // Jika append, cari <ul> yang ada atau buat baru
                    let ulContainer = div.querySelector('.upload-details ul');
                    if (!ulContainer) {
                        // Jika ini item pertama, tambahkan wrapper detail
                        div.innerHTML += '<div class="upload-details"><ul></ul></div>';
                        ulContainer = div.querySelector('.upload-details ul');
                    }
                    // Tambahkan hanya list item baru
                    if (details && Array.isArray(details)) {
                         details.forEach(item => {
                            if (item.message !== undefined) {
                                // Format untuk hasil UPLOAD (Success/Failed)
                                const icon = item.status === 'Success' ? '✅' : '❌';
                                let originalMessage = item.message;
                                if (item.status === 'Success' && originalMessage.includes('berhasil dibuat untuk material')) {
                                    originalMessage = originalMessage.split(' untuk material')[0];
                                }
                                const cleanMessage = originalMessage.replace(/^✅\s*|^\s*/, '').replace(/0*(\d+)/g, "$1");
                                ulContainer.innerHTML += `<li>${icon} ${cleanMessage}</li>`;

                            } else if (item.code !== undefined) {
                                // Format untuk hasil GENERATE CODE (deskripsi + kode)
                                const icon = (item.code !== '#NOT_FOUND#' && item.code !== '#ERROR#') ? '✅' : '❌';
                                const cleanCode = (item.code.match(/^[a-zA-Z]/) || item.code.startsWith('#')) ? item.code : parseInt(item.code, 10);
                                ulContainer.innerHTML += `<li>${icon} <strong>${item.description}:</strong> ${cleanCode}</li>`;
                            }
                         });
                    }
                } else {
                    // Tulis ulang HTML
                    html += detailHtml;
                    div.innerHTML = html;
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

                generateBtn.addEventListener('click', handleGenerateCodes);
                uploadBomBtn.addEventListener('click', () => sapLoginModal.show());
                confirmBtn.addEventListener('click', () => {
                    sapLoginModal.hide();
                    handleBomUpload();
                });
                sendEmailBtn.addEventListener('click', handleSendBomEmail);

                /**
                 * [FUNGSI DIPERBARUI]
                 * Menangani proses generate codes dengan live progress.
                 */
                async function handleGenerateCodes() {
                    setLoadingState(generateBtn, true);
                    resultDiv.innerHTML = ''; // Bersihkan hasil sebelumnya
                    finalGeneratedCodeResults = []; // Reset hasil

                    try {
                        // Langkah 1: Ambil daftar deskripsi yang perlu dicek
                        showProgressBar('Mengambil daftar material...');
                        const listResponse = await fetch("{{ route('api.bom.generate_codes') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({ filename: generateBtn.dataset.filename })
                        });

                        if (!listResponse.ok) {
                            if (listResponse.status === 419) window.location.reload();
                            throw new Error('Gagal mengambil daftar material dari server.');
                        }

                        const listResult = await listResponse.json();
                        if (listResult.status !== 'success' || !listResult.descriptions_to_check) {
                            throw new Error(listResult.message || 'Server mengembalikan data yang tidak valid.');
                        }

                        const descriptions = listResult.descriptions_to_check;
                        const total = descriptions.length;
                        if (total === 0) {
                            showResult(resultDiv, true, "Tidak ada material yang memerlukan pencarian kode. Semua sudah lengkap.", null);
                            generateBtn.classList.add('d-none');
                            uploadBomBtn.classList.remove('d-none');
                            downloadBtn.classList.remove('d-none');
                            downloadRoutingBtn.classList.remove('d-none');
                            return;
                        }

                        // Langkah 2: Loop dan cari satu per satu
                        for (let i = 0; i < total; i++) {
                            const desc = descriptions[i];
                            const shortDesc = desc.length > 40 ? desc.substring(0, 37) + '...' : desc;
                            showProgressBar(`Mencari Kode ${i + 1}/${total}: '${shortDesc}'`);

                            try {
                                const findResponse = await fetch("{{ route('api.bom.find_single_code') }}", {
                                    method: 'POST',
                                    headers: getHeaders(),
                                    body: JSON.stringify({ description: desc })
                                });

                                const findResult = await findResponse.json();
                                finalGeneratedCodeResults.push(findResult); // Simpan hasil

                                // Tampilkan hasil langsung (mode append)
                                showResult(resultDiv, true, "Mencari kode...", [findResult], true);

                            } catch (loopError) {
                                // Tangani error per item (misal network error di tengah loop)
                                const errorResult = { status: 'error', description: desc, code: '#ERROR#' };
                                finalGeneratedCodeResults.push(errorResult);
                                showResult(resultDiv, true, "Mencari kode...", [errorResult], true);
                            }
                        }

                        // Langkah 3: Simpan semua hasil yang ditemukan ke file JSON di server
                        showProgressBar('Menyimpan semua kode yang ditemukan...');
                        const saveResponse = await fetch("{{ route('api.bom.save_codes') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                filename: generateBtn.dataset.filename,
                                results: finalGeneratedCodeResults
                            })
                        });

                        if (!saveResponse.ok) {
                            throw new Error('Gagal menyimpan hasil kode di server.');
                        }

                        const saveResult = await saveResponse.json();
                        if (saveResult.status !== 'success') {
                            throw new Error(saveResult.message || 'Gagal menyimpan hasil kode.');
                        }

                        // Selesai - Tampilkan pesan final
                        hideProgressBar();
                        showResult(resultDiv, true, saveResult.message, saveResult.results, false); // Tulis ulang hasil akhir

                        // Tampilkan tombol berikutnya
                        generateBtn.classList.add('d-none');
                        const hasNotFound = finalGeneratedCodeResults.some(item => item.code === '#NOT_FOUND#' || item.code === '#ERROR#');
                        if (hasNotFound) {
                            uploadBomBtn.disabled = true;
                            uploadBomBtn.setAttribute('title', 'Upload dinonaktifkan karena ada kode material yang tidak ditemukan/error.');
                            uploadBomBtn.classList.remove('btn-primary');
                            uploadBomBtn.classList.add('btn-secondary');
                        } else {
                            uploadBomBtn.disabled = false;
                            uploadBomBtn.removeAttribute('title');
                            uploadBomBtn.classList.remove('btn-secondary');
                            uploadBomBtn.classList.add('btn-primary');
                        }
                        uploadBomBtn.classList.remove('d-none');
                        downloadBtn.classList.remove('d-none');
                        downloadRoutingBtn.classList.remove('d-none');

                    } catch (error) {
                        console.error('Error during code generation:', error);
                        showResult(resultDiv, false, error.message || 'Network error during code generation.');
                    } finally {
                        setLoadingState(generateBtn, false);
                        hideProgressBar();
                    }
                }


                /**
                 * [FUNGSI DIPERBARUI]
                 * Menangani proses upload BOM dengan live progress.
                 */
                async function handleBomUpload() {
                    setLoadingState(uploadBomBtn, true);
                    resultDiv.innerHTML = '';
                    finalBomUploadResults = []; // Reset hasil
                    if (uploadMusic) uploadMusic.play();

                    try {
                        // Langkah 1: Ambil daftar BOM yang akan di-upload
                        showProgressBar('Mengambil daftar BOM untuk di-upload...');
                        const listResponse = await fetch("{{ route('api.bom.upload') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: getAuthBody({ filename: uploadBomBtn.dataset.filename })
                        });

                        if (!listResponse.ok) {
                             if (listResponse.status === 419) window.location.reload();
                             const errorResult = await listResponse.json(); // Coba ambil pesan error
                             throw new Error(errorResult.message || 'Gagal mengambil daftar BOM dari server.');
                        }

                        const listResult = await listResponse.json();
                        if (listResult.status !== 'success') {
                            throw new Error(listResult.message || 'Server mengembalikan data yang tidak valid.');
                        }

                        const bomsToUpload = listResult.boms_to_upload;
                        const descriptionMap = listResult.description_map;
                        const total = bomsToUpload.length;

                        if (total === 0) {
                            showResult(resultDiv, true, "Tidak ada BOM valid yang perlu di-upload.", null);
                            return;
                        }

                        // Langkah 2: Loop dan upload satu per satu
                        for (let i = 0; i < total; i++) {
                            const bom = bomsToUpload[i];
                            const materialCode = ltrim(bom.IV_MATNR, '0');
                            const description = descriptionMap[materialCode] || bom.IV_STKTX;

                            showProgressBar(`Proses BOM ${i + 1}/${total}: '${materialCode}'`);

                            try {
                                const uploadResponse = await fetch("{{ route('api.bom.upload_single') }}", {
                                    method: 'POST',
                                    headers: getHeaders(),
                                    body: getAuthBody({ bom: bom })
                                });

                                const uploadResult = await uploadResponse.json();
                                const results = enrichResults(uploadResult.results || [], descriptionMap);
                                finalBomUploadResults.push(...results);

                                // Tampilkan hasil langsung (mode append)
                                showResult(resultDiv, true, "Mengupload BOM...", results, true);

                            } catch (loopError) {
                                // Tangani error per item (misal network error di tengah loop)
                                const errorResult = [{
                                    status: 'Failed',
                                    material_code: bom.IV_MATNR,
                                    message: `Network Error: ${loopError.message}`
                                }];
                                finalBomUploadResults.push(...errorResult);
                                showResult(resultDiv, true, "Mengupload BOM...", errorResult, true);
                            }
                        }

                        // Langkah 3: Selesai
                        hideProgressBar();
                        showResult(resultDiv, true, `Proses upload selesai. ${total}/${total} BOM diproses.`, finalBomUploadResults, false); // Tulis ulang hasil akhir

                        // Tampilkan tombol email jika ada sukses
                        const successfulUploads = finalBomUploadResults.filter(r => r.status === 'Success');
                        if (successfulUploads.length > 0) {
                            emailNotificationArea.classList.remove('d-none');
                            sendEmailBtn.classList.remove('d-none');
                        }

                        // Sembunyikan tombol upload jika tidak ada error
                        const hasFailures = finalBomUploadResults.some(r => r.status === 'Failed');
                        if (!hasFailures) {
                            uploadBomBtn.classList.add('d-none');
                        }

                        // Auto-download
                        if (downloadBtn) downloadBtn.click();
                        setTimeout(() => {
                            if (downloadRoutingBtn) downloadRoutingBtn.click();
                        }, 1000);

                    } catch (error) {
                        console.error('Error during BOM upload:', error);
                        showResult(resultDiv, false, error.message || 'Network Error during BOM upload.');
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

                        if (response.status === 419) {
                            alert('Sesi Anda telah berakhir. Halaman akan dimuat ulang untuk keamanan.');
                            window.location.reload();
                            return;
                        }

                        const result = await response.json();
                        showResult(emailResultDiv, response.ok, result.message);
                        if (response.ok) {
                            emailNotificationArea.classList.add('d-none');
                            sendEmailBtn.classList.add('d-none');
                        }
                    } catch (error) {
                        showResult(emailResultDiv, false, 'Network error while sending email.');
                    } finally {
                        setLoadingState(sendEmailBtn, false);
                        hideProgressBar();
                    }
                }

                // --- FUNGSI HELPER ---
                function ltrim(str, char = '0') {
                    if (!str) return '';
                    let start = 0;
                    while (start < str.length && str[start] === char) {
                        start++;
                    }
                    return str.substring(start);
                }

                function enrichResults(results, descriptionMap) {
                    if (!results) return [];
                    return results.map(result => {
                        const matCode = ltrim(result.material_code, '0');
                        result['description'] = descriptionMap[matCode] || 'N/A';
                        return result;
                    });
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

