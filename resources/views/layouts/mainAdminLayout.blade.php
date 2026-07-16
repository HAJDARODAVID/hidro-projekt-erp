<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Hugo 0.104.2">
    <title>Hidro-Projekt: ADM</title>

    <link rel="canonical" href="https://getbootstrap.com/docs/5.2/examples/dashboard/">
    <link rel="icon" href="{{ url('/images/hpIcon.png') }}" type="image/x-icon"/>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">


    <!-- Scripts -->
    @livewireStyles
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <link href="{{ url('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ url('css/snowflake.css') }}" rel="stylesheet">
    <link href="{{ url('assets/css/application.css') }}" rel="stylesheet">
    <style>
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }

      .b-example-divider {
        height: 3rem;
        background-color: rgba(0, 0, 0, .1);
        border: solid rgba(0, 0, 0, .15);
        border-width: 1px 0;
        box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
      }

      .b-example-vr {
        flex-shrink: 0;
        width: 1.5rem;
        height: 100vh;
      }

      .bi {
        vertical-align: -.125em;
        fill: currentColor;
      }

      .nav-scroller {
        position: relative;
        z-index: 2;
        height: 2.75rem;
        overflow-y: hidden;
      }

      .nav-scroller .nav {
        display: flex;
        flex-wrap: nowrap;
        padding-bottom: 1rem;
        margin-top: -1px;
        overflow-x: auto;
        text-align: center;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
      }

      input::-webkit-outer-spin-button,
      input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
      }
    </style>
    
    
    <!-- Custom styles for this template -->
    <link href="{{ url('css/dashboard.css') }}" rel="stylesheet">
    
  </head>
  <body>
    <script>
      function fadeAway(id){
        let milliseconds = 3000;
        setTimeout(function () {
            document.getElementById(id).remove();
        }, milliseconds);
      }
    </script>
    <script>
        function setDashBoardCardBodyHeight(div){
            element = document.getElementById(div);
            console.log(element.clientHeight);
            
            element_body = document.getElementById(div+'_body');
            element_body.style.height = (element.clientHeight-40)+"px"; 
            console.log(element.clientHeight+"px");
            
        }
    </script>
    
<header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
  <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 fs-6 d-flex" href="{{route('home')}}">Hidro-projekt ERP &nbsp; @livewire('warning-indicator')</a>
  <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  {{-- <input class="form-control form-control-dark w-100 rounded-0 border-0" type="text" placeholder="Search" aria-label="Search"> --}}
  <div class="navbar-nav navbar-nav-scroll" style="--bs-scroll-height: 100px;">
    <div class="nav-item text-nowrap">
        @if(!Session::get('is_phone'))
          <a class="nav-link px-3" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Sign out</a>
        @endIf
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
  </div>
</header>

<div class="container-fluid">
  <div class="row">
    <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
      <div class="d-flex flex-column position-sticky pt-3 sidebar-sticky">
          @livewire('main-menu')  
          {{-- OLD MENU - BACK UP --}}
          {{-- <x-admin-module-menu-items></x-admin-module-menu-items> --}}
          @livewire('my-profile-component')
      </div>
    </nav>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-md-4">
        @yield('content')   

        @livewire('alert-modal')
        @livewire('exception-modal')
    </main>
  </div>
</div>
<x-ui.notification />
    @include('components.ui.quick-access-modal')
    @include('components.ui.calculator-modal')
    @livewire('components.modal.global-modal')
    @livewireScripts
    <script src="{{ url('js/bootstrap.bundle.min.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js" integrity="sha384-uO3SXW5IuS1ZpFPKugNNWqTZRRglnUJK6UAZ/gxOX80nxEkN9NcGZTftn6RzhGWE" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="{{ url('js/dashboard.js') }}"></script>
    <script>
      Livewire.on('focus-this-input',() => {
        console.log(document.getElementById('qr-input'));
        
          document.getElementById('qr-input').focus()
      }) 
    </script>
  </body>
</html>
