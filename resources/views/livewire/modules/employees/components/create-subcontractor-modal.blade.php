<div>
    <x-ui.card :noBodyPadding=TRUE loading="confirmBtn" :border=FALSE>
        <div class="d-flex gap-3 flex-wrap px-1">
            <div class="flex-grow-1">
                <x-ui.input
                    label="{{ translator('Name') }}"
                    placeholder="{{ translator('Subcontractor name') }}"
                    wModel="name"
                    maxlength="255"
                    wire:keydown.enter="confirmBtn"
                    autofocus
                />
            </div>
            <div style="min-width: 160px">
                <x-ui.select
                    :options="$statuses"
                    label="{{ translator('Status') }}"
                    wModel="status"
                />
            </div>
        </div>

        <div class="d-flex justify-content-end pt-3 px-1">
            <x-ui.btn type="suc" icon="building-add" text="{{ translator('Create') }}" action="confirmBtn" />
        </div>
    </x-ui.card>
</div>
