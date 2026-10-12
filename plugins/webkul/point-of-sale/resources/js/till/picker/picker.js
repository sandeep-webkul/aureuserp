import { reactive } from 'vue'

const ACTIVE_SESSION_KEY = 'pos-active-session'

function csrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

export class Picker {
    constructor(endpoints, strings) {
        this.endpoints = endpoints
        this.strings = strings ?? {}

        this.state = reactive({
            loading: true,
            error: null,
            busyConfigId: null,
            confirmingConfigId: null,
            registers: [],
        })
    }

    t(key, replacements = {}) {
        const value = key.split('.').reduce((carry, part) => (carry == null ? carry : carry[part]), this.strings)

        if (typeof value !== 'string') {
            return key
        }

        return Object.entries(replacements).reduce(
            (text, [token, replacement]) => text.replaceAll(`:${token}`, replacement),
            value,
        )
    }

    async request(url, method = 'GET', body = null) {
        let response

        try {
            response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: body ? JSON.stringify(body) : null,
            })
        } catch {
            throw new Error(this.t('picker.failed'))
        }

        const payload = await response.json().catch(() => ({}))

        if (!response.ok) {
            throw new Error(payload.message ?? this.t('picker.failed'))
        }

        return payload
    }

    async load() {
        this.state.loading = true
        this.state.error = null

        try {
            const payload = await this.request(this.endpoints.registers)

            this.state.registers = payload.data ?? []

            const resumeId = this.autoResumeId(payload.own_session_id)

            if (resumeId) {
                await this.boot(resumeId)

                return
            }
        } catch (error) {
            this.state.error = error.message
        } finally {
            this.state.loading = false
        }
    }

    autoResumeId(ownSessionId) {
        const stored = Number(window.localStorage.getItem(ACTIVE_SESSION_KEY)) || null

        const live = this.state.registers
            .map((register) => register.live_session?.id)
            .filter(Boolean)

        if (stored && live.includes(stored)) {
            return stored
        }

        if (ownSessionId && live.includes(ownSessionId)) {
            return ownSessionId
        }

        return null
    }

    async open(register) {
        if (register.live_session) {
            await this.resume(register.live_session.id)

            return
        }

        if (!navigator.onLine) {
            this.state.error = this.t('picker.offline')

            return
        }

        this.state.error = null
        this.state.busyConfigId = register.id

        try {
            const payload = await this.request(this.endpoints.open.replace('__config__', register.id), 'POST')

            await this.boot(payload.data.id, true)
        } catch (error) {
            this.state.error = error.message
            this.state.busyConfigId = null
        }
    }

    async resume(sessionId) {
        if (!navigator.onLine) {
            this.state.error = this.t('picker.offline')

            return
        }

        this.state.error = null

        await this.boot(sessionId)
    }

    async discard(register) {
        this.state.error = null
        this.state.busyConfigId = register.id

        try {
            await this.request(this.endpoints.discard.replace('__config__', register.id), 'POST')

            this.state.confirmingConfigId = null

            await this.load()
        } catch (error) {
            this.state.error = error.message
        } finally {
            this.state.busyConfigId = null
        }
    }

    boot(sessionId, fresh = false) {
        window.localStorage.setItem(ACTIVE_SESSION_KEY, String(sessionId))

        const url = this.endpoints.terminal.replace('__session__', sessionId)

        window.location.href = fresh ? `${url}?opening=1` : url
    }
}
