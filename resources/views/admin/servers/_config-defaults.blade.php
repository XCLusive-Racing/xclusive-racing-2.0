{{-- Per-server JSON overrides — shared by Configuration > Servers and a league's own
     server page; parsed by FtpServer::configDefaultsFromInput(). --}}
<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <p class="fw-black text-uppercase fst-italic mb-2" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">Config Defaults</p>
    <p class="text-secondary mb-3" style="font-size:.75rem">
        Override per-server defaults. Leave blank to use built-in values.
    </p>

    @php
        $service = app(\App\Services\Contracts\ServerConfigGenerator::class);
        $configFields = [
            'event.json'       => ['field' => 'event_defaults',      'placeholder' => json_encode($service->defaultEventConfig(), JSON_PRETTY_PRINT)],
            'settings.json'    => ['field' => 'settings_defaults',   'placeholder' => json_encode($service->defaultSettings(), JSON_PRETTY_PRINT)],
            'eventrules.json'  => ['field' => 'eventrules_defaults', 'placeholder' => json_encode($service->defaultEventRules(), JSON_PRETTY_PRINT)],
            'assistrules.json' => ['field' => 'assistrules_defaults','placeholder' => json_encode($service->defaultAssistRules(), JSON_PRETTY_PRINT)],
        ];
    @endphp

    <div data-accordions>
        @foreach($configFields as $filename => $meta)
        @php
            $field       = $meta['field'];
            $storedValue = old($field, json_encode($server->{$field} ?? json_decode($meta['placeholder'], true), JSON_PRETTY_PRINT));
            $hasOverride = !empty($server->{$field});
            $isFirst     = $loop->first;
        @endphp
        <div data-accordion="{{ $isFirst ? 'open' : 'closed' }}" style="border:1px solid #f3f4f6;border-radius:8px;margin-bottom:.6rem;overflow:hidden">
            <div class="d-flex align-items-center justify-content-between px-3 py-2"
                 data-accordion-header
                 style="cursor:pointer;background:{{ $hasOverride ? '#fffbeb' : '#f9fafb' }}">
                <div class="d-flex align-items-center gap-2">
                    <svg data-accordion-arrow style="transition:transform .15s;flex-shrink:0;transform:{{ $isFirst ? 'rotate(90deg)' : '' }}" width="12" height="12" viewBox="0 0 20 20" fill="currentColor" class="text-secondary"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    <span class="fw-black text-dark" style="font-family:monospace;font-size:.82rem">{{ $filename }}</span>
                    @if($hasOverride)
                        <span class="badge xcl-badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">custom</span>
                    @else
                        <span class="badge xcl-badge" style="background:#f3f4f6;color:#6b7280;font-size:.65rem;padding:2px 7px;border-radius:5px;font-weight:700">built-in default</span>
                    @endif
                </div>
            </div>
            <div data-accordion-body style="{{ $isFirst ? '' : 'display:none' }}">
                <textarea name="{{ $field }}" rows="16" spellcheck="false"
                          class="xcl-config-textarea @error($field) is-invalid @enderror"
                          style="width:100%;font-family:monospace;font-size:.8rem;line-height:1.5;padding:1rem 1.25rem;border:none;border-top:1px solid #e5e7eb;background:{{ $hasOverride ? '#fffdf5' : 'white' }};resize:vertical;outline:none;display:block">{{ $storedValue }}</textarea>
                @error($field)<div class="invalid-feedback px-3">{{ $message }}</div>@enderror
            </div>
        </div>
        @endforeach
    </div>
</div>
