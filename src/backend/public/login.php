<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>เข้าสู่ระบบ - POS</title>
  <link rel="stylesheet" href="/assets/style.css" />
  <style>
    .login-card { max-width: 420px; margin: 12vh auto; background:#fff; padding:20px; border-radius:12px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); }
    .input { width:100%; padding:12px; border:1px solid #e5e7eb; border-radius:10px; font-size:16px; }
    .stack { display:grid; gap:10px; }
  </style>
</head>
<body>
  <div class="login-card">
    <h2 style="margin-top:0;">เข้าสู่ระบบเจ้าหน้าที่</h2>
    <div class="stack">
      <input id="username" class="input" placeholder="ชื่อผู้ใช้" />
      <input id="password" class="input" type="password" placeholder="รหัสผ่าน" />
      <button class="btn btn-primary" id="loginBtn">เข้าสู่ระบบ</button>
      <div id="msg" style="color:#b91c1c"></div>
    </div>
  </div>
  <script>
    document.getElementById('loginBtn').addEventListener('click', async ()=>{
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const msg = document.getElementById('msg');
      msg.textContent='';
      try{
        const res = await fetch('/api/auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username,password})});
        const data = await res.json();
        if(!res.ok) throw new Error(data.error||'เข้าสู่ระบบไม่สำเร็จ');
        window.location.href = '/products.php';
      }catch(e){ msg.textContent = e.message; }
    });
  </script>
</body>
</html>



