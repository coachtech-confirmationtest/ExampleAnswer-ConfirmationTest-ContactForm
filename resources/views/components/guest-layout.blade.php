<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-[#e8e4df]">
    <header class="bg-white border-b border-[#d9d5d0] relative">
        <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
            <div class="w-24 flex justify-start">
                <!-- 左側のスペース（ボタン幅に合わせて固定） -->
            </div>
            <div class="absolute left-1/2 transform -translate-x-1/2">
                <a class="text-2xl font-serif text-amber-900 hover:text-amber-800" href="/">
                    FashionablyLate
                </a>
            </div>
            <div class="w-24 flex justify-end">
                @if (request()->routeIs('login'))
                    <a href="{{ route('register') }}"
                        class="px-5 py-1.5 border border-[#ddd8d3] text-[#c4bab0] bg-white rounded hover:bg-gray-50 transition lowercase text-center whitespace-nowrap text-sm">
                        {{ __('register') }}
                    </a>
                @elseif(request()->routeIs('register'))
                    <a href="{{ route('login') }}"
                        class="px-5 py-1.5 border border-[#ddd8d3] text-[#c4bab0] bg-white rounded hover:bg-gray-50 transition lowercase text-center whitespace-nowrap text-sm">
                        {{ __('login') }}
                    </a>
                @endif
            </div>
        </div>
    </header>
    <main>
        {{ $slot }}
    </main>
    @stack('scripts')
</body>

</html>
