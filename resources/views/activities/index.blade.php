@extends('layout.main')

@php
$title = 'Aankomende activiteiten';
$subtitle = 'Binnenkort op de agenda bij Gumbo Millennium';
if ($past) {
    $title = 'Afgelopen activiteiten';
    $subtitle = 'Overzicht van afgelopen activiteiten, tot 1 jaar terug.';
}

// Get first activity
$firstActivity = $past ? null : $activities->first();
@endphp

@section('title', "{$title} - Gumbo Millennium")

@if ($firstActivity && $firstActivity->image->exists())
@push('css')
<style nonce="@nonce">
.header--activity {
    background-image: url("{{ $firstActivity->image->url('banner') }}");
}
</style>
@endpush
@endif

@section('content')
<div class="header header--activity">
    <div class="container header__container">
        <h1 class="header__title">{{ $title }}</h1>
        <p class="header__subtitle">{{ $subtitle }}</p>
    </div>
</div>

<div class="content-block">
    <div class="container content-block__container">
        <p>
            Bij Gumbo houden wij van leuke activiteiten.
            Van een gezellige soosavond tot een spektaculair weekend weg in een prachtig landhuis.
        </p>
        <p>
            In onderstaand overzicht zie je de {{ Str::lower($title) }}.
        </p>
        @guest
        <div class="alert alert-info">
            Je bent niet ingelogd. Activiteiten die alleen toegankelijk zijn voor leden worden niet getoond.
        </div>
        @endguest
    </div>
</div>

<div class="activity-blocks">
    @each('activities.bits.list-item', $activities, 'activity', 'activities.bits.list-empty')
</div>
<ul style="list-style: '- ' outside;" class="pl-2">
    @foreach ($activities as $activity)

    @endforeach
</ul>

<p>
    @if ($past)
    <a href="{{ route('activity.index') }}">Toon alleen toekomstige evenementen</a>
    @else
    <a href="{{ route('activity.index', ['past' => true]) }}">Toon afgelopen evenementen</a>
    @endif
</p>
@endsection
