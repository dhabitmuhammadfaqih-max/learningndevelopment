<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Karyawan</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            margin-top: 10px;
        }

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Karyawan</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>

<p>
    Selamat datang,
    <strong>{{ auth()->user()->name }}</strong>
</p>

@if(session('success'))
    <div class="card">
        {{ session('success') }}
    </div>
@endif


<div class="card">

    <h2>Nilai Saya</h2>

    @if($myEvaluation)

        <h1>{{ $myEvaluation->score }}/100</h1>

        <p>
            <strong>Pejabat:</strong>
            {{ $myEvaluation->official->name }}
        </p>

        <p>
            <strong>Rekomendasi:</strong>
            {{ $myEvaluation->recommendation }}
        </p>

        <p>
            {{ $myEvaluation->feedback }}
        </p>

    @else

        <p>Penilaian belum tersedia.</p>

    @endif

</div>


<div class="card">

    <h2>Berikan Tanggapan Teman</h2>

    <form method="POST"
          action="{{ route('employee.feedback') }}">

        @csrf

        <label>Pilih Karyawan</label>

        <select name="employee_id">

            @foreach($employees as $employee)

                <option value="{{ $employee->id }}">
                    {{ $employee->name }}
                </option>

            @endforeach

        </select>

        <br><br>

        <textarea
            name="feedback"
            placeholder="Tulis tanggapan..."
            required
        ></textarea>

        <br><br>

        <button type="submit">
            Kirim Tanggapan
        </button>

    </form>

</div>


<div class="card">

    <h2>Tanggapan Terhadap Saya</h2>

    @forelse($myFeedbacks as $feedback)

        <div>
            <strong>
                {{ $feedback->reviewer->name }}
            </strong>

            <p>
                {{ $feedback->feedback }}
            </p>
        </div>

        <hr>

    @empty

        <p>Belum ada tanggapan.</p>

    @endforelse

</div>

</body>
</html>