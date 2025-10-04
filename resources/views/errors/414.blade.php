@extends('template.template')



    <div class="-mt-[60px] flex min-h-screen items-center justify-center">
        <div class="text-center">
            <p class="text-base font-semibold text-primary-500">414</p>
            <h1 class="mt-4 text-3xl font-bold tracking-tight text-white sm:text-5xl">URI Too Long</h1>
            <p class="mt-6 text-base leading-7 text-murky-300">Sorry, the URI requested by the client is longer than the server is willing to interpret.</p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <a class="flex items-center gap-x-2 text-sm font-semibold leading-7 text-[var(--text-color)]" style="outline: none;" href="/id">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd"></path>
                    </svg>
                    <span>Back to home</span>
                </a>
            </div>
        </div>
    </div>

