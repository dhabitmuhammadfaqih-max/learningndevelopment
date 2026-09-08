<style>
    #hrd-page {
        font-family: inherit;
    }

    #hrd-page h2 {
        margin: 40px 0 16px;
        font-size: 18px;
        font-weight: 800;
        color: #1e293b;
        letter-spacing: -0.01em;
    }

    #hrd-page section:first-of-type h2 {
        margin-top: 8px;
    }

    #hrd-page button,
    #hrd-page .btn-edit {
        font-family: inherit;
        padding: 10px 18px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 999px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.55);
        transition: background .15s ease, transform .1s ease;
    }

    #hrd-page button:hover,
    #hrd-page .btn-edit:hover {
        background: #1d4ed8;
    }

    #hrd-page .btn-purple {
        background: #7c3aed;
        box-shadow: 0 6px 16px -6px rgba(124, 58, 237, 0.55);
    }

    #hrd-page .btn-purple:hover {
        background: #6d28d9;
    }

    #hrd-page .btn-delete {
        background: #ef4444;
        box-shadow: 0 6px 16px -6px rgba(239, 68, 68, 0.5);
    }

    #hrd-page .btn-delete:hover {
        background: #dc2626;
    }

    #hrd-page .btn-delete:disabled {
        background: #e2e8f0;
        color: #94a3b8;
        cursor: not-allowed;
        box-shadow: none;
    }

    #hrd-page .account-actions {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
        max-width: 220px;
    }

    #hrd-page .account-actions .btn-edit,
    #hrd-page .account-actions .btn-delete {
        padding: 5px 10px !important;
        font-size: 11px !important;
    }

    #hrd-page .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
    }

    #hrd-page .card {
        background: white;
        padding: 20px;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 24px -16px rgba(15, 23, 42, 0.25);
        border: 1px solid #f1f5f9;
    }

    #hrd-page .card h3 {
        margin: 0 0 4px 0;
        font-size: 15px;
        font-weight: 700;
        color: #1e293b;
    }

    #hrd-page .card p {
        margin: 0 0 14px 0;
        color: #64748b;
        font-size: 13px;
    }

    #hrd-page .card a {
        display: inline-block;
        padding: 8px 16px;
        background: #eff6ff;
        color: #2563eb;
        text-decoration: none;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
    }

    #hrd-page .card a:hover {
        background: #dbeafe;
    }

    #hrd-page .empty {
        color: #94a3b8;
    }

    #hrd-page .alert {
        padding: 14px 18px;
        border-radius: 14px;
        margin-bottom: 16px;
        font-size: 14px;
    }

    #hrd-page .alert-success {
        background: #dcfce7;
        color: #166534;
    }

    #hrd-page .alert-error {
        background: #fee2e2;
        color: #991b1b;
    }

    #hrd-page table.accounts {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 24px -16px rgba(15, 23, 42, 0.25);
        border: 1px solid #f1f5f9;
    }

    /* Tabel akun punya banyak kolom (Nama, Username, NIK, Vendor, Status,
       dst) yang tidak muat di layar laptop/kecil kalau dipaksa 100% lebar
       tanpa scroll. Sebelumnya ini bikin SELURUH halaman jadi lebih lebar
       dari viewport, jadi user harus zoom out browser supaya semua kolom
       kelihatan. Dibungkus wrapper overflow-x:auto di sini supaya yang
       scroll cuma tabelnya, bukan seluruh halaman/sidebar. */
    #hrd-page .accounts-table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 18px;
    }

    #hrd-page table.accounts th,
    #hrd-page table.accounts td {
        text-align: left;
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 12.5px;
        color: #334155;
        word-break: break-word;
    }

    /* Kolom Nama/Username/Jabatan sering isinya teks panjang - dibatasi
       lebar maksimalnya supaya wrap ke bawah, bukan bikin kolomnya
       melebar dan mendorong kolom-kolom lain keluar layar. */
    #hrd-page table.accounts td:nth-child(1),
    #hrd-page table.accounts td:nth-child(2),
    #hrd-page table.accounts td:nth-child(7),
    #hrd-page table.accounts td:nth-child(8) {
        max-width: 130px;
    }

    #hrd-page table.accounts th {
        background: #f8fafc;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #94a3b8;
        font-weight: 700;
    }

    #hrd-page table.accounts tr:last-child td {
        border-bottom: none;
    }

    #hrd-page .role-badge {
        display: inline-block;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 10.5px;
        font-weight: 700;
        margin: 1px 0;
    }

    #hrd-page .role-pegawai {
        background: #e0e7ff;
        color: #3730a3;
    }

    #hrd-page .role-pejabat {
        background: #dcfce7;
        color: #166534;
    }

    #hrd-page .role-hrd {
        background: #fee2e2;
        color: #991b1b;
    }

    #hrd-page .role-spg {
        background: #fce7f3;
        color: #9d174d;
    }

    #hrd-page .vendor-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #fef3c7;
        color: #92400e;
    }

    #hrd-page .employment-status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        background: #e0f2fe;
        color: #075985;
    }

    #hrd-page .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 6px;
    }

    #hrd-page .status-sudah {
        background: #dcfce7;
        color: #166534;
    }

    #hrd-page .status-belum {
        background: #f1f5f9;
        color: #64748b;
    }

    /* Baris badge (vendor/status/kehadiran/siap-pdf) dijejer rapi
       dalam satu baris yang bisa wrap, supaya tidak menumpuk vertikal
       jadi banyak baris terpisah-pisah. */
    #hrd-page .badge-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    #hrd-page .pdf-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    #hrd-page .pdf-badge-ready {
        background: #dcfce7;
        color: #166534;
    }

    #hrd-page .pdf-badge-not-ready {
        background: #fee2e2;
        color: #991b1b;
    }

    #hrd-page .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        align-items: center;
        justify-content: center;
        z-index: 50;
    }

    #hrd-page .modal-overlay.active {
        display: flex;
    }

    #hrd-page .modal-box {
        background: white;
        border-radius: 20px;
        padding: 26px;
        width: 100%;
        max-width: 380px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.35);
    }

    #hrd-page .modal-box h3 {
        margin-top: 0;
        font-size: 17px;
        font-weight: 800;
        color: #1e293b;
    }

    #hrd-page .modal-box label {
        display: block;
        font-size: 12.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
        margin-top: 12px;
    }

    #hrd-page .modal-box input:not([type="checkbox"]):not([type="radio"]),
    #hrd-page .modal-box select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-sizing: border-box;
        font-family: inherit;
        font-size: 14px;
        background: #f8fafc;
    }

    #hrd-page .modal-box input:not([type="checkbox"]):not([type="radio"]):focus,
    #hrd-page .modal-box select:focus {
        outline: 2px solid #bfdbfe;
        border-color: #2563eb;
        background: white;
    }

    #hrd-page .modal-box input[type="checkbox"],
    #hrd-page .modal-box input[type="radio"] {
        width: 16px;
        height: 16px;
        flex: 0 0 auto;
        padding: 0;
        margin: 0;
    }

    #hrd-page .modal-box .error {
        color: #dc2626;
        font-size: 12px;
        margin-top: 4px;
    }

    #hrd-page .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 20px;
    }

    #hrd-page .btn-cancel {
        background: #f1f5f9;
        color: #334155;
        box-shadow: none;
    }

    #hrd-page .btn-cancel:hover {
        background: #e2e8f0;
    }

    #hrd-page .filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    #hrd-page .filter-bar input,
    #hrd-page .filter-bar select {
        padding: 10px 14px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-family: inherit;
        font-size: 13px;
        box-sizing: border-box;
        background: white;
    }

    #hrd-page .filter-bar input {
        flex: 1 1 260px;
    }

    #hrd-page .filter-bar .btn-reset-filter {
        background: #f1f5f9;
        color: #334155;
        padding: 9px 16px;
        font-size: 12.5px;
        box-shadow: none;
    }

    #hrd-page .filter-bar .btn-reset-filter:hover {
        background: #e2e8f0;
    }

    #hrd-page .filter-count {
        font-size: 12.5px;
        color: #94a3b8;
        white-space: nowrap;
    }

    #hrd-page .no-results {
        color: #94a3b8;
        padding: 16px;
        text-align: center;
    }

    #hrd-page nav.quicknav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin: 0 0 4px;
    }

    #hrd-page nav.quicknav a {
        font-size: 12.5px;
        font-weight: 600;
        color: #2563eb;
        text-decoration: none;
        padding: 8px 16px;
        background: white;
        border-radius: 999px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 6px 16px -10px rgba(15, 23, 42, 0.3);
        border: 1px solid #eef2f7;
    }

    #hrd-page nav.quicknav a:hover {
        background: #eff6ff;
    }

    #hrd-page section {
        margin-top: 8px;
        scroll-margin-top: 24px;
    }

    #hrd-page .hero-banner {
        background: linear-gradient(135deg, #dbeafe 0%, #eef2ff 60%, #f5f3ff 100%);
        border-radius: 24px;
        padding: 28px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }

    #hrd-page .hero-banner h2 {
        margin: 0 0 6px;
        font-size: 22px;
        font-weight: 800;
        color: #1e293b;
    }

    #hrd-page .hero-banner p {
        margin: 0;
        color: #475569;
        font-size: 13.5px;
        max-width: 480px;
    }

    #hrd-page .hero-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
</style>