<x-layouts.member title="Bookmark" heading="Bookmark halaman" description="Penanda halaman dari seluruh dokumen yang Anda baca.">
    <div class="member-list">
    @forelse($bookmarks as $bookmark)<article><div><strong>{{ $bookmark->label ?: 'Halaman '.$bookmark->page }}</strong><a href="{{ route('books.show',$bookmark->book) }}">{{ $bookmark->book->title }}</a>@if($bookmark->note)<p>{{ $bookmark->note }}</p>@endif</div><a class="button-secondary" href="{{ route('reader.show',['book'=>$bookmark->book,'page'=>$bookmark->page]) }}">Buka halaman {{ $bookmark->page }}</a></article>
    @empty<x-public.empty-state title="Belum ada bookmark" description="Tandai halaman penting melalui panel bookmark di reader." />@endforelse
    </div><div class="mt-8">{{ $bookmarks->links() }}</div>
</x-layouts.member>
