<x-layouts.app title="Atur Ulang Kata Sandi">
    <section class="mx-auto max-w-lg px-5 py-16">
        <h1 class="text-3xl font-bold">Atur ulang kata sandi</h1>
        <form method="POST" action="/reset-password" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block">Email<input name="email" type="email" value="{{ $email }}" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <label class="block">Kata sandi baru<input name="password" type="password" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <label class="block">Konfirmasi kata sandi<input name="password_confirmation" type="password" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            @if($errors->any())<div class="text-sm text-red-700">{{ $errors->first() }}</div>@endif
            <button class="rounded-lg bg-red-700 px-5 py-2.5 font-semibold text-white">Simpan kata sandi</button>
        </form>
    </section>
</x-layouts.app>

