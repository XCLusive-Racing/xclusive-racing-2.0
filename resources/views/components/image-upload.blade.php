@props(['name' => 'image', 'label' => 'Event Image', 'current' => null, 'optional' => true])

<div class="mb-4" x-data="{ preview: '{{ $current ? asset('storage/'.$current) : '' }}' }">
    <label class="form-label">
        {{ $label }}
        @if($optional)
            <span class="text-secondary fw-normal" style="text-transform:none">(optional)</span>
        @endif
    </label>
    <div @click="$refs.input.click()"
         style="border:2px dashed #e5e7eb;border-radius:10px;cursor:pointer;overflow:hidden;transition:border-color .15s;min-height:120px"
         @mouseenter="$el.style.borderColor='#7c3aed'"
         @mouseleave="$el.style.borderColor='#e5e7eb'">
        <template x-if="!preview">
            <div class="d-flex flex-column align-items-center justify-content-center py-4 text-secondary" style="font-size:.85rem">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="mb-2" style="opacity:.4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                </svg>
                Click to upload image
            </div>
        </template>
        <template x-if="preview">
            <img :src="preview" style="width:100%;max-height:200px;object-fit:cover;display:block">
        </template>
    </div>
    <input type="file" name="{{ $name }}" accept="image/*" x-ref="input" class="d-none"
           @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
    @error($name)
        <div class="text-danger mt-1" style="font-size:.85rem">{{ $message }}</div>
    @enderror
</div>