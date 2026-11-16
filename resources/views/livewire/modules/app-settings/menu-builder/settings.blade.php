{{-- Settings of the selected item. Every field is saved when it changes (text fields on blur). --}}
@php $type = $form['type'] ?? $selected->type; @endphp
<div wire:key="mb-settings-{{ $selected->id }}">
    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge text-bg-primary"><i class="bi bi-{{ $types[$type]['icon'] }}"></i> {{ translator($types[$type]['label']) }}</span>
        <span class="text-muted small">#{{ $selected->id }}</span>
        <button type="button" class="btn btn-sm btn-link ms-auto p-0 text-muted" wire:click="clearSelection" title="{{ translator('Close') }}"><i class="bi bi-x-lg"></i></button>
    </div>

    @foreach ($problems as $problem)
        <div class="alert alert-warning py-1 px-2 small mb-2"><i class="bi bi-exclamation-triangle"></i> {{ $problem }}</div>
    @endforeach

    @if ($type !== 'divider')
        <div class="mb-3">
            <label class="form-label small fw-bold mb-1">{{ translator('Title') }}</label>
            <input type="text" class="form-control form-control-sm" wire:model.blur="form.title" maxlength="100">
            <div class="form-text">{{ translator('Translated with the app translations.') }}</div>
        </div>
    @endif

    @if (in_array($type, ['link', 'group'], TRUE))
        <div class="mb-3" x-data="{ pick: false }">
            <label class="form-label small fw-bold mb-1">{{ translator('Icon') }}</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text" style="width: 2.2rem; justify-content: center"><i class="bi bi-{{ $form['icon'] ?: 'dash' }}"></i></span>
                <input type="text" class="form-control font-monospace" placeholder="{{ translator('e.g. people') }}" wire:model.blur="form.icon" maxlength="60">
                <button type="button" class="btn btn-light gray-border-color" x-on:click="pick = !pick" title="{{ translator('Pick an icon') }}"><i class="bi bi-grid-3x3-gap"></i></button>
            </div>
            <div class="border p-1 mt-1" x-show="pick" style="display: none">
                <div class="mb-icon-grid">
                    <button type="button" wire:click="pickIcon('')" title="{{ translator('No icon') }}"><i class="bi bi-slash-circle text-muted"></i></button>
                    @foreach ($icons as $icon)
                        <button type="button" @class(['is-picked' => ($form['icon'] ?? NULL) === $icon]) wire:click="pickIcon('{{ $icon }}')" title="{{ $icon }}"><i class="bi bi-{{ $icon }}"></i></button>
                    @endforeach
                </div>
                <div class="form-text">{{ translator('Any Bootstrap icon name can be typed') }}: <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener noreferrer">icons.getbootstrap.com</a></div>
            </div>
        </div>
    @endif

    @if ($type === 'link')
        <div class="mb-3">
            <label class="form-label small fw-bold mb-1">{{ translator('Link to') }}</label>
            <div class="btn-group btn-group-sm w-100 mb-2" role="group">
                <input type="radio" class="btn-check" id="mb-kind-route" value="route" wire:model.live="form.link_kind">
                <label class="btn btn-outline-primary" style="border-radius: 0" for="mb-kind-route"><i class="bi bi-signpost"></i> {{ translator('Route') }}</label>
                <input type="radio" class="btn-check" id="mb-kind-url" value="url" wire:model.live="form.link_kind">
                <label class="btn btn-outline-primary" style="border-radius: 0" for="mb-kind-url"><i class="bi bi-globe"></i> {{ translator('URL') }}</label>
            </div>
            @if (($form['link_kind'] ?? 'route') === 'route')
                <input type="text" class="form-control form-control-sm font-monospace" list="mb-routes" placeholder="{{ translator('Route name') }}" wire:model.blur="form.route_name" wire:key="mb-route-input">
                <div class="form-text">
                    @if ($form['route_name'] && isset($linkableRoutes[$form['route_name']]))
                        <i class="bi bi-link-45deg"></i> <span class="font-monospace">{{ $linkableRoutes[$form['route_name']] }}</span>
                    @else
                        {{ translator('Named GET routes without parameters.') }}
                    @endif
                </div>
            @else
                <input type="text" class="form-control form-control-sm font-monospace" placeholder="https://… {{ translator('or') }} /path" wire:model.blur="form.url" maxlength="2048" wire:key="mb-url-input">
                <div class="form-text">{{ translator('http://, https:// or a page of this app starting with /.') }}</div>
            @endif
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="mb-new-tab" wire:model.live="form.new_tab">
                <label class="form-check-label small" for="mb-new-tab">{{ translator('Open in a new tab') }}</label>
            </div>
        </div>
    @endif

    @if ($type !== 'divider')
        <div class="mb-3">
            <label class="form-label small fw-bold mb-1">{{ translator('Extra permission') }}</label>
            <input type="text" class="form-control form-control-sm font-monospace" list="mb-permissions" placeholder="{{ translator('none') }}" wire:model.blur="form.permission">
            <div class="form-text">
                @php $routePermission = ($form['route_name'] ?? NULL) ? (\App\Services\Application\Acl\AclService::routePermissions()[$form['route_name']] ?? NULL) : NULL; @endphp
                @if ($type === 'link' && $routePermission)
                    {{ translator('The route already needs') }} <code>{{ $routePermission }}</code>.
                @elseif ($type === 'link')
                    {{ translator('The route has no permission, every logged in user may open it.') }}
                @endif
                {{ translator('Users also need this permission to see the item.') }}
            </div>
        </div>
    @endif

    <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="mb-active" wire:model.live="form.active">
        <label class="form-check-label small" for="mb-active">{{ translator('Active') }} <span class="text-muted">({{ translator('switched off items are hidden with everything inside') }})</span></label>
    </div>

    <div class="d-flex gap-2 border-top pt-3">
        <x-ui.btn type="lig.sm" icon="copy" text="{{ translator('Duplicate') }}" action="duplicateItem" param="{{ $selected->id }}" />
        <x-ui.btn
            type="lig.sm"
            icon="trash"
            text="{{ translator('Delete') }}"
            action="deleteItem"
            param="{{ $selected->id }}"
            wire:confirm="{{ translator('Delete the item') }}{{ $selected->isGroup() ? ' ' . translator('with everything inside it') : '' }}?"
        />
    </div>
</div>
