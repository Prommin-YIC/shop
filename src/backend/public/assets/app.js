const state = {
  products: [],
  cart: [],
};

function formatMoney(n) {
  return Number(n).toFixed(2);
}

function findCartIndex(productId) {
  return state.cart.findIndex(i => i.product_id === productId);
}

function addToCart(product) {
  const idx = findCartIndex(product.id);
  if (idx >= 0) {
    const item = state.cart[idx];
    if (item.quantity + 1 > product.stock) return; // don't exceed stock
    item.quantity += 1;
  } else {
    state.cart.push({
      product_id: product.id,
      name: product.name,
      price: Number(product.price),
      quantity: 1,
      stock: product.stock,
    });
  }
  renderCart();
}

function increaseQty(productId) {
  const idx = findCartIndex(productId);
  if (idx < 0) return;
  const item = state.cart[idx];
  if (item.quantity + 1 > item.stock) return;
  item.quantity += 1;
  renderCart();
}

function decreaseQty(productId) {
  const idx = findCartIndex(productId);
  if (idx < 0) return;
  const item = state.cart[idx];
  item.quantity -= 1;
  if (item.quantity <= 0) {
    state.cart.splice(idx, 1);
  }
  renderCart();
}

function removeItem(productId) {
  const idx = findCartIndex(productId);
  if (idx < 0) return;
  state.cart.splice(idx, 1);
  renderCart();
}

function calcGrandTotal() {
  return state.cart.reduce((s, it) => s + it.quantity * it.price, 0);
}

function renderProducts() {
  const grid = document.getElementById('productGrid');
  grid.innerHTML = state.products.map(p => `
    <button class="product-card" onclick='window.__add(${JSON.stringify(p).replace(/"/g, "&quot;")})'>
      <div class="product-name">${p.name}</div>
      <div class="product-price">฿ ${formatMoney(p.price)}</div>
      <div class="product-stock">คงเหลือ ${p.stock}</div>
    </button>
  `).join('');
}

function renderCart() {
  const list = document.getElementById('cartList');
  const total = document.getElementById('grandTotal');
  if (!list || !total) return;

  if (state.cart.length === 0) {
    list.innerHTML = '<div style="text-align:center;color:#6b7280;padding:16px;">ยังไม่มีสินค้าในตะกร้า</div>';
    total.textContent = '0.00';
    return;
  }

  list.innerHTML = state.cart.map(it => `
    <div class="cart-item">
      <div>
        <div style="font-weight:600;">${it.name}</div>
        <div style="color:#6b7280;font-size:12px;">฿ ${formatMoney(it.price)} x ${it.quantity}</div>
      </div>
      <div class="qty-controls">
        <button class="btn qty-btn" onclick="decreaseQty(${it.product_id})">-</button>
        <div style="min-width:32px;text-align:center;font-size:18px;">${it.quantity}</div>
        <button class="btn qty-btn" onclick="increaseQty(${it.product_id})">+</button>
      </div>
      <div style="font-weight:700;">฿ ${formatMoney(it.quantity * it.price)}</div>
      <button class="btn remove-btn" onclick="removeItem(${it.product_id})">ลบ</button>
    </div>
  `).join('');

  total.textContent = formatMoney(calcGrandTotal());
}

async function loadProducts() {
  const res = await fetch('/api/products.php');
  const data = await res.json();
  state.products = (data.data || []).filter(p => p.stock > 0);
  renderProducts();
}

async function checkout() {
  if (state.cart.length === 0) return;
  const payload = {
    items: state.cart.map(it => ({ product_id: it.product_id, quantity: it.quantity }))
  };
  try {
    const res = await fetch('/api/sales.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Sale failed');
    // redirect to payment
    window.location.href = `/payment.php?sale_id=${data.sale_id}`;
  } catch (e) {
    alert('เกิดข้อผิดพลาด: ' + e.message);
  }
}

function clearCart() {
  state.cart = [];
  renderCart();
}

window.__add = addToCart;
window.increaseQty = increaseQty;
window.decreaseQty = decreaseQty;
window.removeItem = removeItem;
window.checkout = checkout;
window.clearCart = clearCart;

document.addEventListener('DOMContentLoaded', () => {
  loadProducts();
  renderCart();
  const checkoutBtn = document.getElementById('checkoutBtn');
  const clearCartBtn = document.getElementById('clearCartBtn');
  if (checkoutBtn) checkoutBtn.addEventListener('click', checkout);
  if (clearCartBtn) clearCartBtn.addEventListener('click', clearCart);
});

