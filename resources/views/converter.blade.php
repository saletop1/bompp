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
        body {
            background-image: url("{{ asset('images/ainun.jpg') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        .card {
            background: rgba(75, 74, 74, 0.33);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
        }
        .converter-container { max-width: 800px; margin-top: 0; margin-bottom: 1rem; }
        #drop-zone { border: 2px dashed rgba(255, 255, 255, 0.6); border-radius: 0.5rem; color: #343a40; transition: all 0.3s ease-in-out; background-color: rgba(255, 255, 255, 0.2); }
        #drop-zone.dragover { border-color: #0d6efd; background-color: rgba(21, 62, 124, 0.21); }
        .form-label { font-weight: 600; color: #FFFFFF; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); }
        .form-control, .form-select { background-color: rgba(255, 255, 255, 0.5); border: 1px solid rgba(0, 0, 0, 0.1); }
        .form-control:focus, .form-select:focus { background-color: rgba(255, 255, 255, 0.8); border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
        .btn-primary { background-color: #0d6efd; border-color: #0d6efd; }
        .btn .spinner-border { width: 1rem; height: 1rem; }
        .page-title-container { text-align: center; }
        .page-title-container .main-title { color: #ffffffff; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); margin-bottom: 0; }
        .page-title-container .subtitle { color: #d7d7d7ff; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); font-size: 0.9rem; }
        .sap-logo-header { height: 100px; width: auto; margin-left: 20px; }
        .download-area-header { display: flex; justify-content: center; align-items: center; text-align: left; }
        .download-area-header dotlottie-player { flex-shrink: 0; }
        .download-area-title { color: #d1d5db; font-weight: bold; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6); }
        .download-area-text { color: #d1d5db; font-size: 1.1rem; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); }
        .alert.alert-success-frosted { background: rgba(25, 135, 126, 0.4); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .alert.alert-danger { background: rgba(220, 53, 69, 0.4); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .upload-details { max-height: 200px; overflow-y: auto; text-align: left; font-size: 0.85rem; background-color: rgba(0,0,0,0.1); padding: 10px; border-radius: 5px; margin-top: 15px; }
        .upload-details ul li { color: #e2e0e0ff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .nav-pills .nav-link { background-color: rgba(255, 255, 255, 0); color: #f0f0f0; margin: 0 5px; transition: all 0.3s ease; border: 1px solid transparent; }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link { background-color: #ffffff1c; color: #a6ff00ff; font-weight: bold; box-shadow: 0 8px 15px rgba(0, 0, 0, 0.76); }
        .nav-pills .nav-link:hover:not(.active) { background-color: rgba(255, 255, 255, 0.4); color: #ffffff; border-color: rgba(255,255,255,0.5); }
        .modal-content.frosted-glass { background: rgba(30, 30, 30, 0.4); backdrop-filter: blur(7px); -webkit-backdrop-filter: blur(7px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); border-radius: 0.75rem; color: #ffffff; }
        .input-group-underline { position: relative; }
        .input-underline { background-color: transparent !important; border: none !important; border-bottom: 2px solid rgba(255, 255, 255, 0.4) !important; border-radius: 0 !important; padding-left: 35px !important; color: #ffffff !important; transition: border-color 0.3s ease; }
        .input-underline::placeholder { color: rgba(7, 226, 255, 0.8); opacity: 1; }
        .input-underline:focus { box-shadow: none !important; border-bottom-color: #05e6ffef !important; }
        .input-group-underline .bi { position: absolute; left: 5px; top: 50%; transform: translateY(-50%); color: rgba(255, 255, 255, 0.6); font-size: 1.1rem; }
        .frosted-glass .modal-header, .frosted-glass .modal-footer { border-bottom: none; border-top: none; }
        .frosted-glass .modal-title { font-weight: 300; }
        #confirmationModal .modal-body { max-height: 40vh; overflow-y: auto; }
        #process-another-btn { background-color: #198754 !important; border-color: #198754 !important; color: white !important; }
        #process-another-btn:hover { background-color: #157347 !important; border-color: #146c43 !important; }
        #progress-text { color: #e9ecef; text-shadow: 1px 1px 2px rgba(0,0,0,0.7); font-weight: 500; margin-top: -20px; }
    </style>
</head>
<body>
    <div class="container converter-container">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-center mb-3">
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

                @if (session('download_filename'))
                {{-- TAMPILAN AREA HASIL --}}
                <div id="download-area">
                    <div class="download-area-header">
                        <div class="ms-3">
                            <h4 class="download-area-title mb-0">Processing Complete!</h4>
                            <p class="download-area-text mt-0">{{ session('success') }}</p>
                        </div>
                    </div>

                    <div id="progress-container" class="text-center mt-4 d-none">
                        <dotlottie-player src="{{ asset('animations/buble.lottie') }}" background="transparent" speed="1" style="width: 200px; height: 200px; margin: 0 auto;" loop autoplay></dotlottie-player>
                        <p id="progress-text" class="text-center">Processing...</p>
                    </div>

                    <div id="upload-result" class="mt-3"></div>
                    {{-- START: DIV BARU UNTUK HASIL INSPECTION PLAN --}}
                    <div id="inspection-plan-result" class="mt-3"></div>
                    {{-- END: DIV BARU UNTUK HASIL INSPECTION PLAN --}}
                    <div id="email-result" class="mt-3"></div>

                    <div id="email-notification-area" class="mt-4 d-none">
                        <div class="input-group mb-3 mx-auto" style="max-width: 450px;">
                            <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" id="email-recipient" class="form-control" placeholder="Enter recipient email for notification...">
                        </div>
                    </div>

                    <div id="action-buttons" class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <button type="button" id="upload-sap-btn" class="btn btn-primary btn-lg px-4 gap-3" data-filename="{{ session('download_filename') }}">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-cloud-upload"></i> Upload to SAP
                        </button>

                        <button type="button" id="send-email-btn" class="btn btn-danger btn-lg px-4 gap-3 d-none">
                            <span class="spinner-border spinner-border-sm d-none"></span>
                            <i class="bi bi-envelope-at-fill"></i> Send Notification
                        </button>

                        <a href="{{ route('converter.download', ['filename' => session('download_filename')]) }}" id="download-only-btn" class="btn btn-outline-light btn-lg px-4">
                            <i class="bi bi-download"></i> Download Only
                        </a>
                        <a href="{{ route('converter.index') }}" id="process-another-btn" class="btn btn-secondary btn-lg px-4"><i class="bi bi-arrow-repeat"></i> Process Another</a>
                    </div>
                </div>
                @else
                {{-- FORM UPLOAD AWAL --}}
                <form action="{{ route('converter.upload') }}" method="post" enctype="multipart/form-data" id="upload-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="material_type" class="form-label">Material Type</label>
                            <select name="material_type" id="material_type" class="form-select" required>
                                <option value="" selected disabled>Pilih Material Type...</option>
                                <option value="FERT">FERT Finish Good</option>
                                <option value="HALB">HALB Semi Finish</option>
                                <option value="HALM">HALM Semi Finish MTL</option>
                                <option value="VERP">VERP Packaging</option>
                            </select>
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
                                <button type="button" class="btn btn-dark" id="generate-btn">
                                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                                    <i class="bi bi-stars"></i> Generate
                                </button>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="plant" class="form-label">Plant</label>
                            <select name="plant" id="plant" class="form-select" required>
                                <option value="" selected disabled>Pilih Plant...</option>
                                <option value="3000">3000</option>
                                <option value="2000">2000</option>
                                <option value="1000">1000</option>
                            </select>
                        </div>
                        <div class="col-12 mt-4">
                            <input type="file" name="file" id="file-input" accept=".xls,.xlsx,.csv" required class="d-none">
                            <div id="drop-zone" class="p-4 text-center" style="cursor: pointer;">
                                <dotlottie-player src="{{ asset('animations/Greenish arrow down.lottie') }}" background="transparent" speed="1" style="width: 150px; height: 150px; margin: 0 auto;" loop autoplay></dotlottie-player>
                                <p class="mb-0 mt-2 fw-bold" id="main-text">Drag & drop your Excel file here</p>
                                <p class="text-muted small" id="file-info-text">or click to browse</p>
                            </div>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" id="submit-button" class="btn btn-primary btn-lg d-none">
                                <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                                <i class="bi bi-gear-fill"></i>
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

    <!-- Modal Login SAP -->
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
                    <button type="button" class="btn btn-primary" id="confirm-action-btn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Upload & Aktivasi QM -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content frosted-glass">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Upload & Activate QM</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmation-modal-body">
                    <!-- Konten akan diisi oleh JavaScript -->
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- VARIABEL GLOBAL ---
            let stagedMaterials = [];
            let finalUploadResults = [];
            let sapUsername = '';
            let sapPassword = '';

            // --- LOGIKA FORM AWAL ---
            const form = document.getElementById('upload-form');
            if (form) {
                // ... (Kode form awal tidak berubah, biarkan seperti adanya) ...
                const materialTypeSelect = document.getElementById('material_type');
                const fertOptionsContainer = document.getElementById('fert-options');
                const divisionSelect = document.getElementById('division');
                const channelSelect = document.getElementById('distribution_channel');
                const dropZone = document.getElementById('drop-zone');
                const generateBtn = document.getElementById('generate-btn');
                const fileInput = document.getElementById('file-input');
                const submitButton = document.getElementById('submit-button');
                const fileInfoText = document.getElementById('file-info-text');
                const mainText = document.getElementById('main-text');

                materialTypeSelect.addEventListener('change', function () {
                    const isFert = this.value === 'FERT';
                    fertOptionsContainer.classList.toggle('d-none', !isFert);
                    divisionSelect.required = isFert;
                    channelSelect.required = isFert;
                });

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
                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length > 0) handleFile(fileInput.files[0]);
                });

                function handleFile(file) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    fileInput.files = dataTransfer.files;
                    mainText.textContent = "File Selected:";
                    fileInfoText.textContent = file.name;
                    submitButton.classList.remove('d-none');
                }

                generateBtn.addEventListener('click', async () => {
                    const materialCodeInput = document.getElementById('start_material_code');
                    const spinner = generateBtn.querySelector('.spinner-border');
                    const btnIcon = generateBtn.querySelector('.bi-stars');
                    const selectedType = materialTypeSelect.value;
                    if (!selectedType) { alert('Please select a Material Type first.'); return; }

                    spinner.classList.remove('d-none');
                    btnIcon.classList.add('d-none');
                    generateBtn.disabled = true;

                    try {
                        const response = await fetch(`{{ route('api.material.generate') }}?material_type=${selectedType}`);
                        const data = await response.json();
                        if (response.ok) {
                            materialCodeInput.value = data.next_material_code;
                        } else {
                            alert(`Error: ${data.error || 'Failed to retrieve data from server.'}`);
                        }
                    } catch (error) {
                        alert('Network error. Please ensure the SAP service (Python) is running.');
                    } finally {
                        spinner.classList.add('d-none');
                        btnIcon.classList.remove('d-none');
                        generateBtn.disabled = false;
                    }
                });

                submitButton.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (form.checkValidity()) {
                        const spinner = submitButton.querySelector('.spinner-border');
                        const text = document.getElementById('submit-button-text');
                        spinner.classList.remove('d-none');
                        text.textContent = 'Processing...';
                        submitButton.disabled = true;
                        form.submit();
                    } else {
                        form.reportValidity();
                    }
                });
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

                // Langkah 1: Tombol "Upload to SAP" diklik, buka modal login.
                if(uploadSapBtn) uploadSapBtn.addEventListener('click', () => sapLoginModal.show());

                // Langkah 2: Tombol "Confirm" di modal login diklik, jalankan proses staging.
                if(confirmLoginBtn) confirmLoginBtn.addEventListener('click', handleStaging);

                // Langkah 4: Tombol "Confirm & Activate" di modal konfirmasi diklik, jalankan aktivasi final.
                if(confirmActivateBtn) confirmActivateBtn.addEventListener('click', handleFinalActivation);

                if(sendEmailBtn) sendEmailBtn.addEventListener('click', handleSendEmail);

                // Fungsi untuk proses Staging (persiapan data)
                async function handleStaging() {
                    sapUsername = document.getElementById('sap-username').value;
                    sapPassword = document.getElementById('sap-password').value;
                    if (!sapUsername || !sapPassword) {
                        alert('Username and Password are required.');
                        return;
                    }
                    sapLoginModal.hide();
                    setLoadingState(uploadSapBtn, true, 'Staging...');
                    showProgressBar('Preparing data for SAP...');

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
                        const result = await response.json();
                        console.log("Server Response for Staging:", result);
                        if (response.ok && result.status === 'staged') {
                            stagedMaterials = result.results;
                            showConfirmationModal(stagedMaterials);
                        } else {
                            let errorMessage = result.message || 'Staging process failed.';
                            if (result.status !== 'staged') {
                                errorMessage += ` (Expected status 'staged', but got '${result.status || 'undefined'}'). Please check the API response.`;
                            }
                            showResult(uploadResultDiv, false, errorMessage);
                        }

                    } catch (error) {
                        console.error("Fetch Error:", error);
                        showResult(uploadResultDiv, false, 'Network Error during staging. Check the browser console for details.');
                    } finally {
                        setLoadingState(uploadSapBtn, false);
                        hideProgressBar();
                    }
                }

                // Fungsi untuk menampilkan modal konfirmasi dengan daftar material
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

                // Fungsi untuk proses aktivasi final
                async function handleFinalActivation() {
                    confirmationModal.hide();
                    setLoadingState(confirmActivateBtn, true);
                    uploadResultDiv.innerHTML = '';
                    inspectionPlanResultDiv.innerHTML = ''; // Kosongkan hasil sebelumnya
                    showProgressBar('Uploading materials and activating QM...');

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
                        const result = await response.json();
                        showResult(uploadResultDiv, response.ok && result.status === 'success', result.message, result.results);

                        if (response.ok && result.status === 'success') {
                            finalUploadResults = result.results;
                            const successfulMaterials = finalUploadResults.filter(r => r.status === 'Success');

                            // --- START: LOGIKA BARU UNTUK MEMICU INSPECTION PLAN ---
                            if (successfulMaterials.length > 0) {
                                // Panggil fungsi untuk membuat inspection plan
                                await handleInspectionPlanCreation(successfulMaterials, sapUsername, sapPassword);

                                if(uploadSapBtn) uploadSapBtn.classList.add('d-none');
                                if(downloadOnlyBtn) downloadOnlyBtn.classList.add('d-none');
                                if(emailNotificationArea) emailNotificationArea.classList.remove('d-none');
                                if(sendEmailBtn) sendEmailBtn.classList.remove('d-none');
                            }
                            // --- END: LOGIKA BARU ---
                        }

                    } catch (error) {
                        showResult(uploadResultDiv, false, 'Network Error during final activation.');
                    } finally {
                        setLoadingState(confirmActivateBtn, false);
                        hideProgressBar();
                    }
                }

                // --- START: FUNGSI BARU UNTUK MEMBUAT INSPECTION PLAN ---
                async function handleInspectionPlanCreation(successfulMaterials, username, password) {
                    showProgressBar('Creating Inspection Task Lists...');
                    try {
                        // Definisikan detail statis untuk inspection plan di sini
                        const planDetails = {
                            task_usage: '5', // Goods receipt
                            task_status: '4', // Released (general)
                            control_key: 'QM01', // QM in procurement is active
                            inspchar: 'THICKNESS' // Master inspection characteristic
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

                        const result = await response.json();
                        // Tampilkan hasil di div yang terpisah
                        showResult(inspectionPlanResultDiv, response.ok && result.status === 'success', result.message, result.results);

                    } catch (error) {
                        showResult(inspectionPlanResultDiv, false, 'Network Error during Inspection Plan creation.');
                    } finally {
                        // Sembunyikan progress bar setelah semuanya selesai
                        hideProgressBar();
                    }
                }
                // --- END: FUNGSI BARU UNTUK MEMBUAT INSPECTION PLAN ---

                async function handleSendEmail() {
                    const recipient = emailRecipientInput.value;
                    if (!recipient) {
                        alert('Please enter a recipient email address.');
                        return;
                    }
                    if (!finalUploadResults || finalUploadResults.length === 0) {
                        alert('There are no results to send.');
                        return;
                    }

                    setLoadingState(sendEmailBtn, true);
                    if(emailResultDiv) emailResultDiv.innerHTML = '';
                    showProgressBar('Sending email notification...');

                    try {
                        const response = await fetch("{{ route('api.notification.send') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: JSON.stringify({
                                recipient: recipient,
                                results: finalUploadResults
                            })
                        });

                        const result = await response.json();
                        if(response.ok) {
                            showResult(emailResultDiv, true, result.message || 'Email notification sent successfully!');
                            if(emailNotificationArea) emailNotificationArea.classList.add('d-none');
                            if(sendEmailBtn) sendEmailBtn.classList.add('d-none');
                        } else {
                            let errorMessage = `Failed to send email: ${result.message || 'Unknown server error'}. <br><small>Please check the mail configuration in your application's <code>.env</code> file.</small>`;
                            showResult(emailResultDiv, false, errorMessage);
                        }
                    } catch (error) {
                        let networkErrorMessage = 'Network error while sending email. Please ensure the application server is running and check the browser console for more details.';
                        showResult(emailResultDiv, false, networkErrorMessage);
                    } finally {
                        setLoadingState(sendEmailBtn, false);
                        hideProgressBar();
                    }
                }

                function showProgressBar(text) {
                    const progressText = document.getElementById('progress-text');
                    if (progressContainer && progressText) {
                        progressText.textContent = text;
                        progressContainer.classList.remove('d-none');
                    }
                }

                function hideProgressBar() {
                    if (progressContainer) {
                        progressContainer.classList.add('d-none');
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
