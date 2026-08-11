<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Pejabat</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card strong {
            display: block;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .card a {
            display: inline-block;
            padding: 8px 16px;
            background: #111;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .card a:hover {
            background: #333;
        }

        .empty {
            color: #999;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Pejabat</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>

<p style="color:#555;">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>
</p>

@if($employees->isEmpty())

    <p class="empty">Belum ada data karyawan.</p>

@else

    <div class="grid">

        @foreach($employees as $employee)

            <div class="card">
                <strong>{{ $employee->name }}</strong>

                <a href="{{ route('official.employee', $employee->id) }}">
                    Berikan Penilaian
                </a>
            </div>

        @endforeach

    </div>

@endif

</body>
</html>