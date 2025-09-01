<?php
// frontend/pages/admin-reservations.php
// Admin Reservations Management UI — list, filter, confirm/cancel, details drawer
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Reservations | The Cafe Rio – Gulshan</title>
  <style>
    .toolbar .form-select, .toolbar .form-control{ min-width: 140px }
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .status-pill{ padding:.28rem .6rem; border-radius:18px; font-size:.8rem; display:inline-block }
    .st-pending{ background:rgba(255,193,7,.15); color:#8a6d00 }
    .st-confirmed{ background:rgba(25,135,84,.15); color:#198754 }
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
          <h1 class="h3 fw-bold mb-1">Reservations Management</h1>
          <div class="muted">Confirm / Cancel bookings, and review details & special requests.</div>
        </div>
        <div class="d-flex gap-2">
          <a href="/restaurant-app/frontend/pages/admin-dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-speedometer2 me-1"></i> Dashboard
          </a>
          <a href="/restaurant-app/frontend/pages/admin-orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bag me-1"></i> Orders
          </a>
        </div>
      </div>

      <!-- Filters Toolbar -->
      <div class="card card-elev mb-4">
        <div class="card-body">
          <div class="row g-2 align-items-end toolbar">
            <div class="col-md-3">
              <label class="form-label">Quick Date</label>
              <select id="quickRange" class="form-select">
                <option value="today" selected>Today</option>
                <option value="tomorrow">Tomorrow</option>
                <option value="next7">Next 7 days</option>
                <option value="all">All (use custom)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Custom From</label>
              <input type="date" id="dateFrom" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Custom To</label>
              <input type="date" id="dateTo" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Status</label>
              <select id="statusFilter" class="form-select">
                <option value="">All</option>
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div class="row g-2 align-items-end toolbar mt-2">
            <div class="col-md-3">
              <label class="form-label">User ID</label>
              <input type="number" id="userFilter" class="form-control" placeholder="e.g. 2" min="1">
            </div>
            <div class="col-md-5">
              <label class="form-label">Search (Name / Special Request)</label>
              <input type="text" id="searchText" class="form-control" placeholder="Type to match customer name or note">
            </div>
            <div class="col-md-4 d-flex gap-2">
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

      <!-- Reservations Table -->
      <div class="card card-elev">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Reservations</h5>
            <div class="small muted">
              <span id="countMeta">—</span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Customer</th>
                  <th>People</th>
                  <th>Table</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody id="tblResv">
                <tr><td colspan="8" class="text-center muted py-4">Loading…</td></tr>
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

  <!-- Offcanvas: Reservation Details -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="ocDetails" aria-labelledby="ocDetailsLabel">
    <div class="offcanvas-header">
      <h5 id="ocDetailsLabel" class="mb-0">Reservation Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
      <div class="muted small mb-2" id="ocMeta">—</div>

      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-semibold">Summary</div>
        <div>
          <span class="status-pill st-pending d-none"   id="tagPending">Pending</span>
          <span class="status-pill st-confirmed d-none" id="tagConfirmed">Confirmed</span>
          <span class="status-pill st-cancelled d-none" id="tagCancelled">Cancelled</span>
        </div>
      </div>

      <ul class="list-group list-group-flush mb-3">
        <li class="list-group-item">
          <div class="small muted">Customer</div>
          <div id="ocName" class="fw-semibold">—</div>
        </li>
        <li class="list-group-item">
          <div class="small muted">People & Table</div>
          <div id="ocPeople" class="fw-semibold">—</div>
        </li>
        <li class="list-group-item">
          <div class="small muted">Date & Time</div>
          <div id="ocDateTime" class="fw-semibold">—</div>
        </li>
        <li class="list-group-item">
          <div class="small muted">Special Request</div>
          <div id="ocSreq" class="fw-semibold">—</div>
        </li>
      </ul>

      <div class="fw-semibold mb-2">Update Status</div>
      <div class="d-grid gap-2">
        <button class="btn btn-outline-secondary" data-status="pending">Mark Pending</button>
        <button class="btn btn-success"            data-status="confirmed">Mark Confirmed</button>
        <button class="btn btn-danger"             data-status="cancelled">Cancel Reservation</button>
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
    function showAlert(box, type, msg){
      box.className = `alert alert-${type}`;
      box.textContent = msg;
      box.classList.remove('d-none');
    }
    function hideAlert(box){ box.classList.add('d-none'); }
    const pad = n => String(n).padStart(2,'0');

    function pill(status){
      const cls = { pending:'st-pending', confirmed:'st-confirmed', cancelled:'st-cancelled' }[status] || 'st-pending';
      return `<span class="status-pill ${cls} text-capitalize">${status}</span>`;
    }

    // -----------------------
    // Date utilities
    // -----------------------
    function todayStr(){
      const d = new Date();
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
    function tomorrowStr(){
      const d = new Date();
      d.setDate(d.getDate()+1);
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }
    function plusDaysStr(days){
      const d = new Date();
      d.setDate(d.getDate()+days);
      return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
    }

    // -----------------------
    // State
    // -----------------------
    let rowsAll = [];        // API fetched rows (server filtered by query)
    let rowsView = [];       // client-side filtered (search)
    let page = 1;
    const PAGE_SIZE = 10;
    let currentId = null;

    // -----------------------
    // Fetch with server-side filters
    // (status, user_id, date_from, date_to)
    // -----------------------
    async function fetchReservations(){
      const status = el('#statusFilter').value;
      const userId = el('#userFilter').value.trim();
      const df = el('#dateFrom').value.trim();
      const dt = el('#dateTo').value.trim();

      const params = [];
      if (status) params.push(`status=${encodeURIComponent(status)}`);
      if (userId) params.push(`user_id=${encodeURIComponent(userId)}`);
      if (df) params.push(`date_from=${encodeURIComponent(df)}`);
      if (dt) params.push(`date_to=${encodeURIComponent(dt)}`);

      let url = '/restaurant-app/backend/public/index.php?r=reservations&a=list';
      if (params.length) url += '&' + params.join('&');

      const res = await fetch(url);
      const data = await res.json().catch(()=> ({}));
      if (!res.ok) throw new Error(data?.error || 'Failed to load reservations');
      return data.items || [];
    }

    function applySearch(rows){
      const q = el('#searchText').value.trim().toLowerCase();
      if (!q) return rows;

      return rows.filter(r => {
        const name = (r.customer_name || '').toLowerCase();
        const req  = (r.special_request || '').toLowerCase();
        return name.includes(q) || req.includes(q);
      });
    }

    function renderTable(){
      const tbody = el('#tblResv');
      tbody.innerHTML = '';

      rowsView = applySearch(rowsAll);

      const startIdx = (page-1) * PAGE_SIZE;
      const endIdx = startIdx + PAGE_SIZE;
      const pageRows = rowsView.slice(startIdx, endIdx);

      if (!pageRows.length){
        tbody.innerHTML = `<tr><td colspan="8" class="text-center muted py-4">No reservations found.</td></tr>`;
      } else {
        pageRows.forEach(r=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="pointer" data-action="open" data-id="${r.reservation_id}">#${r.reservation_id}</td>
            <td>${r.reservation_date}</td>
            <td><span class="badge text-bg-light">${r.reservation_time}</span></td>
            <td>${r.customer_name}</td>
            <td>${r.people_count}</td>
            <td class="text-capitalize">${r.table_type}</td>
            <td>${pill(r.status)}</td>
            <td class="text-end">${actionButtons(r)}</td>
          `;
          tbody.appendChild(tr);
        });
      }

      // meta + pagination
      const total = rowsView.length;
      const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
      el('#countMeta').textContent = `${total} result${total!==1?'s':''}`;
      el('#pageMeta').textContent = `Page ${page} of ${totalPages}`;
      el('#btnPrev').disabled = page <= 1;
      el('#btnNext').disabled = page >= totalPages;

      // bind actions
      tbody.querySelectorAll('[data-action="open"]').forEach(btn=>{
        btn.addEventListener('click', ()=> openDetails(parseInt(btn.getAttribute('data-id'), 10)));
      });
      tbody.querySelectorAll('[data-action="status"]').forEach(btn=>{
        btn.addEventListener('click', ()=> quickUpdateStatus(parseInt(btn.dataset.id,10), btn.dataset.to));
      });
    }

    function actionButtons(r){
      if (r.status === 'cancelled') {
        return `<button class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-x-circle"></i></button>`;
      }
      // Pending -> Confirmed, Confirmed -> (can stay confirmed), Any -> Cancelled
      const next = (r.status === 'pending') ? 'confirmed' : 'pending';
      const labelIcon = (next === 'confirmed') ? '<i class="bi bi-check2"></i>' : '<i class="bi bi-hourglass"></i>';
      return `
        <div class="btn-group btn-group-sm">
          <button class="btn btn-outline-success" data-action="status" data-id="${r.reservation_id}" data-to="confirmed" title="Confirm">
            <i class="bi bi-check2-circle"></i>
          </button>
          <button class="btn btn-outline-warning" data-action="open" data-id="${r.reservation_id}" title="Details">
            <i class="bi bi-eye"></i>
          </button>
          <button class="btn btn-outline-danger" data-action="status" data-id="${r.reservation_id}" data-to="cancelled" title="Cancel">
            <i class="bi bi-x-circle"></i>
          </button>
        </div>
      `;
    }

    async function loadReservations(){
      hideAlert(el('#alertBox'));
      el('#tblResv').innerHTML = `<tr><td colspan="8" class="text-center muted py-4">Loading…</td></tr>`;
      try {
        const rows = await fetchReservations();
        rowsAll = rows.sort((a,b)=>{
          const ad = a.reservation_date.localeCompare(b.reservation_date);
          if (ad !== 0) return ad;
          return a.reservation_time.localeCompare(b.reservation_time);
        });
        page = 1;
        renderTable();
      } catch(err){
        showAlert(el('#alertBox'),'danger', err.message || 'Unable to load reservations');
      }
    }

    // -----------------------
    // Details Drawer
    // -----------------------
    const oc = new bootstrap.Offcanvas('#ocDetails');
    function setStatusTags(status){
      const map = { pending:'#tagPending', confirmed:'#tagConfirmed', cancelled:'#tagCancelled' };
      Object.values(map).forEach(sel => el(sel).classList.add('d-none'));
      const sel = map[status] || '#tagPending';
      el(sel).classList.remove('d-none');
    }

    function openDetails(reservation_id){
      const r = rowsAll.find(x => x.reservation_id === reservation_id);
      if (!r) return;

      currentId = reservation_id;
      el('#ocMeta').textContent = `Reservation #${r.reservation_id} • ${r.reservation_date} ${r.reservation_time}`;
      el('#ocName').textContent = `${r.customer_name} (User #${r.user_id})`;
      el('#ocPeople').textContent = `${r.people_count} people • ${r.table_type}`;
      el('#ocDateTime').textContent = `${r.reservation_date} • ${r.reservation_time}`;
      el('#ocSreq').textContent = r.special_request ? r.special_request : '—';
      setStatusTags(r.status);
      hideAlert(el('#ocAlert'));
      oc.show();
    }

    // Offcanvas status buttons
    elAll('#ocDetails [data-status]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const to = btn.getAttribute('data-status');
        updateStatus(currentId, to, true);
      });
    });

    // Quick status update from table row
    async function quickUpdateStatus(reservation_id, to){
      await updateStatus(reservation_id, to, false);
    }

    async function updateStatus(reservation_id, to, insideCanvas){
      if (!reservation_id || !to) return;
      const ocAlert = el('#ocAlert');
      if (insideCanvas) hideAlert(ocAlert);

      try {
        const res = await fetch('/restaurant-app/backend/public/index.php?r=reservations&a=update_status', {
          method:'POST',
          headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ reservation_id, status: to })
        });
        const data = await res.json().catch(()=> ({}));
        if (!res.ok){
          const msg = data?.error || 'Update failed';
          if (insideCanvas) showAlert(ocAlert,'danger', msg);
          else showAlert(el('#alertBox'),'danger', msg);
          return;
        }

        // update local copy
        const row = rowsAll.find(x => x.reservation_id === reservation_id);
        if (row) row.status = to;

        renderTable();

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
    // Quick Range -> set custom dates
    // -----------------------
    function setQuickRange(){
      const q = el('#quickRange').value;
      const f = el('#dateFrom');
      const t = el('#dateTo');

      if (q === 'today'){
        f.value = todayStr();
        t.value = todayStr();
      } else if (q === 'tomorrow'){
        f.value = tomorrowStr();
        t.value = tomorrowStr();
      } else if (q === 'next7'){
        f.value = todayStr();
        t.value = plusDaysStr(7);
      } else {
        // all -> clear custom
        f.value = '';
        t.value = '';
      }
    }
    el('#quickRange').addEventListener('change', setQuickRange);

    // -----------------------
    // Filters events
    // -----------------------
    el('#btnApply').addEventListener('click', loadReservations);
    el('#btnReset').addEventListener('click', ()=>{
      el('#quickRange').value = 'today';
      el('#statusFilter').value = '';
      el('#userFilter').value = '';
      el('#searchText').value = '';
      setQuickRange();
      loadReservations();
    });

    // init
    window.addEventListener('load', ()=>{
      // default to "today"
      setQuickRange();
      loadReservations();
    });
  </script>
</body>
</html>
