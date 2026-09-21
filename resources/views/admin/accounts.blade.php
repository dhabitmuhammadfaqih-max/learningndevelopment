<x-dashboard-layout title="Semua Akun">

@include('admin.partials.styles')

<div id="hrd-page">

<div class="hero-banner">
    <div>
        <h2>Semua Akun</h2>
        <p>Tambah, edit, hapus, atau import akun pegawai, pejabat, dan HRD.</p>
    </div>

    <div class="hero-actions">
        <button type="button" class="btn-edit" onclick="openAddAccountModal()">
            + Tambah Akun
        </button>

        <button type="button" class="btn-edit btn-purple" onclick="openImportModal()">
            &#8593; Import dari Excel
        </button>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@if(session('import_report'))
    @php
        $report = session('import_report');
    @endphp

    <div class="alert" style="background:#f3f4f6; color:#111;">
        <strong>Hasil Import Excel</strong>

        <p style="margin:6px 0;">
            {{ count($report['created']) }} akun berhasil dibuat,
            {{ count($report['updated'] ?? []) }} akun berhasil diupdate,
            {{ count($report['skipped']) }} baris dilewati,
            {{ count($report['warnings']) }} peringatan (mis. Penilai tidak ketemu).
        </p>

        @if(!empty($report['processed_sheets']))
            <p style="margin:6px 0; font-size:13px;">
                {{ count($report['processed_sheets']) }} sheet berhasil diproses:
                {{ implode(', ', $report['processed_sheets']) }}.
            </p>
        @endif

        @if(!empty($report['created']))
            <details style="margin-top:8px;">
                <summary style="cursor:pointer; color:#166534;">
                    Berhasil dibuat ({{ count($report['created']) }})
                </summary>

                <ul style="margin:6px 0 0; padding-left:18px; font-size:13px; color:#166534;">
                    @foreach($report['created'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if(!empty($report['updated']))
            <details style="margin-top:8px;">
                <summary style="cursor:pointer; color:#1d4ed8;">
                    Berhasil diupdate ({{ count($report['updated']) }})
                </summary>

                <ul style="margin:6px 0 0; padding-left:18px; font-size:13px; color:#1d4ed8;">
                    @foreach($report['updated'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if(!empty($report['skipped']))
            <details style="margin-top:8px;" open>
                <summary style="cursor:pointer; color:#b91c1c;">
                    Dilewati ({{ count($report['skipped']) }})
                </summary>

                <ul style="margin:6px 0 0; padding-left:18px; font-size:13px; color:#b91c1c;">
                    @foreach($report['skipped'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        @if(!empty($report['warnings']))
            <details style="margin-top:8px;" open>
                <summary style="cursor:pointer; color:#92400e;">
                    Peringatan ({{ count($report['warnings']) }})
                </summary>

                <ul style="margin:6px 0 0; padding-left:18px; font-size:13px; color:#92400e;">
                    @foreach($report['warnings'] as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
@endif

<section id="semua-akun">

@if($accounts->isEmpty())

    <p class="empty">Belum ada akun.</p>

@else

    <div class="filter-bar">
        <input type="text"
               id="account-search"
               placeholder="Cari nama, username, NIK, unit kerja, atau jabatan..."
               oninput="filterAccounts()">

        <select id="account-role-filter" onchange="filterAccounts()">
            <option value="">Semua Role</option>
            <option value="pegawai">Pegawai</option>
            <option value="pejabat">Pejabat</option>
            <option value="hrd">HRD</option>
        </select>

        <button type="button"
                class="btn-reset-filter"
                onclick="resetAccountFilter()">
            Reset
        </button>

        <span class="filter-count" id="account-filter-count"></span>
    </div>

    <div class="accounts-table-wrapper">
    <table class="accounts" id="accounts-table">
        <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>NIK</th>
            <th>Vendor</th>
            <th>Status</th>
            <th>Status Kontrak</th>
            <th>Unit Kerja</th>
            <th>Jabatan</th>
            <th>Role</th>
            <th>Atasan</th>
            <th>Atasan Penilai</th>
            <th>Aksi</th>
        </tr>

        @foreach($accounts as $account)
            <tr class="account-row"
                data-search="{{ strtolower(($account->name ?? '') . ' ' . ($account->username ?? '') . ' ' . ($account->nik ?? '') . ' ' . ($account->unit_kerja ?? '') . ' ' . ($account->jabatan ?? '') . ' ' . ($account->vendor ?? '') . ' ' . ($account->status ?? '')) }}"
                data-role-filter="{{ $account->role }}"
                data-id="{{ $account->id }}"
                data-name="{{ $account->name }}"
                data-username="{{ $account->username }}"
                data-nik="{{ $account->nik }}"
                data-vendor="{{ $account->vendor }}"
                data-status="{{ $account->status }}"
                data-unit-kerja="{{ $account->unit_kerja }}"
                data-jabatan="{{ $account->jabatan }}"
                data-role="{{ $account->role }}"
                data-is-spg="{{ $account->is_spg ? '1' : '0' }}"
                data-menilai-manual="{{ $account->menilai_secara_manual ? '1' : '0' }}"
                data-boleh-menilai="{{ $account->boleh_menilai_pegawai_lain ? '1' : '0' }}"
                data-supervisor-id="{{ $account->supervisor_id }}"
                data-atasan-pejabat-id="{{ $account->atasan_pejabat_id }}"
                data-atasan-penilai-pejabat-id="{{ $account->atasan_penilai_pejabat_id }}">

                <td>{{ $account->name }}</td>
                <td>{{ $account->username ?? '-' }}</td>
                <td>{{ $account->nik ?? '-' }}</td>
                <td>{{ $account->vendor ?? '-' }}</td>
                <td>{{ $account->status ?? '-' }}</td>
                <td>
                    @if(in_array($account->role, ['pejabat', 'pegawai']))
                        @if($account->statusKontrakTerbuka())
                            <span class="role-badge role-spg" style="background:#dcfce7; color:#166534;">Terbuka</span>
                        @else
                            <span class="role-badge role-spg" style="background:#fee2e2; color:#991b1b;">Tertutup</span>
                        @endif
                    @else
                        -
                    @endif
                </td>
                <td>{{ $account->unit_kerja ?? '-' }}</td>
                <td>{{ $account->jabatan ?? '-' }}</td>

                <td>
                    <span class="role-badge role-{{ $account->role }}">
                        {{ ucfirst(str_replace('_', ' ', $account->role)) }}
                    </span>

                    @if($account->is_spg)
                        <span class="role-badge role-spg">
                            SPG
                        </span>
                    @endif

                    @if($account->menilai_secara_manual)
                        <span class="role-badge role-spg" title="Akun ini menilai SEMUA bawahannya secara manual - pegawai/pejabat yang ditugaskan ke akun ini otomatis tidak menghambat cetak PDF">
                            Menilai Manual
                        </span>
                    @endif
                </td>

                <td>
                    @if($account->supervisor)
                        {{ $account->supervisor->name }}

                        <span class="role-badge role-{{ $account->supervisor->role }}">
                            {{ ucfirst(str_replace('_', ' ', $account->supervisor->role)) }}
                        </span>
                    @else
                        -
                    @endif
                </td>

                <td>
                    @php
                        // Pegawai pakai kolom atasan_pejabat_id, Pejabat &
                        // HRD pakai atasan_penilai_pejabat_id - lihat
                        // ROLES_WITH_ATASAN_PENILAI_PEJABAT di HrdController.
                        $atasanPenilai = $account->role === 'pegawai'
                            ? $account->atasanPejabat
                            : $account->atasanPenilaiPejabat;
                    @endphp

                    @if($atasanPenilai)
                        {{ $atasanPenilai->name }}

                        <span class="role-badge role-{{ $atasanPenilai->role }}">
                            {{ ucfirst(str_replace('_', ' ', $atasanPenilai->role)) }}
                        </span>
                    @else
                        -
                        <br>
                        <span class="role-badge"
                              style="background:#fef3c7; color:#92400e; margin-top:4px; display:inline-block;"
                              title="Atasan Penilai (Tanggapan Atasan) belum ditugaskan untuk akun ini - tugaskan lewat Edit.">
                            &#9888; Atasan Penilai belum diisi
                        </span>
                    @endif
                </td>

                <td>
                    <div class="account-actions">
                        <button type="button"
                                class="btn-edit"
                                style="padding:6px 12px; font-size:12px;"
                                onclick="openEditAccountModal(this.closest('tr'))">
                            Edit
                        </button>

                        @if(in_array($account->role, ['pejabat', 'pegawai']))
                            <form method="POST"
                                  action="{{ route('admin.account.toggleStatusKontrak', $account->id) }}"
                                  style="display:inline;"
                                  onsubmit="return confirmDialog(event, '{{ $account->statusKontrakTerbuka() ? 'Tutup' : 'Buka' }} Status untuk akun ini? Kalau ditutup, {{ $account->name }} tidak akan bisa melihat badge Status siapapun - baik miliknya sendiri maupun pegawai/pejabat lain - selama login.');">
                                @csrf
                                @method('PATCH')

                                <button type="submit"
                                        class="btn-edit"
                                        style="padding:6px 12px; font-size:12px;"
                                        title="Kalau ditutup, {{ $account->name }} tidak bisa melihat badge Status siapapun (miliknya sendiri maupun orang lain). HRD tetap selalu bisa melihatnya di sini.">
                                    {{ $account->statusKontrakTerbuka() ? 'Tutup Status' : 'Buka Status' }}
                                </button>
                            </form>
                        @endif

                        @if($account->id === auth()->id())
                            <button type="button"
                                    class="btn-delete"
                                    style="padding:6px 12px; font-size:12px;"
                                    disabled
                                    title="Akun yang sedang digunakan tidak dapat dihapus.">
                                Hapus
                            </button>
                        @else
                            <form method="POST"
                                  action="{{ route('admin.account.destroy', $account->id) }}"
                                  onsubmit="return confirmDialog(event, 'Apakah Anda yakin ingin menghapus akun ini? Semua data penilaian dan tanggapan terkait juga akan dihapus.');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                        class="btn-delete"
                                        style="padding:6px 12px; font-size:12px;">
                                    Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach

        <tr id="accounts-no-results" style="display:none;">
            <td colspan="12" class="no-results">
                Tidak ada akun yang cocok dengan pencarian/filter.
            </td>
        </tr>
    </table>
    </div>

@endif

</section>

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

            <label for="acc-vendor">Vendor</label>
            <select id="acc-vendor" name="vendor">
                <option value="">-- Pilih Vendor --</option>
                @foreach(\App\Models\User::VENDORS as $vendorOption)
                    <option value="{{ $vendorOption }}" {{ old('vendor') == $vendorOption ? 'selected' : '' }}>
                        {{ $vendorOption }}
                    </option>
                @endforeach
            </select>

            <label for="acc-status">Status</label>
            <select id="acc-status" name="status">
                <option value="">-- Pilih Status --</option>
                @foreach(\App\Models\User::EMPLOYMENT_STATUSES as $statusOption)
                    <option value="{{ $statusOption }}" {{ old('status') == $statusOption ? 'selected' : '' }}>
                        {{ $statusOption }}
                    </option>
                @endforeach
            </select>

            <label for="acc-role">Role</label>
            <select id="acc-role" name="role" required onchange="toggleSpgOption()">
                <option value="">-- Pilih Role --</option>
                <option value="pegawai" {{ old('role') == 'pegawai' ? 'selected' : '' }}>Pegawai</option>
                <option value="pejabat" {{ old('role') == 'pejabat' ? 'selected' : '' }}>Pejabat</option>
                <option value="hrd" {{ old('role') == 'hrd' ? 'selected' : '' }}>HRD</option>
            </select>

            <div id="acc-spg-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="acc-is-spg" name="is_spg" value="1" {{ old('is_spg') ? 'checked' : '' }}>
                    Akun ini SPG (tanggapan korelasi/teman bersifat opsional)
                </label>
            </div>

            <div id="acc-boleh-menilai-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="acc-boleh-menilai" name="boleh_menilai_pegawai_lain" value="1" {{ old('boleh_menilai_pegawai_lain') ? 'checked' : '' }}>
                    Akun ini boleh ditugaskan menilai pegawai lain (Penilai)
                </label>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Kalau dicentang, akun ini akan muncul di dropdown "Atasan" untuk akun pegawai lain,
                    supaya bisa ditugaskan sebagai Penilai mereka - sama seperti akun pejabat/hrd.
                </small>
            </div>

            <div id="acc-menilai-manual-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="acc-menilai-manual" name="menilai_secara_manual" value="1" {{ old('menilai_secara_manual') ? 'checked' : '' }}>
                    Akun ini menilai secara manual
                </label>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Semua pegawai/pejabat yang Penilai atau Atasan Penilai-nya akun ini otomatis boleh
                    dicetak PDF-nya oleh HRD tanpa menunggu penilaian/tanggapan dari akun ini di sistem -
                    tidak perlu dicentang satu-satu lagi di tiap akun yang dinilai.
                </small>
            </div>

            <div id="acc-supervisor-wrapper">
                <label for="acc-supervisor-id">Atasan</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="acc-supervisor-id"
                            name="supervisor_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('acc-supervisor-id', 'acc-supervisor-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}"
                                    data-role="{{ $atasan->role }}"
                                    {{ old('supervisor_id') == $atasan->id ? 'selected' : '' }}>
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="acc-supervisor-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Bisa dipilih dari akun dengan role Pejabat atau HRD.
                </small>

                @if($atasanList->isEmpty())
                    <small style="display:block; color:#b45309; font-size:11px; margin-top:2px;">
                        Belum ada akun dengan role Pejabat atau HRD.
                    </small>
                @endif
            </div>

            <div id="acc-atasan-pejabat-wrapper" style="display:none; margin-top:8px;">
                <label for="acc-atasan-pejabat-id">Atasan Penilai (Tanggapan Atasan)</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="acc-atasan-pejabat-id"
                            name="atasan_pejabat_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('acc-atasan-pejabat-id', 'acc-atasan-pejabat-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}"
                                    data-role="{{ $atasan->role }}"
                                    {{ old('atasan_pejabat_id') == $atasan->id ? 'selected' : '' }}>
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="acc-atasan-pejabat-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Pejabat/HRD yang ditugaskan di sini HANYA bisa memberi tanggapan &amp;
                    rekomendasi ke akun ini setelah dinilai, bukan ikut menilai.
                </small>
            </div>

            <div id="acc-atasan-penilai-pejabat-wrapper" style="display:none; margin-top:8px;">
                <label for="acc-atasan-penilai-pejabat-id">Atasan Penilai (Tanggapan Atasan)</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="acc-atasan-penilai-pejabat-id"
                            name="atasan_penilai_pejabat_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('acc-atasan-penilai-pejabat-id', 'acc-atasan-penilai-pejabat-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}"
                                    data-role="{{ $atasan->role }}"
                                    {{ old('atasan_penilai_pejabat_id') == $atasan->id ? 'selected' : '' }}>
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="acc-atasan-penilai-pejabat-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Pejabat/HRD yang ditugaskan di sini HANYA bisa memberi tanggapan &amp;
                    rekomendasi ke akun ini setelah dinilai, bukan ikut menilai.
                </small>
            </div>

            @if($errors->any())
                <div class="error">
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </div>
            @endif

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeAddAccountModal()">
                    Batal
                </button>

                <button type="submit" class="btn-edit" id="acc-submit-btn">
                    Simpan
                </button>
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

            <label for="edit-vendor">Vendor</label>
            <select id="edit-vendor" name="vendor">
                <option value="">-- Pilih Vendor --</option>
                @foreach(\App\Models\User::VENDORS as $vendorOption)
                    <option value="{{ $vendorOption }}">{{ $vendorOption }}</option>
                @endforeach
            </select>

            <label for="edit-status">Status</label>
            <select id="edit-status" name="status">
                <option value="">-- Pilih Status --</option>
                @foreach(\App\Models\User::EMPLOYMENT_STATUSES as $statusOption)
                    <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                @endforeach
            </select>

            <label for="edit-role">Role</label>
            <select id="edit-role" name="role" required onchange="toggleEditSpgOption()">
                <option value="pegawai">Pegawai</option>
                <option value="pejabat">Pejabat</option>
                <option value="hrd">HRD</option>
            </select>

            <div id="edit-spg-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="edit-is-spg" name="is_spg" value="1">
                    Akun ini SPG (tanggapan korelasi/teman bersifat opsional)
                </label>
            </div>

            <div id="edit-boleh-menilai-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="edit-boleh-menilai" name="boleh_menilai_pegawai_lain" value="1">
                    Akun ini boleh ditugaskan menilai pegawai lain (Penilai)
                </label>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Kalau dicentang, akun ini akan muncul di dropdown "Atasan" untuk akun pegawai lain,
                    supaya bisa ditugaskan sebagai Penilai mereka - sama seperti akun pejabat/hrd.
                </small>
            </div>

            <div id="edit-menilai-manual-wrapper" style="margin-top:8px; display:none;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal;">
                    <input type="checkbox" id="edit-menilai-manual" name="menilai_secara_manual" value="1">
                    Akun ini menilai secara manual
                </label>
                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Semua pegawai/pejabat yang Penilai atau Atasan Penilai-nya akun ini otomatis boleh
                    dicetak PDF-nya oleh HRD tanpa menunggu penilaian/tanggapan dari akun ini di sistem -
                    tidak perlu dicentang satu-satu lagi di tiap akun yang dinilai.
                </small>
            </div>

            <div id="edit-supervisor-wrapper">
                <label for="edit-supervisor-id">Atasan</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="edit-supervisor-id"
                            name="supervisor_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('edit-supervisor-id', 'edit-supervisor-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}" data-role="{{ $atasan->role }}">
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="edit-supervisor-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Bisa dipilih dari akun dengan role Pejabat atau HRD.
                </small>

                @if($atasanList->isEmpty())
                    <small style="display:block; color:#b45309; font-size:11px; margin-top:2px;">
                        Belum ada akun dengan role Pejabat atau HRD.
                    </small>
                @endif
            </div>

            <div id="edit-atasan-pejabat-wrapper" style="display:none; margin-top:8px;">
                <label for="edit-atasan-pejabat-id">Atasan Penilai (Tanggapan Atasan)</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="edit-atasan-pejabat-id"
                            name="atasan_pejabat_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('edit-atasan-pejabat-id', 'edit-atasan-pejabat-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}" data-role="{{ $atasan->role }}">
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="edit-atasan-pejabat-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Pejabat/HRD yang ditugaskan di sini HANYA bisa memberi tanggapan &amp;
                    rekomendasi ke akun ini setelah dinilai, bukan ikut menilai.
                </small>
            </div>

            <div id="edit-atasan-penilai-pejabat-wrapper" style="display:none; margin-top:8px;">
                <label for="edit-atasan-penilai-pejabat-id">Atasan Penilai (Tanggapan Atasan)</label>

                <div style="display:flex; align-items:center; gap:8px;">
                    <select id="edit-atasan-penilai-pejabat-id"
                            name="atasan_penilai_pejabat_id"
                            style="flex:1;"
                            onchange="updateSupervisorRoleBadge('edit-atasan-penilai-pejabat-id', 'edit-atasan-penilai-pejabat-role-badge')">
                        <option value="">-- Belum Ditugaskan --</option>

                        @foreach($atasanList as $atasan)
                            <option value="{{ $atasan->id }}" data-role="{{ $atasan->role }}">
                                {{ $atasan->name }}
                            </option>
                        @endforeach
                    </select>

                    <span id="edit-atasan-penilai-pejabat-role-badge"
                          class="role-badge"
                          style="display:none;"></span>
                </div>

                <small style="display:block; color:#888; font-size:11px; margin-top:2px;">
                    Pejabat/HRD yang ditugaskan di sini HANYA bisa memberi tanggapan &amp;
                    rekomendasi ke akun ini setelah dinilai, bukan ikut menilai.
                </small>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditAccountModal()">
                    Batal
                </button>

                <button type="submit" class="btn-edit" id="edit-submit-btn">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="import-account-modal">
    <div class="modal-box">
        <h3>Import Akun dari Excel</h3>

        <form method="POST"
              action="{{ route('admin.account.import') }}"
              enctype="multipart/form-data">
            @csrf

            <label for="import-file">File Excel/CSV</label>

            <input type="file"
                   id="import-file"
                   name="import_file"
                   accept=".xlsx,.xls,.csv"
                   required>

            <small style="display:block; color:#888; font-size:11px; margin-top:6px; line-height:1.5;">
                Baris pertama harus header. Kolom yang dipakai (nama kolom harus persis):
                <strong>Vendor</strong>, <strong>Status</strong>, <strong>NIK</strong>,
                <strong>Nama Lengkap</strong>, <strong>Penilai</strong>,
                <strong>Atasan Penilai</strong>,
                <strong>Jabatan</strong>,
                <strong>Departemen</strong>, <strong>Unit</strong>, <strong>Indikator</strong>.
                Kolom lain (NIK OS, Lokasi) diabaikan.

                <br><br>

                Sheet yang tidak punya kolom <strong>Vendor</strong>/<strong>Status</strong>
                (mis. sheet "SPG") otomatis diisi Vendor = "OS ABM" dan
                Status = "PHL" untuk semua barisnya.

                <br><br>

                <strong>Indikator</strong> diisi salah satu:
                Pegawai, Pejabat, HRD — ini menentukan role akun.

                <br><br>

                <strong>Penilai</strong> diisi nama akun yang SUDAH ADA
                (atau ada di baris lain file yang sama) untuk role
                Pejabat/HRD — akan otomatis jadi
                "Penilai" (users.supervisor_id) untuk baris ini. Ini
                BUKAN "Atasan Penilai (Tanggapan Atasan)" — kolom itu
                terpisah dan hanya bisa ditugaskan lewat Edit akun.

                Kalau namanya tidak ketemu, baris tetap dibuat tapi penilainya
                harus ditugaskan manual lewat Edit.

                <br><br>

                <strong>Atasan Penilai</strong> diisi nama akun Pejabat/HRD
                yang sudah ada (atau ada di baris lain file yang sama).
                Satu kolom ini berlaku untuk SEMUA role (Pegawai, Pejabat,
                HRD), otomatis jadi:
                <br>
                &bull; Pegawai → "Atasan Pejabat" pegawai ini
                (users.atasan_pejabat_id, khusus Tanggapan Atasan).
                <br>
                &bull; Pejabat/HRD → "Atasan Penilai" akun ini
                (users.atasan_penilai_pejabat_id, khusus Tanggapan
                Atasan).
                <br>
                Kalau namanya tidak ketemu atau kolomnya dikosongkan,
                harus ditugaskan manual lewat Edit — lihat catatan di
                tabel "Semua Akun".

                <br><br>

                Username & password dibuat otomatis (password = NIK).
                NIK yang sudah terdaftar akan dilewati (tidak menimpa data lama).
            </small>

            <div class="modal-footer">
                <button type="button"
                        class="btn-cancel"
                        onclick="closeImportModal()">
                    Batal
                </button>

                <button type="submit" class="btn-edit">
                    Import
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddAccountModal() {
        const modal = document.getElementById('add-account-modal');

        if (modal) {
            modal.classList.add('active');
        }
    }

    function closeAddAccountModal() {
        const modal = document.getElementById('add-account-modal');

        if (modal) {
            modal.classList.remove('active');
        }
    }

    function openImportModal() {
        const modal = document.getElementById('import-account-modal');

        if (modal) {
            modal.classList.add('active');
        }
    }

    function closeImportModal() {
        const modal = document.getElementById('import-account-modal');

        if (modal) {
            modal.classList.remove('active');
        }
    }

    const ROLE_LABELS = {
        pegawai: 'Pegawai',
        pejabat: 'Pejabat',
        hrd: 'HRD'
    };

    function updateSupervisorRoleBadge(selectId, badgeId) {
        const select = document.getElementById(selectId);
        const badge = document.getElementById(badgeId);

        if (!select || !badge) {
            return;
        }

        const selectedOption = select.options[select.selectedIndex];

        const role = selectedOption
            ? selectedOption.getAttribute('data-role')
            : null;

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

    function toggleSpgOption() {
        const role = document.getElementById('acc-role').value;
        const spgWrapper = document.getElementById('acc-spg-wrapper');
        const supervisorWrapper = document.getElementById('acc-supervisor-wrapper');
        const atasanPejabatWrapper = document.getElementById('acc-atasan-pejabat-wrapper');
        const atasanPenilaiPejabatWrapper = document.getElementById('acc-atasan-penilai-pejabat-wrapper');

        if (spgWrapper) {
            spgWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const spgCheckbox = document.getElementById('acc-is-spg');

        if (role !== 'pegawai' && spgCheckbox) {
            spgCheckbox.checked = false;
        }

        const bolehMenilaiWrapper = document.getElementById('acc-boleh-menilai-wrapper');

        if (bolehMenilaiWrapper) {
            bolehMenilaiWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const bolehMenilaiCheckbox = document.getElementById('acc-boleh-menilai');

        if (role !== 'pegawai' && bolehMenilaiCheckbox) {
            bolehMenilaiCheckbox.checked = false;
        }

        const menilaiManualWrapper = document.getElementById('acc-menilai-manual-wrapper');

        if (menilaiManualWrapper) {
            menilaiManualWrapper.style.display =
                (role === 'pejabat' || role === 'hrd') ? 'block' : 'none';
        }

        const menilaiManualCheckbox = document.getElementById('acc-menilai-manual');

        if (role !== 'pejabat' && role !== 'hrd' && menilaiManualCheckbox) {
            menilaiManualCheckbox.checked = false;
        }

        updateSupervisorRoleBadge(
            'acc-supervisor-id',
            'acc-supervisor-role-badge'
        );

        if (atasanPejabatWrapper) {
            atasanPejabatWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const atasanPejabatSelect =
            document.getElementById('acc-atasan-pejabat-id');

        if (role !== 'pegawai' && atasanPejabatSelect) {
            atasanPejabatSelect.value = '';
        }

        updateSupervisorRoleBadge(
            'acc-atasan-pejabat-id',
            'acc-atasan-pejabat-role-badge'
        );

        if (atasanPenilaiPejabatWrapper) {
            atasanPenilaiPejabatWrapper.style.display =
                (role === 'pejabat' || role === 'hrd') ? 'block' : 'none';
        }

        const atasanPenilaiPejabatSelect =
            document.getElementById('acc-atasan-penilai-pejabat-id');

        if (role !== 'pejabat' && role !== 'hrd' && atasanPenilaiPejabatSelect) {
            atasanPenilaiPejabatSelect.value = '';
        }

        updateSupervisorRoleBadge(
            'acc-atasan-penilai-pejabat-id',
            'acc-atasan-penilai-pejabat-role-badge'
        );
    }

    function toggleEditSpgOption() {
        const role = document.getElementById('edit-role').value;
        const spgWrapper = document.getElementById('edit-spg-wrapper');
        const supervisorWrapper = document.getElementById('edit-supervisor-wrapper');
        const atasanPejabatWrapper = document.getElementById('edit-atasan-pejabat-wrapper');
        const atasanPenilaiPejabatWrapper = document.getElementById('edit-atasan-penilai-pejabat-wrapper');

        if (spgWrapper) {
            spgWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const spgCheckbox = document.getElementById('edit-is-spg');

        if (role !== 'pegawai' && spgCheckbox) {
            spgCheckbox.checked = false;
        }

        const bolehMenilaiWrapper = document.getElementById('edit-boleh-menilai-wrapper');

        if (bolehMenilaiWrapper) {
            bolehMenilaiWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const bolehMenilaiCheckbox = document.getElementById('edit-boleh-menilai');

        if (role !== 'pegawai' && bolehMenilaiCheckbox) {
            bolehMenilaiCheckbox.checked = false;
        }

        const menilaiManualWrapper = document.getElementById('edit-menilai-manual-wrapper');

        if (menilaiManualWrapper) {
            menilaiManualWrapper.style.display =
                (role === 'pejabat' || role === 'hrd') ? 'block' : 'none';
        }

        const menilaiManualCheckbox = document.getElementById('edit-menilai-manual');

        if (role !== 'pejabat' && role !== 'hrd' && menilaiManualCheckbox) {
            menilaiManualCheckbox.checked = false;
        }

        updateSupervisorRoleBadge(
            'edit-supervisor-id',
            'edit-supervisor-role-badge'
        );

        if (atasanPejabatWrapper) {
            atasanPejabatWrapper.style.display =
                role === 'pegawai' ? 'block' : 'none';
        }

        const atasanPejabatSelect =
            document.getElementById('edit-atasan-pejabat-id');

        if (role !== 'pegawai' && atasanPejabatSelect) {
            atasanPejabatSelect.value = '';
        }

        updateSupervisorRoleBadge(
            'edit-atasan-pejabat-id',
            'edit-atasan-pejabat-role-badge'
        );

        if (atasanPenilaiPejabatWrapper) {
            atasanPenilaiPejabatWrapper.style.display =
                (role === 'pejabat' || role === 'hrd') ? 'block' : 'none';
        }

        const atasanPenilaiPejabatSelect =
            document.getElementById('edit-atasan-penilai-pejabat-id');

        if (role !== 'pejabat' && role !== 'hrd' && atasanPenilaiPejabatSelect) {
            atasanPenilaiPejabatSelect.value = '';
        }

        updateSupervisorRoleBadge(
            'edit-atasan-penilai-pejabat-id',
            'edit-atasan-penilai-pejabat-role-badge'
        );
    }

    function openEditAccountModal(row) {
        const data = row.dataset;

        document.getElementById('edit-account-form').action =
            '{{ url("/hrd/akun") }}/' + data.id;

        document.getElementById('edit-name').value =
            data.name || '';

        document.getElementById('edit-username').value =
            data.username || '';

        document.getElementById('edit-nik').value =
            data.nik || '';

        document.getElementById('edit-unit-kerja').value =
            data.unitKerja || '';

        document.getElementById('edit-jabatan').value =
            data.jabatan || '';

        document.getElementById('edit-vendor').value =
            data.vendor || '';

        document.getElementById('edit-status').value =
            data.status || '';

        document.getElementById('edit-role').value =
            data.role || 'pegawai';

        document.getElementById('edit-is-spg').checked =
            data.isSpg === '1';

        document.getElementById('edit-menilai-manual').checked =
            data.menilaiManual === '1';

        document.getElementById('edit-boleh-menilai').checked =
            data.bolehMenilai === '1';

        document.getElementById('edit-supervisor-id').value =
            data.supervisorId || '';

        document.getElementById('edit-atasan-pejabat-id').value =
            data.atasanPejabatId || '';

        document.getElementById('edit-atasan-penilai-pejabat-id').value =
            data.atasanPenilaiPejabatId || '';

        toggleEditSpgOption();

        document.getElementById('edit-account-modal')
            .classList.add('active');
    }

    function closeEditAccountModal() {
        document.getElementById('edit-account-modal')
            .classList.remove('active');
    }

    function filterAccounts() {
        const input = document.getElementById('account-search');
        const roleSelect = document.getElementById('account-role-filter');
        const count = document.getElementById('account-filter-count');
        const noResults = document.getElementById('accounts-no-results');

        if (!input || !roleSelect) {
            return;
        }

        const query = input.value.trim().toLowerCase();
        const role = roleSelect.value;

        const rows = document.querySelectorAll(
            '#accounts-table .account-row'
        );

        let visible = 0;

        rows.forEach(function (row) {
            const matchesSearch =
                !query ||
                (row.dataset.search || '').includes(query);

            const matchesRole =
                !role ||
                row.dataset.roleFilter === role;

            const show =
                matchesSearch && matchesRole;

            row.style.display =
                show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        if (noResults) {
            noResults.style.display =
                visible === 0 ? '' : 'none';
        }

        if (count) {
            count.textContent =
                visible + ' dari ' + rows.length + ' akun ditampilkan';
        }
    }

    function resetAccountFilter() {
        const input = document.getElementById('account-search');
        const role = document.getElementById('account-role-filter');

        if (input) {
            input.value = '';
        }

        if (role) {
            role.value = '';
        }

        filterAccounts();
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleSpgOption();

        const editRole = document.getElementById('edit-role');

        if (editRole) {
            toggleEditSpgOption();
        }

        document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
        });

        const addAccountForm =
            document.querySelector('#add-account-modal form');

        if (addAccountForm) {
            addAccountForm.addEventListener('submit', function () {
                const btn =
                    document.getElementById('acc-submit-btn');

                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Menyimpan...';
                }
            });
        }

        const editAccountForm =
            document.getElementById('edit-account-form');

        if (editAccountForm) {
            editAccountForm.addEventListener('submit', function () {
                const btn =
                    document.getElementById('edit-submit-btn');

                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Menyimpan...';
                }
            });
        }

        @if($errors->any())
            const addModal =
                document.getElementById('add-account-modal');

            if (addModal) {
                addModal.classList.add('active');
            }
        @endif

        filterAccounts();
    });
</script>

</div>

</x-dashboard-layout>
