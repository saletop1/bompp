<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Shop Drawing - SAP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
    <style>
        /* Tema sama dengan Converter */
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --danger-color: #dc3545;
            --glass-bg: rgba(75, 74, 74, 0.33);
            --glass-border: 1px solid rgba(255, 255, 255, 0.3);
            --text-light: #ffffff;
            --text-muted: #d7d7d7;
            --text-dark: #212529;
        }

        body {
            background-image: url("{{ asset('images/ainun.jpg') }}");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            padding: 20px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Floating Particles Animation */
        .particles-container-shop-drawing {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .particle-shop-drawing {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            animation: float-shop-drawing 15s infinite ease-in-out;
        }

        @keyframes float-shop-drawing {
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

        .card-glass {
            background: var(--glass-bg);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            border: var(--glass-border);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            position: relative;
            z-index: 1;
        }

        .container-shop-drawing {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .upload-dropzone {
            border: 2px dashed rgba(255, 255, 255, 0.6);
            border-radius: 10px;
            padding: 30px 15px;
            text-align: center;
            background: rgba(102, 126, 234, 0.05);
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-light);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .upload-dropzone:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #764ba2;
        }

        .upload-dropzone.dragover {
            background: rgba(102, 126, 234, 0.2);
            border-color: #198754;
        }

        .badge-drawing {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .badge-drawing-type {
            font-size: 0.75em;
            padding: 2px 8px;
            border-radius: 12px;
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 40px;
            border-radius: 25px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--text-light);
        }

        .search-box .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .search-box .form-control:focus {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .search-box .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
        }
        
        .material-search-btn {
            min-width: 120px;
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--text-light);
        }
        
        .material-search-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            border-color: var(--primary-color);
        }
        
        .validation-result-table th {
            width: 40%;
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .validation-result-table td {
            background-color: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
        }
        
        .modal-lg-custom {
            max-width: 1000px;
        }
        
        .file-icon {
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        /* Toast Notification */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        
        .toast {
            min-width: 300px;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .loading-overlay.show {
            display: flex;
        }
        
        /* Material Preview Styles */
        .material-preview-container {
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .material-preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .material-info-card {
            height: 100%;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Drawing Preview Badges */
        .drawing-revision-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1;
            background: rgba(0,0,0,0.7);
            color: white;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .drawing-type-badge-preview {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        /* History Table Styles - Compact */
        .history-table {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .history-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .history-table th {
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            padding: 10px 12px;
            font-weight: 600;
            color: var(--text-light);
            backdrop-filter: blur(5px);
        }
        
        .history-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .history-table thead th {
            background: linear-gradient(135deg, #006666 0%, #008080 100%) !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3) !important;
            padding: 12px 8px !important;
            font-weight: 600;
            color: var(--text-light) !important;
            backdrop-filter: blur(5px);
            text-align: center !important;
            vertical-align: middle !important;
        }

        /* Pastikan td juga rata tengah */
        .history-table td {
            padding: 10px 8px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        /* Kecuali untuk kolom Description dan File Name biarkan rata kiri */
        .history-table td:nth-child(2),
        .history-table td:nth-child(5) {
            text-align: left;
        }

        /* Material Code tetap rata tengah tapi dengan link */
        .history-table td:first-child {
            text-align: center;
        }
        .history-table td {
            padding: 10px 12px;
            vertical-align: middle;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .history-table tr:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .material-code-link {
            text-decoration: none;
            font-weight: 500;
        }
        
        .material-code-badge {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
            color: #f8f9fa;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .material-code-badge:hover {
            background: linear-gradient(135deg, #495057 0%, #343a40 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.4);
        }
        
        .badge-type {
            font-size: 0.75em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        /* NEW Drawing Type Badges */
        .badge-type-assembly { background-color: #6f42c1; color: white; }
        .badge-type-detail { background-color: #20c997; color: white; }
        .badge-type-exploded { background-color: #fd7e14; color: white; }
        .badge-type-orthographic { background-color: #17a2b8; color: white; }
        .badge-type-perspective { background-color: #6c757d; color: white; }
        .badge-type-fabrication { background-color: #e83e8c; color: white; }
        
        /* Preview Modal - FIX Z-INDEX */
        #previewModal {
            z-index: 99999 !important;
        }
        
        #previewModal .modal-dialog {
            z-index: 100000;
        }
        
        .preview-modal-image {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        
        .pdf-preview-frame {
            width: 100%;
            height: 70vh;
            border: none;
        }
        
        /* Form Styles */
        .form-label {
            color: var(--text-light);
            font-weight: 600;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            transition: all 0.1s ease;
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        /* Style untuk dropdown agar background sesuai tema */
        .form-select {
            background-color: rgba(255, 255, 255, 0.2) !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='rgba%28255, 255, 255, 0.6%29' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
        }
        
        .form-select option {
            background-color: rgba(30, 30, 30, 0.95);
            color: white;
        }
        
        /* Placeholder abu untuk select */
        .form-control::placeholder, .form-select::placeholder {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        
        /* Placeholder untuk option pertama di dropdown */
        .form-select option:first-child {
            color: rgba(255, 255, 255, 0.5) !important;
        }
        
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.3) !important;
            border-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .readonly-field {
            background-color: rgba(255, 255, 255, 0.1) !important;
            cursor: not-allowed;
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Revision input group */
        .revision-input-group {
            max-width: 150px;
        }
        
        /* Fix for modal footer */
        .preview-modal-footer {
            display: flex !important;
            justify-content: center;
            gap: 10px;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Fix for preview modal */
        .modal-xl .modal-body {
            padding-bottom: 0;
        }
        
        /* Fix for action buttons visibility */
        .action-buttons-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        
        /* Material details header */
        .material-details-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
        }
        
        /* Material details card */
        .material-details-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        /* Drawings grid - Compact tanpa gambar */
        .drawings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        
        .drawing-card-compact {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
            background: rgba(255, 255, 255, 0.1);
            padding: 10px;
        }
        
        .drawing-card-compact:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        /* No drawings state */
        .no-drawings-state {
            text-align: center;
            padding: 20px 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
            color: var(--text-light);
        }
        
        /* Material info section */
        .material-info-section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        
        /* Sticky header untuk Upload History */
        .sticky-history-header {
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(5px);
        }
        
        /* Collapse button styles */
        .collapse-btn {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--text-light);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
        }
        
        .collapse-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: var(--primary-color);
        }
        
        .collapse-btn[aria-expanded="true"] {
            background-color: rgba(13, 110, 253, 0.2);
            border-color: var(--primary-color);
        }
        
        /* Card header styles - Compact */
        .card-header-custom {
            background: rgba(0, 0, 0, 0.2) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-light) !important;
            padding: 0.75rem 1rem !important;
        }
        
        .card-header-custom {
            background: rgba(0, 0, 0, 0.2) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: var(--text-light) !important;
            padding: 0.75rem 1rem !important;
        }

        /* Header khusus untuk Upload History */
        .sticky-history-header {
            text-align: center !important;
            background: linear-gradient(135deg, #006666 0%, #008080 100%) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        .sticky-history-header .d-flex {
            justify-content: center !important;
        }

        .sticky-history-header .d-flex > div {
            text-align: center;
        }

        .sticky-history-header .badge {
            background-color: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }

        /* Button styles */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.1s ease;
            font-size: 0.9rem;
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
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-warning {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }
        
        /* Navigation buttons */
        .nav-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .nav-buttons a {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: var(--text-light);
            transition: all 0.3s ease;
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }
        
        .nav-buttons a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        /* Logout button */
        #logout-form button {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #ff6b6b;
        }
        
        #logout-form button:hover {
            background-color: rgba(220, 53, 69, 0.3);
            border-color: #dc3545;
            color: white;
        }
        
        /* NEW: File list table styles - Compact tanpa header */
        .file-list-table {
            background: rgba(0, 80, 0, 0.1) !important;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 10px;
            border: 1px solid rgba(0, 150, 0, 0.2) !important;
        }
        
        .file-list-table th {
            display: none !important; /* Sembunyikan header */
        }
        
        .file-list-table td {
            padding: 8px 10px !important;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0, 150, 0, 0.1) !important;
            color: var(--text-light);
            background-color: rgba(0, 80, 0, 0.05) !important;
            font-size: 0.85rem;
        }
        
        .file-list-table tr:hover {
            background-color: rgba(0, 120, 0, 0.1) !important;
        }
        
        /* Hilangkan bulk actions */
        .bulk-actions {
            display: none !important;
        }
        
        /* Selected files info tanpa background hijau */
        .selected-files-info {
            background-color: transparent !important;
            border: none !important;
            color: white !important;
            margin-top: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        /* Style untuk form controls di dalam file list table */
        .file-list-table .form-select,
        .file-list-table .form-control {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem;
        }
        
        .file-list-table .form-select:focus,
        .file-list-table .form-control:focus {
            background-color: rgba(255, 255, 255, 0.25) !important;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        /* Modal Material Details & Drawings lebih compact */
        .compact-modal-body {
            font-size: 0.9rem !important;
        }
        
        .compact-modal-body h4 {
            font-size: 1.2rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .compact-modal-body h5 {
            font-size: 1rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .compact-modal-body h6 {
            font-size: 0.95rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .compact-modal-body .material-details-header {
            padding: 12px !important;
        }
        
        .compact-modal-body .material-info-section {
            padding: 10px !important;
            margin-bottom: 10px !important;
        }
        
        .compact-modal-body .table {
            font-size: 0.85rem !important;
            margin-bottom: 0 !important;
        }
        
        .compact-modal-body .table th,
        .compact-modal-body .table td {
            padding: 6px 8px !important;
        }
        
        .compact-modal-body .drawing-card-compact {
            padding: 8px !important;
        }
        
        .compact-modal-body .drawing-card-compact h6 {
            font-size: 0.85rem !important;
            margin-bottom: 0.5rem !important;
        }
        
        .compact-modal-body .btn-group-sm .btn {
            padding: 0.25rem 0.5rem !important;
            font-size: 0.8rem !important;
        }
        
        /* NEW: Gap utility */
        .gap-1 {
            gap: 0.25rem;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        /* Material type badge */
        .badge-material-type {
            background: linear-gradient(135deg, #20c997 0%, #198754 100%);
            color: white;
            font-size: 0.75em;
            padding: 3px 10px;
            border-radius: 12px;
        }
        
        /* Available drawings badge */
        .badge-available-files {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            font-size: 0.75em;
            padding: 3px 10px;
            border-radius: 12px;
        }
        
        /* Drawing types container */
        .drawing-types-container {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: center;
            margin-bottom: 5px;
        }
        
        /* Revision badges */
        .revision-badge {
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 8px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            margin: 1px;
        }
        
        /* Material info badge */
        .material-info-badge {
            background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%);
            color: white;
            font-size: 0.8em;
            padding: 3px 8px;
            border-radius: 10px;
            margin: 2px;
        }
        
        /* Upload form two columns layout */
        .upload-form-column {
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .upload-files-list {
            height: 100%;
            overflow-y: auto;
            background: rgba(0, 80, 0, 0.1);
            border-radius: 8px;
            padding: 15px;
            border: 1px solid rgba(0, 150, 0, 0.2);
        }
        
        .upload-files-list h6 {
            color: white;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .file-list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .file-list-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .file-list-item-info {
            flex: 1;
            overflow: hidden;
        }
        
        .file-list-item-name {
            color: white;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-list-item-size {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.75rem;
        }
        
        .file-list-item-actions {
            display: flex;
            gap: 5px;
        }
        
        .no-files-message {
            text-align: center;
            padding: 30px 15px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container-shop-drawing {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .drawings-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .nav-buttons a, #logout-form button {
                width: 100%;
                text-align: center;
            }
            
            .history-table {
                font-size: 0.85rem;
            }
            
            .history-table th, .history-table td {
                padding: 8px 10px;
            }
            
            .file-list-table {
                font-size: 0.85rem;
            }
            
            .drawing-types-container {
                gap: 3px;
            }
            
            .material-code-badge {
                font-size: 0.75rem;
                padding: 4px 8px;
            }
            
            .upload-form-column {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
            .drawings-grid {
                grid-template-columns: 1fr;
            }
            
            .card-body {
                padding: 1rem !important;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .action-buttons-container {
                flex-direction: column;
            }
            
            .file-list-table td {
                padding: 6px 8px !important;
                font-size: 0.8rem !important;
            }
            
            .history-table {
                font-size: 0.8rem;
            }
            
            .history-table th, .history-table td {
                padding: 6px 8px !important;
            }
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .btn-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .btn-icon.preview:hover {
            background-color: rgba(13, 110, 253, 0.2);
            border-color: #fafbfc;
        }

        .btn-icon.download:hover {
            background-color: rgba(25, 135, 84, 0.2);
            border-color: #198754;
        }

        /* Tooltip for icon buttons */
        .btn-icon::after {
            content: attr(title);
            position: absolute;
            bottom: -35px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
            z-index: 1000;
            pointer-events: none;
        }

        .btn-icon:hover::after {
            opacity: 1;
            visibility: visible;
        }

        /* Compact drawing card action buttons */
        .drawing-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 8px;
        }

        /* Hover effects for drawing card */
        .drawing-card-compact:hover .drawing-actions .btn-icon {
            transform: translateY(-2px);
        }

        /* Pulse animation for new/updated drawings */
        @keyframes pulse-icon {
            0% {
                box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.4);
            }
            70% {
                box-shadow: 0 0 0 8px rgba(13, 110, 253, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
            }
        }

        .btn-icon.pulse {
            animation: pulse-icon 2s infinite;
        }

        /* Icon size adjustments */
        .btn-icon i {
            font-size: 1.2rem;
        }

        /* Ripple effect for icon buttons */
        .btn-icon {
            position: relative;
            overflow: hidden;
        }

        .btn-icon::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.3s, height 0.3s;
        }

        .btn-icon.active::before {
            width: 100px;
            height: 100px;
        }

        /* Icon color transitions */
        .btn-icon i {
            transition: transform 0.3s ease;
        }

        .btn-icon:hover i {
            transform: scale(1.2);
        }

        /* Enhanced hover effect for drawing card */
        .drawing-card-compact {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .drawing-card-compact::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .drawing-card-compact:hover::before {
            left: 100%;
        }

        .drawing-card-compact:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            border-color: rgba(13, 110, 253, 0.5);
        }

        /* Make file name more prominent on hover */
        .drawing-card-compact:hover h6 {
            color: #0dcaf0;
        }

        /* Add subtle gradient background on hover */
        .drawing-card-compact:hover {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.05));
        }

        /* Character counter for description */
        .char-counter {
            font-size: 0.75rem;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        .char-counter.near-limit {
            color: #ffc107;
        }
        
        .char-counter.over-limit {
            color: #dc3545;
        }
        
        /* NEW: Icon buttons for Material Details Modal */
        .icon-buttons-container {
            display: flex;
            gap: 10px;
        }

        .icon-btn {
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        .icon-btn:hover {
            width: 140px;
            border-radius: 30px;
            transition: all 0.4s ease;
        }

        .icon-btn:hover .btn-text {
            opacity: 1;
            transition: opacity 0.4s ease;
        }

        .icon-btn:hover .btn-icon {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-text {
            position: absolute;
            color: white;
            width: 120px;
            font-weight: 600;
            opacity: 0;
            transition: opacity 0.4s ease;
            font-size: 0.9rem;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            pointer-events: none;
        }

        .btn-icon {
            transition: all 0.3s ease;
            font-size: 1.2rem;
            color: white;
        }

        .btn-email {
            background: linear-gradient(135deg, #030303 0%, #0056b3 100%);
        }

        .btn-add-drawing {
            background: linear-gradient(135deg, #000000 0%, #1e7e34 100%);
        }

        /* Icon color */
        .icon-btn i {
            color: white !important;
        }

        /* Apply to All button style */
        .btn-apply-all {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #000;
            border: none;
            font-weight: 600;
        }

        .btn-apply-all:hover {
            background: linear-gradient(135deg, #ffca2c 0%, #ff922b 100%);
            color: #000;
        }

        /* NEW: Custom Icon Images for Material Details Modal */
        .icon-btn-img {
            width: 24px;
            height: 24px;
            object-fit: contain;
            transition: all 0.3s ease;
            display: block;
        }

        /* Specific sizing for each icon */
        .email-icon-img {
            width: 26px;
            height: 26px;
        }

        .add-drawing-icon-img {
            width: 28px;
            height: 28px;
            filter: none; /* Make PNG white */
        }

        /* Hide image icon on hover, show Bootstrap icon */
        .icon-btn:hover .icon-btn-img {
            opacity: 0;
            transform: scale(0.8);
            display: none;
        }

        /* Show Bootstrap icon on hover (hidden by default) */
        .btn-icon-hover {
            position: absolute;
            opacity: 0;
            display: none;
            transition: all 0.3s ease;
            font-size: 1.2rem;
            color: white;
        }

        /* On hover, hide the image and show the Bootstrap icon and text */
        .icon-btn:hover .btn-icon-hover {
            opacity: 1;
            display: block;
            transform: scale(1.1);
        }

        /* Ensure text is hidden by default */
        .icon-btn .btn-text {
            opacity: 0;
            position: absolute;
            color: white;
            width: 120px;
            font-weight: 600;
            transition: opacity 0.4s ease;
            font-size: 0.9rem;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            pointer-events: none;
        }

        /* Show text on hover */
        .icon-btn:hover .btn-text {
            opacity: 1;
        }

        /* Remove previous icon color rule since we're using images */
        .icon-btn i {
            color: white !important;
        }
    </style>
</head>
<body>

    <!-- Floating Particles Animation -->
    <div class="particles-container-shop-drawing" id="particles-shop-drawing"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-light" style="width: 3rem; height: 3rem;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Navigation -->
    <div class="container container-shop-drawing">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mb-4">
            <div class="text-center text-md-start mb-3 mb-md-0">
                <h1 class="h3 text-white mb-0">
                    <i class="bi bi-file-earmark-pdf"></i> Shop Drawing Management
                </h1>
                <p class="text-white-50 mb-0">Upload and manage shop drawings from Dropbox</p>
            </div>
            
            <div class="nav-buttons">
                <a href="{{ route('converter.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-seam"></i> Converter
                </a>
                <a href="{{ route('bom.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-diagram-3"></i> BOM
                </a>
                <a href="{{ route('routing.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-signpost-split"></i> Routing
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-glass">
                    <div class="card-body p-3">
                        <!-- Search for Material Details - PERUBAHAN 3: Hilangkan tombol search -->
                        <div class="row g-2 mb-3">
                            <div class="col-12">
                                <div class="search-box">
                                    <i class="bi bi-search search-icon"></i>
                                    <input type="text" class="form-control" id="searchMaterial" 
                                           placeholder="Enter material code to view details and drawings..." 
                                           aria-label="Search material code">
                                </div>
                            </div>
                        </div>

                        <!-- Upload Section dengan Collapse - PERUBAHAN 4: Icon panah saja -->
                        <div class="card card-glass" id="uploadNewShopDrawingCard">
                            <div class="card-header card-header-custom d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-cloud-upload"></i> Upload New Shop Drawing
                                    @if(!$canUpload)
                                    <span class="badge bg-warning ms-2">RnD Only</span>
                                    @endif
                                </div>
                                <!-- PERUBAHAN 4: Ganti text dengan icon saja -->
                                <button class="btn btn-sm collapse-btn" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#uploadCollapse" aria-expanded="false" 
                                        aria-controls="uploadCollapse" id="collapseToggleBtn" title="Toggle Form">
                                    <i class="bi bi-chevron-down"></i>
                                </button>
                            </div>
                            <div class="collapse" id="uploadCollapse">
                                <div class="card-body p-3">
                                    <!-- PERUBAHAN 1: Tambahkan pesan jika tidak punya akses -->
                                    @if(!$canUpload)
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>Upload Restricted:</strong> Only RnD users can upload drawings.
                                        Please contact administrator if you need upload access.
                                    </div>
                                    @endif
                                    
                                    <form id="uploadForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="row g-2 mb-3">
                                            <!-- Material Code -->
                                            <div class="col-md-4">
                                                <label class="form-label">Material Code *</label>
                                                <div class="input-group">
                                                    <input type="text" class="form-control" 
                                                           id="materialCode" name="material_code" 
                                                           placeholder="Enter material code" required
                                                           aria-label="Material code" {{ !$canUpload ? 'disabled' : '' }}>
                                                    <!-- PERUBAHAN 3: Hilangkan tombol search material kecil -->
                                                </div>
                                                <div class="invalid-feedback" id="materialCodeError"></div>
                                            </div>
                                            
                                            <!-- Description -->
                                            <div class="col-md-8">
                                                <label class="form-label">Description *</label>
                                                <input type="text" class="form-control readonly-field" 
                                                       id="description" name="description" required readonly
                                                       placeholder="Auto-filled from material"
                                                       aria-label="Material description" {{ !$canUpload ? 'disabled' : '' }}>
                                            </div>
                                            
                                            <!-- Plant (hidden) -->
                                            <input type="hidden" id="plant" name="plant" value="3000">
                                        </div>
                                        
                                        <!-- Dua kolom: Drag & Drop dan List File -->
                                        <div class="row g-3 mb-3">
                                            <!-- Kolom 1: Drag & Drop -->
                                            <div class="col-md-6">
                                                <div class="upload-form-column">
                                                    <div class="upload-dropzone" id="uploadDropzone"
                                                         aria-label="File upload area" style="{{ !$canUpload ? 'opacity: 0.5; cursor: not-allowed;' : '' }}">
                                                        @if($canUpload)
                                                        <dotlottie-player src="{{ asset('animations/Greenish arrow down.lottie') }}" 
                                                            background="transparent" speed="1" 
                                                            style="width: 80px; height: 80px; margin: 0 auto;" 
                                                            loop autoplay aria-hidden="true"></dotlottie-player>
                                                        <h5 class="mt-2 text-white">Drop shop drawings here or click to browse</h5>
                                                        <p class="text-white-50 mb-1">Supports JPG, PNG, PDF, DWG, DXF, IGS, IGES, STP, STEP, ZIP, RAR (No file size limit)</p>
                                                        @else
                                                        <i class="bi bi-lock display-4 text-warning mb-2"></i>
                                                        <h5 class="mt-2 text-white">Upload Restricted</h5>
                                                        <p class="text-white-50 mb-1">Only RnD users can upload drawings</p>
                                                        @endif
                                                    </div>
                                                    @if($canUpload)
                                                    <input type="file" id="drawingFile" name="drawing[]" 
                                                           accept=".jpg,.jpeg,.png,.gif,.bmp,.pdf,.dwg,.dxf,.igs,.iges,.stp,.step,.zip,.rar" 
                                                           class="d-none" multiple aria-label="Select drawing files">
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <!-- Kolom 2: List File yang akan diupload -->
                                            <div class="col-md-6">
                                                <div class="upload-form-column">
                                                    <div class="upload-files-list" id="uploadFilesList" style="{{ !$canUpload ? 'opacity: 0.7;' : '' }}">
                                                        <h6><i class="bi bi-list-check"></i> Files to Upload</h6>
                                                        <div id="filesListContainer">
                                                            <!-- File list akan ditampilkan di sini -->
                                                            <div class="no-files-message">
                                                                <i class="bi bi-files display-6 mb-2"></i>
                                                                <p>No files selected yet</p>
                                                                <p class="small">Files will appear here after you select them</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Progress Bar -->
                                        <div class="row g-2 mb-3">
                                            <div class="col-12">
                                                <div class="progress d-none" id="uploadProgress">
                                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                         role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="row g-2">
                                            <div class="col-12 action-buttons-container">
                                                <button type="button" class="btn btn-primary" id="btnUpload" {{ !$canUpload ? 'disabled' : '' }}>
                                                    <i class="bi bi-upload"></i> Upload All Drawings
                                                </button>
                                                <button type="button" class="btn btn-success" id="btnValidateMaterial" {{ !$canUpload ? 'disabled' : '' }}>
                                                    <i class="bi bi-check-circle"></i> Validate Material
                                                </button>
                                                <button type="button" class="btn btn-warning" id="btnClearForm" {{ !$canUpload ? 'disabled' : '' }}>
                                                    <i class="bi bi-x-circle"></i> Clear All
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="modal-header p-3">
                    <h5 class="modal-title text-white" id="previewModalTitle">Drawing Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" id="previewModalCloseBtn"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="imagePreviewContainer" class="text-center d-none">
                        <img id="previewImage" src="" class="preview-modal-image" alt="Preview">
                    </div>
                    <div id="pdfPreviewContainer" class="d-none">
                        <iframe id="previewPdf" src="" class="pdf-preview-frame" frameborder="0" title="PDF Preview"></iframe>
                    </div>
                    <div id="filePreviewContainer" class="text-center d-none">
                        <div class="py-4">
                            <i class="bi bi-file-earmark display-1 text-white-50 mb-3"></i>
                            <h4 class="text-white">File Preview Not Available</h4>
                            <p class="text-white-50">This file type cannot be previewed in browser</p>
                        </div>
                    </div>
                    <div class="preview-modal-footer">
                        <a href="#" id="downloadLink" class="btn btn-primary" download>
                            <i class="bi bi-download"></i> Download
                        </a>
                        <a href="#" id="viewOriginalLink" class="btn btn-secondary" target="_blank">
                            <i class="bi bi-box-arrow-up-right"></i> View Original
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Material Validation Result Modal -->
    <div class="modal fade" id="validationResultModal" tabindex="-1" aria-labelledby="validationResultModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="modal-header p-3">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Material Validation Result
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="validationResultContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="btnUseValidatedMaterial">
                        <i class="bi bi-check"></i> Use This Material
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Material Search Modal with Preview -->
    <div class="modal fade" id="materialSearchModal" tabindex="-1" aria-labelledby="materialSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg-custom">
            <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="modal-header p-3">
                    <h5 class="modal-title text-white">
                        <i class="bi bi-search me-2"></i>
                        Material Details & Drawings
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3 compact-modal-body" id="materialSearchContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Email Request Modal -->
    <div class="modal fade" id="emailRequestModal" tabindex="-1" aria-labelledby="emailRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="emailRequestModalLabel">Request Drawing via Email</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="emailRequestForm">
                        <div class="mb-3">
                            <label for="recipientEmail" class="form-label text-white">Recipient Email</label>
                            <input type="email" class="form-control" id="recipientEmail" 
                                   value="rnd@example.com" required>
                            <small class="text-white-50">Default: RnD Department</small>
                        </div>
                        <div class="mb-3">
                            <label for="emailSubject" class="form-label text-white">Subject</label>
                            <input type="text" class="form-control" id="emailSubject" 
                                   value="Request for Shop Drawing - Material: [Material Code]" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="emailMessage" class="form-label text-white">Message</label>
                            <textarea class="form-control" id="emailMessage" rows="5" required>
Dear RnD Team,

Please upload the shop drawing for material code: [Material Code].

Thank you.
                            </textarea>
                        </div>
                        <input type="hidden" id="emailMaterialCode" value="">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="btnSendEmail">Send Email</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit File Modal (Static) -->
    <div class="modal fade" id="editFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                <div class="modal-header">
                    <h5 class="modal-title text-white">Edit File: <span id="editFileName"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Drawing Type</label>
                        <div class="input-group mb-2">
                            <select class="form-select" id="editDrawingType">
                                <option value="assembly">Assembly Drawing</option>
                                <option value="detail">Detail Drawing</option>
                                <option value="exploded">Exploded View</option>
                                <option value="orthographic">Orthographic Drawing (2D)</option>
                                <option value="perspective">Perspective Drawing (3D)</option>
                                <option value="fabrication">Fabrication Drawing</option>
                            </select>
                            <button class="btn btn-apply-all" type="button" id="applyDrawingTypeToAll" title="Apply this drawing type to all files">
                                <i class="bi bi-check-all"></i> Apply to All
                            </button>
                        </div>
                        <small class="text-white-50">Applies drawing type to all files in the list</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Revision</label>
                        <select class="form-select" id="editRevision">
                            <option value="Rev0">Rev0</option>
                            <option value="Rev1">Rev1</option>
                            <option value="Rev2">Rev2</option>
                            <option value="Rev3">Rev3</option>
                            <option value="Rev4">Rev4</option>
                            <option value="Rev5">Rev5</option>
                            <option value="other">Custom Revision</option>
                        </select>
                        <input type="text" class="form-control mt-2 d-none" 
                            id="editCustomRevision" placeholder="e.g., RevA, Rev6, RevFinal" 
                            value="">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveFileEditBtn" data-index="">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // ========== GLOBAL VARIABLES ==========
        let currentValidatedMaterial = null;
        let selectedFiles = [];
        let currentMaterialDrawings = [];
        let activeToastCount = 0;
        const MAX_TOAST_COUNT = 3;
        const MAX_FILE_COUNT = 100;
        
        // Icon URLs
        const emailIconUrl = "{{ asset('images/gmail.png') }}";
        const addDrawingIconUrl = "{{ asset('images/add.png') }}";
        
        // User role untuk kontrol akses
        const userCanUpload = {{ $canUpload ? 'true' : 'false' }};
        const userRole = "{{ Auth::user()->role ?? '' }}";
                
        // ========== HELPER FUNCTIONS ==========
        function showToast(message, type = 'info') {
        // Limit toast count to prevent spam
        if (activeToastCount >= MAX_TOAST_COUNT) {
            return;
        }
        
        const toastId = 'toast-' + Date.now();
        const icon = type === 'success' ? 'bi-check-circle' : 
                    type === 'error' ? 'bi-exclamation-triangle' : 
                    type === 'warning' ? 'bi-exclamation-circle' : 'bi-info-circle';
        
        const bgClass = type === 'success' ? 'bg-success' : 
                    type === 'error' ? 'bg-danger' : 
                    type === 'warning' ? 'bg-warning' : 'bg-info';
        
        // Untuk warning, gunakan text-dark. Untuk yang lain, tetap text-white
        const textColorClass = type === 'warning' ? 'text-dark' : 'text-white';
        const closeBtnClass = type === 'warning' ? 'btn-close' : 'btn-close btn-close-white';
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${textColorClass} ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i> ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        $('#toastContainer').append(toastHtml);
        const toast = new bootstrap.Toast(document.getElementById(toastId));
        toast.show();
        
        activeToastCount++;
        
        // Set durasi berdasarkan tipe: error 8 detik, lainnya 5 detik
        const duration = type === 'error' ? 16000 : 10000;
        
        // Auto remove after duration
        setTimeout(() => {
            $(`#${toastId}`).remove();
            activeToastCount = Math.max(0, activeToastCount - 1);
        }, duration);
        
        // Handle manual close
        $(`#${toastId}`).on('hidden.bs.toast', function() {
            activeToastCount = Math.max(0, activeToastCount - 1);
        });
    }
        
        function showLoading(show) {
            if (show) {
                $('#loadingOverlay').addClass('show');
            } else {
                $('#loadingOverlay').removeClass('show');
            }
        }
        
        // Fungsi untuk format tanggal Jakarta (tanpa waktu)
        function formatJakartaDate(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';
            
            try {
                // Coba parsing date string
                let date = new Date(dateString);
                
                // Jika parsing gagal, coba format lain
                if (isNaN(date.getTime())) {
                    // Coba format YYYYMMDD atau format SAP lainnya
                    if (dateString.match(/^\d{8}$/)) {
                        // Format: YYYYMMDD
                        const year = dateString.substring(0, 4);
                        const month = dateString.substring(4, 6);
                        const day = dateString.substring(6, 8);
                        return `${day}/${month}/${year}`;
                    } else if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
                        // Format: YYYY-MM-DD
                        const parts = dateString.split('-');
                        return `${parts[2]}/${parts[1]}/${parts[0]}`;
                    } else {
                        // Return as-is jika tidak bisa diparsing
                        return dateString;
                    }
                }
                
                // Format ke waktu Jakarta (UTC+7)
                const jakartaOffset = 7 * 60; // 7 hours in minutes
                const localOffset = date.getTimezoneOffset();
                const jakartaTime = new Date(date.getTime() + (localOffset + jakartaOffset) * 60000);
                
                // Format ke DD/MM/YYYY
                const day = String(jakartaTime.getDate()).padStart(2, '0');
                const month = String(jakartaTime.getMonth() + 1).padStart(2, '0');
                const year = jakartaTime.getFullYear();
                
                return `${day}/${month}/${year}`;
            } catch (error) {
                console.error('Error formatting date:', error);
                return dateString;
            }
        }
        
        // Fungsi untuk format timestamp lengkap
        function formatTimestamp(timestamp) {
            if (!timestamp) return 'N/A';
            
            try {
                const date = new Date(timestamp);
                if (isNaN(date.getTime())) return 'N/A';
                
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                
                return `${day}/${month}/${year} ${hours}:${minutes}`;
            } catch (error) {
                console.error('Error formatting timestamp:', error);
                return 'N/A';
            }
        }

        // ========== FLOATING PARTICLES ANIMATION ==========
        function createParticlesShopDrawing() {
            const container = document.getElementById('particles-shop-drawing');
            if (!container) return;

            const particleCount = 30;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle-shop-drawing';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (10 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // Initialize particles on page load
        document.addEventListener('DOMContentLoaded', function() {
            createParticlesShopDrawing();
        });

        // ========== MAIN SEARCH MATERIAL FUNCTION ==========
        function searchAndShowMaterial(materialCode) {
            if (!materialCode || materialCode.trim() === '') {
                showToast('Please enter material code', 'warning');
                return;
            }
            
            // Show modal and loading
            $('#materialSearchModal').modal('show');
            $('#materialSearchContent').html(`
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-white">Searching material details and drawings...</p>
                </div>
            `);
            
            // Get drawings from database
            $.ajax({
                url: '{{ route("api.shop_drawings.get_shop_drawings") }}',
                type: 'GET',
                data: { material_code: materialCode },
                success: function(drawingsResponse) {
                    const drawings = drawingsResponse.drawings || [];
                    
                    // Get material details from first drawing or search SAP
                    if (drawings.length > 0) {
                        const firstDrawing = drawings[0];
                        const material = {
                            material_code: firstDrawing.material_code,
                            description: firstDrawing.description || 'No description',
                            material_type: firstDrawing.material_type || 'N/A',
                            material_group: firstDrawing.material_group || 'N/A',
                            base_unit: firstDrawing.base_unit || 'N/A',
                            created_on: 'N/A',
                            created_by: firstDrawing.user?.name || 'N/A'
                        };
                        
                        displayMaterialAndDrawings(material, drawings);
                    } else {
                        // No drawings, try to get from SAP
                        $.ajax({
                            url: '{{ route("api.shop_drawings.search") }}',
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: { material_code: materialCode },
                            success: function(materialResponse) {
                                if (materialResponse.status === 'success' && materialResponse.materials && materialResponse.materials.length > 0) {
                                    displayMaterialAndDrawings(materialResponse.materials[0], []);
                                } else {
                                    $('#materialSearchContent').html(`
                                        <div class="alert alert-warning">
                                            <h5><i class="bi bi-exclamation-triangle"></i> Material Not Found</h5>
                                            <p>Material code <strong>${materialCode}</strong> was not found in the system.</p>
                                            <div class="mt-3">
                                                ${userCanUpload ? `<button class="btn btn-primary btn-sm" onclick="useMaterialForUpload('${materialCode}')">
                                                    <i class="bi bi-arrow-left-circle"></i> Add Drawing
                                                </button>` : ''}
                                            </div>
                                        </div>
                                    `);
                                }
                            },
                            error: function() {
                                $('#materialSearchContent').html(`
                                    <div class="alert alert-warning">
                                        <h5><i class="bi bi-exclamation-triangle"></i> Material Not Found</h5>
                                        <p>Material code <strong>${materialCode}</strong> was not found in the system.</p>
                                        <div class="mt-3">
                                            ${userCanUpload ? `<button class="btn btn-primary btn-sm" onclick="useMaterialForUpload('${materialCode}')">
                                                <i class="bi bi-arrow-left-circle"></i> Add Drawing
                                            </button>` : ''}
                                        </div>
                                    </div>
                                `);
                            }
                        });
                    }
                },
                error: function() {
                    $('#materialSearchContent').html(`
                        <div class="alert alert-danger">
                            <h5><i class="bi bi-x-circle"></i> Search Error</h5>
                            <p>Failed to search for material <strong>${materialCode}</strong>.</p>
                        </div>
                    `);
                }
            });
        }
        
        function displayMaterialAndDrawings(material, drawings) {
            const materialCode = material.material_code;
            
            // Ambil data material dari drawing pertama atau dari material object
            let materialType = 'N/A';
            let materialGroup = 'N/A';
            let baseUnit = 'N/A';
            
            // Variabel untuk Last Upload dan Uploaded By
            let lastUpload = 'N/A';
            let uploadedBy = 'N/A';
            
            // Coba ambil dari drawing pertama jika ada dan memiliki data material
            if (drawings && drawings.length > 0) {
                // Sort drawings by uploaded_at desc untuk mendapatkan yang terbaru
                drawings.sort((a, b) => new Date(b.uploaded_at || b.created_at) - new Date(a.uploaded_at || a.created_at));
                
                // Ambil drawing terbaru
                const latestDrawing = drawings[0];
                
                // Ambil data material dari drawing terbaru
                for (let i = 0; i < drawings.length; i++) {
                    const drawing = drawings[i];
                    if (drawing.material_type && drawing.material_type !== 'N/A') {
                        materialType = drawing.material_type;
                        materialGroup = drawing.material_group || 'N/A';
                        baseUnit = drawing.base_unit || 'N/A';
                        break;
                    }
                }
                
                // Jika tidak ditemukan, ambil dari drawing terbaru
                if (materialType === 'N/A') {
                    materialType = latestDrawing.material_type || material.material_type || 'N/A';
                    materialGroup = latestDrawing.material_group || material.material_group || 'N/A';
                    baseUnit = latestDrawing.base_unit || material.base_unit || 'N/A';
                }
                
                // **PERBAIKAN: Ambil data Last Upload dan Uploaded By dari drawing terbaru**
                lastUpload = latestDrawing.uploaded_at || latestDrawing.created_at;
                
                // **PERBAIKAN KRITIS: Ambil nama user sama seperti di Upload History**
                // Di Upload History, menggunakan: $group['last_uploader'] = $drawing->user->name ?? 'System';
                if (latestDrawing.user && latestDrawing.user.name) {
                    uploadedBy = latestDrawing.user.name;
                } else if (latestDrawing.uploaded_by) {
                    uploadedBy = latestDrawing.uploaded_by;
                } else {
                    // Fallback: coba ambil dari field yang mungkin ada
                    uploadedBy = latestDrawing.user_name || latestDrawing.uploader_name || 'System';
                }
            } else {
                // Jika tidak ada drawings, gunakan dari material (dari SAP)
                materialType = material.material_type || 'N/A';
                materialGroup = material.material_group || 'N/A';
                baseUnit = material.base_unit || 'N/A';
            }
            
            // Konversi base unit dari ST ke PC jika diperlukan
            baseUnit = baseUnit === 'ST' ? 'PC' : baseUnit;
            
            // Format Last Upload
            const formattedLastUpload = formatTimestamp(lastUpload);
            
            let drawingsHtml = '';
            
            if (drawings && drawings.length > 0) {
                drawingsHtml = `
                    <div class="mt-3">
                        <h5 class="text-white mb-2"><i class="bi bi-files"></i> Available Drawings (${drawings.length})</h5>
                        <div class="drawings-grid">
                `;
                
                drawings.forEach((drawing) => {
                    const fileExt = drawing.file_extension || (drawing.filename || '').split('.').pop().toLowerCase() || '';
                    const drawingType = drawing.drawing_type || 'assembly';
                    const revision = drawing.revision || 'Rev0';
                    const shareUrl = drawing.dropbox_share_url || drawing.share_url;
                    const directUrl = drawing.dropbox_direct_url || drawing.direct_url;
                    const downloadUrl = directUrl || shareUrl;
                    const fileName = drawing.filename || drawing.original_filename || 'drawing';
                    const matCode = drawing.material_code || materialCode;
                    const timestamp = formatTimestamp(drawing.uploaded_at || drawing.created_at);
                    
                    // **PERBAIKAN: Ambil nama user untuk setiap drawing sama seperti di Upload History**
                    const fileUploadedBy = drawing.user?.name || drawing.uploaded_by || drawing.user_name || drawing.uploader_name || 'System';
                    
                    drawingsHtml += `
                    <div class="drawing-card-compact">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-dark">${revision === 'Master' ? 'Rev0' : revision}</span>
                            <span class="badge ${getDrawingTypeBadgeClass(drawingType)}">${getDrawingTypeDisplayName(drawingType)}</span>
                        </div>
                        <h6 class="text-truncate text-white mb-1" title="${fileName}">${fileName}</h6>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-white-50">${timestamp}</small>
                            <small class="text-white-50">by ${fileUploadedBy}</small>
                        </div>
                        <div class="drawing-actions">
                            <button class="btn btn-outline-primary btn-icon preview" 
                                    onclick="previewDrawing('${shareUrl}', '${matCode}', '${fileExt}', '${fileName}')"
                                    title="Preview Drawing" 
                                    aria-label="Preview ${fileName}">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                            <button class="btn btn-outline-success btn-icon download" 
                                    onclick="downloadDrawing('${downloadUrl}', '${fileName}')"
                                    title="Download Drawing"
                                    aria-label="Download ${fileName}">
                                <i class="bi bi-download"></i>
                            </button>
                        </div>
                    </div>
                `;
                });
                
                drawingsHtml += `
                        </div>
                    </div>
                `;
            } else {
                drawingsHtml = `
                    <div class="no-drawings-state">
                        <i class="bi bi-file-earmark-x display-4 text-white-50 mb-2"></i>
                        <h5 class="text-white">No Drawings Available</h5>
                        <p class="text-white-50">No shop drawings have been uploaded for this material yet.</p>
                    </div>
                `;
            }
            
            // PERUBAHAN 2: Sembunyikan tombol Add Drawing jika user tidak punya akses
            const addDrawingButton = userCanUpload ? 
                `<button class="icon-btn btn-add-drawing" 
                        onclick="$('#materialSearchModal').modal('hide'); useMaterialForUpload('${material.material_code}', '${(material.description || '').replace(/'/g, "\\'")}', '${materialType}', '${materialGroup}', '${baseUnit}')"
                        title="Add Drawing">
                    <img src="${addDrawingIconUrl}" class="icon-btn-img add-drawing-icon-img" alt="Add Drawing Icon">
                    <i class="bi bi-plus-circle-fill btn-icon-hover d-none"></i>
                    <span class="btn-text">Add Drawing</span>
                </button>` : '';
            
            $('#materialSearchContent').html(`
                <div class="material-details-header mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">${material.material_code}</h4>
                            <p class="mb-0">${material.description || 'No description available'}</p>
                        </div>
                        <div class="icon-buttons-container">
                            <button class="icon-btn btn-email" onclick="openEmailRequestModal('${material.material_code}')" title="Request Drawing via Email">
                                <img src="${emailIconUrl}" class="icon-btn-img email-icon-img" alt="Email Icon">
                                <i class="bi bi-envelope-fill btn-icon-hover d-none"></i>
                                <span class="btn-text">Request Drawing</span>
                            </button>
                            ${addDrawingButton}
                        </div>
                    </div>
                </div>
                
                <div class="material-info-section">
                    <h6 class="mb-2 text-white"><i class="bi bi-info-circle"></i> Material Information</h6>
                    <table class="table table-bordered validation-result-table mb-0">
                        <tr>
                            <th>Material Type</th>
                            <td>${materialType}</td>
                        </tr>
                        <tr>
                            <th>Material Group</th>
                            <td>${materialGroup}</td>
                        </tr>
                        <tr>
                            <th>Base Unit</th>
                            <td>${baseUnit}</td>
                        </tr>
                        <tr>
                            <th>Last Upload</th>
                            <td>${formattedLastUpload}</td>
                        </tr>
                        <tr>
                            <th>Uploaded By</th>
                            <td><span class="text-light">${uploadedBy}</span></td>
                        </tr>
                    </table>
                </div>
                
                ${drawingsHtml}
            `);
        }
        
        function getDrawingTypeBadgeClass(drawingType) {
            const classes = {
                'assembly': 'badge-type-assembly',
                'detail': 'badge-type-detail',
                'exploded': 'badge-type-exploded',
                'orthographic': 'badge-type-orthographic',
                'perspective': 'badge-type-perspective',
                'fabrication': 'badge-type-fabrication',
                // Old types for backward compatibility
                'drawing': 'badge-type-assembly',
                'technical': 'badge-type-detail',
                'installation': 'badge-type-exploded',
                'as_built': 'badge-type-orthographic',
                'master': 'badge-type-perspective'
            };
            
            return classes[drawingType] || 'bg-secondary';
        }
        
        function getDrawingTypeDisplayName(drawingType) {
            const names = {
                'assembly': 'Assembly Drawing',
                'detail': 'Detail Drawing',
                'exploded': 'Exploded View',
                'orthographic': 'Orthographic Drawing (2D)',
                'perspective': 'Perspective Drawing (3D)',
                'fabrication': 'Fabrication Drawing',
                // Old types for backward compatibility
                'drawing': 'Assembly Drawing',
                'technical': 'Detail Drawing',
                'installation': 'Exploded View',
                'as_built': 'Orthographic Drawing (2D)',
                'master': 'Perspective Drawing (3D)'
            };
            
            return names[drawingType] || drawingType;
        }
        
        // ========== USE MATERIAL FOR UPLOAD FUNCTION ==========
        function useMaterialForUpload(materialCode, description, materialType = 'N/A', materialGroup = 'N/A', baseUnit = 'N/A') {
            // PERUBAHAN 1: Cek apakah user punya akses upload
            if (!userCanUpload) {
                showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                return;
            }
            
            // Set nilai ke form
            $('#materialCode').val(materialCode);
            $('#description').val(description);
            
            // **PERBAIKAN: Simpan informasi material untuk digunakan nanti**
            window.currentMaterialInfo = {
                material_type: materialType,
                material_group: materialGroup,
                base_unit: baseUnit === 'ST' ? 'PC' : baseUnit
            };
            
            console.log('Material info saved for upload:', window.currentMaterialInfo);
            
            // Tutup modal Material Details & Drawings
            $('#materialSearchModal').modal('hide');
            
            // Expand Upload New Shop Drawing (collapse on)
            $('#uploadCollapse').collapse('show');
            $('#collapseToggleBtn').attr('aria-expanded', 'true');
            $('#collapseToggleBtn').find('i').removeClass('bi-chevron-down').addClass('bi-chevron-up');
            
            // Scroll ke card Upload New Shop Drawing
            setTimeout(() => {
                document.getElementById('uploadNewShopDrawingCard').scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 300);
            
            showToast(`Material ${materialCode} applied to upload form`, 'success');
            $('#materialCode').focus();
        }

        // ========== EMAIL REQUEST FUNCTIONS ==========
        // Function to open email request modal
        function openEmailRequestModal(materialCode) {
            // Set material code to hidden input
            $('#emailMaterialCode').val(materialCode);
            // Set subject with material code
            $('#emailSubject').val(`Request for Shop Drawing - Material: ${materialCode}`);
            // Set message with material code
            $('#emailMessage').val(`Dear RnD Team,\n\nPlease upload the shop drawing for material code: ${materialCode}.\n\nThank you.`);
            // Show modal
            $('#emailRequestModal').modal('show');
        }

        // ========== MULTIPLE FILES HANDLING ==========
        function handleFiles(files) {
            // PERUBAHAN 1: Cek apakah user punya akses upload
            if (!userCanUpload) {
                showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                return;
            }
            
            if (files.length > MAX_FILE_COUNT) {
                showToast(`Maximum ${MAX_FILE_COUNT} files allowed. Only the first ${MAX_FILE_COUNT} files will be processed.`, 'warning');
                files = Array.from(files).slice(0, MAX_FILE_COUNT);
            }
            
            const validFiles = [];
            
            Array.from(files).forEach((file, index) => {
                // PERBAIKAN: Validasi file extension - tambah ZIP dan RAR
                const allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'pdf', 'dwg', 'dxf', 'igs', 'iges', 'stp', 'step', 'zip', 'rar'];
                const fileExtension = file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    showToast(`File "${file.name}" has invalid extension. Allowed: ${allowedExtensions.join(', ')}`, 'error');
                    return;
                }
                
                // Default values
                const defaultDrawingType = 'assembly';
                const defaultRevision = 'Rev0';
                // Generate default description from filename (max 50 chars)
                const defaultDescription = file.name.substring(0, 50).replace(/\.[^/.]+$/, ""); // Remove extension
                
                validFiles.push({
                    file: file,
                    drawing_type: defaultDrawingType,
                    revision: defaultRevision,
                    custom_revision: null
                });
            });
            
            // Add to selected files array
            selectedFiles.push(...validFiles);
            
            // Limit to MAX_FILE_COUNT
            if (selectedFiles.length > MAX_FILE_COUNT) {
                selectedFiles = selectedFiles.slice(0, MAX_FILE_COUNT);
                showToast(`Maximum ${MAX_FILE_COUNT} files allowed.`, 'warning');
            }
            
            renderFilesList();
        }
        
        function renderFilesList() {
            const container = $('#filesListContainer');
            
            container.empty();
            
            if (selectedFiles.length === 0) {
                container.html(`
                    <div class="no-files-message">
                        <i class="bi bi-files display-6 mb-2"></i>
                        <p>No files selected yet</p>
                        <p class="small">Files will appear here after you select them</p>
                    </div>
                `);
                return;
            }
            
            selectedFiles.forEach((fileObj, index) => {
                const fileSizeMB = (fileObj.file.size / (1024 * 1024)).toFixed(2);
                const isCustomRevision = fileObj.revision === 'other' || (fileObj.custom_revision && fileObj.custom_revision !== '');
                const revision = isCustomRevision ? (fileObj.custom_revision || 'Rev0') : fileObj.revision;
                const description = fileObj.description || '';
                
                const fileItem = `
                    <div class="file-list-item" data-index="${index}">
                        <div class="file-list-item-info">
                            <div class="file-list-item-name">
                                <i class="bi ${getFileIconClass(fileObj.file.name)} me-2"></i>
                                ${fileObj.file.name}
                            </div>
                            <div class="file-list-item-size">
                                ${fileSizeMB} MB • ${fileObj.drawing_type} • ${revision}
                            </div>
                        </div>
                        <div class="file-list-item-actions">
                            <button type="button" class="btn btn-sm btn-outline-primary edit-file" data-index="${index}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-file" data-index="${index}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                container.append(fileItem);
            });
        }
        
        function getFileIconClass(fileName) {
            const extension = fileName.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(extension)) {
                return 'bi-file-image text-primary';
            } else if (extension === 'pdf') {
                return 'bi-file-earmark-pdf text-danger';
            } else if (['dwg', 'dxf'].includes(extension)) {
                return 'bi-file-earmark-binary text-warning';
            } else if (['igs', 'iges', 'stp', 'step'].includes(extension)) {
                return 'bi-file-earmark-code text-info';
            } else if (['zip', 'rar'].includes(extension)) {
                return 'bi-file-earmark-zip text-success';
            } else {
                return 'bi-file-earmark text-secondary';
            }
        }
        
        // ========== DRAG & DROP FUNCTIONALITY ==========
        function initializeDragDrop() {
            const dropzone = $('#uploadDropzone')[0];
            
            // Clear any existing event listeners
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.removeEventListener(eventName, preventDefaults);
                dropzone.removeEventListener(eventName, highlight);
                dropzone.removeEventListener(eventName, unhighlight);
            });
            
            // Prevent default drag behaviors
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Highlight dropzone when item is dragged over it
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight(e) {
                // PERUBAHAN 1: Jangan highlight jika tidak punya akses
                if (!userCanUpload) return;
                dropzone.classList.add('dragover');
            }
            
            function unhighlight(e) {
                dropzone.classList.remove('dragover');
            }
            
            // Handle dropped files
            dropzone.addEventListener('drop', handleDrop, false);
        }
        
        function handleDrop(e) {
            // PERUBAHAN 1: Cek apakah user punya akses upload
            if (!userCanUpload) {
                showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                return;
            }
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                handleFiles(files);
            }
        }

        // ========== PREVIEW DRAWING FUNCTION - PERBAIKAN ==========
        function previewDrawing(url, materialCode, fileExtension, fileName = '') {
            // Validasi materialCode
            if (!materialCode || materialCode.trim() === '') {
                showToast('Material code is required for preview', 'error');
                return;
            }

            // Simpan material code untuk kembali ke modal Material Details
            window.previewMaterialCode = materialCode;
            
            // Reset semua containers
            $('#imagePreviewContainer').addClass('d-none');
            $('#pdfPreviewContainer').addClass('d-none');
            $('#filePreviewContainer').addClass('d-none');
            
            // Set modal title
            const modalTitle = fileName ? `Preview: ${materialCode} - ${fileName}` : `Preview: ${materialCode}`;
            $('#previewModalTitle').text(modalTitle);
            
            // Prepare URLs
            const downloadUrl = url + (url.includes('?') ? '&dl=1' : '?dl=1');
            const viewUrl = url.replace('?dl=0', '').replace('&dl=0', '') + (url.includes('?') ? '&raw=1' : '?raw=1');
            
            // Set download and view links
            $('#downloadLink').attr('href', downloadUrl).attr('download', fileName || 'drawing');
            $('#viewOriginalLink').attr('href', viewUrl);
            
            // Show appropriate preview based on file type
            const normalizedExtension = fileExtension.toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(normalizedExtension)) {
                // Untuk images
                const img = $('#previewImage');
                img.attr('src', viewUrl);
                img.attr('alt', fileName || 'Drawing Preview');
                
                // Add error handling for image
                img.off('error').on('error', function() {
                    $('#imagePreviewContainer').addClass('d-none');
                    $('#filePreviewContainer').removeClass('d-none');
                });
                
                $('#imagePreviewContainer').removeClass('d-none');
                
            } else if (normalizedExtension === 'pdf') {
                // Untuk PDFs
                const iframe = $('#previewPdf');
                iframe.attr('src', viewUrl);
                $('#pdfPreviewContainer').removeClass('d-none');
                
            } else {
                // Untuk file types lain (DWG, DXF, etc.)
                $('#filePreviewContainer').removeClass('d-none');
            }
            
            // Remove any existing event handlers
            $('#previewModal').off('hidden.bs.modal');
            
            // Add new event handler with flag to prevent reopening material search
            let shouldReopenMaterialSearch = true;
            
            // Add event handler untuk tombol close di header
            $('#previewModalCloseBtn').off('click').on('click', function() {
                shouldReopenMaterialSearch = false;
            });
            
            // Add event handler untuk tombol close di footer
            $('#previewModal .preview-modal-footer button[data-bs-dismiss="modal"]').off('click').on('click', function() {
                shouldReopenMaterialSearch = false;
            });
            
            // Event handler ketika modal ditutup
            $('#previewModal').on('hidden.bs.modal', function () {
                // Reset preview containers
                $('#previewImage').attr('src', '');
                $('#previewPdf').attr('src', '');
                
                // Kembali ke modal Material Details & Drawings hanya jika perlu
                if (shouldReopenMaterialSearch && window.previewMaterialCode && window.previewMaterialCode.trim() !== '') {
                    setTimeout(() => {
                        searchAndShowMaterial(window.previewMaterialCode);
                    }, 300);
                }
                
                // Reset variabel
                window.previewMaterialCode = null;
                shouldReopenMaterialSearch = true;
                
                // Remove event handler untuk menghindari memory leak
                $('#previewModal').off('hidden.bs.modal');
            });
            
            // Show the modal
            const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
            previewModal.show();
        }

        // ========== DOWNLOAD DRAWING ==========
        function downloadDrawing(url, filename) {
            if (!url) {
                showToast('Download URL not available', 'error');
                return;
            }
            
            // Add dl=1 parameter for direct download
            const downloadUrl = url.includes('?') ? url + '&dl=1' : url + '?dl=1';
            
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = filename || 'drawing';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            showToast('Download started: ' + filename, 'success');
        }

        // ========== SHOW EDIT MODAL ==========
        function showEditModal(index) {
            // PERUBAHAN 1: Cek apakah user punya akses upload
            if (!userCanUpload) {
                showToast('You are not authorized to edit files. Only RnD users can upload.', 'error');
                return;
            }
            
            const fileObj = selectedFiles[index];
            $('#editFileName').text(fileObj.file.name);
            $('#editDrawingType').val(fileObj.drawing_type);
            $('#editRevision').val(fileObj.revision);
            
            if (fileObj.revision === 'other') {
                $('#editCustomRevision').removeClass('d-none').val(fileObj.custom_revision || '');
            } else {
                $('#editCustomRevision').addClass('d-none').val('');
            }
            
            $('#saveFileEditBtn').data('index', index);
            
            const editModal = new bootstrap.Modal(document.getElementById('editFileModal'));
            editModal.show();
        }

        // ========== EVENT HANDLERS ==========
        $(document).ready(function() {
            // Initialize drag & drop
            initializeDragDrop();
            
            // Set default plant
            $('#plant').val('3000');
            
            // Collapse button icon toggle untuk Upload New Shop Drawing
            // PERUBAHAN 4: Sudah menggunakan icon saja
            $('#collapseToggleBtn').on('click', function() {
                const icon = $(this).find('i');
                if ($(this).attr('aria-expanded') === 'true') {
                    icon.removeClass('bi-chevron-down').addClass('bi-chevron-up');
                } else {
                    icon.removeClass('bi-chevron-up').addClass('bi-chevron-down');
                }
            });
            
            // Add hover effects for icon buttons
            $(document).on('mouseenter', '.btn-icon', function() {
                const $icon = $(this).find('i');
                if ($(this).hasClass('preview')) {
                    $icon.addClass('bi-eye');
                    $icon.removeClass('bi-eye-fill');
                }
            });

            $(document).on('mouseleave', '.btn-icon', function() {
                const $icon = $(this).find('i');
                if ($(this).hasClass('preview')) {
                    $icon.removeClass('bi-eye');
                    $icon.addClass('bi-eye-fill');
                }
            });

            // Add click feedback animation
            $(document).on('click', '.btn-icon', function(e) {
                // Create ripple effect
                const $btn = $(this);
                $btn.addClass('active');
                
                // Remove active class after animation
                setTimeout(() => {
                    $btn.removeClass('active');
                }, 300);
            });

            // ========== MAIN SEARCH BUTTON ==========
            // PERUBAHAN 3: Tombol sudah dihapus, gunakan Enter key saja
            $(document).on('keypress', '#searchMaterial', function(e) {
                if (e.which === 13) {
                    const materialCode = $('#searchMaterial').val().trim();
                    searchAndShowMaterial(materialCode);
                }
            });

            // ========== CLICK TO BROWSE FUNCTIONALITY ==========
            $(document).on('click', '#uploadDropzone', function(e) {
                e.preventDefault();
                e.stopPropagation();
                // PERUBAHAN 1: Cek apakah user punya akses upload
                if (!userCanUpload) {
                    showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                    return;
                }
                $('#drawingFile').trigger('click');
            });

            // ========== FILE INPUT HANDLER ==========
            $(document).on('change', '#drawingFile', function(e) {
                const files = e.target.files;
                if (files && files.length > 0) {
                    handleFiles(files);
                }
            });

            // Handle file removal
            $(document).on('click', '.remove-file', function() {
                const index = $(this).data('index');
                selectedFiles.splice(index, 1);
                renderFilesList();
            });

            // Handle file edit - menggunakan modal statis
            $(document).on('click', '.edit-file', function() {
                const index = $(this).data('index');
                showEditModal(index);
            });

            // Handle revision change in edit modal
            $(document).on('change', '#editRevision', function() {
                if ($(this).val() === 'other') {
                    $('#editCustomRevision').removeClass('d-none');
                    $('#editCustomRevision').focus();
                } else {
                    $('#editCustomRevision').addClass('d-none');
                }
            });

            // Handle Apply to All button
            $(document).on('click', '#applyDrawingTypeToAll', function() {
                const drawingType = $('#editDrawingType').val();
                // Ubah drawing type untuk semua file
                selectedFiles.forEach(file => {
                    file.drawing_type = drawingType;
                });
                showToast('Drawing type applied to all files', 'success');
                // Re-render list
                renderFilesList();
            });

            // Handle save changes in edit modal
            $(document).on('click', '#saveFileEditBtn', function() {
                const index = $(this).data('index');
                const drawingType = $('#editDrawingType').val();
                const revision = $('#editRevision').val();
                const customRevision = $('#editCustomRevision').val();
                
                // Validasi
                if (revision === 'other') {
                    if (!customRevision || customRevision.trim() === '') {
                        showToast('Please enter a custom revision', 'warning');
                        $('#editCustomRevision').focus();
                        return;
                    }
                    const customRevPattern = /^[A-Za-z0-9]+$/;
                    if (!customRevPattern.test(customRevision.trim())) {
                        showToast('Custom revision can only contain letters and numbers', 'warning');
                        $('#editCustomRevision').focus();
                        return;
                    }
                }
                
                // Update file object
                selectedFiles[index].drawing_type = drawingType;
                selectedFiles[index].revision = revision;
                selectedFiles[index].custom_revision = customRevision;
                
                // Re-render list
                renderFilesList();
                
                // Tutup modal
                bootstrap.Modal.getInstance(document.getElementById('editFileModal')).hide();
                showToast('File details updated successfully', 'success');
            });

            // ========== USE VALIDATED MATERIAL BUTTON ==========
            $(document).on('click', '#btnUseValidatedMaterial', function() {
                if (currentValidatedMaterial) {
                    $('#materialCode').val(currentValidatedMaterial.material_code);
                    $('#description').val(currentValidatedMaterial.description);
                    $('#validationResultModal').modal('hide');
                    showToast('Material data applied to form', 'success');
                } else {
                    showToast('No validated material data available', 'warning');
                }
            });

            // ========== VALIDATE MATERIAL BUTTON ==========
            $(document).on('click', '#btnValidateMaterial', function() {
                // PERUBAHAN 1: Cek apakah user punya akses upload
                if (!userCanUpload) {
                    showToast('You are not authorized to validate materials for upload. Only RnD users can upload.', 'error');
                    return;
                }
                
                const materialCode = $('#materialCode').val().trim();
                const plant = $('#plant').val();
                
                if (!materialCode) {
                    showToast('Please enter material code', 'warning');
                    $('#materialCode').focus();
                    return;
                }
                
                validateMaterial(materialCode, plant);
            });

            // ========== UPLOAD BUTTON ==========
            $(document).on('click', '#btnUpload', function() {
                // PERUBAHAN 1: Cek apakah user punya akses upload
                if (!userCanUpload) {
                    showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                    return;
                }
                
                const materialCode = $('#materialCode').val().trim();
                const plant = $('#plant').val();
                const description = $('#description').val().trim();
                
                // Validasi required fields
                if (!materialCode) {
                    showToast('Please enter material code', 'warning');
                    $('#materialCode').focus();
                    return;
                }
                
                if (!description) {
                    showToast('Please search for material to auto-fill description', 'warning');
                    $('#materialCode').focus();
                    return;
                }
                
                if (selectedFiles.length === 0) {
                    showToast('Please select at least one drawing file', 'warning');
                    return;
                }
                
                // Validate each file
                for (let i = 0; i < selectedFiles.length; i++) {
                    const fileObj = selectedFiles[i];
                    
                    // Determine final revision
                    let finalRevision = 'Rev0'; // Default to Rev0
                    if (fileObj.revision === 'other' && fileObj.custom_revision) {
                        finalRevision = fileObj.custom_revision;
                    } else if (fileObj.revision && fileObj.revision !== 'other') {
                        finalRevision = fileObj.revision;
                    } else if (!fileObj.revision) {
                        finalRevision = 'Rev0'; // Fallback to Rev0
                    }
                    
                    // Validate drawing type
                    if (!fileObj.drawing_type) {
                        showToast(`Please select drawing type for file ${i + 1}`, 'warning');
                        return;
                    }
                                        
                    selectedFiles[i].final_revision = finalRevision;
                }
                
                uploadMultipleDrawings(materialCode, plant, description, selectedFiles);
            });

            // ========== MATERIAL SEARCH MODAL (small button) ==========
            // PERUBAHAN 3: Tombol sudah dihapus dari form

            // ========== CLEAR FORM ==========
            $(document).on('click', '#btnClearForm', function() {
                $('#materialCode').val('');
                $('#description').val('');
                $('#drawingFile').val('');
                selectedFiles = [];
                renderFilesList();
                showToast('Form cleared', 'info');
            });
            
            // ========== EMAIL SEND BUTTON ==========
            $(document).on('click', '#btnSendEmail', function() {
                const materialCode = $('#emailMaterialCode').val();
                const recipientEmail = $('#recipientEmail').val();
                const subject = $('#emailSubject').val();
                const message = $('#emailMessage').val();

                // Validasi
                if (!materialCode) {
                    showToast('Material code is required', 'error');
                    return;
                }

                if (!recipientEmail) {
                    showToast('Recipient email is required', 'error');
                    return;
                }

                // Show loading
                showLoading(true);

                $.ajax({
                    url: '{{ route("api.shop_drawings.send_email_request") }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        material_code: materialCode,
                        recipient_email: recipientEmail,
                        subject: subject,
                        message: message
                    },
                    success: function(response) {
                        showLoading(false);
                        if (response.status === 'success') {
                            showToast('Email request sent successfully', 'success');
                            $('#emailRequestModal').modal('hide');
                        } else {
                            showToast('Failed to send email: ' + response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        showLoading(false);
                        const errorMsg = xhr.responseJSON?.message || 'Failed to send email';
                        showToast('Error: ' + errorMsg, 'error');
                    }
                });
            });
            
            // ========== MODAL CLOSE HANDLERS ==========
            // Reset variabel saat modal Material Search ditutup
            $('#materialSearchModal').on('hidden.bs.modal', function () {
                window.previewMaterialCode = null;
            });

            // ========== HOVER EFFECTS FOR ICON BUTTONS ==========
            $(document).on('mouseenter', '.icon-btn', function() {
                const $this = $(this);
                // Hide the image icon
                $this.find('.icon-btn-img').css({
                    'opacity': '0',
                    'transform': 'scale(0.8)'
                }).hide();
                
                // Show the Bootstrap icon
                $this.find('.btn-icon-hover').css({
                    'opacity': '1',
                    'transform': 'scale(1.1)'
                }).show();
                
                // Ensure button expands
                $this.css('width', '140px');
            });

            $(document).on('mouseleave', '.icon-btn', function() {
                const $this = $(this);
                // Show the image icon
                $this.find('.icon-btn-img').css({
                    'opacity': '1',
                    'transform': 'scale(1)'
                }).show();
                
                // Hide the Bootstrap icon
                $this.find('.btn-icon-hover').css({
                    'opacity': '0',
                    'transform': 'scale(0.9)'
                }).hide();
                
                // Return to original size
                $this.css('width', '45px');
            });
        });

        // ========== VALIDATE MATERIAL FUNCTION ==========
        function validateMaterial(materialCode, plant) {
    console.log('Validating material:', materialCode, plant);
    
    $.ajax({
        url: '{{ route("api.shop_drawings.validate") }}',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            material_code: materialCode,
            plant: plant
        },
        beforeSend: function() {
            $('#btnValidateMaterial').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm"></span> Validating...');
            showLoading(true);
        },
        success: function(response) {
            console.log('SAP Validation Response:', response); // DEBUG
            
            if (response.status === 'success' && response.is_valid) {
                // **DEBUG: Log material data**
                console.log('Material data from SAP:', response.material);
                
                // Simpan data material lengkap
                currentValidatedMaterial = {
                    material_code: response.material.material_code,
                    description: response.material.description,
                    material_type: response.material.material_type || 'N/A',
                    material_group: response.material.material_group || 'N/A',
                    base_unit: response.material.base_unit || 'N/A',
                    plant: response.plant
                };
                
                // Auto-fill description field
                $('#description').val(response.material.description || '');
                
                // Show success message dengan data lengkap
                const material = response.material;
                const baseUnit = material.base_unit === 'ST' ? 'PC' : (material.base_unit || 'N/A');
                
                const html = `
                    <div class="alert" style="background: linear-gradient(135deg, #006666 0%, #008080 100%); color: white; border: none;">
                        <h5><i class="bi bi-check-circle"></i> Material Validated Successfully</h5>
                        <p class="mb-0 text-white">Material <strong>${material.material_code}</strong> is valid in SAP system.</p>
                        <p class="mb-0 text-white"><strong>Material Type:</strong> ${material.material_type || 'N/A'}</p>
                        <p class="mb-0 text-white"><strong>Material Group:</strong> ${material.material_group || 'N/A'}</p>
                        <p class="mb-0 text-white"><strong>Base Unit:</strong> ${baseUnit}</p>
                    </div>
                    
                `;
                
                $('#validationResultContent').html(html);
                $('#validationResultModal').modal('show');
                showToast('Material validation successful', 'success');
            } else {
                showToast('Material validation failed: ' + (response.message || 'Unknown error'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Validation error:', xhr.responseText);
            const errorMsg = xhr.responseJSON?.message || 'Validation failed';
            showToast('Validation error: ' + errorMsg, 'error');
        },
        complete: function() {
            $('#btnValidateMaterial').prop('disabled', false)
                .html('<i class="bi bi-check-circle"></i> Validate Material');
            showLoading(false);
        }
    });
}

        // ========== UPLOAD MULTIPLE DRAWINGS FUNCTION ==========
        function uploadMultipleDrawings(materialCode, plant, description, files) {
            // PERUBAHAN 1: Cek apakah user punya akses upload (seharusnya sudah dicek sebelumnya)
            if (!userCanUpload) {
                showToast('You are not authorized to upload drawings. Only RnD users can upload.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('material_code', materialCode);
            formData.append('plant', plant);
            formData.append('description', description);
            formData.append('file_count', files.length);
            
            // **PERBAIKAN: Selalu kirim material info jika ada**
            if (window.currentMaterialInfo) {
                formData.append('material_type', window.currentMaterialInfo.material_type || 'N/A');
                formData.append('material_group', window.currentMaterialInfo.material_group || 'N/A');
                formData.append('base_unit', window.currentMaterialInfo.base_unit || 'N/A');
            } else {
                // Jika tidak ada, set default
                formData.append('material_type', 'N/A');
                formData.append('material_group', 'N/A');
                formData.append('base_unit', 'N/A');
            }
            
            console.log('Material info being sent:', window.currentMaterialInfo);
            
            // Add each file with its metadata
            files.forEach((fileObj, index) => {
                formData.append(`files[${index}][file]`, fileObj.file);
                formData.append(`files[${index}][drawing_type]`, fileObj.drawing_type);
                formData.append(`files[${index}][revision]`, fileObj.final_revision || 'Rev0');                
            });
            
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
            
            $.ajax({
                url: '{{ route("api.shop_drawings.upload_multiple") }}',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    const xhr = new XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percent = (e.loaded / e.total) * 100;
                            $('#uploadProgress').removeClass('d-none')
                                .find('.progress-bar').css('width', percent + '%')
                                .text(Math.round(percent) + '%');
                        }
                    });
                    return xhr;
                },
                beforeSend: function() {
                    $('#btnUpload').prop('disabled', true)
                        .html('<span class="spinner-border spinner-border-sm"></span> Uploading...');
                    showLoading(true);
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showToast(`${response.uploaded_count} shop drawing(s) uploaded successfully!`, 'success');
                        
                        // Reset form
                        $('#materialCode').val('');
                        $('#description').val('');
                        selectedFiles = [];
                        renderFilesList();
                        $('#drawingFile').val('');
                        currentValidatedMaterial = null;
                        window.currentMaterialInfo = null;
                        
                        // Collapse the upload form
                        $('#uploadCollapse').collapse('hide');
                        $('#collapseToggleBtn').find('i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
                        
                        // Reload page setelah 2 detik
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showToast('Upload failed: ' + response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    const errorMsg = xhr.responseJSON?.message || 'Upload failed';
                    showToast('Upload error: ' + errorMsg, 'error');
                },
                complete: function() {
                    $('#btnUpload').prop('disabled', false)
                        .html('<i class="bi bi-upload"></i> Upload All Drawings');
                    $('#uploadProgress').addClass('d-none')
                        .find('.progress-bar').css('width', '0%').text('');
                    showLoading(false);
                }
            });
        }
    </script>
</body>
</html>