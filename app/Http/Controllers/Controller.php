<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // <-- PASTIKAN INI ADA

class Controller extends BaseController // <-- PASTIKAN INI ADA
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
