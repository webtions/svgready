/**
 * SVG Ready – Clipboard, Theme Toggle, and AJAX Conversion
 * Simple client-side logic for copying, theme switching, and async conversion.
 */

document.addEventListener('DOMContentLoaded', () => {

	/* ==========================
	   THEME TOGGLE
	   ========================== */
	const toggle = document.getElementById('theme-toggle');
	const saved = localStorage.getItem('theme');
	const root = document.documentElement;

	if (saved) {
		root.classList.toggle('dark', saved === 'dark');
	} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		root.classList.add('dark');
	}

	if (toggle) {
		toggle.addEventListener('click', () => {
			const isDark = root.classList.toggle('dark');
			localStorage.setItem('theme', isDark ? 'dark' : 'light');
		});
	}

	/* ==========================
	   SHARED ELEMENTS
	   ========================== */
	const outputSection = document.querySelector('.output-section');
	const form = document.querySelector('.input-section form');
	const svgInput = form ? form.querySelector('#svg') : null;

	/* ==========================
	   SVG FILE UPLOAD → TEXTAREA
	   ========================== */
	const uploadLink = document.querySelector('.upload-link');
	const svgFileInput = document.getElementById('svgFile');

	if (uploadLink && svgFileInput && svgInput) {
		uploadLink.addEventListener('click', () => {
			svgFileInput.click();
		});

		svgFileInput.addEventListener('change', () => {
			const file = svgFileInput.files[0];
			if (!file) return;

			if (!file.name.toLowerCase().endsWith('.svg')) {
				if (outputSection) {
					outputSection.classList.add('has-error');
					outputSection.innerHTML = `
						<div class="error-state" role="alert">
							<span class="error-icon" aria-hidden="true"></span>
							<h3>Invalid SVG file</h3>
							<p>Please upload a valid .svg file.</p>
						</div>
					`;
				}

				svgFileInput.value = '';
				return;
			}

			const reader = new FileReader();
			reader.onload = (e) => {
				svgInput.value = e.target.result;
			};

			reader.readAsText(file);
		});
	}

	/* ==========================
	   COPY BUTTON HANDLING
	   ========================== */
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
		const temp = document.createElement('textarea');
		temp.value = text;
		temp.style.position = 'fixed';
		temp.style.left = '-9999px';
		document.body.appendChild(temp);
		temp.select();

		try {
			document.execCommand('copy');
			setCopiedState(button, true);
		} catch {
			setCopiedState(button, false);
		} finally {
			document.body.removeChild(temp);
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

	document.body.addEventListener('click', (e) => {
		const button = e.target.closest('.copy-btn');
		if (!button) return;

		const codeEl = button.closest('article')?.querySelector('code');
		if (!codeEl) return;

		const text = codeEl.innerText.trim();
		if (!text) return;

		copyToClipboard(text, button);
	});

	/* ==========================
	   SAVE FILE BUTTON HANDLING
	   ========================== */
	async function saveSvgFile(text, button) {
		const filename = `SvgReady-${Date.now()}.svg`;

		try {
			if ('showSaveFilePicker' in window) {
				try {
					const fileHandle = await window.showSaveFilePicker({
						suggestedName: filename,
						types: [{
							description: 'SVG files',
							accept: { 'image/svg+xml': ['.svg'] }
						}]
					});

					const writable = await fileHandle.createWritable();
					await writable.write(text);
					await writable.close();

					setSavedState(button, true);
					return;
				} catch (err) {
					if (err.name === 'AbortError') return;
				}
			}

			// Fallback
			const blob = new Blob([text], { type: 'image/svg+xml' });
			const url = URL.createObjectURL(blob);

			const a = document.createElement('a');
			a.href = url;
			a.download = filename;
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);

			setTimeout(() => URL.revokeObjectURL(url), 100);

			setSavedState(button, true);

		} catch {
			setSavedState(button, false);
		}
	}

	function setSavedState(button, success) {
		const original = button.textContent;
		button.textContent = success ? 'Saved!' : 'Failed';
		button.classList.add(success ? 'saved' : 'error');
		setTimeout(() => {
			button.textContent = original;
			button.classList.remove('saved', 'error');
		}, 2000);
	}

	document.body.addEventListener('click', (e) => {
		const button = e.target.closest('.save-file-btn');
		if (!button) return;

		const section = button.closest('.output-section') || outputSection;
		if (!section) return;

		const articles = section.querySelectorAll('.result-block');
		let normalizedCodeEl = null;

		for (const article of articles) {
			const heading = article.querySelector('h4');
			if (heading && heading.textContent.includes('Normalized SVG')) {
				normalizedCodeEl = article.querySelector('code');
				break;
			}
		}

		if (!normalizedCodeEl) return;

		const text = normalizedCodeEl.innerText.trim();
		if (!text) return;

		saveSvgFile(text, button);
	});

	/* ==========================
	   AJAX CONVERSION HANDLER
	   ========================== */
	if (form && outputSection && svgInput) {
		form.addEventListener('submit', async (e) => {
			e.preventDefault();

			if (!svgInput.value.trim()) {
				outputSection.classList.add('has-error');
				outputSection.innerHTML = `
					<div class="error-state" role="alert">
						<span class="error-icon" aria-hidden="true"></span>
						<h3>Nothing to convert</h3>
						<p>Please paste your SVG markup first.</p>
					</div>
				`;
				return;
			}

			const formData = new FormData(form);
			outputSection.classList.remove('has-error');
			outputSection.innerHTML = '<div class="empty-state"><p>Processing...</p></div>';

			try {
				const res = await fetch('ajax.php', {
					method: 'POST',
					body: formData
				});
				const html = await res.text();

				if (html.includes('error-state')) {
					outputSection.classList.add('has-error');
				} else {
					outputSection.classList.remove('has-error');
				}

				outputSection.innerHTML = html;
			} catch {
				outputSection.classList.add('has-error');
				outputSection.innerHTML = `
					<div class="error-state" role="alert">
						<span class="error-icon" aria-hidden="true"></span>
						<h3>Something went wrong</h3>
						<p>Network error. Please try again.</p>
					</div>
				`;
			}
		});

		svgInput.addEventListener('keydown', (e) => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
				form.requestSubmit();
			}
		});
	}
});
