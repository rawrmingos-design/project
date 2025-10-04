@extends('template.template')

@yield('css')


<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css">
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
<script>hljs.highlightAll();</script>

<style>
    .bg-get {
    background-color: #3150ff;
}
    .bg-post {
    background-color: #f47b1f;
}
</style>
@section('content')
@include('../navbar')
<style>
        .json-container {
            background: #272822;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.5;
        }
        .json-key {
            color: #66d9ef;
        }
        .json-string {
            color: #a6e22e;
        }
        .json-number {
            color: #e6db74;
        }
        .json-boolean {
            color: #f92672;
        }
    </style>

<main class="relative bg-gradient-theme">
    <div class="pt-5">
        <div class="container min-h-dvh">
            <div class="flex flex-col gap-8">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-primary-foreground hover:bg-primary/80 rounded-md">Version 1.9.2</div>
                    <div
                        class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-primary text-primary-foreground hover:bg-primary/80 rounded-md"
                    >
                    </div>
                    <h1 class="pt-4 text-xl font-semibold uppercase">Documentation API {{ ENV('APP_NAME') }}</h1>
<p class="pt-2 text-sm font-medium">
    Selamat datang di dokumentasi integrasi API {{ ENV('APP_NAME') }}. Panduan ini akan membantu Anda memahami cara mengintegrasikan layanan kami dengan mudah dan efisien.
</p>

                </div>
                <hr />
          <div class="max-w-3xl">
    <h2 class="text-lg font-semibold">Getting Started</h2>
    <p class="pt-2 text-sm font-medium">
        Untuk memulai integrasi, tersedia satu metode, yaitu melalui API dengan menggunakan metode POST. Anda memerlukan API TOKEN dan IP Whitelist untuk dapat mengakses layanan ini.
    </p>
</div>
<div class="max-w-3xl">
    <h2 class="text-lg font-semibold">Authorization</h2>
    <p class="pt-2 text-sm font-medium">
        - TOKEN API dapat diperoleh dari Administrator {{ ENV('APP_NAME') }} untuk memverifikasi identitas Anda.
    </p>
    <p class="pt-2 text-sm font-medium">
        - Untuk menambahkan IP SERVER ke whitelist, silakan hubungi Administrator {{ ENV('APP_NAME') }}.
    </p>
</div>

            </div>
            <div class="flex flex-col">
                <div class="flex w-full flex-col gap-3 pt-16"><BR>
                    <h2 class="font-semibold">API</h2>
                    
                    
<div x-data="{ open: false }" class="mx-auto w-full rounded-2xl bg-muted/50 p-4">
    <!-- Button Section -->
    <button
        class="flex w-full justify-between rounded-lg bg-muted px-1 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-muted/75 focus:outline-none focus-visible:ring focus-visible:ring-muted/75"
        type="button"
        @click="open = !open"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-12">
            <!-- Method and Endpoint Section -->
            <div>
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-post text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    POST
                </div>&nbsp;&nbsp;
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    BALANCE 
                </div>
                
            </div>
        </div>
        <!-- Arrow Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" :class="{'rotate-180': open}" class="transform h-5 w-5 text-muted-foreground transition-transform duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
        </svg>
    </button>

    <!-- Content Section (Responsive Grid Layout) -->
    <div x-show="open" x-transition class="grid grid-cols-1 gap-3 px-1 pb-1 pt-4 md:grid-cols-2 lg:grid-cols-2">
        <!-- Left Column (Query Parameters, Headers, and Request Body) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Query Parameters -->
            <div>
                <div class="font-semibold">Endpoint</div>
                <div class="text-muted">/api/v1/balance</div>
            </div>

            <!-- Headers Section -->
            <div>
                <div class="font-semibold">Headers</div>
                <div class="grid gap-2">
                    <div class="font-mono font-semibold">Content-Type: application/json</div>
                    <div class="font-mono font-semibold">Authorization: Bearer {API_TOKEN}</div>
                </div>
            </div>

            <!-- Request Body -->
            <div>
                <div class="font-semibold">Request Body</div>
                <div class="text-muted">No Request Body</div>
            </div>
        </div>

        <!-- Right Column (Request and Response) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Request Section -->
            <div>
                <div class="font-semibold">Request</div>
                <div class="relative">
                    <pre class="mt-2 whitespace-pre-wrap break-words rounded-xl bg-muted/50 p-3 text-muted-foreground overflow-x-auto">
                        <p>curl -X POST "{BASE_URL}/api/v1/balance" </p>
                        <p>-H "Authorization: Bearer {API_TOKEN}"  </p>
                        <p>-H "Content-Type: application/json" </p>
                    </pre>
                </div>
            </div>

            <!-- Response Section -->
            <div>
                <div class="font-semibold">Response</div>
                <div class="relative">
                  <div class="json-container">
        <div>{</div>
        <div>&nbsp;&nbsp;<span class="json-key">"error"</span>: <span class="json-boolean">false</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-number">200</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"message"</span>: <span class="json-string">"Success"</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"data"</span>: {</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"name"</span>: <span class="json-string">"{{ ENV('APP_NAME') }}"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"telp"</span>: <span class="json-string">"62xxxxxx"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"email"</span>: <span class="json-string">"fahmiaksannugroho@gmail.com"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"membership"</span>: <span class="json-string">"Gold"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"balance"</span>: <span class="json-number">0</span></div>
        <div>&nbsp;&nbsp;}</div>
        <div>}</div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div x-data="{ open: false }" class="mx-auto w-full rounded-2xl bg-muted/50 p-4">
    <!-- Button Section -->
    <button
        class="flex w-full justify-between rounded-lg bg-muted px-1 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-muted/75 focus:outline-none focus-visible:ring focus-visible:ring-muted/75"
        type="button"
        @click="open = !open"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-12">
            <!-- Method and Endpoint Section -->
            <div>
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-post text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    POST
                </div>&nbsp;&nbsp;
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    Product
                </div>
                
            </div>
        </div>
        <!-- Arrow Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" :class="{'rotate-180': open}" class="transform h-5 w-5 text-muted-foreground transition-transform duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
        </svg>
    </button>

    <!-- Content Section (Responsive Grid Layout) -->
    <div x-show="open" x-transition class="grid grid-cols-1 gap-3 px-1 pb-1 pt-4 md:grid-cols-2 lg:grid-cols-2">
        <!-- Left Column (Query Parameters, Headers, and Request Body) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Query Parameters -->
            <div>
                <div class="font-semibold">Endpoint</div>
                <div class="text-muted">/api/v1/product</div>
            </div>

            <!-- Headers Section -->
            <div>
                <div class="font-semibold">Headers</div>
                <div class="grid gap-2">
                    <div class="font-mono font-semibold">Content-Type: application/json</div>
                    <div class="font-mono font-semibold">Authorization: Bearer {API_TOKEN}</div>
                </div>
            </div>

            <!-- Request Body -->
            <div>
                <div class="font-semibold">Request Body</div>
                <div class="text-muted">No Request Body</div>
            </div>
        </div>

        <!-- Right Column (Request and Response) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Request Section -->
            <div>
                <div class="font-semibold">Request</div>
                <div class="relative">
                    <pre class="mt-2 whitespace-pre-wrap break-words rounded-xl bg-muted/50 p-3 text-muted-foreground overflow-x-auto">
                        <code class="language-bash">
                        curl -X POST "{BASE_URL}/api/v1/product" -H "Authorization: Bearer {API_TOKEN}" -H "Content-Type: application/json"
                        </code>
                    </pre>
                </div>
            </div>

            <!-- Response Section -->
            <div>
                <div class="font-semibold">Response</div>
                <div class="relative">
                    <div class="json-container">
        <div>{</div>
        <div>&nbsp;&nbsp;<span class="json-key">"error"</span>: <span class="json-boolean">false</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-number">200</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"message"</span>: <span class="json-string">"Success"</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"data"</span>: [</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;{</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-string">"mobile-legends"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"name"</span>: <span class="json-string">"Mobile Legends"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"is_active"</span>: <span class="json-boolean">true</span></div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;}</div>
        <div>&nbsp;&nbsp;]</div>
        <div>}</div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div x-data="{ open: false }" class="mx-auto w-full rounded-2xl bg-muted/50 p-4">
    <!-- Button Section -->
    <button
        class="flex w-full justify-between rounded-lg bg-muted px-1 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-muted/75 focus:outline-none focus-visible:ring focus-visible:ring-muted/75"
        type="button"
        @click="open = !open"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-12">
            <!-- Method and Endpoint Section -->
            <div>
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-post text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    POST
                </div>&nbsp;
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    List Variant
                </div>
                
            </div>
        </div>
        <!-- Arrow Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" :class="{'rotate-180': open}" class="transform h-5 w-5 text-muted-foreground transition-transform duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
        </svg>
    </button>

    <!-- Content Section (Responsive Grid Layout) -->
    <div x-show="open" x-transition class="grid grid-cols-1 gap-3 px-1 pb-1 pt-4 md:grid-cols-2 lg:grid-cols-2">
        <!-- Left Column (Query Parameters, Headers, and Request Body) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Query Parameters -->
            <div>
                <div class="font-semibold">Endpoint</div>
                <div class="text-muted">/api/v1/variant</div>
            </div>

            <!-- Headers Section -->
            <div>
                <div class="font-semibold">Headers</div>
                <div class="grid gap-2">
                    <div class="font-mono font-semibold">Content-Type: application/json</div>
                    <div class="font-mono font-semibold">Authorization: Bearer {API_TOKEN}</div>
                </div>
            </div>

            <!-- Request Body -->
            <div>
                <div class="font-semibold">Request Body</div>
                <div class="text-muted">{ "code": "string" } </div>
            </div>
        </div>

        <!-- Right Column (Request and Response) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Request Section -->
            <div>
                <div class="font-semibold">Request</div>
                <div class="relative">
                    <pre class="mt-2 whitespace-pre-wrap break-words rounded-xl bg-muted/50 p-3 text-muted-foreground overflow-x-auto">
                        <code class="language-bash">
                        curl -X POST "{BASE_URL}/api/v1/variant" -H "Authorization: Bearer {API_TOKEN}" -H "Content-Type: application/json"
                        </code>
                    </pre>
                </div>
            </div>

            <!-- Response Section -->
            <div>
                <div class="font-semibold">Response</div>
                <div class="relative">
                   <div class="json-container">
        <div>{</div>
        <div>&nbsp;&nbsp;<span class="json-key">"error"</span>: <span class="json-boolean">false</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-number">200</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"message"</span>: <span class="json-string">"Success"</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"data"</span>: [</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;{</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"id"</span>: <span class="json-number">4090</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-string">"MLBB_ID_5"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"name"</span>: <span class="json-string">"5 (5+0) Diamonds"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"is_active"</span>: <span class="json-string">"active"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"price"</span>: <span class="json-number">1389</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"processDuration"</span>: <span class="json-string">"0"</span></div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;}</div>
        <div>&nbsp;&nbsp;]</div>
        <div>}</div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div x-data="{ open: false }" class="mx-auto w-full rounded-2xl bg-muted/50 p-4">
    <!-- Button Section -->
    <button
        class="flex w-full justify-between rounded-lg bg-muted px-1 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-muted/75 focus:outline-none focus-visible:ring focus-visible:ring-muted/75"
        type="button"
        @click="open = !open"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-12">
            <!-- Method and Endpoint Section -->
            <div>
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-post text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    POST
                </div>&nbsp;
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    Order
                </div>
                
            </div>
        </div>
        <!-- Arrow Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" :class="{'rotate-180': open}" class="transform h-5 w-5 text-muted-foreground transition-transform duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
        </svg>
    </button>

    <!-- Content Section (Responsive Grid Layout) -->
    <div x-show="open" x-transition class="grid grid-cols-1 gap-3 px-1 pb-1 pt-4 md:grid-cols-2 lg:grid-cols-2">
        <!-- Left Column (Query Parameters, Headers, and Request Body) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Query Parameters -->
            <div>
                <div class="font-semibold">Endpoint</div>
                <div class="text-muted">/api/v1/order</div>
            </div>

            <!-- Headers Section -->
            <div>
                <div class="font-semibold">Headers</div>
                <div class="grid gap-2">
                    <div class="font-mono font-semibold">Content-Type: application/json</div>
                    <div class="font-mono font-semibold">Authorization: Bearer {API_TOKEN}</div>
                </div>
            </div>

            <!-- Request Body -->
            <div>
                <div class="font-semibold">Request Body</div>
                <div class="text-muted"> { "code": "string", "referenceNumber": "string", "data": "string" }</div>
            </div>
        </div>

        <!-- Right Column (Request and Response) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Request Section -->
            <div>
                <div class="font-semibold">Request</div>
                <div class="relative">
                    <pre class="mt-2 whitespace-pre-wrap break-words rounded-xl bg-muted/50 p-3 text-muted-foreground overflow-x-auto">
                        <code class="language-bash">
                        curl -X POST "{BASE_URL}/api/v1/order" -H "Authorization: Bearer {API_TOKEN}" -H "Content-Type: application/json"
                        </code>
                    </pre>
                </div>
            </div>

            <!-- Response Section -->
            <div>
                <div class="font-semibold">Response</div>
                <div class="relative">
                    <div class="json-container">
        <div>{</div>
        <div>&nbsp;&nbsp;<span class="json-key">"error"</span>: <span class="json-boolean">false</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-number">200</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"message"</span>: <span class="json-string">"Success"</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"data"</span>: {</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"invoiceNumber"</span>: <span class="json-string">"WEJIZY-RAPIXXXXXX"</span></div>
        <div>&nbsp;&nbsp;}</div>
        <div>}</div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div x-data="{ open: false }" class="mx-auto w-full rounded-2xl bg-muted/50 p-4">
    <!-- Button Section -->
    <button
        class="flex w-full justify-between rounded-lg bg-muted px-1 py-2 text-left text-sm font-medium text-muted-foreground hover:bg-muted/75 focus:outline-none focus-visible:ring focus-visible:ring-muted/75"
        type="button"
        @click="open = !open"
    >
        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-12">
            <!-- Method and Endpoint Section -->
            <div>
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-post text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    POST
                </div>&nbsp;
                <div class="inline-flex items-center border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md">
                    Status Order
                </div>
                
            </div>
        </div>
        <!-- Arrow Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" :class="{'rotate-180': open}" class="transform h-5 w-5 text-muted-foreground transition-transform duration-300">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
        </svg>
    </button>

    <!-- Content Section (Responsive Grid Layout) -->
    <div x-show="open" x-transition class="grid grid-cols-1 gap-3 px-1 pb-1 pt-4 md:grid-cols-2 lg:grid-cols-2">
        <!-- Left Column (Query Parameters, Headers, and Request Body) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Query Parameters -->
            <div>
                <div class="font-semibold">Endpoint</div>
                <div class="text-muted">/api/v1/status-order/{invoice}</div>
            </div>

            <!-- Headers Section -->
            <div>
                <div class="font-semibold">Headers</div>
                <div class="grid gap-2">
                    <div class="font-mono font-semibold">Content-Type: application/json</div>
                    <div class="font-mono font-semibold">Authorization: Bearer {API_TOKEN}</div>
                </div>
            </div>

            <!-- Request Body -->
            <div>
                <div class="font-semibold">Request Body</div>
                <div class="text-muted">No Request Body</div>
            </div>
        </div>

        <!-- Right Column (Request and Response) -->
        <div class="flex flex-col gap-4 rounded-xl p-4 bg-muted/50">
            <!-- Request Section -->
            <div>
                <div class="font-semibold">Request</div>
                <div class="relative">
                    <pre class="mt-2 whitespace-pre-wrap break-words rounded-xl bg-muted/50 p-3 text-muted-foreground overflow-x-auto">
                        <code class="language-bash">
                        curl -X POST "{BASE_URL}/api/v1/status-order/{invoice}" -H "Authorization: Bearer {API_TOKEN}" -H "Content-Type: application/json"
                        </code>
                    </pre>
                </div>
            </div>

            <!-- Response Section -->
            <div>
                <div class="font-semibold">Response</div>
                <div class="relative">
                   <div class="json-container">
        <div>{</div>
        <div>&nbsp;&nbsp;<span class="json-key">"error"</span>: <span class="json-boolean">false</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"code"</span>: <span class="json-number">200</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"message"</span>: <span class="json-string">"Success"</span>,</div>
        <div>&nbsp;&nbsp;<span class="json-key">"data"</span>: {</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"invoiceNumber"</span>: <span class="json-string">"WEJIZY-RAPIXXXXXX"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"productName"</span>: <span class="json-string">"string"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"userData"</span>: <span class="json-string">"string"</span>,</div>
        <div>&nbsp;&nbsp;&nbsp;&nbsp;<span class="json-key">"statusCode"</span>: <span class="json-string">"Success"</span></div>
        <div>&nbsp;&nbsp;}</div>
        <div>}</div>
    </div>
                </div>
            </div>
        </div>
    </div>
</div>





                </div>
                
            </div>
        </div>
    </div>
   
</main>




@include('../footer')
@push('custom_script')
@endpush
@endsection

