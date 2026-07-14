<x-layouts.member title="Riwayat Baca" heading="Riwayat baca" description="Lanjutkan dokumen dari halaman terakhir.">
    @forelse($histories as $history)
        <article class="member-book-row"><x-public.book-cover :book="$history->book" /><div><h2><a href="{{ route('reader.show',['book'=>$history->book,'page'=>$history->last_page]) }}">{{ $history->book->title }}</a></h2><p>Halaman {{ $history->last_page }} dari {{ $history->book->page_count ?: '—' }}</p><small>Terakhir dibaca {{ $history->last_read_at?->diffForHumans() }}</small><a class="button-secondary" href="{{ route('reader.show',['book'=>$history->book,'page'=>$history->last_page]) }}">Lanjut membaca</a></div></article>
    @empty<x-public.empty-state title="Riwayat masih kosong" description="Buka sebuah buku untuk mulai mencatat kemajuan baca." />@endforelse
    <div class="mt-8">{{ $histories->links() }}</div>
</x-layouts.member>
