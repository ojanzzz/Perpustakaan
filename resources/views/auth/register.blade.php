<x-layouts.app title="Daftar — E-Perpustakaan Digital KPU">
    <section class="mx-auto max-w-lg px-5 py-16">
        <h1 class="text-3xl font-bold">Daftar Anggota</h1>
        <form method="POST" action="/register" class="mt-8 space-y-5">
            @csrf
            <label class="block">Nama<input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <label class="block">Email<input name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <label class="block">Kata sandi<input name="password" type="password" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <label class="block">Konfirmasi kata sandi<input name="password_confirmation" type="password" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            @if($errors->any())<div class="text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <button class="rounded-lg bg-red-700 px-5 py-2.5 font-semibold text-white">Daftar</button>
        </form>
    </section>
</x-layouts.app>

