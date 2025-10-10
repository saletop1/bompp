<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAP Routing Uploader</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <style>
        body { background-image: url("{{ asset('images/ainun.jpg') }}"); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh; padding-top: 1rem; padding-bottom: 1rem; }
        .card { background: rgba(75, 74, 74, 0.33); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1); color: white; }
        .converter-container { max-width: 1300px; margin-top: 0; margin-bottom: 1rem; }
        .page-title-container { text-align: center; }
        .page-title-container .main-title { color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); margin-bottom: 0; }
        .page-title-container .subtitle { color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); font-size: 0.9rem; }
        .sap-logo-header { height: 80px; width: auto; margin-left: 20px; }
        .nav-pills .nav-link { background-color: rgba(255, 255, 255, 0.1); color: #f0f0f0; margin: 0 5px; transition: all 0.3s ease; border: 1px solid transparent; border-radius: 50px; }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link { background-color: #ffffff1c; color: #02fff7ff; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

        .table {
            --bs-table-bg: transparent;
            --bs-table-hover-bg: rgba(0, 0, 0, 0.04);
            color: #212529;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        .table th, .table td {
            padding: 0.4rem 1rem;
            vertical-align: middle;
            border-top: none;
        }
        .table-responsive {
            max-height: 60vh;
            overflow-y: auto;
            border-radius: 0.75rem;
            background: rgba(255, 255, 240, 0.9); /* Putih gading */
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 2; /* Header utama, lapisan paling atas */
            background-color: #343a40;
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fff;
        }
        tbody tr:not(.collapse-row) {
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        tbody tr:last-child {
            border-bottom: none;
        }
        .document-header-row {
            cursor: pointer;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important;
        }
        .document-header-row > td {
            position: sticky;
            top: 40px; /* Jarak dari atas, persis di bawah header utama */
            background-color: #e9ecef; /* Latar belakang solid untuk menutupi konten */
            z-index: 1; /* Lapisan di bawah header utama */
        }
        #results-tbody > .document-header-row:first-child {
            border-top: none;
        }

        .details-toggle { cursor: pointer; user-select: none; }
        .details-toggle:hover { color: #0d6efd; }
        .collapse-row > td { padding: 0 !important; border: none !important; background-color: transparent !important; }
        .details-card { background: rgba(0,0,0,0.05); padding: 1rem; border-radius: 0.5rem; }
        .table-danger, .table-danger > th, .table-danger > td { --bs-table-bg: #dc3545; color: white; font-weight: bold; }
        .delete-row-icon { cursor: pointer; transition: color 0.2s ease; font-size: 1.1rem; }
        .delete-row-icon:hover { color: #dc3545; }

        .status-badge { display: inline-block; padding: 0.3em 0.6em; font-size: 75%; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.375rem; }
        .status-badge-urgent { background-color: #dc3545; } .status-badge-priority { background-color: #fd7e14; } .status-badge-standart { background-color: #ffc107; color: #212529; }

        .form-check-input { width: 1.25em; height: 1.25em; margin-top: 0.1em; vertical-align: top; background-color: #fff; border: 1px solid #ccc; cursor: pointer; transition: background-color 0.2s ease-in-out; }
        .form-check-input:checked { background-color: #10b981; border-color: #059669; }
        .form-check-input:focus { box-shadow: 0 0 0 .25rem rgba(16, 185, 129, 0.25); border-color: #10b981; }
        #history-card { margin-top: 2rem; }
        #history-card .table-responsive { max-height: 40vh; }

        .doc-header-content { display: flex; align-items: center; gap: 1rem; width: 100%; }
        .doc-header-left { display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0; }
        .doc-title { flex-grow: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: left; font-weight: 500;}

        .status-cycle-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.4em 0.8em;
            font-size: 85%;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
            min-width: 100px;
            justify-content: center;
        }
        .status-cycle-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
        }
        .status-cycle-btn.status-urgent { background-color: #dc3545; border-color: #ff7c8a; }
        .status-cycle-btn.status-priority { background-color: #fd7e14; border-color: #ffaa6a; }
        .status-cycle-btn.status-standart { background-color: #ffc107; color: #212529; border-color: #ffe085; }
        .status-cycle-btn.status-none { background-color: #6c757d; border-color: #a1aab2; }

        #sap-credential-modal .modal-content,
        #save-details-modal .modal-content {
            background: rgba(75, 74, 74, 0.33);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        #sap-credential-modal .modal-header,
        #save-details-modal .modal-header {
            border-bottom-color: rgba(255, 255, 255, 0.2);
        }
        #sap-credential-modal .modal-footer,
        #save-details-modal .modal-footer {
            border-top-color: rgba(255, 255, 255, 0.2);
        }

        .document-header-row .bi-chevron-right {
            transition: transform 0.2s ease-in-out;
        }
        .document-header-row:not(.collapsed) .bi-chevron-right {
            transform: rotate(90deg);
        }

        /* Tema awal untuk bar pencarian */
        .themed-search .form-control {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .themed-search .form-control:focus {
            background-color: rgba(0, 0, 0, 0.4);
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: none;
            color: #fff;
        }
        .themed-search .form-control::placeholder {
            color: #aaa;
        }
        .themed-search .input-group-text {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ccc;
        }

        /* Tema Frosted Glass untuk SweetAlert */
        .swal2-popup {
            background: rgba(75, 74, 74, 0.33) !important;
            backdrop-filter: blur(5px) !important;
            -webkit-backdrop-filter: blur(5px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1) !important;
            color: #fff !important;
        }
        .swal2-title {
            color: #fff !important;
        }
        .swal2-html-container, .swal2-content {
            color: #e0e0e0 !important;
        }
        .swal2-icon.swal2-warning {
            border-color: #ffc107 !important;
            color: #ffc107 !important;
        }
        .swal2-icon.swal2-success .swal2-success-line-tip,
        .swal2-icon.swal2-success .swal2-success-line-long {
            background-color: #28a745 !important;
        }
        .swal2-icon.swal2-success-ring {
            border-color: rgba(40, 167, 69, 0.3) !important;
        }

        /* Warna teks untuk tabel detail operasi */
        .operation-details-table td,
        .operation-details-table th {
            color: #006400; /* Hijau Tua */
        }
        .operation-details-table thead th {
            color: #004d00; /* Hijau lebih tua untuk header */
        }

        .operation-details-table td, .operation-details-table th {
            text-align: center;
        }

    </style>
</head>
<body>
    <div class="container converter-container">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container"> <h1 class="h3 main-title">SAP Routing Data Center</h1> <p class="subtitle mb-0">Confirm & Release Routing Data</p> </div>
            <img src="{{ asset('images/saplogo.png') }}" alt="SAP Logo" class="sap-logo-header">
        </div>

        <div class="card p-4 p-lg-5">
            <div class="card-body">
                <ul class="nav nav-pills nav-fill mb-4">
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('converter.index') ? 'active' : '' }}" href="{{ route('converter.index') }}"> Material Master</a> </li>
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}" href="{{ route('bom.index') }}"> BOM Master</a> </li>
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('routing.index') ? 'active' : '' }}" href="{{ route('routing.index') }}"> Routing Master</a> </li>
                </ul>
                <hr style="border-color: rgba(255,255,255,0.3);">

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                        <h4 class="mb-0 me-auto">Data Routing Menunggu</h4>
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <form id="upload-form" class="m-0">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="routing_file" id="routing_file" required accept=".xlsx, .xls, .csv">
                                    <button class="btn btn-primary" type="submit" id="process-btn"><i class="bi bi-gear-fill"></i></button>
                                </div>
                            </form>
                            <button class="btn btn-danger" id="delete-selected-btn" disabled><i class="bi bi-trash-fill me-2"></i>Hapus</button>
                            <button class="btn btn-warning" id="save-selected-btn" disabled><i class="bi bi-save-fill me-2"></i>Save</button>
                            <button class="btn btn-success" id="upload-selected-btn" disabled><i class="bi bi-cloud-upload-fill me-2"></i>Release</button>
                        </div>
                    </div>

                    <div class="input-group themed-search">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search-input" placeholder="Cari berdasarkan Dokumen, Material, Deskripsi, atau Work Center..." autocomplete="off" readonly onfocus="this.removeAttribute('readonly');" onpaste="return false;">
                    </div>
                </div>

                <div id="results-container">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Plant</th>
                                    <th>Description</th>
                                    <th>
                                        Jml Operasi
                                        <input class="form-check-input ms-1" type="checkbox" id="toggle-all-details-checkbox" title="Buka/Tutup Semua Detail Operasi">
                                    </th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="results-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="history-card" class="card p-4 p-lg-5">
            <div class="card-body">
                 <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">History Upload</h4>
                    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#history-table-container" aria-expanded="true">
                        <i class="bi bi-clock-history me-1"></i> Tampilkan/Sembunyikan
                    </button>
                </div>
                <div class="collapse" id="history-table-container">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Nama Dokumen</th>
                                    <th>Tgl Upload</th>
                                    <th>Jml Material</th>
                                </tr>
                            </thead>
                            <tbody id="history-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <footer class="text-center text-white mt-4"><small>© PT. Kayu Mebel Indonesia, 2025</small></footer>
    </div>

    <div class="modal fade" id="sap-credential-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Masukkan Kredensial SAP</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="sap-username" class="form-label">SAP Username</label><input type="text" class="form-control" id="sap-username"></div><div class="mb-3"><label for="sap-password" class="form-label">SAP Password</label><input type="password" class="form-control" id="sap-password"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-upload-btn">Konfirmasi & Mulai Upload</button></div></div></div></div>
    <div class="modal fade" id="save-details-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail Dokumen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="document-name" class="form-label">Nama Dokumen</label><input type="text" class="form-control" id="document-name" placeholder="Contoh: Routing Pintu Depan" required maxlength="40"></div><div class="mb-3"><label for="product-name" class="form-label">Nama Produk</label><input type="text" class="form-control" id="product-name" placeholder="Contoh: Pintu Jati Model A" required maxlength="20"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-save-btn">Konfirmasi & Simpan</button></div></div></div></div>
    <div class="modal fade" id="upload-progress-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-dark" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(5px);"><div class="modal-header"><h5 class="modal-title">Mengunggah Routing ke SAP...</h5></div><div class="modal-body"><p id="progress-status-text" class="text-center mb-2">Menunggu...</p><div class="progress" role="progressbar" style="height: 25px;"><div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div></div></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Menggunakan 'load' agar skrip berjalan setelah semua konten (termasuk gambar) dimuat.
        window.addEventListener('load', function () {
            let processedDataByFile = @json($savedRoutings ?? []);
            let historyRoutings = @json($historyRoutings ?? []);

            const uploadForm = document.getElementById('upload-form');
            const processBtn = document.getElementById('process-btn');
            const resultsTbody = document.getElementById('results-tbody');
            const historyTbody = document.getElementById('history-tbody');
            const saveSelectedBtn = document.getElementById('save-selected-btn');
            const deleteSelectedBtn = document.getElementById('delete-selected-btn');
            const uploadSelectedBtn = document.getElementById('upload-selected-btn');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const confirmSaveBtn = document.getElementById('confirm-save-btn');
            const confirmUploadBtn = document.getElementById('confirm-upload-btn');
            const searchInput = document.getElementById('search-input');
            const uploadModal = new bootstrap.Modal(document.getElementById('sap-credential-modal'));
            const saveModal = new bootstrap.Modal(document.getElementById('save-details-modal'));
            const progressModal = new bootstrap.Modal(document.getElementById('upload-progress-modal'));

            // === SCRIPT UNTUK MENGATASI MASALAH AUTOFILL (FAILSAFE) ===
            const sapUsernameInput = document.getElementById('sap-username');
            const sapPasswordInput = document.getElementById('sap-password');

            sapPasswordInput.addEventListener('focus', () => {
                setTimeout(() => {
                    if (searchInput.value && searchInput.value === sapUsernameInput.value) {
                        searchInput.value = '';
                    }
                }, 50);
            });
            // === AKHIR PERUBAHAN ===

            function getFlatData() { return processedDataByFile.flatMap(group => group.data); }

            function renderPendingTable(data = processedDataByFile, checkedIndices = new Set()) {
                resultsTbody.innerHTML = '';
                if (data.length === 0) {
                    resultsTbody.innerHTML = `<tr><td colspan="8" class="text-center fst-italic py-4">Tidak ada data yang cocok dengan pencarian.</td></tr>`;
                    return;
                }
                let globalIndex = 0;
                data.forEach((fileGroup, fileIndex) => {
                    const collapseId = `collapse-doc-${fileIndex}`;
                    const headerRow = document.createElement('tr');
                    headerRow.className = 'document-header-row collapsed';
                    headerRow.setAttribute('data-bs-target', `.${collapseId}`);
                    headerRow.setAttribute('aria-expanded', 'false');
                    headerRow.setAttribute('data-file-index', fileIndex);
                    headerRow.setAttribute('data-is-saved', fileGroup.is_saved);
                    headerRow.setAttribute('data-doc-number', fileGroup.document_number || '');

                    const currentStatusText = fileGroup.status || 'None';
                    const statusClass = currentStatusText.toLowerCase();
                    const docNumber = fileGroup.document_number || '';

                    const statusDisplayHtml = fileGroup.is_saved ? `
                        <span class="status-cycle-btn status-${statusClass}" data-doc-number="${docNumber}" data-current-status="${fileGroup.status || ''}" title="Klik untuk ganti status">
                            ${currentStatusText.replace('None', 'Set Status')}
                        </span>
                    ` : (fileGroup.status ? `<span class="status-badge status-badge-${statusClass}">${fileGroup.status}</span>` : '');

                    headerRow.innerHTML = `
                        <td><input class="form-check-input document-group-checkbox" type="checkbox" data-file-index="${fileIndex}" title="Pilih semua di dokumen ini"></td>
                        <td colspan="7">
                            <div class="doc-header-content">
                                <div class="doc-header-left">
                                    <i class="bi bi-chevron-right"></i>
                                    ${statusDisplayHtml}
                                </div>
                                <strong class="doc-title">${fileGroup.fileName}</strong>
                            </div>
                        </td>`;
                    resultsTbody.appendChild(headerRow);

                    if(fileGroup.data && fileGroup.data.length > 0) {
                        fileGroup.data.forEach((group, itemIndex) => {
                            const detailsId = `details-${globalIndex}`;
                            const hasDuplicateZP01 = (group.operations || []).filter(op => (op.IV_STEUS === 'ZP01' || op.CONTROL_KEY === 'ZP01')).length > 1;
                            const rowClass = hasDuplicateZP01 ? 'table-danger' : '';
                            const statusHtml = hasDuplicateZP01 ? `<span class="badge bg-danger">Error: Duplikat ZP01</span>` : `<span class="badge bg-secondary">Menunggu</span>`;
                            const isChecked = checkedIndices.has(globalIndex) ? 'checked' : '';

                            const mainRow = document.createElement('tr');
                            mainRow.className = `collapse ${collapseId} ${rowClass}`;
                            mainRow.setAttribute('data-global-index', globalIndex);
                            mainRow.setAttribute('data-file-index', fileIndex);
                            mainRow.innerHTML = `
                                <td><input class="form-check-input row-checkbox" type="checkbox" data-global-index="${globalIndex}" ${hasDuplicateZP01 ? 'disabled' : ''} ${isChecked}></td>
                                <td>${itemIndex + 1}</td>
                                <td>${group.header.IV_MATERIAL}</td>
                                <td>${group.header.IV_PLANT}</td>
                                <td>${group.header.IV_DESCRIPTION}</td>
                                <td class="details-toggle" data-bs-toggle="collapse" data-bs-target="#${detailsId}">${(group.operations || []).length} <i class="bi bi-info-circle ms-1 small"></i></td>
                                <td class="status-cell">${statusHtml}</td>
                                <td><i class="bi bi-trash-fill delete-row-icon" data-global-index="${globalIndex}" title="Hapus baris ini"></i></td>`;
                            resultsTbody.appendChild(mainRow);

                            let operationsHtml = `<table class="table table-sm mb-0 operation-details-table"><thead><tr><th>Work Center</th><th>Ctrl Key</th><th>Description</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;
                            (group.operations || []).forEach(op => {
                                operationsHtml += `<tr>
                                    <td>${op.IV_ARBPL || op.WORK_CNTR || ''}</td>
                                    <td>${op.IV_STEUS || op.CONTROL_KEY || ''}</td>
                                    <td>${op.IV_LTXA1 || op.DESCRIPTION || ''}</td>
                                    <td>${op.IV_BMSCHX || op.BASE_QTY || ''}</td>
                                    <td>${op.IV_VGW01X || op.ACTIVITY_1 || ''}</td>
                                    <td>${op.IV_VGE01X || op.UOM_1 || ''}</td></tr>`;
                            });
                            operationsHtml += `</tbody></table>`;

                            const detailsRow = document.createElement('tr');
                            detailsRow.className = `collapse-row collapse ${collapseId}`;
                            detailsRow.innerHTML = `<td colspan="8"><div class="collapse" id="${detailsId}"><div class="p-3 details-card">${operationsHtml}</div></div></td>`;
                            resultsTbody.appendChild(detailsRow);
                            globalIndex++;
                        });
                    }
                });
                updateButtonStates();
            }

            function renderHistoryTable(data = historyRoutings) {
                historyTbody.innerHTML = '';
                if (data.length === 0) {
                    historyTbody.innerHTML = `<tr><td colspan="4" class="text-center fst-italic py-3">Tidak ada histori yang cocok dengan pencarian.</td></tr>`;
                    return;
                }
                let historyGlobalIndex = 0;
                data.forEach((doc, index) => {
                    const docCollapseId = `history-collapse-${index}`;
                    const headerRow = document.createElement('tr');
                    headerRow.className = 'document-header-row collapsed';
                    headerRow.setAttribute('data-bs-toggle', 'collapse');
                    headerRow.setAttribute('data-bs-target', `#${docCollapseId}`);
                    headerRow.setAttribute('aria-expanded', 'false');
                    headerRow.innerHTML = `
                        <td><i class="bi bi-chevron-right"></i></td>
                        <td><strong>${doc.fileName}</strong></td>
                        <td>${doc.uploaded_at || 'N/A'}</td>
                        <td>${doc.data.length}</td>`;
                    historyTbody.appendChild(headerRow);

                    const detailRow = document.createElement('tr');
                    detailRow.className = 'collapse-row';
                    detailRow.innerHTML = `<td colspan="4" class="p-0">
                        <div class="collapse" id="${docCollapseId}">
                            <div class="p-3 details-card"></div>
                        </div>
                    </td>`;

                    let innerHtml = '<table class="table table-sm table-borderless"><thead><tr><th>Material</th><th>Description</th><th>Jml Operasi</th><th>Tgl Eksekusi</th></tr></thead><tbody>';
                    doc.data.forEach(item => {
                        const opCollapseId = `history-op-collapse-${historyGlobalIndex}`;
                        innerHtml += `
                            <tr>
                                <td>${item.header.IV_MATERIAL}</td>
                                <td>${item.header.IV_DESCRIPTION}</td>
                                <td class="details-toggle" data-bs-toggle="collapse" data-bs-target="#${opCollapseId}">
                                    ${(item.operations || []).length} <i class="bi bi-info-circle ms-1 small"></i>
                                </td>
                                <td>${item.uploaded_at_item || 'N/A'}</td>
                            </tr>`;

                        let operationsTable = `<table class="table table-sm mb-0 operation-details-table"><thead><tr><th>Work Center</th><th>Ctrl Key</th><th>Description</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;
                            if (Array.isArray(item.operations)) {
                                item.operations.forEach(op => {
                                    operationsTable += `<tr>
                                        <td>${op.IV_ARBPL || ''}</td>
                                        <td>${op.IV_STEUS || ''}</td>
                                        <td>${op.IV_LTXA1 || ''}</td>
                                        <td>${op.IV_BMSCHX || ''}</td>
                                        <td>${op.IV_VGW01X || ''}</td>
                                        <td>${op.IV_VGE01X || ''}</td>
                                    </tr>`;
                                });
                            }
                            operationsTable += `</tbody></table>`;

                        innerHtml += `
                            <tr class="collapse-row">
                                <td colspan="4" class="p-0">
                                    <div class="collapse" id="${opCollapseId}"><div class="p-2 details-card">${operationsTable}</div></div>
                                </td>
                            </tr>`;
                        historyGlobalIndex++;
                    });
                    innerHtml += '</tbody></table>';

                    detailRow.querySelector('.details-card').innerHTML = innerHtml;
                    historyTbody.appendChild(detailRow);
                });
            }

            async function updateDocumentStatusOnServer(document_number, status) {
                const Toast = Swal.mixin({ toast: true, position: 'top-start', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                try {
                    const response = await fetch("{{ route('routing.updateStatus') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ document_number, status: status || null })
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message);

                    Toast.fire({ icon: 'success', title: 'Status diperbarui' });

                    const docGroup = processedDataByFile.find(g => g.document_number === document_number);
                    if (docGroup) {
                        docGroup.status = status;
                    }

                    const cycleBtn = document.querySelector(`.status-cycle-btn[data-doc-number="${document_number}"]`);
                    if (cycleBtn) {
                        cycleBtn.classList.remove('status-urgent', 'status-priority', 'status-standart', 'status-none');

                        const newStatus = status || 'None';
                        const newStatusClass = newStatus.toLowerCase();
                        cycleBtn.classList.add(`status-${newStatusClass}`);
                        cycleBtn.textContent = newStatus.replace('None', 'Set Status');
                        cycleBtn.dataset.currentStatus = status;
                    }
                } catch (error) {
                    console.error('Gagal menyimpan status:', error);
                    Toast.fire({ icon: 'error', title: 'Gagal menyimpan status' });
                }
            }

            function deleteItemsByGlobalIndices(indicesToDeleteSet) {
                if (indicesToDeleteSet.size === 0) return;
                let globalIndexCounter = 0;
                const newProcessedDataByFile = processedDataByFile.map(fileGroup => {
                    const newData = fileGroup.data.filter(item => {
                        const shouldKeep = !indicesToDeleteSet.has(globalIndexCounter);
                        globalIndexCounter++;
                        return shouldKeep;
                    });
                    return { ...fileGroup, data: newData };
                }).filter(fileGroup => fileGroup.data.length > 0);
                processedDataByFile = newProcessedDataByFile;
                renderPendingTable();
            }

            function updateButtonStates() {
                const allRowCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
                const checkedRowCount = document.querySelectorAll('.row-checkbox:checked:not(:disabled)').length;
                const checkedDocCount = document.querySelectorAll('.document-group-checkbox:checked').length;
                const totalChecked = checkedRowCount + checkedDocCount;
                deleteSelectedBtn.disabled = totalChecked === 0;
                uploadSelectedBtn.disabled = checkedRowCount === 0;
                saveSelectedBtn.disabled = checkedRowCount === 0;
                if (allRowCheckboxes.length > 0 && checkedRowCount === allRowCheckboxes.length) {
                    selectAllCheckbox.checked = true;
                    selectAllCheckbox.indeterminate = false;
                } else if (checkedRowCount > 0) {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = true;
                } else {
                    selectAllCheckbox.checked = false;
                    selectAllCheckbox.indeterminate = false;
                }
            }

            function handleDocumentGroupCheck(masterCheckbox) {
                const fileIndex = masterCheckbox.dataset.fileIndex;
                const isChecked = masterCheckbox.checked;
                document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:not(:disabled)`).forEach(child => child.checked = isChecked);
            }

            function handleRowCheck(rowCheckbox) {
                const fileIndex = rowCheckbox.closest('tr').dataset.fileIndex;
                const masterCheckbox = document.querySelector(`.document-group-checkbox[data-file-index="${fileIndex}"]`);
                if (!masterCheckbox) return;
                const allChilds = document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:not(:disabled)`);
                const checkedChilds = document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:checked:not(:disabled)`);
                masterCheckbox.checked = allChilds.length > 0 && checkedChilds.length === allChilds.length;
                masterCheckbox.indeterminate = checkedChilds.length > 0 && checkedChilds.length < allChilds.length;
            }

            async function performSave(docName, prodName) {
                const allItems = getFlatData();
                const selectedItems = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);
                if (selectedItems.length === 0) return Swal.fire({title: 'Info', text: 'Tidak ada data yang dipilih.', icon: 'info'});

                saveSelectedBtn.disabled = true;
                confirmSaveBtn.disabled = true;
                try {
                    const response = await fetch("{{ route('routing.save') }}", {
                        method: 'POST',
                        body: JSON.stringify({ routings: selectedItems, document_name: docName, product_name: prodName }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'}
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Gagal menyimpan data.');

                    await Swal.fire({ icon: 'success', title: 'Sukses!', text: result.message + ". Memuat ulang data."});
                    window.location.reload();
                } catch (error) {
                    Swal.fire({title: 'Error!', text: error.message, icon: 'error'});
                } finally {
                    saveSelectedBtn.disabled = false;
                    confirmSaveBtn.disabled = false;
                }
            }

            async function performDeletion() {
                const allItems = getFlatData();
                const checkedRowBoxes = document.querySelectorAll('.row-checkbox:checked');
                const checkedDocBoxes = document.querySelectorAll('.document-group-checkbox:checked');
                if (checkedRowBoxes.length === 0 && checkedDocBoxes.length === 0) return;

                const docsToDelete = new Set();
                const rowsToDeleteFromDb = [];
                const allIndicesToUpdateView = new Set();

                checkedDocBoxes.forEach(docBox => {
                    const fileIndex = docBox.dataset.fileIndex;
                    const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                    if (headerRow && headerRow.dataset.isSaved === 'true') {
                        docsToDelete.add(headerRow.dataset.docNumber);
                    }
                    document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox`).forEach(rowBox => {
                        allIndicesToUpdateView.add(parseInt(rowBox.dataset.globalIndex));
                    });
                });
                checkedRowBoxes.forEach(rowBox => {
                    const globalIndex = parseInt(rowBox.dataset.globalIndex);
                    if (allIndicesToUpdateView.has(globalIndex)) return;
                    allIndicesToUpdateView.add(globalIndex);
                    const fileIndex = rowBox.closest('tr').dataset.fileIndex;
                    const fileGroup = processedDataByFile[fileIndex];
                    if (fileGroup && fileGroup.is_saved) {
                         rowsToDeleteFromDb.push({ doc_number: fileGroup.document_number, material: allItems[globalIndex].header.IV_MATERIAL });
                    }
                });

                let confirmText = docsToDelete.size > 0 || rowsToDeleteFromDb.length > 0
                    ? 'Item yang dipilih akan dihapus permanen dari database. Lanjutkan?'
                    : 'Hapus item yang dipilih dari tampilan?';

                const result = await Swal.fire({
                    title: 'Konfirmasi Penghapusan', text: confirmText, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!'
                });

                if (result.isConfirmed) {
                    let allSuccess = true;
                    if (docsToDelete.size > 0 || rowsToDeleteFromDb.length > 0) {
                        try {
                            if (docsToDelete.size > 0) {
                                const response = await fetch("{{ route('routing.delete') }}", {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                    body: JSON.stringify({ document_numbers: Array.from(docsToDelete) })
                                });
                                if (!response.ok) throw new Error('Gagal menghapus dokumen.');
                            }
                            if (rowsToDeleteFromDb.length > 0) {
                                const response = await fetch("{{ route('routing.deleteRows') }}", {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                    body: JSON.stringify({ rows_to_delete: rowsToDeleteFromDb })
                                });
                                if (!response.ok) throw new Error('Gagal menghapus baris.');
                            }
                        } catch (error) {
                            allSuccess = false;
                            Swal.fire({title: 'Error!', text: error.message, icon: 'error'});
                        }
                    }
                    if (allSuccess) {
                        deleteItemsByGlobalIndices(allIndicesToUpdateView);
                        Swal.fire({title: 'Dihapus!', text: 'Item yang dipilih telah dihapus.', icon: 'success'});
                    }
                }
            }

            async function performUpload() {
                const username = document.getElementById('sap-username').value;
                const password = document.getElementById('sap-password').value;
                if (!username || !password) return Swal.fire({title: 'Peringatan', text: 'Username dan Password SAP harus diisi.', icon: 'warning'});
                uploadModal.hide();

                const allItems = getFlatData();
                const itemsToUpload = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);
                const totalItems = itemsToUpload.length;
                let successCount = 0, failCount = 0, processedCount = 0;
                const successfulUploads = [];

                statusText = document.getElementById('progress-status-text');
                progressBar = document.getElementById('upload-progress-bar');
                statusText.textContent = `Memulai... 0 / ${totalItems} berhasil`;
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressModal.show();

                for (const routingData of itemsToUpload) {
                    processedCount++;
                    const globalIndex = allItems.findIndex(item => item === routingData);
                    const targetRow = document.querySelector(`tr[data-global-index="${globalIndex}"]`);
                    const statusCell = targetRow.querySelector('.status-cell');
                    statusCell.innerHTML = `<span class="spinner-border spinner-border-sm text-warning"></span> Menciptakan...`;

                    try {
                        const response = await fetch("{{ route('api.routing.uploadToSap') }}", {
                            method: 'POST', body: JSON.stringify({ username, password, routing_data: routingData }),
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'}
                        });
                        const result = await response.json();

                        if (response.ok && result.status?.toLowerCase() === 'success') {
                            statusCell.innerHTML = `<span class="badge bg-success">Success</span>`;
                            successCount++;
                            const fileIndex = targetRow.dataset.fileIndex;
                            const fileGroup = processedDataByFile[fileIndex];
                            if (fileGroup.is_saved) {
                                successfulUploads.push({ material: routingData.header.IV_MATERIAL, doc_number: fileGroup.document_number });
                            }
                        } else {
                            failCount++;
                            statusCell.innerHTML = `<span class="badge bg-danger" title="${result.message || 'Gagal'}">Failed</span>`;
                        }
                    } catch (error) {
                        failCount++;
                        statusCell.innerHTML = `<span class="badge bg-danger" title="${error.message}">Error</span>`;
                    }
                    const percentage = Math.round((processedCount / totalItems) * 100);
                    progressBar.style.width = percentage + '%';
                    progressBar.textContent = percentage + '%';
                    statusText.textContent = `${successCount} / ${totalItems} material berhasil diupload`;
                }

                progressModal.hide();
                if (successfulUploads.length > 0) {
                    await fetch("{{ route('routing.markAsUploaded') }}", {
                        method: 'POST',
                        body: JSON.stringify({ successful_uploads: successfulUploads }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'}
                    });
                    await Swal.fire({ icon: 'success', title: 'Sukses!', text: 'Data berhasil diunggah. Halaman akan dimuat ulang.'});
                    window.location.reload();
                } else if (processedCount > 0) {
                     Swal.fire({title: 'Proses Selesai', text: `Upload selesai: ${successCount} berhasil, ${failCount} gagal.`, icon: 'info'});
                }
            }

            // --- INITIAL RENDER & EVENT LISTENERS ---
            renderPendingTable();
            renderHistoryTable();

            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.toLowerCase().trim();

                const filterData = (data) => {
                    return data.map(fileGroup => {
                        if (fileGroup.fileName.toLowerCase().includes(searchTerm)) {
                            return fileGroup;
                        }
                        const filteredItems = fileGroup.data.filter(item => {
                            const materialMatch = (item.header.IV_MATERIAL || '').toLowerCase().includes(searchTerm);
                            const descriptionMatch = (item.header.IV_DESCRIPTION || '').toLowerCase().includes(searchTerm);
                            const workCenterMatch = (item.operations || []).some(op => (op.IV_ARBPL || '').toLowerCase().includes(searchTerm));
                            return materialMatch || descriptionMatch || workCenterMatch;
                        });
                        if (filteredItems.length > 0) {
                            return { ...fileGroup, data: filteredItems };
                        }
                        return null;
                    }).filter(Boolean);
                };

                renderPendingTable(filterData(processedDataByFile));
                renderHistoryTable(filterData(historyRoutings));
            });

            uploadForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fileInput = document.getElementById('routing_file');
                const file = fileInput.files[0];

                if (!file) {
                    Swal.fire({title: 'Peringatan', text: 'Silakan pilih file terlebih dahulu.', icon: 'warning'});
                    return;
                }

                processBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
                processBtn.disabled = true;

                try {
                    // Logika validasi yang rumit di frontend dihapus.
                    // Kita langsung kirim file ke backend untuk divalidasi di sana.
                    const formData = new FormData(uploadForm);
                    const response = await fetch("{{ route('routing.processFile') }}", {
                        method: 'POST', body: formData,
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json'}
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        // Backend akan memberikan pesan error yang jelas jika validasi gagal
                        throw new Error(result.error || 'Gagal memproses file di server.');
                    }

                    // Jika berhasil, tambahkan data ke tabel
                    processedDataByFile.push(result);
                    renderPendingTable();
                    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, title: 'File berhasil diproses!', icon: 'success' });

                } catch (error) {
                    // Menampilkan pesan error dari backend atau error koneksi
                    Swal.fire({title: 'Error!', html: error.message, icon: 'error'});
                } finally {
                    // Mengembalikan tombol ke keadaan semula
                    processBtn.innerHTML = `<i class="bi bi-gear-fill"></i>`;
                    processBtn.disabled = false;
                    uploadForm.reset();
                }
            });

            deleteSelectedBtn.addEventListener('click', performDeletion);
            saveSelectedBtn.addEventListener('click', () => saveModal.show());
            uploadSelectedBtn.addEventListener('click', () => {
                uploadModal.show();
            });
            confirmSaveBtn.addEventListener('click', () => {
                const docName = document.getElementById('document-name').value;
                const prodName = document.getElementById('product-name').value;
                if (!docName || !prodName) return Swal.fire({title: 'Peringatan', text: 'Nama Dokumen dan Nama Produk harus diisi.', icon: 'warning'});
                saveModal.hide();
                performSave(docName, prodName);
            });
            confirmUploadBtn.addEventListener('click', performUpload);

            selectAllCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                document.querySelectorAll('.document-group-checkbox, .row-checkbox:not(:disabled)').forEach(cb => cb.checked = isChecked);
                updateButtonStates();

                const allHeaders = document.querySelectorAll('#results-tbody .document-header-row');
                const allMainCollapsibleRows = document.querySelectorAll('#results-tbody > tr.collapse');

                if (isChecked) {
                    allHeaders.forEach(header => {
                        header.classList.remove('collapsed');
                        header.setAttribute('aria-expanded', 'true');
                    });
                    allMainCollapsibleRows.forEach(row => {
                        row.classList.add('show');
                    });
                } else {
                    allHeaders.forEach(header => {
                        header.classList.add('collapsed');
                        header.setAttribute('aria-expanded', 'false');
                    });
                    allMainCollapsibleRows.forEach(row => {
                        row.classList.remove('show');
                    });
                }
            });

            resultsTbody.addEventListener('change', (e) => {
                const target = e.target;
                if (target.classList.contains('document-group-checkbox')) handleDocumentGroupCheck(target);
                if (target.classList.contains('row-checkbox')) handleRowCheck(target);
                updateButtonStates();
            });

            resultsTbody.addEventListener('click', e => {
                const cycleBtn = e.target.closest('.status-cycle-btn');
                if (cycleBtn) {
                    e.preventDefault();
                    const statuses = ['', 'Urgent', 'Priority', 'Standart'];
                    const currentStatus = cycleBtn.dataset.currentStatus;
                    const docNumber = cycleBtn.dataset.docNumber;
                    const currentIndex = statuses.indexOf(currentStatus);
                    const nextIndex = (currentIndex + 1) % statuses.length;
                    const newStatus = statuses[nextIndex];

                    if (docNumber) {
                        updateDocumentStatusOnServer(docNumber, newStatus);
                    }
                    return;
                }

                const deleteBtn = e.target.closest('.delete-row-icon');
                if (deleteBtn) {
                    const globalIndex = parseInt(deleteBtn.dataset.globalIndex);
                    document.querySelectorAll('.row-checkbox, .document-group-checkbox').forEach(cb => cb.checked = false);
                    const targetCheckbox = document.querySelector(`.row-checkbox[data-global-index="${globalIndex}"]`);
                    if(targetCheckbox) targetCheckbox.checked = true;
                    performDeletion();
                    return;
                }

                const headerRow = e.target.closest('.document-header-row');
                if (headerRow) {
                    const targetSelector = headerRow.getAttribute('data-bs-target');
                    if (targetSelector) {
                        const collapsibleElements = document.querySelectorAll(targetSelector);
                        collapsibleElements.forEach(element => {
                            const collapseInstance = bootstrap.Collapse.getOrCreateInstance(element);
                            collapseInstance.toggle();
                        });
                    }
                    const isCollapsed = headerRow.classList.toggle('collapsed');
                    headerRow.setAttribute('aria-expanded', !isCollapsed);
                }
            });

            const toggleAllDetailsCheckbox = document.getElementById('toggle-all-details-checkbox');
            if(toggleAllDetailsCheckbox) {
                toggleAllDetailsCheckbox.addEventListener('change', (e) => {
                    const isChecked = e.target.checked;
                    const allDetailElements = document.querySelectorAll('#results-tbody .collapse-row .collapse');

                    allDetailElements.forEach(element => {
                        const collapseInstance = bootstrap.Collapse.getOrCreateInstance(element);
                        if (isChecked) {
                            collapseInstance.show();
                        } else {
                            collapseInstance.hide();
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>

