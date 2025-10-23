/**
 * SVG Ready – Clipboard + Theme Toggle
 * Works reliably for localhost and HTTPS.
 */

document.addEventListener('DOMContentLoaded', () => {
	// THEME TOGGLE
	const toggle = document.getElementById('theme-toggle');
	const saved = localStorage.getItem('theme');

	if (saved) {
		document.body.classList.toggle('dark', saved === 'dark');
	} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		document.body.classList.add('dark');
	}

	if (toggle) {
		toggle.addEventListener('click', () => {
			const isDark = document.body.classList.toggle('dark');
			localStorage.setItem('theme', isDark ? 'dark' : 'light');
		});
	}

	// COPY FUNCTION
	async function copyToClipboard(text, button) {
		try {
			if (navigator.clipboard && window.isSecureContext) {
				await navigator.clipboard.writeText(text);
				setCopiedState(button, true);
			} else {
				fallbackCopy(text, button);
			}
		} catch {
			setCopiedState(button, false);
		}
	}

	function fallbackCopy(text, button) {
		const textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();
		try {
			document.execCommand('copy');
			setCopiedState(button, true);
		} catch {
			setCopiedState(button, false);
		} finally {
			document.body.removeChild(textarea);
		}
	}

	function setCopiedState(button, success) {
		const original = button.textContent;
		button.textContent = success ? 'Copied!' : 'Failed';
		button.classList.add(success ? 'copied' : 'error');
		setTimeout(() => {
			button.textContent = original;
			button.classList.remove('copied', 'error');
		}, 2000);
	}

	// EVENT HANDLER (DELEGATED)
	document.body.addEventListener('click', (e) => {
		const button = e.target.closest('.copy-btn');
		if (!button) return;
		const text = button.dataset.copy || button.closest('article')?.querySelector('code')?.textContent;
		if (text) copyToClipboard(text.trim(), button);
	});
});
