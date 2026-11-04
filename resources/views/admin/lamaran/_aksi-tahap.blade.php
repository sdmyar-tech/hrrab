{{--
    Tombol tindakan tahap lamaran saat ini (Batch 6). Dipakai dua kali di admin.lamaran.show: kartu Tindakan (kolom kanan)
    dan bilah tindakan cepat di HP/tablet. Tombol TIDAK membawa form sendiri — semuanya menunjuk form/dialog yang sudah ada:
      form   : id form yang dikirim (atribut form=, mis. form-verifikasi-terima) — konfirmasi data-confirm milik form tetap berlaku
      alasan : jenis dialog alasan (tolak) untuk form ber-input catatan tersembunyi
      modal  : id modal yang dibuka (#modalMulaiOnboarding, …)
      tab+ke : buka tab detail lalu gulir ke elemen ber-id itu (mis. form jadwal)
    Param: $aksiTahap (daftar tombol), $ukuran ('sm' | 'md').
--}}
@foreach ($aksiTahap as $a)
    <x-button :variant="$a['variant'] ?? 'primary'" :icon="$a['icon']" :size="$ukuran ?? 'md'"
        :type="$a['type'] ?? 'button'" :form="$a['form'] ?? null"
        :href="isset($a['tab']) ? '#' . $a['ke'] : null" :data-buka-tab="$a['tab'] ?? null"
        :data-bs-toggle="isset($a['modal']) ? 'modal' : null" :data-bs-target="$a['modal'] ?? null"
        :data-alasan="$a['alasan'] ?? null"
        :data-konteks="isset($a['konteks']) ? json_encode($a['konteks'], JSON_UNESCAPED_UNICODE) : null">{{ $a['label'] }}</x-button>
@endforeach
