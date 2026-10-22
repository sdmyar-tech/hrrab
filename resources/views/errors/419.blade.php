@extends('errors.layout')
@section('kode', '419')
@section('ikon', 'bi-hourglass-split')
@section('judul', 'Sesi halaman sudah kedaluwarsa')
@section('pesan', 'Halaman terlalu lama dibiarkan terbuka sehingga formulir tidak bisa dikirim demi keamanan. Muat ulang halaman, lalu isi dan kirim kembali.')
@section('aksi')
    <button type="button" class="tombol utama" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/beranda') }}')"><i class="bi bi-arrow-clockwise"></i>Kembali & Muat Ulang</button>
    <a href="{{ url('/beranda') }}" class="tombol"><i class="bi bi-house-door"></i>Ke Beranda</a>
@endsection
@section('catatan', 'Tip: setelah kembali, tekan F5 sebelum mengirim ulang formulir. Data yang sudah diketik mungkin perlu diisi ulang.')