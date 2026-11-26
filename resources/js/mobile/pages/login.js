import { consumeFlash, isAuthed, login, msg, setToken } from '../api.js';
import { toast } from '../ui.js';

export function initLoginPage() {
    const form = document.getElementById('login-form');
    if (!form) {
        return;
    }

    // A bounce off an authenticated page leaves its reason behind — surface it
    // so the user knows why they are looking at a login form again.
    const flash = consumeFlash();
    if (flash) {
        toast(flash, 4200);
    }

    // Already holding a token: the shell would just bounce straight back.
    if (isAuthed()) {
        window.location.replace('/m');
        return;
    }

    const errorBox = document.getElementById('login-error');
    const errorText = document.getElementById('login-error-text');
    const submit = document.getElementById('login-submit');
    const email = form.elements.email;
    const password = form.elements.password;

    const setBusy = (busy) => {
        submit.classList.toggle('is-busy', busy);
        submit.disabled = busy;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.hidden = true;
        setBusy(true);

        try {
            const data = await login(email.value.trim(), password.value);

            if (!data || !data.token) {
                throw Object.assign(new Error(msg('msgInvalidCredentials', 'That email and password do not match.')), {
                    status: 401,
                });
            }

            setToken(data.token);
            window.location.replace('/m');
        } catch (err) {
            // 401 covers a wrong password, an unknown account and an empty
            // form alike — the API answers "Invalid login details" for all
            // three on purpose, so never echo which field was wrong.
            const text =
                err.status === 401
                    ? msg('msgInvalidCredentials', 'That email and password do not match.')
                    : err.message;

            errorText.textContent = text;
            errorBox.hidden = false;
            toast(text, 4200);

            setBusy(false);
            password.value = '';
            password.focus();
        }
    });
}
