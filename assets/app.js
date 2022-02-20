import './styles/app.scss';
import popper from './popper.min';

import * as $ from 'jquery';
global.$ = global.jQuery = $;

$('button.stas-top').click(function (e) {


    $('frida').attr('href', '/'+ $(this).attr('data-code') + '/pay');
    $('#exampleModal').modal('show');


})


require('bootstrap');


$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
});
