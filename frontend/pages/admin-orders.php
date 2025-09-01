<?php
// frontend/pages/admin-orders.php
// Admin Orders Management UI — list, filter, update status, view items
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Orders | The Cafe Rio – Gulshan</title>
  <style>
    .toolbar .form-select, .toolbar .form-control{ min-width: 140px }
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .status-pill{ padding:.28rem .6rem; border-radius:18px; font-size:.8rem; display:inline-block }
    .st-pending{ background:rgba(255,193,7,.15); color:#8a6d00 }
    .st-preparing{ background:rgba(13,110,253,.12); color:#0d6efd }
    .st-ready{ background:rgba(102,16,242,.12); color:#6610f2 }
    .st-completed{ background:rgba(25,135,84,.15); color:#198754 }
    .st-cancelled{ background:rgba(220,53,69,.12); color:#dc3545 }
    .table thead th{ white-space:nowrap }
    .offcanvas-end{ width: 420px }
    .muted{ color:#6c757d }
    .pointer{ cursor:pointer }
  </style>
</head>
<body>

  <!-- Header -->
  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Orders Management</h1>
          <div class="muted">Update statuses, inspect line items, and filter quickly.</div>
        </div>
        <div>
          <a href="/restaurant-app/frontend/pages/admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
        </div>
      </div>

      <!-- Filters Toolbar -->
      <div class="card card-elev mb-4">
        <div class="card-body">
          <div class="row g-2 align-items-end toolbar">
            <div class="col-md-3">
              <label class="form-label">Date Range</label>
              <select id="dateRange" class="form-select">
                <option value="today" selected>Today</option>
                <option value="last7">Last 7 days</option>
                <option value="all">All</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select id="statusFilter" class="form-select">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="preparing">Preparing</option>
                <option value="ready">Ready</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">User ID</label>
              <input type="number" id="userFilter" class="form-control" placeholder="e.g. 2" min="1">
            </div>
            <div class="col-md-3 d-flex gap-2">
              <button id="btnApply" class="btn btn-danger flex-fill">
                <i class="bi bi-filter-circle me-1"></i> Apply
              </button>
              <button id="btnReset" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div id="alertBox" class="alert d-none" role="alert"></div>

      <!-- Orders Table -->
      <div class="card card-elev">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Orders</h5>
            <div class="small muted">
              <span id="countMeta">—</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Customer</th>
                  <th>Status</th>
                  <th>Type</th>
                  <th>Total</th>
                  <th>Time</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="tblOrders">
                <tr><td colspan="7" class="text-center muted py-4">Loading…</td></tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div class="d-flex justify-content-between align-items-center pt-3">
            <div class="small muted" id="pageMeta">—</div>
            <div class="btn-group">
              <button id="btnPrev" class="btn btn-outline-secondary btn-sm" disabled>
                <i class="bi bi-chevron-left"></i>
              </button>
              <button id="btnNext" class="btn btn-outline-secondary btn-sm" disabled>
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>

  <!-- Offcanvas: Order Details -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="ocDetails" aria-labelledby="ocDetailsLabel">
    <div class="offcanvas-header">
      <h5 id="ocDetailsLabel" class="mb-0">Order Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="muted small mb-2" id="ocMeta">—</div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold">Items</div>
        <div>
          <span class="status-pill st-pending d-none" id="tagPending">Pending</span>
          <span class="status-pill st-preparing d-none" id="tagPreparing">Preparing</span>
          <span class="status-pill st-ready d-none" id="tagReady">Ready</span>
          <span class="status-pill st-completed d-none" id="tagCompleted">Completed</span>
          <span class="status-pill st-cancelled d-none" id="tagCancelled">Cancelled</span>
        </div>
      </div>

      <ul class="list-group list-group-flush mb-3" id="ocItems">
        <li class="list-group-item muted">Loading…</li>
      </ul>

      <div class="d-flex justify-content-between">
        <div class="fw-semibold">Total</div>
        <div class="fw-bold" id="ocTotal">৳0</div>
      </div>

      <hr class="my-3">

      <div class="fw-semibold mb-2">Update Status</div>
      <div class="d-grid gap-2">
        <button class="btn btn-outline-secondary" data-status="pending">Mark Pending</button>
        <button class="btn btn-primary" data-status="preparing">Mark Preparing</button>
        <button class="btn btn-warning" data-status="ready">Mark Ready</button>
        <button class="btn btn-success" data-status="completed">Mark Completed</button>
        <button class="btn btn-danger" data-status="cancelled">Cancel Order</button>
      </div>

      <div id="ocAlert" class="alert d-none mt-3" role="alert"></div>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // -----------------------
    // Helpers
    // -----------------------
    const el = s => document.querySelector(s);
    const elAll = s => [...document.querySelectorAll(s)];
    const bd = v => `৳${Number(v||0).toFixed(0)}`;
    function showAlert(box, type, msg){
      box.className = `alert alert-${type}`;
      box.textContent = msg;
      box.classList.remove('d-none');
    }
    function hideAlert(box){
      box.classList.add('d-none');
    }
    function pill(status){
      const cls = {
        pending:'st-pending', preparing:'st-preparing', ready:'st-ready',
        completed:'st-completed', cancelled:'st-cancelled'
      }[status] || 'st-pending';
      return `<span class="status-pill ${cls} text-capitalize">${status}</span>`;
    }
    const fmtTime = iso => {
      try { return new Date(iso).toLocaleString([], {hour:'2-digit', minute:'2-digit'}); }
      catch { return '—'; }
    }

    // -----------------------
    // State
    // -----------------------
    let orders = [];          // full list (filtered by date on fetch)
    let page = 1;
    const PAGE_SIZE = 10;
    let currentOrderId = null;
    let currentOrderStatus = null;

    // -----------------------
    // Fetch Orders with server filters we can support now:
    // - If dateRange = today, we just fetch all and filter client-side by DATE(order_date) (we already have Admin API showing today)
    // - For now we will fetch all (orders list endpoint), then filter client-side by date range & status & user
    // If data grows large, you can adjust backend list to accept date_from/date_to later.
    // -----------------------
    async function fetchOrders(){
      const statusVal = el('#statusFilter').value;
      const userVal = el('#userFilter').value.trim();
      let url = '/restaurant-app/backend/public/index.php?r=orders&a=list';
      const params = [];
      if (statusVal) params.push(`status=${encodeURIComponent(statusVal)}`);
      if (userVal) params.push(`user_id=${encodeURIComponent(userVal)}`);
      if (params.length) url += '&' + params.join('&');
      const res = await fetch(url);
      const data = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(data?.error || 'Failed to load orders');
      return data.items || [];
    }

    function applyDateRange(rows){
      const range = el('#dateRange').value;
      if (range === 'all') return rows;
      const now = new Date();
      const start = new Date(now);
      if (range === 'today'){
        // keep same day
        return rows.filter(r => {
          const d = new Date(r.order_date);
          return d.getFullYear()===now.getFullYear() && d.getMonth()===now.getMonth() && d.getDate()===now.getDate();
        });
      }
      if (range === 'last7'){
        start.setDate(start.getDate() - 7);
        return rows.filter(r => new Date(r.order_date) >= start);
      }
      return rows;
    }

    function renderTable(){
      const tbody = el('#tblOrders');
      tbody.innerHTML = '';

      const startIdx = (page-1) * PAGE_SIZE;
      const endIdx = startIdx + PAGE_SIZE;
      const pageRows = orders.slice(startIdx, endIdx);

      if (!pageRows.length){
        tbody.innerHTML = `<tr><td colspan="7" class="text-center muted py-4">No orders found.</td></tr>`;
      } else {
        pageRows.forEach(o=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="pointer" data-action="open" data-id="${o.order_id}">#${o.order_id}</td>
            <td>${o.customer_name}</td>
            <td>${pill(o.status)}</td>
            <td class="text-capitalize">${o.delivery_type}</td>
            <td class="fw-semibold">${bd(o.total_amount)}</td>
            <td><span class="badge text-bg-light">${fmtTime(o.order_date)}</span></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-secondary" data-action="open" data-id="${o.order_id}">
                  <i class="bi bi-eye"></i>
                </button>
                ${actionButtons(o)}
              </div>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }

      // meta + pagination
      const total = orders.length;
      const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
      el('#countMeta').textContent = `${total} result${total!==1?'s':''}`;
      el('#pageMeta').textContent = `Page ${page} of ${totalPages}`;
      el('#btnPrev').disabled = page <= 1;
      el('#btnNext').disabled = page >= totalPages;

      // bind row actions
      tbody.querySelectorAll('[data-action="open"]').forEach(btn=>{
        btn.addEventListener('click', ()=> openDetails(parseInt(btn.getAttribute('data-id'), 10)));
      });
      tbody.querySelectorAll('[data-action="status"]').forEach(btn=>{
        btn.addEventListener('click', ()=> quickUpdateStatus(parseInt(btn.dataset.id,10), btn.dataset.to));
      });
    }

    function actionButtons(o){
      if (['completed','cancelled'].includes(o.status)) {
        return `<button class="btn btn-outline-secondary" disabled><i class="bi bi-check2-circle"></i></button>`;
      }
      // suggest next logical step
      const flow = { pending:'preparing', preparing:'ready', ready:'completed' };
      const next = flow[o.status] || 'preparing';
      return `
        <button class="btn btn-outline-primary" data-action="status" data-id="${o.order_id}" data-to="${next}" title="Mark ${next}">
          <i class="bi bi-arrow-right-circle"></i>
        </button>
        <button class="btn btn-outline-danger" data-action="status" data-id="${o.order_id}" data-to="cancelled" title="Cancel">
          <i class="bi bi-x-circle"></i>
        </button>`;
    }

    async function loadOrders(){
      hideAlert(el('#alertBox'));
      el('#tblOrders').innerHTML = `<tr><td colspan="7" class="text-center muted py-4">Loading…</td></tr>`;
      try {
        const rows = await fetchOrders();
        orders = applyDateRange(rows).sort((a,b)=> new Date(b.order_date) - new Date(a.order_date));
        page = 1;
        renderTable();
      } catch(err){
        showAlert(el('#alertBox'),'danger', err.message || 'Unable to load orders');
      }
    }

    // -----------------------
    // Offcanvas: Details + Items + Status
    // -----------------------
    const oc = new bootstrap.Offcanvas('#ocDetails');
    async function openDetails(order_id){
      currentOrderId = order_id;
      // find basic info from list
      const row = orders.find(x => x.order_id === order_id);
      if (row){
        currentOrderStatus = row.status;
        el('#ocMeta').textContent = `Order #${row.order_id} • ${row.customer_name} • ${row.delivery_type} • ${new Date(row.order_date).toLocaleString()}`;
        setStatusTags(row.status);
      } else {
        el('#ocMeta').textContent = `Order #${order_id}`;
      }
      // load items
      await loadOrderItems(order_id);
      oc.show();
    }

    function setStatusTags(status){
      const map = {
        pending: '#tagPending', preparing: '#tagPreparing', ready:'#tagReady', completed:'#tagCompleted', cancelled:'#tagCancelled'
      };
      Object.values(map).forEach(sel => el(sel).classList.add('d-none'));
      const sel = map[status] || '#tagPending';
      el(sel).classList.remove('d-none');
    }

    async function loadOrderItems(order_id){
      const list = el('#ocItems');
      const totalEl = el('#ocTotal');
      list.innerHTML = '<li class="list-group-item muted">Loading…</li>';
      totalEl.textContent = '৳0';
      try {
        const url = `/restaurant-app/backend/public/index.php?r=orders&a=items&order_id=${encodeURIComponent(order_id)}`;
        const res = await fetch(url);
        const data = await res.json().catch(()=> ({}));
        if (!res.ok) throw new Error(data?.error || 'Failed to load items');

        const items = data.items || [];
        const order = data.order || {};
        totalEl.textContent = bd(order.total_amount || 0);

        if (!items.length){
          list.innerHTML = '<li class="list-group-item muted">No items.</li>';
        } else {
          list.innerHTML = '';
          items.forEach(it=>{
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
              <div>
                <div class="fw-semibold">${it.name}</div>
                <div class="small muted">৳${Number(it.price).toFixed(0)} × ${it.quantity}</div>
              </div>
              <div class="fw-bold">${bd(it.line_total)}</div>
            `;
            list.appendChild(li);
          });
        }
      } catch(err){
        list.innerHTML = `<li class="list-group-item text-danger">${err.message || 'Error loading items'}</li>`;
      }
    }

    // Offcanvas status buttons:
    elAll('#ocDetails [data-status]').forEach(btn=>{
      btn.addEventListener('click', ()=> {
        const to = btn.getAttribute('data-status');
        updateStatus(currentOrderId, to, true);
      });
    });

    // Quick status update from table row:
    async function quickUpdateStatus(order_id, to){
      await updateStatus(order_id, to, false);
    }

    async function updateStatus(order_id, to, insideCanvas){
      if (!order_id || !to) return;
      const ocAlert = el('#ocAlert');
      if (insideCanvas) hideAlert(ocAlert);
      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=orders&a=update_status', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ order_id, status: to })
        });
        const data = await res.json().catch(()=> ({}));
        if (!res.ok) {
          const msg = data?.error || 'Update failed';
          if (insideCanvas) showAlert(ocAlert, 'danger', msg);
          else showAlert(el('#alertBox'), 'danger', msg);
          return;
        }

        // Update local copy
        const row = orders.find(x => x.order_id === order_id);
        if (row) row.status = to;

        // Re-render table
        renderTable();

        // If inside canvas and it's the same order, refresh tags + items meta
        if (insideCanvas) {
          setStatusTags(to);
          showAlert(ocAlert, 'success', `Status updated: ${data.from} → ${data.to}`);
        }
      } catch(err){
        if (insideCanvas) showAlert(ocAlert, 'danger', 'Network error');
        else showAlert(el('#alertBox'), 'danger', 'Network error');
      }
    }

    // -----------------------
    // Pagination
    // -----------------------
    el('#btnPrev').addEventListener('click', ()=> { if(page>1){ page--; renderTable(); } });
    el('#btnNext').addEventListener('click', ()=> { page++; renderTable(); });

    // -----------------------
    // Filters
    // -----------------------
    el('#btnApply').addEventListener('click', loadOrders);
    el('#btnReset').addEventListener('click', ()=>{
      el('#dateRange').value = 'today';
      el('#statusFilter').value = '';
      el('#userFilter').value = '';
      loadOrders();
    });

    // init
    window.addEventListener('load', loadOrders);
  </script>
</body>
</html>
