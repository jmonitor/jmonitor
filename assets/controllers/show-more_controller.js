import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['item', 'button'];
    static values = {
        visible: Number,
    };

    connect() {
        this.show(this.visibleValue);
    }

    expand() {
        this.show(this.itemTargets.length);
    }

    show(count) {
        this.itemTargets.forEach((item, index) => item.classList.toggle('d-none', index >= count));
        this.buttonTarget.classList.toggle('d-none', count >= this.itemTargets.length);
    }
}
