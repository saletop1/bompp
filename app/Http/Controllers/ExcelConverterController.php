<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Str;

class ExcelConverterController extends Controller
{
    public function index()
    {
        return view('converter');
    }

    public function generateMaterialCode(Request $request)
    {
        $request->validate(['material_type' => 'required|string']);
        $materialType = $request->query('material_type');
        $sapServiceUrl = 'http://localhost:5001/get_next_material';
        try {
            $response = Http::get($sapServiceUrl, [ 'material_type' => $materialType ]);
            if ($response->successful()) { return $response->json(); }
            else { return response()->json(['error' => $response->json()['error'] ?? 'Layanan SAP tidak merespon.'], $response->status()); }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['error' => 'Tidak bisa terhubung ke layanan SAP.'], 503);
        }
    }

    public function uploadToSap(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $filePath = 'temp/' . $request->input('filename');

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json(['error' => 'File tidak ditemukan di server. Mungkin sudah kedaluwarsa.'], 404);
        }

        $dataToUpload = Excel::toCollection(null, Storage::disk('local')->path($filePath))->first();
        $header = $dataToUpload->shift()->toArray();
        $materials = [];
        foreach ($dataToUpload as $row) {
            if ($row->filter()->isNotEmpty() && (count($header) === count($row))) {
                $materials[] = array_combine($header, $row->toArray());
            }
        }

        $sapServiceUrl = 'http://localhost:5001/upload_material';
        try {
            $response = Http::timeout(300)->post($sapServiceUrl, [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'materials' => $materials,
            ]);

            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] === 'success') {
                Storage::disk('local')->delete($filePath);
            }

            return $response->json();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['status' => 'error', 'error' => 'Tidak bisa terhubung ke layanan SAP (Python).'], 503);
        }
    }

    public function upload(Request $request)
    {
        if ($request->session()->has('download_filename')) {
            $oldFilename = $request->session()->get('download_filename');
            $oldFilePath = 'temp/' . $oldFilename;
            if (Storage::disk('local')->exists($oldFilePath)) {
                Storage::disk('local')->delete($oldFilePath);
            }
        }

        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv',
            'start_material_code' => 'required|string',
            'material_type' => 'required|string',
            'plant' => 'required|integer',
            'division' => ['required_if:material_type,FERT', 'nullable', 'string'],
            'distribution_channel' => ['required_if:material_type,FERT', 'nullable', 'string'],
        ]);

        try {
            $collection = Excel::toCollection(null, $request->file('file'))->first();
            if ($collection->count() <= 1) {
                return back()->withErrors(['file' => 'File yang Anda upload tidak berisi data.']);
            }

            $inventorHeader = $collection->first()->toArray();
            $inventorData = $collection->slice(1);
            $divisionMap = [
                '01' => ['acct_group' => '01', 'val_class' => 'FG01', 'mat_group' => 'FFG001'],
                '02' => ['acct_group' => '02', 'val_class' => 'FG02', 'mat_group' => 'FFG009'],
                '03' => ['acct_group' => '03', 'val_class' => 'FG06', 'mat_group' => 'FFG008'],
                '04' => ['acct_group' => '04', 'val_class' => 'FG04', 'mat_group' => 'FFG004'],
                '05' => ['acct_group' => '05', 'val_class' => 'FG03', 'mat_group' => 'FFG002'],
                '06' => ['acct_group' => '06', 'val_class' => 'FG05', 'mat_group' => 'FFG003'],
                '07' => ['acct_group' => '07', 'val_class' => 'FG07', 'mat_group' => 'FFG007'],
                '08' => ['acct_group' => '08', 'val_class' => 'FG10', 'mat_group' => 'FFG005'],
                '09' => ['acct_group' => '09', 'val_class' => 'FG01', 'mat_group' => 'FFG001'],
                '10' => ['acct_group' => '10', 'val_class' => 'FG08', 'mat_group' => 'FFG007'],
                '00' => ['acct_group' => '00', 'val_class' => 'SF01', 'mat_group' => ''],
            ];
            $columnMapping = [ "Material Description" => "Material Description", "Base Unit of Measure" => "Base Unit of Measure", "Dimension" => "Dimension", "MRP GROUP" => "MRP GROUP", "MRP Controller" => "MRP Controller", "Material Group" => "Material Group", "Storage Location" => "Storage Location", "Production Storage Location" => "Storage Location", "Document" => "Document" ];
            $sapHeader = [ 'Material', 'Industry Sector', 'Old material number', 'Material Type', 'Material Group', 'Base Unit of Measure', 'Material Description', 'Division', 'General item cat group', 'Prod./insp. Memo', 'Document', 'Ind. Std Desc', 'Dimension', 'Plant', 'Storage Location', 'Sales Organization', 'Distribution Channel', 'Delivery Plant', 'Sales Unit', 'Tax Country', 'Tax Class', 'Tax Cat', 'Item Category Group', 'Acct assignment grp', 'Mat Group 1', 'Mat Group 2', 'Mat Group 3', 'Mat Group 4', 'Mat Group 5', 'Trans Group', 'Loadin Group', 'Material Package', 'Mat pack type', 'Batch Management', 'Profit Center', 'Valuation Class', 'StandardPrc', 'MovingAvg', 'Price Unit', 'Price Control', 'Price Unit Hard Currency', 'Denominator', 'Alternative UoM', 'Numerator', 'Length', 'Width', 'Height', 'Unit of Dimension', 'Gross Weight', 'Weight Unit', 'Net Weight', 'Volume', 'Purchasing Group', 'Volume Unit', 'Proportion unit', 'Class', 'WARNA ', 'VOLUME PRODUCT', 'MRP Type', 'MRP GROUP', 'MRP Controller', 'Lot Size', 'Min Lot Size', 'Max Lot Size', 'Rounding Value', 'Procurement Type', 'Special Procurement Type', 'Backflush Indicator', 'Inhouse Production', 'Pl. Deliv. Time', 'GR Processing Time', 'Schedulled Margin Key', 'Safety Stock', 'Strategy Group', 'Consumption Mode', 'Forward Consumption Period', 'Backward Consumption Period', 'period indicator', 'fiscal year', 'Availability Check', 'Selection Method', 'Individual Collective', 'Unit Of Issue', 'Production Storage Location', 'Storage loc. for EP', 'Prod Schedule Profile', 'Under Delivery Tolerance', 'Over Delivery Tolerance', 'Unlt Deliv Tol', 'Material-related origin', 'Ind Qty Structure', 'Costing lot size', 'Do Not Cost', 'Plant-sp.matl status', 'Stock Determination Group', 'Unnamed: 95', 'Inspection Type', 'Inspection With Task List' ];
            $sapData = [];
            $startMaterialCode = $request->input('start_material_code');
            preg_match('/(.*\D)?(\d+)$/', $startMaterialCode, $matches);
            $prefix = $matches[1] ?? '';
            $number = isset($matches[2]) ? intval($matches[2]) : 0;
            $padding = isset($matches[2]) ? strlen($matches[2]) : 0;
            $selectedMaterialType = $request->input('material_type');

            foreach ($inventorData as $index => $inventorRow) {
                if ($inventorRow->filter()->isEmpty()) {
                    continue;
                }
                if (count($inventorHeader) !== count($inventorRow)) {
                    Log::warning('Melewati baris ke-' . ($index + 2) . ' karena jumlah kolom tidak cocok.');
                    continue;
                }
                $rowData = array_combine($inventorHeader, $inventorRow->toArray());

                $tempSapRow = array_fill_keys($sapHeader, '');
                foreach ($columnMapping as $sapCol => $inventorCol) {
                    if (isset($rowData[$inventorCol])) {
                        $tempSapRow[$sapCol] = strtoupper(trim((string)$rowData[$inventorCol]));
                    }
                }

                $selectedPlant = $request->input('plant');
                $profitCenterMap = [ '3000' => '300301', '2000' => '200301', '1000' => '100301' ];
                $profitCenter = $profitCenterMap[$selectedPlant] ?? '';
                $currentNumberStr = $padding > 0 ? str_pad($number, $padding, '0', STR_PAD_LEFT) : $number;
                $tempSapRow["Material"] = $prefix . $currentNumberStr;
                $number++;
                $tempSapRow["Material Type"] = $selectedMaterialType;
                $tempSapRow["Plant"] = $selectedPlant;
                $tempSapRow["Profit Center"] = $profitCenter;
                $tempSapRow["Price Control"] = "S";
                $tempSapRow["Industry Sector"] = "F";
                $tempSapRow["Division"] = "M3";
                $tempSapRow["General item cat group"] = "NORM";
                $tempSapRow["Batch Management"] = "X";
                $tempSapRow["Valuation Class"] = "SF01";
                $tempSapRow["Price Unit"] = "1";
                $tempSapRow["Class"] = "PRODUCTION";
                $tempSapRow["MRP Type"] = "PD";
                $tempSapRow["Lot Size"] = "EX";
                $tempSapRow["Procurement Type"] = "E";
                $tempSapRow["Backflush Indicator"] = "1";
                $tempSapRow["Schedulled Margin Key"] = "000";
                $tempSapRow["Strategy Group"] = "40";
                $tempSapRow["period indicator"] = "M";
                $tempSapRow["Availability Check"] = "KP";
                $tempSapRow["Individual Collective"] = "1";
                $tempSapRow["Prod Schedule Profile"] = "000002";
                $tempSapRow["Material-related origin"] = "X";
                $tempSapRow["Ind Qty Structure"] = "X";
                $tempSapRow["Plant-sp.matl status"] = "03";
                $tempSapRow["Stock Determination Group"] = "0001";
                $tempSapRow["Inspection Type"] = "04";
                $tempSapRow["Inspection With Task List"] = "X";
                $tempSapRow["Costing lot size"] = "100";
                if ($selectedMaterialType === 'FERT') {
                    $tempSapRow['Sales Organization'] = '1000';
                    $tempSapRow['Tax Country'] = 'ID';
                    $tempSapRow['Tax Class'] = '1';
                    $tempSapRow['Tax Cat'] = 'ZPPN';
                    $tempSapRow['Item Category Group'] = 'Z001';
                    $tempSapRow['Trans Group'] = '0001';
                    $tempSapRow['Loading Group'] = '0001';
                    $tempSapRow['Material Package'] = 'ZMG1';
                    $selectedDivision = $request->input('division');
                    $tempSapRow['Division'] = $selectedDivision;
                    $tempSapRow['Distribution Channel'] = $request->input('distribution_channel');
                    if (isset($divisionMap[$selectedDivision])) {
                        $tempSapRow['Acct assignment grp'] = $divisionMap[$selectedDivision]['acct_group'];
                        $tempSapRow['Valuation Class'] = $divisionMap[$selectedDivision]['val_class'];
                        $tempSapRow['Material Group'] = $divisionMap[$selectedDivision]['mat_group'];
                    }
                } elseif ($selectedMaterialType === 'VERP') {
                    $tempSapRow['Division'] = 'M5';
                    $tempSapRow['Valuation Class'] = 'PK01';
                }
                $finalRow = [];
                foreach ($sapHeader as $headerName) { $finalRow[] = $tempSapRow[$headerName]; }
                $sapData[] = $finalRow;
            }

            if (empty($sapData)) {
                return back()->withErrors(['file' => 'Tidak ada baris data yang valid untuk diproses setelah validasi.']);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($sapHeader, NULL, 'A1');
            $sheet->fromArray($sapData, NULL, 'A2');
            $writer = new Xlsx($spreadsheet);
            $fileName = 'SAP_Ready_' . Str::random(10) . '.xlsx';
            $filePath = 'temp/' . $fileName;
            if (!Storage::disk('local')->exists('temp')) { Storage::disk('local')->makeDirectory('temp'); }
            $writer->save(Storage::disk('local')->path($filePath));

            // --- PERBAIKAN DI SINI: Menyimpan Plant ke session ---
            return redirect()->route('converter.index')
                ->with('success', count($sapData) . ' baris data berhasil diproses!')
                ->with('download_filename', $fileName)
                ->with('processed_plant', $request->input('plant'));

        } catch (\Exception $e) {
            Log::error('Error saat upload file: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Terjadi error internal saat memproses file. Silakan cek log.']);
        }
    }

    public function download($filename)
    {
        $filePath = 'temp/' . $filename;
        $fullPath = Storage::disk('local')->path($filePath);
        if (!Storage::disk('local')->exists($filePath)) {
            return redirect()->route('converter.index')->withErrors(['file' => 'File unduhan tidak ditemukan atau sudah kedaluwarsa.']);
        }
        return response()->download($fullPath, 'SAP_Upload_Ready.xlsx');
    }

    public function activateQm(Request $request)
    {
        // --- PERBAIKAN DI SINI: Validasi disesuaikan dengan data dari JavaScript ---
        $request->validate([
            'materials' => 'required|array',
            'materials.*' => 'array', // Memastikan setiap item adalah array/object
            'materials.*.matnr' => 'required|string',
            'materials.*.werks' => 'required|string',
            'username'  => 'required|string',
            'password'  => 'required|string',
        ]);

        $sapServiceUrl = 'http://localhost:5001/activate_qm';

        try {
            $response = Http::timeout(300)->post($sapServiceUrl, [
                'username'  => $request->input('username'),
                'password'  => $request->input('password'),
                'materials' => $request->input('materials'),
            ]);

            return $response->json();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['status' => 'error', 'error' => 'Tidak bisa terhubung ke layanan SAP (Python).'], 503);
        }
    }
}
