import { Controller } from '@hotwired/stimulus';
import { Dropdown, Offcanvas } from "bootstrap";
import Cookies from 'js-cookie'

export default class extends Controller {
    static targets = ['sidebar'];
    static values = {};

    connect() {
        const dropdownElementList = this.element.querySelectorAll('.dropdown-toggle');
        const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new Dropdown(dropdownToggleEl));
    }

    disconnect() {
        const dropdownElementList = this.element.querySelectorAll('.dropdown-toggle');
        const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new Dropdown(dropdownToggleEl));
        dropdownList.forEach(dropdown => dropdown.dispose());
    }

    toggleSidebar() {
        this.sidebarTarget.clientWidth > 0 ? this.#closeSidebar() : this.#openSidebar();
    }

    #openSidebar() {
        this.sidebarTarget.classList.add('sidebar-open');
        this.sidebarTarget.classList.remove('sidebar-closed');
        Cookies.set('sidebar_state', 'true');
    }

    #closeSidebar() {
        this.sidebarTarget.classList.add('sidebar-closed');
        this.sidebarTarget.classList.remove('sidebar-open');
        Cookies.set('sidebar_state', 'false');
    }

    turboCanvas(e) {
        e.preventDefault();

        // TODO could be improved: avoid referencing elements by id
        const turboFrame = document.querySelector('#tf-off-canvas-end');
        const offCanvas = Offcanvas.getOrCreateInstance(document.querySelector('#offcanvas-sidebar-end'));

        turboFrame.innerHTML = '';
        turboFrame.setAttribute('src', e.currentTarget.href);
        offCanvas.show();
    }
}
