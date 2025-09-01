<?php
// frontend/pages/admin-reviews.php
// Admin Reviews Moderation UI — filter/list/delete + item/global summary
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin • Reviews Moderation | The Cafe Rio – Gulshan</title>
  <style>
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .muted{ color:#6c757d }
    .star{ color:#ffc107 }
    .star-muted{ color:#e9ecef }
    .review-item{ border-bottom:1px dashed #eee; padding: .9rem 0 }
    .table thead th{ white-space:nowrap }
    .progress{ height:.6rem }
    .chip{ display:inline-block; padding:.22rem .5rem; border-radius:12px; background:#f1f3f5; font-size:.8rem }
    .pointer{ cursor:pointer }
  </style>
</head>
<body>

  <?php include __DIR__ . "/../partials/header.html"; ?>

  <main class="py-5 bg-light">
    <div class="container">

      <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
          <h1 class="h3 fw-bold mb-1">Reviews Moderation</h1>
          <div class="muted">View, filter and delete inappropriate reviews.</div>
        </div>
        <div class="d-flex gap-2">
          <a href="/restaurant-app/frontend/pages/admin-dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
          <a href="/restaurant-app/frontend/pages/admin-menu.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-egg-fried me-1"></i> Menu</a>
          <a href="/restaurant-app/frontend/pages/admin-orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-bag me-1"></i> Orders</a>
        </div>
      </div>

      <!-- Filters -->
      <div class="card card-elev mb-4">
        <div class="card-body">
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label">Filter by Item</label>
              <select id="fItem" class="form-select">
                <option value="">All (Restaurant)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Filter by User ID</label>
              <input id="fUser" class="form-control" type="number" min="1" placeholder="e.g. 2">
            </div>
            <div class="col-md-3">
              <label class="form-label">Search (name/comment)</label>
              <input id="fQ" class="form-control" placeholder="type text">
            </div>
            <div class="col-md-2 d-flex gap-2">
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

      <div class="row g-4">
        <!-- Reviews List -->
        <div class="col-lg-7">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Reviews</h5>
                <div class="small muted">
                  <span id="countMeta">—</span>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>User</th>
                      <th>Rating</th>
                      <th>Item</th>
                      <th>Comment</th>
                      <th>Time</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody id="tblReviews">
                    <tr><td colspan="7" class="text-center muted py-4">Loading…</td></tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="d-flex justify-content-between align-items-center pt-3">
                <div class="small muted" id="pageMeta">—</div>
                <div class="btn-group">
                  <button id="btnPrev" class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-chevron-left"></i></button>
                  <button id="btnNext" class="btn btn-outline-secondary btn-sm" disabled><i class="bi bi-chevron-right"></i></button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Summary panel -->
        <div class="col-lg-5">
          <div class="card card-elev h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Ratings Summary</h5>
                <span id="summaryScope" class="chip">Global</span>
              </div>

              <div class="display-6 fw-bold" id="avgScore">0.0</div>
              <div id="avgStars" class="fs-4 mb-2"></div>
              <div class="muted small mb-3" id="totalReviews">—</div>

              <div class="mb-2 d-flex justify-content-between"><span>5 ★</span><span id="cnt5">0</span></div>
              <div class="progress mb-2"><div id="bar5" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>4 ★</span><span id="cnt4">0</span></div>
              <div class="progress mb-2"><div id="bar4" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>3 ★</span><span id="cnt3">0</span></div>
              <div class="progress mb-2"><div id="bar3" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>2 ★</span><span id="cnt2">0</span></div>
              <div class="progress mb-2"><div id="bar2" class="progress-bar bg-warning" style="width:0%"></div></div>

              <div class="mb-2 d-flex justify-content-between"><span>1 ★</span><span id="cnt1">0</span></div>
              <div class="progress"><div id="bar1" class="progress-bar bg-warning" style="width:0%"></div></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <?php include __DIR__ . "/../partials/footer.html"; ?>

  <script>
    // ----------------------
    // Helpers
    // ----------------------
    const el = s => document.querySelector(s);
    const elAll = s => [...document.querySelectorAll(s)];
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }
    const star = f => `<i class="bi bi-star${f?'-fill':''} ${f?'star':'star-muted'}"></i>`;
    const esc = s => (s||'').replace(/[&<>"']/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[t]));
    const fmtDT = s => new Date(s).toLocaleString();

    // ----------------------
    // State
    // ----------------------
    let cache = [];           // loaded list (from API)
    let view = [];            // filtered by search
    let page = 1;
    const PAGE_SIZE = 10;

    // ----------------------
    // Load menu items into filter
    // ----------------------
    async function loadMenuItems(){
      try{
        const r = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=list&limit=200');
        const d = await r.json().catch(()=>({}));
        if(!r.ok) return;  // ignore
        const items = d.items || [];
        const sel = el('#fItem');
        items.forEach(it=>{
          const op = document.createElement('option');
          op.value = it.item_id;
          op.textContent = it.name;
          sel.appendChild(op);
        });
      }catch(_){/* ignore */}
    }

    // ----------------------
    // Fetch reviews (server filters: item_id/user_id)
    // ----------------------
    async function fetchReviews(){
      const params = [];
      const item = el('#fItem').value;
      const user = el('#fUser').value.trim();
      if (item) params.push(`item_id=${encodeURIComponent(item)}`);
      if (user) params.push(`user_id=${encodeURIComponent(user)}`);
      let url = '/restaurant-app/backend/public/index.php?r=reviews&a=list&limit=200';
      if (params.length) url += '&' + params.join('&');
      const res = await fetch(url);
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Failed to load reviews');
      return data.items || [];
    }

    // ----------------------
    // Client search + render
    // ----------------------
    function applySearch(rows){
      const q = el('#fQ').value.trim().toLowerCase();
      if (!q) return rows;
      return rows.filter(r=>{
        const name = (r.user?.name || '').toLowerCase();
        const cmt  = (r.comment || '').toLowerCase();
        const item = (r.item?.name || '').toLowerCase();
        return name.includes(q) || cmt.includes(q) || item.includes(q);
      });
    }

    function renderTable(){
      const tbody = el('#tblReviews');
      tbody.innerHTML = '';

      view = applySearch(cache);

      const start = (page-1) * PAGE_SIZE;
      const end = start + PAGE_SIZE;
      const rows = view.slice(start, end);

      if (!rows.length){
        tbody.innerHTML = `<tr><td colspan="7" class="text-center muted py-4">No reviews found.</td></tr>`;
      } else {
        rows.forEach(r=>{
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>#${r.review_id}</td>
            <td>${esc(r.user?.name || '—')} <div class="small muted">ID: ${r.user?.user_id ?? '—'}</div></td>
            <td>${[1,2,3,4,5].map(i => star(i<=r.rating)).join(' ')}</td>
            <td>${r.item?.name ? esc(r.item.name) : '<span class="chip">Restaurant</span>'}</td>
            <td>${r.comment ? esc(r.comment) : '<span class="muted">—</span>'}</td>
            <td><span class="badge text-bg-light">${fmtDT(r.created_at)}</span></td>
            <td class="text-end">
              <button class="btn btn-outline-danger btn-sm" data-action="del" data-id="${r.review_id}" title="Delete">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          `;
          tbody.appendChild(tr);
        });
      }

      // meta + pagination
      const total = view.length;
      const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
      el('#countMeta').textContent = `${total} result${total!==1?'s':''}`;
      el('#pageMeta').textContent = `Page ${page} of ${totalPages}`;
      el('#btnPrev').disabled = page <= 1;
      el('#btnNext').disabled = page >= totalPages;

      // bind delete
      tbody.querySelectorAll('[data-action="del"]').forEach(btn=>{
        btn.addEventListener('click', ()=> handleDelete(parseInt(btn.dataset.id,10)));
      });
    }

    // ----------------------
    // Delete (moderate)
    // ----------------------
    async function handleDelete(review_id){
      hideAlert(el('#alertBox'));
      if (!confirm('Delete this review?')) return;
      try{
        const res = await fetch('/restaurant-app/backend/public/index.php?r=reviews&a=remove', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({ review_id })
        });
        const data = await res.json().catch(()=>({}));
        if (!res.ok) throw new Error(data?.error || 'Delete failed');

        // remove from cache & re-render
        cache = cache.filter(r => r.review_id !== review_id);
        renderTable();

        // refresh summary too (respect current item filter)
        await loadSummary(el('#fItem').value || null);
      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Delete failed');
      }
    }

    // ----------------------
    // Summary (global or item)
    // ----------------------
    async function loadSummary(item_id){
      let url = '/restaurant-app/backend/public/index.php?r=reviews&a=summary';
      if (item_id) url += `&item_id=${encodeURIComponent(item_id)}`;
      const res = await fetch(url);
      const data = await res.json().catch(()=>({}));
      if (!res.ok) throw new Error(data?.error || 'Summary load failed');

      const scope = data.scope || 'global';
      el('#summaryScope').textContent = scope === 'item' ? (data.item?.name || 'Item') : 'Global';

      let total = 0, avg = 0, stars = {1:0,2:0,3:0,4:0,5:0};
      if (scope === 'item'){
        total = data.item?.total || 0;
        avg   = data.item?.avg   || 0;
        stars = data.item?.stars || stars;
      } else {
        total = data.global?.total || 0;
        avg   = data.global?.avg   || 0;
        stars = data.global?.stars || stars;
      }

      el('#avgScore').textContent = Number(avg).toFixed(1);
      el('#avgStars').innerHTML = [1,2,3,4,5].map(i => star(i<=Math.round(avg))).join(' ');
      el('#totalReviews').textContent = `${total} review${total!==1?'s':''}`;

      const pct = v => total ? Math.round((v/total)*100) : 0;
      el('#cnt5').textContent = stars[5]||0; el('#bar5').style.width = pct(stars[5]||0)+'%';
      el('#cnt4').textContent = stars[4]||0; el('#bar4').style.width = pct(stars[4]||0)+'%';
      el('#cnt3').textContent = stars[3]||0; el('#bar3').style.width = pct(stars[3]||0)+'%';
      el('#cnt2').textContent = stars[2]||0; el('#bar2').style.width = pct(stars[2]||0)+'%';
      el('#cnt1').textContent = stars[1]||0; el('#bar1').style.width = pct(stars[1]||0)+'%';
    }

    // ----------------------
    // Events
    // ----------------------
    el('#btnPrev').addEventListener('click', ()=>{ if(page>1){ page--; renderTable(); } });
    el('#btnNext').addEventListener('click', ()=>{ page++; renderTable(); });

    el('#btnApply').addEventListener('click', async ()=>{
      hideAlert(el('#alertBox'));
      try{
        cache = await fetchReviews();
        page = 1;
        renderTable();
        await loadSummary(el('#fItem').value || null);
      }catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Failed to load');
      }
    });

    el('#btnReset').addEventListener('click', async ()=>{
      el('#fItem').value = '';
      el('#fUser').value = '';
      el('#fQ').value = '';
      hideAlert(el('#alertBox'));
      try{
        cache = await fetchReviews();
        page = 1;
        renderTable();
        await loadSummary(null);
      }catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Failed to load');
      }
    });

    // ----------------------
    // Init
    // ----------------------
    window.addEventListener('load', async ()=>{
      await loadMenuItems();
      try{
        cache = await fetchReviews();
        page = 1;
        renderTable();
        await loadSummary(null);
      } catch(err){
        showAlert(el('#alertBox'), 'danger', err.message || 'Failed to load');
      }
    });
  </script>
</body>
</html>
