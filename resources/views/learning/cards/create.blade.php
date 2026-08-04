@extends('layouts.app')
@section('title', 'Add Flash Card | ' . config('app.name'))
@section('content')<main class="container py-5" style="max-width:800px"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Flash cards</p><h1 class="display-6 fw-bold">Add card</h1></header><form method="POST" action="{{ route('learning.decks.cards.store',$deck) }}">@csrf @include('learning.cards._form',['submitLabel'=>'Add card','cancelUrl'=>route('learning.decks.show',$deck)])</form></main>@endsection
