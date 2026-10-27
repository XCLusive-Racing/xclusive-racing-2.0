{{-- A settings step's fields, one .cw-section per schema section. A toggle and the
     fields that only matter while it's on render together: up to two of them on
     the toggle's own row, more in an indented block under it. --}}
@foreach($groups as $sectionLabel => $sectionFields)
    @php
        // points_scheme_id gets its own picker with a table preview (scoring step).
        $visible = collect($sectionFields)->filter(fn ($f) =>
            $f['type'] !== 'list' && !($f['hidden'] ?? false) && $f['key'] !== 'points_scheme_id'
        )->values();

        $items = [];
        $consumed = [];
        foreach ($visible as $i => $field) {
            if (in_array($field['key'], $consumed, true)) {
                continue;
            }
            $dependents = $field['type'] === 'boolean'
                ? $visible->filter(fn ($f) => ($f['depends_on'] ?? null) === $field['key'])->values()
                : collect();
            $consumed = array_merge($consumed, $dependents->pluck('key')->all());
            $items[] = ['field' => $field, 'dependents' => $dependents];
        }
    @endphp
    @continue($visible->isEmpty())

    <div class="cw-section">
        <p class="cw-section__title">{{ $sectionLabel }}</p>
        @if($note = ($sectionNotes[$sectionLabel] ?? null))
        <p class="cw-section__note">{{ $note }}</p>
        @endif

        <div class="row g-3">
            @foreach($items as $item)
                @php $field = $item['field']; $dependents = $item['dependents']; @endphp

                @if($dependents->isEmpty())
                    @include('admin.leagues.championships._field', ['field' => $field, 'col' => null])
                @elseif($dependents->count() <= 2)
                    <div class="col-12 cw-group">
                        <div class="row g-3 align-items-start">
                            @include('admin.leagues.championships._field', ['field' => $field, 'col' => 'col-md-4'])
                            @foreach($dependents as $dependent)
                                @include('admin.leagues.championships._field', ['field' => $dependent, 'col' => 'col-md-4'])
                            @endforeach
                        </div>
                    </div>
                @else
                    @php
                        $toggleId = "f-{$field['group']}-{$field['key']}";
                        $toggleOn = (bool) old("settings.{$field['group']}.{$field['key']}", $championship->settings->{$field['group']}->{$field['key']} ?? false);
                    @endphp
                    <div class="col-12 cw-group">
                        <div class="row g-3">
                            @include('admin.leagues.championships._field', ['field' => $field, 'col' => 'col-12'])
                        </div>
                        <div class="cw-group__sub" data-shown-if="{{ $toggleId }}" @if(!$toggleOn) hidden @endif>
                            <div class="row g-3">
                                @foreach($dependents as $dependent)
                                    @include('admin.leagues.championships._field', ['field' => $dependent, 'col' => null])
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Builders sit right under the toggle that shows them. --}}
                @if($field['key'] === 'multiclass_enabled')
                <div class="col-12">@include('admin.leagues.championships._classes-builder')</div>
                @endif
                @if($field['key'] === 'driver_classes_enabled')
                <div class="col-12">@include('admin.leagues.championships._driver-classes-builder')</div>
                @endif
            @endforeach
        </div>
    </div>
@endforeach
