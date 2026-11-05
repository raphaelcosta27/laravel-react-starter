const ALERT_ID = 'network-error';

function showAlert(message) {
    if (document.getElementById(ALERT_ID)) {
        return;
    }

    const alert = document.createElement('div');

    alert.id = ALERT_ID;
    alert.setAttribute('role', 'alert');
    alert.className =
        'fixed inset-x-0 top-0 z-50 bg-red-700 p-4 text-center text-white';
    alert.textContent = message;

    document.body.append(alert);
}

export function registerNetworkErrorHandler(router) {
    router.on('exception', (event) => {
        event.preventDefault();
        showAlert(document.documentElement.dataset.networkError ?? '');
    });
}
