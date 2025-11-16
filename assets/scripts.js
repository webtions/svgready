/**
 * SVG Ready – Clipboard, Theme Toggle, Upload, Save, AJAX Convert
 * Fully optimised, typed (JSDoc), with error boundaries. Uses direct download only.
 */

document.addEventListener('DOMContentLoaded', () => {

	/* ==========================
	   THEME TOGGLE
	   ========================== */

	/** @type {HTMLElement|null} */
	const toggle = document.getElementById('theme-toggle');

	/** @type {string|null} */
	const savedTheme = localStorage.getItem('theme');

	/** @type {HTMLElement} */
	const root = document.documentElement;

	try {
		if (savedTheme) {
			root.classList.toggle('dark', savedTheme === 'dark');
		} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
			root.classList.add('dark');
		}
	} catch {}

	if (toggle) {
		toggle.addEventListener('click', () => {
			try {
				const dark = root.classList.toggle('dark');
				localStorage.setItem('theme', dark ? 'dark' : 'light');
			} catch {}
		});
	}

	/* ==========================
	   SHARED ELEMENTS
	   ========================== */

	/** @type {HTMLElement|null} */
	const outputSection = document.querySelector('.output-section');

	/** @type {HTMLFormElement|null} */
	const form = document.querySelector('.input-section form');

	/** @type {HTMLTextAreaElement|null} */
	const svgInput = form ? form.querySelector('#svg') : null;

	/* ==========================
	   UPLOAD → TEXTAREA
	   ========================== */

	/** @type {HTMLElement|null} */
	const uploadLink = document.querySelector('.upload-link');

	/** @type {HTMLInputElement|null} */
	const svgFileInput = document.getElementById('svgFile');

	if (uploadLink && svgFileInput && svgInput) {
		uploadLink.addEventListener('click', () => {
			try {
				svgFileInput.click();
			} catch {}
		});

		svgFileInput.addEventListener('change', () => {
			const file = svgFileInput.files?.[0];
			if (!file) return;

			if (!file.name.toLowerCase().endsWith('.svg')) {
				if (outputSection) {
					outputSection.classList.add('has-error');
					outputSection.innerHTML = `
						<div class="error-state" role="alert">
							<span class="error-icon"></span>
							<h3>Invalid SVG file</h3>
							<p>Please upload a valid .svg file.</p>
						</div>
					`;
				}
				svgFileInput.value = '';
				return;
			}

			try {
				const reader = new FileReader();
				reader.onload = e => {
					if (svgInput) svgInput.value = e.target?.result || '';
				};
				reader.readAsText(file);
			} catch {}
		});
	}

	/* ==========================
	   COPY HANDLING
	   ========================== */

	/**
	 * @param {string} text
	 * @param {HTMLButtonElement} button
	 */
	async function copyToClipboard(text, button) {
		try {
			if (navigator.clipboard && window.isSecureContext) {
				await navigator.clipboard.writeText(text);
				setCopyState(button, true);
			} else {
				fallbackCopy(text, button);
			}
		} catch {
			setCopyState(button, false);
		}
	}

	/**
	 * @param {string} text
	 * @param {HTMLButtonElement} button
	 */
	function fallbackCopy(text, button) {
		try {
			const temp = document.createElement('textarea');
			temp.value = text;
			temp.style.position = 'fixed';
			temp.style.left = '-9999px';
			document.body.appendChild(temp);
			temp.select();

			try {
				document.execCommand('copy');
				setCopyState(button, true);
			} catch {
				setCopyState(button, false);
			}

			document.body.removeChild(temp);
		} catch {
			setCopyState(button, false);
		}
	}

	/**
	 * @param {HTMLButtonElement} button
	 * @param {boolean} ok
	 */
	function setCopyState(button, ok) {
		const original = button.textContent;
		button.textContent = ok ? 'Copied!' : 'Failed';
		button.classList.add(ok ? 'copied' : 'error');
		setTimeout(() => {
			button.textContent = original;
			button.classList.remove('copied', 'error');
		}, 2000);
	}

	document.body.addEventListener('click', e => {
		/** @type {HTMLButtonElement|null} */
		const button = e.target.closest('.copy-btn');
		if (!button) return;

		try {
			const code = button.closest('article')
				?.querySelector('code')
				?.innerText.trim();

			if (code) copyToClipboard(code, button);
		} catch {}
	});

	/* ==========================
	   SAVE AS SVG (DIRECT DOWNLOAD)
	   ========================== */

	/**
	 * @param {string} text
	 * @param {HTMLButtonElement} button
	 */
	function saveSvgFile(text, button) {
		const filename = `SvgReady-${Date.now()}.svg`;

		try {
			const blob = new Blob([text], { type: 'image/svg+xml' });
			const url = URL.createObjectURL(blob);

			const a = document.createElement('a');
			a.href = url;
			a.download = filename;

			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);

			setTimeout(() => URL.revokeObjectURL(url), 150);

			setSaveState(button, true);

		} catch {
			setSaveState(button, false);
		}
	}

	/**
	 * @param {HTMLButtonElement} button
	 * @param {boolean} ok
	 */
	function setSaveState(button, ok) {
		const original = button.textContent;
		button.textContent = ok ? 'Saved!' : 'Failed';
		button.classList.add(ok ? 'saved' : 'error');
		setTimeout(() => {
			button.textContent = original;
			button.classList.remove('saved', 'error');
		}, 2000);
	}

	document.body.addEventListener('click', e => {
		/** @type {HTMLButtonElement|null} */
		const button = e.target.closest('.save-file-btn');
		if (!button) return;

		try {
			const section = button.closest('.output-section') || outputSection;
			if (!section) return;

			const article = [...section.querySelectorAll('.result-block')]
				.find(a => a.querySelector('h4')?.textContent.includes('Normalized SVG'));

			const code = article?.querySelector('code')?.innerText.trim();
			if (code) saveSvgFile(code, button);

		} catch {}
	});

	/* ==========================
	   AJAX CONVERSION
	   ========================== */
	if (form && outputSection && svgInput) {
		form.addEventListener('submit', async e => {
			e.preventDefault();

			if (!svgInput.value.trim()) {
				outputSection.classList.add('has-error');
				outputSection.innerHTML = `
					<div class="error-state" role="alert">
						<span class="error-icon"></span>
						<h3>Nothing to convert</h3>
						<p>Please paste your SVG markup first.</p>
					</div>
				`;
				return;
			}

			outputSection.classList.remove('has-error');
			outputSection.innerHTML = '<div class="empty-state"><p>Processing...</p></div>';

			try {
				const res = await fetch('ajax.php', {
					method: 'POST',
					body: new FormData(form)
				});

				const html = await res.text();

				outputSection.classList.toggle('has-error', html.includes('error-state'));
				outputSection.innerHTML = html;
			} catch {
				outputSection.classList.add('has-error');
				outputSection.innerHTML = `
					<div class="error-state" role="alert">
						<span class="error-icon"></span>
						<h3>Something went wrong</h3>
						<p>Network error. Please try again.</p>
					</div>
				`;
			}
		});

		svgInput.addEventListener('keydown', e => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
				try {
					form.requestSubmit();
				} catch {}
			}
		});
	}
});
