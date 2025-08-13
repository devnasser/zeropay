<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name'))</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    
    <!-- Custom Styles -->
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f3f4f6;
        }
        
        body {
            font-family: 'Tajawal', sans-serif;
        }
        
        [dir="rtl"] {
            text-align: right;
        }
        
        .voice-interface {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        [dir="rtl"] .voice-interface {
            right: auto;
            left: 20px;
        }
    </style>
    
    @stack('styles')
</head>
<body class="antialiased bg-gray-50">
    <div id="app">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between items-center">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center">
                            <svg class="h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="ml-2 text-xl font-bold text-gray-900">{{ __('Smart AutoParts') }}</span>
                        </a>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="{{ route('categories.index') }}" class="text-gray-700 hover:text-primary">{{ __('Categories') }}</a>
                        <a href="{{ route('shops.index') }}" class="text-gray-700 hover:text-primary">{{ __('Shops') }}</a>
                        <a href="{{ route('products.index') }}" class="text-gray-700 hover:text-primary">{{ __('Products') }}</a>
                        <a href="{{ route('about') }}" class="text-gray-700 hover:text-primary">{{ __('About') }}</a>
                    </div>
                    
                    <!-- Right Side -->
                    <div class="flex items-center space-x-4">
                        <!-- Language Switcher -->
                        <div class="relative">
                            <select onchange="changeLanguage(this.value)" class="appearance-none bg-transparent border border-gray-300 rounded px-3 py-1 text-sm">
                                @foreach(config('app.locales') as $locale)
                                    <option value="{{ $locale }}" {{ app()->getLocale() == $locale ? 'selected' : '' }}>
                                        {{ strtoupper($locale) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Cart -->
                        <a href="{{ route('cart.index') }}" class="relative">
                            <svg class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            @livewire('cart-counter')
                        </a>
                        
                        <!-- User Menu -->
                        @auth
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="flex items-center space-x-2">
                                    <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) }}" alt="">
                                </button>
                                <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Dashboard') }}</a>
                                    <a href="{{ route('profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Profile') }}</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('Logout') }}</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-700 hover:text-primary">{{ __('Login') }}</a>
                            <a href="{{ route('register') }}" class="bg-primary text-white px-4 py-2 rounded-md hover:bg-blue-700">{{ __('Register') }}</a>
                        @endauth
                    </div>
                </div>
            </nav>
        </header>
        
        <!-- Main Content -->
        <main>
            @yield('content')
        </main>
        
        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- About -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('About Us') }}</h3>
                        <p class="text-gray-400">{{ __('Leading auto parts marketplace in Saudi Arabia') }}</p>
                    </div>
                    
                    <!-- Links -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('Quick Links') }}</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-white">{{ __('Terms & Conditions') }}</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white">{{ __('Privacy Policy') }}</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-white">{{ __('Return Policy') }}</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('Contact') }}</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li>{{ __('Phone') }}: +966 50 848 0715</li>
                            <li>{{ __('Email') }}: dev-na@outlook.com</li>
                        </ul>
                    </div>
                    
                    <!-- Social -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ __('Follow Us') }}</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-white">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved') }}.</p>
                </div>
            </div>
        </footer>
        
        <!-- Voice Interface (if enabled) -->
        @if(auth()->check() && auth()->user()->enable_voice_interface)
            <div class="voice-interface">
                @livewire('voice-interface')
            </div>
        @endif
    </div>
    
    @livewireScripts
    @stack('scripts')
    
    <script>
        function changeLanguage(locale) {
            window.location.href = '{{ url('/') }}/locale/' + locale;
        }
    </script>
</body>
</html>