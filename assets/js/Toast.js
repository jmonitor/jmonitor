import Toastify from 'toastify-js';
import 'toast.css';

/**
 * A module to easily create a toast from JS
 * https://github.com/apvarun/toastify-js/blob/master/README.md
 */
export default class Toast {
    constructor(type, message) {
        Toastify({
            text: message,
            duration: 5000,
            close: false,
            gravity: "bottom",
            position: "right",
            stopOnFocus: true,
            className: "rounded-1 p-2 text-bg-"+type,
            onClick: function(){}
        }).showToast();
    }

    static success(message) {
        return new Toast('success', message);
    }

    static error(message) {
        return new Toast('danger', message);
    }
}
