import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['btn', 'icon'];

    static values = {
        enabled: Boolean,
        topic: String,
        component: String
    };

    connect() {
        this.reconnectDelayMs = 1000;
        this.maxReconnectDelayMs = 30000;
        this.reconnectTimer = null;
    }

    disconnect() {
        this.disable();
    }

    enable() {
        this.#clearReconnectTimer();

        // withCredentials: sends the Mercure authorization cookie (subscriber JWT scoped to the project)
        this.eventSource = new EventSource(this.topicValue, { withCredentials: true });
        this.eventSource.onmessage = this.#onMessage.bind(this);
        this.eventSource.onerror  = this.#onError.bind(this);
        this.eventSource.onopen = this.#onOpen.bind(this);

        this.enableLoaderState();
    }

    disable() {
        this.#clearReconnectTimer();
        this.eventSource?.close();
        this.eventSource = null;

        this.disableLoaderState();
    }

    #onMessage(event) {
        const datas = JSON.parse(event.data);

        if (datas.components.includes(this.componentValue)) {
            this.triggerReload();
        }
    }

    // The Mercure connection probably dropped (server restart)
    #onError() {
        this.eventSource?.close();
        this.eventSource = null;

        this.#scheduleReconnect();
    }

    #onOpen() {
        // Reset the delay as soon as we manage to connect cleanly
        this.reconnectDelayMs = 1000;
    }

    #scheduleReconnect() {
        this.#clearReconnectTimer();

        this.reconnectTimer = window.setTimeout(() => {
            if (!this.enabledValue) return;
            this.enable();
        }, this.reconnectDelayMs);

        this.reconnectDelayMs = Math.min(
            this.reconnectDelayMs * 2,
            this.maxReconnectDelayMs
        );
    }

    #clearReconnectTimer() {
        if (this.reconnectTimer !== null) {
            window.clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
    }

    triggerReload() {
        Turbo.visit(window.location.href, { action: "replace" });
    }

    enableLoaderState() {
        this.iconTarget.classList.add('spin');
        this.btnTarget.disabled = true;
    }

    disableLoaderState() {
        this.iconTarget.classList.remove('spin');
        this.btnTarget.disabled = false;
    }

    enabledValueChanged(enabled) {
        enabled ? this.enable() : this.disable();
    }
}
