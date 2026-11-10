<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <div class="d-flex align-items-center gap-2 mb-3">
            <b>{{ translator('Access control') }}</b>
            <x-v-divider class="align-self-stretch" />
            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
            <div class="ms-auto">
                <x-ui.btn
                    type="lig.sm"
                    icon="box-arrow-in-down"
                    text="{{ translator('Import old ACL') }}"
                    action="importFromLegacy"
                    title="{{ translator('Copy the old role groups, resources and assignments into the new ACL') }}"
                    wire:confirm="{{ translator('Import the old ACL? Existing roles and permissions are kept, missing ones are added.') }}"
                    wire:loading.attr="disabled"
                    wire:target="importFromLegacy"
                />
            </div>
        </div>

        @switch ($activeTab)
            @case ('permissions')
                @livewire('modules.app-settings.components.acl-permissions', [], key('acl-permissions-' . $DOMupdateKey))
                @break
            @case ('users')
                @livewire('modules.app-settings.components.acl-users', [], key('acl-users-' . $DOMupdateKey))
                @break
            @default
                @livewire('modules.app-settings.components.acl-roles', [], key('acl-roles-' . $DOMupdateKey))
        @endswitch
    </x-ui.card>
</div>
