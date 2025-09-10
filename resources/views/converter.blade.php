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

        /* Style untuk Hasil Proses */
        .download-area-header { display: flex; justify-content: center; align-items: center; text-align: left; }
        .download-area-header dotlottie-player { flex-shrink: 0; }
        .download-area-title { color: #d1d5db; font-weight: bold; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6); }
        .download-area-text { color: #d1d5db; font-size: 1.1rem; text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5); }
        .alert.alert-success-frosted { background: rgba(25, 135, 126, 0.4); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .alert.alert-danger { background: rgba(220, 53, 69, 0.4); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .upload-details { max-height: 200px; overflow-y: auto; text-align: left; font-size: 0.85rem; background-color: rgba(0,0,0,0.1); padding: 10px; border-radius: 5px; margin-top: 15px; }
        .upload-details ul li { color: #e2e0e0ff; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }

        /* Style Navigasi */
        .nav-pills .nav-link { background-color: rgba(255, 255, 255, 0.2); color: #f0f0f0; margin: 0 5px; transition: all 0.3s ease; border: 1px solid transparent; }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link { background-color: #ffffff64; color: #08e6ffd8; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .nav-pills .nav-link:hover:not(.active) { background-color: rgba(255, 255, 255, 0.4); color: #ffffff; border-color: rgba(255,255,255,0.5); }

        /* Style Modal */
        .modal-content.frosted-glass { background: rgba(30, 30, 30, 0.4); backdrop-filter: blur(7px); -webkit-backdrop-filter: blur(7px); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3); border-radius: 0.75rem; color: #ffffff; }
        .input-group-underline { position: relative; }
        .input-underline { background-color: transparent !important; border: none !important; border-bottom: 2px solid rgba(255, 255, 255, 0.4) !important; border-radius: 0 !important; padding-left: 35px !important; color: #ffffff !important; transition: border-color 0.3s ease; }
        .input-underline::placeholder { color: rgba(7, 226, 255, 0.8); opacity: 1; }
        .input-underline:focus { box-shadow: none !important; border-bottom-color: #05e6ffef !important; }
        .input-group-underline .bi { position: absolute; left: 5px; top: 50%; transform: translateY(-50%); color: rgba(255, 255, 255, 0.6); font-size: 1.1rem; }
        .frosted-glass .modal-header, .frosted-glass .modal-footer { border-bottom: none; border-top: none; }
        .frosted-glass .modal-title { font-weight: 300; }

        /* Style Tombol Process Another */
        #process-another-btn { background-color: #198754 !important; border-color: #198754 !important; color: white !important; }
        #process-another-btn:hover { background-color: #157347 !important; border-color: #146c43 !important; }
    </style>
</head>
<body>
    <div class="container converter-container">
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Material Master Converter</h1>
                <p class="subtitle mb-0">Automate your apps to SAP data conversion</p>
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
                                <i class="bi bi-box-seam me-2"></i>Material Converter
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}" href="{{ route('bom.index') }}">
                                <i class="bi bi-diagram-3 me-2"></i>BOM Uploader
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
                        {{-- <dotlottie-player src="https://lottie.host/88a2a823-34e1-433b-8711-9a4f8eee42d5/C9kQj42a49.json" background="transparent" speed="1" style="width: 120px; height: 120px;" autoplay></dotlottie-player> --}}
                        <div class="ms-3">
                            <h4 class="download-area-title mb-0">Processing Complete!</h4>
                            <p class="download-area-text mt-0">{{ session('success') }}</p>
                        </div>
                    </div>

                    <div id="upload-result" class="mt-3"></div>
                    <div id="qm-result" class="mt-3"></div>
                    <div id="email-notification-area" class="mt-4 d-none">
                    <div class="input-group mb-3 mx-auto" style="max-width: 400px;">
                        <span class="input-group-text"><i class="bi bi-envelope-at"></i></span>
                        <input type="email" id="email-recipient" class="form-control" placeholder="Masukkan email penerima...">
                    </div>
                    </div>

                    <div id="action-buttons" class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                        <button type="button" id="upload-sap-btn" class="btn btn-primary btn-lg px-4 gap-3" data-filename="{{ session('download_filename') }}">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <i class="bi bi-cloud-upload"></i> Upload to SAP
                        </button>

                        <button type="button" id="activate-qm-btn" class="btn btn-warning btn-lg px-4 gap-3 d-none">
                            <span class="spinner-border spinner-border-sm d-none"></span>
                            <i class="bi bi-shield-check"></i> Activate QM
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
                        {{-- Teks dan ikon akan diubah oleh JavaScript --}}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // --- LOGIKA FORM AWAL ---
            const form = document.getElementById('upload-form');
            if (form) {
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
                    fertOptionsContainer.classList.toggle('row', isFert);
                    fertOptionsContainer.classList.toggle('g-3', isFert);
                    fertOptionsContainer.classList.toggle('mb-3', isFert);
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
                    if (event.dataTransfer.files.length > 0) {
                        handleFile(event.dataTransfer.files[0]);
                    }
                }, false);

                dropZone.addEventListener('click', () => fileInput.click());

                fileInput.addEventListener('change', () => {
                    if (fileInput.files.length > 0) {
                        handleFile(fileInput.files[0]);
                    }
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

            // --- LOGIKA HALAMAN HASIL ---
            const downloadArea = document.getElementById('download-area');
            if (downloadArea) {
                const uploadSapBtn = document.getElementById('upload-sap-btn');
                const activateQmBtn = document.getElementById('activate-qm-btn');
                const sendEmailBtn = document.getElementById('send-email-btn'); // <-- BARU
                const emailNotificationArea = document.getElementById('email-notification-area'); // <-- BARU
                const emailRecipientInput = document.getElementById('email-recipient'); // <-- BARU
                const downloadOnlyBtn = document.getElementById('download-only-btn');
                const sapLoginModal = new bootstrap.Modal(document.getElementById('sapLoginModal'));
                const confirmBtn = document.getElementById('confirm-action-btn');
                const modalTitle = document.getElementById('sapLoginModalLabel');
                const uploadResultDiv = document.getElementById('upload-result');
                const qmResultDiv = document.getElementById('qm-result');

                let currentAction = '';

                uploadSapBtn.addEventListener('click', () => {
                    currentAction = 'upload';
                    modalTitle.textContent = 'SAP Authentication for Upload';
                    confirmBtn.innerHTML = '<i class="bi bi-cloud-upload"></i> Confirm & Upload';
                    sapLoginModal.show();
                });

                activateQmBtn.addEventListener('click', () => {
                    currentAction = 'qm';
                    modalTitle.textContent = 'SAP Authentication for QM Activation';
                    confirmBtn.innerHTML = '<i class="bi bi-shield-check"></i> Confirm & Activate';
                    sapLoginModal.show();
                });

                // --- EVENT LISTENER BARU UNTUK TOMBOL EMAIL ---
                sendEmailBtn.addEventListener('click', async () => {
                    if (!emailRecipientInput.value) {
                    alert('Please enter a recipient email address.');
                    return;
                    }
                await handleSendEmail();
                });

                confirmBtn.addEventListener('click', async () => {
                    sapLoginModal.hide();
                    if (currentAction === 'upload') {
                        await handleUpload();
                    } else if (currentAction === 'qm') {
                        await handleQmActivation();
                    }
                });

                async function handleUpload() {
                    setLoadingState(uploadSapBtn, true);
                    uploadResultDiv.innerHTML = '';
                    qmResultDiv.innerHTML = '';

                    try {
                        const response = await fetch("{{ route('api.sap.upload') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: getAuthBody({ filename: uploadSapBtn.dataset.filename })
                        });
                        const result = await response.json();
                        showResult(uploadResultDiv, response.ok && result.status === 'success', result.message, result.results, 'upload');

                        if (response.ok && result.status === 'success') {
                            const successfulMaterials = result.results.filter(r => r.status === 'Success');
                            if (successfulMaterials.length > 0) {
                                uploadSapBtn.classList.add('d-none');
                                if(downloadOnlyBtn) downloadOnlyBtn.classList.add('d-none');
                                activateQmBtn.classList.remove('d-none');
                                sendEmailBtn.classList.remove('d-none');
                                emailNotificationArea.classList.remove('d-none')
                            }
                        }
                    } catch (error) {
                        showResult(uploadResultDiv, false, 'Network Error during upload.');
                    } finally {
                        setLoadingState(uploadSapBtn, false);
                    }
                }

                async function handleQmActivation() {
                    setLoadingState(activateQmBtn, true);
                    qmResultDiv.innerHTML = '';

                    const successfulMaterials = Array.from(uploadResultDiv.querySelectorAll('li.is-success'))
                        .map(li => ({
                            matnr: li.dataset.materialCode,
                            werks: li.dataset.plant
                        }));

                    if (successfulMaterials.length === 0) {
                        showResult(qmResultDiv, false, 'No successful materials to activate.');
                        setLoadingState(activateQmBtn, false);
                        return;
                    }

                    try {
                        const response = await fetch("{{ route('api.qm.activate') }}", {
                            method: 'POST',
                            headers: getHeaders(),
                            body: getAuthBody({
                                materials: successfulMaterials,
                                plant: "{{ session('processed_plant') }}"
                            })
                        });
                        const result = await response.json();
                        showResult(qmResultDiv, response.ok && result.status === 'success', result.message, result.results, 'qm');
                    } catch (error) {
                        showResult(qmResultDiv, false, 'Network Error during QM activation.');
                    } finally {
                        setLoadingState(activateQmBtn, false);
                        activateQmBtn.classList.add('d-none');
                        document.getElementById('send-email-btn').classList.add('d-none');
                        document.getElementById('email-notification-area').classList.add('d-none'); // Sembunyikan juga area input email
                    }
                }

                // --- FUNGSI BARU UNTUK MENGIRIM EMAIL ---
            async function handleSendEmail() {
                setLoadingState(sendEmailBtn, true);

                const successfulMaterials = Array.from(uploadResultDiv.querySelectorAll('li.is-success'))
                    .map(li => ({
                        material_code: li.dataset.materialCode,
                        plant: li.dataset.plant,
                        message: li.textContent.split(':').pop().trim() // Ambil pesan OK
                    }));

                try {
                    const response = await fetch("{{ route('api.notification.send') }}", {
                        method: 'POST',
                        headers: getHeaders(),
                        body: JSON.stringify({
                            recipient: emailRecipientInput.value,
                            results: successfulMaterials
                        })
                    });

                    if(response.ok) {
                        alert('Email notification sent successfully!');
                    } else {
                        const errorResult = await response.json();
                        alert('Failed to send email: ' + (errorResult.message || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Network error while sending email.');
                } finally {
                    setLoadingState(sendEmailBtn, false);
                }
            }

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
                    const spinner = button.querySelector('.spinner-border');
                    button.disabled = isLoading;
                    isLoading ? spinner.classList.remove('d-none') : spinner.classList.add('d-none');
                }

                function showResult(div, isSuccess, message, details = null, type = 'upload') {
                    const alertClass = isSuccess ? 'alert-success-frosted' : 'alert-danger';
                    let html = `<div class="alert ${alertClass}">${message || (isSuccess ? 'Process successful.' : 'An error occurred.')}</div>`;
                    if (details && Array.isArray(details)) {
                        html += '<div class="upload-details"><ul>';
                        details.forEach(item => {
                            const icon = item.status === 'Success' ? '✅' : '❌';
                            const successClass = item.status === 'Success' ? 'is-success' : '';
                            const dataAttrs = (type === 'upload' && item.plant) ? `data-material-code="${item.material_code}" data-plant="${item.plant}"` : `data-material-code="${item.material_code}"`;
                            const plantInfo = item.plant ? `(Plant: ${item.plant})` : '';
                            html += `<li class="${successClass}" ${dataAttrs}>${icon} <strong>${item.material_code}</strong> ${plantInfo}: ${item.message}</li>`;
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
