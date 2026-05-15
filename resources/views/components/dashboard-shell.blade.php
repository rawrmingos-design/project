@props([
    'pageClass' => '',
    'mainClass' => '',
    'headerTitle' => null,
    'headerDescription' => null,
    'headerClass' => '',
])

<section class="public-dashboard-page {{ trim((string) $pageClass) }}">
    <div class="public-shell">
        <div class="public-dashboard">
            @include('components.sidebar-dashboard')

            <main class="public-dashboard-main {{ trim((string) $mainClass) }}">
                @if(filled($headerTitle) || filled($headerDescription))
                    <header class="public-dashboard-page-header {{ trim((string) $headerClass) }}">
                        @if(filled($headerTitle))
                            <h1>{{ $headerTitle }}</h1>
                        @endif
                        @if(filled($headerDescription))
                            <p>{{ $headerDescription }}</p>
                        @endif
                    </header>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</section>
