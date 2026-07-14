<x-layouts.member title="Profil" heading="Profil anggota" description="Kelola identitas akun, keamanan, dan pilihan penghapusan akun.">
    <div class="member-form-grid">
        <form method="POST" action="{{ route('member.profile.update') }}" class="member-panel">@csrf @method('PUT')
            <h2>Informasi profil</h2><p>Perubahan email memerlukan verifikasi ulang.</p>
            <label>Nama lengkap<input name="name" value="{{ old('name',$user->name) }}" required></label>@error('name')<small class="form-error">{{ $message }}</small>@enderror
            <label>Email<input type="email" name="email" value="{{ old('email',$user->email) }}" required></label>@error('email')<small class="form-error">{{ $message }}</small>@enderror
            <button class="button-primary">Simpan profil</button>
        </form>
        <form method="POST" action="{{ route('member.password.update') }}" class="member-panel">@csrf @method('PUT')
            <h2>Ganti kata sandi</h2><p>Gunakan minimal 12 karakter dengan huruf, angka, dan simbol.</p>
            <label>Kata sandi saat ini<input type="password" name="current_password" required autocomplete="current-password"></label>@error('current_password')<small class="form-error">{{ $message }}</small>@enderror
            <label>Kata sandi baru<input type="password" name="password" required autocomplete="new-password"></label>@error('password')<small class="form-error">{{ $message }}</small>@enderror
            <label>Konfirmasi kata sandi<input type="password" name="password_confirmation" required autocomplete="new-password"></label>
            <button class="button-primary">Perbarui kata sandi</button>
        </form>
    </div>
    <form method="POST" action="{{ route('member.account.destroy') }}" class="member-danger" onsubmit="return confirm('Hapus akun dan seluruh data pribadi Anda?')">@csrf @method('DELETE')
        <div><h2>Hapus akun</h2><p>Tindakan ini menghapus profil, favorit, riwayat, bookmark, dan koleksi pribadi.</p></div>
        <label><span>Kata sandi saat ini</span><input type="password" name="password" required autocomplete="current-password"></label>
        <button>Hapus akun</button>
    </form>
</x-layouts.member>
