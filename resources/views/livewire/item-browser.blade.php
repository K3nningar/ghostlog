{{-- resources/views/livewire/item-browser.blade.php --}}
<div>
    <div class="flex gap-4 mb-6">
        <select wire:model.live="category" class="border rounded p-2">
            <option value="">Toutes catégories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
            @endforeach
        </select>

        <input wire:model.live.debounce.300ms="search"
               placeholder="Rechercher..."
               class="border rounded p-2 flex-1">
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach($items as $item)
            <div class="border rounded p-3 text-center hover:shadow-lg transition">
                @if($icon = $item->icon())
                    <img src="{{ asset($icon->public_path) }}"
                         alt="{{ $item->name }}"
                         class="w-16 h-16 mx-auto mb-2 object-contain">
                @endif
                <p class="text-sm font-semibold">{{ $item->name }}</p>
                <p class="text-xs text-gray-500">{{ $item->tier_type }}</p>
            </div>
        @endforeach
    </div>

    {{ $items->links() }}
</div>