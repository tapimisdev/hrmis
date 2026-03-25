<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Patch Notes | {{ config('app.name', 'DOST') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --page-bg:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.12), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 25%),
                linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
            --card-bg: rgba(255, 255, 255, 0.88);
            --card-border: rgba(148, 163, 184, 0.24);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --chip-bg: #f1f5f9;
            --shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
        }

        [data-bs-theme="dark"] {
            --page-bg:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(16, 185, 129, 0.18), transparent 25%),
                linear-gradient(180deg, #0b1120 0%, #111827 100%);
            --card-bg: rgba(17, 24, 39, 0.86);
            --card-border: rgba(148, 163, 184, 0.18);
            --text-main: #e5e7eb;
            --text-muted: #94a3b8;
            --chip-bg: rgba(148, 163, 184, 0.14);
            --shadow: 0 18px 45px rgba(0, 0, 0, 0.35);
        }

        html, body {
            min-height: 100%;
        }

        body {
            margin: 0;
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: var(--page-bg);
            color: var(--text-main);
        }

        .shell {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 18px 48px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 22px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(145deg, #2563eb, #10b981);
            color: white;
            box-shadow: var(--shadow);
        }

        .hero {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            padding: 28px;
            box-shadow: var(--shadow);
            margin-bottom: 22px;
        }

        .hero h1 {
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1.05;
            letter-spacing: -0.04em;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .hero-copy {
            max-width: 760px;
            color: var(--text-muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid var(--card-border);
            background: rgba(255,255,255,0.22);
            color: var(--text-main);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .stat-card {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border-radius: 22px;
            padding: 18px;
            box-shadow: var(--shadow);
        }

        .stat-label {
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
        }

        .notes-list {
            display: grid;
            gap: 16px;
        }

        .note-card {
            position: relative;
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            padding: 22px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .note-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 6px;
            background: var(--accent, #2563eb);
        }

        .note-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 16px;
        }

        .note-title {
            font-size: 1.25rem;
            line-height: 1.3;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .note-summary {
            margin: 0;
            color: var(--text-muted);
            line-height: 1.7;
        }

        .note-title--upper {
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            padding: 0.42rem 0.75rem;
            font-size: 0.83rem;
            font-weight: 600;
            border: 1px solid var(--card-border);
            background: var(--chip-bg);
            color: var(--text-main);
        }

        .badge-status {
            background: rgba(16, 185, 129, 0.12);
            color: #10b981;
            border-color: rgba(16, 185, 129, 0.22);
        }

        .note-foot {
            margin-top: 16px;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 12px;
            color: var(--text-muted);
            font-size: 0.92rem;
        }

        .note-list-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .filter-panel {
            border: 1px solid var(--card-border);
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border-radius: 24px;
            padding: 18px;
            box-shadow: var(--shadow);
            margin-bottom: 22px;
        }

        .filter-label {
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .filter-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
        }

        .form-control,
        .form-select {
            border-color: var(--card-border);
            background-color: var(--bs-body-bg);
            color: var(--text-main);
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        @media (max-width: 991.98px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .shell {
                padding: 18px 12px 34px;
            }

            .hero,
            .stat-card,
            .note-card {
                border-radius: 20px;
                padding: 18px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .note-title {
                font-size: 1.08rem;
            }

            .filter-actions {
                justify-content: stretch;
            }

            .filter-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="topbar">
            <a class="brand" href="{{ url('/') }}">
                <div class="brand-mark">
                    <i class="fa-solid fa-code-branch"></i>
                </div>
                <div>
                    <div class="fw-bold">Patch Notes</div>
                    <div style="color: var(--text-muted); font-size: 0.92rem;">Standalone release summary</div>
                </div>
            </a>
        </div>

        <section class="hero">
            <div class="hero-meta">
                <span class="pill"><i class="fa-solid fa-clipboard-list"></i> Release log</span>
                <span class="pill"><i class="fa-regular fa-calendar"></i> Updated {{ $releasedAt }}</span>
                <span class="pill"><i class="fa-solid fa-shield-heart"></i> Production-ready changes</span>
            </div>
            <h1 class="mt-3">Recent platform patches, sorted from oldest to latest.</h1>
            <p class="hero-copy mb-0">
                This page is intentionally standalone. It reads from <code>public/patches/patches.csv</code>,
                presents the entries in chronological order, and keeps the language clean for a release note format.
            </p>
        </section>

        <section class="filter-panel">
            <form method="GET" action="{{ url('/patch-notes') }}" class="row g-3 align-items-end">
                <div class="col-lg-4">
                    <div class="filter-label">Search</div>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] ?? '' }}"
                        class="form-control"
                        placeholder="Search issue key, summary, assignee..."
                    >
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Type</div>
                    <select name="type" class="form-select">
                        <option value="">All types</option>
                        @foreach($filterOptions['types'] as $option)
                            <option value="{{ $option }}" @selected(($filters['type'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Status</div>
                    <select name="status" class="form-select">
                        <option value="">All status</option>
                        @foreach($filterOptions['statuses'] as $option)
                            <option value="{{ $option }}" @selected(($filters['status'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="filter-label">Priority</div>
                    <select name="priority" class="form-select">
                        <option value="">All priorities</option>
                        @foreach($filterOptions['priorities'] as $option)
                            <option value="{{ $option }}" @selected(($filters['priority'] ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <div class="filter-actions">
                        <a href="{{ url('/patch-notes') }}" class="btn btn-outline-secondary w-100">Reset</a>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Notes</div>
                <div class="stat-value">{{ $stats['notes'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Done</div>
                <div class="stat-value">{{ $stats['done'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Testing</div>
                <div class="stat-value">{{ $stats['testing'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Progress</div>
                <div class="stat-value">{{ $stats['in_progress'] }}</div>
            </div>
        </section>

        <section class="notes-list">
            @foreach($patchNotes as $note)
                <article class="note-card" style="--accent: {{ match($note['tone']) {
                    'emerald' => '#10b981',
                    'amber' => '#f59e0b',
                    'sky' => '#0ea5e9',
                    'rose' => '#f43f5e',
                    default => '#6366f1',
                } }}">
                    <div class="note-header">
                        <div>
                            <div class="note-list-meta mb-2">
                                <span class="tag">{{ $note['ticket'] }}</span>
                                <span class="tag">{{ $note['type'] }}</span>
                                <span class="tag badge-status">{{ $note['status'] }}</span>
                                <span class="tag">{{ $note['priority'] }}</span>
                            </div>
                            <div class="note-title note-title--upper">{{ $note['title'] }}</div>
                            <p class="note-summary">{{ $note['summary'] }}</p>
                        </div>
                    </div>

                    <div class="tag-row">
                        <span class="tag"><i class="fa-regular fa-user"></i> {{ $note['assignee'] }}</span>
                        <span class="tag"><i class="fa-regular fa-address-card"></i> {{ $note['reporter'] }}</span>
                    </div>

                    <div class="note-foot">
                        <div>Created {{ $note['created_label'] }}</div>
                        <div>Updated {{ $note['updated_label'] }}</div>
                    </div>
                </article>
            @endforeach
        </section>

        @if(empty($patchNotes))
            <div class="note-card mt-3">
                <div class="text-center py-4">
                    <div class="fw-semibold mb-2">No patch notes matched your filters.</div>
                    <div style="color: var(--text-muted);">Try a different keyword or clear the filters.</div>
                </div>
            </div>
        @endif
    </div>
</body>
</html>
