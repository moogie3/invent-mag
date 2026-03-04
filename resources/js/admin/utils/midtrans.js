export function loadMidtrans() {
    return new Promise((resolve, reject) => {
        console.log('Loading Midtrans...');
        
        if (typeof snap !== 'undefined') {
            console.log('Snap already loaded');
            resolve();
            return;
        }

        const payButton = document.getElementById('pay-button');
        if (!payButton) {
            reject(new Error('Pay button not found'));
            return;
        }
        
        const isProduction = payButton.dataset.isProduction === 'true';
        const clientKey = payButton.dataset.clientKey;

        console.log('Is production:', isProduction);
        console.log('Client key:', clientKey);

        if (!clientKey) {
            reject(new Error('Client key not found'));
            return;
        }

        const script = document.createElement('script');
        script.src = isProduction 
            ? 'https://app.midtrans.com/snap/snap.js' 
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', clientKey);
        script.onload = () => {
            console.log('Snap.js loaded successfully');
            resolve();
        };
        script.onerror = () => {
            console.error('Failed to load Snap.js');
            reject(new Error('Failed to load Snap.js'));
        };
        document.head.appendChild(script);
    });
}
