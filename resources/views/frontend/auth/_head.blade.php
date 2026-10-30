{{-- Hesab səhifələrinin başlığı. $crumb, $title (HTML ola bilər — vurğu üçün), $lead. --}}
<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="{{ url('/') }}">Ana səhifə</a><span>/</span><span>{{ $crumb }}</span></div>
    <h1>{!! $title !!}</h1>
    @if (! empty($lead))<p class="lead">{{ $lead }}</p>@endif
  </div>
</section>
