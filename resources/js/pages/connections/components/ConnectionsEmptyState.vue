<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Cable, Database, Plus, Shield, Zap } from 'lucide-vue-next';

interface Emits {
    (e: 'create'): void;
}

const emit = defineEmits<Emits>();

function handleCreate() {
    emit('create');
}
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl border border-dashed border-border bg-gradient-to-br from-muted/30 via-background to-muted/50 px-6 py-16"
    >
        <!-- Decorative background elements -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <!-- Floating database icons -->
            <div
                class="absolute top-8 -right-4 opacity-[0.03] dark:opacity-[0.05]"
            >
                <Database class="h-48 w-48 rotate-12" />
            </div>
            <div
                class="absolute bottom-4 -left-8 opacity-[0.03] dark:opacity-[0.05]"
            >
                <Cable class="h-32 w-32 -rotate-12" />
            </div>

            <!-- Gradient orbs -->
            <div
                class="absolute top-1/4 left-1/4 h-64 w-64 rounded-full bg-emerald-500/5 blur-3xl"
            ></div>
            <div
                class="absolute right-1/4 bottom-1/4 h-48 w-48 rounded-full bg-teal-500/5 blur-3xl"
            ></div>
        </div>

        <div class="relative mx-auto max-w-lg text-center">
            <!-- Icon container with animation -->
            <div class="mb-6 inline-flex">
                <div class="relative">
                    <!-- Outer ring animation -->
                    <div
                        class="absolute inset-0 animate-ping rounded-full bg-emerald-500/20"
                    ></div>
                    <div
                        class="absolute inset-0 animate-pulse rounded-full bg-gradient-to-br from-emerald-500/10 to-teal-500/10"
                    ></div>

                    <!-- Main icon container -->
                    <div
                        class="relative flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500/20 to-teal-500/20 ring-1 ring-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10"
                    >
                        <Database
                            class="h-10 w-10 text-emerald-600 dark:text-emerald-400"
                        />

                        <!-- Small plus badge -->
                        <div
                            class="absolute -right-1 -bottom-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white shadow-lg shadow-emerald-500/30 dark:bg-emerald-500"
                        >
                            <Plus class="h-4 w-4" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text content -->
            <h2
                class="mb-2 text-xl font-semibold tracking-tight text-foreground"
            >
                No connections yet
            </h2>
            <p class="mb-8 text-sm text-muted-foreground">
                Add your first database connection to start transferring data
                between databases with full anonymization support.
            </p>

            <!-- CTA button -->
            <Button
                @click="handleCreate"
                size="lg"
                class="group gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 px-6 text-white shadow-lg shadow-emerald-500/25 transition-all hover:from-emerald-500 hover:to-teal-500 hover:shadow-xl hover:shadow-emerald-500/30 dark:shadow-emerald-500/15 dark:hover:shadow-emerald-500/25"
            >
                <Plus
                    class="h-4 w-4 transition-transform group-hover:rotate-90"
                />
                Create your first connection
            </Button>

            <!-- Feature highlights -->
            <div class="mt-12 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="group rounded-xl border border-border/50 bg-card/50 p-4 transition-all hover:border-border hover:bg-card hover:shadow-md"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 transition-transform group-hover:scale-110 dark:text-blue-400"
                    >
                        <Database class="h-5 w-5" />
                    </div>
                    <h3 class="mb-1 text-sm font-medium text-foreground">
                        Multi-database
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        MySQL, PostgreSQL, SQL Server
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-border/50 bg-card/50 p-4 transition-all hover:border-border hover:bg-card hover:shadow-md"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 transition-transform group-hover:scale-110 dark:text-emerald-400"
                    >
                        <Zap class="h-5 w-5" />
                    </div>
                    <h3 class="mb-1 text-sm font-medium text-foreground">
                        Fast transfers
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Efficient batch processing
                    </p>
                </div>

                <div
                    class="group rounded-xl border border-border/50 bg-card/50 p-4 transition-all hover:border-border hover:bg-card hover:shadow-md"
                >
                    <div
                        class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 transition-transform group-hover:scale-110 dark:text-amber-400"
                    >
                        <Shield class="h-5 w-5" />
                    </div>
                    <h3 class="mb-1 text-sm font-medium text-foreground">
                        Secure
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Encrypted credentials
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
