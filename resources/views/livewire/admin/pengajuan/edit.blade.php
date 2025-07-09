<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\{Layout, Title, Rule};
use App\Models\Pengajuan;
use App\Models\Bidang;
use Illuminate\Support\Facades\Storage;
use Mary\Traits\Toast;
use Illuminate\Support\Str;

new
    #[Layout('components.layouts.app')]
    #[Title('Edit Pengajuan')]
    class extends Component {

    use WithFileUploads, Toast;

    public Pengajuan $pengajuan;

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

    // File uploads dibuat opsional saat update
    #[Rule('nullable|file|mimes:pdf|max:2048')]
    public $surat_pengantar_path;

    #[Rule('nullable|file|mimes:pdf|max:2048')]
    public $cv_path;

    #[Rule('required|string|in:review,diterima,ditolak,berlangsung,selesai')]
    public string $status = '';

    /**
     * Dijalankan saat komponen dimuat. Mengisi form dengan data yang ada.
     */
    public function mount(Pengajuan $pengajuan): void
    {
        $this->pengajuan = $pengajuan;

        $this->nama = $pengajuan->nama;
        $this->nim_nis = $pengajuan->nim_nis;
        $this->no_hp = $pengajuan->no_hp;
        $this->email = $pengajuan->email;
        $this->sekolah_universitas = $pengajuan->sekolah_universitas;
        $this->jurusan_prodi = $pengajuan->jurusan_prodi;
        $this->bidang_id = $pengajuan->bidang_id;
        $this->no_surat_pengantar = $pengajuan->no_surat_pengantar;
        $this->status = $pengajuan->status;

        // Secara eksplisit parsing tanggal menjadi objek Carbon sebelum diformat.
        $this->tanggal_mulai = ($pengajuan->tanggal_mulai)->format('Y-m-d');
        $this->tanggal_selesai = ($pengajuan->tanggal_selesai)->format('Y-m-d');
        $this->tanggal_surat_pengantar = ($pengajuan->tanggal_surat_pengantar)->format('Y-m-d');
    }

    /**
     * Memperbarui data pengajuan.
     */
    public function update()
    {
        $validated = $this->validate();

        // Validasi kuota bidang jika status diubah menjadi diterima atau berlangsung
        if (in_array($validated['status'], ['diterima', 'berlangsung'])) {
            $sisaKuota = $this->pengajuan->bidang->kuota - Pengajuan::where('bidang_id', $this->pengajuan->bidang_id)
                ->whereIn('status', ['diterima', 'berlangsung'])
                ->count();
            if ($sisaKuota <= 0) {
                $this->error(
                    'Kuota bidang ini sudah HABIS. Tidak dapat mengubah status pengajuan ke Diterima atau Berlangsung.'
                );
                // Kembalikan status ke nilai awal sebelum validasi
                $this->status = $this->pengajuan->status;
                return;
            }
        }

        // 1. Update data teks terlebih dahulu, dengan mengecualikan field file.
        $this->pengajuan->update(
            Arr::except($validated, ['surat_pengantar_path', 'cv_path'])
        );

        // 2. Proses file surat pengantar HANYA jika ada file baru.
        if ($this->surat_pengantar_path) {
            Storage::disk('public')->delete($this->pengajuan->surat_pengantar_path);
            $sluggedName = Str::slug($this->nama);
            $timestamp = now()->format('dmY-siH');
            $extension = $this->surat_pengantar_path->getClientOriginalExtension();
            $newFileName = "surat-pengantar-{$sluggedName}-{$timestamp}.{$extension}";

            // Update path file langsung ke model
            $this->pengajuan->surat_pengantar_path = $this->surat_pengantar_path->storeAs('surat_pengantar', $newFileName, 'public');
        }

        // 3. Proses file CV HANYA jika ada file baru.
        if ($this->cv_path) {
            Storage::disk('public')->delete($this->pengajuan->cv_path);
            $sluggedName = Str::slug($this->nama);
            $timestamp = now()->format('dmY-siH');
            $extension = $this->cv_path->getClientOriginalExtension();
            $newFileName = "cv-{$sluggedName}-{$timestamp}.{$extension}";

            // Update path file langsung ke model
            $this->pengajuan->cv_path = $this->cv_path->storeAs('cv', $newFileName, 'public');
        }

        // 4. Simpan perubahan pada model (terutama untuk path file jika ada)
        $this->pengajuan->save();

        $this->toast(
            type: 'success',
            title: 'Pengajuan Diperbarui!',
            description: 'Data pengajuan telah berhasil diperbarui.',
            position: 'toast-top toast-end',
            icon: 'o-check-circle',
            timeout: 3000
        );

        return $this->redirect(route('admin.pengajuan.index'), navigate: true);
    }

    /**
     * Mengambil data yang dibutuhkan oleh view.
     */
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

        $statusOptions = collect(['review', 'ditolak', 'diterima', 'berlangsung', 'selesai'])
            ->map(function ($status) {
                return ['id' => $status, 'name' => Str::title($status)];
            });

        return [
            'bidangOptions' => $bidangOptions,
            'statusOptions' => $statusOptions,
        ];
    }
}; ?>

<div class="flex flex-col gap-4 w-full">
    <x-mary-header class="mb-1!" title="Edit Pengajuan"
        subtitle="Perbarui detail pengajuan magang untuk {{ $pengajuan->nama }}" separator />

    <x-mary-form wire:submit="update">
        <div class="space-y-8">
            {{-- SEKSI DATA DIRI & AKADEMIK --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <x-mary-input label="Nomor Surat Pengantar" placeholder="Contoh: 933/AKD11/KP-WD1/2025"
                    wire:model="no_surat_pengantar" class="bg-transparent dark:bg-zinc-900 rounded-md" />
                <x-mary-datepicker label="Tanggal Surat Pengantar" wire:model="tanggal_surat_pengantar"
                    icon="o-calendar" class="bg-transparent dark:bg-zinc-900 rounded-md" />

                <div class="md:col-span-2">
                    <x-mary-file label="Surat Pengantar (Opsional)" wire:model="surat_pengantar_path"
                        hint="Unggah file baru untuk mengganti yang lama" accept="application/pdf"
                        class="bg-transparent dark:bg-zinc-900 rounded-md" />
                    <a href="{{ asset('storage/' . $pengajuan->surat_pengantar_path) }}" target="_blank"
                        class="text-sm text-blue-500 hover:underline mt-2 inline-block">Lihat Surat Pengantar Saat
                        Ini</a>
                </div>

                <div class="md:col-span-2">
                    <x-mary-file label="Curriculum Vitae (CV) (Opsional)" wire:model="cv_path"
                        hint="Unggah file baru untuk mengganti yang lama" accept="application/pdf" class="rounded-md" />
                    <a href="{{ asset('storage/' . $pengajuan->cv_path) }}" target="_blank"
                        class="text-sm text-blue-500 hover:underline mt-2 inline-block">Lihat CV Saat Ini</a>
                </div>

                <div class="md:col-span-2">
                    <x-mary-select label="Status" placeholder="Pilih Status Magang"
                        hint="Ketika sisa kuota bidang HABIS, maka status pengajuan tidak bisa diubah ke 'Diterima' atau 'Berlangsung'"
                        :options="$statusCollection" wire:model="status" :options="$statusOptions" wire:model="status"
                        class="bg-transparent dark:bg-zinc-900 rounded-md" />
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <x-slot:actions>
            <x-mary-button label="Batal" link="{{ route('admin.pengajuan.index') }}"
                class="btn-primary dark:btn-neutral rounded-lg" wire:navigate />
            <x-mary-button label="Simpan Perubahan" icon="o-document-check" spinner="update" type="submit"
                class="btn-primary dark:btn-neutral rounded-lg" />
        </x-slot:actions>
    </x-mary-form>
</div>