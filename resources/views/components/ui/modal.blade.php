<div class="modal helvetica modal-bg-blur" style="display: @isset($modalStatus)@if ($modalStatus) block @else none @endif @endisset;z-index:9999;" @if($id) id="{{ $id }}" @endif @if($wKey) wire:key="{{ $wKey }}" @endif>
  <div class="modal-dialog @if($size) modal-{{$size}} @endif" >
    <div class="modal-content" style="border-radius: 0px">
      @if ($alert)
        <div class="alert alert-{{$alert}} alert-dismissible m-0 no-border-radius" role="alert">
          <div class="alert-heading">
            <h6 ><b>{{ mb_strtoupper($title) }}</b></h6>
            <button type="button" class="btn-close" wire:click={{ $closeModelMethod }}></button>
          </div>
          <hr class="m-0 my-2">
          {{ $slot }}
          
        </div>
      @else
        <div class="modal-header">
          <div class="">
            <h5 class="modal-title">{{ mb_strtoupper($title) }}</h5>
            @if($subtitle)
              {{ $subtitle }}
            @endif
          </div>
          <x-ui.btn type="dar.sm" icon="x-lg" :wClickMethod=$closeModelMethod />
        </div>
        <div class="modal-body">
          {{ $slot }}
        </div>
        @if ($footerRight || $footerLeft)
          <div class="modal-footer d-flex justify-content-between">
            <div class="">{{$footerLeft}}</div>
            <div class="d-flex gap-2">{{$footerRight}}</div>
          </div>
        @endif
      @endif
    </div>
  </div>
</div>
