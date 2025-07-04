<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\Pengajuan;
use App\Models\Bidang;
use Illuminate\Support\Str;

new
    #[Layout('components.layouts.app')]
    #[Title('Pengajuan')]
    class extends Component {

    use WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];
    public string $selectedStatus = 'all';
    public string $selectedBidangId = 'all';
    public $selectedTahun = 'all';
    public array $statusOptions = ['review', 'diterima', 'ditolak', 'berlangsung', 'selesai'];

    public function with(): array
    {

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
                $query->where('nama', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        return [
            // Lakukan paginasi pada hasil akhir kueri
            'semuaPengajuan' => $pengajuanQuery->paginate($this->perPage),
            'semuaBidang' => $bidang,
            'availableTahun' => $availableTahun,
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
                            <flux:menu.radio value="{{ $status }}">{{ Str::Title($status) }}</flux:menu.radio>
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
            {{-- Arahkan ke halaman detail/edit dengan ID pengajuan --}}
            <x-mary-button label="Lihat" icon-right="o-document-magnifying-glass" link="#" spinner
                class="btn-sm rounded-md dark:btn-neutral" />
        </div>
        @endscope
    </x-mary-table>
</div>