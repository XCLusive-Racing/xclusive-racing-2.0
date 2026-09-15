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
    <label class="form-label fw-bold text-dark mb-1" style="font-size:.78rem">
        Category
        <span class="fw-normal text-secondary ms-1" style="font-size:.75rem">— used to filter FAQs, pick an existing one or type a new one</span>
    </label>
    <input type="text" name="category" list="faq-categories"
           value="{{ old('category', $faq?->category ?? 'General') }}"
           class="form-control @error('category') is-invalid @enderror"
           style="font-size:.85rem;max-width:280px"
           placeholder="e.g. Registration">
    <datalist id="faq-categories">
        @foreach($categories as $cat)
        <option value="{{ $cat }}">
        @endforeach
    </datalist>
    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

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
