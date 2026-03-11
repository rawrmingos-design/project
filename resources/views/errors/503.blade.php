<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Maintenance Mode</title>
  <style>
    :root {
      --bg-1: #07111f;
      --bg-2: #0b1f38;
      --bg-3: #12345e;
      --primary: #6ee7ff;
      --secondary: #8b5cf6;
      --accent: #22d3ee;
      --text: #f8fafc;
      --muted: #b6c2d1;
      --glass: rgba(255, 255, 255, 0.08);
      --border: rgba(255, 255, 255, 0.16);
      --shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    * {
      box-sizing: border-box;
    }

    html, body {
      margin: 0;
      padding: 0;
      min-height: 100%;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background:
        radial-gradient(circle at 20% 20%, rgba(110, 231, 255, 0.12), transparent 30%),
        radial-gradient(circle at 80% 30%, rgba(139, 92, 246, 0.16), transparent 28%),
        radial-gradient(circle at 50% 80%, rgba(34, 211, 238, 0.12), transparent 30%),
        linear-gradient(135deg, var(--bg-1), var(--bg-2) 48%, var(--bg-3));
      color: var(--text);
      overflow: hidden;
    }

    body {
      display: grid;
      place-items: center;
      position: relative;
      isolation: isolate;
    }

    .grid {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
      background-size: 40px 40px;
      mask-image: radial-gradient(circle at center, black 45%, transparent 90%);
      animation: drift 18s linear infinite;
      opacity: 0.35;
      z-index: -3;
    }

    .orb {
      position: absolute;
      border-radius: 999px;
      filter: blur(12px);
      opacity: 0.45;
      z-index: -2;
      animation: float 9s ease-in-out infinite;
    }

    .orb.one {
      width: 280px;
      height: 280px;
      left: 10%;
      top: 12%;
      background: rgba(110, 231, 255, 0.22);
    }

    .orb.two {
      width: 360px;
      height: 360px;
      right: 8%;
      bottom: 8%;
      background: rgba(139, 92, 246, 0.2);
      animation-delay: -3s;
    }

    .orb.three {
      width: 200px;
      height: 200px;
      right: 22%;
      top: 14%;
      background: rgba(34, 211, 238, 0.16);
      animation-delay: -6s;
    }

    .container {
      width: min(920px, calc(100% - 32px));
      position: relative;
    }

    .card {
      position: relative;
      display: grid;
      grid-template-columns: 1.05fr 0.95fr;
      gap: 28px;
      padding: 36px;
      border-radius: 28px;
      background: linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
      border: 1px solid var(--border);
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
      overflow: hidden;
    }

    .card::before {
      content: "";
      position: absolute;
      inset: -1px;
      border-radius: inherit;
      padding: 1px;
      background: linear-gradient(135deg, rgba(110, 231, 255, 0.6), rgba(139, 92, 246, 0.35), rgba(255,255,255,0.08));
      -webkit-mask:
        linear-gradient(#fff 0 0) content-box,
        linear-gradient(#fff 0 0);
      -webkit-mask-composite: xor;
      mask-composite: exclude;
      pointer-events: none;
    }

    .left {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 18px;
      z-index: 1;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      width: fit-content;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.14);
      color: var(--primary);
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.02em;
    }

    .pulse-dot {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      background: var(--primary);
      box-shadow: 0 0 0 rgba(110, 231, 255, 0.6);
      animation: pulse 1.8s infinite;
    }

    h1 {
      margin: 0;
      font-size: clamp(2.2rem, 5vw, 4rem);
      line-height: 1.02;
      letter-spacing: -0.04em;
    }

    p {
      margin: 0;
      color: var(--muted);
      font-size: 1.03rem;
      line-height: 1.8;
      max-width: 560px;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 8px;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 14px 18px;
      border-radius: 16px;
      text-decoration: none;
      font-weight: 700;
      transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
    }

    .button.primary {
      color: #04111f;
      background: linear-gradient(135deg, var(--primary), #c4fbff);
      box-shadow: 0 12px 30px rgba(110, 231, 255, 0.24);
    }

    .button.secondary {
      color: var(--text);
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
    }

    .button:hover {
      transform: translateY(-2px);
    }

    .meta {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 6px;
    }

    .meta-item {
      padding: 12px 14px;
      border-radius: 16px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.1);
      min-width: 140px;
    }

    .meta-label {
      display: block;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #8aa0b9;
      margin-bottom: 4px;
    }

    .meta-value {
      font-size: 15px;
      font-weight: 700;
      color: var(--text);
    }

    .right {
      position: relative;
      min-height: 360px;
      display: grid;
      place-items: center;
    }

    .planet-wrap {
      position: relative;
      width: min(320px, 78vw);
      aspect-ratio: 1;
      display: grid;
      place-items: center;
    }

    .ring {
      position: absolute;
      inset: 9%;
      border-radius: 50%;
      border: 1px dashed rgba(255,255,255,0.2);
      animation: spin 22s linear infinite;
    }

    .ring::before,
    .ring::after {
      content: "";
      position: absolute;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary), var(--secondary));
      box-shadow: 0 0 20px rgba(110, 231, 255, 0.25);
    }

    .ring::before {
      width: 14px;
      height: 14px;
      top: 12%;
      left: 50%;
      transform: translateX(-50%);
    }

    .ring::after {
      width: 10px;
      height: 10px;
      bottom: 8%;
      right: 18%;
    }

    .planet {
      width: 58%;
      aspect-ratio: 1;
      border-radius: 50%;
      position: relative;
      background:
        radial-gradient(circle at 30% 30%, rgba(255,255,255,0.35), transparent 24%),
        radial-gradient(circle at 65% 60%, rgba(255,255,255,0.1), transparent 16%),
        linear-gradient(135deg, #56e9ff 0%, #3aa4ff 40%, #7c4dff 100%);
      box-shadow:
        inset -16px -20px 30px rgba(0, 0, 0, 0.25),
        0 0 60px rgba(82, 204, 255, 0.22);
      animation: bob 5.5s ease-in-out infinite;
    }

    .planet::before {
      content: "";
      position: absolute;
      width: 130%;
      height: 26%;
      top: 42%;
      left: 50%;
      transform: translateX(-50%) rotate(-12deg);
      border-radius: 999px;
      border: 10px solid rgba(255,255,255,0.2);
      box-shadow: inset 0 0 18px rgba(255,255,255,0.08);
    }

    .gear {
      position: absolute;
      inset: 0;
      display: grid;
      place-items: center;
      font-size: clamp(46px, 6vw, 72px);
      animation: slow-spin 12s linear infinite;
      text-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .spark {
      position: absolute;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: white;
      opacity: 0.8;
      animation: twinkle 2.2s infinite ease-in-out;
    }

    .spark.s1 { top: 12%; left: 16%; }
    .spark.s2 { top: 22%; right: 14%; animation-delay: .4s; }
    .spark.s3 { bottom: 18%; left: 10%; animation-delay: 1s; }
    .spark.s4 { bottom: 12%; right: 20%; animation-delay: 1.4s; }

    .footer-note {
      margin-top: 20px;
      text-align: center;
      color: rgba(255,255,255,0.62);
      font-size: 14px;
    }

    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(110, 231, 255, 0.55); }
      70% { box-shadow: 0 0 0 12px rgba(110, 231, 255, 0); }
      100% { box-shadow: 0 0 0 0 rgba(110, 231, 255, 0); }
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) translateX(0); }
      50% { transform: translateY(-16px) translateX(10px); }
    }

    @keyframes drift {
      0% { transform: translateY(0); }
      50% { transform: translateY(-12px); }
      100% { transform: translateY(0); }
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @keyframes slow-spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(-360deg); }
    }

    @keyframes bob {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }

    @keyframes twinkle {
      0%, 100% { opacity: 0.2; transform: scale(0.8); }
      50% { opacity: 1; transform: scale(1.35); }
    }

    @media (max-width: 860px) {
      .card {
        grid-template-columns: 1fr;
        padding: 24px;
      }

      .right {
        min-height: 280px;
      }

      .planet-wrap {
        width: min(280px, 72vw);
      }
    }

    @media (max-width: 560px) {
      body {
        overflow: auto;
      }

      .container {
        padding: 20px 0;
      }

      .meta-item {
        min-width: calc(50% - 6px);
      }

      .button {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="grid"></div>
  <div class="orb one"></div>
  <div class="orb two"></div>
  <div class="orb three"></div>

  <main class="container">
    <section class="card">
      <div class="left">
        <div class="badge">
          <span class="pulse-dot"></span>
          Maintenance Mode Active
        </div>

        <h1>Kami sedang melakukan<br />peningkatan sistem</h1>

        <p>
          Website sementara tidak tersedia karena sedang dalam proses maintenance agar performa,
          keamanan, dan stabilitas jadi lebih baik. Tenang, kami akan segera kembali online.
        </p>

        <div class="meta">
          <div class="meta-item">
            <span class="meta-label">Status</span>
            <span class="meta-value">Scheduled Upgrade</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Estimasi</span>
            <span class="meta-value">~ 1 - 2 Jam</span>
          </div>
          <div class="meta-item">
            <span class="meta-label">Progress</span>
            <span class="meta-value" id="progressText">68%</span>
          </div>
        </div>

        <div class="actions">
          <a class="button primary" href="#" onclick="location.reload()">Refresh Halaman</a>
          <a class="button secondary" href="mailto:support@domainkamu.com">Hubungi Support</a>
        </div>
      </div>

      <div class="right">
        <div class="planet-wrap">
          <div class="spark s1"></div>
          <div class="spark s2"></div>
          <div class="spark s3"></div>
          <div class="spark s4"></div>
          <div class="ring"></div>
          <div class="planet">
            <div class="gear">⚙️</div>
          </div>
        </div>
      </div>
    </section>

    <div class="footer-note">
      © <span id="year"></span> Your Company — Terima kasih sudah menunggu.
    </div>
  </main>

  <script>
    document.getElementById('year').textContent = new Date().getFullYear();

    const progressText = document.getElementById('progressText');
    let value = 68;
    let direction = 1;

    setInterval(() => {
      value += direction;
      if (value >= 92) direction = -1;
      if (value <= 68) direction = 1;
      progressText.textContent = value + '%';
    }, 140);
  </script>
</body>
</html>
