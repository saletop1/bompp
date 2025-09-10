<!DOCTYPE html>
<html>
<head>
    <title>SAP Material Upload Notification</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dddddd; text-align: left; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h2>SAP Material Upload Process Completed</h2>
    <p>The following materials have been successfully created in SAP:</p>
    <table>
        <thead>
            <tr>
                <th>Material Code</th>
                <th>Plant</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $item)
                <tr>
                    <td>{{ $item['material_code'] }}</td>
                    <td>{{ $item['plant'] }}</td>
                    <td>{{ $item['message'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p>This is an automated notification.</p>
</body>
</html>
