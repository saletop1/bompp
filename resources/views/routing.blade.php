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
        .delete-row-icon { cursor: pointer; }
        .delete-row-icon:hover { color: #dc3545; }
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
                <h1 class="h3 main-title">SAP Data Routing Center</h1>
                <p class="subtitle mb-0">Manage Routing Data SAP</p>
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

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                    <h4 class="mb-0 me-auto">Preview Data Routing</h4>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <form id="upload-form" class="m-0">
                            <div class="input-group">
                                <input type="file" class="form-control" name="routing_file" id="routing_file" required accept=".xlsx, .xls, .csv">
                                <button class="btn btn-primary" type="submit" id="process-btn">
                                    <i class="bi bi-gear-fill"></i>
                                </button>
                            </div>
                        </form>
                        <button class="btn btn-danger" id="delete-selected-btn" disabled>
                            <i class="bi bi-trash-fill me-2"></i>Hapus
                        </button>
                        <button class="btn btn-warning" id="save-selected-btn" disabled>
                            <i class="bi bi-save-fill me-2"></i>Save
                        </button>
                        <button class="btn btn-success" id="upload-selected-btn" disabled>
                            <i class="bi bi-cloud-upload-fill me-2"></i>Upload
                        </button>
                    </div>
                </div>

                <div id="results-container" class="mt-2" style="display: none;">
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

    <div class="modal fade" id="save-details-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content text-dark">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="document-name" class="form-label">Nama Dokumen</label>
                        <input type="text" class="form-control" id="document-name" placeholder="Contoh: Routing Pintu Depan" required>
                    </div>
                    <div class="mb-3">
                        <label for="product-name" class="form-label">Nama Produk</label>
                        <input type="text" class="form-control" id="product-name" placeholder="Contoh: Pintu Jati Model A" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirm-save-btn">Konfirmasi & Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const savedRoutings = @json($savedRoutings ?? []);
        let processedDataByFile = savedRoutings;
        let uploadModal, saveModal;

        // [FUNGSI DIPERBARUI]
        function renderTable(checkedIndices = new Set()) {
            const tbody = document.getElementById('results-tbody');
            tbody.innerHTML = '';
            let globalIndex = 0;

            processedDataByFile.forEach((fileGroup, fileIndex) => {
                // [UBAH] Tambahkan checkbox pada baris header dokumen
                const headerRow = `
                    <tr class="file-header-row">
                        <td><input class="form-check-input document-group-checkbox" type="checkbox" data-group-index="${fileIndex}" title="Pilih semua di dokumen ini"></td>
                        <td colspan="7">${fileGroup.fileName}</td>
                    </tr>`;
                tbody.innerHTML += headerRow;

                fileGroup.data.forEach((group, itemIndex) => {
                    const detailsId = `details-${globalIndex}`;
                    const hasDuplicateZP01 = group.operations.filter(op => op.CONTROL_KEY === 'ZP01').length > 1;
                    const rowClass = hasDuplicateZP01 ? 'table-danger' : '';
                    const statusHtml = hasDuplicateZP01 ? `<span class="badge bg-danger">Error: Duplikat ZP01</span>` : `<span class="badge bg-secondary">Menunggu</span>`;
                    const isChecked = checkedIndices.has(globalIndex) ? 'checked' : '';

                    // [UBAH] Tambahkan atribut data-group-index pada baris data
                    const mainRow = `
                        <tr data-global-index="${globalIndex}" data-group-index="${fileIndex}" class="${rowClass}">
                            <td><input class="form-check-input row-checkbox" type="checkbox" data-global-index="${globalIndex}" ${hasDuplicateZP01 ? 'disabled' : ''} ${isChecked}></td>
                            <td>${globalIndex + 1}</td>
                            <td>${group.header.IV_MATERIAL}</td>
                            <td>${group.header.IV_PLANT}</td>
                            <td>${group.header.IV_DESCRIPTION}</td>
                            <td class="details-toggle" data-bs-toggle="collapse" data-bs-target="#${detailsId}">${group.operations.length} <i class="bi bi-chevron-down ms-1 small"></i></td>
                            <td class="status-cell">${statusHtml}</td>
                            <td><i class="bi bi-trash-fill delete-row-icon" data-global-index="${globalIndex}" title="Hapus baris ini"></i></td>
                        </tr>`;

                    let operationsHtml = `<table class="table table-dark table-sm mb-0"><thead><tr><th>Work Center</th><th>Ctrl Key</th><th>Description</th><th>Base Qty</th><th>Activity 1</th><th>UoM 1</th></tr></thead><tbody>`;
                    group.operations.forEach(op => {
                        operationsHtml += `<tr class="${(hasDuplicateZP01 && op.CONTROL_KEY === 'ZP01') ? 'table-danger' : ''}">
                            <td>${op.WORK_CNTR}</td><td>${op.CONTROL_KEY}</td>
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

        // [FUNGSI DIPERBARUI]
        function handleCheckboxChange() {
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            const enabledCount = document.querySelectorAll('.row-checkbox:not(:disabled)').length;
            const selectAllCheckbox = document.getElementById('select-all-checkbox');

            document.getElementById('delete-selected-btn').disabled = checkedCount === 0;
            document.getElementById('upload-selected-btn').disabled = checkedCount === 0;
            document.getElementById('save-selected-btn').disabled = checkedCount === 0;

            if (selectAllCheckbox) {
                selectAllCheckbox.checked = enabledCount > 0 && checkedCount === enabledCount;
                selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < enabledCount;
            }

            // [TAMBAHAN] Sinkronisasi checkbox grup dokumen
            document.querySelectorAll('.document-group-checkbox').forEach(groupCheckbox => {
                const groupIndex = groupCheckbox.dataset.groupIndex;
                const rowsInGroup = document.querySelectorAll(`tr[data-group-index="${groupIndex}"] .row-checkbox:not(:disabled)`);
                const checkedInGroup = document.querySelectorAll(`tr[data-group-index="${groupIndex}"] .row-checkbox:checked`);

                if (rowsInGroup.length > 0) {
                    if (checkedInGroup.length === 0) {
                        groupCheckbox.checked = false;
                        groupCheckbox.indeterminate = false;
                    } else if (checkedInGroup.length === rowsInGroup.length) {
                        groupCheckbox.checked = true;
                        groupCheckbox.indeterminate = false;
                    } else {
                        groupCheckbox.checked = false;
                        groupCheckbox.indeterminate = true;
                    }
                }
            });
        }

        function deleteItemsByGlobalIndices(indicesToDeleteSet) {
            const flatData = getFlatData();
            const itemsToKeepChecked = new Set();
            document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
                const globalIndex = parseInt(cb.getAttribute('data-global-index'));
                if (!indicesToDeleteSet.has(globalIndex)) {
                    itemsToKeepChecked.add(flatData[globalIndex]);
                }
            });

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

            const newFlatData = getFlatData();
            const newCheckedIndices = new Set();
            newFlatData.forEach((item, newIndex) => {
                if (itemsToKeepChecked.has(item)) {
                    newCheckedIndices.add(newIndex);
                }
            });
            renderTable(newCheckedIndices);
        }

        document.addEventListener('DOMContentLoaded', function () {
            uploadModal = new bootstrap.Modal(document.getElementById('sap-credential-modal'));
            saveModal = new bootstrap.Modal(document.getElementById('save-details-modal'));

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

            if (processedDataByFile.length > 0) {
                resultsContainer.style.display = 'block';
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
                    if (!response.ok) throw new Error((await response.json()).error || 'Gagal memproses file.');
                    const result = await response.json();
                    if (result.error) throw new Error(result.error);
                    result.fileName = `Dari File: ${result.fileName}`;
                    processedDataByFile.push(result);
                    resultsContainer.style.display = 'block';
                    renderTable();
                } catch (error) {
                    Swal.fire('Error!', error.message, 'error');
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
                if (!docName || !prodName) return Swal.fire('Peringatan', 'Nama Dokumen dan Nama Produk harus diisi.', 'warning');
                saveModal.hide();
                const allItems = getFlatData();
                const selectedItems = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);
                if (selectedItems.length === 0) return Swal.fire('Info', 'Tidak ada data yang dipilih untuk disimpan.', 'info');
                saveSelectedBtn.disabled = true;
                confirmSaveBtn.disabled = true;
                try {
                    const response = await fetch("{{ route('routing.save') }}", {
                        method: 'POST',
                        body: JSON.stringify({ routings: selectedItems, document_name: docName, product_name: prodName }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json', 'Accept': 'application/json'}
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Gagal menyimpan data.');
                    Swal.fire('Sukses!', result.message, 'success').then(() => window.location.reload());
                } catch (error) {
                    Swal.fire('Error!', error.message, 'error');
                } finally {
                    saveSelectedBtn.disabled = false;
                    confirmSaveBtn.disabled = false;
                    document.getElementById('document-name').value = '';
                    document.getElementById('product-name').value = '';
                }
            });

            confirmUploadBtn.addEventListener('click', async function() {
            const username = document.getElementById('sap-username').value, password = document.getElementById('sap-password').value;
            if (!username || !password) return Swal.fire('Peringatan', 'Username dan Password SAP harus diisi.', 'warning');
            uploadModal.hide();

            const allItems = getFlatData();
            const itemsToUpload = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => allItems[cb.getAttribute('data-global-index')]);

            let successCount = 0, failCount = 0;
            const successfulUploads = []; // Untuk menandai di DB
            const successfulIndices = new Set(); // Untuk menghapus dari UI

            for (const routingData of itemsToUpload) {
                const globalIndex = allItems.findIndex(item => item === routingData);
                const targetRow = document.querySelector(`tr[data-global-index="${globalIndex}"]`);
                const statusCell = targetRow.querySelector('.status-cell');
                statusCell.innerHTML = `<span class="spinner-border spinner-border-sm text-warning"></span> Uploading...`;

                try {
                    const response = await fetch("{{ route('api.routing.uploadToSap') }}", {
                        method: 'POST', body: JSON.stringify({ username, password, routing_data: routingData }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json', 'Accept': 'application/json'}
                    });
                    const result = await response.json();
                    if (response.ok && result.status?.toLowerCase() === 'success') {
                        statusCell.innerHTML = `<span class="badge bg-success">Success</span>`;
                        successCount++;
                        successfulIndices.add(globalIndex); // Tambahkan index untuk dihapus dari UI

                        // Kumpulkan data untuk dikirim ke backend
                        const groupIndex = targetRow.dataset.groupIndex;
                        const fileGroup = processedDataByFile[groupIndex];
                        const docNumberMatch = fileGroup.fileName.match(/RPP\.\d+/);
                        const docNumber = docNumberMatch ? docNumberMatch[0] : null;

                        if (docNumber) {
                            successfulUploads.push({
                                material: routingData.header.IV_MATERIAL,
                                doc_number: docNumber
                            });
                        }
                    } else {
                        statusCell.innerHTML = `<span class="badge bg-danger" title="${result.message || result.error || 'Unknown error'}">Failed</span>`;
                        failCount++;
                    }
                } catch (error) {
                    statusCell.innerHTML = `<span class="badge bg-danger" title="${error.message}">Error</span>`;
                    failCount++;
                }
            }

            // Setelah loop selesai, tandai yang berhasil di database
            if (successfulUploads.length > 0) {
                try {
                    await fetch("{{ route('routing.markAsUploaded') }}", {
                        method: 'POST',
                        body: JSON.stringify({ successful_uploads: successfulUploads }),
                        headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Content-Type': 'application/json', 'Accept': 'application/json'}
                    });
                    // Hapus baris yang berhasil dari UI secara dinamis
                    deleteItemsByGlobalIndices(successfulIndices);
                } catch (error) {
                    console.error('Gagal menandai data sebagai ter-upload:', error);
                }
            }

            Swal.fire('Proses Selesai', `Upload selesai: ${successCount} berhasil, ${failCount} gagal.`, 'info');
        });

            selectAllCheckbox.addEventListener('change', () => {
                document.querySelectorAll('.row-checkbox:not(:disabled)').forEach(cb => cb.checked = selectAllCheckbox.checked);
                handleCheckboxChange();
            });

            resultsTbody.addEventListener('click', e => {
                // Hapus satu baris
                if (e.target.classList.contains('delete-row-icon')) {
                    const indexToDelete = parseInt(e.target.getAttribute('data-global-index'), 10);
                    Swal.fire({
                        title: 'Hapus Baris Ini?', text: "Data akan dihapus dari tampilan preview.", icon: 'warning',
                        showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, hapus!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteItemsByGlobalIndices(new Set([indexToDelete]));
                            Swal.fire('Dihapus!', 'Baris telah dihapus dari preview.', 'success');
                        }
                    });
                }

                // [TAMBAHAN] Event handler untuk checkbox grup dokumen
                if (e.target.classList.contains('document-group-checkbox')) {
                    const groupIndex = e.target.dataset.groupIndex;
                    const isChecked = e.target.checked;
                    document.querySelectorAll(`tr[data-group-index="${groupIndex}"] .row-checkbox:not(:disabled)`).forEach(rowCheckbox => {
                        rowCheckbox.checked = isChecked;
                    });
                    handleCheckboxChange();
                }
            });

            resultsTbody.addEventListener('change', e => {
                if (e.target.classList.contains('row-checkbox')) {
                    handleCheckboxChange();
                }
            });

            deleteSelectedBtn.addEventListener('click', () => {
                const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
                if (checkedBoxes.length === 0) return Swal.fire('Info', 'Tidak ada baris yang dipilih untuk dihapus.', 'info');
                Swal.fire({
                    title: 'Anda yakin?', text: `Anda akan menghapus ${checkedBoxes.length} baris yang dipilih dari tampilan.`, icon: 'warning',
                    showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Ya, hapus yang dipilih!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const indicesToDelete = new Set(Array.from(checkedBoxes).map(cb => parseInt(cb.getAttribute('data-global-index'))));
                        deleteItemsByGlobalIndices(indicesToDelete);
                        Swal.fire('Dihapus!', 'Baris yang dipilih telah dihapus dari preview.', 'success');
                    }
                });
            });
        });
    </script>
</body>
</html>
