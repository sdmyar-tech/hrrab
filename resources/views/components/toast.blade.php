{{--
    Toast di pojok kanan bawah, memakai sistem toast global (SweetAlert, layouts.partials.sweetalert) agar satu gaya.
    <x-toast type="success" message="Data tersimpan." />
    Pesan dari controller (->with('success', ...)) sudah otomatis tampil; komponen ini untuk pesan dari view.
    Dari JavaScript: window.hrToast('success', 'Pesan').
--}}
@props([
    'type' => 'success',
    'message',
])
<script>
    (function () {
        const tampil = () => window.hrToast && window.hrToast(@json($type), @json($message));
        window.hrToast ? tampil() : document.addEventListener('hr:toast-siap', tampil, { once: true });
    })();
</script>
