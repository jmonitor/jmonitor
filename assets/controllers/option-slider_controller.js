import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['value', 'badge', 'reset'];
    static values = { default: Number };

    connect() {
        this.input = this.element.querySelector('input[type="range"]');
        this.render();
    }

    // Live readout while dragging; the form submits itself on `change`, at release.
    update() {
        this.render();
    }

    reset() {
        this.input.value = this.defaultValue;
        this.render();
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    render() {
        const current = parseFloat(this.input.value);
        const isDefault = Math.abs(current - this.defaultValue) < 1e-9;

        this.valueTarget.textContent = current.toFixed(this.decimals());
        this.badgeTarget.classList.toggle('d-none', !isDefault);
        this.resetTarget.classList.toggle('d-none', isDefault);
    }

    // Number of decimals to display, derived from the widget's own step so the readout
    // matches what the slider can actually produce (e.g. step="0.01" -> 2 decimals).
    decimals() {
        const step = parseFloat(this.input.step);
        if (!Number.isFinite(step) || step <= 0) {
            return 1;
        }

        const fraction = String(step).split('.')[1];
        return fraction ? fraction.length : 0;
    }
}
