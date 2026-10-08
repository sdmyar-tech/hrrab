@extends('errors.layout')
@section('kode', '503')
@section('ikon', 'bi-cone-striped')
@section('judul', 'Sistem sedang dalam pemeliharaan')
@section('pesan', 'HRRAB sedang diperbarui agar lebih baik. Silakan kembali beberapa saat lagi.')
@section('aksi')
    <button type="button" class="tombol utama" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i>Muat Ulang</button>
@endsection
@section('catatan', 'Terima kasih atas kesabaran Anda.')