<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>POS - ชำระเงิน</title>
  <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>
  <div class="container narrow">
    <header class="header">
      <h1>ชำระเงิน</h1>
    </header>

    <div id="paymentInfo" class="payment-info">
      กำลังโหลดข้อมูล...
    </div>

    <div class="payment-actions">
      <a id="printBtn" class="btn btn-primary" href="#" target="_blank">พิมพ์ใบเสร็จ</a>
      <a class="btn btn-secondary" href="/sale.php">ขายรายการใหม่</a>
    </div>
  </div>

  <script>
    (function() {
      const params = new URLSearchParams(window.location.search);
      const saleId = params.get('sale_id');
      const info = document.getElementById('paymentInfo');
      const printBtn = document.getElementById('printBtn');
      if (!saleId) {
        info.textContent = 'ไม่พบหมายเลขการขาย';
        return;
      }

      fetch(`/api/sales.php?action=detail&id=${saleId}`)
        .then(r => r.json())
        .then(res => {
          if (res.error) throw new Error(res.error);
          const amount = Number(res.sale.total_amount).toFixed(2);
          const qrUrl = res.promptpay_url;
          printBtn.href = `/receipt.php?sale_id=${saleId}`;
          info.innerHTML = `
            <div class="pay-amount">ยอดที่ต้องชำระ: <strong>${amount}</strong> บาท</div>
            <div class="qr-wrap">
              <img class="qr" src="${qrUrl}" alt="PromptPay QR" />
              <div class="qr-caption">สแกนเพื่อชำระเงิน (PromptPay)</div>
            </div>
          `;
        })
        .catch(err => {
          info.textContent = 'เกิดข้อผิดพลาด: ' + err.message;
        });
    })();
  </script>
</body>
</html>

