@extends('layouts.desktop.app-admin')

@section('content')

    <div class="dashboard-grid">
        @foreach ($config as $widget)
            <div class="dashboard-grid-item dashboard-grid-item--{{ $widget['key'] }}">
                <x-dynamic-component :component="$widget['component']" />
            </div>
        @endforeach
    </div>

@endsection
