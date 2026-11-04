@extends('layouts.admin.app')

@section('title', 'Tambah Akun')

@section('content')
{{-- Galat isian ditampilkan di bawah isiannya + ringkasan di atas form; banner global tidak perlu. --}}
@section('galat-di-halaman', '1')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Tambah Akun" eyebrow="Sistem"
            subtitle="Buat akun untuk masuk ke HRRAB. Pilih dari Data Pegawai agar nama, email, dan NIP terisi dan akun langsung tertaut."
            :back="route('admin.users.index')" back-label="Kelola Akun" />

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-4">
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <form data-fokus-awal action="{{ route('admin.users.store') }}" method="POST" data-confirm="Tambah akun baru ini?">
            @csrf
            <div class="row g-4">
                <div class="col-xl-8">
                    <x-card>
                        @include('admin.users._akun', ['akun' => null, 'wajibPassword' => true, 'selectedDivisi' => old('divisi', []), 'selectedKepalaUnit' => old('kepala_unit')])
                    </x-card>
                </div>
                <div class="col-xl-4">
                    <div class="hr-detail-samping d-grid gap-4">
                        @include('admin.users._ringkasan', ['akun' => null])
                    </div>
                </div>
            </div>

            <div class="hr-bilah-aksi mt-4">
                <x-button variant="secondary" :href="route('admin.users.index')">Batal</x-button>
                <x-button type="submit" icon="bi-check-lg">Simpan Akun</x-button>
            </div>
        </form>
    </div>
</main>
@endsection
