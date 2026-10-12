<div class="alert alert-warning d-flex gap-2 align-items-start small">
    <i class="bi bi-exclamation-triangle-fill fs-5 lh-1"></i>
    <div>
        <strong>Mode Admin — perubahan langsung ke database.</strong>
        Aturan alur (tahap, tanggal minimal, status final) dan notifikasi <u>tidak</u> dijalankan.
        Data terkait tidak ikut disesuaikan otomatis (mis. mengubah hasil interview tidak mengubah tahap lamaran).
        Setiap perubahan tercatat di <a href="{{ route('admin.log.index') }}" class="alert-link">Log Aktivitas</a>.
    </div>
</div>
