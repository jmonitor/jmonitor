import './stimulus_bootstrap.js';

import 'bootstrap/dist/css/bootstrap.min.css';
import './css/app.css';

import annotationPlugin from 'chartjs-plugin-annotation';
import 'chartjs-adapter-moment';
// import 'chartjs-adapter-date-fns';
// import {fr} from 'date-fns/locale';

document.addEventListener('chartjs:init', function (event) {
    event.detail.Chart.register(annotationPlugin);
});
