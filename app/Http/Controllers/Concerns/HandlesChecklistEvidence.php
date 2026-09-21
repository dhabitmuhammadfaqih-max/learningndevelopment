<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Shared logic untuk checklist "sudah bertemu & evaluasi": user WAJIB
 * memilih salah satu dari 2 metode bukti - Online (Upload File bukti
 * Zoom/Telpon/Chat) ATAU Offline (Kode Pertemuan yang di-generate
 * Penilai saat tatap muka, lihat App\Models\MeetingCode). Lihat
 * resources/views/partials/checklist-pertemuan-toggle.blade.php.
 *
 * CATATAN: metode Offline DULU berupa selfie kamera (evidence_type
 * 'selfie'). Sejak diganti kode pertemuan, jalur selfie tidak menerima
 * input baru lagi - tapi data lama tetap bisa ditampilkan (path
 * fotonya masih di *_konfirmasi_pertemuan_selfie).
 *
 * Dipakai oleh EmployeeController::toggleChecklistPertemuan(),
 * OfficialController::toggleChecklistPertemuanSaya()/
 * toggleChecklistPertemuanPegawai(), &
 * SupervisorController::toggleChecklistPertemuanPejabat() - keempatnya
 * dulu punya logic base64-selfie yang identik, sekarang dipusatkan di
 * sini supaya tidak duplikat & konsisten saat menambah metode upload.
 *
 * PATH file/selfie tetap disimpan ke kolom existing
 * (*_konfirmasi_pertemuan_selfie) baik untuk upload maupun selfie -
 * lihat migration add_selfie_to_checklist_pertemuan_columns. Hanya
 * kolom *_konfirmasi_pertemuan_evidence_type yang baru (migration
 * add_evidence_type_to_checklist_pertemuan_columns), dipakai untuk
 * menampilkan label metode di HRD (User::checklistEvidenceLabel()).
 */
trait HandlesChecklistEvidence
{
    /**
     * Hapus file bukti checklist lama dari disk 'public', kalau ada.
     *
     * WAJIB dipanggil dari controller di 2 momen supaya file lama tidak
     * jadi sampah menumpuk di storage selamanya:
     * 1) Saat checklist DIBATALKAN (toggle dari tercentang -> kosong) -
     *    bukti lama sudah tidak dipakai/ditampilkan di manapun setelah
     *    dibatalkan (lihat checklist-pertemuan-toggle.blade.php, $selfieUrl
     *    hanya dipakai saat $checked true), jadi aman dihapus.
     * 2) Saat checklist DICENTANG ULANG dengan bukti baru (upload/selfie
     *    baru menggantikan path lama di kolom
     *    *_konfirmasi_pertemuan_selfie) - tanpa ini, path lama hilang
     *    dari database tapi file fisiknya tetap ada di disk (orphan).
     *
     * Silent by design (tidak melempar exception) - kegagalan hapus file
     * lama TIDAK BOLEH menggagalkan proses toggle checklist yang sedang
     * berjalan; paling buruk cuma 1 file orphan tersisa, bukan checklist
     * gagal tersimpan.
     */
    protected function deleteChecklistEvidence(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable $e) {
            // Sengaja diamkan - lihat catatan di atas.
        }
    }

    /**
     * Kompres & resize gambar bukti checklist sebelum disimpan, supaya
     * ukuran per file jauh lebih kecil dari aslinya - selfie dari kamera
     * device modern bisa beberapa MB per foto (PNG, resolusi native
     * kamera) padahal cuma dipakai sebagai bukti kecil yang dilihat di
     * halaman HRD/penilai.
     *
     * Dikompres ke JPEG kualitas 75 & sisi terpanjang dibatasi 1000px
     * (cukup jelas untuk bukti, tapi jauh lebih hemat dibanding foto
     * mentah). Kalau ekstensi GD tidak tersedia di server, ATAU gambar
     * gagal diproses (format tidak didukung/corrupt), kembalikan bytes
     * asli apa adanya - jangan sampai proses checklist gagal gara-gara
     * kompresi opsional ini.
     *
     * @return array{0: string, 1: string} [$compressedBytes, $extension]
     */
    protected function compressChecklistImage(string $imageContent): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return [$imageContent, 'png'];
        }

        try {
            $source = @imagecreatefromstring($imageContent);

            if ($source === false) {
                return [$imageContent, 'png'];
            }

            $width = imagesx($source);
            $height = imagesy($source);
            $maxSide = 1000;

            if (max($width, $height) > $maxSide) {
                $ratio = $maxSide / max($width, $height);
                $newWidth = (int) round($width * $ratio);
                $newHeight = (int) round($height * $ratio);

                $resized = imagecreatetruecolor($newWidth, $newHeight);
                imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                imagedestroy($source);
                $source = $resized;
            }

            ob_start();
            imagejpeg($source, null, 75);
            $compressed = ob_get_clean();
            imagedestroy($source);

            if (! $compressed) {
                return [$imageContent, 'png'];
            }

            return [$compressed, 'jpg'];
        } catch (\Throwable $e) {
            return [$imageContent, 'png'];
        }
    }

    /**
     * Validasi input bukti checklist dari request, simpan filenya ke
     * disk 'public' (folder checklist-selfies/, sama seperti selfie
     * existing), dan kembalikan [$path, $evidenceType, $meetingMethod].
     *
     * $meetingMethod ('zoom'/'telpon'/'chat') HANYA wajib & dipakai
     * saat evidence_type = 'upload' (Online) - lihat migration
     * add_meeting_metode_to_checklist_pertemuan_columns &
     * User::CHECKLIST_MEETING_METHODS. Untuk evidence_type = 'selfie'
     * (Offline), selalu null.
     *
     * @return array{0: string, 1: string, 2: ?string}
     *
     * @throws ValidationException
     */
    protected function resolveChecklistEvidence(Request $request, string $rolePrefix, int $subjectId): array
    {
        $validated = $request->validate([
            'evidence_type'  => 'required|in:upload,kode',
            'evidence_file'  => 'required_if:evidence_type,upload|nullable|image|max:5120',
            'meeting_code'   => 'required_if:evidence_type,kode|nullable|string|max:16',
            'meeting_method' => 'required_if:evidence_type,upload|nullable|in:zoom,telpon,chat',
        ], [
            'evidence_type.required'    => 'Silakan pilih metode bukti checklist terlebih dahulu.',
            'evidence_type.in'          => 'Metode bukti checklist tidak valid.',
            'evidence_file.required_if' => 'Silakan pilih file untuk diupload.',
            'evidence_file.image'       => 'File yang diupload harus berupa gambar.',
            'evidence_file.max'         => 'Ukuran file maksimal 5MB.',
            'meeting_code.required_if'  => 'Silakan masukkan kode pertemuan dari Penilai Anda.',
            'meeting_method.required_if' => 'Silakan pilih metode pertemuan (Zoom/Telpon/Chat) terlebih dahulu.',
            'meeting_method.in'          => 'Metode pertemuan tidak valid.',
        ]);

        $meetingMethod = $validated['evidence_type'] === 'upload'
            ? $validated['meeting_method']
            : null;

        // Metode Upload File: file dikirim sebagai multipart. Dikompres
        // dulu (lihat compressChecklistImage()) sebelum disimpan lewat
        // Laravel Storage - TIDAK melalui base64.
        if ($validated['evidence_type'] === 'upload') {
            $file = $request->file('evidence_file');

            // File lolos validasi 'image' tapi gagal sampai utuh ke
            // server (mis. upload_max_filesize/post_max_size di
            // php.ini terlalu kecil, atau folder tmp PHP tidak
            // writable) - tanpa cek ini, ->store() akan gagal dengan
            // error mentah "Path cannot be empty" yang membingungkan.
            //
            // ->isValid() saja TERNYATA TIDAK CUKUP: pernah terjadi
            // isValid() balikin true tapi path-nya tidak terbaca (lihat
            // catatan getPathname() vs getRealPath() di bawah).
            // PENTING (Windows/Laragon): sengaja pakai getPathname(),
            // BUKAN getRealPath().
            //
            // getRealPath() memanggil realpath() PHP, yang punya bug lama
            // di Windows: kalau folder temp upload PHP ada di balik
            // junction/symlink (default Laragon: C:\laragon\tmp adalah
            // junction), realpath() bisa mengembalikan `false` padahal
            // file-nya valid & bisa dibaca normal - lihat log
            // 'Checklist evidence upload ditolak' yang pernah muncul
            // (is_valid: true, upload_error_code: 0, TAPI real_path:
            // false). Akibatnya file yang sebenarnya OK selalu ditolak
            // di server Windows tertentu.
            //
            // getPathname() mengembalikan path asli tanpa resolve
            // symlink/junction - ini juga yang dipakai Laravel sendiri
            // di balik layar saat ->store()/->move(), jadi lebih aman
            // dipakai di sini juga.
            $path = $file?->getPathname();

            if (
                ! $file
                || ! $file->isValid()
                || ! $path
                || ! is_readable($path)
            ) {
                // Sebelumnya tidak ada log sama sekali di sini - jadi
                // saat upload gagal, tidak ada jejak di laravel.log untuk
                // dilihat (ValidationException memang tidak dicatat oleh
                // Laravel, itu perilaku normal - dianggap kesalahan input
                // user, bukan error sistem). Tambahkan log manual di sini
                // supaya kalau upload gagal lagi, penyebab PERSISNYA
                // (file null? PHP upload error code berapa? path kosong?
                // tidak readable?) langsung ketahuan dari log, tidak
                // perlu nebak-nebak.
                Log::warning('Checklist evidence upload ditolak.', [
                    'has_file' => (bool) $file,
                    'is_valid' => $file?->isValid(),
                    'upload_error_code' => $file?->getError(),
                    'upload_error_message' => $file?->getErrorMessage(),
                    'pathname' => $path,
                    'client_original_name' => $file?->getClientOriginalName(),
                    'client_mime_type' => $file?->getClientMimeType(),
                    'size' => $file?->getSize(),
                ]);

                throw ValidationException::withMessages([
                    'evidence_file' => 'Upload file gagal, silakan coba lagi dengan file yang lebih kecil.',
                ]);
            }

            [$compressed, $extension] = $this->compressChecklistImage(
                file_get_contents($path)
            );

            $path = 'checklist-selfies/' . $rolePrefix . '_' . $subjectId . '_' . time() . '.' . $extension;

            // Jaring pengaman terakhir: kalaupun lolos semua cek di
            // atas tapi penyimpanan tetap gagal karena kondisi
            // environment yang tidak terduga, jangan sampai bocor jadi
            // error 500 mentah ke user - ubah jadi pesan validasi yang
            // jelas.
            try {
                Storage::disk('public')->put($path, $compressed);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'evidence_file' => 'Upload file gagal, silakan coba lagi dengan file yang lebih kecil, atau gunakan metode Ambil Selfie.',
                ]);
            }

            return [$path, 'upload', $meetingMethod, null];
        }

        // Metode Offline: tidak ada file sama sekali, yang dikirim
        // cuma kode pertemuan yang ditunjukkan Penilai saat tatap muka.
        // Validasi & penandaan "sudah dipakai" dilakukan di
        // MeetingCode::redeem() (dipanggil controller) - di sini cukup
        // normalisasi bentuknya, karena user mengetik manual dan sering
        // menambah spasi/tanda hubung atau memakai huruf kecil.
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $validated['meeting_code'] ?? ''));

        if ($code === '') {
            throw ValidationException::withMessages([
                'meeting_code' => 'Silakan masukkan kode pertemuan dari Penilai Anda.',
            ]);
        }

        return [null, 'kode', null, $code];
    }
}