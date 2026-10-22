@extends('errors.layout')
@section('kode', isset($exception) && method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : '5xx')
@section('ikon', 'bi-tools')
@section('judul', 'Layanan sedang bermasalah')
@section('pesan', 'Server tidak dapat menyelesaikan permintaan saat ini. Silakan coba lagi beberapa saat lagi.')
@section('aksi')
    <button type="button" class="tombol utama" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i>Coba Lagi</button>
    <a href="{{ url('/beranda') }}" class="tombol"><i class="bi bi-house-door"></i>Ke Beranda</a>
@endsection