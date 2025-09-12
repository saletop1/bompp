// app/Services/SapService.php (Contoh perubahan)

use Illuminate\Support\Facades\Http; // <-- Gunakan HTTP Client Laravel

class SapService
{
    protected $pythonApiUrl;

    public function __construct()
    {
        // Ambil URL dari file .env Anda
        $this->pythonApiUrl = env('PYTHON_SAP_API_URL', 'http://localhost:5001');
    }

    public function findMaterialByDescription(string $description): ?string
    {
        try {
            $response = Http::get($this->pythonApiUrl . '/find_material', [
                'description' => $description,
            ]);

            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json('material_code');
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Gagal menghubungi Python API: " . $e->getMessage());
            return null;
        }
    }

    // Anda akan melakukan hal yang sama untuk fungsi uploadBom, dll.
    // dengan method POST dan mengirimkan data JSON.
}

Dengan cara ini, file Python Anda menjadi pusat dari semua interaksi dengan SAP, dan Laravel Anda menjadi lebih bersih, hanya berfokus pada logika bisnis dan antarmuka pengguna.
