<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers; // Pastikan ini ada

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers; // Gunakan trait ini

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    // Pastikan ini mengarah ke halaman yang benar setelah login
    protected $redirectTo = '/converter';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        // Jika sudah login, redirect ke halaman utama
        if (Auth::check()) {
            return redirect()->route('converter.index');
        }
        return view('login');
    }

    /**
     * [PERBAIKAN] Mengganti 'email' menjadi 'username'
     * Get the login username to be used by the controller.
     */
    public function username()
    {
        return 'username';
    }

    /**
     * [PERBAIKAN] Memvalidasi 'username' bukan 'email'
     * Validate the user login request.
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password' => 'required|string',
        ]);
    }

    /**
     * [PERBAIKAN BARU] Memetakan 'username' ke kolom 'name' di database
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        // Ambil input 'username' dari form, tapi gunakan sebagai 'name' untuk query DB
        return [
            'name' => $request->input($this->username()),
            'password' => $request->input('password'),
        ];
    }

    /**
     * [PERBAIKAN] Mengubah pesan error dari 'email' ke 'username'
     * Get the failed login response instance.
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            // Ini akan menampilkan error di bawah input 'username'
            $this->username() => [__('auth.failed')],
        ]);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
