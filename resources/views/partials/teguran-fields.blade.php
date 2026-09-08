@php
    $teguranPernahValue = $teguranPernahValue ?? 'tidak';
    $teguranKeteranganValue = $teguranKeteranganValue ?? '';
    $subjectLabel = $subjectLabel ?? 'pegawai';
@endphp

<div class="mt-4" x-data="{ pernah: {{ Illuminate\Support\Js::from((string) $teguranPernahValue) }} }">
    <label class="block text-sm font-bold text-slate-700 mb-1.5">
        Apakah {{ $subjectLabel }} yang bersangkutan pernah diberikan teguran baik tulis ataupun lisan?
        <span class="font-normal text-slate-400 text-xs">(opsional)</span>
    </label>

    <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-1.5 text-sm text-slate-700 cursor-pointer">
            <input type="radio" name="teguran_pernah" value="tidak" x-model="pernah"
                   class="w-4 h-4 border-slate-300 text-blue-600 focus:ring-blue-500">
            Tidak pernah
        </label>
        <label class="flex items-center gap-1.5 text-sm text-slate-700 cursor-pointer">
            <input type="radio" name="teguran_pernah" value="ya" x-model="pernah"
                   class="w-4 h-4 border-slate-300 text-blue-600 focus:ring-blue-500">
            Pernah
        </label>
    </div>
    @error('teguran_pernah')
        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
    @enderror

    <div class="mt-3 max-w-xl" x-show="pernah === 'ya'" x-cloak>
        <label class="block text-sm font-bold text-slate-700 mb-1.5">
            Keterangan Teguran <span class="font-normal text-slate-400 text-xs">(opsional)</span>
        </label>
        <textarea name="teguran" maxlength="1000" placeholder="Tulis keterangan teguran jika ada..."
                  class="w-full min-h-[90px] rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-rose-400 focus:ring-1 focus:ring-rose-400 outline-none resize-y">{{ old('teguran', $teguranKeteranganValue) }}</textarea>
        @error('teguran')
            <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
        @enderror
    </div>
</div>
