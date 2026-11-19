<div class="position-relative">
    <style>
        .um-root .um-password { font-size: 1.1rem; letter-spacing: .05em; }
    </style>

    <div class="um-root" wire:loading.class="opacity-50" wire:target="save, setActive, resetPassword, resetForm">
        {{-- Who --}}
        <div class="d-flex align-items-center gap-2 mb-3">
            @if ($user)
                <i class="bi bi-person-circle fs-4"></i>
                <div>
                    <div class="fw-semibold">
                        {{ $user->name }} {{ $user->last_name }}
                        @if ($user->active)
                            <span class="badge text-bg-success no-border-radius">{{ translator('Active') }}</span>
                        @else
                            <span class="badge text-bg-secondary no-border-radius">{{ translator('Inactive') }}</span>
                        @endif
                        @if ($isSelf) <span class="badge text-bg-light border no-border-radius">{{ translator('You') }}</span> @endif
                    </div>
                    <div class="small text-muted">
                        #{{ $user->id }}
                        · {{ translator('Created') }}: {{ $user->created_at?->format('d.m.Y') ?? '–' }}
                        · {{ translator('Last login') }}: {{ $user->last_login_at?->format('d.m.Y H:i') ?? translator('never') }}
                    </div>
                </div>
            @else
                <i class="bi bi-person-plus fs-4"></i>
                <div class="fw-semibold">{{ translator('New user') }}</div>
            @endif
        </div>

        {{-- The password that was just set, shown once --}}
        @if ($shownPassword)
            <div class="alert alert-warning no-border-radius py-2">
                <div class="small mb-1">{{ translator('The password is shown only now. Give it to the user, they can change it after signing in.') }}</div>
                <div class="d-flex align-items-center gap-2" x-data="{ copied: false }">
                    <code class="um-password text-body bg-white border px-2 py-1 flex-fill">{{ $shownPassword }}</code>
                    <button type="button" class="btn btn-light btn-sm border no-border-radius" title="{{ translator('Copy') }}"
                        x-on:click="navigator.clipboard.writeText(@js($shownPassword)); copied = true; setTimeout(() => copied = false, 1500)">
                        <i class="bi" :class="copied ? 'bi-check-lg' : 'bi-clipboard'"></i>
                    </button>
                    <button type="button" class="btn btn-light btn-sm border no-border-radius" wire:click="hidePassword" title="{{ translator('Hide') }}"><i class="bi bi-eye-slash"></i></button>
                </div>
            </div>
        @endif

        @if ($user && !$canManage)
            <div class="alert alert-info no-border-radius py-2 small">{{ translator('Only a super admin can change a super admin.') }}</div>
        @elseif ($isSuperAdmin)
            <div class="alert alert-info no-border-radius py-2 small">{{ translator('This user is a super admin and may do everything, the roles do not limit them.') }}</div>
        @endif

        @php($readOnly = $user ? (!$can['edit'] || !$canManage) : !$can['create'])

        <div class="row g-4">
            {{-- Details --}}
            <div class="col-md-7">
                <form wire:submit="save" class="d-flex flex-column gap-2">
                    <fieldset @disabled($readOnly) class="d-flex flex-column gap-2">
                        <div class="row g-2">
                            <div class="col">
                                <label class="small">{{ translator('First name') }} *</label>
                                <input type="text" class="form-control form-control-sm no-border-radius" wire:model="form.name" maxlength="255" @if (!$user) autofocus @endif>
                            </div>
                            <div class="col">
                                <label class="small">{{ translator('Last name') }}</label>
                                <input type="text" class="form-control form-control-sm no-border-radius" wire:model="form.last_name" maxlength="255">
                            </div>
                        </div>
                        <div>
                            <label class="small">{{ translator('Email') }} *</label>
                            <input type="email" class="form-control form-control-sm no-border-radius" wire:model="form.email" maxlength="255" autocomplete="off">
                        </div>
                        <div class="row g-2">
                            <div class="col">
                                <label class="small">{{ translator('User type') }} *</label>
                                <select class="form-select form-select-sm no-border-radius" wire:model="form.type">
                                    @foreach ($assignableTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-4">
                                <label class="small">{{ translator('Language') }}</label>
                                <select class="form-select form-select-sm no-border-radius" wire:model="form.lang">
                                    @foreach ($languages as $code)
                                        <option value="{{ $code }}">{{ strtoupper($code) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="small">{{ translator('Linked worker') }}</label>
                            <select class="form-select form-select-sm no-border-radius" wire:model.live="form.link">
                                <option value="">– {{ translator('Not linked') }} –</option>
                                @if ($linkOptions['workers'])
                                    <optgroup label="{{ translator('Workers') }}">
                                        @foreach ($linkOptions['workers'] as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if ($linkOptions['coops'])
                                    <optgroup label="{{ translator('Subcontractor workers') }}">
                                        @foreach ($linkOptions['coops'] as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                            <div class="small text-muted">{{ translator('Only active workers without a user are listed.') }}</div>
                        </div>

                        @if (!$user)
                            <div>
                                <label class="small">{{ translator('Password') }}</label>
                                <input type="text" class="form-control form-control-sm no-border-radius" wire:model="customPassword" autocomplete="new-password"
                                    placeholder="{{ translator('Empty = generate a password') }} ({{ translator('min.') }} {{ $minPasswordLength }})">
                            </div>
                        @endif
                    </fieldset>

                    @if (!$readOnly)
                        <div class="d-flex gap-2 mt-1">
                            <button type="submit" class="btn btn-primary btn-sm shadow no-border-radius">
                                {{ $user ? translator('Save') : translator('Create the user') }}
                            </button>
                            <button type="button" class="btn btn-light btn-sm shadow gray-border-color no-border-radius" wire:click="resetForm" title="{{ translator('Undo the unsaved changes') }}">
                                {{ translator('Reset') }}
                            </button>
                        </div>
                    @endif
                </form>
            </div>

            {{-- Roles --}}
            <div class="col-md-5">
                @if ($can['roles'] && $canManage)
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <b>{{ translator('Roles') }}</b>
                        <small class="text-muted">{{ $user ? translator('saved right away') : translator('saved with the user') }}</small>
                        @if ($can['acl'] && $user)
                            <a href="{{ route('getAclModule') }}" class="ms-auto small" title="{{ translator('Per-permission overrides are set in Access control - User access') }}">{{ translator('Permissions') }} <i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                    </div>
                    <div class="d-flex flex-column gap-1" style="max-height: 260px; overflow-y: auto">
                        @forelse ($roles as $role)
                            <div class="form-check" wire:key="um-role-{{ $role->id }}">
                                <input class="form-check-input" type="checkbox" id="umr{{ $role->id }}" wire:model.live="userRoles.{{ $role->id }}">
                                <label class="form-check-label @if (!$role->active) text-muted text-decoration-line-through @endif" for="umr{{ $role->id }}" title="{{ $role->description ?: $role->key }}">{{ $role->name }}</label>
                            </div>
                        @empty
                            <i class="small text-muted">{{ translator('No roles yet!') }}</i>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>

        @if ($user && $canManage && ($can['password'] || $can['status']))
            <hr>
            <div class="row g-4">
                {{-- Password --}}
                <div class="col-md-7">
                    @if ($can['password'])
                        <b>{{ translator('Password') }}</b>
                        <div class="d-flex gap-2 mt-2">
                            <input type="text" class="form-control form-control-sm no-border-radius" wire:model="customPassword" autocomplete="new-password"
                                placeholder="{{ translator('Empty = generate a password') }} ({{ translator('min.') }} {{ $minPasswordLength }})">
                            <button type="button" class="btn btn-warning btn-sm shadow no-border-radius text-nowrap" wire:click="resetPassword"
                                wire:confirm="{{ translator('Set a new password? The old one stops working and the user is signed out of remembered devices.') }}">
                                <i class="bi bi-key"></i> {{ translator('Set') }}
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Status --}}
                <div class="col-md-5">
                    @if ($can['status'])
                        <b>{{ translator('Status') }}</b>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            @if ($user->active)
                                @if (!$isSelf)
                                    <button type="button" class="btn btn-danger btn-sm shadow no-border-radius" wire:click="setActive(0)"
                                        wire:confirm="{{ translator('Deactivate the user? They can not sign in any more and are signed out on their next request.') }}">
                                        <i class="bi bi-person-slash"></i> {{ translator('Deactivate') }}
                                    </button>
                                @else
                                    <small class="text-muted">{{ translator('You can not deactivate yourself.') }}</small>
                                @endif
                            @else
                                <button type="button" class="btn btn-success btn-sm shadow no-border-radius" wire:click="setActive(1)">
                                    <i class="bi bi-person-check"></i> {{ translator('Activate') }}
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
