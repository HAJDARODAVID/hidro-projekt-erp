<div class="list-group mx-2 pb-3">
    @foreach ($menuItems as $mKey => $module)
        <div style="cursor: pointer" class="list-group-item list-group-item-action @if ($mKey == $activeModule) active @endif" wire:click='activateModule({{ $mKey }})'>
            <div class="d-flex gap-3">
                <i class="{{ $module['icon'] }}"></i>
                <b @if ($mKey != $activeModule) style="color: #475563" @endif>{{ strtoupper($module['name']) }}</b>
            </div>   
        </div>
        <div style="display: @if ($mKey == $activeModule) block @else none @endif" class="list-group py-0">
            @foreach ($module['menu_items'] as $item)
                @if (Route::has($item['route_name']))
                    <a 
                        href="@isset($item['route_name']) {{ route($item['route_name']) }} @endisset" 
                        class="list-group-item list-group-item-action px-4 py-1" 
                        style="border-radius: 0px 0px"
                    > 
                        &#x2022; 
                        @if($item['route_name'] == $routeName) <b> @endif
                            {{ $item['name'] }}
                        </b>
                    </a>  
                @endif 
            @endforeach
            <hr class="my-1">
          </div>
    @endforeach

    <hr>

    @if(Auth::user()->id == 1 || Auth::user()->id == 2)
        <button class="list-group-item list-group-item-action" id="calculator">
            <div class="d-flex gap-3">
                <i class="bi bi-calculator"></i>
                <b>KALKULATOR</b>
            </div>
        </button>
    @endif

    {{-- INVENTORY MENU ITEM  --}}
    @if($activeInventory)
        <a href="{{ route('hp_activeInventoryChecking', $activeInventory->inv_name) }}" class="list-group-item list-group-item-action">
            <div class="d-flex gap-3">
                <i class="bi bi-list-check"></i>
                <b>INVENTURA: {{ $activeInventory->inv_name }}</b>
            </div>
        </a>
    @endif

    {{-- SIGN OUT BTN IF USER IS ON PHONE --}}
    @if(Session::get('is_phone'))
        <hr>
        <a class="list-group-item list-group-item-action" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <div class="d-flex gap-3">
                <i class="bi bi-box-arrow-left"></i>
                <b>SIGN OUT</b>
            </div>
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    @endif
</div>

<script>
    function showHideRoutes(routes) {
      var x = document.getElementById(routes);
      if (x.style.display === 'none') {
      x.style.display = 'block';
      } else {
      x.style.display = 'none';
      }
    }
  </script>