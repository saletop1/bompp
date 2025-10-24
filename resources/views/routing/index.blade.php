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
        .table { --bs-table-bg: transparent; --bs-table-hover-bg: rgba(0, 0, 0, 0.04); color: #212529; width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.9rem; }
        .table th, .table td { padding: 0.4rem 1rem; vertical-align: middle; border-top: none; }
        .table-responsive { max-height: 70vh; overflow-y: auto; border-radius: 0.75rem; background: rgba(255, 255, 240, 0.9); border: 1px solid rgba(0, 0, 0, 0.1); }


        /* Aturan untuk Tabel "Data Menunggu" */
        #results-container .table-responsive thead {
             position: sticky; top: 0; z-index: 2;
             background-color: #009095ff;
        }
        #results-container .table-responsive thead th {
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em; color: #fff;
        }
        #results-container .table-responsive .document-header-row > td {
            position: sticky; top: 40px; background-color: #e9ecef; z-index: 1;
        }

        /* --- Aturan CSS untuk Tabel History --- */

        /* 1. Header Utama (NAMA DOKUMEN...) */
        #history-card .table-responsive > .table > thead {
            position: sticky;
            top: 0;
            z-index: 10; /* Tertinggi */
            background-color: #009095ff; /* Tambahkan background di sini */
        }
        #history-card .table-responsive > .table > thead th {
            /* Tidak perlu background lagi di sini */
            color: #fff;
            border-bottom: 2px solid rgba(0, 0, 0, 0.2);
            font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.08em;
        }

        /* 2. Header Dokumen (Dokumen: tes...) */
        #history-card .table-responsive .document-header-row > td {
            position: sticky;
            top: 40px;
            background-color: #e9ecef;
            z-index: 5; /* Menengah */
        }

        /* 3. Header Detail (MATERIAL...) - Tidak sticky */
        #history-card .sticky-detail-header th {
            /* Dihapus sticky */
            background-color: #343a40;
            color: #fff;
            border-bottom: 1px solid #495057;
        }
        /* --- Akhir Aturan CSS Tabel History --- */

        tbody tr:not(.collapse-row) { border-bottom: 1px solid rgba(0, 0, 0, 0.1); }
        tbody tr:last-child { border-bottom: none; }
        .document-header-row { cursor: pointer; border-top: 1px solid rgba(0, 0, 0, 0.1); border-bottom: 1px solid rgba(0, 0, 0, 0.1) !important; }

        #results-tbody > .document-header-row:first-child { border-top: none; }
        .details-modal-trigger { cursor: pointer; user-select: none; }
        .details-modal-trigger:hover { color: #0d6efd; }
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
        .status-cycle-btn { display: inline-flex; align-items: center; padding: 0.4em 0.8em; font-size: 85%; font-weight: 700; line-height: 1; color: #fff; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 20px; cursor: pointer; transition: all 0.2s ease-in-out; border: 1px solid transparent; min-width: 100px; justify-content: center; }
        .status-cycle-btn:hover { transform: scale(1.05); box-shadow: 0 0 10px rgba(255, 255, 255, 0.5); }
        .status-cycle-btn.status-urgent { background-color: #dc3545; border-color: #ff7c8a; }
        .status-cycle-btn.status-priority { background-color: #fd7e14; border-color: #ffaa6a; }
        .status-cycle-btn.status-standart { background-color: #ffc107; color: #212529; border-color: #ffe085; }
        .status-cycle-btn.status-none { background-color: #6c757d; border-color: #a1aab2; }
        #sap-credential-modal .modal-content,
        #save-details-modal .modal-content,
        #routing-details-modal .modal-content {
            background: rgba(75, 74, 74, 0.33); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.3); color: white;
        }
        #sap-credential-modal .modal-header,
        #save-details-modal .modal-header,
        #routing-details-modal .modal-header {
             border-bottom-color: rgba(255, 255, 255, 0.2);
        }
        #sap-credential-modal .modal-footer,
        #save-details-modal .modal-footer,
        #routing-details-modal .modal-footer {
             border-top-color: rgba(255, 255, 255, 0.2);
        }
        #routing-details-modal .modal-body {
             color: #333;
        }
        .document-header-row .bi-chevron-right { transition: transform 0.2s ease-in-out; }
        .document-header-row:not(.collapsed) .bi-chevron-right { transform: rotate(90deg); }
        .themed-search .form-control { background-color: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.2); color: #fff; }
        .themed-search .form-control:focus { background-color: rgba(0, 0, 0, 0.4); border-color: rgba(255, 255, 255, 0.5); box-shadow: none; color: #fff; }
        .themed-search .form-control::placeholder { color: #aaa; }
        .themed-search .input-group-text { background-color: rgba(0, 0, 0, 0.2); border: 1px solid rgba(255, 255, 255, 0.2); color: #ccc; }
        .swal2-popup { background: rgba(75, 74, 74, 0.33) !important; backdrop-filter: blur(5px) !important; -webkit-backdrop-filter: blur(5px) !important; border: 1px solid rgba(255, 255, 255, 0.3) !important; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1) !important; color: #fff !important; }
        .swal2-title { color: #fff !important; }
        .swal2-html-container, .swal2-content { color: #e0e0e0 !important; }
        .swal2-icon.swal2-warning { border-color: #ffc107 !important; color: #ffc107 !important; }
        .swal2-icon.swal2-success .swal2-success-line-tip, .swal2-icon.swal2-success .swal2-success-line-long { background-color: #28a745 !important; }
        .swal2-icon.swal2-success-ring { border-color: rgba(40, 167, 69, 0.3) !important; }

        #routing-details-modal .table {
            background-color: #fff;
            color: #212529;
            margin-bottom: 0;
        }
         #routing-details-modal .table th,
         #routing-details-modal .table td {
             vertical-align: middle !important;
             text-align: center;
         }
         #routing-details-modal .table th {
            background-color: #f8f9fa;
         }

         /* [PERBAIKAN] Mengurangi padding vertikal untuk tabel detail di History (di dalam collapse) */
        #history-card .details-card .table th,
        #history-card .details-card .table td {
            padding-top: 0.2rem;  /* Kurangi padding atas */
            padding-bottom: 0.2rem; /* Kurangi padding bawah */
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
                                        Jml Detail
                                        <input class="form-check-input ms-1" type="checkbox" id="toggle-all-details-checkbox" title="Buka/Tutup Semua Detail">
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
                    <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#history-table-container" aria-expanded="false">
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

    <!-- Modal Kredensial SAP -->
    <div class="modal fade" id="sap-credential-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Masukkan Kredensial SAP</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="sap-username" class="form-label">SAP Username</label><input type="text" class="form-control" id="sap-username"></div><div class="mb-3"><label for="sap-password" class="form-label">SAP Password</label><input type="password" class="form-control" id="sap-password"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-upload-btn">Konfirmasi & Mulai Upload</button></div></div></div></div>
    <!-- Modal Simpan Detail -->
    <div class="modal fade" id="save-details-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail Dokumen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="document-name" class="form-label">Nama Dokumen</label><input type="text" class="form-control" id="document-name" placeholder="Contoh: Routing Pintu Depan" required maxlength="40"></div><div class="mb-3"><label for="product-name" class="form-label">Nama Produk</label><input type="text" class="form-control" id="product-name" placeholder="Contoh: Pintu Jati Model A" required maxlength="20"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-save-btn">Konfirmasi & Simpan</button></div></div></div></div>
    <!-- Modal Progress Upload -->
    <div class="modal fade" id="upload-progress-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-dark" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(5px);"><div class="modal-header"><h5 class="modal-title">Mengunggah Routing ke SAP...</h5></div><div class="modal-body"><p id="progress-status-text" class="text-center mb-2">Menunggu...</p><div class="progress" role="progressbar" style="height: 25px;"><div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div></div></div></div></div></div>

    <!-- Modal untuk Detail Routing -->
    <div class="modal fade" id="routing-details-modal" tabindex="-1" aria-labelledby="routingDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="routingDetailsModalLabel">Detail Routing</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="background-color: #f8f9fa;">
             <div class="mb-3">
                 <strong>Material:</strong> <span id="modal-material"></span><br>
                 <strong>Plant:</strong> <span id="modal-plant"></span><br>
                 <strong>Description:</strong> <span id="modal-description"></span>
             </div>
             <div id="modal-details-content">
                 <!-- Konten tabel detail (operasi/jasa) akan dimasukkan di sini oleh JavaScript -->
             </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          </div>
        </div>
      </div>
    </div>

    <audio id="upload-sound" src="{{ asset('audio/Mozart_Allegro.mp3') }}" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        window.addEventListener('load', function () {
            let processedDataByFile = @json($savedRoutings ?? []);
            let historyRoutings = @json($historyRoutings ?? []);
            let allHistoryItemsFlat = historyRoutings.flatMap(group => group.data); // Data flat untuk history

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
            const detailsModalElement = document.getElementById('routing-details-modal');
            const detailsModal = new bootstrap.Modal(detailsModalElement);
            const sapUsernameInput = document.getElementById('sap-username');
            const sapPasswordInput = document.getElementById('sap-password');
            const uploadSound = document.getElementById('upload-sound'); // [PERUBAHAN] Ambil elemen audio

            sapPasswordInput.addEventListener('focus', () => { setTimeout(() => { if (searchInput.value && searchInput.value === sapUsernameInput.value) { searchInput.value = ''; } }, 50); });

            function getFlatData() { return processedDataByFile.flatMap(group => group.data); }

            // Fungsi untuk menampilkan modal detail
            function showDetailsModal(dataItem) {
                 if (!dataItem) return;

                 // Isi data header
                 document.getElementById('modal-material').textContent = dataItem.header.IV_MATERIAL || 'N/A';
                 document.getElementById('modal-plant').textContent = dataItem.header.IV_PLANT || 'N/A';
                 document.getElementById('modal-description').textContent = dataItem.header.IV_DESCRIPTION || 'N/A';
                 document.getElementById('routingDetailsModalLabel').textContent = `Detail Routing: ${dataItem.header.IV_MATERIAL || ''}`;

                 const detailsContent = document.getElementById('modal-details-content');
                 detailsContent.innerHTML = ''; // Kosongkan konten sebelumnya

                 // Buat tabel detail (operasi atau jasa)
                 if (dataItem.services && dataItem.services.length > 0) {
                     let servicesHtml = `<h6 class="text-dark mt-3">Detail Jasa</h6><table class="table table-sm table-bordered table-striped"><thead><tr><th>Purch. Group</th><th>Deliv. Time</th><th>Price Unit</th><th>Net Price</th><th>Currency</th><th>Cost Element</th><th>Mat. Group</th></tr></thead><tbody>`;
                     dataItem.services.forEach(service => {
                         servicesHtml += `<tr><td>${service.purchasing_group || ''}</td><td>${service.pln_deliv_time || ''}</td><td>${service.price_unit || ''}</td><td>${service.net_price || ''}</td><td>${service.currency || ''}</td><td>${service.cost_element || ''}</td><td>${service.mat_grp || ''}</td></tr>`;
                     });
                     servicesHtml += '</tbody></table>';
                     detailsContent.innerHTML = servicesHtml;
                 } else if (dataItem.operations && dataItem.operations.length > 0) {

                    let operationsHtml = `<h6 class="text-dark mt-3">Detail Operasi</h6><div class="table-responsive"><table class="table table-sm table-bordered table-striped"><thead><tr><th>Op.</th><th>Work Center</th><th>Ctrl Key</th><th>Description</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;

                    dataItem.operations.forEach(op => {
                        operationsHtml += `<tr>
                            <td>${op.IV_VORNR || op.OPERATION || ''}</td>
                            <td>${op.IV_ARBPL || op.WORK_CNTR || ''}</td>
                            <td>${op.IV_STEUS || op.CONTROL_KEY || ''}</td>
                            <td>${op.IV_LTXA1 || op.DESCRIPTION || ''}</td>
                            <td>${op.IV_BMSCHX || op.BASE_QTY || ''}</td>
                            <td>${op.IV_VGW01X || op.ACTIVITY_1 || ''}</td>
                            <td>${op.IV_VGE01X || op.UOM_1 || ''}</td>
                        </tr>`;
                    });
                    operationsHtml += '</tbody></table></div>';
                    detailsContent.innerHTML = operationsHtml;
                 } else {
                     detailsContent.innerHTML = '<p class="text-muted">Tidak ada detail operasi atau jasa.</p>';
                 }

                 detailsModal.show(); // Tampilkan modal
            }


            function renderPendingTable(data = processedDataByFile, checkedIndices = new Set()) {
                resultsTbody.innerHTML = '';
                if (data.length === 0) {
                    resultsTbody.innerHTML = `<tr><td colspan="8" class="text-center fst-italic py-4">Tidak ada data.</td></tr>`;
                    return;
                }
                let globalIndex = 0;
                const flatOriginalData = getFlatData(); // Ambil data flat original sekali saja

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
                    const statusDisplayHtml = fileGroup.is_saved ? `<span class="status-cycle-btn status-${statusClass}" data-doc-number="${docNumber}" data-current-status="${fileGroup.status || ''}" title="Klik untuk ganti status">${currentStatusText.replace('None', 'Set Status')}</span>` : (fileGroup.status ? `<span class="status-badge status-badge-${statusClass}">${fileGroup.status}</span>` : '');
                    headerRow.innerHTML = `<td><input class="form-check-input document-group-checkbox" type="checkbox" data-file-index="${fileIndex}" title="Pilih semua di dokumen ini"></td><td colspan="7"><div class="doc-header-content"><div class="doc-header-left"><i class="bi bi-chevron-right"></i>${statusDisplayHtml}</div><strong class="doc-title">${fileGroup.fileName}</strong></div></td>`;
                    resultsTbody.appendChild(headerRow);

                    if(fileGroup.data && fileGroup.data.length > 0) {
                        fileGroup.data.forEach((group, itemIndex) => {
                             // Cari index asli dari data flat
                            const originalGlobalIndex = flatOriginalData.findIndex(item => item === group);

                            const hasDuplicateZP01 = (group.operations || []).filter(op => (op.IV_STEUS === 'ZP01' || op.CONTROL_KEY === 'ZP01')).length > 1;
                            const rowClass = hasDuplicateZP01 ? 'table-danger' : '';
                            const statusHtml = hasDuplicateZP01 ? `<span class="badge bg-danger">Error: Duplikat ZP01</span>` : `<span class="badge bg-secondary">Menunggu</span>`;
                            const isChecked = checkedIndices.has(originalGlobalIndex) ? 'checked' : '';

                            const detailCount = (group.services && group.services.length > 0) ? group.services.length : (group.operations || []).length;

                            const mainRow = document.createElement('tr');
                            mainRow.className = `collapse ${collapseId} ${rowClass}`;
                            mainRow.setAttribute('data-global-index', originalGlobalIndex);
                            mainRow.setAttribute('data-file-index', fileIndex);

                            mainRow.innerHTML = `
                                <td><input class="form-check-input row-checkbox" type="checkbox" data-global-index="${originalGlobalIndex}" ${hasDuplicateZP01 ? 'disabled' : ''} ${isChecked}></td>
                                <td>${itemIndex + 1}</td>
                                <td>${group.header.IV_MATERIAL}</td>
                                <td>${group.header.IV_PLANT}</td>
                                <td>${group.header.IV_DESCRIPTION}</td>
                                <td class="details-modal-trigger" data-item-source="pending" data-item-index="${originalGlobalIndex}">
                                    ${detailCount} <i class="bi bi-info-circle ms-1 small"></i>
                                </td>
                                <td class="status-cell">${statusHtml}</td>
                                <td><i class="bi bi-trash-fill delete-row-icon" data-global-index="${originalGlobalIndex}" title="Hapus baris ini"></i></td>`;
                            resultsTbody.appendChild(mainRow);

                        });
                    }
                });
                updateButtonStates();
            }

            function renderHistoryTable(data = historyRoutings) {
                historyTbody.innerHTML = '';
                 allHistoryItemsFlat = data.flatMap(group => group.data); // Update data flat history

                if (data.length === 0) {
                    historyTbody.innerHTML = `<tr><td colspan="4" class="text-center fst-italic py-3">Tidak ada histori.</td></tr>`;
                    return;
                }

                data.forEach((doc, index) => {
                    const docCollapseId = `history-collapse-${index}`;
                    const headerRow = document.createElement('tr');
                    headerRow.className = 'document-header-row collapsed';
                    headerRow.setAttribute('data-bs-toggle', 'collapse');
                    headerRow.setAttribute('data-bs-target', `#${docCollapseId}`);
                    headerRow.setAttribute('aria-expanded', 'false');
                    headerRow.innerHTML = `<td><i class="bi bi-chevron-right"></i></td><td><strong>${doc.fileName}</strong></td><td>${doc.uploaded_at || 'N/A'}</td><td>${doc.data.length}</td>`;
                    historyTbody.appendChild(headerRow);

                    const detailRow = document.createElement('tr');
                    detailRow.className = 'collapse-row'; // Tetap perlu class ini untuk collapse
                    detailRow.innerHTML = `<td colspan="4" class="p-0"><div class="collapse" id="${docCollapseId}"><div class="details-card"></div></div></td>`;

                    // Buat tabel di dalam collapse
                    let innerHtml = '<table class="table table-sm table-borderless"><thead><tr><th>Material</th><th>Description</th><th>Jml Detail</th><th>Tgl Eksekusi</th></tr></thead><tbody>';
                    doc.data.forEach(item => {
                         // Cari index item ini di data flat history
                        const historyItemIndex = allHistoryItemsFlat.findIndex(flatItem => flatItem === item);

                        const detailCount = (item.services && item.services.length > 0) ? item.services.length : (item.operations || []).length;

                        innerHtml += `<tr>
                            <td>${item.header.IV_MATERIAL}</td>
                            <td>${item.header.IV_DESCRIPTION}</td>
                            <td class="details-modal-trigger" data-item-source="history" data-item-index="${historyItemIndex}">
                                ${detailCount} <i class="bi bi-info-circle ms-1 small"></i>
                            </td>
                            <td>${item.uploaded_at_item || 'N/A'}</td>
                        </tr>`;

                    });
                    innerHtml += '</tbody></table>';

                    detailRow.querySelector('.details-card').innerHTML = innerHtml;
                    historyTbody.appendChild(detailRow);
                });
            }

            function handleDetailTriggerClick(event) {
                const trigger = event.target.closest('.details-modal-trigger');
                if (trigger) {
                    const source = trigger.dataset.itemSource;
                    const index = parseInt(trigger.dataset.itemIndex, 10);
                    let dataItem;
                    if (source === 'pending') {
                         const flatData = getFlatData();
                         dataItem = flatData[index];
                    } else if (source === 'history') {
                         dataItem = allHistoryItemsFlat[index];
                    }
                    showDetailsModal(dataItem);
                }
            }
            resultsTbody.addEventListener('click', handleDetailTriggerClick);
            historyTbody.addEventListener('click', handleDetailTriggerClick);


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
                    if (docGroup) { docGroup.status = status; }
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
                 const flatData = getFlatData(); // Ambil data flat sebelum modifikasi
                 const itemsToDelete = Array.from(indicesToDeleteSet).map(index => flatData[index]);

                 processedDataByFile = processedDataByFile.map(fileGroup => {
                    // Filter data dalam grup berdasarkan item yang *tidak* ada di itemsToDelete
                     const newData = fileGroup.data.filter(item => !itemsToDelete.includes(item));
                    return { ...fileGroup, data: newData };
                 }).filter(fileGroup => fileGroup.data.length > 0); // Hapus grup jika kosong

                renderPendingTable(); // Render ulang
            }

            function updateButtonStates() {
                const checkedRowCount = document.querySelectorAll('.row-checkbox:checked:not(:disabled)').length;
                const checkedDocCount = document.querySelectorAll('.document-group-checkbox:checked').length;
                const totalChecked = checkedRowCount + checkedDocCount;
                deleteSelectedBtn.disabled = totalChecked === 0;
                uploadSelectedBtn.disabled = checkedRowCount === 0;
                saveSelectedBtn.disabled = checkedRowCount === 0;
                const allRowCheckboxes = document.querySelectorAll('.row-checkbox:not(:disabled)');
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
                 document.querySelectorAll(`tr[data-file-index="${fileIndex}"].collapse .row-checkbox:not(:disabled)`).forEach(child => child.checked = isChecked);
                 updateButtonStates(); // Update tombol setelah check grup
            }

            function handleRowCheck(rowCheckbox) {
                const fileIndex = rowCheckbox.closest('tr').dataset.fileIndex;
                const masterCheckbox = document.querySelector(`.document-group-checkbox[data-file-index="${fileIndex}"]`);
                if (!masterCheckbox) return;
                const allChilds = document.querySelectorAll(`tr[data-file-index="${fileIndex}"].collapse .row-checkbox:not(:disabled)`);
                const checkedChilds = document.querySelectorAll(`tr[data-file-index="${fileIndex}"].collapse .row-checkbox:checked:not(:disabled)`);
                masterCheckbox.checked = allChilds.length > 0 && checkedChilds.length === allChilds.length;
                masterCheckbox.indeterminate = checkedChilds.length > 0 && checkedChilds.length < allChilds.length;
                updateButtonStates(); // Update tombol setelah check baris
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
                const allIndicesToUpdateView = new Set(); // Hanya index yg akan dihapus dari view
                const itemsToDeleteFromMemory = []; // Data objek yang akan dihapus dari memory

                checkedDocBoxes.forEach(docBox => {
                    const fileIndex = docBox.dataset.fileIndex;
                    const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                    // Cari semua baris dalam grup ini
                    document.querySelectorAll(`tr[data-file-index="${fileIndex}"].collapse`).forEach(row => {
                         const globalIndex = parseInt(row.dataset.globalIndex);
                         if (!isNaN(globalIndex)) {
                             allIndicesToUpdateView.add(globalIndex);
                             itemsToDeleteFromMemory.push(allItems[globalIndex]); // Tambahkan objeknya
                         }
                    });
                    // Jika dokumen ini sudah disimpan, tandai untuk dihapus dari DB
                    if (headerRow && headerRow.dataset.isSaved === 'true') {
                        const docNumber = headerRow.dataset.docNumber;
                        if(docNumber) docsToDelete.add(docNumber);
                    }
                });

                checkedRowBoxes.forEach(rowBox => {
                    const globalIndex = parseInt(rowBox.dataset.globalIndex);
                    if (allIndicesToUpdateView.has(globalIndex)) return; // Sudah ditandai oleh check grup

                    allIndicesToUpdateView.add(globalIndex);
                    itemsToDeleteFromMemory.push(allItems[globalIndex]); // Tambahkan objeknya

                    // Cari grup file terkait
                    const rowElement = rowBox.closest('tr');
                    const fileIndex = rowElement.dataset.fileIndex;
                    const fileGroupHeader = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);

                    // Jika baris ini berasal dari dokumen yang sudah disimpan, tandai untuk dihapus dari DB
                    if (fileGroupHeader && fileGroupHeader.dataset.isSaved === 'true') {
                        const docNumber = fileGroupHeader.dataset.docNumber;
                        const material = allItems[globalIndex].header.IV_MATERIAL;
                        if(docNumber && material) {
                             rowsToDeleteFromDb.push({ doc_number: docNumber, material: material });
                        }
                    }
                });

                let confirmText = docsToDelete.size > 0 || rowsToDeleteFromDb.length > 0 ? 'Item yang dipilih akan dihapus permanen dari database. Lanjutkan?' : 'Hapus item yang dipilih dari tampilan?';
                const result = await Swal.fire({ title: 'Konfirmasi Penghapusan', text: confirmText, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: 'Batal', confirmButtonText: 'Ya, Hapus!' });

                if (result.isConfirmed) {
                    let allSuccess = true;
                    // Hapus dari DB jika perlu
                    if (docsToDelete.size > 0 || rowsToDeleteFromDb.length > 0) {
                        try {
                            if (docsToDelete.size > 0) {
                                const response = await fetch("{{ route('routing.delete') }}", {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                    body: JSON.stringify({ document_numbers: Array.from(docsToDelete) })
                                });
                                if (!response.ok) throw new Error('Gagal menghapus dokumen dari database.');
                            }
                            if (rowsToDeleteFromDb.length > 0) {
                                // Filter rowsToDeleteFromDb: hanya hapus baris jika dokumennya TIDAK ikut dihapus
                                const finalRowsToDelete = rowsToDeleteFromDb.filter(row => !docsToDelete.has(row.doc_number));
                                if (finalRowsToDelete.length > 0) {
                                    const response = await fetch("{{ route('routing.deleteRows') }}", {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                        body: JSON.stringify({ rows_to_delete: finalRowsToDelete })
                                    });
                                    if (!response.ok) throw new Error('Gagal menghapus baris dari database.');
                                }
                            }
                        } catch (error) {
                            allSuccess = false;
                            Swal.fire({title: 'Error Database!', text: error.message, icon: 'error'});
                        }
                    }

                    // Jika operasi DB berhasil (atau tidak diperlukan), hapus dari tampilan/memory
                    if (allSuccess) {
                         // Hapus item dari processedDataByFile berdasarkan objek
                         processedDataByFile = processedDataByFile.map(fileGroup => {
                            const newData = fileGroup.data.filter(item => !itemsToDeleteFromMemory.includes(item));
                            return { ...fileGroup, data: newData };
                        }).filter(fileGroup => fileGroup.data.length > 0); // Hapus grup jika kosong

                        renderPendingTable(); // Render ulang tabel
                        Swal.fire({title: 'Dihapus!', text: 'Item yang dipilih telah dihapus.', icon: 'success'});
                    }
                }
            }


            async function performUpload() {
                const username = document.getElementById('sap-username').value;
                const password = document.getElementById('sap-password').value;
                if (!username || !password) return Swal.fire({title: 'Peringatan', text: 'Username dan Password SAP harus diisi.', icon: 'warning'});

                uploadModal.hide();

                // [PERUBAHAN] Putar audio saat release dimulai
                if (uploadSound) {
                    uploadSound.currentTime = 0;
                    uploadSound.play().catch(error => console.warn("Pemutaran audio diblokir:", error));
                }

                const allItems = getFlatData();
                const itemsToUpload = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);
                const totalItems = itemsToUpload.length;
                let successCount = 0, failCount = 0, processedCount = 0;
                const successfulUploads = [];
                const statusText = document.getElementById('progress-status-text');
                const progressBar = document.getElementById('upload-progress-bar');
                statusText.textContent = `Memulai... 0 / ${totalItems} berhasil`;
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                progressModal.show();

                try { // [PERUBAHAN] Tambahkan try...finally di sekitar loop
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
                                const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                                if (headerRow && headerRow.dataset.isSaved === 'true') {
                                    successfulUploads.push({
                                        material: routingData.header.IV_MATERIAL,
                                        doc_number: headerRow.dataset.docNumber
                                    });
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
                } finally { // [PERUBAHAN] Hentikan audio di finally
                     progressModal.hide();
                     if (uploadSound) {
                         uploadSound.pause();
                         uploadSound.currentTime = 0;
                     }
                    // Tampilkan hasil setelah audio berhenti
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
            }


            renderPendingTable();
            renderHistoryTable();

            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const filterData = (sourceData) => { // Fungsi filter generik
                    return sourceData.map(fileGroup => {
                        // Cek match di nama file/dokumen
                        if ((fileGroup.fileName || '').toLowerCase().includes(searchTerm)) { return fileGroup; }
                        // Filter item di dalam dokumen
                        const filteredItems = (fileGroup.data || []).filter(item => {
                            const materialMatch = (item.header.IV_MATERIAL || '').toLowerCase().includes(searchTerm);
                            const descriptionMatch = (item.header.IV_DESCRIPTION || '').toLowerCase().includes(searchTerm);
                            // Cek match di work center dalam operasi
                            const workCenterMatch = (item.operations || []).some(op =>
                                (op.IV_ARBPL || op.WORK_CNTR || '').toLowerCase().includes(searchTerm)
                            );
                            return materialMatch || descriptionMatch || workCenterMatch;
                        });
                        // Jika ada item yang match, kembalikan grup dengan item yang terfilter
                        if (filteredItems.length > 0) { return { ...fileGroup, data: filteredItems }; }
                        // Jika tidak ada match sama sekali di grup ini
                        return null;
                    }).filter(Boolean); // Hapus grup yang null (tidak ada match sama sekali)
                };
                renderPendingTable(filterData(processedDataByFile));
                renderHistoryTable(filterData(historyRoutings));
            });


            uploadForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const fileInput = document.getElementById('routing_file');
                if (!fileInput.files[0]) return Swal.fire({title: 'Peringatan', text: 'Silakan pilih file terlebih dahulu.', icon: 'warning'});

                // [PERUBAHAN] Hapus pemutaran audio di sini
                // const uploadSound = document.getElementById('upload-sound');
                // if (uploadSound) { ... }

                processBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
                processBtn.disabled = true;
                try {
                    const formData = new FormData(uploadForm);
                    const response = await fetch("{{ route('routing.processFile') }}", {
                        method: 'POST', body: formData,
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json'}
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || 'Gagal memproses file di server.');
                    processedDataByFile.push(result);
                    renderPendingTable(); // Render ulang tabel pending
                    Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, title: 'File berhasil diproses!', icon: 'success' });
                } catch (error) {
                    Swal.fire({title: 'Error!', html: error.message, icon: 'error'});
                } finally {
                    processBtn.innerHTML = `<i class="bi bi-gear-fill"></i>`;
                    processBtn.disabled = false;
                    uploadForm.reset();
                }
            });

            deleteSelectedBtn.addEventListener('click', performDeletion);
            saveSelectedBtn.addEventListener('click', () => saveModal.show());
            uploadSelectedBtn.addEventListener('click', () => { uploadModal.show(); });
            confirmSaveBtn.addEventListener('click', () => {
                const docName = document.getElementById('document-name').value;
                const prodName = document.getElementById('product-name').value;
                if (!docName || !prodName) return Swal.fire({title: 'Peringatan', text: 'Nama Dokumen dan Nama Produk harus diisi.', icon: 'warning'});
                saveModal.hide();
                performSave(docName, prodName);
            });
            confirmUploadBtn.addEventListener('click', performUpload); // [PERUBAHAN] Tetap panggil performUpload

            selectAllCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                // Hanya check/uncheck checkbox di tabel pending
                document.querySelectorAll('#results-tbody .document-group-checkbox, #results-tbody .row-checkbox:not(:disabled)').forEach(cb => cb.checked = isChecked);
                updateButtonStates();
            });

             // Event listener hanya untuk tabel pending (resultsTbody)
            resultsTbody.addEventListener('change', (e) => {
                 const target = e.target;
                 if (target.classList.contains('document-group-checkbox')) {
                     handleDocumentGroupCheck(target);
                 } else if (target.classList.contains('row-checkbox')) {
                     handleRowCheck(target);
                 }
                 // Tidak perlu updateButtonStates di sini karena sudah dipanggil di dalam handleDocumentGroupCheck dan handleRowCheck
             });


             // Event listener klik untuk tabel pending (resultsTbody)
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
                     if (docNumber) { updateDocumentStatusOnServer(docNumber, newStatus); }
                     return;
                 }
                 const deleteBtn = e.target.closest('.delete-row-icon');
                 if (deleteBtn) {
                     // Uncheck semua dulu
                     document.querySelectorAll('#results-tbody .row-checkbox, #results-tbody .document-group-checkbox').forEach(cb => cb.checked = false);
                     // Check baris yang diklik
                     const globalIndex = parseInt(deleteBtn.dataset.globalIndex);
                     const targetCheckbox = document.querySelector(`#results-tbody .row-checkbox[data-global-index="${globalIndex}"]`);
                     if (targetCheckbox) targetCheckbox.checked = true;
                     updateButtonStates(); // Update tombol sebelum konfirmasi
                     performDeletion(); // Panggil fungsi delete
                     return;
                 }
                 // Handle collapse/expand untuk grup dokumen
                 const headerRow = e.target.closest('.document-header-row');
                 if (headerRow) {
                     const targetSelector = headerRow.getAttribute('data-bs-target');
                     if (targetSelector) {
                          // Pastikan kita hanya men-toggle baris di dalam #results-tbody
                         document.querySelectorAll(`#results-tbody tr.collapse${targetSelector.substring(1)}`).forEach(element => {
                            const instance = bootstrap.Collapse.getOrCreateInstance(element);
                             instance.toggle();
                         });
                     }
                     const isCollapsed = headerRow.classList.toggle('collapsed');
                     headerRow.setAttribute('aria-expanded', !isCollapsed);
                 }
             });


            // Toggle All Details hanya untuk tabel pending
            const toggleAllDetailsCheckbox = document.getElementById('toggle-all-details-checkbox');
            if(toggleAllDetailsCheckbox) {
                toggleAllDetailsCheckbox.addEventListener('change', (e) => {
                    const isChecked = e.target.checked;
                    // Fungsi ini sekarang tidak relevan karena detail ada di modal
                    console.log("Toggle all details (di tabel pending) tidak lagi berfungsi karena detail dipindah ke modal.");

                });
            }
        });
    </script>
</body>
</html>

