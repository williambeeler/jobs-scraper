@extends('layouts.app')
@section('title', 'Edit Flash Card | ' . config('app.name'))
@section('content')<main class="container py-5"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Flash cards</p><h1 class="display-6 fw-bold">Edit card</h1></header><form method="POST" action="{{ route('learning.cards.update',$flashCard) }}">@csrf @method('PUT') @include('learning.cards._form',['submitLabel'=>'Save changes','cancelUrl'=>route('learning.cards.show',$flashCard)])</form></main>@endsection
