@extends('layouts.app')
@section('title', 'Add Portfolio Project | ' . config('app.name'))
@section('content')<main class="container py-5"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p><h1 class="display-6 fw-bold">Add portfolio project</h1></header><form method="POST" action="{{ route('learning.projects.store') }}">@csrf @include('learning.projects._form',['submitLabel'=>'Add project','cancelUrl'=>route('learning.projects.index')])</form></main>@endsection
