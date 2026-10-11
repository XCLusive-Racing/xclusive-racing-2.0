{{-- The business details (config xcl.legal); a line that isn't configured is left out. --}}
@php $legal = config('xcl.legal'); @endphp
<ul class="list-unstyled mb-0" style="line-height:2">
    <li><strong class="text-dark">{{ $legal['company'] }}</strong> (trading as {{ config('xcl.name') }})</li>
    @if($legal['address'])<li>{{ $legal['address'] }}</li>@endif
    @if($legal['coc'])<li>Chamber of Commerce (KvK): {{ $legal['coc'] }}</li>@endif
    @if($legal['vat'])<li>VAT number: {{ $legal['vat'] }}</li>@endif
    <li>E-mail: <a href="mailto:{{ $legal['email'] }}" class="text-decoration-none fw-bold" style="color:#7c3aed">{{ $legal['email'] }}</a></li>
    <li>Website: <a href="{{ url('/') }}" class="text-decoration-none fw-bold" style="color:#7c3aed">{{ parse_url(url('/'), PHP_URL_HOST) }}</a></li>
</ul>
