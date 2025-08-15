<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>POS - หน้าขายสินค้า</title>
  <link rel="stylesheet" href="/assets/style.css" />
</head>
<body>
  <div class="container">
    <header class="header">
      <h1>POS - บันทึกการขาย</h1>
      <div class="total-display">ยอดรวม: <span id="grandTotal">0.00</span> บาท</div>
      <a class="btn btn-secondary" href="/login.php" style="margin-left:12px;">เข้าสู่ระบบเจ้าหน้าที่</a>
    </header>

    <main class="layout">
      <section class="products">
        <h2>สินค้า</h2>
        <div id="productGrid" class="product-grid"></div>
      </section>

      <section class="cart">
        <h2>ตะกร้า</h2>
        <div id="cartList" class="cart-list"></div>
        <div class="cart-actions">
          <button id="clearCartBtn" class="btn btn-secondary">ล้างตะกร้า</button>
          <button id="checkoutBtn" class="btn btn-primary">ชำระเงิน</button>
        </div>
      </section>
    </main>
  </div>

  <script src="/assets/app.js"></script>
</body>
</html>

