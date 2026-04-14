@if(($config->live_sales_enabled ?? true))
    @include('template.id.partials.live-sales-toast')
@endif
