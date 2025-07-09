<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Magang</title>
    <style>
        @page {
            margin: 0;
            size: A4 landscape;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        .certificate-container {
            width: 29.7cm;
            height: 21cm;
            position: relative;
            padding: 2cm;
            box-sizing: border-box;
            background: white;
            border: 10px solid #1e3a8a;
            /* Warna biru tua */
        }

        .inner-border {
            border: 2px solid #facc15;
            /* Warna emas */
            width: calc(100% - 1cm);
            height: calc(100% - 1cm);
            position: absolute;
            top: 0.5cm;
            left: 0.5cm;
        }

        .content {
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .kop-surat {
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 3px double black;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: auto;
            margin-right: 20px;
        }

        .kop-teks {
            text-align: center;
        }

        .kop-teks h2,
        .kop-teks h3 {
            margin: 0;
        }

        h1 {
            font-size: 48px;
            color: #1e3a8a;
            margin-top: 40px;
            margin-bottom: 20px;
            font-family: 'Garamond', serif;
        }

        .diberikan-kepada {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .nama-peserta {
            font-size: 36px;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 2px solid #facc15;
            display: inline-block;
            padding-bottom: 5px;
            margin-bottom: 20px;
        }

        .deskripsi {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .tanda-tangan-container {
            margin-top: 60px;
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
        }

        .tanda-tangan {
            text-align: center;
            width: 40%;
        }

        .tanda-tangan .nama {
            font-weight: bold;
            border-bottom: 1px solid black;
            padding-bottom: 2px;
            margin-top: 70px;
            /* Ruang untuk tanda tangan */
        }

        .tanda-tangan .jabatan {
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="certificate-container">
        <div class="inner-border"></div>
        <div class="content">
            {{-- Anda bisa menambahkan kop surat di sini jika perlu --}}

            <h1>SERTIFIKAT</h1>
            <p class="diberikan-kepada">Diberikan kepada:</p>
            <p class="nama-peserta">{{ $pengajuan->nama }}</p>

            <p class="deskripsi">
                Telah berhasil menyelesaikan program Praktik Kerja Lapangan (Magang)
                di Dinas Komunikasi dan Informatika Kabupaten Banyumas pada bidang
                <strong>{{ $pengajuan->bidang->nama }}</strong>
                yang dilaksanakan pada tanggal
                <strong>{{ $pengajuan->tanggal_mulai->translatedFormat('d F Y') }}</strong>
                sampai dengan <strong>{{ $pengajuan->tanggal_selesai->translatedFormat('d F Y') }}</strong>.
            </p>

            <div class="tanda-tangan-container">
                <div class="tanda-tangan">
                    <p>Kepala Bidang E-Government</p>
                    <div class="nama">Nama Pejabat</div>
                    <div class="jabatan">NIP. XXXXXXXXXXXXXX</div>
                </div>
                <div class="tanda-tangan">
                    <p>Purwokerto, {{ now()->translatedFormat('d F Y') }}</p>
                    <p>Kepala Dinas Kominfo</p>
                    <div class="nama">Nama Kepala Dinas</div>
                    <div class="jabatan">NIP. YYYYYYYYYYYYYY</div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>