<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SAP Routing</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-image: url("{{ asset('images/ainun.jpg') }}"); background-size: cover; background-position: center; background-attachment: fixed; min-height: 100vh; padding-top: 1rem; padding-bottom: 1rem; }
        .card { background: rgba(75, 74, 74, 0.33); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1); color: white; }
        .converter-container { max-width: 800px; margin-top: 0; margin-bottom: 1rem; }
        .page-title-container { text-align: center; }
        .page-title-container .main-title { color: #fff; text-shadow: 1px 1px 3px rgba(0,0,0,0.3); margin-bottom: 0; }
        .page-title-container .subtitle { color: #fff; text-shadow: 1px 1px 2px rgba(0,0,0,0.2); font-size: 0.9rem; }
        .sap-logo-header { height: 80px; width: auto; margin-left: 20px; }
        .nav-pills .nav-link { background-color: rgba(255, 255, 255, 0.2); color: #f0f0f0; margin: 0 5px; transition: all 0.3s ease; border: 1px solid transparent; }
        .nav-pills .nav-link.active, .nav-pills .show>.nav-link { background-color: #ffffff64; color: #08e6ffd8; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .nav-pills .nav-link:hover:not(.active) { background-color: rgba(255, 255, 255, 0.4); color: #ffffff; border-color: rgba(255,255,255,0.5); }

        /* Kode Animasi Loader Baru */
        .loader {
            display: flex;
            justify-content: center;
            align-items: center;
            --animation: 2s ease-in-out infinite;
        }

        .loader .circle {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            width: 20px;
            height: 20px;
            border: solid 2px transparent;
            border-radius: 50%;
            margin: 0 10px;
            background-color: transparent;
            animation: circle-keys var(--animation);
        }

        .loader .circle .dot {
            position: absolute;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 16px;
            border-radius: 50%;
            animation: dot-keys var(--animation);
        }

        .loader .circle .outline {
            position: absolute;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            animation: outline-keys var(--animation);
        }

        .circle:nth-child(1) {
            --color: #4285f4; /* Blue */
            border-color: var(--color);
        }

        .circle:nth-child(1) .dot {
            background-color: var(--color);
        }

        .circle:nth-child(2) {
            --color: #ea4335; /* Red */
            border-color: var(--color);
            animation-delay: 0.3s;
        }

        .circle:nth-child(2) .dot {
            background-color: var(--color);
            animation-delay: 0.3s;
        }

        .circle:nth-child(3) {
            --color: #fbbc05; /* Yellow */
            border-color: var(--color);
            animation-delay: 0.6s;
        }

        .circle:nth-child(3) .dot {
            background-color: var(--color);
            animation-delay: 0.6s;
        }

        .circle:nth-child(4) {
            --color: #34a853; /* Green */
            border-color: var(--color);
            animation-delay: 0.9s;
        }

        .circle:nth-child(4) .dot {
            background-color: var(--color);
            animation-delay: 0.9s;
        }

        .circle:nth-child(5) {
            --color: #4285f4; /* Blue (repeat for continuity) */
            border-color: var(--color);
            animation-delay: 1.2s;
        }

        .circle:nth-child(5) .dot {
            background-color: var(--color);
            animation-delay: 1.2s;
        }

        .circle:nth-child(1) .outline {
            animation-delay: 0.9s;
        }

        .circle:nth-child(2) .outline {
            animation-delay: 1.2s;
        }

        .circle:nth-child(3) .outline {
            animation-delay: 1.5s;
        }

        .circle:nth-child(4) .outline {
            animation-delay: 1.8s;
        }

        .circle:nth-child(5) .outline {
            animation-delay: 2.1s;
        }

        @keyframes circle-keys {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.5);
                opacity: 0.5;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes dot-keys {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(0);
            }

            100% {
                transform: scale(1);
            }
        }

        @keyframes outline-keys {
            0% {
                transform: scale(0);
                outline: solid 20px var(--color);
                outline-offset: 0;
                opacity: 1;
            }

            100% {
                transform: scale(1);
                outline: solid 0 transparent;
                outline-offset: 20px;
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container converter-container">
        <div class="d-flex align-items-center justify-content-center mb-3">
            <div class="page-title-container">
                <h1 class="h3 main-title">SAP Routing Uploader</h1>
                <p class="subtitle mb-0">Manage and upload routing data</p>
            </div>
            <img src="{{ asset('images/saplogo.png') }}" alt="SAP Logo" class="sap-logo-header">
        </div>

        <div class="card p-4 p-lg-5">
            <div class="card-body">
                <ul class="nav nav-pills nav-fill mb-4">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('converter.index') ? 'active' : '' }}" href="{{ route('converter.index') }}">
                            <i class="bi bi-box-seam me-2"></i>Material Converter
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('bom.index') ? 'active' : '' }}" href="{{ route('bom.index') }}">
                            <i class="bi bi-diagram-3 me-2"></i>BOM Uploader
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('routing.index') ? 'active' : '' }}" href="{{ route('routing.index') }}">
                            <i class="bi bi-signpost-split me-2"></i>Routing
                        </a>
                    </li>
                </ul>
                <hr style="border-color: rgba(255,255,255,0.3);">

                <div class="text-center">
                    <div class="d-flex justify-content-center mb-4" style="height: 100px;">
                        <!-- Loader Animation Start -->
                        <div class="loader">
                            <div class="circle">
                                <div class="dot"></div>
                                <div class="outline"></div>
                            </div>
                            <div class="circle">
                                <div class="dot"></div>
                                <div class="outline"></div>
                            </div>
                            <div class="circle">
                                <div class="dot"></div>
                                <div class="outline"></div>
                            </div>
                            <div class="circle">
                                <div class="dot"></div>
                                <div class="outline"></div>
                            </div>
                            <div class="circle">
                                <div class="dot"></div>
                                <div class="outline"></div>
                            </div>
                        </div>
                        <!-- Loader Animation End -->
                    </div>
                    <h1>Halaman Routing</h1>
                    <p>Fitur untuk routing akan dikembangkan di sini.</p>
                </div>
            </div>
        </div>
        <footer class="text-center text-white mt-4">
            <small style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">© PT. Kayu Mebel Indonesia, 2025</small>
        </footer>
    </div>
</body>
</html>

