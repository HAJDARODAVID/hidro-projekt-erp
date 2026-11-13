{{--
    Fixed sidebar entries under the app menu (taken over from the old main-menu component):
    calculator, the running inventory and the sign out button on phones.
    Uses the styles of the app menu component, so render it after it.
--}}
@php
    $activeInventory = \App\Models\InventoryCheckingModel::where('status', 1)->first();
    $showCalculator = in_array(Auth::id(), [1, 2], TRUE);
    $isPhone = Session::get('is_phone');
@endphp

@if ($showCalculator || $activeInventory || $isPhone)
    <nav class="app-menu" aria-label="{{ translator('Tools') }}">
        <ul>
            <li class="app-menu-divider" role="separator"></li>

            @if ($showCalculator)
                <li>
                    <button type="button" class="app-menu-link" id="calculator">
                        <i class="bi bi-calculator app-menu-icon"></i>
                        <span class="app-menu-title">{{ translator('Calculator') }}</span>
                    </button>
                </li>
            @endif

            @if ($activeInventory)
                <li>
                    <a href="{{ route('hp_activeInventoryChecking', $activeInventory->inv_name) }}" class="app-menu-link">
                        <i class="bi bi-list-check app-menu-icon"></i>
                        <span class="app-menu-title">{{ translator('Inventory') }}: {{ $activeInventory->inv_name }}</span>
                    </a>
                </li>
            @endif

            @if ($isPhone)
                <li>
                    <a class="app-menu-link" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-left app-menu-icon"></i>
                        <span class="app-menu-title">{{ translator('Sign out') }}</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            @endif
        </ul>
    </nav>
@endif
