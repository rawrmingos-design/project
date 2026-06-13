<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title inertia>API Docs - Reseller Hub</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Geist:wght@400;500;600;700;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <style>
        body {
            background-color: #020617;
            color: #dce1fb;
            font-family: 'Inter', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-panel {
            background: rgba(21, 27, 45, 0.4);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            transition: backdrop-filter 0.3s ease;
        }
        .glass-panel:hover {
            backdrop-filter: blur(24px);
        }
        .neon-glow {
            box-shadow: 0px 0px 20px rgba(192, 193, 255, 0.15);
        }
        .code-block {
            background: rgba(7, 13, 31, 0.6);
            font-family: 'JetBrains Mono', monospace;
        }
        .divider-gradient {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        }
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(192, 193, 255, 0.1);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(192, 193, 255, 0.2);
        }
    </style>
    
    <script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                  "on-primary": "#1000a9",
                  "tertiary": "#4edea3",
                  "tertiary-container": "#00885d",
                  "on-secondary-fixed": "#23005c",
                  "inverse-surface": "#dce1fb",
                  "primary-container": "#8083ff",
                  "on-surface-variant": "#c7c4d7",
                  "on-error": "#690005",
                  "primary-fixed": "#e1e0ff",
                  "on-error-container": "#ffdad6",
                  "secondary-container": "#571bc1",
                  "background": "#0c1324",
                  "on-primary-fixed": "#07006c",
                  "error": "#ffb4ab",
                  "surface-container-highest": "#2e3447",
                  "on-tertiary-fixed-variant": "#005236",
                  "surface-bright": "#33394c",
                  "on-secondary-fixed-variant": "#5516be",
                  "on-primary-container": "#0d0096",
                  "on-tertiary": "#003824",
                  "surface-tint": "#c0c1ff",
                  "secondary": "#d0bcff",
                  "surface-container-lowest": "#070d1f",
                  "on-tertiary-fixed": "#002113",
                  "on-tertiary-container": "#000703",
                  "error-container": "#93000a",
                  "surface-container-low": "#151b2d",
                  "outline-variant": "#464554",
                  "tertiary-fixed": "#6ffbbe",
                  "tertiary-fixed-dim": "#4edea3",
                  "on-secondary-container": "#c4abff",
                  "on-primary-fixed-variant": "#2f2ebe",
                  "surface-variant": "#2e3447",
                  "on-secondary": "#3c0091",
                  "on-background": "#dce1fb",
                  "primary": "#c0c1ff",
                  "on-surface": "#dce1fb",
                  "inverse-primary": "#494bd6",
                  "surface-container-high": "#23293c",
                  "outline": "#908fa0",
                  "secondary-fixed-dim": "#d0bcff",
                  "surface-container": "#191f31",
                  "surface-dim": "#0c1324",
                  "secondary-fixed": "#e9ddff",
                  "inverse-on-surface": "#2a3043",
                  "surface": "#0c1324",
                  "primary-fixed-dim": "#c0c1ff"
              },
              borderRadius: {
                  DEFAULT: "0.25rem",
                  lg: "0.5rem",
                  xl: "0.75rem",
                  full: "9999px"
              },
              spacing: {
                  "section-gap": "48px",
                  "container-margin": "32px",
                  "gutter": "24px",
                  "unit": "4px",
                  "component-padding-y": "12px",
                  "component-padding-x": "16px"
              },
              fontFamily: {
                  "body-md": ["Inter"],
                  "headline-lg": ["Geist"],
                  "headline-md": ["Geist"],
                  "headline-lg-mobile": ["Geist"],
                  "body-lg": ["Inter"],
                  "label-sm": ["Geist"],
                  "label-md": ["Geist"],
                  "display-lg": ["Geist"]
              },
              fontSize: {
                  "body-md": ["16px", {lineHeight: "24px", fontWeight: "400"}],
                  "headline-lg": ["32px", {lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "600"}],
                  "headline-md": ["24px", {lineHeight: "32px", fontWeight: "500"}],
                  "headline-lg-mobile": ["24px", {lineHeight: "32px", fontWeight: "600"}],
                  "body-lg": ["18px", {lineHeight: "28px", fontWeight: "400"}],
                  "label-sm": ["12px", {lineHeight: "16px", fontWeight: "500"}],
                  "label-md": ["14px", {lineHeight: "20px", letterSpacing: "0.05em", fontWeight: "600"}],
                  "display-lg": ["48px", {lineHeight: "56px", letterSpacing: "-0.04em", fontWeight: "700"}]
              }
            }
          }
        }
    </script>
    
    @viteReactRefresh
    @vite(['resources/js/public-app.jsx'])
    @inertiaHead
</head>
<body class="bg-surface-dim selection:bg-primary/30">
    @inertia
</body>
</html>
