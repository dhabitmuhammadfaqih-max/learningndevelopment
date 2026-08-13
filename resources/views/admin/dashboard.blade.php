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

        .role-spg {
            background: #fce7f3;
            color: #9d174d;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            margin-left: 6px;
        }

        .status-sudah {
            background: #dcfce7;
            color: #166534;
        }

        .status-belum {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* ===== Badge status PDF ===== */
        .pdf-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .pdf-badge-ready {
            background: #dcfce7;
            color: #166534;
        }

        .pdf-badge-not-ready {
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

@if($pejabatBinaan->isNotEmpty())

    <h2>Pejabat yang Harus Anda Nilai</h2>

    <div class="grid">

        @foreach($pejabatBinaan as $pejabat)

            <div class="card">
                <h3 style="margin-top:0;">{{ $pejabat->name }}</h3>
                <p style="margin-top:-8px; font-size:12px; color:#888;">
                    {{ $pejabat->username }}
                    @if($pejabat->jabatan)
                        &middot; {{ $pejabat->jabatan }}
                    @endif
                </p>

                @if($pejabat->evaluated_count > 0)
                    <span class="status-badge status-sudah">&#10003; Sudah Dinilai</span>
                @else
                    <span class="status-badge status-belum">Belum Dinilai</span>
                @endif

                <br><br>

                <a href="{{ route('supervisor.official', $pejabat->id) }}">
                    Nilai Pejabat
                </a>
            </div>

        @endforeach

    </div>

@endif

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

            <label for="acc-jabatan">Jabatan</label>
            <input type="text" id="acc-jabatan" name="jabatan" value="{{ old('jabatan') }}">

            <label for="acc-role">Role</label>
            <select id="acc-role" name="role" required onchange="toggleSpgOption()">
                <option value="">-- Pilih Role --</option>
                <option value="karyawan" {{ old('role') == 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                <option value="pejabat" {{ old('role') == 'pejabat' ? 'selected' : '' }}>Pejabat</option>
                <option value="atasan_pejabat" {{ old('role') == 'atasan_pejabat' ? 'selected' : '' }}>Atasan Pejabat</option>
                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
            </select>

            <div id="acc-spg-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="acc-is-spg" name="is_spg" value="1" {{ old('is_spg') ? 'checked' : '' }}>
                    Akun ini SPG (tanggapan korelasi/teman bersifat opsional)
                </label>
            </div>

            <div id="acc-supervisor-wrapper" style="display:none;">
                <label for="acc-supervisor-id">Atasan Penilai</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="acc-supervisor-id" name="supervisor_id" style="flex:1;" onchange="updateSupervisorRoleBadge('acc-supervisor-id', 'acc-supervisor-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>
                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}" data-role="{{ $atasan->role }}" {{ old('supervisor_id') == $atasan->id ? 'selected' : '' }}>
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>
                    <span id="acc-supervisor-role-badge" class="role-badge" style="display:none;"></span>
                </div>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Bisa dipilih dari akun dengan role Pejabat, Atasan Pejabat, atau Admin.
                </small>
                @if($atasanList->isEmpty())
                    <small style="display:block; color:#b45309; font-size:11px; margin-top:2px;">
                        Belum ada akun dengan role Pejabat, Atasan Pejabat, atau Admin.
                    </small>
                @endif
            </div>

            <script>
                function toggleSpgOption() {
                    const role = document.getElementById('acc-role').value;
                    const spgWrapper = document.getElementById('acc-spg-wrapper');
                    const supervisorWrapper = document.getElementById('acc-supervisor-wrapper');

                    spgWrapper.style.display = role === 'karyawan' ? 'block' : 'none';
                    if (role !== 'karyawan') {
                        document.getElementById('acc-is-spg').checked = false;
                    }

                    supervisorWrapper.style.display = role === 'pejabat' ? 'block' : 'none';
                    if (role !== 'pejabat') {
                        document.getElementById('acc-supervisor-id').value = '';
                    }

                    updateSupervisorRoleBadge('acc-supervisor-id', 'acc-supervisor-role-badge');
                }
                document.addEventListener('DOMContentLoaded', toggleSpgOption);
            </script>

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

<div class="modal-overlay" id="edit-account-modal">
    <div class="modal-box">
        <h3>Edit Akun</h3>

        <form method="POST" id="edit-account-form" action="">
            @csrf
            @method('PUT')

            <label for="edit-name">Nama Lengkap</label>
            <input type="text" id="edit-name" name="name" required>

            <label for="edit-username">Username</label>
            <input type="text" id="edit-username" name="username" required>

            <label for="edit-nik">NIK</label>
            <input type="text" id="edit-nik" name="nik" required>
            <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                NIK ini juga dipakai sebagai password login.
            </small>

            <label for="edit-unit-kerja">Unit Kerja</label>
            <input type="text" id="edit-unit-kerja" name="unit_kerja">

            <label for="edit-jabatan">Jabatan</label>
            <input type="text" id="edit-jabatan" name="jabatan">

            <label for="edit-role">Role</label>
            <select id="edit-role" name="role" required onchange="toggleEditSpgOption()">
                <option value="karyawan">Karyawan</option>
                <option value="pejabat">Pejabat</option>
                <option value="atasan_pejabat">Atasan Pejabat</option>
                <option value="admin">Admin</option>
            </select>

            <div id="edit-spg-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="edit-is-spg" name="is_spg" value="1">
                    Akun ini SPG (tanggapan korelasi/teman bersifat opsional)
                </label>
            </div>

            <div id="edit-supervisor-wrapper" style="display:none;">
                <label for="edit-supervisor-id">Atasan Penilai</label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="edit-supervisor-id" name="supervisor_id" style="flex:1;" onchange="updateSupervisorRoleBadge('edit-supervisor-id', 'edit-supervisor-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>
                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}" data-role="{{ $atasan->role }}">{{ $atasan->name }}</option>
                        @endforeach
                    </select>
                    <span id="edit-supervisor-role-badge" class="role-badge" style="display:none;"></span>
                </div>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Bisa dipilih dari akun dengan role Pejabat, Atasan Pejabat, atau Admin.
                </small>
                @if($atasanList->isEmpty())
                    <small style="display:block; color:#b45309; font-size:11px; margin-top:2px;">
                        Belum ada akun dengan role Pejabat, Atasan Pejabat, atau Admin.
                    </small>
                @endif
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditAccountModal()">Batal</button>
                <button type="submit" class="btn-edit" id="edit-submit-btn">Simpan Perubahan</button>
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
            <th>Jabatan</th>
            <th>Role</th>
            <th>Atasan</th>
            <th>Aksi</th>
        </tr>

        @foreach($accounts as $account)
            <tr
                data-id="{{ $account->id }}"
                data-name="{{ $account->name }}"
                data-username="{{ $account->username }}"
                data-nik="{{ $account->nik }}"
                data-unit-kerja="{{ $account->unit_kerja }}"
                data-jabatan="{{ $account->jabatan }}"
                data-role="{{ $account->role }}"
                data-is-spg="{{ $account->is_spg ? '1' : '0' }}"
                data-supervisor-id="{{ $account->supervisor_id }}"
            >
                <td>{{ $account->name }}</td>
                <td>{{ $account->username ?? '-' }}</td>
                <td>{{ $account->nik ?? '-' }}</td>
                <td>{{ $account->unit_kerja ?? '-' }}</td>
                <td>{{ $account->jabatan ?? '-' }}</td>
                <td>
                    <span class="role-badge role-{{ $account->role }}">
                        {{ ucfirst(str_replace('_', ' ', $account->role)) }}
                    </span>
                    @if($account->is_spg)
                        <span class="role-badge role-spg">SPG</span>
                    @endif
                </td>
                <td>
                    @if($account->role === 'pejabat' && $account->supervisor)
                        {{ $account->supervisor->name }}
                        <span class="role-badge role-{{ $account->supervisor->role }}">
                            {{ ucfirst(str_replace('_', ' ', $account->supervisor->role)) }}
                        </span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <button type="button" class="btn-edit" style="padding:6px 12px; font-size:12px;" onclick="openEditAccountModal(this.closest('tr'))">
                        Edit
                    </button>
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
            @php
                // Syarat sama persis dengan pengecekan di AdminController::pdf().
                // Untuk akun dengan flag is_spg, minimal 3 korelasi bersifat opsional.
                $pdfReady = ($employee->is_spg || $employee->feedbacks_received_count >= 3)
                    && $employee->evaluations->isNotEmpty()
                    && $employee->supervisorFeedbacks->isNotEmpty();
            @endphp

            <div class="card">
                <h3>{{ $employee->name }}</h3>
                <p>
                    {{ $employee->username }}
                    <span class="role-badge role-{{ $employee->role }}">
                        {{ strtoupper($employee->role) }}
                    </span>
                    @if($employee->is_spg)
                        <span class="role-badge role-spg">SPG</span>
                    @endif
                </p>
                <p style="margin-top:-12px; font-size:12px;">
                    Izin {{ $employee->jumlah_izin ?? 0 }} &middot;
                    Sakit {{ $employee->jumlah_sakit ?? 0 }} &middot;
                    Alpa {{ $employee->jumlah_alpa ?? 0 }} &middot;
                    Terlambat {{ $employee->jumlah_terlambat ?? 0 }}
                    <br>
                    {{ $employee->contractStatusLabel() }}
                </p>

                @if($pdfReady)
                    <span class="pdf-badge pdf-badge-ready">&#10003; Sudah bisa di-print PDF</span>
                @else
                    <span class="pdf-badge pdf-badge-not-ready">Belum bisa di-print PDF</span>
                @endif

                <br>

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

    // Label & warna badge role dipakai buat menampilkan badge di samping
    // dropdown "Atasan Penilai" (mengikuti kelas .role-badge / .role-<role>
    // yang sudah didefinisikan di CSS).
    const ROLE_LABELS = {
        pejabat: 'Pejabat',
        atasan_pejabat: 'Atasan Pejabat',
        admin: 'Admin',
    };

    function updateSupervisorRoleBadge(selectId, badgeId) {
        const select = document.getElementById(selectId);
        const badge = document.getElementById(badgeId);
        if (!select || !badge) return;

        const selectedOption = select.options[select.selectedIndex];
        const role = selectedOption ? selectedOption.getAttribute('data-role') : null;

        if (!role) {
            badge.style.display = 'none';
            badge.textContent = '';
            badge.className = 'role-badge';
            return;
        }

        badge.textContent = ROLE_LABELS[role] || role;
        badge.className = 'role-badge role-' + role;
        badge.style.display = 'inline-block';
    }

    function toggleEditSpgOption() {
        const role = document.getElementById('edit-role').value;
        const spgWrapper = document.getElementById('edit-spg-wrapper');
        const supervisorWrapper = document.getElementById('edit-supervisor-wrapper');

        spgWrapper.style.display = role === 'karyawan' ? 'block' : 'none';
        if (role !== 'karyawan') {
            document.getElementById('edit-is-spg').checked = false;
        }

        supervisorWrapper.style.display = role === 'pejabat' ? 'block' : 'none';
        if (role !== 'pejabat') {
            document.getElementById('edit-supervisor-id').value = '';
        }

        updateSupervisorRoleBadge('edit-supervisor-id', 'edit-supervisor-role-badge');
    }

    function openEditAccountModal(row) {
        const data = row.dataset;

        document.getElementById('edit-account-form').action =
            '{{ url("/admin/akun") }}/' + data.id;

        document.getElementById('edit-name').value = data.name || '';
        document.getElementById('edit-username').value = data.username || '';
        document.getElementById('edit-nik').value = data.nik || '';
        document.getElementById('edit-unit-kerja').value = data.unitKerja || '';
        document.getElementById('edit-jabatan').value = data.jabatan || '';
        document.getElementById('edit-role').value = data.role || 'karyawan';
        document.getElementById('edit-is-spg').checked = data.isSpg === '1';
        document.getElementById('edit-supervisor-id').value = data.supervisorId || '';

        toggleEditSpgOption();

        document.getElementById('edit-account-modal').classList.add('active');
    }

    function closeEditAccountModal() {
        document.getElementById('edit-account-modal').classList.remove('active');
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

    var editAccountForm = document.getElementById('edit-account-form');
    if (editAccountForm) {
        editAccountForm.addEventListener('submit', function () {
            var btn = document.getElementById('edit-submit-btn');
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