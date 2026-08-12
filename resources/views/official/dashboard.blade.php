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

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-korelasi {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-dinilai {
            background: #dcfce7;
            color: #166534;
        }

        .badge-belum-dinilai {
            background: #fef3c7;
            color: #92400e;
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

        .card small {
            display: block;
            color: #888;
            margin-bottom: 12px;
        }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-edit {
            background: #2563eb;
        }

        .btn-edit:hover {
            background: #1d4ed8;
        }

        .btn-delete {
            background: #dc2626;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .card-actions a,
        .card-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 10px;
            padding: 24px;
            width: 100%;
            max-width: 380px;
        }

        .modal-box h3 {
            margin-top: 0;
        }

        .modal-box label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 4px;
            margin-top: 12px;
        }

        .modal-box input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .modal-box .error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #111;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Pejabat</h1>

    <div style="display:flex; gap:8px;">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</div>

<p style="color:#555;">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>
</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($employees->isEmpty())

    <p class="empty">Belum ada data karyawan.</p>

@else

    <div class="grid">

        @foreach($employees as $employee)

            <div class="card">
                <strong>{{ $employee->name }}</strong>
                <small>
                    {{ $employee->username }}
                    @if($employee->unit_kerja)
                        &middot; {{ $employee->unit_kerja }}
                    @endif
                </small>

                <div class="badges">
                    <span class="badge badge-korelasi">
                        {{ $employee->feedbacks_received_count }} tanggapan korelasi
                    </span>

                    @if($employee->evaluations->isNotEmpty())
                        <span class="badge badge-dinilai">&#10003; Sudah dinilai</span>
                    @else
                        <span class="badge badge-belum-dinilai">Belum dinilai</span>
                    @endif
                </div>

                <a href="{{ route('official.employee', $employee->id) }}">
                    Berikan Penilaian
                </a>

                <div class="card-actions">
                    <button type="button" class="btn-edit" onclick="openEditModal({{ $employee->id }})">
                        Edit
                    </button>

                    <form method="POST"
                          action="{{ route('official.employee.destroy', $employee->id) }}"
                          onsubmit="return confirm('Yakin ingin menghapus data {{ $employee->name }}? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-delete">Hapus</button>
                    </form>
                </div>
            </div>

            <div class="modal-overlay" id="edit-modal-{{ $employee->id }}">
                <div class="modal-box">
                    <h3>Edit Data Karyawan</h3>

                    <form method="POST" action="{{ route('official.employee.update', $employee->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_employee_id" value="{{ $employee->id }}">

                        <label for="name-{{ $employee->id }}">Nama Lengkap</label>
                        <input type="text" id="name-{{ $employee->id }}" name="name"
                               value="{{ old('name', $employee->name) }}" required>

                        <label for="username-{{ $employee->id }}">Username</label>
                        <input type="text" id="username-{{ $employee->id }}" name="username"
                               value="{{ old('username', $employee->username) }}" required>

                        <label for="nik-{{ $employee->id }}">NIK</label>
                        <input type="text" id="nik-{{ $employee->id }}" name="nik"
                               value="{{ old('nik', $employee->nik) }}" required>
                        <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                            NIK ini juga dipakai sebagai password login. Ubah NIK = password ikut berubah.
                        </small>

                        <label for="unit-kerja-{{ $employee->id }}">Unit Kerja</label>
                        <input type="text" id="unit-kerja-{{ $employee->id }}" name="unit_kerja"
                               value="{{ old('unit_kerja', $employee->unit_kerja) }}">

                        @if($errors->any())
                            <div class="error">
                                @foreach($errors->all() as $error)
                                    {{ $error }}<br>
                                @endforeach
                            </div>
                        @endif

                        <div class="modal-footer">
                            <button type="button" class="btn-cancel" onclick="closeEditModal({{ $employee->id }})">
                                Batal
                            </button>
                            <button type="submit" class="btn-edit">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

        @endforeach

    </div>

@endif

<script>
    function openEditModal(id) {
        document.getElementById('edit-modal-' + id).classList.add('active');
    }

    function closeEditModal(id) {
        document.getElementById('edit-modal-' + id).classList.remove('active');
    }

    // Tutup modal saat klik area gelap di luar box
    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    @if($errors->any() && old('_form_employee_id'))
        document.addEventListener('DOMContentLoaded', function () {
            var modal = document.getElementById('edit-modal-{{ old('_form_employee_id') }}');
            if (modal) modal.classList.add('active');
        });
    @endif
</script>

</body>
</html>