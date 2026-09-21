{{--
    Shell bersama untuk semua halaman error custom (404, 403, 419, 429, 500, 503).
    Dipakai lewat @include('errors._shell', ['code' => ..., 'heading' => ..., 'message' => ...])
    dari masing-masing file kode status di folder ini.

    Sengaja dibuat standalone (tanpa @extends layout aplikasi) karena halaman
    error bisa saja muncul PERSIS ketika layout utama gagal dimuat (mis. saat
    500 karena error di provider/config) - jadi tidak boleh bergantung ke
    komponen/layout lain yang mungkin justru jadi sumber error.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ $heading }} · {{ config('app.name', 'Learning & Development') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/logo-dagsap.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: #FAF7F2;
            color: #171717;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .font-display { font-family: 'Playfair Display', serif; }

        .card {
            width: 100%;
            max-width: 480px;
            text-align: center;
        }

        .badge-wrap {
            width: 84px;
            height: 84px;
            border-radius: 9999px;
            background: white;
            box-shadow: 0 10px 30px rgba(29, 78, 216, 0.12);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }
        .badge-wrap img { width: 44px; height: 44px; object-fit: contain; }

        .code {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 5rem;
            line-height: 1;
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 8px;
        }

        h1 {
            font-size: 1.375rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: #171717;
        }

        p.desc {
            color: #52525b;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 10px;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.15s ease;
        }
        .btn:hover { opacity: 0.85; }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb, #1e3a8a);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #1d4ed8;
            border: 1px solid #e4e4e7;
        }

        footer {
            margin-top: 40px;
            font-size: 11px;
            color: #a1a1aa;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge-wrap">
            <img src="{{ asset('images/logo-dagsap.png') }}" alt="Logo">
        </div>

        <p class="code">{{ $code }}</p>
        <h1>{{ $heading }}</h1>
        <p class="desc">{{ $message }}</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Beranda</a>
            @if($code !== 419)
                <a href="javascript:history.back()" class="btn btn-secondary">Halaman Sebelumnya</a>
            @endif
        </div>

        <footer>
            &copy; {{ date('Y') }} PT Dagsap Endura Eatore &mdash; Learning &amp; Development
        </footer>
    </div>
</body>
</html>
