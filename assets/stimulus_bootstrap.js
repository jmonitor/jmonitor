import { startStimulusApp } from '@symfony/stimulus-bundle';
import Clipboard from '@stimulus-components/clipboard'

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('clipboard', Clipboard);
