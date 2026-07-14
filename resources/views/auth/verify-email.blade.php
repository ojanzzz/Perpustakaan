<x-layouts.app title="Verifikasi Email">
    <section class="mx-auto max-w-lg px-5 py-16">
        <h1 class="text-3xl font-bold">Verifikasi email</h1>
        <p class="mt-4 text-slate-600">Periksa kotak masuk Anda untuk melanjutkan verifikasi.</p>
        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button class="rounded-lg bg-red-700 px-5 py-2.5 font-semibold text-white">Kirim ulang email</button>
        </form>
    </section>
</x-layouts.app>

