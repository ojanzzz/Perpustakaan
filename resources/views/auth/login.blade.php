<x-layouts.app title="Masuk — E-Perpustakaan Digital KPU">
    <section class="mx-auto flex min-h-[calc(100vh-73px)] max-w-7xl items-center px-5 py-12">
        <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <h1 class="text-3xl font-bold tracking-tight">Masuk</h1>
            <p class="mt-2 text-sm text-slate-600">Gunakan akun anggota atau administrator Anda.</p>

            <form action="/login" method="POST" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100">
                    @error('email')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">Kata sandi</label>
                    <input id="password" name="password" type="password" required
                        class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-red-600 focus:outline-none focus:ring-2 focus:ring-red-100">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-red-700">
                    Ingat saya
                </label>
                <button type="submit" class="w-full rounded-lg bg-red-700 px-4 py-2.5 font-semibold text-white hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-300">
                    Masuk
                </button>
            </form>
        </div>
    </section>
</x-layouts.app>

