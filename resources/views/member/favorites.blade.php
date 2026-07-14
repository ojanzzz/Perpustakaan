<x-layouts.member title="Favorit" heading="Buku favorit" description="Buku yang Anda simpan untuk dibaca kembali.">
    @if($books->isEmpty())<x-public.empty-state title="Belum ada favorit" description="Tekan ikon hati pada reader untuk menyimpan buku." />
    @else<div class="book-grid">@foreach($books as $book)<x-public.book-card :book="$book" />@endforeach</div><div class="mt-8">{{ $books->links() }}</div>@endif
</x-layouts.member>
