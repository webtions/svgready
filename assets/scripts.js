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

	// Apply saved or system theme
	if (saved) {
		document.body.classList.toggle('dark', saved === 'dark');
	} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		document.body.classList.add('dark');
	}

	// Handle manual toggle
	if (toggle) {
		toggle.addEventListener('click', () => {
			const isDark = document.body.classList.toggle('dark');
			localStorage.setItem('theme', isDark ? 'dark' : 'light');
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

	// Copy button click handler (delegated)
	document.body.addEventListener('click', (e) => {
		const button = e.target.closest('.copy-btn');
		if (!button) return;
		const text = button.dataset.copy || button.closest('article')?.querySelector('code')?.textContent;
		if (text) copyToClipboard(text.trim(), button);
	});

	/* ==========================
	   AJAX CONVERSION HANDLER
	   ========================== */
	const form = document.querySelector('.input-section form');
	const outputSection = document.querySelector('.output-section');
	const svgInput = form ? form.querySelector('#svg') : null;

	if (form && outputSection && svgInput) {
		// Submit handler
		form.addEventListener('submit', async (e) => {
			e.preventDefault();

			// Stop if no SVG content
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
					body: formData,
				});

				const data = await res.json();

				if (data.error) {
					outputSection.classList.add('has-error');
					outputSection.innerHTML = `
						<div class="error-state" role="alert">
							<span class="error-icon" aria-hidden="true"></span>
							<h3>Something went wrong</h3>
							<p>${data.error}</p>
						</div>
					`;
					return;
				}

				const r = data.results;
				outputSection.classList.remove('has-error');

				let html = `
					<article class="result-block">
						<h4>SVG Preview</h4>
						<div class="preview-container" role="img">${r.preview}</div>
					</article>
					<article class="result-block">
						<h4>Normalized SVG
							<button class="copy-btn" data-copy="${encodeURIComponent(r.normalized)}">Copy</button>
						</h4>
						<pre><code>${r.normalized.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code></pre>
					</article>
					<article class="result-block">
						<h4>Percent-encoded Data URI
							<button class="copy-btn" data-copy="${encodeURIComponent(r.data_uri_css)}">Copy</button>
						</h4>
						<pre><code>${r.data_uri_css}</code></pre>
					</article>
					<article class="result-block">
						<h4>Background Image CSS
							<button class="copy-btn" data-copy="${encodeURIComponent(r.bg_snippet)}">Copy</button>
						</h4>
						<pre><code>${r.bg_snippet}</code></pre>
					</article>
					<article class="result-block">
						<h4>Mask Image CSS
							<button class="copy-btn" data-copy="${encodeURIComponent(r.mask_snippet)}">Copy</button>
						</h4>
						<pre><code>${r.mask_snippet}</code></pre>
					</article>
				`;

				if (r.show_base64 && r.data_uri_b64) {
					html += `
						<article class="result-block">
							<h4>Base64 Data URI
								<button class="copy-btn" data-copy="${encodeURIComponent(r.data_uri_b64)}">Copy</button>
							</h4>
							<pre><code>${r.data_uri_b64}</code></pre>
						</article>
					`;
				}

				outputSection.innerHTML = html;
			} catch (err) {
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

		// Shortcut: Ctrl + Enter or Cmd + Enter triggers conversion
		svgInput.addEventListener('keydown', (e) => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
				form.requestSubmit();
			}
		});
	}
});
