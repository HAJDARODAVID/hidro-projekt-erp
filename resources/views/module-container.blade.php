@extends(Session::get('is_phone') ? 'layouts.mobile.app-admin' : 'layouts.app-admin')

@section('content')
    <div class="mt-3">
        @if ($mainTitle) 
            <x-ui.module.header :title=$mainTitle :tabLinks=$tabLinks :specialIndexIcon=$specialIndexIcon >
                <x-slot:actionsBtn> 
                    @foreach ($moduleAction as $action)
                        @livewire('modules.workday-diary.create-new-diary-modal')
                    @endforeach
                    @if ($moduleAction && $additionalAction) <x-v-divider px=0 /> @endif
                    @foreach ($additionalAction as $action)
                        @livewire('modules.workday-diary.create-new-diary-modal')
                    @endforeach
                </x-slot>
            </x-ui.module-header>
        @endif
        @if ($livewire)
            @livewire("modules.$module.$component")
        @endif
    </div>
@endsection