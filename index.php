<?php
require_once __DIR__ . '/inc/functions.php';

$input_svg        = $_POST['svg'] ?? '';
$strip_root_wh    = isset($_POST['strip_wh']);
$strip_root_class = isset($_POST['strip_class']);
$show_base64      = isset($_POST['show_base64']);

// Security: Prevent large SVG submissions from overloading the server.
if ($input_svg !== '' && strlen($input_svg) > 250000) { // 250 KB limit
    die('SVG too large (250 KB limit).');
}

$normalized = $data_uri_css = $data_uri_b64 = $bg_snippet = $mask_snippet = '';

if ($input_svg !== '') {
    $normalized   = normalize_svg($input_svg, $strip_root_wh, $strip_root_class);
    $data_uri_css = svg_to_data_uri($normalized);
    $bg_snippet   = 'background-image: url("' . $data_uri_css . '");';
    $mask_snippet = 'mask-image: url("' . $data_uri_css . '");' . "\n" .
                    '-webkit-mask-image: url("' . $data_uri_css . '");';
    if ($show_base64) {
        $data_uri_b64 = svg_to_base64($normalized);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>SVG Ready – Convert SVG to CSS Data URI | Webtions</title>
    <meta name="description" content="Convert SVG to CSS Data URI instantly. Optimize SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS. Free SVG converter tool.">
    <meta name="keywords" content="SVG converter, CSS Data URI, SVG to base64, SVG optimization, SVG to CSS, Data URI generator">
    <meta property="og:title" content="SVG Ready - Convert SVG to CSS Data URI">
    <meta property="og:description" content="Instantly convert SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS.">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Google tag(gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-MTZ6348352"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
    function gtag()
    {
        dataLayer.push(arguments);
    }
        gtag('js', new Date());
        gtag('config', 'G-MTZ6348352');
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "SVG Ready",
        "description": "Convert SVG to CSS Data URI instantly",
        "applicationCategory": "DeveloperApplication"
    }
    </script>
</head>

<body>
<header class="site-header">
    <h1 class="site-title">
        <a href="/" aria-label="SVG Ready Home">SVG <em class="word">Ready</em></a>
    </h1>

    <nav class="site-nav" aria-label="Main navigation">
        <ul>
            <li>
                <a href="https://github.com/webtions/svgready" target="_blank" rel="noopener noreferrer" aria-label="Contribute on GitHub">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github-icon lucide-github"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
                    <span class="sr-only">Contribute</span>
                </a>
            </li>
            <li>
                <a href="https://github.com/webtions/svgready/issues" target="_blank" rel="noopener noreferrer" aria-label="Report an issue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3l-8.47-14.14a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12" y2="17"></line></svg>
                    <span class="sr-only">Report issue</span>
                </a>
            </li>
            <li>
                <button id="theme-toggle" aria-label="Toggle dark mode">
                    <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                    <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
            </li>
        </ul>
    </nav>
</header>

<section class="intro">
    <h2>Convert SVG to CSS Data URI</h2>
    <p>
        Instantly convert SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS.
    </p>
</section>

    <main class="wrap">
        <div class="main-layout">
            <?php require __DIR__ . '/inc/form.php'; ?>
            <?php require __DIR__ . '/inc/output.php'; ?>

        </div>
    </main>

    <hr class="sep-dots" aria-hidden="true">

    <footer class="site-footer">
        <?php require __DIR__ . '/inc/ads.php'; ?>
        <hr class="sep-dots" aria-hidden="true">
        <p><svg xmlns="http://www.w3.org/2000/svg" width="120"  viewBox="0 0 300 152" fill="none">



  <path d="M154.962 82.2801C157.693 79.5452 157.693 75.1111 154.962 72.3762C152.231 69.6413 147.803 69.6413 145.071 72.3762C142.34 75.1111 142.34 79.5452 145.071 82.2801C147.803 85.015 152.231 85.015 154.962 82.2801Z" fill="var(--secondary)"></path>
  <path d="M154.962 14.8558C157.694 12.1209 157.694 7.68674 154.962 4.95185C152.231 2.21696 147.803 2.21695 145.072 4.95185C142.34 7.68674 142.34 12.1209 145.072 14.8558C147.803 17.5907 152.231 17.5907 154.962 14.8558Z" fill="var(--secondary)"></path>
  <path fill-rule="evenodd" clip-rule="evenodd" d="M183.677 36.6559C181.919 36.6562 180.225 37.3195 178.933 38.5137C177.642 39.7079 176.846 41.3454 176.706 43.1003C176.692 43.2845 176.685 43.4736 176.685 43.659C176.685 43.8444 176.685 44.0074 176.703 44.1791C176.565 46.5976 175.542 48.8804 173.829 50.5914C172.117 52.3024 169.835 53.3216 167.419 53.4544C167.241 53.442 167.063 53.4345 166.881 53.4345H166.824C166.643 53.4345 166.464 53.442 166.286 53.4544C163.87 53.3216 161.588 52.3024 159.876 50.5914C158.163 48.8804 157.14 46.5976 157.002 44.1791C157.014 44.0074 157.021 43.8345 157.021 43.659C157.021 43.6329 157.021 43.6067 157.021 43.5806C157.021 43.5545 157.021 43.5396 157.021 43.5184C157.021 43.394 157.021 43.2695 157.009 43.1451C157.125 40.6187 158.207 38.2338 160.031 36.4844C161.855 34.7349 164.281 33.7551 166.807 33.7479V33.7416C168.166 33.7416 169.496 33.3449 170.634 32.6C171.772 31.855 172.668 30.7942 173.213 29.5472C173.759 28.3002 173.929 26.9211 173.704 25.5786C173.479 24.2361 172.868 22.9884 171.945 21.9882C171.023 20.988 169.83 20.2785 168.512 19.9466C167.193 19.6148 165.807 19.6749 164.522 20.1196C163.237 20.5643 162.109 21.3744 161.277 22.4507C160.445 23.527 159.944 24.8228 159.835 26.1798C159.821 26.3639 159.814 26.5531 159.814 26.7385C159.814 26.9239 159.814 27.0869 159.833 27.2586C159.695 29.676 158.673 31.9579 156.962 33.6688C155.251 35.3797 152.97 36.3996 150.556 36.5339C150.381 36.5215 150.206 36.514 150.028 36.514H150.001C149.823 36.514 149.648 36.5215 149.473 36.5339C147.058 36.3993 144.778 35.3793 143.067 33.6685C141.356 31.9577 140.334 29.6759 140.196 27.2586C140.208 27.0869 140.215 26.9139 140.215 26.7385C140.215 26.563 140.207 26.3652 140.193 26.1798C140.085 24.8228 139.583 23.527 138.751 22.4508C137.918 21.3746 136.79 20.5648 135.505 20.1203C134.22 19.6759 132.834 19.6161 131.515 19.9483C130.197 20.2805 129.004 20.9903 128.082 21.9908C127.16 22.9913 126.549 24.2392 126.325 25.5818C126.1 26.9244 126.271 28.3035 126.816 29.5503C127.362 30.7972 128.259 31.8578 129.397 32.6024C130.535 33.3471 131.865 33.7434 133.225 33.7429V33.7491C135.686 33.7566 138.056 34.6879 139.865 36.3592C141.675 38.0305 142.793 40.3203 142.999 42.7768C142.986 42.8838 142.975 42.992 142.966 43.1003C142.952 43.2845 142.945 43.4736 142.945 43.659C142.945 43.8444 142.945 44.0074 142.963 44.1791C142.825 46.5972 141.803 48.8797 140.091 50.5906C138.378 52.3015 136.097 53.321 133.682 53.4544C133.505 53.442 133.328 53.4345 133.148 53.4345H133.139C132.959 53.4345 132.781 53.442 132.604 53.4544C130.189 53.321 127.908 52.3015 126.196 50.5906C124.484 48.8797 123.461 46.5972 123.323 44.1791C123.335 44.0074 123.342 43.8345 123.342 43.659C123.342 43.4836 123.334 43.2857 123.32 43.1003C123.212 41.7434 122.711 40.4475 121.879 39.3712C121.046 38.2949 119.919 37.4848 118.634 37.0401C117.349 36.5954 115.962 36.5353 114.644 36.8672C113.326 37.199 112.132 37.9085 111.21 38.9087C110.288 39.9089 109.677 41.1566 109.452 42.4991C109.227 43.8416 109.397 45.2207 109.942 46.4677C110.488 47.7147 111.384 48.7756 112.522 49.5205C113.66 50.2654 114.99 50.6622 116.349 50.6621V50.6684C118.897 50.6753 121.342 51.6718 123.171 53.448C125 55.2242 126.069 57.6416 126.154 60.1913C126.154 60.2734 126.148 60.3555 126.148 60.4402C126.147 61.3598 126.328 62.2704 126.679 63.1201C127.031 63.9697 127.546 64.7417 128.195 65.3921C128.844 66.0424 129.615 66.5582 130.464 66.9102C131.312 67.2621 132.222 67.4433 133.14 67.4433H133.149C134.067 67.4433 134.977 67.2621 135.825 66.9102C136.673 66.5582 137.444 66.0424 138.094 65.3921C138.743 64.7417 139.258 63.9697 139.609 63.1201C139.961 62.2704 140.141 61.3598 140.141 60.4402C140.141 60.3568 140.141 60.2747 140.135 60.1913C140.22 57.642 141.289 55.2251 143.118 53.4492C144.946 51.6733 147.391 50.6769 149.939 50.6696V50.6634H150.024V50.6696C152.583 50.6768 155.039 51.6822 156.87 53.4724C158.701 55.2627 159.763 57.6969 159.831 60.2585V60.2684C159.831 60.3244 159.831 60.3817 159.831 60.4389C159.831 60.4962 159.831 60.5907 159.831 60.6666C159.89 62.4836 160.653 64.2064 161.957 65.4707C163.262 66.7351 165.006 67.4421 166.821 67.4421H166.879C168.694 67.4421 170.438 66.7351 171.743 65.4707C173.047 64.2064 173.81 62.4836 173.869 60.6666C173.869 60.5911 173.869 60.5152 173.869 60.4389C173.869 60.3817 173.869 60.3244 173.869 60.2684V60.2585C173.937 57.6964 174.999 55.2617 176.83 53.4712C178.661 51.6807 181.118 50.6752 183.677 50.6684V50.6621C185.532 50.6621 187.311 49.9243 188.622 48.611C189.934 47.2976 190.671 45.5164 190.671 43.659C190.671 41.8017 189.934 40.0204 188.622 38.707C187.311 37.3937 185.532 36.6559 183.677 36.6559Z" fill="var(--accent)"></path>
</svg></a></p>
        <p>Built and maintained by <a href="https://webtions.com" target="_blank" rel="noopener noreferrer">Webtions</a>.</p>
        <p>&copy; <?php echo date('Y'); ?> Webtions. All rights reserved.</p>
    </footer>

    <script src="assets/scripts.js" defer></script>
</body>
</html>
