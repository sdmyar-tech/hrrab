@extends('layouts.admin.app')

@section('title', 'Ubah Akun')

@section('content')
{{-- Galat isian ditampilkan di bawah isiannya + ringkasan di atas form; banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Ubah Akun" eyebrow="Sistem" :subtitle="$user->name . ' · ' . $user->email"
            :back="route('admin.users.index')" back-label="Kelola Akun">
            <x-badge tone="neutral" icon="bi-shield-check">{{ $user->roleLabel() }}</x-badge>
            @if ($user->wajib_ganti_password)<x-badge tone="warning" icon="bi-shield-exclamation">Wajib ganti kata sandi</x-badge>@endif
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form action="{{ route('admin.users.update', $user) }}" method="POST" data-confirm="Simpan perubahan akun ini?">
            @csrf
            @method('PUT')
            <div class="row g-4">
                <div class="col-xl-8">
                    <x-card>
                        @if ($user->dataPegawai || $user->dataPelamar)
                            <x-alert tone="info" class="mb-4">
                                Terhubung dengan {{ $user->dataPegawai ? 'pegawai' : 'pelamar' }}
                                <strong>{{ $user->dataPegawai?->nama_lengkap ?? $user->dataPelamar?->nama_lengkap }}</strong>.
                                Tautan dikelola otomatis lewat NIP & proses rekrutmen.
                            </x-alert>
                        @endif
                        @include('admin.users._akun', ['akun' => $user, 'wajibPassword' => false,
                            'selectedDivisi' => old('divisi', $user->divisi ?? []), 'selectedKepalaUnit' => old('kepala_unit', $user->unitsDipimpin())])
                    </x-card>
                </div>
                <div class="col-xl-4">
                    <div class="hr-detail-samping d-grid gap-4">
                        @include('admin.users._ringkasan', ['akun' => $user])
                    </div>
                </div>
            </div>

            <div class="hr-bilah-aksi mt-4">
                <x-button variant="secondary" :href="route('admin.users.index')">Batal</x-button>
                <x-button type="submit" icon="bi-check-lg">Simpan Perubahan</x-button>
            </div>
        </form>
    </div>
</main>
@endsection
