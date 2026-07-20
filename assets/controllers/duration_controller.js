import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {seconds: Number};

    secondsValueChanged(value) {
        if (value) this.element.textContent = this.format(value);
    }

    format(s) {
        const d = Math.floor(s / 86400);
        const h = Math.floor((s % 86400) / 3600);
        const m = Math.floor((s % 3600) / 60);
        const sec = s % 60;

        const parts = [];
        if (d > 0) parts.push(`${d}d`);
        if (h > 0) parts.push(`${h}h`);
        if (m > 0) parts.push(`${m}m`);
        // Show the seconds only when no other unit is present.
        if (parts.length === 0) parts.push(`${sec}s`);

        return parts.join(' ');
    }
}
