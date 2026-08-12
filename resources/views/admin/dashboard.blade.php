<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>

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
            margin-bottom: 20px;
        }

        h2 {
            margin-top: 32px;
        }

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-edit {
            background: #2563eb;
        }

        .btn-edit:hover {
            background: #1d4ed8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin: 0 0 4px 0;
        }

        .card p {
            margin: 0 0 16px 0;
            color: #777;
            font-size: 14px;
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
            color: #777;
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

        /* ===== Tabel Semua Akun ===== */
        table.accounts {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        table.accounts th,
        table.accounts td {
            text-align: left;
            padding: 10px 14px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        table.accounts th {
            background: #f0f0f0;
            font-size: 12px;
            text-transform: uppercase;
            color: #666;
        }

        table.accounts tr:last-child td {
            border-bottom: none;
        }

        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .role-karyawan {
            background: #e0e7ff;
            color: #3730a3;
        }

        .role-pejabat {
            background: #dcfce7;
            color: #166534;
        }

        .role-atasan_pejabat {
            background: #fef3c7;
            color: #92400e;
        }

        .role-admin {
            background: #fee2e2;
            color: #991b1b;
        }

        /* ===== Modal Tambah Akun ===== */
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

        .modal-box input,
        .modal-box select {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-family: Arial;
            font-size: 14px;
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
    <h1>Dashboard Admin</h1>

    <div style="display:flex; gap:8px;">
        <button type="button" class="btn-edit" onclick="openAddAccountModal()">
            + Tambah Akun
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</div>

<p>
    Selamat datang,
    <strong>{{ auth()->user()->name }}</strong>
</p>

<div class="modal-overlay" id="add-account-modal">
    <div class="modal-box">
        <h3>Tambah Akun</h3>

        <form method="POST" action="{{ route('admin.account.store') }}">
            @csrf

            <label for="acc-name">Nama Lengkap</label>
            <input type="text" id="acc-name" name="name" value="{{ old('name') }}" required>

            <label for="acc-username">Username</label>
            <input type="text" id="acc-username" name="username" value="{{ old('username') }}" required>

            <label for="acc-nik">NIK</label>
            <input type="text" id="acc-nik" name="nik" value="{{ old('nik') }}" required>
            <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                NIK ini juga dipakai sebagai password login.
            </small>

            <label for="acc-unit-kerja">Unit Kerja</label>
            <input type="text" id="acc-unit-kerja" name="unit_kerja" value="{{ old('unit_kerja') }}">

            <label for="acc-role">Role</label>
            <select id="acc-role" name="role" required>
                <option value="">-- Pilih Role --</option>
                <option value="karyawan" {{ old('role') == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                <option value="pejabat" {{ old('role') == 'pejabat' ? 'selected' : '' }}>Pejabat</option>
                <option value="atasan_pejabat" {{ old('role') == 'atasan_pejabat' ? 'selected' : '' }}>Atasan Pejabat</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>

            @if($errors->any())
                <div class="error">
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddAccountModal()">Batal</button>
                <button type="submit" class="btn-edit" id="acc-submit-btn">Simpan</button>
            </div>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<h2>Semua Akun</h2>

@if($accounts->isEmpty())

    <p class="empty">Belum ada akun.</p>

@else

    <table class="accounts">
        <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>NIK</th>
            <th>Unit Kerja</th>
            <th>Role</th>
        </tr>

        @foreach($accounts as $account)
            <tr>
                <td>{{ $account->name }}</td>
                <td>{{ $account->username ?? '-' }}</td>
                <td>{{ $account->nik ?? '-' }}</td>
                <td>{{ $account->unit_kerja ?? '-' }}</td>
                <td>
                    <span class="role-badge role-{{ $account->role }}">
                        {{ ucfirst(str_replace('_', ' ', $account->role)) }}
                    </span>
                </td>
            </tr>
        @endforeach
    </table>

@endif

<h2>Pilih Karyawan</h2>

@if($employees->isEmpty())

    <p class="empty">Belum ada data karyawan.</p>

@else

    <div class="grid">

        @foreach($employees as $employee)

            <div class="card">
                <h3>{{ $employee->name }}</h3>
                <p>{{ $employee->username }}</p>

                <a href="{{ route('admin.employee', $employee->id) }}">
                    Lihat Detail
                </a>
            </div>

        @endforeach

    </div>

@endif

<script>
    function openAddAccountModal() {
        document.getElementById('add-account-modal').classList.add('active');
    }

    function closeAddAccountModal() {
        document.getElementById('add-account-modal').classList.remove('active');
    }

    document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // Cegah klik "Simpan" dua kali (double-submit) yang bisa bikin
    // request terkirim dua kali dan muncul error "sudah ada" padahal
    // akun pertama sebenarnya berhasil dibuat.
    var addAccountForm = document.querySelector('#add-account-modal form');
    if (addAccountForm) {
        addAccountForm.addEventListener('submit', function () {
            var btn = document.getElementById('acc-submit-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Menyimpan...';
            }
        });
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('add-account-modal').classList.add('active');
        });
    @endif
</script>

</body>
</html>