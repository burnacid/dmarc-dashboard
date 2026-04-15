import './bootstrap';

import Alpine from 'alpinejs';
import Webpass from '@laragear/webpass';

window.Alpine = Alpine;

Alpine.start();

const setMessage = (target, message, tone = 'neutral') => {
	if (! target) {
		return;
	}

	target.textContent = message;
	target.classList.remove('text-slate-300', 'text-emerald-300', 'text-rose-300', 'text-amber-300');

	target.classList.add({
		neutral: 'text-slate-300',
		success: 'text-emerald-300',
		error: 'text-rose-300',
		warning: 'text-amber-300',
	}[tone] ?? 'text-slate-300');
};

const toggleBusy = (button, busy) => {
	if (! button) {
		return;
	}

	button.disabled = busy;
	button.classList.toggle('opacity-60', busy);
	button.classList.toggle('cursor-not-allowed', busy);
};

const normaliseError = (error, fallback) => {
	if (! error) {
		return fallback;
	}

	if (typeof error === 'string') {
		return error;
	}

	return error.data?.message ?? error.message ?? fallback;
};

const bindPasskeyLogin = async () => {
	const button = document.querySelector('[data-passkey-login]');

	if (! button) {
		return;
	}

	const messageTarget = document.querySelector(button.dataset.messageTarget);
	const emailInput = document.querySelector(button.dataset.emailInput);
	const rememberInput = document.querySelector('#remember_me');

	if (Webpass.isUnsupported()) {
		setMessage(messageTarget, 'This browser or device does not support passkeys.', 'warning');
		button.disabled = true;

		return;
	}

	button.addEventListener('click', async () => {
		toggleBusy(button, true);
		setMessage(messageTarget, 'Waiting for your device to confirm the passkey…');

		try {
			const remember = Boolean(rememberInput?.checked);
			const email = emailInput?.value?.trim();
			const options = email
				? {
					path: '/webauthn/login/options',
					body: { email },
				}
				: '/webauthn/login/options';
			const assertion = remember
				? {
					path: '/webauthn/login',
					headers: {
						'X-WebAuthn-Remember': '1',
					},
				}
				: '/webauthn/login';

			const { success, data, error } = await Webpass.assert(options, assertion);

			if (! success) {
				setMessage(messageTarget, normaliseError(error, 'Passkey sign in failed. Please try again.'), 'error');

				return;
			}

			setMessage(messageTarget, 'Passkey accepted. Redirecting…', 'success');
			window.location.assign(data?.redirect ?? '/dashboard');
		} catch (error) {
			setMessage(messageTarget, normaliseError(error, 'Passkey sign in failed. Please try again.'), 'error');
		} finally {
			toggleBusy(button, false);
		}
	});
};

const bindPasskeyRegistration = async () => {
	const button = document.querySelector('[data-passkey-register]');

	if (! button) {
		return;
	}

	const messageTarget = document.querySelector(button.dataset.messageTarget);
	const aliasInput = document.querySelector(button.dataset.aliasInput);

	if (Webpass.isUnsupported()) {
		setMessage(messageTarget, 'This browser or device does not support passkey registration.', 'warning');
		button.disabled = true;

		return;
	}

	button.addEventListener('click', async () => {
		toggleBusy(button, true);
		setMessage(messageTarget, 'Preparing passkey registration…');

		try {
			const alias = aliasInput?.value?.trim() ?? '';

			const { success, data, error } = await Webpass.attest(
				'/webauthn/register/options',
				{
					path: '/webauthn/register',
					body: alias ? { alias } : {},
				},
			);

			if (! success) {
				setMessage(messageTarget, normaliseError(error, 'Passkey registration failed. Please try again.'), 'error');

				return;
			}

			setMessage(messageTarget, data?.message ?? 'Passkey registered successfully.', 'success');

			window.setTimeout(() => window.location.reload(), 600);
		} catch (error) {
			setMessage(messageTarget, normaliseError(error, 'Passkey registration failed. Please try again.'), 'error');
		} finally {
			toggleBusy(button, false);
		}
	});
};

bindPasskeyLogin();
bindPasskeyRegistration();

