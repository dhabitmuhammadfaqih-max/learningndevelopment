<x-dashboard-layout title="Notifikasi">

    <div class="max-w-2xl mx-auto">

        <div class="flex items-center justify-between mb-6">
            <p class="text-sm text-slate-500">
                {{ $notifications->total() }} notifikasi
            </p>

            @if ($notifications->where('read_at', null)->count() > 0 || $notifications->total() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-700">
                        Tandai semua sudah dibaca
                    </button>
                </form>
            @endif
        </div>

        @if ($notifications->isEmpty())
            <div class="bg-white rounded-2xl border border-slate-100 py-16 text-center">
                <div class="w-14 h-14 rounded-full bg-slate-50 grid place-items-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-slate-300">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-600">Belum ada notifikasi</p>
                <p class="text-xs text-slate-400 mt-1">Notifikasi baru akan muncul di sini.</p>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-slate-100 divide-y divide-slate-50 overflow-hidden">
                @foreach ($notifications as $notification)
                    <form method="POST"
                          action="{{ route('notifications.read', $notification) }}"
                          @if($notification->url) onsubmit="setTimeout(() => window.location.href = @js($notification->url), 50)" @endif
                          class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-5 py-4 flex gap-3 items-start hover:bg-slate-50 transition
                            {{ $notification->isRead() ? '' : 'bg-blue-50/40' }}">

                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ $notification->isRead() ? 'bg-transparent' : 'bg-blue-600' }}"></span>

                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-semibold text-slate-800">{{ $notification->title }}</span>
                                <span class="block text-sm text-slate-500 mt-0.5">{{ $notification->body }}</span>
                                <span class="block text-xs text-slate-400 mt-1.5">{{ $notification->created_at->diffForHumans() }}</span>
                            </span>
                        </button>
                    </form>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>

</x-dashboard-layout>
