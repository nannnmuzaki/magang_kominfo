<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Illuminate\Validation\Rule as ValidationRule;
use App\Models\Bidang;
use Mary\Traits\Toast;

new
    #[Layout('components.layouts.app')]
    #[Title('Kelola Bidang')]
    class extends Component {
    use Toast;

    public ?Bidang $selectedBidang = null;

    // Properti tabel
    public string $search = '';
    public int $perPage = 10;
    public array $sortBy = ['column' => 'nama', 'direction' => 'asc'];

    // Properti untuk show modal
    public bool $editModal = false;
    public bool $createModal = false;
    public bool $deleteModal = false;


    #[Rule('required|string|max:255')]
    public string $nama = '';

    #[Rule('required|numeric|min:1')]
    public $kuota = '';

    public function rules()
    {
        return [
            'nama' => ['required', 'string', 'max:255', ValidationRule::unique('bidang')->ignore($this->selectedBidang?->id)],
        ];
    }

    public function create()
    {
        $this->reset(['nama', 'kuota']);
        $this->createModal = true;
    }

    public function store(): void
    {
        $validated = $this->validate();

        // Cek apakah bidang dengan nama yang sama sudah ada
        if (Bidang::where('nama', $validated['nama'])->exists()) {
            $this->error('Bidang dengan nama ini sudah ada.');
            return;
        }

        // Buat bidang baru
        Bidang::create([
            'nama' => $validated['nama'],
            'kuota' => $validated['kuota'],
        ]);

        $this->success('Bidang berhasil dibuat.');
        $this->createModal = false;
    }

    public function edit(string $bidangId): void
    {
        $this->selectedBidang = Bidang::findOrFail($bidangId);

        if (!$this->selectedBidang) {
            $this->error('Bidang tidak ditemukan.');
            return;
        }
        $this->nama = $this->selectedBidang->nama;
        $this->kuota = $this->selectedBidang->kuota;
        $this->editModal = true;
    }

    public function update(): void
    {
        $validated = $this->validate();

        if ($this->selectedBidang) {
            $this->selectedBidang->update([
                'nama' => $validated['nama'],
                'kuota' => $validated['kuota'],
            ]);

            // Logika untuk mengupdate bidang
            $this->selectedBidang->save();

            $this->success('Bidang berhasil diperbarui.');
            $this->editModal = false;
        } else {
            $this->error('Bidang tidak ditemukan.');
        }
    }

    public function delete(string $bidangId): void
    {
        $this->selectedBidang = Bidang::findOrFail($bidangId);

        if (!$this->selectedBidang) {
            $this->error('Bidang tidak ditemukan.');
            return;
        }

        $this->deleteModal = true;
    }

    public function destroy(): void
    {
        if ($this->selectedBidang) {
            $this->selectedBidang->delete();
            $this->success('Bidang berhasil dihapus.');
            $this->deleteModal = false;
        } else {
            $this->error('Bidang tidak ditemukan.');
        }
    }

    public function with()
    {
        $bidangQuery = Bidang::query()
            ->when($this->search, function ($query) {
                $query->where('nama', 'ilike', '%' . $this->search . '%');
            })
            ->select('id', 'nama', 'kuota', 'created_at') // Pilih kolom asli
            ->selectRaw(
                // Hitung kuota terpakai dan kurangi dari kuota total
                'kuota - (
                SELECT count(*) 
                FROM pengajuan 
                WHERE pengajuan.bidang_id = bidang.id 
                AND pengajuan.status IN (?, ?)
            ) as sisa_kuota',
                ['diterima', 'berlangsung'] // Bindings untuk keamanan
            )
            ->orderBy($this->sortBy['column'], $this->sortBy['direction']);

        return [
            'semuaBidang' => $bidangQuery->paginate($this->perPage),
        ];
    }
}; ?>

<div class="flex h-full w-full flex-col gap-4 rounded-xl">
    <x-mary-header class="mb-1!" title="Bidang" subtitle="Kelola bidang magang di Kominfo Banyumas" separator />

    <flux:button icon:trailing="plus" wire:click="create" class="mr-auto cursor-pointer" wire:navigate>
        Buat Bidang
    </flux:button>

    <div class="flex max-sm:flex-col max-sm:items-start w-full mb-1 gap-2 items-center">
        <flux:input class:input="bg-transparent! dark:placeholder-white/90! dark:border-zinc-600 max-sm:w-full"
            wire:model.live.debounce.300ms="search" kbd="⌘K" icon="magnifying-glass" placeholder="Search..." />
    </div>

    {{-- Definisikan header tabel dengan kunci yang benar --}}
    @php
        $headers = [
            ['key' => 'nama', 'label' => 'Nama'],
            ['key' => 'kuota', 'label' => 'Kuota'],
            ['key' => 'sisa_kuota', 'label' => 'Sisa Kuota'],
            ['key' => 'created_at', 'label' => 'Tanggal Dibuat', 'sortable' => true],
            ['key' => 'aksi', 'label' => 'Aksi', 'sortable' => false],
        ];
    @endphp

    {{-- Render tabel dengan data dan header yang sudah disiapkan --}}
    <x-mary-table class="font-medium" :headers="$headers" :rows="$semuaBidang" :sort-by="$sortBy" with-pagination
        per-page="perPage" :per-page-values="[10, 20]">
        {{-- Scope untuk tanggal pembuatan --}}
        @scope('cell_created_at', $bidang)
        <span>{{ $bidang->created_at->format('d/m/Y') }}</span>
        @endscope

        {{-- Scope untuk tombol aksi --}}
        @scope('cell_aksi', $bidang)
        <div class="flex items-center space-x-2">
            <x-mary-button label="Edit" icon-right="o-pencil-square" wire:click="edit('{{ $bidang->id }}')" spinner
                class="btn-sm rounded-md dark:btn-neutral" />
            <x-mary-button label="Hapus" icon-right="o-trash" wire:click="delete('{{ $bidang->id }}')" spinner
                class="btn-sm btn-error bg-red-500 text-primary-content dark:bg-red-600 rounded-md" />
        </div>
        @endscope
    </x-mary-table>


    {{-- MODAL CREATE BIDANG --}}
    @if ($this->createModal)
        <x-mary-modal wire:model="createModal" title="Buat Bidang Baru" box-class="dark:bg-zinc-800 rounded-md">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />

            <x-mary-form wire:submit="store">
                <x-mary-input label="Nama Bidang" placeholder="Nama bidang..." wire:model="nama"
                    class="bg-transparent dark:bg-transparent rounded-md" />
                <x-mary-input label="Kuota" placeholder="Kuota bidang..." wire:model="kuota"
                    class="bg-transparent dark:bg-transparent rounded-md" type="number" />

                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.createModal = false"
                        class="btn-primary dark:btn-neutral rounded-md" />
                    <x-mary-button label="Simpan" wire:click="store" spinner class="btn-primary rounded-md" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    @endif

    @if ($this->editModal)
        {{-- MODAL UPDATE BIDANG --}}
        <x-mary-modal wire:model="editModal" title="Edit Bidang" box-class="dark:bg-zinc-800 rounded-md">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />

            <x-mary-form wire:submit="update">
                <x-mary-input label="Nama Bidang" placeholder="Nama bidang..." wire:model="nama" value="{{ $this->nama }}"
                    class="bg-transparent dark:bg-transparent rounded-md" />
                <x-mary-input label="Kuota" placeholder="Kuota bidang..." wire:model="kuota" value="{{ $this->kuota }}"
                    class="bg-transparent dark:bg-transparent rounded-md" type="number" />

                <x-slot:actions>
                    <x-mary-button label="Batal" @click="$wire.editModal = false"
                        class="btn-primary dark:btn-neutral rounded-md" />
                    <x-mary-button label="Update" icon="o-trash" wire:click="update" spinner
                        class="btn-primary rounded-md" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-modal>
    @endif

    @if($this->deleteModal)
        {{-- MODAL DELETE BIDANG --}}
        <x-mary-modal wire:model="deleteModal" title="Hapus Bidang" box-class="dark:bg-zinc-800 rounded-md">
            <hr class="border-t-zinc-300 dark:border-t-zinc-700 mb-4 -mt-2" />

            <p class="text-sm font-medium text-red-500">Apakah Anda yakin ingin menghapus bidang ini? Tindakan ini akan
                menghapus bidang
                dan semua data pengajuan di dalamnya. <br><br>Tindakan ini tidak dapat
                dibatalkan.</p>

            <x-slot:actions>
                <x-mary-button label="Batal" @click="$wire.deleteModal = false"
                    class="btn-primary dark:btn-neutral rounded-md" />
                <x-mary-button label="Hapus" icon="o-trash" wire:click="destroy" spinner
                    class="btn-error bg-red-500 text-primary-content dark:bg-red-600 rounded-md" />
            </x-slot:actions>
        </x-mary-modal>
    @endif

</div>