{{--
    Tombol "Ubah/Hapus (Admin)" ke Editor Data untuk satu baris. Hanya tampil untuk Admin.
    Parameter: $entitas, $id, opsional $teks (label tombol ubah), $hapus (bool, default true), $kembaliKe (URL setelah hapus).
--}}
@if (auth()->user()?->isAdmin())
    <span class="d-inline-flex gap-1 align-items-center tombol-admin-data">
        <a href="{{ route('admin.data.edit', [$entitas, $id, 'kembali' => request()->fullUrl()]) }}" class="btn btn-outline-warning btn-sm"
            title="Ubah langsung semua kolom (Admin)"><i class="bi bi-shield-lock me-1"></i>{{ $teks ?? 'Ubah (Admin)' }}</a>
        @if ($hapus ?? true)
            <form action="{{ route('admin.data.destroy', [$entitas, $id]) }}" method="POST" class="d-inline"
                data-confirm="Hapus permanen data ini? Tindakan ini tidak bisa dibatalkan.">
                @csrf @method('DELETE')
                <input type="hidden" name="kembali" value="{{ $kembaliKe ?? request()->fullUrl() }}">
                <button class="btn btn-outline-danger btn-sm" title="Hapus permanen (Admin)"><i class="bi bi-trash me-1"></i>Hapus</button>
            </form>
        @endif
    </span>
@endif
