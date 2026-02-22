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
                    Start Compliant Cloning
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
            <!-- Dot-grid pattern overlay -->
            <div class="absolute inset-0 opacity-40 dark:opacity-[0.07] pointer-events-none" style="background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px); background-size: 28px 28px;" aria-hidden="true"></div>

            <div class="max-w-5xl mx-auto text-center animate-fade-in">
                <!-- Pill badge -->
                <div class="inline-flex border border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 text-sm px-4 py-1.5 rounded-full mb-8">
                    Open source · Self-hosted · GDPR-compliant
                </div>

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
                        Start Compliant Cloning
                    </a>
                    <a href="#solution" class="w-full sm:w-auto px-8 py-4 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-lg font-semibold rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-all focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                        See how it works →
                    </a>
                </div>

                <!-- Trust Badges -->
                <div class="flex flex-wrap justify-center items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 shadow-sm">
                        <svg class="size-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Free for 60 days
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 shadow-sm">
                        <svg class="size-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        No credit card ever
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 shadow-sm">
                        <svg class="size-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        GDPR-compliant
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-400 shadow-sm">
                        <svg class="size-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Open source
                    </span>
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
                        Clonio automatically copies your production database to test and dev environments – with configurable anonymization, schema-version handling, and cryptographically signed audit logs. Running inside your infrastructure. All in one click.
                    </p>
                </div>

                <!-- Animated Flow Visualization -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-2xl mb-8" role="img" aria-label="Database cloning flow visualization">
                    <div class="flex items-center justify-between gap-4">
                        <svg width="800" height="300" class="block m-auto" viewBox="0 0 800 300" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="ghost-gradient" x1="0" x2="1" y1="0" y2="1">
                                    <stop offset="0" stop-color="#6EE7B7"/>
                                    <stop offset="1" stop-color="#3B82F6"/>
                                </linearGradient>

                                <g id="ghost-icon">
                                    <g transform="matrix(.43 0 0 .43 -38.1 -60.17)">
                                        <path fill="url(#ghost-gradient)" d="M160 250a96 96 0 1 1 192 0v170q0 70-96 20t-96-140Z"/>
                                        <ellipse cx="215" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                                        <ellipse cx="297" cy="260" fill="#1e3a8a" rx="18" ry="28"/>
                                    </g>
                                </g>

                                <style>
                                    .text-label { font-family: sans-serif; font-size: 14px; fill: #555; text-anchor: middle; font-weight: bold;}
                                    .text-label-small { font-family: sans-serif; font-size: 10px; fill: #999; text-anchor: middle; font-weight: normal;}
                                    .db-body { fill: #f0f0f0; stroke: #999; stroke-width: 1; }
                                    .db-top { fill: #f0f0f0; stroke: #999; stroke-width: 1; }

                                    /* Stile für Datenpakete */
                                    .raw-data-static { fill: #d9534f; opacity: 0.3; }
                                    .packet-dirty { fill: #d9534f; stroke: #c9302c; stroke-width: 1; }

                                    /* Stile für saubere Daten (Clean Data) */
                                    .clean-data-static { fill: #5bc0de; opacity: 0.3; }
                                    .packet-clean { fill: #5bc0de; stroke: #46b8da; stroke-width: 1; }

                                    /* Transformation Box Style */
                                    .transform-box { fill: #fff; stroke: #3B82F6; stroke-width: 3; stroke-dasharray: 5,5; }

                                    /* --- ANIMATIONEN --- */
                                    /* Geist Schweben (Symmetrisch um die Mitte) */
                                    @keyframes hover {
                                        0%, 100% { transform: translateY(8px); }
                                        50% { transform: translateY(-8px); }
                                    }
                                    .ghost-hovering {
                                        animation: hover 3s ease-in-out infinite;
                                    }


                                    /* Datenfluss Animationen */
                                    @keyframes moveDirty {
                                        0% { transform: translate(100px, 150px) scale(1) rotate(0deg); opacity: 1; }
                                        45% { transform: translate(380px, 150px) scale(0.8) rotate(180deg); opacity: 1; }
                                        50% { transform: translate(400px, 150px) scale(0.1) rotate(200deg); opacity: 0; }
                                        100% { transform: translate(400px, 150px) scale(0); opacity: 0; }
                                    }
                                    @keyframes moveClean {
                                        0% { transform: translate(400px, 150px) scale(0.1); opacity: 0; }
                                        50% { transform: translate(400px, 150px) scale(0.1); opacity: 0; }
                                        55% { transform: translate(420px, 150px) scale(0.8); opacity: 1; }
                                        100% { transform: translate(700px, 150px) scale(1); opacity: 1; }
                                    }

                                    .anim-packet-dirty-1 { animation: moveDirty 3s linear infinite; }
                                    .anim-packet-clean-1 { animation: moveClean 3s linear infinite; }
                                    .anim-packet-dirty-2 { animation: moveDirty 3s linear infinite; animation-delay: 1s; }
                                    .anim-packet-clean-2 { animation: moveClean 3s linear infinite; animation-delay: 1s; }
                                    .anim-packet-dirty-3 { animation: moveDirty 3s linear infinite; animation-delay: 2s; }
                                    .anim-packet-clean-3 { animation: moveClean 3s linear infinite; animation-delay: 2s; }
                                </style>

                                <g id="db-icon">
                                    <g transform="translate(-25, 0) scale(7)">
                                        <path d="M3 5V19A9 3 0 0 0 21 19V5" class="db-body"/>
                                        <ellipse cx="12" cy="5" rx="9" ry="3" class="db-top"/>
                                        <path d="M3 12A9 3 0 0 0 21 12" class="db-body"/>
                                    </g>
                                </g>
                                <g id="db-icon-target">
                                    <g transform="translate(-25, 0) scale(7)">
                                        <path d="M3 5V19A9 3 0 0 0 21 19V5" class="db-body" style="fill: #e6f7ff; stroke: #46b8da;"/>
                                        <ellipse cx="12" cy="5" rx="9" ry="3" class="db-top" style="fill: #e6f7ff; stroke: #46b8da;"/>
                                        <path d="M3 12A9 3 0 0 0 21 12" class="db-body" style="fill: #e6f7ff; stroke: #46b8da;"/>
                                    </g>
                                </g>
                                <polygon id="dirty-packet-shape" points="-15,-15 -10,0 -15,15 0,10 15,15 10,0 15,-15 0,-10" class="packet-dirty" />
                                <rect id="clean-packet-shape" x="-12" y="-12" width="24" height="24" rx="4" ry="4" class="packet-clean" />
                            </defs>

                            <g transform="translate(40, 70)">
                                <text x="60" y="-20" class="text-label">Source DB</text>
                                <text x="60" y="-8" class="text-label-small">(Raw Data)</text>
                                <use href="#db-icon" />
                                <g clip-path="url(#db-clip)">
                                    <rect x="30" y="50" width="20" height="20" class="raw-data-static" transform="rotate(15)"/>
                                    <rect x="70" y="80" width="25" height="25" class="raw-data-static" transform="rotate(-10)"/>
                                    <rect x="90" y="90" width="15" height="15" class="raw-data-static" transform="rotate(30)"/>
                                </g>
                            </g>

                            <g transform="translate(640, 70)">
                                <text x="60" y="-20" class="text-label">Target DB</text>
                                <text x="60" y="-8" class="text-label-small">(Clean Data)</text>
                                <use href="#db-icon-target"/>
                                <g>
                                    <rect x="30" y="70" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="50" y="70" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="70" y="70" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="30" y="115" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="50" y="115" width="15" height="15" class="clean-data-static" rx="2"/>
                                    <rect x="70" y="115" width="15" height="15" class="clean-data-static" rx="2"/>
                                </g>
                            </g>

                            <g transform="translate(300, 50)">
                                <text x="100" y="0" class="text-label">ETL Process / Transform</text>
                                <text x="100" y="12" class="text-label-small">(App Transformation)</text>
                                <rect x="0" y="20" width="200" height="160" rx="10" ry="10" class="transform-box" />
                            </g>

                            <use href="#dirty-packet-shape" class="anim-packet-dirty-1" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-1" />

                            <use href="#dirty-packet-shape" class="anim-packet-dirty-2" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-2" />

                            <use href="#dirty-packet-shape" class="anim-packet-dirty-3" />
                            <use href="#clean-packet-shape" class="anim-packet-clean-3" />

                            <g transform="translate(400, 150)" class="ghost-hovering">
                                <use href="#ghost-icon" x="325" y="77" />
                            </g>
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
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">Trigger via API</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Start a cloning run with a single API call. Integrate it into any workflow, script, or pipeline.</p>
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
                                <h3 class="text-xl font-bold mb-2 text-gray-900 dark:text-white">One call to clone</h3>
                                <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Trigger a cloning run with curl or wget. Drop it into any pipeline step, cron job, or shell script — no special tooling needed.</p>
                            </div>
                        </div>
                        <div class="absolute bottom-0 left-4 right-4 h-1 bg-gradient-to-r from-orange-500 to-red-600 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left rounded-full"></div>
                    </div>
                </div>

                <!-- Integration Logos -->
                <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4 font-medium uppercase">Integrates with your favorite tools</p>
                    <div class="flex flex-wrap justify-center items-center gap-8">
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">GitLab CI</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">GitHub Actions</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Jenkins</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">CircleCI</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">BitBucket</div>
                        <div class="text-gray-400 dark:text-gray-600 font-semibold text-lg hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Azure DevOps</div>
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
                            <pre class="text-gray-100" aria-label="Code example for cloning databases"><code><span class="code-comment"># Trigger via API within your internal firewall boundaries</span>
<span class="code-function">curl</span> <span class="code-variable">-X</span> <span class="code-keyword">POST</span> <span class="code-string">https://&lt;your-clonio-instance&gt;/api/trigger/5f23fcede47385479ab59ca4e5d5de978911658fcd677480dce13076fe40f75c</span>
<span class="code-comment"># The `5f23fc...75c` is a hash for the exact cloning you want to execute</span></code></pre>
                        </div>
                    </div>
                </div>
                <div class="text-center mb-12">
                    Or it get's triggered manually or on a planned schedule.
                </div>

                <div class="text-center mb-12">
                    <a href="/docs" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-semibold rounded-xl transition-all transform hover:scale-105 shadow-lg hover:shadow-xl">
                        Explore documentation
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
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">Self-hosted only — by design. Your data never leaves your servers. Stays behind your firewalls, inside your audits, zero third-party risk.</p>
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
        <section id="pricing" class="relative px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950 py-24" aria-labelledby="pricing-heading">
            <div class="max-w-6xl mx-auto w-full pt-12">
                <h2 id="pricing-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center mb-4 text-gray-900 dark:text-white">
                    Fair pricing, transparent model
                </h2>
                <p class="text-xl text-center text-gray-600 dark:text-gray-300 mb-8">
                    Free forever for the community. Fair pricing for commerce.
                </p>

                <!-- 2026 Launch Pricing Banner -->
                <div class="flex justify-center mb-12">
                    <div class="inline-flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-full text-sm font-bold shadow-lg">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>2026 Early Adopter Pricing — our promise to all current customers for the whole year</span>
                    </div>
                </div>

                <!-- Pricing Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12 items-stretch">

                    <!-- Free Tier -->
                    <div class="relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg flex flex-col">
                        <div class="h-1 bg-gradient-to-r from-green-400 to-emerald-500"></div>
                        <div class="p-8 flex flex-col flex-1">
                            <div class="mb-6">
                                <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-950 text-green-700 dark:text-green-400 text-xs font-bold rounded-full mb-3 uppercase tracking-wide">Free Forever</span>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Community</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Individuals, hobby projects, NGOs &amp; open source</p>
                            </div>
                            <div class="mb-6">
                                <div class="flex items-baseline gap-1">
                                    <span class="text-5xl font-bold text-gray-900 dark:text-white">€0</span>
                                    <span class="text-gray-500 dark:text-gray-400 ml-1">/ forever</span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">No credit card. No license required. Ever.</p>
                            </div>
                            <ul class="space-y-3 mb-8 flex-1">
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Unlimited database clones</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">All anonymization types</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Full audit trail &amp; GDPR compliance</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">REST API &amp; webhooks</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Open source — inspect every line</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Self-hosted in your infrastructure</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Non-commercial use only</span>
                                </li>
                            </ul>
                            <a href="/docs" class="block text-center px-6 py-3 border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-500 dark:hover:text-primary-400 rounded-xl font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-primary-500">
                                Start Compliant Cloning
                            </a>
                            <p class="text-center text-xs text-gray-500 dark:text-gray-500 mt-3">Be responsible for compliant data</p>
                        </div>
                    </div>

                    <!-- SMB / Business Tier — Most Popular -->
                    <div class="relative group flex flex-col">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-primary-600 via-blue-600 to-indigo-600 rounded-2xl blur opacity-25 group-hover:opacity-45 transition duration-500"></div>
                        <div class="relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-primary-200 dark:border-primary-800 overflow-hidden shadow-2xl flex flex-col flex-1">
                            <div class="h-1 bg-gradient-to-r from-primary-600 via-blue-600 to-indigo-600"></div>
                            <!-- Most Popular badge -->
                            <div class="absolute top-5 right-5">
                                <span class="px-2.5 py-1 bg-primary-600 text-white text-xs font-bold rounded-full">Most Popular</span>
                            </div>
                            <div class="p-8 flex flex-col flex-1">
                                <div class="mb-6">
                                    <span class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-950 text-primary-700 dark:text-primary-400 text-xs font-bold rounded-full mb-3 uppercase tracking-wide">SMB Commercial</span>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Business</h3>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Commercial use · up to €1M net annual revenue</p>
                                </div>
                                <div class="mb-6">
                                    <div class="flex items-baseline gap-2 mb-1">
                                        <span class="text-5xl font-bold text-gray-900 dark:text-white">€39</span>
                                        <span class="text-gray-500 dark:text-gray-400">/ month</span>
                                    </div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-sm text-gray-400 dark:text-gray-500 line-through">€59 / month</span>
                                        <span class="text-xs font-bold px-2 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 rounded-full">2026 Launch Price</span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-500">€468 / year · billed annually in advance</p>
                                </div>
                                <ul class="space-y-3 mb-8 flex-1">
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">Unlimited database clones</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">All anonymization types</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">Full audit trail &amp; GDPR compliance</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">Schema-aware cloning</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">REST API &amp; webhooks</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">Priority email support</span>
                                    </li>
                                    <li class="flex items-start gap-2.5">
                                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="text-gray-700 dark:text-gray-300 text-sm">Fair use license · funds ongoing development</span>
                                    </li>
                                </ul>
                                <a href="/docs" class="group/btn relative block text-center px-6 py-3.5 bg-gradient-to-r from-primary-600 to-blue-600 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-primary-500 overflow-hidden">
                                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-600 to-indigo-600 opacity-0 group-hover/btn:opacity-100 transition-opacity duration-300"></span>
                                    <span class="relative">Start Compliant Cloning</span>
                                </a>
                                <p class="text-center text-xs text-gray-500 dark:text-gray-500 mt-3">60-day implementation period · then billed yearly</p>
                            </div>
                        </div>
                    </div>

                    <!-- Scale / Enterprise Tier -->
                    <div class="relative bg-white dark:bg-gray-900 rounded-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden shadow-lg flex flex-col">
                        <div class="h-1 bg-gradient-to-r from-violet-500 to-purple-600"></div>
                        <div class="p-8 flex flex-col flex-1">
                            <div class="mb-6">
                                <span class="inline-block px-3 py-1 bg-violet-100 dark:bg-violet-950 text-violet-700 dark:text-violet-400 text-xs font-bold rounded-full mb-3 uppercase tracking-wide">Enterprise</span>
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Scale</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">Commercial use · above €1M net annual revenue</p>
                            </div>
                            <div class="mb-6">
                                <div class="flex items-baseline gap-2 mb-1">
                                    <span class="text-5xl font-bold text-gray-900 dark:text-white">€99</span>
                                    <span class="text-gray-500 dark:text-gray-400">/ month</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-sm text-gray-400 dark:text-gray-500 line-through">€199 / month</span>
                                    <span class="text-xs font-bold px-2 py-0.5 bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 rounded-full">2026 Launch Price</span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-500">€1188 / year · billed annually in advance</p>
                            </div>
                            <ul class="space-y-3 mb-8 flex-1">
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Everything in Business</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Unlimited databases &amp; environments</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Full audit trail &amp; compliance reports</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">AI-friendly docs &amp; markdown export</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Priority support &amp; onboarding</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-gray-700 dark:text-gray-300 text-sm">Fair use license · funds ongoing development</span>
                                </li>
                            </ul>
                            <a href="/docs" class="block text-center px-6 py-3 border-2 border-violet-500 dark:border-violet-600 text-violet-700 dark:text-violet-400 hover:bg-violet-500 hover:text-white dark:hover:bg-violet-600 dark:hover:text-white rounded-xl font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-violet-500">
                                Start Compliant Cloning
                            </a>
                            <p class="text-center text-xs text-gray-500 dark:text-gray-500 mt-3">60-day implementation period · then billed yearly</p>
                        </div>
                    </div>
                </div>

                <!-- Self-hosting callout -->
                <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl p-8 mb-6">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-950 flex items-center justify-center">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Self-hosted only — by design, not by accident</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                Clonio needs access to your production database to do its job. That means it must live <strong class="text-gray-800 dark:text-gray-200">inside your infrastructure</strong> — behind your firewalls, compliant with your audits, and following your data governance rules. We can't guarantee those requirements from the outside, so we don't try. Keep Clonio behind your firewalls and you stay in full control. Your data never leaves your environment.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 60-day period explanation -->
                <div class="bg-blue-50 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-800 rounded-2xl p-8 mb-8">
                    <div class="flex items-start gap-5">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">60 days to integrate — no payment required upfront</h3>
                            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">
                                This is not a "try before you buy" trial. You can deploy Clonio and run it in your environment for <strong class="text-gray-800 dark:text-gray-200">60 full days without any payment</strong>. At day 61, commercial users are required to obtain a license. Revenue determines which tier applies — simple and transparent. Free for non-commercial use, always.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Trust indicators -->
                <div class="flex flex-wrap justify-center items-center gap-8 text-sm text-gray-500 dark:text-gray-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>GDPR Compliant</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                        </svg>
                        <span>Open Source</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"></path>
                        </svg>
                        <span>AI-Friendly Docs</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Behind Your Firewalls</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 9: FINAL CTA -->
        <section id="cta" class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8 bg-gray-950 overflow-hidden" aria-labelledby="cta-heading">

            <!-- Background grid -->
            <div class="absolute inset-0 opacity-[0.04]" aria-hidden="true" style="background-image: linear-gradient(rgba(255,255,255,.6) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.6) 1px, transparent 1px); background-size: 48px 48px;"></div>

            <!-- Ambient glows -->
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-primary-600 rounded-full opacity-10 blur-3xl" aria-hidden="true"></div>
            <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-blue-500 rounded-full opacity-10 blur-3xl" aria-hidden="true"></div>

            <div class="relative max-w-5xl mx-auto w-full">

                <!-- Top label -->
                <div class="flex justify-center mb-8">
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-gray-700 text-gray-400 text-sm font-medium tracking-wide">
                        <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse" aria-hidden="true"></span>
                        Self-hosted · Open source · GDPR-compliant
                    </span>
                </div>

                <!-- Headline -->
                <h2 id="cta-heading" class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white text-center mb-6 leading-tight tracking-tight">
                    Stop guessing.<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-blue-400">Clone with confidence.</span>
                </h2>

                <p class="text-lg sm:text-xl text-gray-400 text-center mb-12 max-w-2xl mx-auto leading-relaxed">
                    Deploy Clonio behind your firewalls. Connect your databases. Run your first compliant clone in minutes — free for 60 days, free forever for non-commercial use.
                </p>

                <!-- Terminal snippet -->
                <div class="mx-auto max-w-2xl mb-12">
                    <div class="rounded-2xl overflow-hidden border border-gray-800 shadow-2xl">
                        <!-- Terminal bar -->
                        <div class="flex items-center gap-2 px-4 py-3 bg-gray-900 border-b border-gray-800">
                            <span class="w-3 h-3 rounded-full bg-red-500 opacity-70" aria-hidden="true"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-500 opacity-70" aria-hidden="true"></span>
                            <span class="w-3 h-3 rounded-full bg-green-500 opacity-70" aria-hidden="true"></span>
                            <span class="ml-3 text-xs text-gray-500 font-mono">trigger a clone from anywhere</span>
                        </div>
                        <!-- Terminal body -->
                        <div class="bg-gray-950 px-6 py-5 font-mono text-sm leading-relaxed">
                            <p class="text-gray-600 mb-1"># one POST call is all it takes</p>
                            <p>
                                <span class="text-primary-400">curl</span>
                                <span class="text-gray-300"> --request POST \</span>
                            </p>
                            <p class="text-gray-300 pl-4">"https://<span class="text-blue-400">&lt;your-clonio-instance&gt;</span>/api/trigger/<span class="text-green-400">&lt;token&gt;</span>"</p>
                        </div>
                    </div>
                </div>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center mb-14">
                    <a href="/docs" class="group inline-flex items-center gap-3 px-8 py-4 bg-primary-600 hover:bg-primary-500 text-white text-lg font-semibold rounded-xl transition-all shadow-lg shadow-primary-900/40 hover:shadow-primary-900/60 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-950">
                        Read the docs
                        <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <a href="#pricing" class="inline-flex items-center gap-2 px-8 py-4 border border-gray-700 hover:border-gray-500 text-gray-300 hover:text-white text-lg font-semibold rounded-xl transition-all focus:outline-none focus:ring-2 focus:ring-gray-600">
                        See pricing
                    </a>
                </div>

                <!-- Trust strip -->
                <div class="flex flex-wrap justify-center items-center gap-x-10 gap-y-4 text-sm text-gray-600">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Free for 60 days
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        No credit card ever
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Open source
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Stays behind your firewalls
                    </span>
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Free forever for non-commercial use
                    </span>
                </div>
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
                            Absolutely. Clonio runs entirely within your infrastructure. Your data never leaves your environment — there is no external cloud service involved. All data transfer happens between your own databases, and anonymization is applied in-flight before data reaches the target. This is precisely why Clonio is self-hosted only.
                        </p>
                    </details>

                    <!-- FAQ 4 -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                What happens after the 60-day implementation period?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            Clonio gives you 60 days to integrate it into your infrastructure — no payment required. This is not a trial: you get full access to all features from day one. After day 60, commercial users need to purchase a license based on their annual net revenue. Free forever for individuals, hobby projects, NGOs, and open source. Revenue determines your tier, simple and transparent.
                        </p>
                    </details>

                    <!-- FAQ: Why self-hosted only? -->
                    <details class="group bg-gray-50 dark:bg-gray-900 rounded-xl p-6 hover:shadow-lg transition-shadow">
                        <summary class="flex justify-between items-center cursor-pointer list-none">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white pr-8">
                                Why is Clonio self-hosted only?
                            </h3>
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </summary>
                        <p class="mt-4 text-gray-700 dark:text-gray-300 leading-relaxed">
                            To clone your production database, Clonio needs a direct connection to it. Your production database must comply with your company's security audits, data governance policies, and GDPR requirements. These constraints cannot be guaranteed by an external cloud service — and we don't want to pretend otherwise. By running Clonio inside your own infrastructure, behind your firewalls, you stay in full control. Your data never travels outside your environment.
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
                            Yes. You can trigger a cloning run with a single API call — a plain curl or wget is all it takes. Drop it into any GitLab CI, GitHub Actions, Jenkins, or any other pipeline step. Schedule it via cron, or wire it up to a webhook. No special tooling required.
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
                            All plans include email support with a response within 24 hours. For enterprise customers, we offer dedicated support channels and custom SLAs. We're friendly and supportive — reach out anytime during your integration and beyond.
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
                            We provide detailed documentation and examples for common use cases — all packed inside Clonio itself and available as markdown, so AI-driven workflows can consume it directly. For complex scenarios, we offer onboarding sessions for enterprise plans.
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
                    <li><a href="/docs" class="hover:text-primary-400 transition-colors">Documentation</a></li>
                </ul>
            </div>

            <!-- Company Links -->
            <div>
                <h3 class="font-semibold text-white mb-4">Author</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="https://robert-kummer.de" class="hover:text-primary-400 transition-colors">Robert Kummer IT</a></li>
                    <li><a href="https://github.com/clonio-dev/" class="hover:text-primary-400 transition-colors">GitHub</a></li>
                </ul>
            </div>

            <!-- Legal Links -->
            <div>
                <h3 class="font-semibold text-white mb-4">Legal</h3>
                <ul class="space-y-2 text-sm" role="list">
                    <li><a href="{{ route('static.policy') }}" class="hover:text-primary-400 transition-colors">Privacy Policy</a> (<a href="{{ route('static.datenschutz') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
                    <li><a href="{{ route('static.terms') }}" class="hover:text-primary-400 transition-colors">Terms of Service</a> (<a href="{{ route('static.agb') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
                    <li><a href="{{ route('static.imprint') }}" class="hover:text-primary-400 transition-colors">Imprint</a> (<a href="{{ route('static.impressum') }}" class="hover:text-primary-400 transition-colors">de</a>)</li>
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
