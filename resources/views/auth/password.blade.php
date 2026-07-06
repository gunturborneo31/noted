<x-layouts.app title="Ubah Password">
    <div class="max-w-xl mx-auto neo-card p-5 md:p-6">
        <p class="text-xs font-black uppercase tracking-widest text-lime-700">Security</p>
        <h1 class="text-2xl font-black mt-1 mb-5">Ubah Password</h1>

        @if ($errors->any())
            <div class="mb-4 border-4 border-black bg-red-100 px-3 py-2 font-bold text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf

            <div>
                <label for="current_password" class="neo-label">Password Saat Ini</label>
                <input id="current_password" name="current_password" type="password" required
                       class="neo-input w-full" placeholder="Masukkan password saat ini" />
            </div>

            <div>
                <label for="password" class="neo-label">Password Baru</label>
                <input id="password" name="password" type="password" required minlength="8"
                       class="neo-input w-full" placeholder="Minimal 8 karakter" />
            </div>

            <div>
                <label for="password_confirmation" class="neo-label">Konfirmasi Password Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                       class="neo-input w-full" placeholder="Ulangi password baru" />
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="neo-btn bg-black text-lime-400">Simpan Password</button>
                <a href="{{ route('dashboard') }}" class="neo-btn bg-white text-black">Batal</a>
            </div>
        </form>
    </div>
</x-layouts.app>
