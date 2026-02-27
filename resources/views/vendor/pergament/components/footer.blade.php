{{-- Footer --}}
{{--<footer class="border-t border-gray-200 dark:border-gray-700 pergament-bg print:hidden">--}}
<footer class="bg-gray-900 dark:bg-black text-gray-300 py-12 px-4 sm:px-6 lg:px-8" role="contentinfo">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-8 mb-8">
            <!-- Company Info -->
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-primary-500" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" viewBox="0 0 144 144">
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
                    <li><a href="/#features" class="hover:text-primary-400 transition-colors">Features</a></li>
                    <li><a href="/#pricing" class="hover:text-primary-400 transition-colors">Pricing</a></li>
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
