<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>จัดการสินค้า</title>
  <link rel="stylesheet" href="/assets/style.css" />
  <style>
    .grid { background:#fff;border-radius:12px;padding:12px; }
    .table { width:100%; border-collapse: collapse; }
    .table th, .table td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
    .form { background:#fff;border-radius:12px;padding:12px; margin-bottom:12px; display:grid; gap:8px; grid-template-columns: 2fr 1fr 1fr auto; align-items:end; }
    .input { padding:10px;border:1px solid #e5e7eb;border-radius:8px;font-size:16px; }
    .actions { display:flex; gap:8px; }
    @media(max-width: 920px){ .form{ grid-template-columns: 1fr; } }
  </style>
  <script>
    async function ensureAuth(){
      const res = await fetch('/api/auth.php?action=status');
      const data = await res.json();
      if(!data.authenticated){ window.location.href = '/login.php'; }
    }
    let stateProducts = [];
    function renderRows(){
      const rows = stateProducts.map(p=>`
        <tr id="row-${p.id}">
          <td>${p.id}</td>
          <td>${p.name}</td>
          <td>฿ ${Number(p.price).toFixed(2)}</td>
          <td>${p.stock}</td>
          <td>
            <button class="btn btn-secondary" onclick="startEdit(${p.id})">แก้ไข</button>
            <button class="btn remove-btn" onclick="deleteProduct(${p.id})">ลบ</button>
          </td>
        </tr>
      `).join('');
      document.getElementById('tbody').innerHTML = rows || '<tr><td colspan="5" style="text-align:center;color:#6b7280;">ไม่มีสินค้า</td></tr>';
    }
    async function loadProducts(){
      const res = await fetch('/api/products.php');
      const data = await res.json();
      stateProducts = (data.data||[]);
      renderRows();
    }
    async function addProduct(ev){
      ev.preventDefault();
      const name = document.getElementById('name').value.trim();
      const price = parseFloat(document.getElementById('price').value);
      const stock = parseInt(document.getElementById('stock').value);
      if(!name || !(price>0) || stock<0){ alert('กรอกข้อมูลให้ครบถ้วน'); return; }
      const res = await fetch('/api/products.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({name,price,stock})});
      const data = await res.json();
      if(!res.ok){ alert(data.error||'บันทึกไม่สำเร็จ'); return; }
      document.getElementById('form').reset();
      stateProducts.push(data);
      renderRows();
      alert('เพิ่มสินค้าเรียบร้อย');
    }
    function startEdit(id){
      const p = stateProducts.find(x=>x.id==id);
      if(!p) return;
      const tr = document.getElementById('row-'+id);
      tr.innerHTML = `
        <td>${p.id}</td>
        <td><input id="e-name-${id}" class="input" value="${p.name}"></td>
        <td><input id="e-price-${id}" class="input" type="number" step="0.01" min="0" value="${Number(p.price).toFixed(2)}"></td>
        <td><input id="e-stock-${id}" class="input" type="number" step="1" min="0" value="${p.stock}"></td>
        <td>
          <button class="btn btn-primary" onclick="saveEdit(${id})">บันทึก</button>
          <button class="btn btn-secondary" onclick="cancelEdit(${id})">ยกเลิก</button>
        </td>
      `;
    }
    function cancelEdit(id){ renderRows(); }
    async function saveEdit(id){
      const name = document.getElementById('e-name-'+id).value.trim();
      const price = parseFloat(document.getElementById('e-price-'+id).value);
      const stock = parseInt(document.getElementById('e-stock-'+id).value);
      if(!name || !(price>=0) || stock<0){ alert('ข้อมูลไม่ถูกต้อง'); return; }
      const res = await fetch('/api/products.php',{method:'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, name, price, stock})});
      const data = await res.json();
      if(!res.ok){ alert(data.error||'แก้ไขไม่สำเร็จ'); return; }
      const idx = stateProducts.findIndex(x=>x.id==id);
      if(idx>=0) stateProducts[idx] = data;
      renderRows();
      alert('บันทึกการแก้ไขแล้ว');
    }
    async function deleteProduct(id){
      if(!confirm('ยืนยันการลบสินค้า #' + id + ' ?')) return;
      const res = await fetch('/api/products.php',{method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
      const data = await res.json();
      if(!res.ok){ alert(data.error||'ลบไม่สำเร็จ'); return; }
      stateProducts = stateProducts.filter(x=>x.id!=id);
      renderRows();
      alert('ลบสินค้าเรียบร้อย');
    }
    document.addEventListener('DOMContentLoaded',()=>{ ensureAuth(); loadProducts(); document.getElementById('form').addEventListener('submit',addProduct); });
  </script>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <h1>จัดการสินค้า</h1>
        <a class="btn btn-secondary" href="/sale.php">กลับไปหน้าขาย</a>
      </div>

      <form id="form" class="form" onsubmit="addProduct(event)">
        <div>
          <label>ชื่อสินค้า</label>
          <input id="name" class="input" placeholder="เช่น น้ำดื่ม 600ml" required />
        </div>
        <div>
          <label>ราคา (บาท)</label>
          <input id="price" class="input" type="number" step="0.01" min="0" placeholder="0.00" required />
        </div>
        <div>
          <label>สต็อก</label>
          <input id="stock" class="input" type="number" step="1" min="0" placeholder="0" required />
        </div>
        <div class="actions">
          <button class="btn btn-primary" type="submit">เพิ่มสินค้า</button>
        </div>
      </form>

      <div class="grid">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>ชื่อสินค้า</th>
              <th>ราคา</th>
              <th>สต็อก</th>
              <th>การทำงาน</th>
            </tr>
          </thead>
          <tbody id="tbody"></tbody>
        </table>
      </div>
    </div>
  </body>
  </html>

