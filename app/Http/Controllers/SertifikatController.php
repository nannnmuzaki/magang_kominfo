<?php

namespace App\Http\Controllers;

use App\Models\Pengajuan;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;


class SertifikatController extends Controller
{
    public function download(Pengajuan $pengajuan)
    {
        $slugNama = Str::slug($pengajuan->nama);

        return Pdf::view('pdf.template-sertifikat', ['pengajuan' => $pengajuan])
            ->format('a4')
            ->orientation('landscape')
            ->download("sertifikat-magang-{$slugNama}.pdf");
    }
}

