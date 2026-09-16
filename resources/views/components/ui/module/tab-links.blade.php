<ul class="nav nav-tabs">
    @foreach ($routes as $routeName => $link)
        <x-ui.module.tab-link-item :routeName=$routeName :title="$link['title']" :specialIndexIcon=$specialIndexIcon />
        @if ($link['divider_after'] && !$loop->last)
            <x-ui.module.tab-link-divider />
        @endif
    @endforeach
</ul>
