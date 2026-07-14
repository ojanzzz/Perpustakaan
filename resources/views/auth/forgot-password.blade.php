<x-layouts.app title="Lupa Kata Sandi">
    <section class="mx-auto max-w-lg px-5 py-16">
        <h1 class="text-3xl font-bold">Lupa kata sandi</h1>
        @if(session('status'))<p class="mt-4 text-green-700">{{ session('status') }}</p>@endif
        <form method="POST" action="/forgot-password" class="mt-8 space-y-5">
            @csrf
            <label class="block">Email<input name="email" type="email" required class="mt-2 w-full rounded-lg border px-3 py-2"></label>
            <button class="rounded-lg bg-red-700 px-5 py-2.5 font-semibold text-white">Kirim tautan reset</button>
        </form>
    </section>
</x-layouts.app>

