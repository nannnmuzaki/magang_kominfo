<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\Pengajuan;
use Illuminate\Support\Str;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.app')]
    #[Title('Kelola Pengajuan')]
    class extends Component {

    use WithPagination, Toast;

    // Properti untuk filter dan pencarian
    public string $search = '';
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

    // Properti untuk detail pengajuan
    public ?Pengajuan $selectedPengajuan = null;
    public bool $detailDrawer = false;

    public function pengajuanDetail($pengajuanId)
    {
        try {
            $this->selectedPengajuan = Pengajuan::with('bidang')->findOrFail($pengajuanId);

            // Pastikan user memiliki izin untuk melihat pengajuan ini
            $this->authorize('view', $this->selectedPengajuan);

            // Set status pengajuan yang dipilih
            $this->selectedPengajuanStatus = $this->selectedPengajuan->status;

            // Tampilkan drawer detail
            $this->detailDrawer = true;
        } catch (\Exception $e) {
            $this->error('Pengajuan tidak ditemukan atau terjadi kesalahan saat mengambil data.');
            return [];
        }
    }

    public function with(): array
    {

        $pengajuanQuery = Auth::user()->pengajuan()
            ->with('bidang')
            ->when($this->search, function ($query) {
                // Terapkan filter pencarian jika $this->search tidak kosong
                $query->where('nama', 'ilike', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        return [
            'semuaPengajuan' => $pengajuanQuery->paginate(10),
        ];
    }

}; ?>

<div class="flex h-full w-full flex-col gap-4 rounded-xl">
    <x-mary-header class="mb-1!" title="Pengajuan" subtitle="Kelola pengajuan magang anda di Kominfo Banyumas"
        separator />

    <flux:button icon:trailing="plus" href="{{ route('pengajuan.create') }}" class="mr-auto" wire:navigate>
        Buat Pengajuan
    </flux:button>

    <div class="flex max-sm:flex-col max-sm:items-start w-full mb-1 gap-2 items-center">
        <flux:input class:input="bg-transparent! dark:placeholder-white/90! dark:border-zinc-600 max-sm:w-full"
            wire:model.live.debounce.300ms="search" kbd="⌘K" icon="magnifying-glass" placeholder="Search..." />
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
    <x-mary-table class="font-medium" :headers="$headers" :rows="$semuaPengajuan" :sort-by="$sortBy" with-pagination>

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
            @can('update', $pengajuan)
                <x-mary-button label="Edit" icon-right="o-pencil-square"
                    link="{{ route('pengajuan.edit', ['pengajuan' => $pengajuan->id]) }}"
                    class="btn-sm btn-primary rounded-md dark:btn-neutral" />
            @endcan

            @can('view', $pengajuan)
                <x-mary-button label="Lihat" icon-right="o-document-magnifying-glass"
                    wire:click="pengajuanDetail('{{ $pengajuan->id }}')" spinner
                    class="btn-sm rounded-md dark:btn-neutral" />
            @endcan
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
                                    class="font-semibold text-right">{{ Carbon\Carbon::parse($selectedPengajuan->tanggal_surat_pengantar)->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Tanggal Mulai</span>
                                <span
                                    class="font-semibold text-right">{{ Carbon\Carbon::parse($selectedPengajuan->tanggal_mulai)->locale('id')->translatedFormat('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between gap-8 font-semibold">
                                <span class="text-gray-500 dark:text-gray-400">Tanggal Selesai</span>
                                <span
                                    class="font-semibold text-right">{{ Carbon\Carbon::parse($selectedPengajuan->tanggal_selesai)->locale('id')->translatedFormat('d F Y') }}</span>
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
</div>