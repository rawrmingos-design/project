@php
    $onboardingCssVersion = file_exists(public_path('assets/admin/onboarding-guide.css'))
        ? filemtime(public_path('assets/admin/onboarding-guide.css'))
        : time();

    $onboardingJsVersion = file_exists(public_path('assets/admin/onboarding-guide.js'))
        ? filemtime(public_path('assets/admin/onboarding-guide.js'))
        : time();

    $guideBadge = $guideBadge ?? 'Panduan Admin Panel';
    $guideTitle = $guideTitle ?? 'Kenali area penting halaman ini';
    $guideDescription = $guideDescription ?? 'Panduan singkat ini membantu admin memahami area utama yang paling sering dipakai.';
    $guideHighlights = $guideHighlights ?? [];
@endphp

<link rel="stylesheet" href="{{ asset('assets/admin/onboarding-guide.css') }}?v={{ $onboardingCssVersion }}">

<div data-onboarding-guide>
    <div class="admin-onboarding-modal" data-onboarding-welcome>
        <div class="admin-onboarding-modal__backdrop"></div>
        <div class="admin-onboarding-modal__panel" role="dialog" aria-modal="true" aria-labelledby="admin-onboarding-title">
            <div class="admin-onboarding-modal__badge">{{ $guideBadge }}</div>
            <h2 id="admin-onboarding-title" class="admin-onboarding-modal__title">{{ $guideTitle }}</h2>
            <p class="admin-onboarding-modal__body">
                {{ $guideDescription }}
            </p>

            @if ($guideHighlights !== [])
                <ul class="admin-onboarding-modal__list">
                    @foreach ($guideHighlights as $highlight)
                        <li>{{ $highlight }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="admin-onboarding-modal__actions">
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--ghost" data-onboarding-dismiss>
                    Lupakan Saat Ini
                </button>
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--ghost" data-onboarding-close>
                    Tutup
                </button>
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--primary" data-onboarding-start>
                    Mulai Panduan
                </button>
            </div>
        </div>
    </div>

    <div class="admin-onboarding-tour" data-onboarding-tour hidden>
        <div class="admin-onboarding-tour__backdrop"></div>
        <div class="admin-onboarding-tour__spotlight" data-onboarding-spotlight hidden></div>
        <div class="admin-onboarding-tour__tooltip" data-onboarding-tooltip>
            <div class="admin-onboarding-tour__step" data-onboarding-step-label></div>
            <h3 class="admin-onboarding-tour__title" data-onboarding-title></h3>
            <p class="admin-onboarding-tour__description" data-onboarding-description></p>
            <div class="admin-onboarding-tour__actions">
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--ghost" data-onboarding-prev>
                    Sebelumnya
                </button>
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--ghost" data-onboarding-finish>
                    Selesai
                </button>
                <button type="button" class="admin-onboarding-btn admin-onboarding-btn--primary" data-onboarding-next>
                    Berikutnya
                </button>
            </div>
        </div>
    </div>

    <script type="application/json" data-onboarding-targets-payload>
    {!! json_encode($onboardingTargets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script type="application/json" data-onboarding-steps-payload>
    {!! json_encode($onboardingSteps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</div>

<script src="{{ asset('assets/admin/onboarding-guide.js') }}?v={{ $onboardingJsVersion }}" defer></script>
