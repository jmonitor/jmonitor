import { Controller } from '@hotwired/stimulus';
import Toast from "../js/Toast.js";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    initialize() {
        this.onFetchResponse = this.onFetchResponse.bind(this);
        this.onFrameMissing = this.onFrameMissing.bind(this);
    }

    connect() {
        // before-fetch-response (rather than frame-render) to catch X-toasts even when no frame gets rendered
        document.addEventListener('turbo:before-fetch-response', this.onFetchResponse);
        document.addEventListener('turbo:frame-missing', this.onFrameMissing);
    }
    disconnect() {
        document.removeEventListener('turbo:before-fetch-response', this.onFetchResponse);
        document.removeEventListener('turbo:frame-missing', this.onFrameMissing);
    }

    // A response without a <turbo-frame> but carrying a toast (X-toasts header) is an
    // intentional no-op (e.g. access denied, see AccessDeniedListener): neutralize the
    // "Content missing" behavior to leave the frame untouched.
    onFrameMissing(event) {
        if (event.detail.response.headers.get('X-toasts')) {
            event.preventDefault();
        }
    }

    onFetchResponse(event) {
        const toasts = event.detail.fetchResponse.response.headers.get('X-toasts');

        if (toasts) {
            const toastsJson = JSON.parse(toasts);

            for (const [type, messages] of Object.entries(toastsJson)) {
                messages.forEach(message => {
                    new Toast(type, message);
                });
            }
        }
    }
}
