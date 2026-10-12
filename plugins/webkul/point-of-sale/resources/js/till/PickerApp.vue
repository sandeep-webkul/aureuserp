<script setup>
import { inject, reactive, onMounted, onBeforeUnmount } from 'vue'

const picker = inject('picker')

const state = picker.state

const element = document.getElementById('pos-till')

const links = {
    backOffice: element?.dataset.backOfficeUrl ?? '#',
    logout: element?.dataset.logoutUrl ?? '#',
    brandLogo: element?.dataset.brandLogo ?? '',
}

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const clock = reactive({ value: '' })

let timer = null

function tick() {
    clock.value = new Date().toLocaleString()
}

onMounted(() => {
    tick()

    timer = window.setInterval(tick, 1000)
})

onBeforeUnmount(() => {
    if (timer) {
        window.clearInterval(timer)
    }
})
</script>

<template>
    <div class="fixed inset-0 flex min-h-0 flex-col bg-gray-50 dark:bg-gray-950">
        <header class="flex flex-none items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <img v-if="links.brandLogo" :src="links.brandLogo" alt="" class="h-8 w-auto">

            <div class="text-right">
                <h1 class="text-base font-semibold text-gray-950 dark:text-white">{{ picker.t('picker.title') }}</h1>

                <p class="font-mono text-sm tabular-nums text-gray-500 dark:text-gray-400">{{ clock.value }}</p>
            </div>
        </header>

        <div class="min-h-0 flex-auto overflow-y-auto px-6 pb-6">
            <div
                v-if="state.error"
                class="mx-auto mb-4 max-w-3xl rounded-lg border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-700 dark:border-danger-500/40 dark:bg-danger-500/10 dark:text-danger-300"
            >
                {{ state.error }}
            </div>

            <p v-if="state.loading" class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ picker.t('picker.opening') }}
            </p>

            <div
                v-else-if="!state.registers.length"
                class="mx-auto max-w-md rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900"
            >
                <p class="text-base font-semibold text-gray-950 dark:text-white">{{ picker.t('picker.empty.heading') }}</p>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ picker.t('picker.empty.description') }}</p>
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="register in state.registers"
                    :key="register.id"
                    class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900"
                >
                    <p class="text-base font-semibold text-gray-950 dark:text-white">{{ register.name }}</p>

                    <p v-if="register.code" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ register.code }}</p>

                    <div class="mt-5 flex items-center justify-between gap-3">
                        <span
                            v-if="register.live_session"
                            class="inline-flex items-center gap-1 rounded-md bg-success-100 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-500/20 dark:text-success-300"
                        >
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm6.39-2.908a.75.75 0 0 1 .766.027l3.5 2.25a.75.75 0 0 1 0 1.262l-3.5 2.25A.75.75 0 0 1 8 12.25v-4.5a.75.75 0 0 1 .39-.658Z" clip-rule="evenodd" /></svg>

                            {{ picker.t('picker.open-by', { name: register.live_session.user ?? '' }) }}
                        </span>

                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300"
                        >
                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 10a8 8 0 1 1 16 0 8 8 0 0 1-16 0Zm5-2.25A.75.75 0 0 1 7.75 7h4.5a.75.75 0 0 1 .75.75v4.5a.75.75 0 0 1-.75.75h-4.5a.75.75 0 0 1-.75-.75v-4.5Z" clip-rule="evenodd" /></svg>

                            {{ picker.t('picker.closed') }}
                        </span>

                        <div class="flex items-center gap-2">
                            <template v-if="register.is_discardable">
                                <button
                                    v-if="state.confirmingConfigId !== register.id"
                                    type="button"
                                    class="flex size-9 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                                    :aria-label="picker.t('picker.discard')"
                                    @click="state.confirmingConfigId = register.id"
                                >
                                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443A24 24 0 0 0 3.32 4.5a.75.75 0 0 0 .06 1.499l.46-.02.613 8.58A3 3 0 0 0 7.445 17.5h5.11a3 3 0 0 0 2.992-2.94l.613-8.581.46.02a.75.75 0 1 0 .06-1.5 24 24 0 0 0-2.68-.306V3.75A2.75 2.75 0 0 0 11.25 1h-2.5Z" clip-rule="evenodd" /></svg>
                                </button>

                                <button
                                    v-else
                                    type="button"
                                    class="shrink-0 rounded-lg bg-danger-600 px-3 py-2 text-xs font-semibold text-white hover:bg-danger-700"
                                    :disabled="state.busyConfigId === register.id"
                                    @click="picker.discard(register)"
                                >
                                    {{ picker.t('picker.discard-confirm.confirm') }}
                                </button>
                            </template>

                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-60"
                                :disabled="state.busyConfigId === register.id"
                                @click="picker.open(register)"
                            >
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.25 4.5a.75.75 0 0 1 1.14-.638l7 4.25a.75.75 0 0 1 0 1.276l-7 4.25A.75.75 0 0 1 8.25 13V4.5Z" clip-rule="evenodd" /></svg>

                                {{ state.busyConfigId === register.id
                                    ? picker.t('picker.opening')
                                    : (register.live_session ? picker.t('picker.resume') : picker.t('picker.open')) }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <footer class="flex flex-none items-center justify-between gap-3 border-t border-gray-200 px-6 py-3 dark:border-gray-800">
            <a
                :href="links.backOffice"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                {{ picker.t('picker.back-office') }}
            </a>

            <form :action="links.logout" method="POST">
                <input type="hidden" name="_token" :value="csrf">

                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-gray-500 hover:text-danger-600 dark:text-gray-400 dark:hover:text-danger-400"
                >
                    {{ picker.t('picker.logout') }}
                </button>
            </form>
        </footer>
    </div>
</template>
