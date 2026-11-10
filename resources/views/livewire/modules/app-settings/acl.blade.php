<div class="px-3 flex-fill h-100 d-flex flex-column" style="min-height: 85vh !important" id='module_container'>
    <x-ui.card :border=FALSE :noBodyPadding=TRUE class="h-100 d-flex flex-column">
        <div class="d-flex align-items-center gap-2 mb-3">
            <b>{{ translator('Access control') }}</b>
            <x-v-divider class="align-self-stretch" />
            <x-ui.nav-tabs :tabs=$tabs :selectedTab=$activeTab py=1 />
            <div class="ms-auto d-flex gap-2">
                {{-- Local: export the ACL into its sync file / other envs: install the sync file --}}
                @if ($isLocalEnv)
                    <x-ui.btn
                        type="lig.sm"
                        icon="box-arrow-up"
                        action="exportAcl"
                        title="{{ translator('Export permissions and roles to the ACL sync file') }}"
                        wire:loading.attr="disabled"
                        wire:target="exportAcl"
                    />
                @else
                    <x-ui.btn
                        type="lig.sm"
                        icon="arrow-repeat"
                        action="syncAclFromFile"
                        :disabled="$syncFileDate === NULL"
                        title="{{ $syncFileDate ? translator('Sync permissions and roles from the ACL sync file') . ' (' . $syncFileDate . ')' : translator('The ACL sync file does not exist') }}"
                        wire:confirm="{{ translator('Sync the permissions and roles from the file? Existing ones are updated, new ones are created, nothing is deleted.') }}"
                        wire:loading.attr="disabled"
                        wire:target="syncAclFromFile"
                    />
                @endif
                {{-- Rebuild the ACL so it matches the last sync file exactly --}}
                <x-ui.btn
                    type="lig.sm"
                    icon="box-arrow-in-down"
                    text="{{ translator('Import') }}"
                    action="rebuildAclFromFile"
                    :disabled="$syncFileDate === NULL"
                    title="{{ $syncFileDate ? translator('Rebuild the ACL from the sync file') . ' (' . $syncFileDate . ')' : translator('The ACL sync file does not exist') }}"
                    wire:confirm="{{ translator('Rebuild the ACL from the sync file? Permissions and roles that are not in the file are DELETED (with their user assignments), and every role gets exactly the permissions of the file. User roles of the roles that stay are kept.') }}"
                    wire:loading.attr="disabled"
                    wire:target="rebuildAclFromFile"
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
