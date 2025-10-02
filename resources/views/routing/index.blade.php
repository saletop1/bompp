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
    <style>
        body { background-image: url("{{ asset('images/ainun.jpg') }}"); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh; padding-top: 1rem; padding-bottom: 1rem; }
        .card { background: rgba(75, 74, 74, 0.33); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1); color: white; }
        .converter-container { max-width: 1200px; margin-top: 0; margin-bottom: 1rem; }
        .page-title-container { text-align: center; }
        .page-title-container .main-title { color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); margin-bottom: 0; }
        .page-title-container .subtitle { color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); font-size: 0.9rem; }
        .sap-logo-header { height: 80px; width: auto; margin-left: 20px; }
        .nav-pills .nav-link { background-color: rgba(255, 255, 255, 0.2); color: #f0f0f0; margin: 0 5px; transition: all 0.3s ease; border: 1px solid transparent; }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link { background-color: #ffffff64; color: #08e6ffd8; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .table { color: white; } .table th { border-bottom-color: rgba(255,255,255,0.3); } .table td, .table th { border-top-color: rgba(255,255,255,0.2); }
        .details-toggle { cursor: pointer; font-weight: bold; user-select: none; }
        .details-toggle:hover { color: #08e6ffd8; }
        .collapse-row > td { padding: 0 !important; border: none !important; background-color: transparent !important; }
        .details-card { background: rgba(0,0,0,0.3); }
        .table-danger, .table-danger > th, .table-danger > td { --bs-table-bg: #dc354566; color: white; font-weight: bold; }
        .delete-row-icon { cursor: pointer; }
        .delete-row-icon:hover { color: #dc3545; }
        .document-header-row {
            background-color: rgba(255, 255, 255, 0.15) !important;
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            font-weight: bold;
            color: #FFFFF0; /* Ivory White */
        }
        .document-header-row > td { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; vertical-align: middle; font-style: normal !important; }

        .status-flag { font-size: 1.2rem; margin-right: 8px; vertical-align: middle; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .status-urgent { color: #dc3545; }
        .status-priority { color: #fd7e14; }
        .status-standart { color: #ffc107; }
        .status-none, .status- { color: #6c757d; }

        .status-badge {
            display: inline-block;
            padding: 0.3em 0.6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            color: #fff;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
            margin-right: 0.75rem;
        }
        .status-badge-urgent { background-color: #dc3545; }
        .status-badge-priority { background-color: #fd7e14; }
        .status-badge-standart { background-color: #ffc107; color: #212529; }

        .status-dropdown .btn {
            background-color: transparent !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            display: flex;
            align-items: center;
            width: 160px;
            justify-content: space-between;
        }
        .status-dropdown .btn:focus { box-shadow: 0 0 0 .25rem rgba(13,110,253,.25); }

        .status-dropdown .dropdown-menu {
            background-color: #f1f1f1;
            border-radius: .5rem;
            padding: .5rem 0;
            color: #212529;
        }
        .status-dropdown .dropdown-menu a.dropdown-item {
            color: inherit !important;
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        .status-dropdown .dropdown-item:hover { background-color: #e2e6ea; }
        .current-status-text { flex-grow: 1; text-align: left; }

        .progress-bar { transition: width 0.4s ease; }
        .table-responsive { max-height: 65vh; overflow-y: auto; }
        thead th { position: sticky; top: 0; background-color: #212529; z-index: 10; }
        .swal2-container { background-color: rgba(0, 0, 0, 0.5) !important; }
        .swal2-popup { background: rgba(52, 58, 64, 0.5) !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; border-radius: 1rem !important; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.15) !important; }
        .swal2-title, .swal2-content, .swal2-html-container { color: #ffffff !important; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .swal2-toast { background: #f8f9fa !important; box-shadow: 0 4px 15px 0 rgba(0,0,0,0.25) !important; border: 1px solid #dee2e6 !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }
        .swal2-toast .swal2-title { color: #212529 !important; text-shadow: none !important; }
        #sap-credential-modal .modal-content, #save-details-modal .modal-content { background: rgba(52, 58, 64, 0.5) !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; border: 1px solid rgba(255, 255, 255, 0.2) !important; border-radius: 1rem !important; color: #ffffff; }
        #sap-credential-modal .modal-header, #save-details-modal .modal-header { border-bottom-color: rgba(255, 255, 255, 0.2); }
        #sap-credential-modal .modal-footer, #save-details-modal .modal-footer { border-top-color: rgba(255, 255, 255, 0.2); }
        #sap-credential-modal .form-control, #save-details-modal .form-control { background-color: rgba(255, 255, 255, 0.9); color: #212529; }

        .form-check-input {
            width: 1.25em;
            height: 1.25em;
            margin-top: 0.1em;
            vertical-align: top;
            background-color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.75);
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .form-check-input:checked {
            background-color: #198754;
            border-color: #146c43;
        }
        .form-check-input:focus {
            box-shadow: 0 0 0 .25rem rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }

    </style>
</head>
<body>
    <div class="container converter-container">
        <!-- Header and Nav Pills -->
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Routing Data Center</h1>
                <p class="subtitle mb-0">Confirm & Release Routing Data</p>
            </div>
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
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h4 class="mb-0 me-auto">Preview Data Routing</h4>
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
                <div id="results-container" class="mt-2" style="display: block;">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
                                    <th>#</th><th>Material</th><th>Plant</th><th>Description</th><th>Jml Operasi</th><th>Status</th><th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="results-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <footer class="text-center text-white mt-4"><small>© PT. Kayu Mebel Indonesia, 2025</small></footer>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="sap-credential-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Masukkan Kredensial SAP</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="sap-username" class="form-label">SAP Username</label><input type="text" class="form-control" id="sap-username"></div><div class="mb-3"><label for="sap-password" class="form-label">SAP Password</label><input type="password" class="form-control" id="sap-password"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-upload-btn">Konfirmasi & Mulai Upload</button></div></div></div></div>
    <div class="modal fade" id="save-details-modal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detail Dokumen</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label for="document-name" class="form-label">Nama Dokumen</label><input type="text" class="form-control" id="document-name" placeholder="Contoh: Routing Pintu Depan" required maxlength="40"></div><div class="mb-3"><label for="product-name" class="form-label">Nama Produk</label><input type="text" class="form-control" id="product-name" placeholder="Contoh: Pintu Jati Model A" required maxlength="20"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="confirm-save-btn">Konfirmasi & Simpan</button></div></div></div></div>
    <div class="modal fade" id="upload-progress-modal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-dark"><div class="modal-header"><h5 class="modal-title">Mengunggah Routing ke SAP...</h5></div><div class="modal-body"><p id="progress-status-text" class="text-center mb-2">Menunggu...</p><div class="progress" role="progressbar" style="height: 25px;"><div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 0%">0%</div></div></div></div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const savedRoutings = @json($savedRoutings ?? []);
            let processedDataByFile = savedRoutings;
            let uploadModal, saveModal, progressModal;
            const uploadForm = document.getElementById('upload-form');
            const processBtn = document.getElementById('process-btn');
            const resultsContainer = document.getElementById('results-container');
            const uploadSelectedBtn = document.getElementById('upload-selected-btn');
            const saveSelectedBtn = document.getElementById('save-selected-btn');
            const deleteSelectedBtn = document.getElementById('delete-selected-btn');
            const confirmUploadBtn = document.getElementById('confirm-upload-btn');
            const confirmSaveBtn = document.getElementById('confirm-save-btn');
            const resultsTbody = document.getElementById('results-tbody');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            uploadModal = new bootstrap.Modal(document.getElementById('sap-credential-modal'));
            saveModal = new bootstrap.Modal(document.getElementById('save-details-modal'));
            progressModal = new bootstrap.Modal(document.getElementById('upload-progress-modal'));

            function renderTable(checkedIndices = new Set()) {
                const tbody = document.getElementById('results-tbody');
                tbody.innerHTML = '';
                let globalIndex = 0;
                processedDataByFile.forEach((fileGroup, fileIndex) => {
                    const isSaved = fileGroup.is_saved;
                    const docNumber = fileGroup.document_number || null;
                    const currentStatus = fileGroup.status || '';
                    const statusDropdownHtml = `
                        <div class="dropdown status-dropdown">
                            <button class="btn btn-sm dropdown-toggle" type="button" id="dropdownMenuButton${fileIndex}" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="status-flag status-${currentStatus.toLowerCase() || 'none'}">▼</span>
                                <span class="current-status-text">${currentStatus || 'Pilih Status'}</span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton${fileIndex}">
                                <li><a class="dropdown-item status-option" href="#" data-status="Urgent" data-file-index="${fileIndex}"><span class="status-flag status-urgent">▼</span> Urgent</a></li>
                                <li><a class="dropdown-item status-option" href="#" data-status="Priority" data-file-index="${fileIndex}"><span class="status-flag status-priority">▼</span> Priority</a></li>
                                <li><a class="dropdown-item status-option" href="#" data-status="Standart" data-file-index="${fileIndex}"><span class="status-flag status-standart">▼</span> Standart</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item status-option" href="#" data-status="" data-file-index="${fileIndex}"><span class="status-flag status-none">▼</span> Hapus Status</a></li>
                            </ul>
                        </div>
                    `;

                    let statusBadgeHtml = '';
                    if (currentStatus) {
                        statusBadgeHtml = `<span class="status-badge status-badge-${currentStatus.toLowerCase()}">${currentStatus.toUpperCase()}</span>`;
                    }

                    const headerRow = `
                        <tr class="document-header-row" data-file-index="${fileIndex}" data-is-saved="${isSaved}" data-doc-number="${docNumber || ''}">
                            <td><input class="form-check-input document-group-checkbox" type="checkbox" data-file-index="${fileIndex}" title="Pilih semua di dokumen ini"></td>
                            <td colspan="2">${statusDropdownHtml}</td>
                            <td colspan="5">${statusBadgeHtml}${fileGroup.fileName}</td>
                        </tr>`;
                    tbody.innerHTML += headerRow;
                    if(fileGroup.data && fileGroup.data.length > 0) {
                        fileGroup.data.forEach((group, itemIndex) => {
                            const detailsId = `details-${globalIndex}`;
                            const hasDuplicateZP01 = (group.operations || []).filter(op => (op.IV_STEUS === 'ZP01' || op.CONTROL_KEY === 'ZP01')).length > 1;
                            const rowClass = hasDuplicateZP01 ? 'table-danger' : '';
                            const statusHtml = hasDuplicateZP01 ? `<span class="badge bg-danger">Error: Duplikat ZP01</span>` : `<span class="badge bg-secondary">Menunggu</span>`;
                            const isChecked = checkedIndices.has(globalIndex) ? 'checked' : '';
                            const mainRow = `
                                <tr data-global-index="${globalIndex}" data-file-index="${fileIndex}" class="${rowClass}">
                                    <td><input class="form-check-input row-checkbox" type="checkbox" data-global-index="${globalIndex}" ${hasDuplicateZP01 ? 'disabled' : ''} ${isChecked}></td>
                                    <td>${itemIndex + 1}</td>
                                    <td>${group.header.IV_MATERIAL}</td>
                                    <td>${group.header.IV_PLANT}</td>
                                    <td>${group.header.IV_DESCRIPTION}</td>
                                    <td class="details-toggle" data-bs-toggle="collapse" data-bs-target="#${detailsId}">${(group.operations || []).length} <i class="bi bi-chevron-down ms-1 small"></i></td>
                                    <td class="status-cell">${statusHtml}</td>
                                    <td><i class="bi bi-trash-fill delete-row-icon" data-global-index="${globalIndex}" title="Hapus baris ini"></i></td>
                                </tr>`;
                            let operationsHtml = `<table class="table table-dark table-sm mb-0"><thead><tr><th>Work Center</th><th>Ctrl Key</th><th>Description</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;
                            (group.operations || []).forEach(op => {
                                operationsHtml += `<tr>
                                    <td>${op.IV_ARBPL || op.WORK_CNTR || ''}</td>
                                    <td>${op.IV_STEUS || op.CONTROL_KEY || ''}</td>
                                    <td>${op.IV_LTXA1 || op.DESCRIPTION || ''}</td>
                                    <td>${op.IV_BMSCHX || op.BASE_QTY || ''}</td>
                                    <td>${op.IV_VGW01X || op.ACTIVITY_1 || ''}</td>
                                    <td>${op.IV_VGE01X || op.UOM_1 || ''}</td>
                                </tr>`;
                            });
                            operationsHtml += `</tbody></table>`;
                            const detailsRow = `<tr class="collapse-row"><td colspan="8"><div class="collapse" id="${detailsId}"><div class="p-3 details-card">${operationsHtml}</div></div></td></tr>`;
                            tbody.innerHTML += mainRow + detailsRow;
                            globalIndex++;
                        });
                    }
                });
                updateButtonStates();
            }

            function getFlatData() { return processedDataByFile.flatMap(group => group.data); }

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
                renderTable();
            }

            async function updateDocumentStatusOnServer(document_number, status) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-start',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                });
                try {
                    const response = await fetch("{{ route('routing.updateStatus') }}", {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                        body: JSON.stringify({ document_number, status: status || null })
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message);
                    Toast.fire({ icon: 'success', title: 'Status diperbarui' });
                    renderTable();
                } catch (error) {
                    console.error('Gagal menyimpan status:', error);
                    Toast.fire({ icon: 'error', title: 'Gagal menyimpan status' });
                }
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
                const childCheckboxes = document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:not(:disabled)`);
                childCheckboxes.forEach(child => child.checked = isChecked);
            }

            function handleRowCheck(rowCheckbox) {
                const fileIndex = rowCheckbox.closest('tr').dataset.fileIndex;
                const masterCheckbox = document.querySelector(`.document-group-checkbox[data-file-index="${fileIndex}"]`);
                if (!masterCheckbox) return;
                const allChildCheckboxes = document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:not(:disabled)`);
                const checkedChildCheckboxes = document.querySelectorAll(`tr[data-file-index="${fileIndex}"] .row-checkbox:checked:not(:disabled)`);
                if (allChildCheckboxes.length > 0 && checkedChildCheckboxes.length === allChildCheckboxes.length) {
                    masterCheckbox.checked = true;
                    masterCheckbox.indeterminate = false;
                } else if (checkedChildCheckboxes.length > 0) {
                    masterCheckbox.checked = false;
                    masterCheckbox.indeterminate = true;
                } else {
                    masterCheckbox.checked = false;
                    masterCheckbox.indeterminate = false;
                }
            }

            async function performDeletion() {
                const allItems = getFlatData();
                const checkedRowBoxes = document.querySelectorAll('.row-checkbox:checked');
                const checkedDocBoxes = document.querySelectorAll('.document-group-checkbox:checked');
                if (checkedRowBoxes.length === 0 && checkedDocBoxes.length === 0) {
                    return Swal.fire({title: 'Info', text: 'Tidak ada item yang dipilih untuk dihapus.', icon: 'info'});
                }
                const docsToDelete = new Set();
                const rowsToDeleteFromDb = [];
                const allIndicesToUpdateView = new Set();
                checkedDocBoxes.forEach(docBox => {
                    const fileIndex = docBox.dataset.fileIndex;
                    const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                    if (headerRow.dataset.isSaved === 'true') {
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
                    const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                    if (headerRow.dataset.isSaved === 'true') {
                        const docNumber = headerRow.dataset.docNumber;
                        const material = allItems[globalIndex].header.IV_MATERIAL;
                        rowsToDeleteFromDb.push({ doc_number: docNumber, material: material });
                    }
                });
                let confirmText = 'Hapus item yang dipilih dari tampilan?';
                if (docsToDelete.size > 0 || rowsToDeleteFromDb.length > 0) {
                    confirmText = 'Item yang dipilih akan dihapus permanen dari database. Lanjutkan?';
                }
                const result = await Swal.fire({
                    title: 'Konfirmasi Penghapusan', text: confirmText, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
                });
                if (result.isConfirmed) {
                    let allSuccess = true;
                    let errorMessages = [];
                    if (docsToDelete.size > 0) {
                         try {
                            const response = await fetch("{{ route('routing.delete') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: JSON.stringify({ document_numbers: Array.from(docsToDelete) })
                            });
                            if (!response.ok) throw new Error((await response.json()).message || 'Gagal menghapus dokumen.');
                        } catch(error) {
                            allSuccess = false;
                            errorMessages.push(`Gagal hapus dokumen: ${error.message}`);
                        }
                    }
                    if (rowsToDeleteFromDb.length > 0) {
                        try {
                            const response = await fetch("{{ route('routing.deleteRows') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                                body: JSON.stringify({ rows_to_delete: rowsToDeleteFromDb })
                            });
                            if (!response.ok) throw new Error((await response.json()).message || 'Gagal menghapus baris.');
                        } catch (error) {
                            allSuccess = false;
                            errorMessages.push(`Gagal hapus baris: ${error.message}`);
                        }
                    }
                    if (allSuccess) {
                        deleteItemsByGlobalIndices(allIndicesToUpdateView);
                        Swal.fire({title: 'Dihapus!', text: 'Item yang dipilih telah dihapus.', icon: 'success'});
                    } else {
                        Swal.fire({title: 'Error!', text: errorMessages.join('\n'), icon: 'error'});
                    }
                }
            }

            async function performSave(docName, prodName) {
                const allItems = getFlatData();
                const selectedCheckboxes = Array.from(document.querySelectorAll('.row-checkbox:checked'));
                const selectedItems = selectedCheckboxes.map(cb => allItems[cb.getAttribute('data-global-index')]);
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
                    console.error("Save error:", error);
                    Swal.fire({title: 'Error!', text: error.message, icon: 'error'});
                } finally {
                    saveSelectedBtn.disabled = false;
                    confirmSaveBtn.disabled = false;
                    document.getElementById('document-name').value = '';
                    document.getElementById('product-name').value = '';
                }
            }

            if (processedDataByFile.length > 0) {
                renderTable();
            }

            uploadForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                processBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;
                processBtn.disabled = true;
                try {
                    const response = await fetch("{{ route('routing.processFile') }}", {
                        method: 'POST', body: new FormData(this),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json'}
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.error || 'Gagal memproses file.');

                    processedDataByFile.push(result);
                    renderTable();
                } catch (error) {
                    console.error("Upload error:", error);
                    Swal.fire({title: 'Error!', text: error.message, icon: 'error'});
                } finally {
                    processBtn.innerHTML = `<i class="bi bi-gear-fill"></i>`;
                    processBtn.disabled = false;
                    uploadForm.reset();
                }
            });

            saveSelectedBtn.addEventListener('click', () => saveModal.show());
            uploadSelectedBtn.addEventListener('click', () => uploadModal.show());

            confirmSaveBtn.addEventListener('click', async () => {
                const docName = document.getElementById('document-name').value;
                const prodName = document.getElementById('product-name').value;
                if (!docName || !prodName) {
                    return Swal.fire({title: 'Peringatan', text: 'Nama Dokumen dan Nama Produk harus diisi.', icon: 'warning'});
                }
                saveModal.hide();
                await performSave(docName, prodName);
            });

            confirmUploadBtn.addEventListener('click', async function() {
                const username = document.getElementById('sap-username').value;
                const password = document.getElementById('sap-password').value;
                if (!username || !password) return Swal.fire({title: 'Peringatan', text: 'Username dan Password SAP harus diisi.', icon: 'warning'});
                uploadModal.hide();
                const allItems = getFlatData();
                const itemsToUpload = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);
                const totalItems = itemsToUpload.length;
                let successCount = 0, failCount = 0, processedCount = 0;
                const successfulUploads = [];
                const successfulIndices = new Set();
                const progressBar = document.getElementById('upload-progress-bar');
                const statusText = document.getElementById('progress-status-text');
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
                statusText.textContent = `Memulai... 0 / ${totalItems} berhasil`;
                progressModal.show();

                for (const routingData of itemsToUpload) {
                    processedCount++;
                    const globalIndex = allItems.findIndex(item => item === routingData);
                    const targetRow = document.querySelector(`tr[data-global-index="${globalIndex}"]`);
                    const statusCell = targetRow.querySelector('.status-cell');
                    statusCell.innerHTML = `<span class="spinner-border spinner-border-sm text-warning"></span> Menciptakan...`;

                    try {
                        const createResponse = await fetch("{{ route('api.routing.uploadToSap') }}", {
                            method: 'POST', body: JSON.stringify({ username, password, routing_data: routingData }),
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'}
                        });

                        const createResult = await createResponse.json();
                        if (createResponse.ok && createResult.status?.toLowerCase() === 'success') {
                            statusCell.innerHTML = `<span class="badge bg-success">Success</span>`;
                            successCount++;
                            successfulIndices.add(globalIndex);
                             const fileIndex = targetRow.dataset.fileIndex;
                            const fileGroup = processedDataByFile[fileIndex];
                            if (fileGroup.is_saved) {
                                successfulUploads.push({ material: routingData.header.IV_MATERIAL, doc_number: fileGroup.document_number });
                            }
                        } else {
                            failCount++;
                            let errorMessage = createResult.message || createResult.error || 'Gagal menciptakan routing awal.';
                            if (typeof createResult.details === 'object' && createResult.details !== null) {
                                errorMessage += ' Detail: ' + JSON.stringify(createResult.details);
                            } else if (createResult.details) {
                                errorMessage += ' Detail: ' + createResult.details;
                            }
                            statusCell.innerHTML = `<span class="badge bg-danger" title="${errorMessage}">Create Failed</span>`;
                        }
                    } catch (error) {
                        failCount++;
                        console.error("Release error:", error);
                        statusCell.innerHTML = `<span class="badge bg-danger" title="${error.message}">Error</span>`;
                    } finally {
                        const percentage = Math.round((processedCount / totalItems) * 100);
                        progressBar.style.width = percentage + '%';
                        progressBar.textContent = percentage + '%';
                        statusText.textContent = `${successCount} / ${totalItems} material berhasil diupload`;
                    }
                }

                progressModal.hide();
                if (successfulUploads.length > 0) {
                    try {
                        await fetch("{{ route('routing.markAsUploaded') }}", {
                            method: 'POST',
                            body: JSON.stringify({ successful_uploads: successfulUploads }),
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json'}
                        });
                        deleteItemsByGlobalIndices(successfulIndices);
                    } catch (error) {
                        console.error('Gagal menandai data sebagai ter-upload:', error);
                    }
                }
                if (processedCount > 0) {
                     Swal.fire({title: 'Proses Selesai', text: `Upload selesai: ${successCount} berhasil, ${failCount} gagal.`, icon: 'info'});
                }
            });

            resultsTbody.addEventListener('click', e => {
                if (e.target.classList.contains('delete-row-icon')) {
                    const globalIndex = parseInt(e.target.dataset.globalIndex);
                    document.querySelectorAll('.row-checkbox, .document-group-checkbox').forEach(cb => cb.checked = false);
                    const targetCheckbox = document.querySelector(`.row-checkbox[data-global-index="${globalIndex}"]`);
                    if(targetCheckbox) targetCheckbox.checked = true;
                    performDeletion();
                }

                const statusOption = e.target.closest('.status-option');
                if (statusOption) {
                    e.preventDefault();
                    const fileIndex = statusOption.dataset.fileIndex;
                    const newStatus = statusOption.dataset.status;
                    processedDataByFile[fileIndex].status = newStatus;

                    const headerRow = document.querySelector(`.document-header-row[data-file-index="${fileIndex}"]`);
                    const isSaved = headerRow.dataset.isSaved === 'true';
                    const docNumber = headerRow.dataset.docNumber;
                    if (isSaved && docNumber) {
                        updateDocumentStatusOnServer(docNumber, newStatus);
                    } else {
                        renderTable();
                    }
                }
            });

            // [DIKEMBALIKAN] Event listener untuk semua tombol utama
            deleteSelectedBtn.addEventListener('click', () => performDeletion());

            selectAllCheckbox.addEventListener('change', (e) => {
                const isChecked = e.target.checked;
                document.querySelectorAll('.document-group-checkbox, .row-checkbox:not(:disabled)').forEach(cb => {
                    cb.checked = isChecked;
                    if(cb.classList.contains('document-group-checkbox')) cb.indeterminate = false;
                });
                updateButtonStates();
            });

            resultsTbody.addEventListener('change', (e) => {
                const target = e.target;
                if (target.classList.contains('document-group-checkbox')) {
                    handleDocumentGroupCheck(target);
                }
                if (target.classList.contains('row-checkbox')) {
                    handleRowCheck(target);
                }
                updateButtonStates();
            });
        });
    </script>
</body>
</html>

