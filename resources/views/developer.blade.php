@extends('layouts.developer')

@section('content')
    <div class="mt-3">
        <h3>DEVELOPER</h3><hr>
        <x-ui.employees.absence-btns />
        @livewire('modules.working-hours.components.new-attendance-modal')
        <br>
        <div x-data="{ color: '#007bff' }" x-init="
            let picker;
            picker = new Picker({
                parent: $refs.pickerWrapper,
                popup: 'bottom',
                color: color,
                onChange: (c) => { color = c.hex; $refs.input.value = c.hex },
                onDone: (c) => {
                    color = c.hex;
                    $refs.input.value = c.hex;
                    picker.hide();
                }
            });
        ">
            <input type="hidden" name="color" x-ref="input" :value="color">
            <div x-ref="pickerWrapper" style="display:inline-block; position:relative;">
                <button type="button" class="btn" :style="`background-color: ${color}`">
                    Click to change color
                </button>
            </div>
        </div>

        <script src="https://unpkg.com/vanilla-picker@2"></script>  

    </div>
@endsection