@extends('preview.base')
@section('title', 'Masuk')
@section('body')
<main id="main" class="grid h-dvh grid-cols-1 overflow-hidden bg-slate-100 lg:grid-cols-2">

    {{-- Panel kiri: logo besar --}}
    <section class="hidden h-full min-h-0 flex-col bg-gradient-to-br from-blue-700 via-blue-700 to-blue-900 p-8 text-white lg:flex xl:p-12">
        <a href="{{ route('login') }}" class="inline-flex w-fit items-center gap-2 text-xl font-bold tracking-tight">
            <x-icon name="book" class="h-6 w-6"/>SIPKL
        </a>

        <div class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 text-center">
            <div class="rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-white/30 xl:p-8">
                <img src="{{ asset('assets/logo-smk.png') }}" alt="Logo SMK TI Bali Global Badung"
                     class="h-56 w-auto max-w-full object-contain xl:h-72">
            </div>
        </div>

        <p class="text-center text-xs text-blue-100">Sistem Informasi Praktik Kerja Lapangan &middot; SMK TI Bali Global Badung</p>
    </section>

    {{-- Panel kanan: form --}}
    <section class="relative h-full min-h-0">
        <button type="button" data-dialog="about-dialog"
                class="absolute right-4 top-4 z-10 rounded-md px-2 py-1 text-xs font-medium text-blue-700 underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 sm:right-8 sm:top-6 sm:text-sm">
            Tentang pratinjau
        </button>

        <div class="h-full overflow-y-auto">
            <div class="flex min-h-full items-center justify-center px-4 pb-6 pt-14 sm:px-8 sm:pb-10 sm:pt-16">
                <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-lg sm:p-7">

                    {{-- Brand mobile --}}
                    <div class="mb-4 flex items-center gap-3 lg:hidden">
                        <img src="{{ asset('assets/logo-smk.png') }}" alt="Logo SMK TI Bali Global Badung" class="h-14 w-auto">
                        <span class="inline-flex items-center gap-1.5 text-lg font-bold text-blue-700"><x-icon name="book" class="h-5 w-5"/>SIPKL</span>
                    </div>

                    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Masuk ke SIPKL</h1>
                    <p class="mt-0.5 text-sm text-slate-600">Gunakan email akun sekolahmu.</p>

                    @if(session('success'))
                        <div class="mt-3"><x-alert>{{ session('success') }}</x-alert></div>
                    @endif

                    <form id="login-form" method="POST" action="{{ route('login.store') }}" class="mt-5">
                        @csrf

                        <div>
                            <label for="email" class="mb-1 block text-sm font-medium text-slate-900">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="nama@sekolah.sch.id"
                                   class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30">
                            @error('email')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-3">
                            <label for="password" class="mb-1 block text-sm font-medium text-slate-900">Kata sandi</label>
                            <div class="flex gap-2">
                                <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi"
                                       class="block w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600/30">
                                <button type="button" id="toggle-password"
                                        class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    Tampilkan
                                </button>
                            </div>
                            @error('password')<p role="alert" class="mt-1 text-xs text-red-700">{{ $message }}</p>@enderror
                        </div>

                        <x-button type="submit" class="mt-5 w-full">Masuk</x-button>
                    </form>

                    <p class="mt-3 text-center text-xs text-slate-500">Butuh bantuan? Hubungi Admin sekolah melalui kanal resmi.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const pw = document.getElementById('password');
    const toggle = document.getElementById('toggle-password');
    toggle.addEventListener('click', () => {
        const show = pw.type === 'password';
        pw.type = show ? 'text' : 'password';
        toggle.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    });

})();
</script>
@endsection