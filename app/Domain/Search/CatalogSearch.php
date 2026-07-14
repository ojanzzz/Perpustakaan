<?php

namespace App\Domain\Search;

use App\Domain\Catalog\BookAccessService;
use App\Models\Book;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogSearch
{
    public function __construct(private readonly BookAccessService $access) {}

    /** @param array<string, mixed> $filters */
    public function paginate(array $filters, ?User $user, int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->build($filters, $user);
        $results = $query->paginate($perPage)->withQueryString();

        $term = trim((string) ($filters['q'] ?? ''));
        if ($term !== '') {
            DB::table('search_logs')->insert([
                'query' => Str::limit($term, 255, ''),
                'normalized_query' => Str::lower(Str::squish($term)),
                'result_count' => $results->total(),
                'filters' => json_encode(collect($filters)->except('q')->filter()->all(), JSON_THROW_ON_ERROR),
                'session_hash' => request()->hasSession()
                    ? hash_hmac('sha256', request()->session()->getId(), (string) config('app.key'))
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $results;
    }

    /** @return Collection<int, string> */
    public function publicationTypes(?User $user): Collection
    {
        return $this->access->discoverableQuery($user)
            ->whereNotNull('publication_type')
            ->reorder()
            ->distinct()
            ->orderBy('publication_type')
            ->pluck('publication_type');
    }

    /** @param array<string, mixed> $filters
     * @return Builder<Book>
     */
    public function build(array $filters, ?User $user): Builder
    {
        $query = $this->access->discoverableQuery($user)
            ->with(['authors:id,name,slug', 'publisher:id,name,slug', 'language:id,name,code', 'categories:id,name,slug', 'collections:id,name,slug'])
            ->withCount(['views', 'downloads']);

        if ($term = trim((string) ($filters['q'] ?? ''))) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $query->where(function (Builder $query) use ($like, $term): void {
                $query->where('title', 'like', $like)
                    ->orWhere('subtitle', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('isbn', 'like', $like)
                    ->orWhere('document_number', 'like', $like)
                    ->orWhere('publication_year', ctype_digit($term) ? (int) $term : -1)
                    ->orWhereHas('authors', fn (Builder $relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('publisher', fn (Builder $relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('categories', fn (Builder $relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('collections', fn (Builder $relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('tags', fn (Builder $relation) => $relation->where('name', 'like', $like));
            });
        }

        $relationFilters = [
            'category' => ['categories', 'categories.id'],
            'collection' => ['collections', 'collections.id'],
            'author' => ['authors', 'authors.id'],
        ];
        foreach ($relationFilters as $key => [$relation, $column]) {
            if (! empty($filters[$key])) {
                $query->whereHas($relation, fn (Builder $builder) => $builder->where($column, $filters[$key]));
            }
        }

        foreach (['publisher' => 'publisher_id', 'language' => 'language_id'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        if (! empty($filters['year_from'])) {
            $query->where('publication_year', '>=', $filters['year_from']);
        }
        if (! empty($filters['year_to'])) {
            $query->where('publication_year', '<=', $filters['year_to']);
        }
        if (! empty($filters['publication_type'])) {
            $query->where('publication_type', $filters['publication_type']);
        }
        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        return match ($filters['sort'] ?? 'custom') {
            'newest' => $query->orderByDesc('published_at'),
            'oldest' => $query->orderBy('published_at'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            'popular' => $query->orderByDesc('views_count')->orderBy('title'),
            'downloaded' => $query->orderByDesc('downloads_count')->orderBy('title'),
            default => $query->orderBy('sort_order')->orderByDesc('published_at')->orderBy('title'),
        };
    }
}
