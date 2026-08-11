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
                'karyawan' => ['label' => 'Karyawan', 'hint' => 'Pemohon / pengaju dokumen', 'order' => '01'],
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
}