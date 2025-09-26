<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembuatan Material Master</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { padding: 20px; max-width: 800px; margin: auto; }
        .header { background-color: #f2f2f2; padding: 10px; text-align: center; font-size: 20px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #0d6efd; color: white; }
        .status-success { color: green; font-weight: bold; }
        .status-failed { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Laporan Status Pembuatan Material Master ke SAP</div>
        <p>Berikut adalah rincian status dari proses pembuatan Material Master yang telah dijalankan:</p>
        <table>
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Deskripsi</th>
                    <th>Plant</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result['material_code'] }}</td>
                        <td>{{ $result['description'] ?? 'N/A' }}</td>
                        <td>{{ $result['plant'] ?? 'N/A' }}</td>
                        <td>
                            @if (($result['status'] ?? 'Failed') === 'Success')
                                <span class="status-success">
                                    {{ $result['message'] ?? 'Berhasil dibuat' }}
                                </span>
                            @else
                                <span class="status-failed">
                                    Gagal: {{ $result['message'] ?? 'Unknown error' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="margin-top: 20px; font-size: 12px; color: #777;">
            Ini adalah email yang dibuat secara otomatis. Mohon untuk tidak membalas email ini.
        </p>
    </div>
</body>
</html>
