<x-mail::layout>
    {{-- Header --}}
    <x-slot:header>
        <x-mail::header :url="config('app.frontend_url')">
            <img src="http://localhost:8000/storage/images/logo_cas_mas_pcia_2024_crop_2.webp">
            <h2 style="text-align: center">CAS Departamento Casinos</h2>
        </x-mail::header>
    </x-slot:header>

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        <x-slot:subcopy>
            <x-mail::subcopy>
                {{ $subcopy }}
            </x-mail::subcopy>
        </x-slot:subcopy>
    @endisset

    {{-- Footer --}}
    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>
