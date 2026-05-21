<nav class="navbar navbar-expand-lg bg-white border-bottom px-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h6">Logistic Module</span>

        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                {{ auth()->user()->name }}
            </button>

            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit">Logout</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
