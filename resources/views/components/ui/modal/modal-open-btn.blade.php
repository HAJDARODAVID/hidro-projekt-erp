<div>
    @if($displayIcon)
        @if($targetComponent)
            <button
                type="button"
                class="btn {{ app(\App\View\Components\Ui\Btn::class, ['type' => $btnType])->btnColor }} @if(app(\App\View\Components\Ui\Btn::class, ['type' => $btnType])->btnSize){{ app(\App\View\Components\Ui\Btn::class, ['type' => $btnType])->btnSize }} @endif shadow"
                style="border-radius: 0px!Important;"
                data-trigger="global-modal"
                data-component="{{ $targetComponent }}"
                data-params='@json($params)'
            >
                <div class="d-flex align-items-center gap-2">
                    <x-ui.bi-icon :icon="$icon" />
                </div>
            </button>
        @else
            <x-ui.btn type="{{ $btnType }}" icon="{{ $icon }}" action="openModal" />
        @endif
    @endif
</div>