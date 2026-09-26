@extends('layout.app')

{{-- @yield çıxışı escape etmir, dəyər isə atributun içinə düşür — burada özümüz escape edirik. --}}
@section('meta_title', e($page->title.' — Yol Yoldaşı'))
@section('meta_description', e($page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $page->lead), 155)))

@section('content')
<main>

<section class="page-head">
  <div class="hero__bg" aria-hidden="true"><div class="hero__glow hero__glow--1"></div></div>
  <div class="hero__grid" aria-hidden="true"></div>
  <div class="container">
    <div class="crumbs"><a href="{{ url('/') }}">Ana səhifə</a><span>/</span><span>{{ $page->label() }}</span></div>
    <h1 data-split>{{ $page->title }}</h1>
    @if ($page->lead)
      <p class="lead">{{ $page->lead }}</p>
    @endif
    @if ($page->content_updated_at)
      <div style="margin-top:26px">
        <span class="update-chip">Son yenilənmə: {{ $page->content_updated_at->translatedFormat('d F Y') }}</span>
      </div>
    @endif
  </div>
</section>

<section class="section section--tight">
  <div class="container legal">
    @php $toc = $page->tableOfContents(); @endphp

    @if (count($toc))
      <nav class="toc">
        <h4>Bölmələr</h4>
        <ul>
          @foreach ($toc as $item)
            <li><a href="#{{ $item['id'] }}">{{ $item['title'] }}</a></li>
          @endforeach
        </ul>
      </nav>
    @endif

    <article class="prose">
      {{-- Məzmun yalnız admin panelindən yazılır və HTML kimi saxlanılır. --}}
      {!! $page->body !!}
    </article>
  </div>
</section>
</main>
@endsection
