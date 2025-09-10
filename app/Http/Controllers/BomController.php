<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BomController extends Controller
{
    public function index()
    {
        return view('bom');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx,csv',
            'plant' => 'required|string',
        ]);

        try {
            $collection = Excel::toCollection(null, $request->file('file'))->first();
            if ($collection->count() <= 1) {
                return back()->withErrors(['file' => 'File Excel tidak berisi data.']);
            }

            $header = array_map('trim', $collection->first()->toArray());
            $data = $collection->slice(1);

            $groupedBoms = [];
            foreach ($data as $row) {
                if ($row->filter()->isEmpty()) continue;

                $rowData = array_combine($header, $row->toArray());
                $parent = trim($rowData['Parent']);

                if (empty($parent)) continue;

                if (!isset($groupedBoms[$parent])) {
                    $groupedBoms[$parent] = [
                        'parent' => $parent,
                        'plant' => $request->input('plant'),
                        'bom_usage' => '1',
                        'base_quantity' => '1',
                        'base_unit' => 'PC',
                        'alternative' => '1',
                        'bom_text' => '',
                        'components' => [],
                    ];
                }
                $groupedBoms[$parent]['components'][] = [
                    'Child' => trim($rowData['Child']),
                    'Qty' => trim($rowData['Qty']),
                    'Unit' => trim($rowData['Unit']),
                    'Item Category' => trim($rowData['Item Category']),
                ];
            }

            $fileName = 'BOM_Processed_' . Str::random(10) . '.json';
            Storage::disk('local')->put('temp/' . $fileName, json_encode(array_values($groupedBoms)));

            return redirect()->route('bom.index')
                ->with('success', count($groupedBoms) . ' Parent BOM berhasil dikelompokkan.')
                ->with('processed_filename', $fileName);

        } catch (\Exception $e) {
            Log::error('BOM Upload Error: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Terjadi error saat memproses file: ' . $e->getMessage()]);
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
            return response()->json(['error' => 'File sementara tidak ditemukan.'], 404);
        }

        $bomsData = json_decode(Storage::disk('local')->get($filePath), true);
        $sapServiceUrl = 'http://localhost:5001/upload_bom';

        try {
            $response = Http::timeout(600)->post($sapServiceUrl, [
                'username' => $request->input('username'),
                'password' => $request->input('password'),
                'boms'     => $bomsData,
            ]);

            if ($response->successful()) {
                Storage::disk('local')->delete($filePath);
            }

            return $response->json();

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['status' => 'error', 'error' => 'Tidak bisa terhubung ke layanan SAP (Python).'], 503);
        }
    }
}
