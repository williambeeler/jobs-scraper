@extends('layouts.app')
@section('title', 'Edit ' . $project->title . ' | ' . config('app.name'))
@section('content')<main class="container py-5" style="max-width:860px"><header class="mb-4"><p class="mb-2 text-uppercase text-primary fw-semibold small">Learning center</p><h1 class="display-6 fw-bold">Edit portfolio project</h1></header><form method="POST" action="{{ route('learning.projects.update',$project) }}">@csrf @method('PUT') @include('learning.projects._form',['submitLabel'=>'Save changes','cancelUrl'=>route('learning.projects.show',$project)])</form></main>@endsection
