<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Clonio - Clone your production database to test environments with automatic anonymization, schema versioning, and full audit trails. GDPR-compliant by design.">
    <meta name="keywords" content="database cloning, GDPR compliance, test data, database anonymization, schema versioning">
    <meta name="theme-color" content="#1e40af">
    <title>Clonio - Test with real data. Without the GDPR nightmare.</title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Tailwind Config -->
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-in-right': 'slideInRight 0.8s ease-out',
                        'slide-in-left': 'slideInLeft 0.8s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' }
                        },
                        slideInRight: {
                            '0%': { transform: 'translateX(100px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' }
                        },
                        slideInLeft: {
                            '0%': { transform: 'translateX(-100px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' }
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Base Styles */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Hide scrollbar but keep functionality */
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Vertical Scroll Container - all devices */
        .scroll-container {
            display: block;
        }

        .scroll-container section {
            min-height: 100vh;
        }

        /* Smooth scrolling for entire page - NO snap scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Smooth transitions for all animations */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Section Navigation Dots */
        .section-nav {
            position: fixed;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 40;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .section-nav-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.3);
            border: 2px solid rgba(59, 130, 246, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .section-nav-dot:hover {
            background: rgba(59, 130, 246, 0.6);
            transform: scale(1.2);
        }

        .section-nav-dot.active {
            background: rgb(59, 130, 246);
            border-color: rgb(59, 130, 246);
            transform: scale(1.3);
        }

        @media (max-width: 768px) {
            .section-nav {
                right: 1rem;
                gap: 0.75rem;
            }
            .section-nav-dot {
                width: 10px;
                height: 10px;
            }
        }

        /* Three Pillars - Background Icons */
        .pillar-card {
            position: relative;
            overflow: hidden;
        }

        .pillar-icon-bg {
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 160px;
            height: 160px;
            opacity: 0.08;
            transform: rotate(10deg);
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .dark .pillar-icon-bg {
            opacity: 0.12;
        }

        .pillar-card:hover .pillar-icon-bg {
            transform: rotate(15deg) scale(1.1);
            opacity: 0.12;
        }

        .dark .pillar-card:hover .pillar-icon-bg {
            opacity: 0.18;
        }

        /* Hide background icons on mobile */
        @media (max-width: 768px) {
            .pillar-icon-bg {
                display: none;
            }
        }

        /* Syntax Highlighting (CSS-only) */
        .code-block {
            background: #1e293b;
            border-radius: 0.5rem;
            padding: 1.5rem;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.7;
        }

        .code-comment { color: #64748b; }
        .code-keyword { color: #c084fc; }
        .code-string { color: #6ee7b7; }
        .code-function { color: #60a5fa; }
        .code-variable { color: #fbbf24; }

        /* Database Flow Animation */
        @keyframes dbFlow {
            0% { transform: translateX(0) scale(1); opacity: 1; }
            45% { transform: translateX(45%) scale(0.8); opacity: 0.5; }
            55% { transform: translateX(55%) scale(0.8); opacity: 0.5; }
            100% { transform: translateX(100%) scale(1); opacity: 1; }
        }

        .db-flow-item {
            animation: dbFlow 3s ease-in-out infinite;
        }

        /* Focus visible for accessibility */
        *:focus-visible {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<!-- Skip to content link for screen readers -->
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary-600 focus:text-white focus:rounded">
    Skip to main content
</a>

<!-- Navigation Bar -->
<nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 dark:bg-gray-950/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800" role="navigation" aria-label="Main navigation">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="#hero" class="flex items-center space-x-2 focus:outline-none focus:ring-2 focus:ring-primary-500 rounded" aria-label="Clonio Home">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" viewBox="0 0 144 144">
                        <defs>
                            <linearGradient id="a" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0" stop-color="#6EE7B7"/>
                                <stop offset="1" stop-color="#3B82F6"/>
                            </linearGradient>
                        </defs>
                        <g transform="matrix(.43 0 0 .43 -38.1 -60.17)">
                            <path fill="url(#a)" d="M160 250a96 96 0 1 1 192 0v170q0 70-96 20t-96-140Z"/>
                            <ellipse cx="215" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                            <ellipse cx="297" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                        </g>
                    </svg>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">Clonio</span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="#features" class="text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Features</a>
                <a href="#workflow" class="text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">How it works</a>
                <a href="#pricing" class="text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Pricing</a>
                <a href="#faq" class="text-gray-600 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">FAQ</a>
            </div>

            <!-- Theme Toggle & CTA -->
            <div class="flex items-center space-x-4">
                <button id="theme-toggle" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors" aria-label="Toggle dark mode">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>
                <a href="#cta" class="hidden md:inline-block px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                    Start Free Trial
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Main Content -->
<main id="main-content" class="pt-16">
    <!-- Vertical Scroll Container -->
    <div class="scroll-container" id="scroll-container">

        <!-- Section 1: HERO -->
        <section id="hero" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-950 dark:to-gray-900" aria-labelledby="hero-heading">
            <div class="max-w-5xl mx-auto text-center animate-fade-in">
                <h1 id="hero-heading" class="text-4xl sm:text-5xl lg:text-7xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    Test with real data. <br>
                    <span class="text-primary-600 dark:text-primary-400">Without the GDPR nightmare.</span>
                </h1>
                <p class="text-xl sm:text-2xl text-gray-600 dark:text-gray-300 mb-4 max-w-3xl mx-auto">
                    Clone your production database to dev and test environments – with automatic anonymization, schema versioning, and full audit trails.
                </p>
                <p class="text-lg sm:text-xl text-primary-700 dark:text-primary-300 font-semibold mb-12 max-w-3xl mx-auto">
                    Even when your test environment is 3 versions behind production.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-12">
                    <a href="#cta" class="w-full sm:w-auto px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white text-lg font-semibold rounded-lg transition-all transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 shadow-lg">
                        Start Free Trial (No Card Required)
                    </a>
                    <a href="#solution" class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-lg font-semibold rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        See how it works →
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="flex flex-wrap justify-center items-center gap-6 text-sm text-gray-600 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>3 days free • Cancel anytime</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>GDPR-compliant by design</span>
                    </div>
                </div>

                <!-- Scroll Indicator -->
                <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce" aria-hidden="true">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </section>

        <!-- Section 2: THE PAIN -->
        <section id="pain" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950" aria-labelledby="pain-heading">
            <div class="max-w-7xl mx-auto w-full">
                <h2 id="pain-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-4 text-gray-900 dark:text-white">
                    Your team right now
                </h2>
                <p class="text-xl text-center text-gray-600 dark:text-gray-400 mb-16 max-w-3xl mx-auto">
                    Sound familiar? You're not alone in this struggle.
                </p>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Pain Card 1 - GDPR Risk -->
                    <article class="group relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-red-200 dark:border-red-900/50 hover:border-red-500 dark:hover:border-red-500 transition-all duration-300 overflow-hidden hover:shadow-2xl hover:-translate-y-2">
                        <!-- Content -->
                        <div class="p-8">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="flex-shrink-0 w-2 h-16 bg-gradient-to-b from-red-500 to-rose-600 rounded-full"></div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">Real customer data in test</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed pl-5">
                                Your QA team needs realistic data to test properly. But copying production means exposing real customer emails, addresses, and payment info in unprotected environments. One compliance audit away from a <span class="font-bold text-red-600 dark:text-red-400">€20M GDPR fine</span> and a <span class="font-bold text-red-600 dark:text-red-400">career-ending headline</span>.
                            </p>
                        </div>

                        <!-- Hover glow effect -->
                        <div class="absolute inset-0 border-2 border-red-500/0 group-hover:border-red-500/50 rounded-2xl transition-all duration-300 pointer-events-none"></div>
                    </article>

                    <!-- Pain Card 2 - Manual Work -->
                    <article class="group relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-orange-200 dark:border-orange-900/50 hover:border-orange-500 dark:hover:border-orange-500 transition-all duration-300 overflow-hidden hover:shadow-2xl hover:-translate-y-2">
                        <!-- Content -->
                        <div class="p-8">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="flex-shrink-0 w-2 h-16 bg-gradient-to-b from-orange-500 to-amber-600 rounded-full"></div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">Manual copying nightmares</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed pl-5">
                                Export. Sanitize. Import. Repeat every sprint. Schema mismatches break everything. Your developers waste <span class="font-bold text-orange-600 dark:text-orange-400">days debugging test data</span> instead of shipping features your customers actually want. And you still can't be sure all PII is gone.
                            </p>
                        </div>

                        <!-- Hover glow effect -->
                        <div class="absolute inset-0 border-2 border-orange-500/0 group-hover:border-orange-500/50 rounded-2xl transition-all duration-300 pointer-events-none"></div>
                    </article>

                    <!-- Pain Card 3 - No Audit Trail -->
                    <article class="group relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-yellow-200 dark:border-yellow-900/50 hover:border-yellow-500 dark:hover:border-yellow-500 transition-all duration-300 overflow-hidden hover:shadow-2xl hover:-translate-y-2">
                        <!-- Content -->
                        <div class="p-8">
                            <div class="flex items-start gap-3 mb-4">
                                <div class="flex-shrink-0 w-2 h-16 bg-gradient-to-b from-yellow-500 to-amber-600 rounded-full"></div>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight">No audit trail</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed pl-5">
                                Who copied what data? When? Which anonymization rules were applied? Zero visibility means zero compliance proof. When the auditor asks, <span class="font-bold text-yellow-600 dark:text-yellow-400">you have no answers</span> — and you're the one <span class="font-bold text-yellow-600 dark:text-yellow-400">explaining to your CEO</span> why the company faces regulatory penalties.
                            </p>
                        </div>

                        <!-- Hover glow effect -->
                        <div class="absolute inset-0 border-2 border-yellow-500/0 group-hover:border-yellow-500/50 rounded-2xl transition-all duration-300 pointer-events-none"></div>
                    </article>
                </div>

                <!-- Bottom CTA hint -->
                <div class="mt-16 text-center">
                    <p class="text-lg text-gray-600 dark:text-gray-400 mb-4">
                        These problems don't have to be your reality.
                    </p>
                    <a href="#solution" class="inline-flex items-center gap-2 text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-semibold text-lg group transition-colors">
                        See how Clonio solves this
                        <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section 3: MEET CLONIO -->
        <section id="solution" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-primary-50 to-blue-100 dark:from-gray-900 dark:to-primary-950" aria-labelledby="solution-heading">
            <div class="max-w-6xl mx-auto w-full">
                <div class="text-center mb-12">
                    <h2 id="solution-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6 text-gray-900 dark:text-white">
                        Meet Clonio: Your database cloning co-pilot
                    </h2>
                    <p class="text-xl text-gray-700 dark:text-gray-300 max-w-4xl mx-auto">
                        Clonio automatically copies your production database to test and dev environments – with configurable anonymization, schema-version handling, and cryptographically signed audit logs. All in one click.
                    </p>
                </div>

                <!-- Animated Flow Visualization -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl mb-8" role="img" aria-label="Database cloning flow visualization">
                    <div class="flex items-center justify-between gap-4">
                        <svg class="block m-auto" width="800" height="300" viewBox="0 0 800 300" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <style>
                                    .text-label { font-family: sans-serif; font-size: 14px; fill: #555; text-anchor: middle; font-weight: bold;}
                                    .db-body { fill: #e0e0e0; stroke: #999; stroke-width: 2; }
                                    .db-top { fill: #f0f0f0; stroke: #999; stroke-width: 2; }

                                    /* Stile für Rohdaten (Dirty Data) */
                                    .raw-data-static { fill: #d9534f; opacity: 0.3; }
                                    .packet-dirty { fill: #d9534f; stroke: #c9302c; stroke-width: 1; }

                                    /* Stile für saubere Daten (Clean Data) */
                                    .clean-data-static { fill: #5bc0de; opacity: 0.3; }
                                    .packet-clean { fill: #5bc0de; stroke: #46b8da; stroke-width: 1; }

                                    /* Transformation Box Style */
                                    .transform-box { fill: #fff; stroke: #f0ad4e; stroke-width: 3; stroke-dasharray: 5,5; }
                                    .gear { fill: #f0ad4e; }

                                    /* --- ANIMATIONEN --- */

                                    /* Zahnrad drehen - KORRIGIERT */
                                    @keyframes spin {
                                        100% { transform: rotate(360deg); }
                                    }
                                    .gear-spinning {
                                        /* Der Drehpunkt ist exakt die Mitte des SVG (400,150), wo das Zahnrad definiert ist */
                                        transform-origin: 400px 150px;
                                        animation: spin 4s linear infinite;
                                    }

                                    /* Der Pfad für das schmutzige Paket (fliegt zur Mitte und verschwindet) */
                                    @keyframes moveDirty {
                                        0% { transform: translate(100px, 150px) scale(1) rotate(0deg); opacity: 1; }
                                        45% { transform: translate(380px, 150px) scale(0.8) rotate(180deg); opacity: 1; }
                                        50% { transform: translate(400px, 150px) scale(0.1) rotate(200deg); opacity: 0; }
                                        100% { transform: translate(400px, 150px) scale(0); opacity: 0; }
                                    }

                                    /* Der Pfad für das saubere Paket (erscheint in der Mitte und fliegt zum Ziel) */
                                    @keyframes moveClean {
                                        0% { transform: translate(400px, 150px) scale(0.1); opacity: 0; }
                                        50% { transform: translate(400px, 150px) scale(0.1); opacity: 0; }
                                        55% { transform: translate(420px, 150px) scale(0.8); opacity: 1; }
                                        100% { transform: translate(700px, 150px) scale(1); opacity: 1; }
                                    }

                                    /* Anwendung der Animationen auf die Pakete */
                                    .anim-packet-dirty-1 { animation: moveDirty 3s linear infinite; }
                                    .anim-packet-clean-1 { animation: moveClean 3s linear infinite; }

                                    .anim-packet-dirty-2 { animation: moveDirty 3s linear infinite; animation-delay: 1s; }
                                    .anim-packet-clean-2 { animation: moveClean 3s linear infinite; animation-delay: 1s; }

                                    .anim-packet-dirty-3 { animation: moveDirty 3s linear infinite; animation-delay: 2s; }
                                    .anim-packet-clean-3 { animation: moveClean 3s linear infinite; animation-delay: 2s; }

                                </style>

                                <g id="db-icon">
                                    <ellipse cx="60" cy="30" rx="60" ry="30" class="db-top"/>
                                    <path d="M0,30 v100 a60,30 0 0 0 120,0 v-100" class="db-body"/>
                                    <ellipse cx="60" cy="130" rx="60" ry="30" class="db-body" opacity="0.5" />
                                </g>

                                <polygon id="dirty-packet-shape" points="-15,-15 -10,0 -15,15 0,10 15,15 10,0 15,-15 0,-10" class="packet-dirty" />
                                <rect id="clean-packet-shape" x="-12" y="-12" width="24" height="24" rx="4" ry="4" class="packet-clean" />

                                <path id="gear-icon2" class="gear" d="M439.9,163.7h-8.3c-0.9,0-1.8-0.5-2.3-1.3l-4.2-7.3c-0.5-0.8-0.5-1.8,0-2.7l4.2-7.3c0.5-0.8,1.3-1.3,2.3-1.3h8.3c1.5,0,2.7-1.2,2.7-2.7v-9.6c0-1.5-1.2-2.7-2.7-2.7h-9.6c-1.5,0-2.7,1.2-2.7,2.7v8.3c0,0.9-0.5,1.8-1.3,2.3l-7.3,4.2c-0.8,0.5-1.8,0.5-2.7,0l-7.3-4.2c-0.8-0.5-1.3-1.3-1.3-2.3v-8.3c0-1.5-1.2-2.7-2.7-2.7h-9.6c-1.5,0-2.7,1.2-2.7,2.7v9.6c0,1.5,1.2,2.7,2.7,2.7h8.3c0.9,0,1.8,0.5,2.3,1.3l4.2,7.3c0.5,0.8,0.5,1.8,0,2.7l-4.2,7.3c-0.5,0.8-1.3,1.3-2.3,1.3h-8.3c-1.5,0-2.7,1.2-2.7,2.7v9.6c0,1.5,1.2,2.7,2.7,2.7h9.6c1.5,0,2.7-1.2,2.7-2.7v-8.3c0-0.9,0.5-1.8,1.3-2.3l7.3-4.2c0.8-0.5,1.8-0.5,2.7,0l7.3,4.2c0.8,0.5,1.3,1.3,1.3,2.3v8.3c0,1.5,1.2,2.7,2.7,2.7h9.6c1.5,0,2.7-1.2,2.7-2.7v-9.6C442.6,164.9,441.4,163.7,439.9,163.7z M400,165c-8.3,0-15-6.7-15-15c0-8.3,6.7-15,15-15s15,6.7,15,15C415,158.3,408.3,165,400,165z"/>

                                <defs>
                                    <linearGradient id="a" x1="0" x2="1" y1="0" y2="1">
                                        <stop offset="0" stop-color="#6EE7B7"/>
                                        <stop offset="1" stop-color="#3B82F6"/>
                                    </linearGradient>
                                </defs>
                                <g id="gear-icon" transform="matrix(.43 0 0 .43 -38.1 -60.17)">
                                    <path fill="url(#a)" d="M160 250a96 96 0 1 1 192 0v170q0 70-96 20t-96-140Z"/>
                                    <ellipse cx="215" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                                    <ellipse cx="297" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                                </g>

                            </defs>

                            <g transform="translate(40, 70)">
                                <text x="60" y="-20" class="text-label">Source DB (Raw Data)</text>
                                <use href="#db-icon" />
                                <g clip-path="url(#db-clip)">
                                    <rect x="20" y="50" width="20" height="20" class="raw-data-static" transform="rotate(15)"/>
                                    <rect x="70" y="80" width="25" height="25" class="raw-data-static" transform="rotate(-10)"/>
                                    <rect x="40" y="110" width="15" height="15" class="raw-data-static" transform="rotate(30)"/>
                                </g>
                            </g>

                            <g transform="translate(640, 70)">
                                <text x="60" y="-20" class="text-label">Target DB (Clean Data)</text>
                                <use href="#db-icon" style="fill: #e6f7ff; stroke: #46b8da;"/>
                                <g>
                                    <rect x="30" y="60" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="50" y="60" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="70" y="60" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="30" y="85" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="50" y="85" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="70" y="85" width="15" height="15" class="clean-data-static" rx="2"/>
                                </g>
                            </g>

                            <g transform="translate(300, 50)">
                                <text x="100" y="0" class="text-label">ETL Process / Transform</text>
                                <rect x="0" y="20" width="200" height="160" rx="10" ry="10" class="transform-box" />
                            </g>

                            <!-- use href="#gear-icon" class="gear-spinning"/ -->


                            <use href="#dirty-packet-shape" class="anim-packet-dirty-1" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-1" />

                            <use href="#dirty-packet-shape" class="anim-packet-dirty-2" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-2" />

                            <use href="#dirty-packet-shape" class="anim-packet-dirty-3" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-3" />

                        </svg>
                    </div>
                </div>

                <p class="text-center text-lg text-gray-700 dark:text-gray-300 max-w-3xl mx-auto">
                    No more manual exports. No more compliance anxiety. No more schema mismatches. Just production-realistic test data that's safe to use.
                </p>
            </div>
        </section>

        <!-- Section 4: WORKFLOW TRANSFORMATION -->
        <section id="workflow" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950" aria-labelledby="workflow-heading">
            <div class="max-w-7xl mx-auto w-full pt-12">
                <h2 id="workflow-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-16 text-gray-900 dark:text-white">
                    How Clonio changes your workflow
                </h2>

                <div class="grid lg:grid-cols-2 gap-12">
                    <!-- BEFORE -->
                    <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-950/20 dark:to-red-900/20 p-8 rounded-2xl border-2 border-red-200 dark:border-red-800">
                        <div class="inline-block px-4 py-2 bg-red-600 text-white text-sm font-bold rounded-full mb-6">
                            WITHOUT CLONIO
                        </div>

                        <ol class="space-y-4 mb-8" role="list">
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">1</span>
                                <span class="text-gray-700 dark:text-gray-300">Export production dump manually (risky, slow)</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">2</span>
                                <span class="text-gray-700 dark:text-gray-300">Write custom anonymization scripts</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">3</span>
                                <span class="text-gray-700 dark:text-gray-300">Try to import to test (schema breaks)</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">4</span>
                                <span class="text-gray-700 dark:text-gray-300">Debug mismatches for hours</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-red-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">5</span>
                                <span class="text-gray-700 dark:text-gray-300">Cross fingers you caught all PII</span>
                            </li>
                        </ol>

                        <div class="pt-6 border-t border-red-300 dark:border-red-800 space-y-2">
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">⏱️</span>
                                <span><strong>Time:</strong> Days</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">⚠️</span>
                                <span><strong>Risk:</strong> High</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">😓</span>
                                <span><strong>Developer happiness:</strong> Low</span>
                            </div>
                        </div>
                    </div>

                    <!-- AFTER -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-950/20 dark:to-green-900/20 p-8 rounded-2xl border-2 border-green-200 dark:border-green-800">
                        <div class="inline-block px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-full mb-6">
                            WITH CLONIO
                        </div>

                        <ol class="space-y-4 mb-8" role="list">
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">1</span>
                                <span class="text-gray-700 dark:text-gray-300">Configure transformation rules (once)</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">2</span>
                                <span class="text-gray-700 dark:text-gray-300">Click "Clone" or trigger via API</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center text-sm font-bold" aria-hidden="true">3</span>
                                <span class="text-gray-700 dark:text-gray-300">Done. ✓</span>
                            </li>
                        </ol>

                        <div class="pt-6 border-t border-green-300 dark:border-green-800 space-y-2">
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">⏱️</span>
                                <span><strong>Time:</strong> Minutes</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">✅</span>
                                <span><strong>Risk:</strong> Zero</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                                <span class="text-2xl" aria-hidden="true">🚀</span>
                                <span><strong>Developer happiness:</strong> High</span>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-center text-xl font-semibold text-gray-900 dark:text-white mt-12">
                    From days of manual work to minutes of automation.<br>
                    That's the Clonio difference.
                </p>
            </div>
        </section>

        <!-- Section 5: CORE CAPABILITIES -->
        <section id="features" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950" aria-labelledby="features-heading">
            <div class="max-w-7xl mx-auto w-full">
                <h2 id="features-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-16 text-gray-900 dark:text-white">
                    Three pillars of safe database cloning
                </h2>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Pillar 1: Privacy First -->
                    <article class="pillar-card bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl hover:shadow-2xl transition-shadow">
                        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Privacy First</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Four transformation types for every column. Choose what fits your data:
                        </p>
                        <ul class="space-y-2 text-gray-700 dark:text-gray-300 mb-4" role="list">
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span><strong>Keep original</strong> – for non-sensitive reference data</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span><strong>Static value</strong> – replace all with a fixed test value</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span><strong>Random value</strong> – generate realistic but fake data</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span><strong>Format-preserving</strong> – keep structure (valid IBANs, emails) but anonymize content</span>
                            </li>
                        </ul>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">
                            Built-in GDPR compliance. No PII leaves your infrastructure unprotected.
                        </p>

                        <!-- Background Icon -->
                        <svg class="pillar-icon-bg text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </article>

                    <!-- Pillar 2: Schema-Aware -->
                    <article class="pillar-card bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl hover:shadow-2xl transition-shadow">
                        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Schema-Aware Cloning</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Your test database is 3 versions behind production? No problem.
                        </p>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Clonio handles schema differences automatically:
                        </p>
                        <ul class="space-y-2 text-gray-700 dark:text-gray-300 mb-4" role="list">
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Columns added, removed, or renamed? Handled.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Tables restructured? No issue.</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Different database versions? Works seamlessly.</span>
                            </li>
                        </ul>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">
                            Clone confidently, even when your environments are out of sync.
                        </p>

                        <!-- Background Icon -->
                        <svg class="pillar-icon-bg text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </article>

                    <!-- Pillar 3: Full Auditability -->
                    <article class="pillar-card bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl hover:shadow-2xl transition-shadow">
                        <h3 class="text-2xl font-bold mb-4 text-gray-900 dark:text-white">Full Auditability</h3>
                        <p class="text-gray-700 dark:text-gray-300 mb-4">
                            Every clone operation is logged and cryptographically signed:
                        </p>
                        <ul class="space-y-2 text-gray-700 dark:text-gray-300 mb-4" role="list">
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Who triggered the clone</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Which transformations were applied</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Exact timestamp and target environment</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-primary-600 dark:text-primary-400 mt-1" aria-hidden="true">•</span>
                                <span>Tamper-proof audit trail</span>
                            </li>
                        </ul>
                        <p class="text-gray-700 dark:text-gray-300 font-medium">
                            Audit-ready reports for compliance teams. Zero manual documentation needed.
                        </p>

                        <!-- Background Icon -->
                        <svg class="pillar-icon-bg text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </article>
                </div>
            </div>
        </section>

        <!-- Section 6: DEVELOPER LOVE -->
        <section id="devops" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950" aria-labelledby="devops-heading">
            <div class="max-w-6xl mx-auto w-full pt-20">
                <h2 id="devops-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-8 text-gray-900 dark:text-white">
                    Built for DevOps workflows
                </h2>
                <p class="text-xl text-center text-gray-600 dark:text-gray-300 mb-12">
                    Clonio fits into your existing toolchain. Trigger clones however you work.
                </p>

                <!-- Feature Grid - Modern Design -->
                <div class="grid md:grid-cols-2 gap-6 mb-12">
                    <!-- Feature Card 1 -->
                    <div class="group relative bg-white dark:bg-gray-900 p-6 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-primary-500 dark:hover:border-primary-400 transition-all hover:shadow-xl overflow-hidden">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-primary-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span class="text-2xl" aria-hidden="true">⚡</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Manual or automated</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Schedule clones with cron-style timing or trigger on-demand when you need fresh data.</p>
                            </div>
                        </div>
                        <!-- Hover accent line -->
                        <div class="absolute bottom-0 left-4 right-4 h-1 bg-gradient-to-r from-primary-500 to-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-full"></div>
                    </div>

                    <!-- Feature Card 2 -->
                    <div class="group relative bg-white dark:bg-gray-900 p-6 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-purple-500 dark:hover:border-purple-400 transition-all hover:shadow-xl overflow-hidden">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span class="text-2xl" aria-hidden="true">🔌</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">API-first design</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Full REST API for programmatic control. Integrate Clonio into any workflow or script.</p>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-4 right-4 h-1 bg-gradient-to-r from-purple-500 to-pink-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-full"></div>
                    </div>

                    <!-- Feature Card 3 -->
                    <div class="group relative bg-white dark:bg-gray-900 p-6 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-green-500 dark:hover:border-green-400 transition-all hover:shadow-xl overflow-hidden">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span class="text-2xl" aria-hidden="true">🪝</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Event-driven triggers</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Connect to GitLab CI, GitHub Actions, Jenkins, or any webhook-compatible system. Deploy to prod → auto-clone to test.</p>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-4 right-4 h-1 bg-gradient-to-r from-green-500 to-emerald-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-full"></div>
                    </div>

                    <!-- Feature Card 4 -->
                    <div class="group relative bg-white dark:bg-gray-900 p-6 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-orange-500 dark:hover:border-orange-400 transition-all hover:shadow-xl overflow-hidden">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span class="text-2xl" aria-hidden="true">🖥️</span>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">CLI available</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Script your clones. Perfect for local development or CI/CD pipelines.</p>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-4 right-4 h-1 bg-gradient-to-r from-orange-500 to-red-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-full"></div>
                    </div>
                </div>

                <!-- Integration Logos -->
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 font-medium">INTEGRATES WITH YOUR FAVORITE TOOLS</p>
                    <div class="flex flex-wrap justify-center items-center gap-8">
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">GitLab CI</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">GitHub Actions</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Jenkins</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">CircleCI</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">BitBucket</div>
                    </div>
                </div>

                <!-- Code Example - Enhanced Design -->
                <div class="mb-6 relative group">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 to-blue-600 rounded-2xl blur opacity-20 group-hover:opacity-40 transition duration-300"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between px-6 py-3 bg-gray-900 rounded-t-2xl border-b border-gray-800">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <div class="w-3 h-3 rounded-full bg-green-500"></div>
                            </div>
                            <span class="text-xs text-gray-400 font-mono">terminal</span>
                        </div>
                        <div class="code-block rounded-t-none">
                                <pre class="text-gray-100" aria-label="Code example for cloning databases"><code><span class="code-comment"># Clone production to test with "strict" privacy preset</span>
<span class="code-function">clonio</span> <span class="code-keyword">clone</span> <span class="code-variable">--source</span>=<span class="code-string">prod</span> <span class="code-variable">--target</span>=<span class="code-string">test</span> <span class="code-variable">--preset</span>=<span class="code-string">strict</span>

<span class="code-comment"># Or trigger via API</span>
<span class="code-function">curl</span> <span class="code-variable">-X</span> <span class="code-keyword">POST</span> <span class="code-string">https://api.clonio.io/v1/clone</span> \
  <span class="code-variable">-H</span> <span class="code-string">"Authorization: Bearer YOUR_TOKEN"</span> \
  <span class="code-variable">-d</span> <span class="code-string">'{"source":"prod","target":"test","preset":"strict"}'</span></code></pre>
                        </div>
                    </div>
                </div>

                <div class="text-center mb-12">
                    <a href="#" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg hover:shadow-xl">
                        Explore API documentation
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Section 7: TRUST & SECURITY -->
        <section id="security" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950" aria-labelledby="security-heading">
            <div class="max-w-6xl mx-auto w-full">
                <h2 id="security-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-16 text-gray-900 dark:text-white">
                    Enterprise-grade security & compliance
                </h2>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Trust Element 1 - GDPR (Green) -->
                    <div class="group relative bg-white dark:bg-gray-900 p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-green-500 dark:hover:border-green-400 transition-all duration-300 hover:shadow-xl">
                        <!-- Small flag-style tag -->
                        <div class="absolute left-0 top-8 flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-r-xl flex items-center justify-center shadow-lg transform group-hover:w-20 transition-all duration-300">
                                <svg class="w-8 h-8 text-white transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="pl-20">
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">GDPR-compliant by design</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">No PII leaves your infrastructure unprotected. Transformation rules ensure compliance at the data level.</p>
                        </div>
                    </div>

                    <!-- Trust Element 2 - Cryptography (Blue) -->
                    <div class="group relative bg-white dark:bg-gray-900 p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-300 hover:shadow-xl">
                        <!-- Small flag-style tag -->
                        <div class="absolute left-0 top-8 flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-r-xl flex items-center justify-center shadow-lg transform group-hover:w-20 transition-all duration-300">
                                <svg class="w-8 h-8 text-white transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="pl-20">
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Cryptographically signed audit logs</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Tamper-proof compliance trail. Every action is logged, signed, and verifiable.</p>
                        </div>
                    </div>

                    <!-- Trust Element 3 - Self-Hosted (Purple) -->
                    <div class="group relative bg-white dark:bg-gray-900 p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-purple-500 dark:hover:border-purple-400 transition-all duration-300 hover:shadow-xl">
                        <!-- Small flag-style tag -->
                        <div class="absolute left-0 top-8 flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-violet-600 rounded-r-xl flex items-center justify-center shadow-lg transform group-hover:w-20 transition-all duration-300">
                                <svg class="w-8 h-8 text-white transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="pl-20">
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Deploy on your infrastructure</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Self-hosted option available. Your data never leaves your servers. Full control, zero third-party risk.</p>
                        </div>
                    </div>

                    <!-- Trust Element 4 - Security (Orange) -->
                    <div class="group relative bg-white dark:bg-gray-900 p-8 rounded-2xl border-2 border-gray-200 dark:border-gray-800 hover:border-orange-500 dark:hover:border-orange-400 transition-all duration-300 hover:shadow-xl">
                        <!-- Small flag-style tag -->
                        <div class="absolute left-0 top-8 flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-amber-600 rounded-r-xl flex items-center justify-center shadow-lg transform group-hover:w-20 transition-all duration-300">
                                <svg class="w-8 h-8 text-white transform group-hover:scale-110 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                        </div>
                        <!-- Content -->
                        <div class="pl-20">
                            <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Battle-tested security</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Industry-standard encryption, role-based access control, and secure credential management.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 8: PRICING -->
        <section id="pricing" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950" aria-labelledby="pricing-heading">
            <div class="max-w-4xl mx-auto w-full pt-20">
                <h2 id="pricing-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-8 text-gray-900 dark:text-white">
                    Simple, transparent pricing
                </h2>
                <p class="text-xl text-center text-gray-600 dark:text-gray-300 mb-12">
                    One price. Unlimited databases. No surprises.
                </p>

                <!-- Pricing Card - Modern Design -->
                <div class="relative group">
                    <!-- Glow effect background -->
                    <div class="absolute -inset-1 bg-gradient-to-r from-primary-600 via-blue-600 to-indigo-600 rounded-3xl blur-lg opacity-25 group-hover:opacity-40 transition duration-500"></div>

                    <!-- Main pricing card -->
                    <div class="relative bg-white dark:bg-gray-900 rounded-3xl border-2 border-gray-200 dark:border-gray-800 overflow-hidden shadow-2xl">
                        <!-- Top accent bar -->
                        <div class="h-2 bg-gradient-to-r from-primary-600 via-blue-600 to-indigo-600"></div>

                        <div class="p-12">
                            <!-- Free Trial Badge -->
                            <div class="text-center mb-8">
                                <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-full text-lg font-bold shadow-lg transform hover:scale-105 transition-transform">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                    </svg>
                                    <span>3-DAY FREE TRIAL</span>
                                </div>
                                <p class="mt-4 text-gray-600 dark:text-gray-400 text-lg">
                                    Full features • No credit card required • Cancel anytime
                                </p>
                            </div>

                            <div class="border-t-2 border-gray-200 dark:border-gray-800 my-8"></div>

                            <!-- Pricing Details -->
                            <div class="text-center mb-8">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Annual Plan</h3>
                                </div>

                                <!-- Price -->
                                <div class="flex items-baseline justify-center gap-2 mb-4">
                                    <span class="text-6xl font-bold text-gray-900 dark:text-white">€199</span>
                                    <div class="text-left">
                                        <div class="text-xl text-gray-600 dark:text-gray-400">/month</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-500">billed annually</div>
                                    </div>
                                </div>

                                <!-- Unlimited badge -->
                                <div class="inline-flex items-center gap-2 px-4 py-2 bg-primary-50 dark:bg-primary-950 border-2 border-primary-200 dark:border-primary-800 rounded-full">
                                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    <span class="font-semibold text-primary-700 dark:text-primary-300">Unlimited databases</span>
                                </div>
                            </div>

                            <!-- Features List -->
                            <div class="space-y-4 mb-10">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Unlimited database clones</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Clone as many databases as you need, as often as you want</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">All transformation types</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Keep, static, random, and format-preserving anonymization</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Schema-aware cloning</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Handle different schema versions automatically</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Full audit logs & compliance</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Cryptographically signed, tamper-proof audit trail</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">API, CLI & webhooks</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Full automation for your CI/CD pipelines</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <div>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">Priority email support</span>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">Response within 24 hours</p>
                                    </div>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="text-center space-y-4">
                                <a href="#cta" class="group relative inline-flex items-center justify-center w-full px-12 py-5 text-xl font-bold text-white bg-gradient-to-r from-primary-600 to-blue-600 rounded-xl shadow-lg hover:shadow-2xl transform hover:scale-105 transition-all duration-300 overflow-hidden">
                                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-600 to-indigo-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></span>
                                    <span class="relative flex items-center gap-3">
                                            Start Your Free Trial
                                            <svg class="w-6 h-6 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                            </svg>
                                        </span>
                                </a>

                                <p class="text-sm text-gray-500 dark:text-gray-500">
                                    No credit card required • Start in 2 minutes • Cancel anytime
                                </p>
                            </div>
                        </div>

                        <!-- Bottom accent -->
                        <div class="px-12 py-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-center gap-2 text-gray-600 dark:text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <span class="font-medium">Need custom pricing for your team?</span>
                                <a href="#" class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-semibold underline transition-colors">Contact sales</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust indicators -->
                <div class="my-12 flex flex-wrap justify-center items-center gap-8 text-sm text-gray-500 dark:text-gray-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>GDPR Compliant</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Secure Payments</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        <span>Money-back Guarantee</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 9: FINAL CTA -->
        <section id="cta" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-primary-600 to-blue-700 dark:from-primary-900 dark:to-blue-900" aria-labelledby="cta-heading">
            <div class="max-w-4xl mx-auto text-center">
                <h2 id="cta-heading" class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6">
                    Ready to test safely?
                </h2>
                <p class="text-xl sm:text-2xl text-primary-100 mb-12 max-w-3xl mx-auto">
                    Join development teams who stopped worrying about GDPR violations in their test environments.
                </p>

                <div class="flex flex-col sm:flex-row gap-6 justify-center items-center mb-12">
                    <a href="/register" class="w-full sm:w-auto px-12 py-5 bg-white hover:bg-gray-100 text-primary-700 text-xl font-bold rounded-xl transition-all transform hover:scale-105 shadow-2xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2">
                        Start 3-Day Free Trial
                    </a>
                    <a href="#" class="w-full sm:w-auto px-12 py-5 bg-primary-800 hover:bg-primary-900 text-white text-xl font-semibold rounded-xl border-2 border-white/30 transition-all focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2">
                        Book a 15-minute demo →
                    </a>
                </div>

                <p class="text-primary-100 text-lg">
                    Trusted by development teams at startups and enterprises
                </p>
            </div>
        </section>

        <!-- Section 10: FAQ -->
        <section id="faq" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950 py-24" aria-labelledby="faq-heading">
            <div class="max-w-4xl mx-auto w-full">
                <h2 id="faq-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-16 text-gray-900 dark:text-white">
                    Frequently asked questions
                </h2>

                <div class="space-y-8">
                    <!-- FAQ 1 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                How does Clonio handle schema differences between environments?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Clonio automatically detects and adapts to schema differences. If your test database has added, removed, or renamed columns compared to production, Clonio intelligently maps the data. You define transformation rules once, and they work across different schema versions.
                        </p>
                    </details>

                    <!-- FAQ 2 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                Which databases does Clonio support?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Clonio currently supports PostgreSQL, MySQL, MariaDB, and Microsoft SQL Server. Need Oracle or MongoDB? Contact us – we prioritize development based on customer demand and can provide custom solutions for enterprise clients.
                        </p>
                    </details>

                    <!-- FAQ 3 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                Is my data secure during the cloning process?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Absolutely. All data transfer happens within your infrastructure. Transformations are applied before any data leaves the source database. We use encryption in transit and at rest. For maximum control, choose our self-hosted deployment option.
                        </p>
                    </details>

                    <!-- FAQ 4 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                What happens after the 3-day trial?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Your trial includes full access to all features. After 3 days, you can choose to subscribe with an annual plan. If you don't subscribe, your trial account is paused – no automatic charges, no surprises. You can reactivate anytime.
                        </p>
                    </details>

                    <!-- FAQ 5 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                Can I automate cloning in my CI/CD pipeline?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Yes! Clonio is API-first and built for automation. Use our REST API, CLI, or webhooks to trigger clones from GitLab CI, GitHub Actions, Jenkins, or any CI/CD tool. Schedule automatic clones or trigger them on specific events like deployments.
                        </p>
                    </details>

                    <!-- FAQ 6 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                Do you offer support?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            All plans include email support with response within 24 hours. For enterprise customers, we offer dedicated support channels and custom SLAs. During your trial, we're here to help you get set up.
                        </p>
                    </details>

                    <!-- FAQ 7 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                What if I need help setting up transformation rules?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            We provide detailed documentation and examples for common use cases. During your trial, our team can help you configure your first clone. For complex scenarios, we offer onboarding sessions as part of enterprise plans.
                        </p>
                    </details>
                </div>
            </div>
        </section>
    </div>
</main>

<!-- Section Navigation Dots -->
<nav class="section-nav" aria-label="Page sections" role="navigation">
    <button class="section-nav-dot active" data-section="hero" aria-label="Go to hero section"></button>
    <button class="section-nav-dot" data-section="pain" aria-label="Go to pain points"></button>
    <button class="section-nav-dot" data-section="solution" aria-label="Go to solution"></button>
    <button class="section-nav-dot" data-section="workflow" aria-label="Go to workflow"></button>
    <button class="section-nav-dot" data-section="features" aria-label="Go to features"></button>
    <button class="section-nav-dot" data-section="devops" aria-label="Go to DevOps"></button>
    <button class="section-nav-dot" data-section="security" aria-label="Go to security"></button>
    <button class="section-nav-dot" data-section="pricing" aria-label="Go to pricing"></button>
    <button class="section-nav-dot" data-section="cta" aria-label="Go to call to action"></button>
    <button class="section-nav-dot" data-section="faq" aria-label="Go to FAQ"></button>
</nav>

<!-- Footer -->
<footer class="bg-gray-900 dark:bg-black text-gray-300 py-12 px-4 sm:px-6 lg:px-8" role="contentinfo">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" viewBox="0 0 144 144">
                        <defs>
                            <linearGradient id="a" x1="0" x2="1" y1="0" y2="1">
                                <stop offset="0" stop-color="#6EE7B7"/>
                                <stop offset="1" stop-color="#3B82F6"/>
                            </linearGradient>
                        </defs>
                        <g transform="matrix(.43 0 0 .43 -38.1 -60.17)">
                            <path fill="url(#a)" d="M160 250a96 96 0 1 1 192 0v170q0 70-96 20t-96-140Z"/>
                            <ellipse cx="215" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                            <ellipse cx="297" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                        </g>
                    </svg>

                    <span class="text-xl font-bold text-white">Clonio</span>
                </div>
                <p class="text-sm">Safe database cloning for modern teams.</p>
            </div>

            <!-- Product Links -->
            <div>
                <h3 class="font-semibold text-white mb-4">Product</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="#features" class="hover:text-primary-400 transition-colors">Features</a></li>
                    <li><a href="#pricing" class="hover:text-primary-400 transition-colors">Pricing</a></li>
                    {{--<li><a href="#" class="hover:text-primary-400 transition-colors">Documentation</a></li>--}}
                    {{--<li><a href="#" class="hover:text-primary-400 transition-colors">API Reference</a></li>--}}
                </ul>
            </div>

            {{--<!-- Company Links -->
            <div>
                <h3 class="font-semibold text-white mb-4">Company</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="#" class="hover:text-primary-400 transition-colors">About Us</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">Blog</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">Careers</a></li>
                    <li><a href="#" class="hover:text-primary-400 transition-colors">Contact</a></li>
                </ul>
            </div>--}}

            <!-- Legal Links -->
            <div>
                <h3 class="font-semibold text-white mb-4">Legal</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="{{ route('static.policy') }}" class="hover:text-primary-400 transition-colors">Privacy Policy</a> (<a href="{{ route('static.datenschutz') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
                    <li><a href="{{ route('static.terms') }}" class="hover:text-primary-400 transition-colors">Terms of Service</a> (<a href="{{ route('static.agb') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
                    <li><a href="{{ route('static.imprint') }}" class="hover:text-primary-400 transition-colors">Imprint</a> (<a href="{{ route('static.impressum') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
                    {{--<li><a href="#" class="hover:text-primary-400 transition-colors">GDPR Compliance</a></li>--}}
                    {{--<li><a href="#" class="hover:text-primary-400 transition-colors">Security</a></li>--}}
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-800 pt-8 text-sm text-center">
            <a
                href="/"
                class="group inline-flex items-center hover:text-gray-700 focus:rounded-sm focus:outline-2 focus:outline-indigo-500 dark:hover:text-white"
            >
                Made with
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    class="mx-1 -mt-px h-5 w-5 stroke-gray-400 group-hover:fill-red-500 group-hover:stroke-red-600 dark:stroke-gray-600 dark:group-hover:fill-red-700 dark:group-hover:stroke-red-800"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                    />
                </svg>
                in Berlin
            </a>
        </div>
    </div>
</footer>

<!-- JavaScript for Theme Toggle & Navigation -->
<script>
    // Theme Toggle
    const themeToggleBtn = document.getElementById('theme-toggle');
    const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
    const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

    // Check for saved theme preference or default to system preference
    const currentTheme = localStorage.getItem('theme') ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    // Set initial theme
    if (currentTheme === 'dark') {
        document.documentElement.classList.add('dark');
        themeToggleLightIcon.classList.remove('hidden');
    } else {
        document.documentElement.classList.remove('dark');
        themeToggleDarkIcon.classList.remove('hidden');
    }

    // Toggle theme
    themeToggleBtn.addEventListener('click', function() {
        themeToggleDarkIcon.classList.toggle('hidden');
        themeToggleLightIcon.classList.toggle('hidden');

        if (document.documentElement.classList.contains('dark')) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    });

    // Section Navigation Dots
    const sections = document.querySelectorAll('section[id]');
    const navDots = document.querySelectorAll('.section-nav-dot');

    // Click handler for dots
    navDots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            const sectionId = dot.getAttribute('data-section');
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Update active dot on scroll
    const observerOptions = {
        threshold: 0.3,
        rootMargin: '0px 0px 0px 0px'
    };

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const sectionId = entry.target.getAttribute('id');
                navDots.forEach(dot => {
                    if (dot.getAttribute('data-section') === sectionId) {
                        navDots.forEach(d => d.classList.remove('active'));
                        dot.classList.add('active');
                    }
                });
            }
        });
    }, observerOptions);

    sections.forEach(section => {
        sectionObserver.observe(section);
    });

    // Keyboard navigation (Arrow keys) - smooth, not forced
    let currentSectionIndex = 0;
    const sectionIds = Array.from(sections).map(s => s.id);

    document.addEventListener('keydown', (e) => {
        // Only handle if not in input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            currentSectionIndex = Math.min(currentSectionIndex + 1, sections.length - 1);
            sections[currentSectionIndex].scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentSectionIndex = Math.max(currentSectionIndex - 1, 0);
            sections[currentSectionIndex].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        // Let PageUp/PageDown work naturally without preventDefault
    });

    // Update currentSectionIndex based on visible section
    window.addEventListener('scroll', () => {
        const scrollPosition = window.scrollY + window.innerHeight / 2;
        sections.forEach((section, index) => {
            const sectionTop = section.offsetTop;
            const sectionBottom = sectionTop + section.offsetHeight;
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                currentSectionIndex = index;
            }
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Intersection Observer for fade-in animations
    const fadeObserverOptions = {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    };

    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, fadeObserverOptions);

    // Observe all article and card elements for fade-in
    document.querySelectorAll('article, .bg-gradient-to-br').forEach(element => {
        element.style.opacity = '0';
        element.style.transform = 'translateY(15px)';
        element.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
        fadeObserver.observe(element);
    });
</script>
</body>
</html>
