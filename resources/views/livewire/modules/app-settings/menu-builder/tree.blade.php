{{-- One level of the menu builder structure, included recursively for the children of a group --}}
@foreach ($nodes as $node)
    @php
        $id = $node['id'];
        $isGroup = $node['type'] === 'group';
        $previous = $nodes[$loop->index - 1] ?? NULL;
        $canIndent = $previous && $previous['type'] === 'group' && $node['depth'] + ($isGroup ? 2 : 1) <= $maxDepth;
        $typeInfo = $types[$node['type']];
    @endphp
    <div wire:key="mb-item-{{ $id }}">
        <div
            @class([
                'mb-row',
                'is-selected' => $selected && $selected->id === $id,
                'is-inactive' => !$node['active'],
            ])
            :class="{
                'is-dragging': dragId === {{ $id }},
                'drop-before': overId === {{ $id }} && zone === 'before',
                'drop-after': overId === {{ $id }} && zone === 'after',
                'drop-inside': overId === {{ $id }} && zone === 'inside',
            }"
            draggable="true"
            x-on:dragstart.stop="start($event, {{ $id }})"
            x-on:dragover="over($event, {{ $id }}, {{ $isGroup ? 'true' : 'false' }})"
            x-on:drop.stop="drop($event, {{ $id }})"
            wire:click="selectItem({{ $id }})"
        >
            <div class="mb-row-main">
                <i class="bi bi-grip-vertical mb-handle" title="{{ translator('Drag to move') }}"></i>

                @if ($node['type'] === 'divider')
                    <span class="text-muted small text-uppercase" style="letter-spacing: .05em">{{ translator('Divider') }}</span>
                    <span class="flex-fill border-top"></span>
                @else
                    <i class="bi bi-{{ $node['icon'] ?: $typeInfo['icon'] }} @if (!$node['icon']) text-muted @endif" style="width: 1.1rem; text-align: center"></i>
                    <div class="d-flex flex-column" style="min-width: 0">
                        <span @class(['mb-row-title', 'text-uppercase small fw-bold text-muted' => $node['type'] === 'header'])>{{ $node['title'] }}</span>
                        @if ($node['type'] === 'link')
                            <span class="mb-row-sub font-monospace">
                                @if ($node['route_name']) {{ $node['route_name'] }}
                                @elseif ($node['url']) {{ $node['url'] }}
                                @else <i>{{ translator('no target') }}</i>
                                @endif
                            </span>
                        @endif
                    </div>
                @endif

                <div class="d-flex align-items-center gap-1 ms-auto flex-shrink-0">
                    @if ($isGroup)
                        <span class="badge text-bg-light border">{{ count($node['children']) }}</span>
                    @endif
                    @if ($node['permission'])
                        <i class="bi bi-shield-lock {{ $node['own_permission'] ? 'text-primary' : 'text-muted' }}" title="{{ translator('Permission') }}: {{ $node['permission'] }}{{ $node['own_permission'] ? '' : ' (' . translator('from the route') . ')' }}"></i>
                    @endif
                    @if ($node['new_tab'])
                        <i class="bi bi-box-arrow-up-right text-muted" title="{{ translator('Opens in a new tab') }}"></i>
                    @endif
                    @if (!$node['active'])
                        <span class="badge text-bg-secondary">{{ translator('off') }}</span>
                    @endif
                    @if ($node['problems'])
                        <i class="bi bi-exclamation-triangle-fill text-warning" title="{{ implode("\n", $node['problems']) }}"></i>
                    @endif
                </div>
            </div>

            <div class="mb-row-actions" x-on:click.stop>
                <button type="button" wire:click="moveUp({{ $id }})" @disabled($loop->first) title="{{ translator('Move up') }}"><i class="bi bi-arrow-up"></i></button>
                <button type="button" wire:click="moveDown({{ $id }})" @disabled($loop->last) title="{{ translator('Move down') }}"><i class="bi bi-arrow-down"></i></button>
                <button type="button" wire:click="outdent({{ $id }})" @disabled($parentId === NULL) title="{{ translator('Move out of the group') }}"><i class="bi bi-text-indent-right"></i></button>
                <button type="button" wire:click="indent({{ $id }})" @disabled(!$canIndent) title="{{ translator('Move into the group above') }}"><i class="bi bi-text-indent-left"></i></button>
                <button type="button" wire:click="toggleActive({{ $id }})" title="{{ $node['active'] ? translator('Switch off') : translator('Switch on') }}"><i class="bi bi-{{ $node['active'] ? 'eye' : 'eye-slash' }}"></i></button>
                <button type="button" wire:click="duplicateItem({{ $id }})" title="{{ translator('Duplicate') }}"><i class="bi bi-copy"></i></button>
                <button
                    type="button"
                    wire:click="deleteItem({{ $id }})"
                    @if ($isGroup && count($node['children'])) wire:confirm="{{ translator('Delete the group with everything inside it?') }}" @endif
                    title="{{ translator('Delete') }}"
                ><i class="bi bi-trash text-danger"></i></button>
            </div>
        </div>

        @if ($isGroup)
            <div class="mb-children">
                @if (count($node['children']))
                    @include('livewire.modules.app-settings.menu-builder.tree', ['nodes' => $node['children'], 'parentId' => $id])
                @else
                    <div class="mb-children-empty">{{ translator('Empty group, drop items here or select it and add items.') }}</div>
                @endif
            </div>
        @endif
    </div>
@endforeach
