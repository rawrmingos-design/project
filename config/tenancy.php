<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SaaS Tenancy Kill Switch
    |--------------------------------------------------------------------------
    |
    | Keep SaaS tenant storefronts and self-service onboarding disabled unless
    | a deployment explicitly enables them. SUSPENDED_TENANT is the deployment
    | flag; SAAS_TENANCY_DISABLED is also accepted as a clearer alias.
    |
    */

    'disabled' => env('SUSPENDED_TENANT', env('SAAS_TENANCY_DISABLED', true)),

];
