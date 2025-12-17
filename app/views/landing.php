<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sinergi - Satu Platform, Sejuta Koneksi</title>

    <link href="/sinergi/public/css/output.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        -webkit-font-smoothing: antialiased;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Dark Mode Styles */
    body.dark-mode {
        background-color: #08090A;
        color: #ffffff;
    }

    /* Light Mode Styles */
    body.light-mode {
        background-color: #FAFAFA;
        color: #1a1a1a;
    }

    /* Subtle Grid Pattern for Texture */
    .bg-grid-pattern {
        background-size: 4rem 4rem;
        opacity: 0.2;
        transition: background-image 0.3s ease, opacity 0.3s ease;
    }

    .dark-mode .bg-grid-pattern {
        background-image: linear-gradient(to right, #1f1f1f 1px, transparent 1px),
            linear-gradient(to bottom, #1f1f1f 1px, transparent 1px);
        mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
    }

    .light-mode .bg-grid-pattern {
        background-image: linear-gradient(to right, #e5e5e5 1px, transparent 1px),
            linear-gradient(to bottom, #e5e5e5 1px, transparent 1px);
        mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
        -webkit-mask-image: radial-gradient(circle at center, black 40%, transparent 100%);
    }

    /* Glassmorphism */
    .glass-panel {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .dark-mode .glass-panel {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .dark-mode .glass-panel:hover {
        border-color: rgba(255, 255, 255, 0.15);
        background: rgba(255, 255, 255, 0.05);
    }

    .light-mode .glass-panel {
        background: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    .light-mode .glass-panel:hover {
        border-color: rgba(0, 0, 0, 0.15);
        background: rgba(255, 255, 255, 0.9);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    }

    /* Text Gradient */
    .text-gradient-white {
        transition: all 0.3s ease;
    }

    .dark-mode .text-gradient-white {
        background: linear-gradient(to bottom right, #ffffff 30%, #666666 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .light-mode .text-gradient-white {
        background: linear-gradient(to bottom right, #1a1a1a 30%, #666666 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Animasi Text Blur In */
    @keyframes blur-in {
        0% {
            opacity: 0;
            filter: blur(12px);
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            filter: blur(0);
            transform: translateY(0);
        }
    }

    .animate-blur {
        animation: blur-in 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
    }

    .delay-100 {
        animation-delay: 0.1s;
    }

    .delay-200 {
        animation-delay: 0.2s;
    }

    .delay-300 {
        animation-delay: 0.3s;
    }

    .delay-400 {
        animation-delay: 0.4s;
    }

    @keyframes gentle-float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-15px);
        }
    }

    @keyframes gentle-float-delayed {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .animate-float-manual {
        animation: gentle-float 4s ease-in-out infinite;
    }

    .animate-float-delayed {
        animation: gentle-float-delayed 5s ease-in-out infinite;
        animation-delay: 1s;
    }

    /* Theme Toggle Button */
    .theme-toggle-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .dark-mode .theme-toggle-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }

    .dark-mode .theme-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .light-mode .theme-toggle-btn {
        background: rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.1);
        color: #1a1a1a;
    }

    .light-mode .theme-toggle-btn:hover {
        background: rgba(0, 0, 0, 0.1);
        border-color: rgba(0, 0, 0, 0.15);
    }

    /* Dynamic Colors for Dark/Light Mode */
    .dynamic-bg-primary {
        transition: background-color 0.3s ease;
    }

    .dark-mode .dynamic-bg-primary {
        background-color: #08090A;
    }

    .light-mode .dynamic-bg-primary {
        background-color: #FAFAFA;
    }

    .dynamic-text-primary {
        transition: color 0.3s ease;
    }

    .dark-mode .dynamic-text-primary {
        color: #ffffff;
    }

    .light-mode .dynamic-text-primary {
        color: #1a1a1a;
    }

    .dynamic-text-secondary {
        transition: color 0.3s ease;
    }

    .dark-mode .dynamic-text-secondary {
        color: #9ca3af;
    }

    .light-mode .dynamic-text-secondary {
        color: #6b7280;
    }

    .dynamic-border {
        transition: border-color 0.3s ease;
    }

    .dark-mode .dynamic-border {
        border-color: rgba(255, 255, 255, 0.05);
    }

    .light-mode .dynamic-border {
        border-color: rgba(0, 0, 0, 0.08);
    }

    /* Navbar dynamic */
    .navbar-dynamic {
        transition: all 0.3s ease;
    }

    .dark-mode .navbar-dynamic {
        background-color: rgba(8, 9, 10, 0.8);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .light-mode .navbar-dynamic {
        background-color: rgba(255, 255, 255, 0.8);
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    /* Button dynamic */
    .btn-primary {
        transition: all 0.3s ease;
    }

    .dark-mode .btn-primary {
        background-color: #ffffff;
        color: #000000;
    }

    .dark-mode .btn-primary:hover {
        background-color: #e5e5e5;
    }

    .light-mode .btn-primary {
        background-color: #1a1a1a;
        color: #ffffff;
    }

    .light-mode .btn-primary:hover {
        background-color: #2d2d2d;
    }

    /* Card dynamic */
    .card-dynamic {
        transition: all 0.3s ease;
    }

    .dark-mode .card-dynamic {
        background-color: #0F1012;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .light-mode .card-dynamic {
        background-color: #ffffff;
        border: 1px solid rgba(0, 0, 0, 0.1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }

    /* Glow effect */
    .glow-effect {
        transition: all 0.3s ease;
    }

    .dark-mode .glow-effect {
        background: rgba(255, 255, 255, 0.05);
    }

    .light-mode .glow-effect {
        background: rgba(0, 0, 0, 0.02);
    }

    /* Selection */
    .dark-mode ::selection {
        background: #ffffff;
        color: #000000;
    }

    .light-mode ::selection {
        background: #1a1a1a;
        color: #ffffff;
    }
    </style>
</head>

<body class="dark-mode overflow-x-hidden">

    <nav class="fixed w-full z-50 top-0 transition-all duration-300 navbar-dynamic backdrop-blur-md" id="navbar">
        <div class="max-w-5xl mx-auto px-6">
            <div class="relative flex items-center justify-between h-20">
                <!-- Logo (Kiri) -->
                <div class="flex-shrink-0 cursor-pointer flex items-center gap-3 z-50" onclick="window.scrollTo(0,0)">
                    <img src="/sinergi/public/assets/images/logo_sinergi_putih.png" alt="Sinergi Logo"
                        class="h-9 w-auto opacity-90 hover:opacity-100 transition-opacity" id="logo-img">
                </div>

                <!-- Menu (Center Absolut) -->
                <div class="absolute left-1/2 transform -translate-x-1/2 hidden md:flex items-center gap-10">
                    <a href="#home"
                        class="text-sm font-medium dynamic-text-secondary hover:opacity-70 transition-opacity">Beranda</a>
                    <a href="#about"
                        class="text-sm font-medium dynamic-text-secondary hover:opacity-70 transition-opacity">Fitur</a>
                </div>

                <!-- Buttons (Kanan) -->
                <div class="flex items-center gap-4 z-50">
                    <!-- Theme Toggle Button -->
                    <button class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Theme">
                        <svg class="w-5 h-5" id="theme-icon" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                            </path>
                        </svg>
                    </button>

                    <a href="index.php?page=login"
                        class="text-sm font-medium dynamic-text-primary hover:opacity-70 transition-opacity hidden md:block">
                        Masuk
                    </a>
                    <a href="index.php?page=register"
                        class="hidden md:inline-flex h-10 items-center justify-center overflow-hidden rounded-full btn-primary px-6 font-medium transition-all group relative">
                        <span class="text-sm font-bold">Daftar</span>
                    </a>

                    <!-- Hamburger Button (Mobile) -->
                    <button id="hamburger-btn" onclick="toggleMobileMenu()"
                        class="md:hidden p-2 rounded-lg dynamic-text-primary hover:bg-white/10 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Menu (Slide-in Panel) -->
    <div id="mobile-menu"
        class="fixed top-0 right-0 w-72 h-full z-[100] translate-x-full transition-transform duration-300 ease-out">
        <!-- Glass Panel -->
        <div class="h-full backdrop-blur-xl rounded-l-2xl border-l border-white/10 flex flex-col"
            style="background: rgba(20, 20, 20, 0.95);">

            <!-- Header with Close -->
            <div class="flex items-center justify-between p-5 border-b border-white/10">
                <span class="text-white font-bold text-lg">Menu</span>
                <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 p-8 space-y-1">
                <a href="#home" onclick="toggleMobileMenu()"
                    class="block py-3 px-4 text-white font-medium rounded-lg hover:bg-white/10 transition-colors">
                    Beranda
                </a>
                <a href="#about" onclick="toggleMobileMenu()"
                    class="block py-3 px-4 text-white font-medium rounded-lg hover:bg-white/10 transition-colors">
                    Fitur
                </a>
            </nav>

            <!-- Auth Buttons -->
            <div class="p-5 space-y-3 border-t border-white/10">
                <a href="index.php?page=login"
                    class="block w-full py-3 text-center text-white/80 font-medium rounded-lg border border-white/20 hover:bg-white/10 transition-colors">
                    Masuk
                </a>
                <a href="index.php?page=register"
                    class="block w-full py-3 text-center bg-white text-black font-bold rounded-lg hover:bg-gray-100 transition-colors">
                    Daftar
                </a>
            </div>
        </div>
    </div>

    <!-- Backdrop Overlay -->
    <div id="mobile-backdrop" onclick="toggleMobileMenu()"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[99] hidden opacity-0 transition-opacity duration-300">
    </div>

    <script>
    function toggleMobileMenu() {
        const menu = document.getElementById('mobile-menu');
        const backdrop = document.getElementById('mobile-backdrop');
        const isOpen = !menu.classList.contains('translate-x-full');

        if (isOpen) {
            // Close menu
            menu.classList.add('translate-x-full');
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
            document.body.style.overflow = '';
        } else {
            // Open menu
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            menu.classList.remove('translate-x-full');
            document.body.style.overflow = 'hidden';
        }
    }
    </script>

    <section id="home" class="relative pt-32 pb-20 flex items-center min-h-[90vh] overflow-hidden">
        <div class="absolute inset-0 bg-grid-pattern pointer-events-none z-0"></div>
        <div
            class="absolute top-1/2 right-0 -translate-y-1/2 w-[600px] h-[600px] glow-effect opacity-[0.02] blur-[100px] rounded-full pointer-events-none">
        </div>

        <div class="relative z-10 max-w-5xl mx-auto px-6 grid md:grid-cols-2 gap-12 items-center">
            <div class="text-justify relative z-20">
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass-panel mb-8 animate-blur">
                    <span class="flex h-2 w-2 rounded-full dynamic-bg-primary"></span>
                    <span class="text-xs font-medium dynamic-text-secondary tracking-wide uppercase">The Student
                        Platform</span>
                </div>

                <h1
                    class="text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight dynamic-text-primary mb-6 leading-[1.1] animate-blur delay-100 border-b dynamic-border pb-6">
                    Satu Wadah.<br>
                    <span class="text-gradient-white">Sejuta Koneksi.</span>
                </h1>

                <p
                    class="text-base md:text-lg dynamic-text-secondary mb-8 max-w-md leading-relaxed animate-blur delay-200 tracking-wide">
                    Ekosistem digital eksklusif mahasiswa. Diskusi materi, berbagi pengetahuan, dan membangun jaringan
                    profesional dalam antarmuka yang sederhana.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-start gap-4 animate-blur delay-300">
                    <a href="index.php?page=register"
                        class="h-12 px-8 rounded-full btn-primary font-semibold text-sm transition-all flex items-center justify-center">
                        Mulai Sekarang
                    </a>

                    <a href="#about"
                        class="h-12 px-6 rounded-full dynamic-text-secondary font-medium text-sm hover:opacity-70 transition-opacity flex items-center justify-center group gap-2">
                        <span>Pelajari sistem kami</span>
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <div class="relative h-full flex items-center justify-center reveal animate-blur delay-400">
                <div class="relative w-full max-w-sm aspect-square">
                    <div class="absolute inset-0 glow-effect rounded-full blur-3xl animate-pulse"></div>

                    <div
                        class="absolute inset-0 m-auto w-64 h-auto card-dynamic rounded-2xl p-5 shadow-2xl z-10 flex flex-col gap-4">
                        <div class="flex items-center gap-3 border-b dynamic-border pb-4">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center">
                                <img src="/sinergi/public/assets/images/logo_sinergi_transparent.png" class="w-6 h-auto"
                                    alt="S">
                            </div>
                            <div>
                                <h3 class="dynamic-text-primary font-bold text-sm">Sinergi App</h3>
                                <p class="text-xs text-green-400 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                                    Online System
                                </p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-2 w-full glass-panel rounded-full"></div>
                            <div class="h-2 w-3/4 glass-panel rounded-full"></div>
                            <div class="h-2 w-5/6 glass-panel rounded-full"></div>
                        </div>
                        <div
                            class="mt-2 h-8 w-full glass-panel rounded-lg flex items-center justify-center text-xs dynamic-text-secondary">
                            Join Group
                        </div>
                    </div>

                    <div class="absolute bottom-10 -left-4 z-20 animate-float-delayed">
                        <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3 shadow-lg">
                            <div
                                class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-500 to-indigo-500 flex items-center justify-center text-xs text-white font-bold">
                                D</div>
                            <div>
                                <div class="text-[10px] dynamic-text-secondary">Diskusi Baru</div>
                                <div class="text-xs font-bold dynamic-text-primary">Project UAS Web?</div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute top-10 -right-4 z-20 animate-float-manual">
                        <div class="glass-panel px-4 py-3 rounded-xl flex items-center gap-3 shadow-lg">
                            <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs font-bold dynamic-text-primary">Munir</div>
                                <div class="text-[10px] dynamic-text-secondary">Menyukai postingan Anda</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-32 dynamic-bg-primary relative border-t dynamic-border">
        <div class="max-w-5xl mx-auto px-6">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-16 gap-6">
                <div class="max-w-2xl">
                    <h2 class="text-3xl md:text-5xl font-bold dynamic-text-primary mb-6">Didesain untuk Fokus.</h2>
                    <p class="dynamic-text-secondary text-lg">Kami menghilangkan gangguan. Sinergi memberikan pengalaman
                        diskusi
                        dan kolaborasi yang bersih, cepat, dan elegan.</p>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div class="glass-panel p-10 rounded-2xl group transition-all duration-300 hover:-translate-y-2">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-lg glass-panel dynamic-text-primary mb-8">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold dynamic-text-primary mb-4">Forum Terarah</h3>
                    <p class="dynamic-text-secondary leading-relaxed">
                        Diskusi yang dikategorikan berdasarkan mata kuliah dan minat. Tanpa spam, hanya konten
                        berkualitas.
                    </p>
                </div>

                <div class="glass-panel p-10 rounded-2xl group transition-all duration-300 hover:-translate-y-2">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-lg glass-panel dynamic-text-primary mb-8">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold dynamic-text-primary mb-4">Materi Terpusat</h3>
                    <p class="dynamic-text-secondary leading-relaxed">
                        Akses repositori catatan, soal ujian tahun lalu, dan referensi jurnal dalam satu klik pencarian.
                    </p>
                </div>

                <div class="glass-panel p-10 rounded-2xl group transition-all duration-300 hover:-translate-y-2">
                    <div
                        class="w-12 h-12 flex items-center justify-center rounded-lg glass-panel dynamic-text-primary mb-8">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold dynamic-text-primary mb-4">Real-time Chat</h3>
                    <p class="dynamic-text-secondary leading-relaxed">
                        Terhubung langsung dengan mahasiswa lain. Kolaborasi tugas kelompok menjadi lebih mudah dan
                        cepat.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <footer class="border-t dynamic-border dynamic-bg-primary pt-16 pb-10">
        <div class="max-w-5xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex flex-col items-center md:items-start">
                <div class="flex items-center gap-3 mb-4">
                    <img src="public/assets/images/logo_sinergi_putih.png" alt="Sinergi Logo"
                        class="h-6 w-auto opacity-70" id="footer-logo">
                    <span class="text-lg font-bold dynamic-text-primary">Sinergi.</span>
                </div>
                <p class="dynamic-text-secondary text-sm text-center md:text-left">
                    &copy; 2025 Sinergi Dev Team.<br>
                    Depok, Indonesia.
                </p>
            </div>
        </div>
    </footer>

    <script>
    function toggleTheme() {
        const body = document.body;
        const themeIcon = document.getElementById('theme-icon');
        const logoImg = document.getElementById('logo-img');
        const footerLogo = document.getElementById('footer-logo');

        if (body.classList.contains('dark-mode')) {
            // Switch to Light Mode
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');

            // Icon Matahari (Sun) ☀️
            themeIcon.innerHTML = `
            <circle cx="12" cy="12" r="5"></circle>
            <line x1="12" y1="1" x2="12" y2="3"></line>
            <line x1="12" y1="21" x2="12" y2="23"></line>
            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
            <line x1="1" y1="12" x2="3" y2="12"></line>
            <line x1="21" y1="12" x2="23" y2="12"></line>
            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
        `;

            // Ganti ke logo hitam untuk light mode
            logoImg.src = '/sinergi/public/assets/images/logo_sinergi_hitam.png';
            footerLogo.src = 'public/assets/images/logo_sinergi_hitam.png';

            window.currentTheme = 'light';
        } else {
            // Switch to Dark Mode
            body.classList.remove('light-mode');
            body.classList.add('dark-mode');

            // Icon Bulan (Moon) 🌙
            themeIcon.innerHTML = `
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        `;

            // Ganti ke logo putih untuk dark mode
            logoImg.src = '/sinergi/public/assets/images/logo_sinergi_putih.png';
            footerLogo.src = 'public/assets/images/logo_sinergi_putih.png';

            window.currentTheme = 'dark';
        }
    }

    // Initialize theme on page load
    window.addEventListener('DOMContentLoaded', (event) => {
        // Default to dark mode
        window.currentTheme = 'dark';
        document.body.classList.add('dark-mode');
    });
    </script>

</body>

</html>