@extends('layouts.app')
@section('title', 'Edit ' . $deck->title . ' | ' . config('app.name'))
@section('content')<main class="container py-5" style="max-width:760px"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Flash cards</p><h1 class="display-6 fw-bold">Edit deck</h1></header><form method="POST" action="{{ route('learning.decks.update',$deck) }}">@csrf @method('PUT') @include('learning.decks._form',['submitLabel'=>'Save changes','cancelUrl'=>route('learning.decks.show',$deck)])</form></main>@endsection
