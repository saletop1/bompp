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
        .table-danger, .table-danger > th, .table-danger > td {
             --bs-table-bg: #dc354566;
             color: white;
             font-weight: bold;
        }
        .delete-icon { cursor: pointer; }
        .delete-icon:hover { color: #dc3545; }
        .file-header-row > td {
            background-color: rgba(0, 0, 0, 0.3) !important;
            font-weight: bold;
            font-style: italic;
            color: #ccc;
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
    </style>
</head>
<body>
    <div class="container converter-container">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Data Master Center</h1>
                <p class="subtitle mb-0">Manage Material, BOM, and Routing Data</p>
            </div>
            <img src="{{ asset('images/saplogo.png') }}" alt="SAP Logo" class="sap-logo-header">
        </div>

        <div class="card p-4 p-lg-5">
            <div class="card-body">
                <ul class="nav nav-pills nav-fill mb-4">
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('converter.index') ? 'active' : '' }}" href="{{ route('converter.index') }}"><i class="bi bi-box-seam me-2"></i>Material Master</a> </li>
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}" href="{{ route('bom.index') }}"><i class="bi bi-diagram-3 me-2"></i>BOM Master</a> </li>
                     <li class="nav-item"> <a class="nav-link {{ request()->routeIs('routing.index') ? 'active' : '' }}" href="{{ route('routing.index') }}"><i class="bi bi-signpost-split me-2"></i>Routing Master</a> </li>
                </ul>
                <hr style="border-color: rgba(255,255,255,0.3);">

                <form id="upload-form" class="mb-4">
                    <div class="input-group">
                        <input type="file" class="form-control" name="routing_file" id="routing_file" required accept=".xlsx, .xls, .csv">
                        <button class="btn btn-primary" type="submit" id="process-btn">
                            <i class="bi bi-gear-fill me-2"></i>Proses File
                        </button>
                    </div>
                </form>

                <div id="results-container" class="mt-4" style="display: none;">
                    <h4 class="mb-3">Preview Data Routing</h4>
                    <div class="d-flex justify-content-end mb-3 gap-2">
                        <button class="btn btn-warning" id="save-selected-btn" disabled>
                            <i class="bi bi-save-fill me-2"></i>Save
                        </button>
                        <button class="btn btn-success" id="upload-selected-btn" disabled>
                            <i class="bi bi-cloud-upload-fill me-2"></i>Upload yang Dipilih ke SAP
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
                                    <th>#</th>
                                    <th>Material</th><th>Plant</th><th>Description</th>
                                    <th>Jml Operasi</th><th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="results-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <footer class="text-center text-white mt-4">
            <small style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">© PT. Kayu Mebel Indonesia, 2025</small>
        </footer>
    </div>

    <div class="modal fade" id="sap-credential-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content text-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Masukkan Kredensial SAP untuk Upload</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sap-username" class="form-label">SAP Username</label>
                        <input type="text" class="form-control" id="sap-username">
                    </div>
                    <div class="mb-3">
                        <label for="sap-password" class="form-label">SAP Password</label>
                        <input type="password" class="form-control" id="sap-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirm-upload-btn">Konfirmasi & Mulai Upload</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let processedDataByFile = [];
        let uploadModal;

        function renderTable() {
            const tbody = document.getElementById('results-tbody');
            tbody.innerHTML = '';
            let globalIndex = 0;

            processedDataByFile.forEach((fileGroup, fileIndex) => {
                tbody.innerHTML += `<tr class="file-header-row"><td colspan="8">File: ${fileGroup.fileName}</td></tr>`;
                fileGroup.data.forEach((group, itemIndex) => {
                    const detailsId = `details-${globalIndex}`;

                    // [PERBAIKAN] Logika diubah dari .some() menjadi .filter().length > 1
                    const hasDuplicateZP01 = group.operations.filter(op => op.CONTROL_KEY === 'ZP01').length > 1;

                    const rowClass = hasDuplicateZP01 ? 'table-danger' : '';
                    const statusHtml = hasDuplicateZP01 ? `<span class="badge bg-danger">Error: Duplikat ZP01</span>` : `<span class="badge bg-secondary">Menunggu</span>`;

                    const mainRow = `
                        <tr data-global-index="${globalIndex}" class="${rowClass}">
                            <td><input class="form-check-input row-checkbox" type="checkbox" data-global-index="${globalIndex}" ${hasDuplicateZP01 ? 'disabled' : ''}></td>
                            <td>${globalIndex + 1}</td>
                            <td>${group.header.IV_MATERIAL}</td>
                            <td>${group.header.IV_PLANT}</td>
                            <td>${group.header.IV_DESCRIPTION}</td>
                            <td class="details-toggle" data-bs-toggle="collapse" data-bs-target="#${detailsId}">${group.operations.length} <i class="bi bi-chevron-down ms-1 small"></i></td>
                            <td class="status-cell">${statusHtml}</td>
                            <td><i class="bi bi-trash-fill delete-icon" data-file-index="${fileIndex}" title="Hapus semua data dari file ${fileGroup.fileName}"></i></td>
                        </tr>`;

                    let operationsHtml = `<table class="table table-dark table-sm mb-0"><thead><tr><th>Work Center</th><th>Ctrl Key</th><th>Work Center Desc.</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;
                    group.operations.forEach(op => {
                        operationsHtml += `<tr class="${(hasDuplicateZP01 && op.CONTROL_KEY === 'ZP01') ? 'table-danger' : ''}">
                            <td>${op.WORK_CENTER}</td><td>${op.CONTROL_KEY}</td>
                            <td>${op.DESCRIPTION}</td><td>${op.BASE_QTY}</td>
                            <td>${op.STD_VALUE_01}</td><td>${op.STD_UNIT_01}</td></tr>`;
                    });
                    operationsHtml += `</tbody></table>`;

                    const detailsRow = `<tr class="collapse-row"><td colspan="8"><div class="collapse" id="${detailsId}"><div class="p-3 details-card">${operationsHtml}</div></div></td></tr>`;
                    tbody.innerHTML += mainRow + detailsRow;
                    globalIndex++;
                });
            });

            handleCheckboxChange();
        }

        function getFlatData() {
            return processedDataByFile.flatMap(group => group.data);
        }

        function handleCheckboxChange() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            const enabledCount = document.querySelectorAll('.row-checkbox:not(:disabled)').length;

            const selectAllCheckbox = document.getElementById('select-all-checkbox');

            document.getElementById('upload-selected-btn').disabled = checkedCount === 0;
            document.getElementById('save-selected-btn').disabled = checkedCount === 0;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = enabledCount > 0 && checkedCount === enabledCount;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < enabledCount;
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            uploadModal = new bootstrap.Modal(document.getElementById('sap-credential-modal'));
            const uploadForm = document.getElementById('upload-form');
            const processBtn = document.getElementById('process-btn');
            const resultsContainer = document.getElementById('results-container');
            const uploadSelectedBtn = document.getElementById('upload-selected-btn');
            const saveSelectedBtn = document.getElementById('save-selected-btn');
            const confirmUploadBtn = document.getElementById('confirm-upload-btn');
            const resultsTbody = document.getElementById('results-tbody');
            const selectAllCheckbox = document.getElementById('select-all-checkbox');

            uploadForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                processBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Memproses...`;
                processBtn.disabled = true;
                try {
                    const response = await fetch("{{ route('routing.processFile') }}", {
                        method: 'POST', body: new FormData(this),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json'}
                    });
                    if (!response.ok) throw new Error((await response.json()).error || 'Gagal memproses file.');

                    const result = await response.json();
                    if (result.error) throw new Error(result.error);

                    processedDataByFile.push({
                        fileName: result.fileName,
                        data: result.data
                    });

                    resultsContainer.style.display = 'block';
                    renderTable();

                    processBtn.innerHTML = `<i class="bi bi-plus-circle-fill me-2"></i>Tambah File Lain`;

                } catch (error) {
                    Swal.fire('Error!', error.message, 'error');
                } finally {
                    processBtn.disabled = false;
                    uploadForm.reset();
                }
            });

            uploadSelectedBtn.addEventListener('click', () => uploadModal.show());

            saveSelectedBtn.addEventListener('click', async () => {
                const allItems = getFlatData();
                const selectedItems = Array.from(document.querySelectorAll('.row-checkbox:checked'))
                                           .map(cb => allItems[cb.getAttribute('data-global-index')]);
                if (selectedItems.length === 0) {
                    return Swal.fire('Info', 'Tidak ada data yang dipilih untuk disimpan.', 'info');
                }

                saveSelectedBtn.disabled = true;

                try {
                    const response = await fetch("{{ route('routing.save') }}", {
                        method: 'POST',
                        body: JSON.stringify({ routings: selectedItems }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json', 'Accept': 'application/json'}
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Gagal menyimpan data.');
                    }
                    Swal.fire('Sukses!', result.message, 'success');
                } catch (error) {
                    Swal.fire('Error!', error.message, 'error');
                } finally {
                    saveSelectedBtn.disabled = false;
                }
            });

            confirmUploadBtn.addEventListener('click', async function() {
                const username = document.getElementById('sap-username').value, password = document.getElementById('sap-password').value;
                if (!username || !password) return Swal.fire('Peringatan', 'Username dan Password SAP harus diisi.', 'warning');
                uploadModal.hide();

                const allItems = getFlatData();
                const itemsToUpload = Array.from(document.querySelectorAll('.row-checkbox:checked'))
                                           .map(cb => allItems[cb.getAttribute('data-global-index')]);
                let successCount = 0, failCount = 0;

                for (const routingData of itemsToUpload) {
                    const targetRow = document.querySelector(`tr[data-global-index="${allItems.indexOf(routingData)}"]`);
                    const statusCell = targetRow.querySelector('.status-cell');
                    statusCell.innerHTML = `<span class="spinner-border spinner-border-sm text-warning"></span> Uploading...`;

                    try {
                        const response = await fetch("{{ route('api.routing.uploadToSap') }}", {
                            method: 'POST', body: JSON.stringify({ username, password, routing_data: routingData }),
                            headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json', 'Accept': 'application/json'}
                        });
                        const result = await response.json();
                        if (response.ok && result.status?.toLowerCase() === 'success') {
                            statusCell.innerHTML = `<span class="badge bg-success">Success</span>`; successCount++;
                        } else {
                            statusCell.innerHTML = `<span class="badge bg-danger" title="${result.message || result.error}">Failed</span>`; failCount++;
                        }
                    } catch (error) {
                        statusCell.innerHTML = `<span class="badge bg-danger" title="${error.message}">Error</span>`; failCount++;
                    }
                }
                Swal.fire('Proses Selesai', `Upload selesai: ${successCount} berhasil, ${failCount} gagal.`, 'info');
            });

            selectAllCheckbox.addEventListener('change', () => {
                document.querySelectorAll('.row-checkbox:not(:disabled)').forEach(cb => cb.checked = selectAllCheckbox.checked);
                handleCheckboxChange();
            });

            resultsTbody.addEventListener('change', e => {
                if (e.target.classList.contains('row-checkbox')) {
                    handleCheckboxChange();
                }
            });

            resultsTbody.addEventListener('click', e => {
                if (e.target.classList.contains('delete-icon')) {
                    const fileIndexToDelete = parseInt(e.target.getAttribute('data-file-index'), 10);
                    const fileName = processedDataByFile[fileIndexToDelete].fileName;

                    Swal.fire({
                        title: 'Anda yakin?',
                        text: `Semua data dari file "${fileName}" akan dihapus dari tampilan preview.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Ya, hapus semua!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            processedDataByFile.splice(fileIndexToDelete, 1);
                            renderTable();
                            Swal.fire('Dihapus!', `Data dari file ${fileName} telah dihapus.`, 'success');
                        }
                    })
                }
            });
        });
    </script>
</body>
</html>
