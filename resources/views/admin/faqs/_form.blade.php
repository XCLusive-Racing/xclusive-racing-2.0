@php $faq = $faq ?? null; @endphp

<div class="px-4 pt-4 pb-3 border-bottom">
    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Question <span class="text-danger">*</span></label>
    <input type="text" name="question"
           value="{{ old('question', $faq?->question) }}"
           class="form-control @error('question') is-invalid @enderror"
           style="font-size:.85rem"
           placeholder="e.g. How do I register for a race?">
    @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="px-4 py-3 border-bottom">
    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">Answer <span class="text-danger">*</span></label>
    <textarea name="answer" rows="5"
              class="form-control @error('answer') is-invalid @enderror"
              style="font-size:.85rem"
              placeholder="Write the answer here...">{{ old('answer', $faq?->answer) }}</textarea>
    @error('answer')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="px-4 py-3 border-bottom">
    @php
        $currentCategory = old('category', $faq?->category ?? 'General');
        $isNewCategory   = $currentCategory !== '' && !in_array($currentCategory, $categories, true);
    @endphp

    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">
        Category
        <span class="fw-normal text-secondary ms-1" style="font-size:.75rem">— used to filter FAQs, pick an existing one or add a new one</span>
    </label>

    <select id="faq-category-select"
            @if(!$isNewCategory) name="category" @endif
            class="form-select @error('category') is-invalid @enderror"
            style="font-size:.85rem;max-width:280px">
        @foreach($categories as $cat)
        <option value="{{ $cat }}" {{ !$isNewCategory && $currentCategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
        @if(!in_array('General', $categories, true))
        <option value="General" {{ !$isNewCategory && $currentCategory === 'General' ? 'selected' : '' }}>General</option>
        @endif
        <option value="__new__" {{ $isNewCategory ? 'selected' : '' }}>+ Add new category</option>
    </select>

    <input type="text" id="faq-category-new"
           @if($isNewCategory) name="category" @endif
           value="{{ $isNewCategory ? $currentCategory : '' }}"
           class="form-control mt-2 @error('category') is-invalid @enderror"
           style="font-size:.85rem;max-width:280px;{{ $isNewCategory ? '' : 'display:none' }}"
           placeholder="New category name">

    @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>

<script>
(function () {
    const select   = document.getElementById('faq-category-select');
    const newInput = document.getElementById('faq-category-new');
    if (!select || !newInput) return;

    select.addEventListener('change', function () {
        const isNew = select.value === '__new__';

        newInput.style.display = isNew ? '' : 'none';

        if (isNew) {
            newInput.setAttribute('name', 'category');
            select.removeAttribute('name');
            newInput.focus();
        } else {
            select.setAttribute('name', 'category');
            newInput.removeAttribute('name');
        }
    });
})();
</script>

<div class="px-4 pt-3 pb-4">
    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">
        Order
        <span class="fw-normal text-secondary ms-1" style="font-size:.75rem">— lower numbers appear first</span>
    </label>
    <input type="number" name="sort_order"
           value="{{ old('sort_order', $faq?->sort_order ?? 0) }}"
           class="form-control @error('sort_order') is-invalid @enderror"
           style="font-size:.85rem;max-width:160px"
           min="0">
    @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
