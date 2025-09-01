<?php
// frontend/pages/admin-dashboard.php
// Admin Dashboard UI — KPIs + recent orders + upcoming reservations
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Dashboard | The Cafe Rio – Gulshan</title>
  <style>
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .muted{ color:#6c757d }
    .kpi{ padding:18px }
    .kpi h2{ font-size:1.6rem; margin:0 }
    .kpi .label{ font-size:.9rem; color:#6c757d }
    .kpi .icon{ width:44px; height:44px; border-radius:12px; display:grid; place-items:center; }
    .icon-red{ background:rgba(220,53,69,.1); color:#dc3545 }
    .icon-green{ background:rgba(25,135,84,.12); color:#198754 }
    .icon-blue{ background:rgba(13,110,253,.12); color:#0d6efd }
    .icon-purple{ background:rgba(102,16,242,.12); color:#6610f2 }
    .icon-amber{ background:rgba(255,193,7,.15); color:#8a6d00 }
    .status-pill{ padding:.22rem .5rem; border-radius:16px; font-size:.75rem; display:inline-block }
    .st-pending{ background:rgba(255,193,7,.15); color:#8a6d00 }
    .st-preparing{ background:rgba(13,110,253,.12); color:#0d6efd }
    .st-ready{ background:rgba(102,16,242,.12); color:#6610f2 }
    .st-completed{ background:rgba(25,135,84,.15); color:#198754 }
    .st-cancelled{ background:rgba(220,53,69,.12); color:#dc3545 }
    .table thead th{ white-space:nowrap }
  </style>
</head>
<body>

  <!-- Header -->
  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Admin Dashboard</h1>
          <div class="muted">Today’s snapshot: orders, revenue, reservations, and ratings.</div>
        </div>
        <div class="d-flex gap-2">
          <a href="/restaurant-app/frontend/pages/admin-orders.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-bag me-1"></i> Orders
          </a>
          <a href="/restaurant-app/frontend/pages/admin-reservations.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-calendar2-check me-1"></i> Reservations
          </a>
          <a href="/restaurant-app/frontend/pages/admin-menu.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-egg-fried me-1"></i> Menu
          </a>
        </div>
      </div>

      <!-- KPIs -->
      <div class="row g-3 mb-4">
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-blue"><i class="bi bi-bag"></i></div>
              <div>
                <div class="label">Today’s Orders</div>
                <h2 id="kpiOrders">0</h2>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-green"><i class="bi bi-cash-stack"></i></div>
              <div>
                <div class="label">Revenue (৳)</div>
                <h2 id="kpiRevenue">0</h2>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-amber"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="label">Pending Orders</div>
                <h2 id="kpiPending">0</h2>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-purple"><i class="bi bi-people"></i></div>
              <div>
                <div class="label">Today’s Reservations</div>
                <h2 id="kpiResv">0</h2>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-green"><i class="bi bi-check2-circle"></i></div>
              <div>
                <div class="label">Confirmed (Today)</div>
                <h2 id="kpiResvConfirmed">0</h2>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-lg-2">
          <div class="card card-elev kpi">
            <div class="d-flex align-items-center gap-3">
              <div class="icon icon-red"><i class="bi bi-star-half"></i></div>
              <div>
                <div class="label">Avg Rating</div>
                <h2 id="kpiAvg">0.0</h2>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alerts -->
      <div id="alertBox" class="alert d-none mb-4" role="alert"></div>

      <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-lg-7">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="/restaurant-app/frontend/pages/admin-orders.php" class="small">Manage &rarr;</a>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Customer</th>
                      <th>Status</th>
                      <th>Type</th>
                      <th class="text-end">Total</th>
                      <th>Time</th>
                    </tr>
                  </thead>
                  <tbody id="tblOrders">
                    <tr><td colspan="6" class="text-center muted py-4">Loading…</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Reservations -->
        <div class="col-lg-5">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Upcoming Reservations</h5>
                <a href="/restaurant-app/frontend/pages/admin-reservations.php" class="small">Manage &rarr;</a>
              </div>
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Date</th>
                      <th>Time</th>
                      <th>Customer</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="tblResv">
                    <tr><td colspan="5" class="text-center muted py-4">Loading…</td></tr>
                  </tbody>
                </table>
              </div>
              <div class="small muted mt-2" id="resvMeta">—</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // Helpers
    const el = s => document.querySelector(s);
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }
    const BDT = n => '৳' + Number(n||0).toFixed(0);
    const pad = n => String(n).padStart(2,'0');
    const todayStr = ()=>{ const d=new Date(); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; };
    const tomorrowStr = ()=>{ const d=new Date(); d.setDate(d.getDate()+1); return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`; };
    const pill = st => {
      const cls = {pending:'st-pending',preparing:'st-preparing',ready:'st-ready',completed:'st-completed',cancelled:'st-cancelled'}[st]||'st-pending';
      return `<span class="status-pill ${cls} text-capitalize">${st}</span>`;
    };

    // Load all dashboard data
    async function initDashboard(){
      hideAlert(el('#alertBox'));
      try {
        // Try admin summary if available (optional; will ignore failure)
        let admin = null;
        try{
          const a = await fetch('/restaurant-app/backend/public/index.php?r=admin&a=dashboard');
          admin = await a.json().catch(()=>null);
        }catch(_){/* ignore */}

        // Orders (limit 100)
        const oRes = await fetch('/restaurant-app/backend/public/index.php?r=orders&a=list&limit=100');
        const oData = await oRes.json().catch(()=>({}));
        if(!oRes.ok) throw new Error(oData?.error || 'Failed loading orders');
        const orders = oData.items || [];

        // Compute today metrics from orders
        const today = new Date(todayStr());
        let kOrders = 0, kRevenue = 0, kPending = 0;
        const recent = [...orders].sort((a,b)=> new Date(b.order_date)-new Date(a.order_date)).slice(0,10);
        orders.forEach(o=>{
          const d = new Date(o.order_date);
          if (d.getFullYear()===today.getFullYear() && d.getMonth()===today.getMonth() && d.getDate()===today.getDate()){
            kOrders++;
            if (o.status !== 'cancelled') kRevenue += Number(o.total_amount||0);
            if (o.status === 'pending') kPending++;
          }
        });

        // Reservations: today & tomorrow
        const rTodayUrl = `/restaurant-app/backend/public/index.php?r=reservations&a=list&date_from=${todayStr()}&date_to=${todayStr()}&limit=100`;
        const rTomorrowUrl = `/restaurant-app/backend/public/index.php?r=reservations&a=list&date_from=${tomorrowStr()}&date_to=${tomorrowStr()}&limit=100`;
        const [r1Res, r2Res] = await Promise.all([fetch(rTodayUrl), fetch(rTomorrowUrl)]);
        const r1 = await r1Res.json().catch(()=>({items:[]}));
        const r2 = await r2Res.json().catch(()=>({items:[]}));
        if(!r1Res.ok) throw new Error(r1?.error || 'Failed loading reservations');
        if(!r2Res.ok) throw new Error(r2?.error || 'Failed loading reservations');

        const todayResv = r1.items || [];
        const tomorrowResv = r2.items || [];
        const kResv = todayResv.length;
        const kResvConfirmed = todayResv.filter(x=>x.status==='confirmed').length;

        // Reviews summary (global)
        const rvRes = await fetch('/restaurant-app/backend/public/index.php?r=reviews&a=summary');
        const rvData = await rvRes.json().catch(()=>({}));
        if(!rvRes.ok) throw new Error(rvData?.error || 'Failed loading ratings');
        const avg = rvData?.global?.avg ?? (rvData?.item?.avg ?? 0);

        // Render KPIs
        el('#kpiOrders').textContent = kOrders;
        el('#kpiRevenue').textContent = Math.round(kRevenue).toLocaleString();
        el('#kpiPending').textContent = kPending;
        el('#kpiResv').textContent = kResv;
        el('#kpiResvConfirmed').textContent = kResvConfirmed;
        el('#kpiAvg').textContent = Number(avg||0).toFixed(1);

        // Render Recent Orders
        const tbo = el('#tblOrders');
        if (!recent.length){
          tbo.innerHTML = `<tr><td colspan="6" class="text-center muted py-4">No orders.</td></tr>`;
        } else {
          tbo.innerHTML = '';
          recent.forEach(o=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>#${o.order_id}</td>
              <td>${o.customer_name || ''}</td>
              <td>${pill(o.status)}</td>
              <td class="text-capitalize">${o.delivery_type}</td>
              <td class="text-end fw-semibold">${BDT(o.total_amount)}</td>
              <td><span class="badge text-bg-light">${new Date(o.order_date).toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</span></td>
            `;
            tbo.appendChild(tr);
          });
        }

        // Render Upcoming Reservations (today + tomorrow)
        const tbr = el('#tblResv');
        const upcoming = [...todayResv, ...tomorrowResv].sort((a,b)=>{
          const ad = a.reservation_date.localeCompare(b.reservation_date);
          if (ad !== 0) return ad;
          return a.reservation_time.localeCompare(b.reservation_time);
        }).slice(0, 12);

        if (!upcoming.length){
          tbr.innerHTML = `<tr><td colspan="5" class="text-center muted py-4">No reservations.</td></tr>`;
        } else {
          tbr.innerHTML = '';
          upcoming.forEach(r=>{
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>#${r.reservation_id}</td>
              <td>${r.reservation_date}</td>
              <td><span class="badge text-bg-light">${r.reservation_time}</span></td>
              <td>${r.customer_name}</td>
              <td><span class="status-pill ${r.status==='confirmed'?'st-completed':(r.status==='cancelled'?'st-cancelled':'st-pending')} text-capitalize">${r.status}</span></td>
            `;
            tbr.appendChild(tr);
          });
          el('#resvMeta').textContent = `Showing ${upcoming.length} (today + tomorrow)`;
        }

      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Failed to load dashboard');
      }
    }

    window.addEventListener('load', initDashboard);
  </script>
</body>
</html>
