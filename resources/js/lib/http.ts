import axios from 'axios';

/**
 * Cliente HTTP para las peticiones de datos (XHR, no navegación Inertia).
 * Envía la cookie XSRF de Laravel y marca las peticiones como AJAX.
 */
const http = axios.create({
    withCredentials: true,
    withXSRFToken: true,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});

export default http;
