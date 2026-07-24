@php
    $description = $book->description ? \Illuminate\Support\Str::limit(strip_tags($book->description), 160) : 'Detail publikasi '.$book->title;
    $coverUrl = $book->coverUrl();
    $jsonLd = ['@context'=>'https://schema.org','@type'=>'Book','name'=>$book->title,'url'=>route('books.show',$book),'datePublished'=>$book->publication_date?->toDateString() ?: $book->publication_year,'inLanguage'=>$book->language?->code,'isbn'=>$book->isbn,'author'=>$book->authors->map(fn($author)=>['@type'=>'Person','name'=>$author->name])->all()];
@endphp
<x-layouts.app :title="$book->title.' — E-Perpustakaan Digital KPU'" :description="$description" :canonical="route('books.show',$book)" :image="$coverUrl" :json-ld="$jsonLd">
    <x-public.breadcrumb :items="['Katalog'=>route('catalog.index'), $book->title=>null]"/>
    <section class="portal-container pb-16 pt-4">
        <div class="detail-layout">
            <div class="detail-cover"><x-public.book-cover :book="$book" /></div>
            <article class="min-w-0">
                <h1 class="page-title">{{ $book->title }}</h1>@if($book->subtitle)<p class="mt-2 text-xl text-slate-500">{{ $book->subtitle }}</p>@endif
                <p class="mt-5 font-semibold text-navy">{{ $book->authors->pluck('name')->join(', ') ?: ($book->publisher?->name ?: 'Publikasi digital') }}</p>
                <dl class="metadata-list">
                    @foreach([['Tahun terbit',$book->publication_year],['ISBN / No. dokumen',$book->isbn ?: $book->document_number],['Jumlah halaman',$book->page_count ? $book->page_count.' halaman' : null],['Ukuran file',$book->file_size ? number_format($book->file_size/1048576,2).' MB' : null],['Jenis publikasi',$book->publication_type]] as [$label,$value])@if($value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endif @endforeach
                </dl>
                @if($locked)
                    <div class="access-panel locked"><div><x-public.icon name="lock" class="size-7"/><h2>Masukkan kata sandi</h2><p>Dokumen ini dilindungi. Kata sandi hanya digunakan untuk sesi browser ini.</p></div><form method="POST" action="{{ route('books.unlock',$book) }}">@csrf<label for="book-password">Kata sandi dokumen</label><div class="flex gap-2"><input id="book-password" name="password" type="password" required autocomplete="current-password"><button class="button-primary">Buka akses</button></div>@error('password')<p role="alert" class="form-error">{{ $message }}</p>@enderror</form></div>
                @else
                    @if($book->original_file)
                        <div class="mt-7 flex flex-wrap gap-3"><a class="button-primary" href="{{ route('reader.show',$book) }}"><x-public.icon name="book"/> Baca sekarang</a></div>
                        <p class="mt-3 text-xs text-slate-500">Dokumen dibuka melalui pembaca digital dan tautan privat sementara.</p>
                    @else
                        <div class="mt-7 flex flex-wrap gap-3"><span class="button-secondary cursor-default" aria-disabled="true">File belum tersedia</span></div>
                    @endif
                @endif
            </article>
        </div>
        @unless($locked)
            <div class="description-block"><h2>Deskripsi</h2><div class="prose-copy">{!! nl2br(e($book->description ?: 'Deskripsi belum tersedia.')) !!}</div><dl class="taxonomy-list">@if($book->categories->isNotEmpty())<div><dt>Kategori</dt><dd>@foreach($book->categories as $item)<a href="{{ route('categories.show',$item) }}">{{ $item->name }}</a>@endforeach</dd></div>@endif @if($book->collections->isNotEmpty())<div><dt>Koleksi</dt><dd>@foreach($book->collections as $item)<a href="{{ route('collections.show',$item) }}">{{ $item->name }}</a>@endforeach</dd></div>@endif @if($book->tags->isNotEmpty())<div><dt>Tag</dt><dd>@foreach($book->tags as $item)<span>{{ $item->name }}</span>@endforeach</dd></div>@endif</dl></div>
            @if($related->isNotEmpty())<section class="mt-14"><div class="section-heading"><div><h2>Buku terkait</h2><p>Publikasi lain dalam kategori serupa.</p></div></div><div class="book-grid related-grid">@foreach($related as $item)<x-public.book-card :book="$item"/>@endforeach</div></section>@endif
        @endunless
    </section>
</x-layouts.app>
