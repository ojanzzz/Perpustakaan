<x-layouts.admin title="Koleksi">
    <h1 class="text-3xl font-bold">Koleksi</h1>
    <div class="mt-6 grid gap-6 xl:grid-cols-[360px_1fr]">
        @can('taxonomy.manage')
            <form method="POST" action="{{ route('admin.collections.store') }}" class="space-y-4 rounded-xl border bg-white p-5">@csrf<h2 class="font-bold">Tambah koleksi</h2><label class="block text-sm">Nama<input name="name" required class="mt-1 w-full rounded-lg border px-3 py-2"></label><label class="block text-sm">Visibilitas<select name="visibility" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="public">Publik</option><option value="unlisted">Unlisted</option><option value="private">Privat</option></select></label><input type="hidden" name="status" value="active"><input type="hidden" name="sort_order" value="0"><button class="rounded-lg bg-red-700 px-4 py-2 font-semibold text-white">Tambah</button></form>
        @endcan
        <div class="overflow-x-auto rounded-xl border bg-white"><table class="min-w-full text-sm"><thead><tr class="bg-slate-50 text-left"><th class="p-3">Nama</th><th class="p-3">Visibilitas</th><th class="p-3">Buku</th></tr></thead><tbody class="divide-y">
            @foreach($collections as $item)<tr><td class="p-3 font-medium">{{ $item->name }}
                @can('taxonomy.manage')<details class="mt-2 font-normal"><summary class="cursor-pointer text-xs font-semibold text-red-700">Edit</summary><form method="POST" action="{{ route('admin.collections.update',$item) }}" class="mt-2 grid gap-2">@csrf @method('PUT')<input name="name" value="{{ $item->name }}" class="rounded border px-2 py-1.5"><select name="visibility" class="rounded border px-2 py-1.5"><option value="public" @selected($item->visibility==='public')>Publik</option><option value="unlisted" @selected($item->visibility==='unlisted')>Unlisted</option><option value="private" @selected($item->visibility==='private')>Privat</option></select><input type="hidden" name="sort_order" value="{{ $item->sort_order }}"><input type="hidden" name="status" value="{{ $item->status }}"><button class="rounded bg-slate-900 px-3 py-1.5 text-xs text-white">Simpan</button></form></details>@endcan
            </td><td class="p-3">{{ $item->visibility }}</td><td class="p-3">{{ $item->books_count }}</td></tr>@endforeach
        </tbody></table></div>
    </div><div class="mt-5">{{ $collections->links() }}</div>
</x-layouts.admin>
