{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach([route('home'),route('catalog.index'),route('catalog.latest'),route('catalog.popular'),route('about'),route('guide'),route('contact'),route('privacy')] as $url)<url><loc>{{ $url }}</loc></url>
@endforeach
@foreach($books as $book)<url><loc>{{ route('books.show',$book) }}</loc><lastmod>{{ $book->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@foreach($categories as $category)<url><loc>{{ route('categories.show',$category) }}</loc><lastmod>{{ $category->updated_at->toAtomString() }}</lastmod></url>
@endforeach
@foreach($collections as $collection)<url><loc>{{ route('collections.show',$collection) }}</loc><lastmod>{{ $collection->updated_at->toAtomString() }}</lastmod></url>
@endforeach
</urlset>

