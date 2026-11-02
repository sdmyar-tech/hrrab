{{--
    Baris kosong tabel (Batch 4): x-empty-state di dalam satu sel selebar tabel.
    <x-table-empty :colspan="6" icon="bi-people" title="Belum ada pegawai">
        Tambahkan pegawai pertama untuk mulai mengelola data.
        <x-slot:action><x-button :href="…" icon="bi-plus-lg">Tambah Pegawai</x-button></x-slot:action>
    </x-table-empty>
    Untuk saring di sisi klien: tambahkan data-saring-kosong="#idDaftar" hidden (tampil otomatis bila tidak ada yang cocok).
--}}
@props([
    'colspan',
    'icon' => 'bi-inbox',
    'title' => null,
])
<tr {{ $attributes->merge(['class' => 'hr-table-kosong']) }}>
    <td colspan="{{ $colspan }}">
        <x-empty-state :icon="$icon" :title="$title">
            {{ $slot }}
            @isset($action)
                <x-slot:action>{{ $action }}</x-slot:action>
            @endisset
        </x-empty-state>
    </td>
</tr>
