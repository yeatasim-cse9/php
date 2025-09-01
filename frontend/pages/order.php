<?php
// frontend/pages/order.php
// Online Order Page: Menu fetch -> Cart -> Checkout (AJAX to backend/public API)
// Now with auto user_id injection from localStorage (cr_user)
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Order Online | The Cafe Rio – Gulshan</title>
</head>
<body>

  <!-- Header -->
  <?php include __DIR__ . "/../partials/header.html"; ?>

  <section id="order" class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-4">
        <h1 class="fw-bold">Order Online</h1>
        <p class="text-muted mb-0">মেনু থেকে আইটেম বাছাই করুন, কার্টে যোগ করুন, এবং পিকআপ/ডেলিভারির মাধ্যমে অর্ডার কনফার্ম করুন।</p>
      </div>

      <!-- Login notice (shown only if not logged in) -->
      <div id="loginNotice" class="alert alert-warning d-none mb-4" role="alert">
        আপনি লগইন করেননি। <a class="alert-link" id="loginLink" href="/restaurant-app/frontend/pages/login.php">Login</a> করলে আপনার তথ্য অটো–ফিল হবে।
      </div>

      <div class="row g-4">
        <!-- Menu List -->
        <div class="col-lg-8">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Menu</h5>
                <button id="reloadMenu" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-arrow-clockwise me-1"></i> Reload
                </button>
              </div>

              <div id="menuAlert" class="alert d-none" role="alert"></div>

              <div id="menuGrid" class="row g-3">
                <!-- Cards injected here -->
              </div>
            </div>
          </div>
        </div>

        <!-- Cart & Checkout -->
        <div class="col-lg-4">
          <div class="card shadow-sm mb-3">
            <div class="card-body p-4">
              <h5 class="mb-3">Your Cart</h5>

              <div id="cartEmpty" class="text-muted">কার্ট ফাঁকা। মেনু থেকে আইটেম যোগ করুন।</div>
              <ul id="cartList" class="list-group list-group-flush d-none"></ul>

              <div id="cartTotals" class="mt-3 d-none">
                <div class="d-flex justify-content-between">
                  <span class="fw-semibold">Subtotal</span>
                  <span id="cartSubtotal" class="fw-bold">৳0</span>
                </div>
              </div>

              <div class="d-grid mt-3">
                <button id="clearCart" class="btn btn-outline-danger" disabled>
                  <i class="bi bi-trash3 me-1"></i> Clear Cart
                </button>
              </div>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-body p-4">
              <h5 class="mb-3">Checkout</h5>

              <div id="orderAlert" class="alert d-none" role="alert"></div>

              <form id="checkoutForm" novalidate>
                <!-- User ID (auto-filled & hidden when logged in) -->
                <div class="mb-3" id="userIdWrap">
                  <label class="form-label" for="user_id">User ID <span class="text-danger">*</span></label>
                  <input type="number" inputmode="numeric" min="1" class="form-control" id="user_id" placeholder="e.g. 2" required>
                  <div class="form-text">লগইন করলে এটি অটো-ফিল ও হাইড হবে।</div>
                  <div class="invalid-feedback">Valid User ID দিন।</div>
                </div>

                <div class="mb-3">
                  <label class="form-label d-block">Delivery Type</label>
                  <div class="btn-group w-100" role="group" aria-label="delivery-type">
                    <input type="radio" class="btn-check" name="delivery_type" id="typePickup" value="pickup" checked>
                    <label class="btn btn-outline-secondary" for="typePickup">Pickup</label>

                    <input type="radio" class="btn-check" name="delivery_type" id="typeDelivery" value="delivery">
                    <label class="btn btn-outline-secondary" for="typeDelivery">Delivery</label>
                  </div>
                </div>

                <div class="mb-3 d-none" id="addressWrap">
                  <label class="form-label" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
                  <textarea id="delivery_address" class="form-control" rows="3" placeholder="House/Road, Area, City"></textarea>
                  <div class="invalid-feedback">ডেলিভারি এড্রেস দিন।</div>
                </div>

                <div class="d-grid">
                  <button type="submit" id="placeOrderBtn" class="btn btn-danger" disabled>
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="orderSpinner" aria-hidden="true"></span>
                    Place Order
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

  <!-- Success Modal -->
  <div class="modal fade" id="orderSuccessModal" tabindex="-1" aria-labelledby="orderSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0">
        <div class="modal-header border-0">
          <h5 class="modal-title" id="orderSuccessLabel"><i class="bi bi-bag-check me-2"></i>Order Placed</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-1">আপনার অর্ডার রিসিভ করা হয়েছে।</p>
          <div id="orderSuccessDetails" class="small text-muted"></div>
        </div>
        <div class="modal-footer border-0">
          <a href="/restaurant-app/index.php#order" class="btn btn-danger">OK</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // -------------------------
    // Auth integration (auto user)
    // -------------------------
    const loadUser = () => { try { return JSON.parse(localStorage.getItem('cr_user')||'null'); } catch { return null; } };
    function applyUser(){
      const u = loadUser();
      const userWrap = document.getElementById('userIdWrap');
      const userInput = document.getElementById('user_id');
      const loginNotice = document.getElementById('loginNotice');
      const loginLink = document.getElementById('loginLink');

      // set login redirect back to this page
      const redirectUrl = encodeURIComponent('/restaurant-app/frontend/pages/order.php');
      loginLink.href = `/restaurant-app/frontend/pages/login.php?redirect=${redirectUrl}`;

      if (u && u.user_id){
        userInput.value = u.user_id;
        userWrap.classList.add('d-none');   // hide user_id field
        loginNotice.classList.add('d-none');
      } else {
        userWrap.classList.remove('d-none');
        loginNotice.classList.remove('d-none');
      }
    }

    // -------------------------
    // Helpers
    // -------------------------
    const bdCurrency = v => `৳${Number(v).toFixed(0)}`;
    const el = sel => document.querySelector(sel);
    const elAll = sel => [...document.querySelectorAll(sel)];
    const create = (tag, cls) => { const e = document.createElement(tag); if (cls) e.className = cls; return e; };

    function showAlert(box, type, msg){
      box.className = `alert alert-${type}`;
      box.textContent = msg;
      box.classList.remove('d-none');
    }
    function hideAlert(box){
      box.classList.add('d-none');
    }

    // -------------------------
    // Menu Fetch & Render
    // -------------------------
    const menuGrid = el('#menuGrid');
    const menuAlert = el('#menuAlert');
    const reloadBtn = el('#reloadMenu');

    async function loadMenu(){
      hideAlert(menuAlert);
      menuGrid.innerHTML = '<div class="col-12 text-center text-muted py-4">Loading menu...</div>';
      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=list');
        const data = await res.json().catch(()=> ({}));
        if (!res.ok) {
          throw new Error(data?.error || 'Failed to load menu');
        }
        renderMenu(data.items || []);
      } catch (err) {
        menuGrid.innerHTML = '';
        showAlert(menuAlert, 'danger', err.message || 'Could not load menu');
      }
    }

    function renderMenu(items){
      menuGrid.innerHTML = '';
      if (!items.length) {
        menuGrid.innerHTML = '<div class="col-12 text-center text-muted py-4">No items available.</div>';
        return;
      }
      items.forEach(item => {
        const col = create('div', 'col-md-6 col-xl-4');
        const card = create('div', 'card h-100 shadow-sm');
        const img = create('img', 'card-img-top');
        img.alt = item.name;
        img.src = `/restaurant-app/frontend/assets/images/${item.image || 'placeholder.jpg'}`;
        img.onerror = () => { img.src = '/restaurant-app/frontend/assets/images/placeholder.jpg'; };

        const body = create('div', 'card-body d-flex flex-column');
        const title = create('h6', 'fw-bold mb-1');
        title.textContent = item.name;

        const desc = create('p', 'text-muted small mb-2');
        desc.textContent = item.description || '';

        const price = create('div', 'fw-bold mb-3');
        price.textContent = bdCurrency(item.price || 0);

        const actions = create('div', 'mt-auto d-flex gap-2');
        const qtyInput = create('input', 'form-control');
        qtyInput.type = 'number';
        qtyInput.min = '1';
        qtyInput.value = '1';
        qtyInput.style.maxWidth = '90px';

        const addBtn = create('button', 'btn btn-danger');
        addBtn.innerHTML = '<i class="bi bi-cart-plus me-1"></i> Add';
        addBtn.addEventListener('click', ()=>{
          const qty = Math.max(1, parseInt(qtyInput.value, 10) || 1);
          addToCart({
            item_id: item.item_id,
            name: item.name,
            price: Number(item.price),
            quantity: qty
          });
        });

        actions.append(qtyInput, addBtn);
        body.append(title, desc, price, actions);
        card.append(img, body);
        col.append(card);
        menuGrid.append(col);
      });
    }

    reloadBtn.addEventListener('click', loadMenu);
    window.addEventListener('load', loadMenu);

    // -------------------------
    // Cart Logic (in-memory + localStorage)
    // -------------------------
    const CART_KEY = 'cr_cart';
    let cart = [];

    function saveCart(){
      localStorage.setItem(CART_KEY, JSON.stringify(cart));
    }
    function loadCart(){
      try {
        cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]') || [];
      } catch { cart = []; }
      renderCart();
    }

    function addToCart(item){
      const idx = cart.findIndex(x => x.item_id === item.item_id);
      if (idx > -1) {
        cart[idx].quantity += item.quantity;
      } else {
        cart.push(item);
      }
      saveCart();
      renderCart();
    }
    function removeFromCart(item_id){
      cart = cart.filter(x => x.item_id !== item_id);
      saveCart();
      renderCart();
    }
    function setQty(item_id, qty){
      const row = cart.find(x => x.item_id === item_id);
      if (!row) return;
      row.quantity = Math.max(1, qty);
      saveCart();
      renderCart();
    }
    function clearCart(){
      cart = [];
      saveCart();
      renderCart();
    }
    function cartSubtotal(){
      return cart.reduce((sum, r) => sum + (Number(r.price) * Number(r.quantity)), 0);
    }

    const cartEmpty = el('#cartEmpty');
    const cartList = el('#cartList');
    const cartTotals = el('#cartTotals');
    const cartSubtotalEl = el('#cartSubtotal');
    const clearCartBtn = el('#clearCart');
    const placeOrderBtn = el('#placeOrderBtn');

    function renderCart(){
      cartList.innerHTML = '';
      if (!cart.length) {
        cartEmpty.classList.remove('d-none');
        cartList.classList.add('d-none');
        cartTotals.classList.add('d-none');
        clearCartBtn.disabled = true;
        placeOrderBtn.disabled = true;
        return;
      }
      cartEmpty.classList.add('d-none');
      cartList.classList.remove('d-none');
      cartTotals.classList.remove('d-none');
      clearCartBtn.disabled = false;
      placeOrderBtn.disabled = false;

      cart.forEach(row => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center justify-content-between';
        const left = document.createElement('div');
        left.innerHTML = `<div class="fw-semibold">${row.name}</div><div class="small text-muted">${bdCurrency(row.price)}</div>`;

        const right = document.createElement('div');
        right.className = 'd-flex align-items-center gap-2';
        const qty = document.createElement('input');
        qty.className = 'form-control form-control-sm';
        qty.type = 'number';
        qty.min = '1';
        qty.value = row.quantity;
        qty.style.width = '70px';
        qty.addEventListener('change', () => {
          const val = Math.max(1, parseInt(qty.value, 10) || 1);
          setQty(row.item_id, val);
        });

        const rm = document.createElement('button');
        rm.className = 'btn btn-outline-secondary btn-sm';
        rm.innerHTML = '<i class="bi bi-x-lg"></i>';
        rm.addEventListener('click', ()=> removeFromCart(row.item_id));

        right.append(qty, rm);
        li.append(left, right);
        cartList.append(li);
      });

      cartSubtotalEl.textContent = bdCurrency(cartSubtotal());
    }

    clearCartBtn.addEventListener('click', clearCart);
    window.addEventListener('load', loadCart);

    // -------------------------
    // Checkout -> POST /orders#create
    // -------------------------
    const checkoutForm = el('#checkoutForm');
    const orderAlert = el('#orderAlert');
    const orderSpinner = el('#orderSpinner');

    function setOrdering(loading){
      placeOrderBtn.disabled = loading || cart.length === 0;
      orderSpinner.classList.toggle('d-none', !loading);
    }

    // delivery type toggle
    elAll('input[name="delivery_type"]').forEach(r => {
      r.addEventListener('change', ()=>{
        const isDelivery = el('#typeDelivery').checked;
        el('#addressWrap').classList.toggle('d-none', !isDelivery);
      });
    });

    // auto-user on load
    window.addEventListener('load', applyUser);

    checkoutForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      hideAlert(orderAlert);

      // basic validity
      checkoutForm.classList.add('was-validated');
      const user_id = parseInt(el('#user_id').value, 10);
      if (!user_id || user_id < 1) {
        showAlert(orderAlert, 'warning', 'Valid User ID দিন বা আগে লগইন করুন।');
        return;
      }
      if (!cart.length) {
        showAlert(orderAlert, 'warning', 'কার্ট ফাঁকা। মেনু থেকে আইটেম যোগ করুন।');
        return;
      }

      const delivery_type = el('#typeDelivery').checked ? 'delivery' : 'pickup';
      let delivery_address = null;
      if (delivery_type === 'delivery') {
        delivery_address = (el('#delivery_address').value || '').trim();
        if (!delivery_address) {
          showAlert(orderAlert, 'warning', 'ডেলিভারি এড্রেস দিন।');
          return;
        }
      }

      const payload = {
        user_id,
        items: cart.map(r => ({ item_id: r.item_id, quantity: r.quantity })),
        delivery_type,
        delivery_address
      };

      setOrdering(true);
      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=orders&a=create', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json().catch(()=> ({}));

        if (!res.ok) {
          const msg = data?.error || 'Order failed';
          showAlert(orderAlert, 'danger', msg);
          return;
        }

        // success
        clearCart();
        const details = `
          <div>Order ID: <b>${data.order?.order_id ?? '—'}</b></div>
          <div>Total: <b>${bdCurrency(data.order?.total_amount ?? 0)}</b></div>
          <div>Status: <b>${data.order?.status ?? 'pending'}</b></div>
          <div>Type: <b>${data.order?.delivery_type ?? '-'}</b></div>`;
        el('#orderSuccessDetails').innerHTML = details;
        new bootstrap.Modal('#orderSuccessModal').show();

      } catch (err) {
        showAlert(orderAlert, 'danger', 'Network error. Please try again.');
      } finally {
        setOrdering(false);
      }
    });
  </script>
</body>
</html>
