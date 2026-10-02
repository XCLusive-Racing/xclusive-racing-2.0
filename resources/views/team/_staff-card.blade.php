{{-- One staff member, styled like the esports driver cards; blank portrait until a photo is set in config/staff.php. --}}
@php
    $socialIconClasses = [
        'twitter' => 'fa-brands fa-x-twitter',
        'instagram' => 'fa-brands fa-instagram',
        'website' => 'fa-solid fa-globe',
        'linkedin' => 'fa-brands fa-linkedin',
        'facebook' => 'fa-brands fa-facebook',
        'twitch' => 'fa-brands fa-twitch',
        'tiktok' => 'fa-brands fa-tiktok',
        'youtube' => 'fa-brands fa-youtube',
    ];
@endphp
<div class="esports-driver-card">
    <div class="esports-driver-card__portrait {{ $member['photo'] ? '' : 'esports-driver-card__portrait--blank' }}">
        @if($member['photo'])
        <img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}">
        @endif
        @if($member['flag'])
        <img src="/images/flags/flag-{{ $member['flag'] }}.png" alt="" class="esports-driver-card__flag">
        @endif
        @if(! empty($member['socials']))
        <div class="esports-driver-card__socials">
            @foreach($member['socials'] as $s)
            <a href="{{ $s['href'] }}" class="esports-driver-card__social-link" title="{{ $s['type'] }}" target="_blank" rel="noopener noreferrer">
                <i class="{{ $socialIconClasses[$s['type']] ?? 'fa-solid fa-link' }}"></i>
            </a>
            @endforeach
        </div>
        @endif
    </div>
    <div class="esports-driver-card__name">{{ $member['name'] }}</div>
    <div class="esports-driver-card__role">{{ collect($member['roles'])->map(fn ($role) => $roles[$role] ?? $role)->implode(' · ') }}</div>
</div>
