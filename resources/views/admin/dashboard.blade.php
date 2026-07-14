<x-layouts.admin title="Ringkasan">
    <div class="mb-7"><h1 class="text-3xl font-bold tracking-tight">Ringkasan</h1><p class="mt-2 text-slate-600">Status katalog dan operasional perpustakaan digital.</p></div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['books'=>'Total buku','drafts'=>'Draft','published'=>'Terbit','private'=>'Privat','users'=>'Pengguna','readers_today'=>'Pembaca hari ini','downloads'=>'Unduhan','failed'=>'Pemrosesan gagal'] as $key=>$label)
            <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-600">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ number_format($metrics[$key]) }}</p></article>
        @endforeach
    </div>
    <section class="mt-7 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="font-bold">Kunjungan tujuh hari</h2><p class="mt-1 text-sm text-slate-500">Jumlah pembukaan buku per hari.</p></div></div><div class="mt-5 h-64"><canvas data-visit-chart data-labels='@json($visitChart->pluck("label"))' data-values='@json($visitChart->pluck("value"))' aria-label="Grafik kunjungan tujuh hari" role="img"></canvas></div></section>
    <div class="mt-7 grid gap-6 xl:grid-cols-[2fr_1fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Buku terbaru</h2><div class="mt-4 divide-y divide-slate-100">@forelse($latestBooks as $book)<div class="flex items-center justify-between py-3"><div><p class="font-medium">{{ $book->title }}</p><p class="text-xs text-slate-500">{{ $book->created_at->format('d M Y') }}</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs">{{ $book->status->value }}</span></div>@empty<p class="py-8 text-center text-sm text-slate-500">Belum ada buku.</p>@endforelse</div></section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Status backup</h2><p class="mt-4 text-sm text-slate-600">{{ $backup ? 'Backup terakhir: '.$backup->status : 'Belum ada riwayat backup.' }}</p></section>
    </div>
</x-layouts.admin>
