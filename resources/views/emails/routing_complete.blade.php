@component('mail::message')
# Upload Dokumen Routing Selesai

Halo,

Ini adalah notifikasi otomatis untuk memberitahukan bahwa semua material dari dokumen routing berikut telah berhasil diunggah ke SAP.

**Nama Dokumen:** {{ $documentDetails['document_name'] }},
**Nama Produk:** {{ $documentDetails['product_name'] }},
**Nomor Dokumen:** {{ $documentDetails['document_number'] }},
**Total Material:** {{ count($documentDetails['items']) }},
**Waktu Selesai:** {{ $documentDetails['completion_time'] }};

Berikut adalah daftar material yang berhasil diunggah dalam dokumen ini dan siap untuk di proses lebih lanjut:

@component('mail::table')
| Material | Deskripsi |
| :------------- | :------------- |
@foreach($documentDetails['items'] as $item)
| {{ $item['material'] }} | {{ $item['description'] }} |
@endforeach
@endcomponent

@component('mail::button', ['url' => 'http://material-master.kmifilebox.com/routing'])
Buka Halaman Routing
@endcomponent

Terima kasih.
<br>
{{ config('app.name') }}
@endcomponent
