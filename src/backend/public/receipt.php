<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ใบเสร็จรับเงิน</title>
  <link rel="stylesheet" href="/assets/style.css" />
  <style>
    .receipt { max-width: 420px; margin: 0 auto; background: #fff; padding: 16px; }
    .receipt h1 { font-size: 20px; margin: 0 0 8px; text-align: center; }
    .receipt .meta { font-size: 13px; color: #555; text-align: center; margin-bottom: 12px; }
    .receipt table { width: 100%; border-collapse: collapse; }
    .receipt th, .receipt td { font-size: 14px; padding: 6px 0; }
    .receipt .right { text-align: right; }
    .receipt .total { font-weight: bold; border-top: 1px dashed #999; margin-top: 8px; padding-top: 8px; }
    .receipt .qr { display: block; margin: 16px auto 8px; width: 200px; height: 200px; }
    .receipt .actions { text-align: center; margin-top: 12px; }
  </style>
</head>
<body>
  <div class="receipt" id="receipt">
    <h1>ใบเสร็จรับเงิน</h1>
    <div class="meta" id="meta">กำลังโหลด...</div>
    <table>
      <thead>
        <tr>
          <th>สินค้า</th>
          <th class="right">จำนวน</th>
          <th class="right">ราคา</th>
          <th class="right">รวม</th>
        </tr>
      </thead>
      <tbody id="items"></tbody>
    </table>
    <div class="total right" id="total"></div>
    <img id="qr" class="qr" alt="PromptPay QR" />
    <div class="actions">
      <button class="btn btn-primary" onclick="window.print()">พิมพ์</button>
      <a class="btn btn-secondary" href="/sale.php">กลับไปหน้าขาย</a>
    </div>
  </div>

  <script>
    (function() {
      const params = new URLSearchParams(window.location.search);
      const saleId = params.get('sale_id');
      const meta = document.getElementById('meta');
      const body = document.getElementById('items');
      const total = document.getElementById('total');
      const qr = document.getElementById('qr');
      if (!saleId) {
        meta.textContent = 'ไม่พบหมายเลขการขาย';
        return;
      }

      fetch(`/api/sales.php?action=detail&id=${saleId}`)
        .then(r => r.json())
        .then(res => {
          if (res.error) throw new Error(res.error);
          const s = res.sale;
          meta.textContent = `เลขที่ใบเสร็จ #${s.id} | วันที่ ${s.sale_date}`;
          body.innerHTML = res.items.map(it => `
            <tr>
              <td>${it.name}</td>
              <td class=right>${Number(it.quantity)}</td>
              <td class=right>${Number(it.price).toFixed(2)}</td>
              <td class=right>${Number(it.line_total).toFixed(2)}</td>
            </tr>
          `).join('');
          total.textContent = `ยอดรวมสุทธิ ${Number(s.total_amount).toFixed(2)} บาท`;
          qr.src = res.promptpay_url;
        })
        .catch(err => {
          meta.textContent = 'เกิดข้อผิดพลาด: ' + err.message;
        });
    })();
  </script>
</body>
</html>

