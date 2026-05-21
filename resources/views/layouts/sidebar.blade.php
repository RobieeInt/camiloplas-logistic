@php
    use Illuminate\Support\Facades\Route;

    $menuService = app(\App\Services\System\MenuService::class);
    $menus = $menuService->getSidebarMenus(auth()->id());
@endphp

<aside class="bg-dark text-white min-vh-100" style="width: 270px;">
    <div class="p-3 border-bottom border-secondary">
        <h5 class="mb-0"> Logistic</h5>
        <small class="text-secondary">Warehouse Flow System</small>
    </div>

    <ul class="nav flex-column p-3 gap-1">
        @foreach ($menus as $menu)
            @php
                $children = $menu->children ?? [];
                $hasChildren = count($children) > 0;

                $menuUrl = '#';

                if (!empty($menu->route) && Route::has($menu->route)) {
                    $menuUrl = route($menu->route);
                }
            @endphp

            @if ($hasChildren)
                <li class="nav-item text-uppercase small text-secondary mt-3 mb-1">
                    {{ $menu->name }}
                </li>

                @foreach ($children as $child)
                    @php
                        $childUrl = '#';

                        if (!empty($child->route) && Route::has($child->route)) {
                            $childUrl = route($child->route);
                        }
                    @endphp

                    <li class="nav-item">
                        <a href="{{ $childUrl }}" class="nav-link text-white rounded">
                            <i class="{{ $child->icon ?: 'bi bi-circle' }} me-2"></i>
                            {{ $child->name }}
                        </a>
                    </li>
                @endforeach
            @else
                <li class="nav-item">
                    <a href="{{ $menuUrl }}" class="nav-link text-white rounded">
                        <i class="{{ $menu->icon ?: 'bi bi-circle' }} me-2"></i>
                        {{ $menu->name }}
                    </a>
                </li>
            @endif
        @endforeach
    </ul>
</aside>
