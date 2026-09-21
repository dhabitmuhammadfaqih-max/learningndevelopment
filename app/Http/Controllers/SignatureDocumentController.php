<?php

namespace App\Http\Controllers;

use App\Models\SignatureDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SignatureDocumentController extends Controller
{
    /**
     * Buat dokumen baru lalu arahkan ke halaman tanda tangan.
     */
    public function create()
    {
        $document = SignatureDocument::create([
            'nomor_dokumen' => 'DOC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'status' => 'draft',
        ]);

        return redirect()->route('signature.edit', $document);
    }

    /**
     * Halaman utama untuk mengisi & menandatangani dokumen.
     */
    public function edit(SignatureDocument $document)
    {
        return view('signature.create', [
            'document' => $document,
            'roles' => [
                'pegawai' => ['label' => 'Pegawai', 'hint' => 'Pemohon / pengaju dokumen', 'order' => '01'],
                'pejabat' => ['label' => 'Pejabat', 'hint' => 'Pemeriksa / penyetuju tingkat pertama', 'order' => '02'],
                'atasan' => ['label' => 'Atasan Pejabat', 'hint' => 'Pengesah akhir', 'order' => '03'],
            ],
        ]);
    }

    /**
     * Simpan tanda tangan untuk satu role tertentu.
     * Menolak jika bukan giliran role tersebut (urutan dijaga di server).
     */
    public function saveSignature(Request $request, SignatureDocument $document, string $role)
    {
        if (! in_array($role, SignatureDocument::ROLES, true)) {
            abort(404);
        }

        if ($document->nextRole() !== $role) {
            return response()->json([
                'message' => 'Belum giliran role ini untuk menandatangani.',
            ], 422);
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jabatan' => ['required', 'string', 'max:150'],
            'signature' => ['required', 'string'], // base64 data URL from canvas
        ]);

        // Decode base64 PNG dari canvas (format: data:image/png;base64,....)
        $imageData = $validated['signature'];
        if (! preg_match('/^data:image\/png;base64,/', $imageData)) {
            return response()->json(['message' => 'Format tanda tangan tidak valid.'], 422);
        }

        $imageContent = base64_decode(substr($imageData, strpos($imageData, ',') + 1));
        $imageContent = $this->optimizeSignaturePng($imageContent);
        $filename = "signatures/{$document->id}_{$role}_" . time() . '.png';
        Storage::disk('public')->put($filename, $imageContent);

        // Hapus file lama jika sebelumnya ada (jaga-jaga)
        if ($document->{"{$role}_signature"}) {
            Storage::disk('public')->delete($document->{"{$role}_signature"});
        }

        $document->update([
            "{$role}_nama" => $validated['nama'],
            "{$role}_jabatan" => $validated['jabatan'],
            "{$role}_signature" => $filename,
            "{$role}_signed_at" => now(),
            'status' => $document->isComplete() ? 'completed' : "{$role}_signed",
        ]);

        $document->refresh();

        return response()->json([
            'message' => 'Tanda tangan berhasil disimpan.',
            'next_role' => $document->nextRole(),
            'is_complete' => $document->isComplete(),
            'signature_url' => Storage::disk('public')->url($filename),
        ]);
    }

    /**
     * Buat dan unduh PDF setelah semua pihak menandatangani.
     */
    public function generatePdf(SignatureDocument $document)
    {
        if (! $document->isComplete()) {
            abort(422, 'Dokumen belum ditandatangani oleh semua pihak.');
        }

        // Sematkan gambar sebagai base64 supaya dompdf tidak perlu
        // mengakses file lewat HTTP (lebih aman & konsisten).
        $signatures = [];
        foreach (SignatureDocument::ROLES as $role) {
            $path = $document->{"{$role}_signature"};
            $signatures[$role] = $path
                ? 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($path))
                : null;
        }

        $pdf = Pdf::loadView('signature.pdf', [
            'document' => $document,
            'signatures' => $signatures,
        ])->setPaper('a4', 'portrait');

        return $pdf->download("dokumen-{$document->nomor_dokumen}.pdf");
    }

    /**
     * Optimasi PNG tanda tangan dari canvas sebelum disimpan.
     *
     * Beda dari kompresi selfie checklist (compressChecklistImage - resize
     * + convert ke JPEG kualitas 75): tanda tangan WAJIB tetap PNG supaya
     * background-nya transparan (dipakai di atas garis tanda tangan saat
     * ditampilkan/di-print ke PDF), jadi tidak boleh di-lossy-compress atau
     * diubah ke JPEG.
     *
     * Dua optimasi yang AMAN (lossless, tidak mengubah tampilan sama
     * sekali) yang dilakukan di sini:
     * 1) Crop ke bounding box goresan tanda tangan - kanvas gambar
     *    biasanya jauh lebih besar (mis. 600x200px) daripada area yang
     *    benar-benar digoresin, sisanya transparan kosong percuma.
     * 2) Re-encode dengan level kompresi PNG maksimum (9) - PNG dari
     *    canvas browser biasanya tidak dikompres maksimal secara default.
     *
     * Kalau ekstensi GD tidak ada, atau proses gagal/gambar kosong sama
     * sekali (belum digoresin), kembalikan bytes asli apa adanya - jangan
     * sampai proses tanda tangan gagal gara-gara optimasi opsional ini.
     */
    protected function optimizeSignaturePng(string $imageContent): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $imageContent;
        }

        try {
            $source = @imagecreatefromstring($imageContent);

            if ($source === false) {
                return $imageContent;
            }

            $width = imagesx($source);
            $height = imagesy($source);

            // Cari bounding box piksel yang tidak transparan (goresan
            // tanda tangannya), supaya bisa crop area kosong di sekitarnya.
            $minX = $width;
            $minY = $height;
            $maxX = 0;
            $maxY = 0;
            $hasInk = false;

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $alpha = (imagecolorat($source, $x, $y) >> 24) & 0x7F;

                    // alpha 127 = fully transparent di GD (skala 0-127,
                    // kebalikan dari CSS/PNG standar 0-255).
                    if ($alpha < 127) {
                        $hasInk = true;
                        $minX = min($minX, $x);
                        $minY = min($minY, $y);
                        $maxX = max($maxX, $x);
                        $maxY = max($maxY, $y);
                    }
                }
            }

            // Kanvas kosong (user submit tanpa goresan) - tidak mungkin
            // lolos validasi form normal, tapi jaga-jaga saja: jangan
            // crop jadi 0x0, kembalikan apa adanya.
            if (! $hasInk) {
                imagedestroy($source);

                return $imageContent;
            }

            // Beri padding tipis di sekeliling goresan supaya tidak
            // terlalu mepet ke tepi gambar hasil crop.
            $padding = 10;
            $cropX = max(0, $minX - $padding);
            $cropY = max(0, $minY - $padding);
            $cropWidth = min($width, $maxX + $padding) - $cropX;
            $cropHeight = min($height, $maxY + $padding) - $cropY;

            $cropped = imagecreatetruecolor($cropWidth, $cropHeight);
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
            imagefill($cropped, 0, 0, $transparent);

            imagecopy($cropped, $source, 0, 0, $cropX, $cropY, $cropWidth, $cropHeight);
            imagedestroy($source);

            ob_start();
            // Level 9 = kompresi PNG maksimum, tetap lossless (tidak ada
            // piksel yang berubah/hilang, cuma cara encode-nya lebih padat).
            imagepng($cropped, null, 9);
            $optimized = ob_get_clean();
            imagedestroy($cropped);

            return $optimized ?: $imageContent;
        } catch (\Throwable $e) {
            return $imageContent;
        }
    }
}