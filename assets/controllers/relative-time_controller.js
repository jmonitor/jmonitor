import {Controller} from '@hotwired/stimulus';
import { formatDistanceToNow } from 'date-fns';
// import { fr } from 'date-fns/locale';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        timestamp: Number,
        live: {
            type: Boolean,
            default: false,
        },
    };

    timestampValueChanged(value) {
        this.update();
    }

    liveValueChanged(value) {
        if (value) {
            this.startTimer();
        } else {
            this.stopTimer();
        }
    }

    connect() {
        if (this.liveValue) {
            this.startTimer();
        }
    }

    disconnect() {
        this.stopTimer();
    }

    startTimer() {
        this.stopTimer();
        this.timer = setInterval(() => {
            this.update();
        }, 1000);
    }

    stopTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }

    update() {
        if (this.timestampValue) {
            this.element.textContent = this.format(this.timestampValue);
        }
    }

    format(timestamp) {
        const date = new Date(timestamp * 1000);

        return formatDistanceToNow(date, {
            addSuffix: true,
            includeSeconds: true,
        });
    }
}
