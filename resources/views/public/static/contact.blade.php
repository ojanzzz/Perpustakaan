<x-layouts.app title="Kontak — E-Perpustakaan Digital KPU" description="Kirim saran atau laporan dokumen kepada pengelola E-Perpustakaan Digital KPU.">
    <x-public.breadcrumb :items="['Kontak' => null]"/>
    <div class="portal-container grid gap-8 py-10 lg:grid-cols-[.8fr_1.2fr]">
        <article class="static-page !mx-0 !py-0">
            <h1>Kontak pengelola</h1>
            <p>Gunakan formulir ini untuk menyampaikan usulan koleksi, kendala akses, atau laporan dokumen bermasalah. Jangan mencantumkan data pribadi sensitif.</p>
            <h2>Waktu penanganan</h2>
            <p>Pesan ditinjau pada hari kerja. Status penyelesaian dicatat oleh pengelola untuk akuntabilitas layanan.</p>
        </article>
        <form method="POST" action="{{ route('feedback.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold">Jenis<select name="type" class="mt-2 w-full rounded-lg border-slate-300" required><option value="suggestion">Saran</option><option value="report">Laporan dokumen</option><option value="contact">Pertanyaan</option></select></label>
                <label class="text-sm font-semibold">Nama<input name="name" value="{{ old('name', auth()->user()?->name) }}" maxlength="120" class="mt-2 w-full rounded-lg border-slate-300"></label>
                <label class="text-sm font-semibold sm:col-span-2">Email<input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" maxlength="190" class="mt-2 w-full rounded-lg border-slate-300"></label>
                <label class="text-sm font-semibold sm:col-span-2">Subjek<input name="subject" value="{{ old('subject') }}" minlength="5" maxlength="180" class="mt-2 w-full rounded-lg border-slate-300" required></label>
                <label class="text-sm font-semibold sm:col-span-2">Pesan<textarea name="message" rows="6" minlength="10" maxlength="5000" class="mt-2 w-full rounded-lg border-slate-300" required>{{ old('message') }}</textarea></label>
            </div>
            <button class="mt-5 rounded-lg bg-red-700 px-5 py-3 font-bold text-white hover:bg-red-800">Kirim pesan</button>
        </form>
    </div>
</x-layouts.app>
