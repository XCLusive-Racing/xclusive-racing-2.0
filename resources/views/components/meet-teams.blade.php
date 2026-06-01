{{-- Meet the Teams sectie: hexagon PNG achtergrond + Alpine.js tabfilter + rijderskaarten --}}
<section id="teams" class="py-5 px-3 position-relative"
         style="background:url('/images/home/XCLusive_Dark_Hexagon_background_v1.png') center/cover no-repeat #1a0a2e;"
         x-data="{ activeTab: 'acc' }">

    <div class="container-xl">

        {{-- Logo, sectiekop en decoratieve streep --}}
        <div class="text-center mb-5">
            <img src="/images/home/xclusive_racing_logo_lion.png"
                 alt="XCLusive Esports" height="72"
                 class="d-block mx-auto mb-3" loading="lazy">

            <h2 class="text-white fw-bold fst-italic text-uppercase mb-3"
                style="font-size:clamp(2.2rem,5vw,3.5rem);
                       font-family:'Barlow Condensed',sans-serif;
                       letter-spacing:.04em;">
                MEET THE TEAM
            </h2>

            {{-- Paarse decoratieve streep --}}
            <hr style="width:80px;border-color:#7c3aed;border-width:2px;
                        opacity:1;margin:0 auto 0;">
        </div>

        {{-- Tabfilterknoppen: actief = wit gevuld, inactief = witte outline --}}
        <div class="d-flex justify-content-center gap-3 mb-5 flex-wrap">

            <button @click="activeTab = 'acrally'"
                    :class="activeTab === 'acrally' ? 'active' : ''"
                    class="platform-btn">
                AC RALLY
            </button>

            <button @click="activeTab = 'acc'"
                    :class="activeTab === 'acc' ? 'active' : ''"
                    class="platform-btn">
                ACC CONSOLE
            </button>

            <button @click="activeTab = 'lmu'"
                    :class="activeTab === 'lmu' ? 'active' : ''"
                    class="platform-btn">
                LE MANS ULTIMATE
            </button>

            <button @click="activeTab = 'iracing'"
                    :class="activeTab === 'iracing' ? 'active' : ''"
                    class="platform-btn">
                iRACING
            </button>

        </div>

        {{-- ─── AC Rally rijders ───────────────────────────────────────────── --}}
        <div x-show="activeTab === 'acrally'"
             x-transition.opacity.duration.300ms
             class="row g-3">
            @php
            $acRallyTeam = [
                ['name' => 'TBA', 'lastName' => 'COMING SOON', 'country' => '🏁'],
            ];
            @endphp
            @foreach($acRallyTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar" style="background:linear-gradient(135deg,#16a34a,#7c3aed)">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ─── ACC Console rijders ────────────────────────────────────────── --}}
        <div x-show="activeTab === 'acc'"
             x-transition.opacity.duration.300ms
             class="row g-3">
            @php
            $accTeam = [
                ['name' => 'Nat',      'lastName' => 'BENNET',       'country' => '🇬🇧'],
                ['name' => 'Sergio',   'lastName' => 'HERNÁNDEZ',    'country' => '🇪🇸'],
                ['name' => 'Phil',     'lastName' => 'SOURCY',       'country' => '🇨🇦'],
                ['name' => 'Joakim',   'lastName' => 'ERIKSSON',     'country' => '🇸🇪'],
                ['name' => 'Matteo',   'lastName' => 'MASTROMAURO',  'country' => '🇮🇹'],
                ['name' => 'Gianluca', 'lastName' => 'ZAMBIONE',     'country' => '🇮🇹'],
            ];
            @endphp
            @foreach($accTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar bg-gradient-xcl">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ─── Le Mans Ultimate rijders ───────────────────────────────────── --}}
        <div x-show="activeTab === 'lmu'"
             x-transition.opacity.duration.300ms
             class="row g-3">
            @php
            $lmuTeam = [
                ['name' => 'Giuseppe', 'lastName' => 'DINOIA',   'country' => '🇮🇹'],
                ['name' => 'Paul',     'lastName' => 'MÖLLER',   'country' => '🇩🇪'],
                ['name' => 'Jesse',    'lastName' => 'AALBREGT', 'country' => '🇳🇱'],
                ['name' => 'Denis',    'lastName' => 'EBERT',    'country' => '🇩🇪'],
            ];
            @endphp
            @foreach($lmuTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar" style="background:linear-gradient(135deg,#db2777,#7c3aed)">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ─── iRacing rijders ────────────────────────────────────────────── --}}
        <div x-show="activeTab === 'iracing'"
             x-transition.opacity.duration.300ms
             class="row g-3">
            @php
            $iracingTeam = [
                ['name' => 'Ethan',  'lastName' => 'AMBURG',  'country' => '🇺🇸'],
                ['name' => 'Parker', 'lastName' => 'SOUKUP',  'country' => '🇺🇸'],
                ['name' => 'James',  'lastName' => 'CURTIN',  'country' => '🇺🇸'],
            ];
            @endphp
            @foreach($iracingTeam as $driver)
            <div class="col-6 col-md-4 col-lg-3">
                <div class="driver-card rounded-2 p-4 bg-white">
                    <div class="driver-avatar" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
                        <span>{{ $driver['name'][0] }}</span>
                    </div>
                    <div class="small fw-bold text-xcl-purple mb-1">{{ $driver['name'] }}</div>
                    <div class="fw-black text-dark mb-2">{{ $driver['lastName'] }}</div>
                    <div class="fs-4">{{ $driver['country'] }}</div>
                </div>
            </div>
            @endforeach
        </div>

    </div>
</section>