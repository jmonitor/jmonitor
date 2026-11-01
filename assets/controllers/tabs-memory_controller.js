import { Controller } from '@hotwired/stimulus';
import { Tab } from 'bootstrap';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['tab'];

    connect() {
        const key = window.location.hash.slice(1);
        const remembered = this.tabTargets.find((tab) => tab.dataset.tabsMemoryKeyParam === key);

        if (remembered) {
            Tab.getOrCreateInstance(remembered).show();
        }

        this.tabTargets.forEach((tab) => tab.addEventListener('shown.bs.tab', this.remember));
    }

    disconnect() {
        this.tabTargets.forEach((tab) => tab.removeEventListener('shown.bs.tab', this.remember));
    }

    remember = (event) => {
        history.replaceState(history.state, '', '#' + event.target.dataset.tabsMemoryKeyParam);
    };
}
