<x-layouts.admin title="Saran dan laporan">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="text-3xl font-bold tracking-tight">Saran dan laporan</h1><p class="mt-2 text-slate-600">Tindak lanjut masukan publik dan laporan dokumen secara terukur.</p></div>
        <form method="GET"><label class="text-xs font-bold text-slate-600">Status<select name="status" onchange="this.form.submit()" class="mt-1 block rounded-lg border-slate-300"><option value="">Semua</option>@foreach(['new'=>'Baru','in_progress'=>'Diproses','resolved'=>'Selesai','closed'=>'Ditutup'] as $value=>$label)<option value="{{ $value }}" @selected($status===$value)>{{ $label }}</option>@endforeach</select></label></form>
    </div>
    <div class="mt-6 space-y-4">
        @forelse($feedback as $item)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-red-700">{{ $item->type }} · {{ $item->status }}</p><h2 class="mt-1 text-lg font-bold">{{ $item->subject }}</h2><p class="text-sm text-slate-500">{{ $item->name ?: 'Anonim' }} · {{ $item->email ?: 'tanpa email' }} · {{ $item->created_at->format('d M Y H:i') }}</p></div>@if($item->book)<a class="text-sm font-semibold text-red-700" href="{{ route('books.show',$item->book) }}">{{ $item->book->title }}</a>@endif</div>
                <p class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ $item->message }}</p>
                <form method="POST" action="{{ route('admin.feedback.update',$item) }}" class="mt-5 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[180px_1fr_auto]">@csrf @method('PUT')<select name="status" class="rounded-lg border-slate-300">@foreach(['new'=>'Baru','in_progress'=>'Diproses','resolved'=>'Selesai','closed'=>'Ditutup'] as $value=>$label)<option value="{{ $value }}" @selected($item->status===$value)>{{ $label }}</option>@endforeach</select><input name="resolution_notes" value="{{ $item->resolution_notes }}" maxlength="2000" placeholder="Catatan tindak lanjut" class="rounded-lg border-slate-300"><button class="rounded-lg bg-slate-950 px-4 py-2 text-sm font-bold text-white">Simpan</button></form>
            </article>
        @empty <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Belum ada pesan pada filter ini.</div> @endforelse
    </div>
    <div class="mt-6">{{ $feedback->links() }}</div>
</x-layouts.admin>
