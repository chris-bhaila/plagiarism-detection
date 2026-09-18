import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import $ from 'jquery';
window.jQuery = window.$ = $;

import 'toastr/build/toastr.min.css';
import toastr from 'toastr';
window.toastr = toastr;

toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 4000,
};
