/**
 * Autosave form generik.
 *
 * Tujuan: progres pengisian form (penilaian, tanggapan, feedback, dst)
 * tidak hilang kalau tab/browser ketutup, halaman ke-refresh tidak
 * sengaja, atau koneksi internet putus SEBELUM sempat klik submit.
 * Semua penyimpanan draf dilakukan ke localStorage milik browser
 * (client-side murni) - makanya tetap jalan walau jaringan lagi mati,
 * karena tidak butuh request ke server sama sekali untuk menyimpan
 * draf-nya.
 *
 * Cara pakai: tambahkan atribut `data-autosave` ke tag <form> mana pun
 * yang perlu dilindungi. Contoh:
 *   <form id="form-penilaian" data-autosave>...</form>
 *
 * Key localStorage dibuat dari kombinasi id user yang sedang login + path
 * URL halaman + id form, supaya form yang sama tapi dibuka untuk
 * pegawai/pejabat berbeda (URL berbeda), MAUPUN oleh akun berbeda yang
 * gantian login di perangkat/browser yang sama (URL bisa saja sama
 * persis), draf-nya tidak saling timpa/ketawarkan ke akun lain.
 *
 * Field yang DIABAIKAN dari autosave: file input, password, dan field
 * CSRF/method spoofing (_token, _method) - field-field itu tidak perlu
 * (atau tidak boleh) disimpan balik ke localStorage.
 */
(function () {
    const DEBOUNCE_MS = 600;
    const PERIODIC_SAVE_MS = 8000;
    const IGNORED_TYPES = ['file', 'password'];
    const IGNORED_NAMES = ['_token', '_method'];

    // Key localStorage HARUS menyertakan id user yang sedang login, bukan
    // cuma path URL + id form. localStorage itu MILIK BROWSER/PERANGKAT,
    // BUKAN milik akun - kalau satu perangkat/browser dipakai gantian
    // oleh beberapa akun berbeda (mis. komputer bersama di kantor HRD)
    // untuk membuka halaman dengan URL yang sama persis (mis. dashboard
    // pegawai/pejabat masing-masing yang page-nya sama), TANPA id user di
    // key, draf akun yang login sebelumnya bisa ketawarkan/ketimpa ke
    // akun berikutnya yang login di perangkat yang sama. Id user diambil
    // dari <meta name="autosave-user-id"> yang dirender server (lihat
    // resources/views/layouts/app.blade.php &
    // resources/views/components/dashboard-layout.blade.php) - fallback
    // ke 'guest' kalau meta tidak ada/kosong (mis. halaman belum login),
    // supaya autosave tetap tidak error, bukan supaya dipakai share antar
    // akun.
    function currentUserId() {
        const meta = document.querySelector('meta[name="autosave-user-id"]');
        const value = meta ? meta.getAttribute('content') : '';
        return value && value.trim() !== '' ? value.trim() : 'guest';
    }

    function storageKey(form) {
        return 'autosave:user-' + currentUserId() + ':' + window.location.pathname + '#' + (form.id || 'form');
    }

    // Draf lama (sebelum perbaikan di atas) tersimpan dengan key tanpa id
    // user - key-nya cuma 'autosave:' + pathname + '#' + formId, jadi
    // bisa jadi milik akun siapa saja yang sempat pakai perangkat ini.
    // Buang semua draf berformat lama itu sekali saat script dimuat,
    // supaya tidak ada draf "tanpa pemilik jelas" yang nyasar ketawarkan
    // ke akun manapun yang kebetulan buka path yang sama.
    function purgeLegacyDrafts() {
        try {
            const staleKeys = [];
            for (let i = 0; i < localStorage.length; i++) {
                const k = localStorage.key(i);
                if (k && k.indexOf('autosave:') === 0 && k.indexOf('autosave:user-') !== 0) {
                    staleKeys.push(k);
                }
            }
            staleKeys.forEach((k) => localStorage.removeItem(k));
        } catch (e) {
            // localStorage tidak bisa diakses - diamkan, sama seperti
            // penanganan error localStorage lain di file ini.
        }
    }

    function collectableFields(form) {
        return Array.from(form.elements).filter((el) => {
            if (!el.name) return false;
            if (IGNORED_NAMES.includes(el.name)) return false;
            if (IGNORED_TYPES.includes((el.type || '').toLowerCase())) return false;
            return true;
        });
    }

    function serializeForm(form) {
        const data = {};
        collectableFields(form).forEach((el) => {
            if (el.type === 'checkbox') {
                data[el.name] = data[el.name] || [];
                if (el.checked) data[el.name].push(el.value);
            } else if (el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function isFieldEmpty(el) {
        if (el.type === 'checkbox' || el.type === 'radio') {
            return !el.checked;
        }
        return !el.value || el.value.trim() === '';
    }

    // Beberapa field (mis. input nilai komponen) punya default value dari
    // server seperti value="0", bukan string kosong. Kalau dianggap
    // "sudah diisi" (isFieldEmpty), draf tidak akan pernah bisa mengisi
    // ulang field itu walau user belum pernah menyentuhnya sama sekali.
    // Karena itu kita rekam nilai awal tiap field SEBELUM user sempat
    // mengetik apa pun, lalu saat restore anggap field "boleh ditimpa"
    // kalau isinya masih persis sama dengan nilai awal itu (belum
    // diubah user) - bukan cuma kalau kosong.
    function captureInitialState(form) {
        const initial = new Map();
        collectableFields(form).forEach((el) => {
            if (el.type === 'checkbox' || el.type === 'radio') {
                initial.set(el, el.checked);
            } else {
                initial.set(el, el.value);
            }
        });
        return initial;
    }

    function isFieldUntouched(el, initial) {
        if (!initial.has(el)) return isFieldEmpty(el);
        if (el.type === 'checkbox' || el.type === 'radio') {
            return el.checked === initial.get(el);
        }
        return el.value === initial.get(el);
    }

    function restoreForm(form, data, initial) {
        collectableFields(form).forEach((el) => {
            if (!(el.name in data)) return;
            // Sengaja hanya mengisi field yang MASIH SAMA DENGAN NILAI
            // AWAL (belum disentuh user) saat halaman dimuat - supaya
            // tidak menimpa nilai `old()` yang sudah dikirim ulang server
            // setelah redirect gagal validasi, ATAUPUN nilai yang sudah
            // sempat diketik/diubah user setelah draf ditawarkan.
            if (!isFieldUntouched(el, initial)) return;

            if (el.type === 'checkbox') {
                el.checked = Array.isArray(data[el.name]) && data[el.name].includes(el.value);
            } else if (el.type === 'radio') {
                el.checked = el.value === data[el.name];
            } else {
                el.value = data[el.name];
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function formHasAnyValue(form) {
        return collectableFields(form).some((el) => !isFieldEmpty(el));
    }

    function indicatorEl(form) {
        let el = form.querySelector('[data-autosave-indicator]');
        if (!el) {
            el = document.createElement('p');
            el.setAttribute('data-autosave-indicator', '');
            el.className = 'text-xs text-slate-600 mt-2 select-none';
            form.appendChild(el);
        }
        return el;
    }

    function showSaved(form) {
        const el = indicatorEl(form);
        const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        el.textContent = 'Isian Anda tersimpan otomatis di perangkat ini pukul ' + time + '. Belum terkirim - klik tombol kirim/simpan untuk menyelesaikan.';
    }

    function showRestoredBanner(form, savedAt, onUse, onDiscard) {
        const banner = document.createElement('div');
        banner.className = 'mb-4 rounded-xl border flex flex-wrap items-center justify-between gap-3';
        banner.style.cssText = 'background:#fffbeb;border-color:#fde68a;color:#92400e;padding:12px 16px;font-size:0.875rem;';
        const time = savedAt ? new Date(savedAt).toLocaleString('id-ID') : '';
        banner.innerHTML =
            '<span>Anda punya isian sebelumnya di form ini yang belum sempat dikirim' +
            (time ? ' (tersimpan otomatis ' + time + ')' : '') +
            '. Isi ulang otomatis dari draf tersebut, atau mulai dari form kosong?</span>' +
            '<span style="display:flex;gap:8px;flex-shrink:0;">' +
            '<button type="button" data-use style="white-space:nowrap;border-radius:8px;background:#d97706;color:#ffffff;font-size:0.875rem;font-weight:600;padding:8px 16px;border:none;cursor:pointer;">Isi dari Draf</button>' +
            '<button type="button" data-discard style="white-space:nowrap;border-radius:8px;background:#ffffff;color:#92400e;font-size:0.875rem;font-weight:600;padding:8px 16px;border:2px solid #fbbf24;cursor:pointer;">Mulai Kosong</button>' +
            '</span>';

        const useBtn = banner.querySelector('[data-use]');
        const discardBtn = banner.querySelector('[data-discard]');
        useBtn.addEventListener('mouseenter', () => { useBtn.style.background = '#b45309'; });
        useBtn.addEventListener('mouseleave', () => { useBtn.style.background = '#d97706'; });
        discardBtn.addEventListener('mouseenter', () => { discardBtn.style.background = '#fef3c7'; });
        discardBtn.addEventListener('mouseleave', () => { discardBtn.style.background = '#ffffff'; });

        useBtn.addEventListener('click', () => {
            onUse();
            banner.remove();
        });
        discardBtn.addEventListener('click', () => {
            onDiscard();
            banner.remove();
        });

        form.parentNode.insertBefore(banner, form);
    }

    function initAutosaveForm(form) {
        const key = storageKey(form);
        let saveTimer = null;

        // Rekam kondisi awal SEBELUM user sempat mengetik apa pun dan
        // SEBELUM banner draf ditawarkan - jadi kita tahu persis field
        // mana yang masih "nilai bawaan dari server" vs yang sudah user
        // isi (termasuk field dengan default value seperti "0").
        const initialState = captureInitialState(form);

        function save() {
            try {
                const payload = { data: serializeForm(form), savedAt: Date.now() };
                localStorage.setItem(key, JSON.stringify(payload));
                showSaved(form);
            } catch (e) {
                // localStorage penuh/diblokir (mis. mode private browser
                // tertentu) - diamkan, tidak fatal, cuma berarti fitur
                // autosave-nya tidak aktif buat sesi ini.
            }
        }

        function scheduleSave() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(save, DEBOUNCE_MS);
        }

        // Coba tawarkan draf lama (kalau ada) SEBELUM user mulai mengisi -
        // supaya tidak diam-diam menimpa apa yang sedang diketik.
        let raw = null;
        try {
            raw = localStorage.getItem(key);
        } catch (e) {
            raw = null;
        }

        if (raw) {
            try {
                const parsed = JSON.parse(raw);
                // Jaring pengaman kedua: kalau draf yang tersimpan ternyata
                // isinya kosong semua (mis. nyangkut dari draf lama sebelum
                // perbaikan di atas, atau localStorage diedit manual),
                // jangan tawarkan banner "isi ulang dari draf" - langsung
                // buang saja draf kosong itu.
                const hasAnyValue = Object.values(parsed.data || {}).some((v) =>
                    Array.isArray(v) ? v.length > 0 : (v !== undefined && v !== null && String(v).trim() !== '')
                );

                if (!hasAnyValue) {
                    try { localStorage.removeItem(key); } catch (e) {}
                } else {
                    showRestoredBanner(
                        form,
                        parsed.savedAt,
                        () => restoreForm(form, parsed.data || {}, initialState),
                        () => {
                            try { localStorage.removeItem(key); } catch (e) {}
                        }
                    );
                }
            } catch (e) {
                try { localStorage.removeItem(key); } catch (e) {}
            }
        }

        // Simpan setiap ada perubahan (di-debounce), ditambah simpan
        // berkala supaya progres tetap aman walau user diam saja lama
        // (mis. lagi mikir) dan tiba-tiba koneksi/perangkatnya
        // bermasalah.
        form.addEventListener('input', scheduleSave);
        form.addEventListener('change', scheduleSave);
        const periodicTimer = setInterval(() => {
            if (formHasAnyValue(form)) save();
        }, PERIODIC_SAVE_MS);

        // Simpan juga tepat sebelum tab/halaman ditinggalkan (ketutup,
        // pindah tab lama, refresh) - kondisi paling rawan progres
        // hilang. PENTING: dicek dulu formHasAnyValue() di sini juga
        // (sama seperti simpan berkala di atas) - tanpa ini, auto-refresh
        // dari fitur polling (window.location.reload()) ikut memicu
        // 'beforeunload'/'pagehide' dan menyimpan draf KOSONG setiap kali
        // halaman reload otomatis, lalu banner "tersimpan otomatis"
        // muncul terus padahal form-nya belum pernah diisi sama sekali.
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden' && formHasAnyValue(form)) save();
        });
        window.addEventListener('pagehide', () => { if (formHasAnyValue(form)) save(); });
        window.addEventListener('beforeunload', () => { if (formHasAnyValue(form)) save(); });

        // Submit berhasil (form benar-benar terkirim) -> draf sudah
        // tidak relevan lagi, hapus supaya tidak nyangkut nawarin draf
        // basi di lain waktu. Kalau ternyata validasi gagal di server,
        // halaman akan reload dengan old() terisi dan draf baru akan
        // otomatis tersimpan lagi begitu user lanjut mengetik.
        form.addEventListener('submit', () => {
            clearTimeout(saveTimer);
            clearInterval(periodicTimer);
            try { localStorage.removeItem(key); } catch (e) {}
        });
    }

    function init() {
        purgeLegacyDrafts();
        document.querySelectorAll('form[data-autosave]').forEach(initAutosaveForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
