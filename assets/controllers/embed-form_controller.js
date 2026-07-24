import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['form', 'autoRefreshInput'];

    // The Live toggle in the preview drives the form's hidden autoRefresh field.
    toggleLive(event) {
        this.autoRefreshInputTarget.value = event.target.checked ? '1' : '';
        this.formTarget.requestSubmit();
    }

    refreshPreview() {
        this.formTarget.requestSubmit();
    }
}
