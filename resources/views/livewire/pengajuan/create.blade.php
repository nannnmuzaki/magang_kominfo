<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title, Rule};
use App\Models\Pengajuan;
use App\Models\Bidang;
use Illuminate\Support\Facades\Auth;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new
    #[Layout('components.layouts.app')]
    #[Title('Buat Pengajuan')]
    class extends Component {

    use WithFileUploads, Toast;

    // Properti untuk data diri & akademik
    #[Rule('required|string|max:255')]
    public string $nama = '';

    #[Rule('required|string|max:255')]
    public string $nim_nis = '';

    #[Rule('required|string|max:255')]
    public string $no_hp = '';

    #[Rule('required|email|max:255')]
    public string $email = '';

    #[Rule('required|string|max:255')]
    public string $sekolah_universitas = '';

    #[Rule('required|string|max:255')]
    public string $jurusan_prodi = '';

    // Properti untuk detail magang
    #[Rule('required|uuid|exists:bidang,id')]
    public string $bidang_id = '';

    #[Rule('required|date')]
    public string $tanggal_mulai = '';

    #[Rule('required|date|after:tanggal_mulai')]
    public string $tanggal_selesai = '';

    // Properti untuk dokumen
    #[Rule('required|string|max:255')]
    public string $no_surat_pengantar = '';

    #[Rule('required|date')]
    public string $tanggal_surat_pengantar = '';

    #[Rule('required|file|mimes:pdf|max:2048')] // Maks 2MB
    public $surat_pengantar_path;

    #[Rule('required|file|mimes:pdf|max:2048')] // Maks 2MB
    public $cv_path;

    #[Rule('required|string|in:review,diterima,ditolak,berlangsung,selesai')]
    public string $status = '';


    public function store()
    {
        $validated = $this->validate();
        $validated['user_id'] = Auth::id();

        // Buat nama file baru untuk CV dan Surat Pengantar yang bersih dan unik
        $sluggedName = Str::slug($this->nama);
        $timestamp = now()->format('dmY-siH');

        $suratExtension = $this->surat_pengantar_path->getClientOriginalExtension();
        $newSuratName = "surat-pengantar-{$sluggedName}-{$timestamp}.{$suratExtension}";

        $cvExtension = $this->cv_path->getClientOriginalExtension();
        $newCvName = "cv-{$sluggedName}-{$timestamp}.{$cvExtension}";

        // Simpan file dan masukkan path-nya ke array validated
        $validated['surat_pengantar_path'] = $this->surat_pengantar_path->storeAs('surat_pengantar', $newSuratName, 'public');
        $validated['cv_path'] = $this->cv_path->storeAs('cv', $newCvName, 'public');

        // Simpan pengajuan baru
        $pengajuan = Pengajuan::create([
            'user_id' => $validated['user_id'],
            'nama' => $validated['nama'],
            'nim_nis' => $validated['nim_nis'],
            'no_hp' => $validated['no_hp'],
            'email' => $validated['email'],
            'sekolah_universitas' => $validated['sekolah_universitas'],
            'jurusan_prodi' => $validated['jurusan_prodi'],
            'bidang_id' => $validated['bidang_id'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'no_surat_pengantar' => $validated['no_surat_pengantar'],
            'tanggal_surat_pengantar' => $validated['tanggal_surat_pengantar'],
            'status' => $validated['status'],
            'surat_pengantar_path' => $validated['surat_pengantar_path'],
            'cv_path' => $validated['cv_path'],
        ]);

        // Tampilkan notifikasi sukses
        $this->toast(
            type: 'success',
            title: 'Pengajuan Terkirim!',
            description: 'Data Anda telah berhasil dikirim dan akan segera kami proses.',
            position: 'toast-top toast-end',
            icon: 'o-paper-airplane',
            timeout: 3000
        );

        // Redirect ke halaman pengajuan
        return $this->redirect(route('pengajuan.index'), navigate: true);
    }

    public function with(): array
    {
        $bidangOptions = Bidang::query()
            ->withCount([
                'pengajuan as pengajuan_diterima_berlangsung_count' => function ($query) {
                    $query->whereIn('status', ['diterima', 'berlangsung']);
                }
            ])
            ->orderBy('nama', 'asc')
            ->get()
            ->map(function ($bidang) {
                return [
                    'id' => $bidang->id,
                    'name' => "{$bidang->nama} (Kuota: {$bidang->kuota}, Sisa: {$bidang->sisa_kuota})"
                ];
            })
            ->all();

        $statusOptions = ['review', 'ditolak', 'diterima', 'berlangsung', 'selesai'];
        $statusCollection = collect($statusOptions)->map(function ($status) {
            return [
                'id' => $status,
                'name' => Str::title($status)
            ];
        });
        return [
            'bidangOptions' => $bidangOptions,
            'statusCollection' => $statusCollection,
        ];
    }

}; ?>

<div class="flex flex-col gap-4 w-full">
    <x-mary-header class="mb-1!" title="Buat Pengajuan" subtitle="Buat pengajuan magang baru di Kominfo Banyumas"
        separator />
    <x-mary-form wire:submit="store">
        <div>
            {{-- SEKSI DATA DIRI & AKADEMIK --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <x-mary-input label="Nama Lengkap" placeholder="Nama lengkap anda..." wire:model="nama"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-input label="NIM / NIS" placeholder="NIM/NIS anda..." wire:model="nim_nis"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-input label="Nomor Handphone" wire:model="no_hp"
                    placeholder="Isi dengan nomor WA aktif dan bisa dihubungi"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-input label="Alamat Email" placeholder="Isi dengan email yang valid dan bisa dihubungi"
                    wire:model="email" type="email" class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-input label="Sekolah / Universitas"
                    placeholder="Nama lengkap sekolah / perguruan tinggi anda..." wire:model="sekolah_universitas"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-input label="Jurusan / Program Studi" placeholder="Jurusan / Program studi anda..."
                    wire:model="jurusan_prodi" class="bg-transparent dark:bg-zinc-900 rounded-md" />
            </div>

            <hr class="border-t-zinc-200 dark:border-t-zinc-700 mt-10 mb-6" />

            {{-- SEKSI DETAIL MAGANG --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <div class="md:col-span-2">
                    <x-mary-select label="Bidang yang diminati" placeholder="Pilih Bidang..." wire:model="bidang_id"
                        :options="$bidangOptions" class="bg-transparent dark:bg-zinc-900 rounded-md" />
                </div>
                <x-mary-datepicker label="Tanggal Mulai" wire:model="tanggal_mulai" icon="o-calendar"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-datepicker label="Tanggal Selesai" wire:model="tanggal_selesai" icon="o-calendar"
                    class="bg-transparent dark:bg-zinc-900 rounded-md" />
            </div>

            <hr class="border-t-zinc-200 dark:border-t-zinc-700 mt-10 mb-6" />

            {{-- SEKSI DOKUMEN --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                <x-mary-input label="Nomor Surat Pengantar" placeholder="Contoh: 933/AKD11/KP-WD1/2025"
                    wire:model="no_surat_pengantar" class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-datepicker label="Tanggal Surat Pengantar" wire:model="tanggal_surat_pengantar"
                    icon="o-calendar" class="bg-transparent dark:bg-zinc-900 rounded-md" />

                <div class="md:col-span-2">
                    <x-mary-file label="Surat Pengantar" wire:model="surat_pengantar_path" hint="Hanya PDF, Maks 2MB"
                        accept="application/pdf" class="bg-transparent dark:bg-zinc-900 rounded-md" />
                </div>

                <div class="md:col-span-2">
                    <x-mary-file label="Curriculum Vitae (CV)" wire:model="cv_path" hint="Hanya PDF, Maks 2MB"
                        accept="application/pdf" class="rounded-md" />
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <x-slot:actions>
            <x-mary-button label="Batal" link="{{ route('pengajuan.index') }}"
                class="btn-primary dark:btn-neutral rounded-lg" wire:navigate />
            <x-mary-button label="Kirim Pengajuan" icon="o-document-plus" spinner="store" type="submit"
                class="btn-primary dark:btn-neutral rounded-lg" />
        </x-slot:actions>
    </x-mary-form>
</div>