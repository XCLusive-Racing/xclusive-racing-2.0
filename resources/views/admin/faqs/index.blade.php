@extends('layouts.admin')

@section('title', 'FAQ Management')
@section('page-title', 'FAQ')

@section('page-actions')
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-sm fw-black text-uppercase text-white" style="background:#7c3aed;font-size:.78rem">
        + Add FAQ
    </a>
@endsection

@section('content')

@if(session('success'))
<div class="alert border-0 text-white fw-bold mb-4 rounded-3" style="background:#16a34a">{{ session('success') }}</div>
@endif

<div class="admin-card mb-4">
    <div class="px-4 py-3 d-flex align-items-center gap-2 flex-wrap">
        <span class="fw-bold text-uppercase text-secondary" style="font-size:.72rem;letter-spacing:.05em">Category</span>
        <a href="{{ route('admin.faqs.index') }}"
           class="btn btn-sm fw-bold text-uppercase {{ !$category ? '' : 'btn-outline-secondary' }}"
           style="font-size:.7rem;padding:4px 12px;{{ !$category ? 'background:#7c3aed;color:#fff' : '' }}">
            All
        </a>
        @foreach($categories as $cat)
        <a href="{{ route('admin.faqs.index', ['category' => $cat]) }}"
           class="btn btn-sm fw-bold text-uppercase {{ $category === $cat ? '' : 'btn-outline-secondary' }}"
           style="font-size:.7rem;padding:4px 12px;{{ $category === $cat ? 'background:#7c3aed;color:#fff' : '' }}">
            {{ $cat }}
        </a>
        @endforeach
    </div>
</div>

<div class="admin-card p-0 overflow-hidden">
    @if($faqs->isEmpty())
    <p class="text-secondary text-center py-4 mb-0" style="font-size:.85rem">
        @if($category)
            No FAQs in "{{ $category }}" yet.
        @else
            No FAQs yet — add the first one.
        @endif
    </p>
    @else
    <div class="table-responsive">
        <table class="table align-middle mb-0" style="font-size:.85rem">
            <thead style="background:#fafafa;border-bottom:2px solid #f3f4f6">
                <tr>
                    <th class="fw-bold text-uppercase ps-4 py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af;width:70px">Order</th>
                    <th class="fw-bold text-uppercase py-3" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Question</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-sm-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Category</th>
                    <th class="fw-bold text-uppercase py-3 d-none d-md-table-cell" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af">Answer</th>
                    <th class="fw-bold text-uppercase pe-4 py-3 text-end" style="font-size:.68rem;letter-spacing:.06em;color:#9ca3af"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($faqs as $faq)
                <tr style="border-bottom:1px solid #f9fafb">
                    <td class="ps-4 text-secondary">{{ $faq->sort_order }}</td>
                    <td class="fw-bold text-dark">{{ $faq->question }}</td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge fw-bold" style="background:#f3e8ff;color:#7c3aed;font-size:.68rem;padding:3px 8px;border-radius:6px">{{ $faq->category }}</span>
                    </td>
                    <td class="text-secondary d-none d-md-table-cell" style="max-width:300px">
                        <span class="text-truncate d-block">{{ \Illuminate\Support\Str::limit($faq->answer, 100) }}</span>
                    </td>
                    <td class="pe-4 text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase"
                               style="font-size:.68rem;padding:4px 10px">
                                Edit
                            </a>
                            <form action="{{ route('admin.faqs.destroy', $faq) }}" method="POST"
                                  onsubmit="return confirm('Delete this FAQ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm fw-bold text-uppercase"
                                        style="font-size:.68rem;padding:4px 10px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<p class="text-secondary mt-3 mb-0" style="font-size:.75rem">
    Lower order numbers appear first on the public <a href="{{ route('faq') }}" target="_blank" class="fw-bold" style="color:#7c3aed">FAQ page</a>.
</p>

@endsection
