{{-- Fields of the achievement form. Needs $achievement, $scopes, $metrics, $categories;
     $isNew decides whether the code can be typed. --}}
@php $isNew = ! $achievement->exists; @endphp

<div class="row g-3">
    <div class="col-md-7">
        <label class="form-label">Name</label>
        <input type="text" name="name" value="{{ old('name', $achievement->name) }}" required maxlength="255"
               class="form-control @error('name') is-invalid @enderror">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">Code</label>
        @if($isNew)
        <input type="text" name="code" value="{{ old('code') }}" required maxlength="80" placeholder="wins_750"
               class="form-control @error('code') is-invalid @enderror">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Lower case letters, digits and underscores. Cannot be changed later.</div>
        @else
        <input type="text" value="{{ $achievement->code }}" class="form-control" disabled>
        @endif
    </div>

    <div class="col-12">
        <label class="form-label">Description</label>
        <input type="text" name="description" value="{{ old('description', $achievement->description) }}" maxlength="500"
               class="form-control @error('description') is-invalid @enderror">
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Category</label>
        <input type="text" name="category" value="{{ old('category', $achievement->category) }}" required maxlength="60" list="xp-categories"
               class="form-control @error('category') is-invalid @enderror">
        <datalist id="xp-categories">
            @foreach($categories as $category)<option value="{{ $category }}">@endforeach
        </datalist>
        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @if($isNew)
    <div class="col-md-4">
        <label class="form-label">Family</label>
        <input type="text" name="family" value="{{ old('family') }}" maxlength="80" class="form-control" placeholder="wins">
        <div class="form-text">Groups the tiers of the same thing. Optional.</div>
    </div>
    @endif
    <div class="col-md-4">
        <label class="form-label">Game scope</label>
        <select name="game_scope" class="form-select @error('game_scope') is-invalid @enderror">
            @foreach($scopes as $key => $label)
            <option value="{{ $key }}" @selected(old('game_scope', $achievement->game_scope) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('game_scope')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">All titles: progress in every title adds up. A title: only progress in that title counts.</div>
    </div>

    <div class="col-md-8">
        <label class="form-label">Metric</label>
        <select name="metric_key" class="form-select @error('metric_key') is-invalid @enderror">
            @foreach($metrics as $key => $metric)
            <option value="{{ $key }}" @selected(old('metric_key', $achievement->metric_key) === $key)>
                {{ $metric->label }} ({{ $key }}){{ $metric->countable() ? '' : ' [not countable yet]' }}
            </option>
            @endforeach
        </select>
        @error('metric_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Only metrics the site can compute are listed. A metric that is not in this list must be added in code first.
            An achievement on a metric marked "not countable yet" is never awarded until that data exists.
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Threshold</label>
        <input type="number" name="threshold" value="{{ old('threshold', $achievement->threshold) }}" required min="0" step="0.01"
               class="form-control @error('threshold') is-invalid @enderror">
        @error('threshold')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Awarded when the metric reaches this value{{ $achievement->comparison === 'lte' ? ' or lower (a lap time, in seconds)' : '' }}.</div>
    </div>

    <div class="col-md-4">
        <label class="form-label">XP value</label>
        <input type="number" name="xp" value="{{ old('xp', $achievement->xp) }}" required min="0" step="1"
               class="form-control @error('xp') is-invalid @enderror">
        @error('xp')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Sort order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $achievement->sort_order ?? 0) }}" min="0" step="1" class="form-control">
    </div>
    <div class="col-12">
        <div class="alert mb-0" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:.82rem">
            <strong>Changing the XP value affects future awards only.</strong>
            Drivers who already hold this achievement keep the XP they were awarded at the time: it is stored on their award and is never recalculated.
            Changing it also moves the curve health figure on the Achievements screen.
        </div>
    </div>
</div>
