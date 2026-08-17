@extends('layouts.app')

@section('title', 'Join The Team - ' . config('xcl.name'))

@php
    // Canonical display/filter order — also used to sort each role's own
    // category list so multi-category badges always read in this order.
    $categoryOrder = ['Real Racing', 'Esports', 'Operations', 'Media', 'Community'];

    $categoryColors = [
        'Esports'      => '#7c3aed',
        'Real Racing'  => '#16a34a',
        'Media'        => '#cc0000',
        'Operations'   => '#2563eb',
        'Community'    => '#d97706',
    ];

    $joinRoles = [
        'esports-drivers' => [
            'title'      => 'Esports Drivers',
            'categories' => ['Esports'],
            'desc'       => 'Sim racers competing in ACC, LMU, iRacing or ACC PC. Race under the XCLusive banner in official events and leagues.',
        ],
        'professional-drivers' => [
            'title'      => 'Professional Drivers',
            'categories' => ['Real Racing'],
            'desc'       => 'Real-world circuit drivers looking for a team to represent at track days, championships, and real-world events.',
        ],
        'race-engineers' => [
            'title'      => 'Race Engineers',
            'categories' => ['Real Racing', 'Esports'],
            'desc'       => 'Help our drivers set up their cars, build race strategies, and attend events as part of the technical team. Both virtual and real-world experience welcome.',
        ],
        'community-coaches' => [
            'title'      => 'Community Coaches',
            'categories' => ['Operations', 'Community'],
            'desc'       => 'Help XCLusive community members improve their racecraft. Provide feedback, run training sessions, and support driver development.',
        ],
        'stewards' => [
            'title'      => 'Stewards',
            'categories' => ['Operations', 'Community'],
            'desc'       => 'Oversee fair play in XCLusive sim racing events. Review incidents, apply penalties, and help maintain our race standards.',
        ],
        'event-managers' => [
            'title'      => 'Event Managers',
            'categories' => ['Operations'],
            'desc'       => 'Organise and run XCLusive events from start to finish. Coordinate schedules, entries, and communications.',
        ],
        'social-media-managers' => [
            'title'      => 'Social Media Managers',
            'categories' => ['Media'],
            'desc'       => 'Create content, manage our channels, and grow the XCLusive Racing brand across Instagram, TikTok, X, and YouTube.',
        ],
        'news-broadcasters' => [
            'title'      => 'News Broadcasters',
            'categories' => ['Media'],
            'desc'       => 'Write race reports, news articles, and features for the XCLusive Racing news platform powered by TRTN.',
        ],
        'team-managers' => [
            'title'      => 'Team Managers',
            'categories' => ['Esports', 'Operations'],
            'desc'       => 'Oversee driver lineups, team logistics, and internal coordination across esports and real-world programs.',
        ],
        'ambassadors' => [
            'title'      => 'Ambassadors',
            'categories' => ['Community'],
            'desc'       => 'Represent XCLusive Racing in the sim racing community. Help recruit members, attend events, and be the face of the brand in your region.',
        ],
        'relationship-managers' => [
            'title'      => 'Relationship Managers',
            'categories' => ['Real Racing', 'Esports', 'Operations'],
            'desc'       => 'Actively seek out business partners, sponsors, and real-world motorsport organisations to collaborate with XCLusive Racing.',
        ],
        'scouts' => [
            'title'      => 'Scouts',
            'categories' => ['Esports'],
            'desc'       => 'Active across any sim racing title, with the connections to spot talented drivers. Scout new recruits and help train and develop them once they join.',
        ],
        'livery-designers' => [
            'title'      => 'Livery Designer',
            'categories' => ['Esports', 'Media', 'Operations'],
            'desc'       => 'Design liveries for our esports and race cars across ACC, LMU, and iRacing — keeping the XCLusive look sharp and consistent across every series.',
        ],
    ];

    // Normalize every role's category list to the canonical order.
    foreach ($joinRoles as &$__role) {
        usort($__role['categories'], fn($a, $b) => array_search($a, $categoryOrder) <=> array_search($b, $categoryOrder));
    }
    unset($__role);
@endphp

@section('content')
<main class="xcl-page pb-5 px-3" style="background:white">
    <div class="about-section__topo" style="background-image:url('/topo.png')"></div>

    <div class="container-xl" style="position:relative;z-index:1">

        {{-- Page header --}}
        <div class="pt-4 mb-5">
            <h1 class="display-4 fw-black text-uppercase fst-italic about-section__heading mb-3">JOIN THE TEAM</h1>
            <div class="section-divider" style="margin-left:0"></div>
            <p class="mt-3 mb-0" style="color:#6b7280;font-size:.95rem;max-width:720px">
                Help us build something special. XCLusive Racing competes at the highest level in both virtual and real-world motorsport — and we are looking for passionate people to grow with us.
            </p>
        </div>

        {{-- Feature cards --}}
        <div class="row g-4 mb-5">
            @foreach([
                ['icon' => 'fa-solid fa-trophy',     'title' => 'Compete At The Top',   'text' => 'Race in premier sim racing events against some of the best drivers in the community.'],
                ['icon' => 'fa-solid fa-users',      'title' => 'Build Something Real', 'text' => 'Join a team that bridges virtual and real-world motorsport.'],
                ['icon' => 'fa-solid fa-chart-line', 'title' => 'Grow With Us',         'text' => 'Develop your skills, build your network, and be part of something bigger.'],
            ] as $perk)
            <div class="col-md-4">
                <div class="driver-card rounded-3 p-4 h-100 text-center">
                    <i class="{{ $perk['icon'] }} mb-3" style="font-size:1.75rem;color:#7c3aed"></i>
                    <h3 class="fw-black text-uppercase fs-6 mb-2">{{ $perk['title'] }}</h3>
                    <p class="mb-0" style="color:#6b7280;font-size:.88rem">{{ $perk['text'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Open roles --}}
        <div class="mb-5">
            <h2 class="fs-3 fw-black text-uppercase fst-italic mb-4">OPEN ROLES</h2>

            {{-- Category filter --}}
            <div class="d-flex flex-wrap gap-2 mb-4" data-role-filters>
                <button type="button" class="role-filter-btn active" data-filter="all" onclick="filterRoles('all', this)">All</button>
                @foreach($categoryOrder as $category)
                <button type="button" class="role-filter-btn" data-filter="{{ $category }}" onclick="filterRoles('{{ $category }}', this)">
                    {{ strtoupper($category) }}
                </button>
                @endforeach
            </div>

            <div class="row g-4">
                @foreach($joinRoles as $slug => $role)
                <div class="col-md-6 col-lg-4" data-role-col data-categories="{{ implode('|', $role['categories']) }}">
                    <div class="role-card h-100 p-4 d-flex flex-column">
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @foreach($role['categories'] as $category)
                            <span class="role-badge" style="background:{{ $categoryColors[$category] }}">{{ strtoupper($category) }}</span>
                            @endforeach
                        </div>
                        <h3 class="fw-black text-uppercase fs-6 mb-2">{{ $role['title'] }}</h3>
                        <p class="mb-3" style="color:#6b7280;font-size:.85rem;flex:1">{{ $role['desc'] }}</p>
                        <button type="button" onclick="applyForRole('{{ $slug }}')"
                                class="btn fw-bold text-uppercase text-xcl-purple w-100"
                                style="border:2px solid #7c3aed;font-size:.78rem">
                            Apply for this role
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Application form --}}
        <section id="apply-form" class="mb-5">
            <div class="text-center mb-4">
                <h2 class="fs-3 fw-black text-uppercase fst-italic mb-2">APPLY NOW</h2>
                <p class="mb-0" style="color:#6b7280">Fill in your details and we will get back to you.</p>
            </div>

            @if(session('success'))
            <div class="alert alert-success rounded-3 fw-bold text-center mb-4 mx-auto" style="max-width:720px">
                {{ session('success') }}
            </div>
            @endif

            <div class="apply-form-card mx-auto" style="max-width:720px">
                <form method="POST" action="{{ route('team.apply') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="apply-name">Full Name</label>
                        <input type="text" name="name" id="apply-name" required
                               value="{{ old('name') }}"
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="apply-email">Email Address</label>
                        <input type="email" name="email" id="apply-email" required
                               value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="apply-discord">
                            Discord Username <span class="text-secondary fw-normal">(optional)</span>
                        </label>
                        <input type="text" name="discord" id="apply-discord"
                               placeholder="e.g. username#0000"
                               value="{{ old('discord') }}"
                               class="form-control @error('discord') is-invalid @enderror">
                        @error('discord')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" for="apply-role">Role You Are Applying For</label>
                        <select name="role" id="apply-role" required
                                class="form-select @error('role') is-invalid @enderror">
                            <option value="" disabled {{ old('role') ? '' : 'selected' }}>Select a role…</option>
                            @foreach(\App\Models\TeamApplication::ROLES as $roleSlug => $roleLabel)
                            <option value="{{ $roleSlug }}" {{ old('role') === $roleSlug ? 'selected' : '' }}>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold d-block">Platform(s) <span class="text-secondary fw-normal">(optional)</span></label>
                        @foreach(['PC', 'PS5', 'Xbox'] as $platform)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="platforms[]" value="{{ $platform }}"
                                   id="apply-platform-{{ $platform }}"
                                   {{ collect(old('platforms'))->contains($platform) ? 'checked' : '' }}>
                            <label class="form-check-label" for="apply-platform-{{ $platform }}">{{ $platform }}</label>
                        </div>
                        @endforeach
                        @error('platforms')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold" for="apply-motivation">Your Motivation</label>
                        <textarea name="motivation" id="apply-motivation" required
                                  style="min-height:150px"
                                  placeholder="Tell us why you want to join XCLusive Racing and what you bring to the team..."
                                  class="form-control @error('motivation') is-invalid @enderror">{{ old('motivation') }}</textarea>
                        @error('motivation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn fw-black text-uppercase text-white px-5 py-3 w-100" style="background:#7c3aed">
                        SEND APPLICATION
                    </button>
                </form>
            </div>
        </section>

    </div>
</main>

<script>
    function applyForRole(slug) {
        const select = document.getElementById('apply-role');
        if (select) select.value = slug;
        document.getElementById('apply-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function filterRoles(category, btn) {
        document.querySelectorAll('[data-role-filters] .role-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('[data-role-col]').forEach(col => {
            const categories = col.dataset.categories.split('|');
            col.style.display = (category === 'all' || categories.includes(category)) ? '' : 'none';
        });
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('apply-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    @endif
</script>
@endsection
