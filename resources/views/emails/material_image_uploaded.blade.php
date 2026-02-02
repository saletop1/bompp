<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gambar Material Diupload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
            text-align: center;
        }
        .content {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .material-info {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        .preview-image {
            max-width: 100%;
            border-radius: 5px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>✅ Gambar Material Berhasil Diupload</h1>
    </div>
    
    <div class="content">
        <p>Halo,</p>
        
        <p>Gambar untuk material berikut berhasil diupload ke sistem:</p>
        
        <div class="material-info">
            <strong>📦 Kode Material:</strong> {{ $materialImage->material_code }}<br>
            <strong>🏭 Plant:</strong> {{ $materialImage->plant }}<br>
            <strong>📝 Deskripsi:</strong> {{ $materialImage->description }}<br>
            <strong>📎 File:</strong> {{ $materialImage->filename }}<br>
            <strong>📏 Ukuran:</strong> {{ number_format($materialImage->file_size / 1024, 2) }} KB<br>
            <strong>👤 Diupload oleh:</strong> {{ $materialImage->user->name ?? 'N/A' }}<br>
            <strong>⏰ Waktu:</strong> {{ $materialImage->created_at->format('d M Y H:i') }}
        </div>

        <div style="text-align: center;">
            <img src="{{ $materialImage->dropbox_share_url }}?raw=1" 
                 alt="{{ $materialImage->description }}"
                 class="preview-image"
                 style="max-width: 300px;">
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="{{ $materialImage->dropbox_share_url }}?raw=1" class="btn" target="_blank">
                🔍 Lihat Gambar Lengkap
            </a>
            
            <a href="{{ route('material_images.index') }}" class="btn btn-secondary">
                📁 Buka Menu Material Images
            </a>
        </div>

        <p style="margin-top: 20px;">
            <em>Gambar ini telah tersimpan dengan aman di Dropbox dan dapat diakses kapan saja.</em>
        </p>
    </div>
    
    <div class="footer">
        <p>Ini adalah email notifikasi otomatis dari SAP Material Master System.</p>
        <p>© {{ date('Y') }} PT. Kayu Mebel Indonesia. All rights reserved.</p>
    </div>
</body>
</html>