<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Protected — Build4Performance × XCLusive Racing</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,400;0,600;0,700;0,800;1,700;1,800&family=Inter:wght@300;400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --ink:#0a0a0f;
  --surface:#111118;
  --panel:#16161f;
  --edge:#1e1e2a;
  --red:#c8102e;
  --red-dim:rgba(200,16,46,0.14);
  --white:#f0eff4;
  --muted:#6e6d82;
  --light:rgba(240,239,244,0.65);
}
html,body{height:100%}
body{background:var(--ink);color:var(--white);font-family:'Inter',sans-serif;font-weight:400;display:flex;align-items:center;justify-content:center;padding:24px}
.bg-grid{position:fixed;inset:0;background-image:linear-gradient(rgba(200,16,46,0.04) 1px,transparent 1px),linear-gradient(90deg,rgba(200,16,46,0.04) 1px,transparent 1px);background-size:70px 70px;mask-image:radial-gradient(ellipse 90% 70% at 50% 50%,black,transparent);pointer-events:none}
.card{position:relative;z-index:1;width:100%;max-width:380px;background:var(--panel);border:1px solid var(--edge);border-radius:4px;padding:2.5rem 2rem;text-align:center}
.kicker-tag{display:inline-block;font-family:'Roboto Mono',monospace;font-size:0.62rem;letter-spacing:0.22em;text-transform:uppercase;color:var(--red);padding:4px 12px;border:1px solid rgba(200,16,46,0.35);border-radius:1px;margin-bottom:1.5rem}
h1{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.9rem;line-height:1;text-transform:uppercase;letter-spacing:-0.01em;margin-bottom:0.75rem}
p.sub{font-size:0.85rem;color:var(--light);font-weight:300;margin-bottom:1.75rem}
form{display:flex;flex-direction:column;gap:1rem}
input[type=password]{background:var(--surface);border:1px solid var(--edge);border-radius:2px;color:var(--white);font-family:'Inter',sans-serif;font-size:0.9rem;padding:12px 14px;outline:none;transition:border-color 0.2s}
input[type=password]:focus{border-color:var(--red)}
.btn-red{background:var(--red);color:#fff;font-size:0.75rem;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;border:none;border-radius:2px;padding:13px 28px;cursor:pointer;transition:background 0.2s}
.btn-red:hover{background:#a50d25}
.error{font-family:'Roboto Mono',monospace;font-size:0.7rem;letter-spacing:0.04em;color:var(--red);margin-top:-0.25rem}
</style>
</head>
<body>
<div class="bg-grid"></div>
<div class="card">
  <span class="kicker-tag">Confidential</span>
  <h1>Protected Page</h1>
  <p class="sub">Enter the password you were given to view this proposal.</p>
  <form method="POST" action="{{ route('b4p-proposal.unlock') }}">
    @csrf
    <input type="password" name="password" placeholder="Password" autofocus required>
    @if(!empty($error))
      <div class="error">Incorrect password. Try again.</div>
    @endif
    <button type="submit" class="btn-red">Unlock</button>
  </form>
</div>
</body>
</html>
