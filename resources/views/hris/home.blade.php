<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>HRIS Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.10), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.10), transparent 26%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            --surface: rgba(255, 255, 255, 0.84);
            --surface-strong: rgba(255, 255, 255, 0.96);
            --border: rgba(148, 163, 184, 0.24);
            --text: #0f172a;
            --muted: #64748b;
            --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
        }

        [data-bs-theme="dark"] {
            --bg:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.14), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 26%),
                linear-gradient(180deg, #0b1020 0%, #111827 100%);
            --surface: rgba(17, 24, 39, 0.82);
            --surface-strong: rgba(17, 24, 39, 0.96);
            --border: rgba(148, 163, 184, 0.18);
            --text: #e5e7eb;
            --muted: #94a3b8;
            --shadow: 0 20px 45px rgba(0, 0, 0, 0.30);
        }

        html, body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--bg);
            color: var(--text);
            transition: background .25s ease, color .25s ease;
        }

        .page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .wrap {
            width: min(1120px, calc(100% - 2rem));
            margin: 0 auto;
            padding: 1.25rem 0 3rem;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
            color: inherit;
        }

        .brand-mark {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #2563eb, #10b981);
            color: #fff;
            box-shadow: var(--shadow);
        }

        .badge-soft {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .45rem .8rem;
            border: 1px solid var(--border);
            border-radius: 999px;
            background: var(--surface);
            color: var(--muted);
            font-size: .86rem;
            font-weight: 600;
            text-decoration: none;
        }

        .theme-toggle {
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text);
            border-radius: 999px;
            padding: .48rem .85rem;
            font-size: .9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            box-shadow: var(--shadow);
        }

        .theme-toggle:hover {
            color: var(--text);
        }

        .hero {
            display: grid;
            grid-template-columns: 1.2fr .8fr;
            gap: 1rem;
            align-items: stretch;
            margin-bottom: 1rem;
        }

        .panel {
            border: 1px solid var(--border);
            background: var(--surface);
            backdrop-filter: blur(16px);
            border-radius: 1.5rem;
            box-shadow: var(--shadow);
        }

        .hero-main {
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-main::after {
            content: "";
            position: absolute;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            right: -6rem;
            top: -7rem;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.10), transparent 68%);
            pointer-events: none;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .82rem;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 1rem;
        }

        .hero h1 {
            font-size: clamp(2.35rem, 5vw, 4.25rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            margin-bottom: 1rem;
            font-weight: 800;
            max-width: 13ch;
        }

        .hero p {
            color: var(--muted);
            line-height: 1.8;
            max-width: 62ch;
            margin-bottom: 1.5rem;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
        }

        .btn-pill {
            border-radius: 999px;
            padding: .8rem 1.1rem;
            font-weight: 600;
        }

        .hero-side {
            padding: 1.5rem;
            display: grid;
            gap: .9rem;
            align-content: center;
        }

        .stat {
            padding: 1rem 1.05rem;
            border-radius: 1.1rem;
            border: 1px solid var(--border);
            background: var(--surface-strong);
        }

        .stat .label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: var(--muted);
            margin-bottom: .3rem;
        }

        .stat .value {
            font-size: 1.25rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .status-card {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: 1rem 1.05rem;
            border-radius: 1.1rem;
            border: 1px solid var(--border);
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(16, 185, 129, 0.08));
        }

        .status-card__dot {
            width: .8rem;
            height: .8rem;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 6px rgba(16, 185, 129, 0.12);
            flex: 0 0 auto;
        }

        .status-card__title {
            font-weight: 700;
            margin-bottom: .15rem;
        }

        .status-card__text {
            color: var(--muted);
            font-size: .92rem;
            margin: 0;
        }

        .section-title {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 1rem;
            margin: 1.4rem 0 .9rem;
        }

        .section-title h2 {
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: .16em;
            margin: 0;
        }

        .section-title p {
            margin: 0;
            color: var(--muted);
            font-size: .92rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .feature {
            padding: 1.25rem;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            background: var(--surface);
            box-shadow: var(--shadow);
            min-height: 100%;
        }

        .feature-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: .9rem;
            display: grid;
            place-items: center;
            background: rgba(37, 99, 235, 0.10);
            color: #2563eb;
            margin-bottom: .9rem;
        }

        .feature h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: .45rem;
        }

        .feature p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .feature-list {
            margin: .9rem 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: .45rem;
        }

        .feature-list li {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            color: var(--muted);
            font-size: .94rem;
        }

        .feature-list i {
            color: #10b981;
            margin-top: .25rem;
            font-size: .8rem;
        }

        .footer-note {
            margin-top: 1.25rem;
            text-align: center;
            color: var(--muted);
            font-size: .92rem;
        }

        @media (max-width: 991.98px) {
            .hero,
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .wrap {
                width: min(100% - 1rem, 1120px);
                padding-top: .75rem;
            }

            .hero-main,
            .hero-side {
                padding: 1.15rem;
            }

            .topbar {
                margin-bottom: 1rem;
            }

            .hero h1 {
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="wrap">
            <div class="topbar">
                <a class="brand" href="{{ url('/') }}">
                    <div class="brand-mark">
                        <i class="fa-solid fa-building-user"></i>
                    </div>
                    <div>
                        <div class="fw-bold">HRIS Platform</div>
                        <div style="color: var(--muted); font-size: .92rem;">Minimal workspace for people operations</div>
                    </div>
                </a>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                        <i class="fa-regular fa-moon"></i>
                        <span class="theme-toggle__label">Light</span>
                    </button>
                    <a class="badge-soft" href="{{ route('patch-notes') }}">
                        <i class="fa-regular fa-note-sticky"></i>
                        Patch Notes
                    </a>
                    <a class="badge-soft" href="{{ url('/login') }}">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                        Sign in
                    </a>
                </div>
            </div>

            <section class="hero">
                <div class="panel hero-main">
                    <div class="eyebrow">
                        <i class="fa-solid fa-circle-nodes"></i>
                        Human Resource Information System
                    </div>
                    <h1>One calm workspace for the entire HR flow.</h1>
                    <p>
                        A modern, minimal HRIS built to keep daily work clear: attendance, payroll, employee records,
                        leave approvals, announcements, and realtime messaging in one place.
                    </p>
                    <div class="cta-row">
                        <a href="{{ url('/login') }}" class="btn btn-primary btn-pill">Open Portal</a>
                        <a href="#features" class="btn btn-outline-secondary btn-pill">View Features</a>
                    </div>
                </div>

                <div class="panel hero-side">
                    <div class="status-card">
                        <div class="status-card__dot"></div>
                        <div>
                            <div class="status-card__title">Built for clarity</div>
                            <p class="status-card__text">A lean interface that keeps attention on tasks, not clutter.</p>
                        </div>
                    </div>
                    <div class="stat">
                        <div class="label">Core modules</div>
                        <div class="value">HRIS + Payroll</div>
                    </div>
                    <div class="stat">
                        <div class="label">Realtime tools</div>
                        <div class="value">Messaging, notices, updates</div>
                    </div>
                    <div class="stat">
                        <div class="label">Workflow focus</div>
                        <div class="value">Simple, direct, organized</div>
                    </div>
                </div>
            </section>

            <div id="features" class="section-title">
                <div>
                    <h2>Features</h2>
                    <p>Built around the parts people use every day.</p>
                </div>
            </div>

            <section class="features-grid">
                @foreach($features as $feature)
                    <article class="feature">
                        <div class="feature-icon">
                            <i class="fa-solid fa-check"></i>
                        </div>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['description'] }}</p>
                        <ul class="feature-list">
                            <li><i class="fa-solid fa-circle-check"></i> Designed for quick daily use.</li>
                            <li><i class="fa-solid fa-circle-check"></i> Keeps related data in one clean flow.</li>
                        </ul>
                    </article>
                @endforeach
            </section>

            <div class="footer-note">
                Built for clarity, speed, and everyday HR work.
            </div>
        </div>
    </div>
    <script>
        (function () {
            const storageKey = 'hris-theme';
            const root = document.documentElement;
            const toggle = document.getElementById('themeToggle');
            const label = toggle?.querySelector('.theme-toggle__label');
            const icon = toggle?.querySelector('i');

            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const savedTheme = localStorage.getItem(storageKey);

            const applyTheme = (value) => {
                root.setAttribute('data-bs-theme', value);
                if (label) {
                    label.textContent = value === 'dark' ? 'Dark' : 'Light';
                }
                if (icon) {
                    icon.className = value === 'dark' ? 'fa-regular fa-sun' : 'fa-regular fa-moon';
                }
            };

            applyTheme(savedTheme || systemTheme);

            toggle?.addEventListener('click', () => {
                const current = root.getAttribute('data-bs-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem(storageKey, next);
                applyTheme(next);
            });
        })();
    </script>
</body>
</html>
