{{-- What drivers see in ACC's server list and type to join — pushed in settings.json
     and sent to every driver who registers (inbox message). Blank keeps the default:
     "XCL SERVER n" for XCL's own servers, the Server Name above for a league's. --}}
<div class="px-4 py-3" style="border-top:1px solid #f3f4f6">
    <p class="fw-black text-uppercase fst-italic mb-3" style="font-size:.72rem;letter-spacing:.08em;color:#9ca3af">In-Game</p>

    <div class="row g-3">
        <div class="col-sm-7">
            <label class="form-label">In-Game Server Name</label>
            <input type="text" name="ingame_name" maxlength="100" value="{{ old('ingame_name', $server?->ingame_name) }}"
                   class="form-control @error('ingame_name') is-invalid @enderror">
            <div class="form-text" style="font-size:.72rem;color:#9ca3af">Shown in ACC's server list. Blank = the Server Name above{{ ($server?->league?->is_system ?? true) ? ' (XCL servers: "XCL SERVER n")' : '' }}.</div>
            @error('ingame_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-sm-5">
            <label class="form-label">In-Game Password</label>
            <input type="text" name="ingame_password" maxlength="50" value="{{ old('ingame_password', $server?->ingame_password) }}"
                   class="form-control @error('ingame_password') is-invalid @enderror" autocomplete="off">
            <div class="form-text" style="font-size:.72rem;color:#9ca3af">What drivers type to join. Sent to them when they register.</div>
            @error('ingame_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
</div>
