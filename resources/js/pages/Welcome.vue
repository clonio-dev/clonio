<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/Laravel/Fortify/Http/Controllers/AuthenticatedSessionController';
import RegisteredUserController from '@/actions/Laravel/Fortify/Http/Controllers/RegisteredUserController';
import { Head, Link } from '@inertiajs/vue3';
import { Database, FileText, Shield } from 'lucide-vue-next';
</script>

<template>
    <Head title="Welcome — Clonio" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background px-4 py-16"
    >
        <div class="w-full max-w-2xl">
            <!-- Logo / Brand -->
            <div class="mb-8 flex items-center justify-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-cyan-100 dark:bg-cyan-950/50"
                >
                    <Database class="size-5 text-cyan-600 dark:text-cyan-400" />
                </div>
                <span
                    class="text-lg font-semibold tracking-tight text-foreground"
                    >Clonio</span
                >
            </div>

            <!-- Main card -->
            <div
                class="rounded-2xl border border-border/60 bg-card px-8 py-10 shadow-sm dark:border-border/40"
            >
                <!-- Heading -->
                <div class="mb-6 text-center">
                    <h1
                        class="mb-2 text-2xl font-semibold tracking-tight text-foreground"
                    >
                        Welcome to Clonio
                    </h1>
                    <p class="text-base text-muted-foreground">
                        Your self-hosted, GDPR-compliant database cloning tool.
                    </p>
                </div>

                <!-- Description -->
                <p
                    class="mb-8 text-center text-sm leading-relaxed text-muted-foreground"
                >
                    Clone production databases to test or staging environments
                    with automatic anonymization, schema versioning, and a
                    cryptographically signed audit trail — all running inside
                    your own infrastructure.
                </p>

                <!-- Feature strip -->
                <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div
                        class="flex flex-col items-center rounded-xl border border-border/40 bg-muted/30 px-4 py-5 text-center dark:border-border/20 dark:bg-muted/10"
                    >
                        <div
                            class="mb-3 flex size-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-950/50"
                        >
                            <Shield
                                class="size-5 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                        <span class="text-sm font-medium text-foreground"
                            >GDPR-compliant by design</span
                        >
                    </div>

                    <div
                        class="flex flex-col items-center rounded-xl border border-border/40 bg-muted/30 px-4 py-5 text-center dark:border-border/20 dark:bg-muted/10"
                    >
                        <div
                            class="mb-3 flex size-10 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-950/50"
                        >
                            <FileText
                                class="size-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                        <span class="text-sm font-medium text-foreground"
                            >Cryptographic audit trail</span
                        >
                    </div>

                    <div
                        class="flex flex-col items-center rounded-xl border border-border/40 bg-muted/30 px-4 py-5 text-center dark:border-border/20 dark:bg-muted/10"
                    >
                        <div
                            class="mb-3 flex size-10 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-950/50"
                        >
                            <Database
                                class="size-5 text-cyan-600 dark:text-cyan-400"
                            />
                        </div>
                        <span class="text-sm font-medium text-foreground"
                            >Schema-aware cloning</span
                        >
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a
                        href="/docs"
                        class="inline-flex items-center justify-center rounded-lg bg-foreground px-5 py-2.5 text-sm font-medium text-background transition-opacity hover:opacity-80 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Read the docs
                    </a>
                    <Link
                        :href="AuthenticatedSessionController.create().url"
                        class="inline-flex items-center justify-center rounded-lg border border-border bg-transparent px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Login
                    </Link>
                    <Link
                        :href="RegisteredUserController.create().url"
                        class="inline-flex items-center justify-center rounded-lg border border-border bg-transparent px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        Sign up
                    </Link>
                </div>
            </div>

            <!-- Footer note -->
            <p class="mt-6 text-center text-xs text-muted-foreground">
                Clonio runs entirely inside your infrastructure. Your data never
                leaves your network.
            </p>
        </div>
    </div>
</template>
