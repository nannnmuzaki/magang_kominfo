<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Livewire\WithPagination;
use App\Models\Pengajuan;
use App\Models\Bidang;
use Illuminate\Support\Str;
use Mary\Traits\Toast;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;

new
    #[Layout('components.layouts.app')]
    #[Title('Kelola Pengajuan')]
    class extends Component {

    use WithPagination, Toast;

    // Properti untuk filter dan pencarian
    public string $search = '';
    public int $perPage = 20;
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public string $selectedStatus = 'all';
    public string $selectedBidangId = 'all';
    public $selectedTahun = 'all';

    // Properti untuk detail pengajuan
    public ?Pengajuan $selectedPengajuan = null;
    public string $selectedPengajuanStatus = '';
    public bool $detailDrawer = false;

    // Properti untuk modal delete
    public bool $deleteModal = false;
    public ?string $pengajuanToDeleteId = null;
    public ?string $pengajuanToDeleteName = null;

    // Properti untuk modal generate surat balasan
    public bool $generateBalasanModal = false;
    #[Rule('required|string|max:255')]
    public string $nomorSuratBalasan = '';

    // Properti untuk modal generate surat selesai
    public bool $generateSelesaiModal = false;
    #[Rule('required|string|max:255')]
    public string $nomorSuratSelesai = '';

    /**
     * buka modal untuk mengenerate surat balasan.
     */
    public function startGenerateDocxBalasan()
    {
        if (!$this->selectedPengajuan) {
            $this->error('Silakan pilih pengajuan terlebih dahulu.');
            return;
        }

        // Tampilkan modal generate surat balasan
        $this->generateBalasanModal = true;
    }

    /**
     * Menghasilkan surat balasan.
     */
    public function generateDocxBalasan()
    {
        try {
            // Path ke template Word 
            $templatePath = storage_path('app/templates/template-surat-balasan.docx');
            if (!file_exists($templatePath)) {
                $this->error('Template surat tidak ditemukan.');
                return;
            }

            // 1. Load template
            $templateProcessor = new TemplateProcessor($templatePath);

            // 2. Ganti placeholder dengan data
            $templateProcessor->setValue('nomor_surat', $this->nomorSuratBalasan);
            $templateProcessor->setValue('nomor_surat_pengantar', $this->selectedPengajuan->no_surat_pengantar);
            $templateProcessor->setValue('nama', $this->selectedPengajuan->nama);
            $templateProcessor->setValue('nim_nis', $this->selectedPengajuan->nim_nis);
            $templateProcessor->setValue('sekolah_universitas', $this->selectedPengajuan->sekolah_universitas);
            $templateProcessor->setValue('jurusan_prodi', $this->selectedPengajuan->jurusan_prodi);
            $templateProcessor->setValue('bidang', $this->selectedPengajuan->bidang->nama);
            $templateProcessor->setValue('tanggal_surat_pengantar', $this->selectedPengajuan->tanggal_surat_pengantar->locale('id')->translatedFormat('d F Y'));
            $templateProcessor->setValue('tanggal_mulai', $this->selectedPengajuan->tanggal_mulai->locale('id')->translatedFormat('d F Y'));
            $templateProcessor->setValue('tanggal_selesai', $this->selectedPengajuan->tanggal_selesai->locale('id')->translatedFormat('d F Y'));
            $templateProcessor->setValue('tanggal_surat_dibuat', now()->locale('id')->translatedFormat('d F Y'));

            // 3. Simpan sebagai file Word sementara
            $slugNama = Str::slug($this->selectedPengajuan->nama);
            $docxPath = storage_path("app/temp/surat-balasan-{$slugNama}.docx");
            $templateProcessor->saveAs($docxPath);

            // 4. Kembalikan file .docx sebagai download dan hapus setelah dikirim
            return response()->download($docxPath)->deleteFileAfterSend(true);

            $this->generateBalasanModal = false; // Tutup modal

        } catch (\Exception $e) {
            // Tampilkan pesan error jika terjadi masalah
            $this->error('Gagal membuat Surat Balasan: ' . $e->getMessage());
        }
    }

    /**
     * buka modal untuk mengenerate surat balasan.
     */
    public function startGenerateDocxSelesai()
    {
        if (!$this->selectedPengajuan) {
            $this->error('Silakan pilih pengajuan terlebih dahulu.');
            return;
        }

        // Tampilkan modal generate surat balasan
        $this->generateSelesaiModal = true;
    }

    /**
     * Menghasilkan surat selesai.
     */
    public function generateDocxSelesai()
    {
        try {
            // Path ke template Word 
            $templatePath = storage_path('app/templates/template-surat-selesai.docx');
            if (!file_exists($templatePath)) {
                $this->error('Template surat tidak ditemukan.');
                return;
            }

            // 1. Load template
            $templateProcessor = new TemplateProcessor($templatePath);

            // 2. Ganti placeholder dengan data
            $templateProcessor->setValue('nomor_surat_selesai', $this->nomorSuratSelesai);
            $templateProcessor->setValue('nama', $this->selectedPengajuan->nama);
            $templateProcessor->setValue('nim_nis', $this->selectedPengajuan->nim_nis);
            $templateProcessor->setValue('jurusan_prodi', $this->selectedPengajuan->jurusan_prodi);
            $templateProcessor->setValue('tanggal_mulai', $this->selectedPengajuan->tanggal_mulai->locale('id')->translatedFormat('d F Y'));
            $templateProcessor->setValue('tanggal_selesai', $this->selectedPengajuan->tanggal_selesai->locale('id')->translatedFormat('d F Y'));
            $templateProcessor->setValue('tanggal_surat_dibuat', now()->locale('id')->translatedFormat('d F Y'));

            // 3. Simpan sebagai file Word sementara
            $slugNama = Str::slug($this->selectedPengajuan->nama);
            $docxPath = storage_path("app/temp/surat-selesai-{$slugNama}.docx");
            $templateProcessor->saveAs($docxPath);

            // 4. Kembalikan file .docx sebagai download dan hapus setelah dikirim
            return response()->download($docxPath)->deleteFileAfterSend(true);

            $this->generateSelesaiModal = false; // Tutup modal

        } catch (\Exception $e) {
            // Tampilkan pesan error jika terjadi masalah
            $this->error('Gagal membuat Surat Selesai: ' . $e->getMessage());
        }
    }

    public function pengajuanDetail($pengajuanId)
    {
        $this->detailDrawer = true;
        try {
            $this->selectedPengajuan = Pengajuan::with('bidang')->findOrFail($pengajuanId);
            $this->selectedPengajuanStatus = $this->selectedPengajuan->status;
        } catch (\Exception $e) {
            $this->error('Pengajuan tidak ditemukan atau terjadi kesalahan saat mengambil data.');
            return [];
        }
    }

    public function updateStatus()
    {
        if (!$this->selectedPengajuan) {
            $this->error('Tidak ada pengajuan yang dipilih.');
            return;
        }

        // Validasi status yang diberikan
        if (!in_array($this->selectedPengajuanStatus, ['review', 'ditolak', 'diterima', 'berlangsung', 'selesai'])) {
            $this->error('Status yang dipilih tidak valid.');
            return;
        } else {
            $this->selectedPengajuan->status = $this->selectedPengajuanStatus;
            $this->selectedPengajuan->save();
            $this->success('Status pengajuan berhasil diperbarui.');
        }
    }

    public function startDelete(string $id, string $name): void
    {
        $this->pengajuanToDeleteId = $id;
        $this->pengajuanToDeleteName = $name;
        $this->deleteModal = true;
    }

    /**
     * Menjalankan proses penghapusan setelah dikonfirmasi.
     */
    public function confirmDelete(): void
    {
        $pengajuan = Pengajuan::find($this->pengajuanToDeleteId);

        if (!$pengajuan) {
            $this->error('Pengajuan tidak ditemukan.');
            return;
        }

        // Metode delete akan otomatis memicu event 'deleting' di model
        // untuk menghapus file terkait.
        $pengajuan->delete();

        $this->detailDrawer = false; // Tutup drawer detail

        $this->success("Pengajuan dari {$this->pengajuanToDeleteName} telah dihapus.");
        $this->deleteModal = false; // Tutup modal
    }

    public function with(): array
    {
        $statusOptions = collect(['review', 'ditolak', 'diterima', 'berlangsung', 'selesai'])
            ->map(function ($status) {
                return ['id' => $status, 'name' => Str::title($status)];
            });

        $bidang = Bidang::query()
            ->select('id', 'nama', 'kuota')
            ->get();

        $availableTahun = Pengajuan::query()
            ->selectRaw('EXTRACT(YEAR FROM created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $pengajuanQuery = Pengajuan::query()
            ->with([
                'bidang' => function ($query) {
                    // Hanya ambil id dan nama dari tabel bidang
                    $query->select('id', 'nama');
                }
            ])
            ->select('id', 'nama', 'nim_nis', 'bidang_id', 'status', 'created_at')
            ->when($this->selectedTahun !== 'all', function ($query) {
                // Terapkan filter bidang jika $this->selectedBidangId tidak kosong
                $query->whereYear('created_at', $this->selectedTahun);
            })
            ->when($this->selectedBidangId !== 'all', function ($query) {
                // Terapkan filter bidang jika $this->selectedBidangId tidak kosong
                $query->where('bidang_id', $this->selectedBidangId);
            })
            ->when($this->selectedStatus !== 'all', function ($query) {
                // Terapkan filter status jika bukan 'all'
                $query->where('status', $this->selectedStatus);
            })
            ->when($this->search, function ($query) {
                // Terapkan filter pencarian jika $this->search tidak kosong
                $query->where('nama', 'ilike', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        return [
            'semuaPengajuan' => $pengajuanQuery->paginate($this->perPage),
            'semuaBidang' => $bidang,
            'availableTahun' => $availableTahun,
            'statusOptions' => $statusOptions,
        ];
    }

}; ?>

<div class="flex h-full w-full flex-col gap-4 rounded-xl">
    <x-mary-header class="mb-1!" title="Pengajuan" subtitle="Kelola pengajuan magang di Kominfo Banyumas" separator />

    <flux:button icon:trailing="plus" href="{{ route('admin.pengajuan.create') }}" class="mr-auto" wire:navigate>
        Buat Pengajuan
    </flux:button>

    <div class="flex max-sm:flex-col max-sm:items-start w-full mb-1 gap-2 items-center">
        <flux:input class:input="bg-transparent! dark:placeholder-white/90! dark:border-zinc-600 max-sm:w-full"
            wire:model.live.debounce.300ms="search" kbd="⌘K" icon="magnifying-glass" placeholder="Search..." />

        <div class="flex items-center gap-2 max-sm:flex-wrap">
            <flux:dropdown>
                <flux:button icon:trailing="chevron-down">Bidang</flux:button>

                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedBidangId" keep-open>
                        <flux:menu.radio value="all">Semua</flux:menu.radio>
                        @foreach ($semuaBidang as $bidang)
                            <flux:menu.radio value="{{ $bidang->id }}">{{ $bidang->nama }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>

            <flux:dropdown>
                <flux:button icon:trailing="chevron-down">Status</flux:button>

                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedStatus" keep-open>
                        <flux:menu.radio value="all">Semua</flux:menu.radio>
                        @foreach ($statusOptions as $status)
                            <flux:menu.radio value="{{ $status['id'] }}">{{ Str::Title($status['name']) }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>

            <flux:dropdown>
                <flux:button icon:trailing="chevron-down">Tahun</flux:button>

                <flux:menu>
                    <flux:menu.radio.group wire:model.live="selectedTahun" keep-open>
                        <flux:menu.radio value="all">Semua</flux:menu.radio>
                        @foreach ($availableTahun as $tahun)
                            <flux:menu.radio value="{{ $tahun }}">{{ $tahun }}</flux:menu.radio>
                        @endforeach
                    </flux:menu.radio.group>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Definisikan header tabel dengan kunci yang benar --}}
    @php
        $headers = [
            ['key' => 'nama', 'label' => 'Nama'],
            ['key' => 'nim_nis', 'label' => 'NIM/NIS', 'sortable' => false], // NIM/NIS tidak perlu sortable
            ['key' => 'bidang.nama', 'label' => 'Bidang', 'sortable' => false], // Gunakan dot notation untuk relasi
            ['key' => 'status', 'label' => 'Status', 'sortable' => false], // Status tidak perlu sortable
            ['key' => 'created_at', 'label' => 'Tanggal Pengajuan'],
            ['key' => 'detail', 'label' => 'Detail Pengajuan', 'sortable' => false],
        ];
    @endphp

    {{-- Render tabel dengan data dan header yang sudah disiapkan --}}
    <x-mary-table class="font-medium" :headers="$headers" :rows="$semuaPengajuan" :sort-by="$sortBy" with-pagination
        per-page="perPage" :per-page-values="[20, 30, 50]">

        @scope('cell_bidang.nama', $pengajuan)
        {{-- Akses nama bidang langsung dari relasi yang sudah di-load --}}
        <span class="font-medium">{{ $pengajuan->bidang->nama ?? 'N/A' }}</span>
        @endscope

        @scope('cell_status', $pengajuan)
        @php
            $color = match ($pengajuan->status) {
                'review' => 'zinc',
                'diterima' => 'lime',
                'ditolak' => 'red',
                'berlangsung' => 'blue',
                'selesai' => 'fuchsia',
            };
        @endphp

        <flux:badge class="font-semibold" color="{{ $color }}">{{ Str::Title($pengajuan->status) }}</flux:badge>
        @endscope

        {{-- Scope untuk memformat tanggal pengajuan --}}
        @scope('cell_created_at', $pengajuan)
        <span>{{ $pengajuan->created_at->format('d/m/Y') }}</span>
        @endscope

        {{-- Scope untuk tombol aksi --}}
        @scope('cell_detail', $pengajuan)
        <div class="flex items-center space-x-2">
            <x-mary-button label="Lihat" icon-right="o-document-magnifying-glass"
                wire:click="pengajuanDetail('{{ $pengajuan->id }}')" spinner
                class="btn-sm rounded-md dark:btn-neutral" />
        </div>
        @endscope
    </x-mary-table>

    {{-- DRAWER DETAIL PENGAJUAN --}}
    <x-mary-drawer wire:model="detailDrawer" title="Detail Pengajuan" right separator with-close-button
        class="w-11/12 lg:w-2/5">

        {{-- Tampilkan loading spinner saat data sedang diambil --}}
        <div wire:loading wire:target="selectedPengajuan" class="w-full h-full flex justify-center items-center">
            <x-mary-loading class="loading-lg" />
        </div>

        {{-- Tampilkan detail jika data sudah ada --}}
        <div wire:loading.remove wire:target="showDetail">
            @if ($selectedPengajuan)
                <div class="space-y-6 p-4">

                    {{-- INFORMASI PEMOHON --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Informasi Pemohon</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Nama Pemohon</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->nama }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">NIM / NIS</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->nim_nis }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Email</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->email }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">No. HP</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->no_hp }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-t-zinc-200 dark:border-t-zinc-700" />

                    {{-- INFORMASI AKADEMIK --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Informasi Akademik</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Perguruan Tinggi/Sekolah</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->sekolah_universitas }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Jurusan/Prodi</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->jurusan_prodi }}</span>
                            </div>
                        </div>
                    </div>

                    <hr class="border-t-zinc-200 dark:border-t-zinc-700" />

                    {{-- DETAIL MAGANG --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Detail Magang</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Bidang yang Diminati</span>
                                <span class="font-semibold text-right">{{ $selectedPengajuan->bidang->nama }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Tanggal Surat Pengantar</span>
                                <span
                                    class="font-semibold text-right">{{ Carbon::parse($selectedPengajuan->tanggal_surat_pengantar)->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Tanggal Mulai</span>
                                <span
                                    class="font-semibold text-right">{{ Carbon::parse($selectedPengajuan->tanggal_mulai)->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Tanggal Selesai</span>
                                <span
                                    class="font-semibold text-right">{{ Carbon::parse($selectedPengajuan->tanggal_selesai)->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold items-center">
                                <span class="text-gray-500 dark:text-gray-400">Status Saat Ini</span>
                                @php
                                    $color = match ($selectedPengajuan->status) {
                                        'review' => 'zinc',
                                        'diterima' => 'lime',
                                        'ditolak' => 'red',
                                        'berlangsung' => 'blue',
                                        'selesai' => 'fuchsia',
                                    };
                                @endphp
                                <flux:badge class="font-semibold" color="{{ $color }}">
                                    {{ Str::Title($selectedPengajuan->status) }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>

                    <hr class="border-t-zinc-200 dark:border-t-zinc-700" />

                    {{-- DOKUMEN --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Dokumen Terlampir</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-mary-button label="Lihat Surat Pengantar" icon="o-document-text"
                                link="{{ asset('storage/' . $selectedPengajuan->surat_pengantar_path) }}" target="_blank"
                                class="btn-outline w-full rounded-md" />
                            <x-mary-button label="Lihat CV" icon="o-user-circle"
                                link="{{ asset('storage/' . $selectedPengajuan->cv_path) }}" target="_blank"
                                class="btn-outline w-full rounded-md" />
                        </div>
                    </div>

                    <hr class="border-t-zinc-200 dark:border-t-zinc-700" />

                    {{-- UPDATE STATUS --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Update Status</h3>
                        <div class="flex flex-col gap-4 text-sm">
                            <x-mary-select label="Pilih Status" wire:model="selectedPengajuanStatus"
                                :options="$statusOptions" placeholder="Pilih Status Magang"
                                class="bg-transparent dark:bg-zinc-950 rounded-md" />
                            <x-mary-button label="Update Status" icon="o-document-arrow-up" wire:click="updateStatus"
                                spinner class="btn-primary dark:btn-neutral rounded-md ml-auto" />
                        </div>
                    </div>

                    <hr class="border-t-zinc-200 dark:border-t-zinc-700" />

                    {{-- AKSI/TINDAKAN --}}
                    <div>
                        <h3 class="font-bold text-lg mb-3">Tindakan</h3>
                        <x-mary-button label="Generate Surat Balasan" icon="o-document-arrow-down"
                            wire:click="startGenerateDocxBalasan" spinner
                            class="btn-primary rounded-md dark:btn-neutral w-full mb-3" />
                        @if ($selectedPengajuan->status === 'selesai')
                            <x-mary-button label="Generate Surat Selesai" icon="o-document-arrow-down"
                                wire:click="startGenerateDocxSelesai" spinner
                                class="btn-primary rounded-md dark:btn-neutral w-full mb-3" />
                        @endif
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-2">
                            <x-mary-button label="Edit Pengajuan" icon="o-pencil-square"
                                link="{{ route('admin.pengajuan.edit', ['pengajuan' => $selectedPengajuan->id]) }}"
                                class="btn-primary rounded-md dark:btn-neutral" />
                            <x-mary-button label="Hapus pengajuan" icon="o-trash"
                                wire:click="startDelete('{{ $selectedPengajuan->id }}', '{{ $selectedPengajuan->nama }}')"
                                spinner class="btn-error bg-red-500 text-primary-content dark:bg-red-600 rounded-md" />
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4">
                    <p>Silakan pilih pengajuan untuk melihat detail.</p>
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-mary-button label="Tutup" @click="$wire.detailDrawer = false"
                class="dark:btn-neutral rounded-md ml-auto" />
        </x-slot:actions>
    </x-mary-drawer>

    @if ($this->deleteModal)
        {{-- MODAL KONFIRMASI HAPUS --}}
        <x-mary-modal wire:model="deleteModal" title="Konfirmasi Hapus">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />
            <div>Apakah Anda yakin ingin menghapus pengajuan dari <span
                    class="font-bold">{{ $pengajuanToDeleteName }}</span>?</div>
            <div class="text-sm text-gray-500 mt-3">Tindakan ini tidak dapat dibatalkan. Semua data dan file terkait akan
                dihapus secara permanen.</div>

            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.deleteModal = false"
                    class="btn-primary dark:btn-neutral rounded-md" />
                <x-mary-button label="Hapus" icon="o-trash" wire:click="confirmDelete"
                    class="btn-error bg-red-500 text-primary-content dark:bg-red-600 rounded-md" spinner="confirmDelete" />
            </x-slot:actions>
        </x-mary-modal>
    @endif

    @if ($this->generateBalasanModal)
        {{-- MODAL GENERATE SURAT BALASAN --}}
        <x-mary-modal wire:model="generateBalasanModal" title="Generate Surat Balasan"
            box-class="dark:bg-zinc-800 rounded-md">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />

            <x-mary-form wire:submit="generateDocxBalasan">
                <x-mary-input label="Nomor Surat Balasan" placeholder="Nomor Surat Balasan..."
                    wire:model="nomorSuratBalasan" class="bg-transparent dark:bg-transparent rounded-md" />

                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.generateBalasanModal = false"
                        class="btn-primary dark:btn-neutral rounded-md" />
                    <x-mary-button label="Generate" wire:click="generateDocxBalasan" spinner
                        class="btn-primary rounded-md" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    @endif

    @if ($this->generateSelesaiModal)
        {{-- MODAL GENERATE SURAT BALASAN --}}
        <x-mary-modal wire:model="generateSelesaiModal" title="Generate Surat Balasan"
            box-class="dark:bg-zinc-800 rounded-md">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />

            <x-mary-form wire:submit="generateDocxSelesai">
                <x-mary-input label="Nomor Surat Balasan" placeholder="Nomor Surat Balasan..."
                    wire:model="nomorSuratSelesai" class="bg-transparent dark:bg-transparent rounded-md" />

                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.generateSelesaiModal = false"
                        class="btn-primary dark:btn-neutral rounded-md" />
                    <x-mary-button label="Generate" wire:click="generateDocxSelesai" spinner
                        class="btn-primary rounded-md" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    @endif
</div>