<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman Audit Log untuk HRD (route di grup role:hrd).
 *
 * Hanya-baca: tidak ada aksi ubah/hapus. Lihat App\Support\AuditLogger
 * untuk cara log ditulis.
 */
class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $logs = AuditLog::query()
            ->filter($filters)
            ->orderByDesc('id')
            ->paginate((int) config('audit.per_page', 25))
            ->withQueryString();

        return view('admin.audit-logs', [
            'logs'        => $logs,
            'filters'     => $filters,
            'events'      => AuditLog::EVENTS,
            'types'       => AuditLog::TYPES,
            'actorFilter' => ! empty($filters['actor'])
                ? AuditLogger::userName((int) $filters['actor'])
                : null,
        ]);
    }

    /**
     * Unduh CSV sesuai filter yang sedang aktif. Pengunduhan itu sendiri
     * dicatat ke audit log (data audit bersifat sensitif).
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $limit   = (int) config('audit.export_limit', 50000);

        AuditLogger::log(
            AuditLog::EVENT_EXPORT,
            'Mengunduh audit log (CSV)',
            ['meta' => ['filter' => array_filter($filters), 'batas_baris' => $limit]]
        );

        $filename = 'audit-log-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($filters, $limit) {
            $out = fopen('php://output', 'w');

            // BOM supaya Excel membaca UTF-8 dengan benar.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Waktu', 'Pengguna', 'Peran', 'Aksi', 'Jenis Objek', 'Objek',
                'Deskripsi', 'IP', 'Data Lama', 'Data Baru', 'Info Tambahan',
            ]);

            $written = 0;

            foreach (AuditLog::query()->filter($filters)->lazyByIdDesc(1000) as $log) {
                if ($written >= $limit) {
                    break;
                }

                fputcsv($out, array_map(fn ($value) => $this->csvSafe($value), [
                    $log->created_at?->format('Y-m-d H:i:s'),
                    $log->actor_name,
                    $log->actor_role,
                    $log->eventLabel(),
                    $log->typeLabel(),
                    $log->auditable_label,
                    $log->description,
                    $log->ip_address,
                    $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '',
                    $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '',
                    $log->meta ? json_encode($log->meta, JSON_UNESCAPED_UNICODE) : '',
                ]));

                $written++;
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'q'     => ['nullable', 'string', 'max:100'],
            'event' => ['nullable', Rule::in(array_keys(AuditLog::EVENTS))],
            'type'  => ['nullable', Rule::in(array_keys(AuditLog::TYPES))],
            'actor' => ['nullable', 'integer', 'min:1'],
            'from'  => ['nullable', 'date'],
            'to'    => ['nullable', 'date', 'after_or_equal:from'],
        ]);
    }

    /**
     * Cegah CSV/formula injection: sel yang diawali = + - @ (atau tab/CR)
     * bisa dieksekusi sebagai rumus saat dibuka di Excel. Isi log berasal
     * dari input pengguna (nama akun, dsb.), jadi tidak boleh dipercaya.
     */
    private function csvSafe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }
}
