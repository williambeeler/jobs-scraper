@extends('layouts.app')
@section('title', 'Add Deck | ' . config('app.name'))
@section('content')<main class="container py-5" style="max-width:760px"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Flash cards</p><h1 class="display-6 fw-bold">Add deck</h1></header><form method="POST" action="{{ route('learning.decks.store') }}">@csrf @include('learning.decks._form',['submitLabel'=>'Add deck','cancelUrl'=>route('learning.decks.index')])</form></main>@endsection
