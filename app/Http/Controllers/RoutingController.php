<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoutingController extends Controller
{
    /**
     * Menampilkan halaman utama untuk fitur Routing.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('routing');
    }

    // Anda bisa menambahkan fungsi-fungsi lain untuk fitur routing di sini,
    // seperti processRoutingFile(), uploadRoutingToSap(), dll.
}
