@extends('layouts.mobile.app-admin')

@section('content')

    @foreach ($config as $widget)
        <x-dynamic-component :component="$widget['component']" />
    @endforeach

@endsection
