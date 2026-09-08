{{--
    Selector Tahun (reusable).

    Dropdown untuk pindah lihat data tahun lain di halaman mana pun yang
    query controllernya sudah pakai App\Http\Concerns\FiltersByTahun. Ganti
    pilihan langsung submit GET ke URL yang sama, cuma parameter "tahun"
    yang berubah - parameter lain (search, filter, dsb.) tetap dipertahankan
    lewat hidden input di bawah.

    Props:
    - tahun    : tahun yang sedang aktif ditampilkan (int)
    - options  : daftar tahun yang bisa dipilih (array of int), lihat
                 FiltersByTahun::availableTahunOptions()
--}}
@props(['tahun', 'options' => []])

<form method="GET" action="{{ url()->current() }}" class="tahun-selector">
    @foreach(request()->except('tahun') as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach

    <label for="tahun-selector-select">&#128197; Tahun</label>

    <select name="tahun" id="tahun-selector-select" onchange="this.form.submit()">
        @foreach($options as $option)
            <option value="{{ $option }}" @selected((int) $option === (int) $tahun)>
                {{ $option }}
            </option>
        @endforeach
    </select>

    <noscript>
        <button type="submit" class="tahun-selector-submit">Tampilkan</button>
    </noscript>
</form>

<style>
    .tahun-selector {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: white;
        padding: 8px 8px 8px 16px;
        border-radius: 999px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    .tahun-selector label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #64748b;
        white-space: nowrap;
    }

    .tahun-selector select {
        padding: 6px 30px 6px 12px;
        border-radius: 999px;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        font-weight: 700;
        font-size: 13.5px;
        color: #1d4ed8;
        cursor: pointer;
    }

    .tahun-selector-submit {
        padding: 6px 14px;
        border-radius: 999px;
        border: none;
        background: #2563eb;
        color: white;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
    }
</style>
