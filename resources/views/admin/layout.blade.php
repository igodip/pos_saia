<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Admin' }} | POS Saia</title>
    <style>
        :root {
            --bg: #f4efe7;
            --panel: rgba(255, 251, 247, 0.9);
            --panel-strong: #fffaf4;
            --line: rgba(91, 63, 34, 0.12);
            --text: #26190f;
            --muted: #6f5a46;
            --brand: #a34a1f;
            --brand-dark: #6e2e10;
            --accent: #d7a24d;
            --success: #2c7a55;
            --danger: #a13333;
            --shadow: 0 18px 40px rgba(72, 43, 20, 0.08);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(215,162,77,0.28), transparent 28%),
                radial-gradient(circle at top right, rgba(163,74,31,0.18), transparent 24%),
                linear-gradient(180deg, #f7f0e6 0%, #f2ebe2 100%);
        }
        a { color: inherit; text-decoration: none; }
        .shell {
            width: min(1380px, calc(100% - 32px));
            margin: 24px auto 48px;
        }
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 18px 22px;
            border: 1px solid var(--line);
            border-radius: 24px;
            background: rgba(38, 25, 15, 0.92);
            color: #f7eee5;
            box-shadow: var(--shadow);
        }
        .brand h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .brand p {
            margin: 4px 0 0;
            color: rgba(247, 238, 229, 0.7);
            font-size: 0.92rem;
        }
        .nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav a {
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.08);
        }
        .nav a.active {
            background: linear-gradient(135deg, #d7a24d, #b66422);
            color: #22150e;
            font-weight: 700;
        }
        .logout-btn, .btn {
            border: 0;
            cursor: pointer;
            border-radius: 14px;
            padding: 10px 14px;
            font: inherit;
        }
        .logout-btn {
            background: rgba(255,255,255,0.12);
            color: #f8efe8;
        }
        .content {
            margin-top: 18px;
            display: grid;
            gap: 18px;
        }
        .grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(12, 1fr);
        }
        .panel {
            grid-column: span 12;
            background: var(--panel);
            backdrop-filter: blur(12px);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 20px;
            box-shadow: var(--shadow);
        }
        .panel h2, .panel h3 {
            margin-top: 0;
        }
        .panel-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .kpi-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        }
        .kpi {
            padding: 16px;
            border-radius: 18px;
            background: linear-gradient(160deg, rgba(255,255,255,0.82), rgba(248,240,232,0.92));
            border: 1px solid var(--line);
        }
        .kpi .label {
            color: var(--muted);
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .kpi .value {
            margin-top: 10px;
            font-size: 1.9rem;
            font-weight: 700;
        }
        .cols-6 { grid-column: span 6; }
        .cols-4 { grid-column: span 4; }
        .cols-8 { grid-column: span 8; }
        .cols-12 { grid-column: span 12; }
        .muted { color: var(--muted); }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(215,162,77,0.18);
            color: var(--brand-dark);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
        .badge.success { background: rgba(44,122,85,0.16); color: var(--success); }
        .badge.danger { background: rgba(161,51,51,0.14); color: var(--danger); }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.94rem;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.07em;
        }
        .table-wrap { overflow-x: auto; }
        form.inline { display: inline; }
        .form-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
        label {
            display: grid;
            gap: 6px;
            font-size: 0.9rem;
            color: var(--muted);
        }
        input, select, textarea {
            width: 100%;
            border: 1px solid rgba(95, 67, 39, 0.18);
            background: var(--panel-strong);
            border-radius: 14px;
            padding: 11px 12px;
            font: inherit;
            color: var(--text);
        }
        textarea { min-height: 110px; resize: vertical; }
        .btn {
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: #fff6ef;
            box-shadow: 0 12px 22px rgba(163,74,31,0.16);
        }
        .btn.secondary {
            background: #efe3d3;
            color: var(--text);
            box-shadow: none;
        }
        .btn.danger {
            background: linear-gradient(135deg, #b54a4a, #7d2424);
        }
        .btn.success {
            background: linear-gradient(135deg, #367d59, #22533b);
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .flash, .errors {
            border-radius: 20px;
            padding: 16px 18px;
            border: 1px solid var(--line);
        }
        .flash {
            background: rgba(44,122,85,0.08);
            color: var(--success);
        }
        .errors {
            background: rgba(161,51,51,0.08);
            color: var(--danger);
        }
        .login-wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .login-card {
            width: min(480px, 100%);
            border-radius: 28px;
            border: 1px solid var(--line);
            background: rgba(255, 250, 244, 0.94);
            box-shadow: var(--shadow);
            padding: 28px;
        }
        .split {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        @media (max-width: 980px) {
            .cols-6, .cols-4, .cols-8 { grid-column: span 12; }
            .topbar { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
@yield('body')
</body>
</html>
