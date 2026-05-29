@extends('layouts.developer')

@section('content')
    <div class="mt-3">
        <h3>DEVELOPER</h3><hr>
        <x-ui.employees.absence-btns :att='["wire:click" => "selectSomething"]'/>
        {{-- @livewire('modules.working-hours.components.new-attendance-modal') --}}
    </div>
@endsection