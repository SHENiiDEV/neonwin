<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name', 'Neonwin') }} - Next-Gen Cyber iGaming Platform</title>

    <!-- Meta Tags -->
    <meta name="description" content="Neonwin - Modern Cyberpunk iGaming Platform with Instant Payouts, VIP Gamification and Tournaments">
    <meta name="theme-color" content="#0B0E14">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><polygon points='50,5 95,50 50,95 5,50' fill='%238B5CF6'/><polygon points='50,20 80,50 50,80 20,50' fill='%23EC4899'/></svg>">

    <!-- Scripts and Styles -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-[#0B0E14] text-gray-100 antialiased min-h-screen">
    @inertia
</body>
</html>
