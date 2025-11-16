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

	// Apply saved or system theme
	if (saved) {
		root.classList.toggle('dark', saved === 'dark');
	} else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
		root.classList.add('dark');
	}

	// Handle manual toggle
	if (toggle) {
		toggle.addEventListener('click', () => {
			const isDark = root.classList.toggle('dark');
			localStorage.setItem('theme', isDark ? 'dark' : 'light');
		});
	}

/* ==========================
   SVG FILE UPLOAD → TEXTAREA
   ========================== */
const uploadLink = document.querySelector('.upload-link');
const svgFileInput = document.getElementById('svgFile');
const svgTextarea = document.getElementById('svg');

if (uploadLink && svgFileInput && svgTextarea) {

	uploadLink.addEventListener('click', () => {
		svgFileInput.click();
	});

	svgFileInput.addEventListener('change', () => {
		const file = svgFileInput.files[0];
		if (!file) return;

		if (!file.type.includes('svg')) {

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
			// Only update textarea — do NOT touch output section here
			svgTextarea.value = e.target.result;
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

		const codeEl = button.closest('article')?.querySelector('code');
		if (!codeEl) return;

		const text = codeEl.innerText.trim();
		copyToClipboard(text, button);
	});

	/* ==========================
	   SAVE FILE BUTTON HANDLING
	   ========================== */
	function saveSvgFile(text, button) {
		try {
			// Create a Blob with SVG MIME type
			const blob = new Blob([text], { type: 'image/svg+xml' });

			// Create object URL
			const url = URL.createObjectURL(blob);

			// Create temporary anchor element
			const a = document.createElement('a');
			a.href = url;
			a.download = 'svgready-output.svg';
			document.body.appendChild(a);

			// Trigger download
			a.click();

			// Cleanup
			document.body.removeChild(a);
			URL.revokeObjectURL(url);

			// Visual feedback
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

	// Save file button click handler (delegated)
	document.body.addEventListener('click', (e) => {
		const button = e.target.closest('.save-file-btn');
		if (!button) return;

		// Find the "Normalized SVG" section's code element
		const outputSection = button.closest('.output-section') || document.querySelector('.output-section');
		if (!outputSection) return;

		// Find the article with "Normalized SVG" heading
		const articles = outputSection.querySelectorAll('.result-block');
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
	const form = document.querySelector('.input-section form');
	const outputSection = document.querySelector('.output-section');
	const svgInput = form ? form.querySelector('#svg') : null;

	if (form && outputSection && svgInput) {
		form.addEventListener('submit', async (e) => {
			e.preventDefault();

			// Empty input error
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

			// Processing state
			const formData = new FormData(form);
			outputSection.classList.remove('has-error');
			outputSection.innerHTML = '<div class="empty-state"><p>Processing...</p></div>';

			try {
				const res = await fetch('ajax.php', {
					method: 'POST',
					body: formData
				});
				const html = await res.text();

				// Ensure consistent error styling
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

		// Shortcut: Ctrl + Enter or Cmd + Enter triggers conversion
		svgInput.addEventListener('keydown', (e) => {
			if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
				form.requestSubmit();
			}
		});
	}
});
