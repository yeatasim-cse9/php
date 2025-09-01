<?php
// index.php — Home page for The Cafe Rio — Gulshan
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>The Cafe Rio — Gulshan | Best Buffet in Town</title>
  <meta name="description" content="The Cafe Rio — Gulshan. Best Buffet in Town. Reservations, Online Orders, and Reviews." />
  <style>
    .hero{
      position:relative;
      background: radial-gradient(1200px 500px at 20% -10%, #fff6f6, transparent),
                  radial-gradient(1000px 400px at 120% 0%, #fff0f0, transparent),
                  #ffffff;
      overflow:hidden;
    }
    .hero .blob{
      position:absolute; inset:auto -60px -120px auto; width:300px; height:300px;
      background: #dc3545; filter: blur(60px); opacity:.15; border-radius:50%;
      transform: rotate(12deg);
    }
    .hero-title{ font-size: clamp(1.8rem, 3vw + 1rem, 3rem); }
    .badge-soft{ background:rgba(220,53,69,.1); color:#dc3545; border-radius:999px; padding:.25rem .6rem; font-weight:600 }
    .card-elev{ border:0; box-shadow:0 10px 25px rgba(0,0,0,.06); border-radius:18px }
    .muted{ color:#6c757d }
    .menu-card{ border:1px solid #eee; border-radius:16px; overflow:hidden; background:#fff }
    .menu-img{ aspect-ratio: 16 / 11; width:100%; object-fit:cover; display:block }
    .menu-body{ padding:12px 14px }
    .price{ font-weight:700 }
    .chip{ display:inline-block; padding:.22rem .5rem; border-radius:12px; background:#f1f3f5; font-size:.8rem }
    .star{ color:#ffc107 } .star-muted{ color:#e9ecef }
    .link-quiet{ text-decoration:none }
    .section-title{ font-size:1.6rem; font-weight:700 }
  </style>
</head>
<body>

  <?php include __DIR__ . "/frontend/partials/header.html"; ?>

  <!-- HERO -->
  <section class="hero py-5">
    <div class="container position-relative">
      <div class="blob"></div>
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <span class="badge-soft">Best Buffet in Town</span>
          <h1 class="hero-title fw-bold mt-2">The Cafe Rio — Gulshan</h1>
          <p class="lead text-secondary mb-4">
            Family, couple, বা window-side টেবিল—ঝকঝকে ইন্টারফেসে বুকিং করুন, আর অনলাইনে অর্ডার করুন প্রিয় ডিশ।
          </p>

          <div class="d-flex flex-wrap gap-2">
            <a href="/restaurant-app/frontend/pages/reservations.php" class="btn btn-danger">
              <i class="bi bi-calendar2-check me-1"></i> Book a Table
            </a>
            <a href="/restaurant-app/frontend/pages/order.php" class="btn btn-outline-secondary">
              <i class="bi bi-bag me-1"></i> Order Online
            </a>
            <a href="#menu" class="btn btn-link link-quiet">
              Explore Menu <i class="bi bi-arrow-down-short"></i>
            </a>
          </div>

          <div class="d-flex flex-wrap gap-3 mt-4">
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-geo-alt text-danger"></i>
              <span class="muted">Jabbar Tower, 7th Floor, Gulshan-1, Dhaka 1212</span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <i class="bi bi-telephone text-danger"></i>
              <a class="link-quiet" href="tel:01799437172">01799-437172</a>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="card-elev p-3 p-md-4">
            <h5 class="fw-bold mb-2">Today’s Highlights</h5>
            <div class="d-flex align-items-center justify-content-between">
              <span class="muted">Open Hours</span>
              <span class="fw-semibold">11:30 AM – 10:30 PM</span>
            </div>
            <hr>
            <div class="d-flex align-items-center justify-content-between">
              <span class="muted">Buffet</span>
              <span><span class="badge text-bg-danger-subtle">Fresh, hot, unlimited</span></span>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <span class="muted">Avg Rating</span>
              <span id="kpiAvg" class="fw-semibold">—</span>
            </div>
            <div class="d-grid mt-3">
              <a href="/restaurant-app/frontend/pages/reviews.php" class="btn btn-outline-secondary">
                <i class="bi bi-star-half me-1"></i> See Reviews
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MENU -->
  <section id="menu" class="py-5 bg-light">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="section-title mb-0">Menu</h2>
        <a href="/restaurant-app/frontend/pages/order.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-bag me-1"></i> Order Now
        </a>
      </div>

      <!-- Tabs -->
      <div id="menuTabs" class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-sm btn-danger" data-cat="">All</button>
        <!-- dynamic categories -->
      </div>

      <!-- Search -->
      <div class="row g-3 align-items-end mb-3">
        <div class="col-md-6">
          <label class="form-label">Search dishes</label>
          <input id="menuSearch" class="form-control" placeholder="Type name or description">
        </div>
        <div class="col-md-2">
          <button id="btnMenuSearch" class="btn btn-outline-secondary w-100">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div class="col-md-4 text-md-end">
          <div id="menuMeta" class="muted small">—</div>
        </div>
      </div>

      <div id="menuAlert" class="alert d-none" role="alert"></div>

      <div id="menuGrid" class="row g-3">
        <!-- Cards -->
      </div>

      <div class="d-grid mt-3">
        <button id="btnLoadMore" class="btn btn-outline-secondary d-none">
          <i class="bi bi-plus-lg me-1"></i> Load more
        </button>
      </div>
    </div>
  </section>

  <!-- CTA Band -->
  <section class="py-5">
    <div class="container">
      <div class="card-elev p-4 p-md-5 text-center">
        <h3 class="fw-bold mb-2">Table for tonight?</h3>
        <p class="muted mb-3">Family, couple, or window-side—book in a few taps.</p>
        <a href="/restaurant-app/frontend/pages/reservations.php" class="btn btn-danger">
          <i class="bi bi-calendar2-check me-1"></i> Book a Table
        </a>
      </div>
    </div>
  </section>

  <!-- REVIEWS (latest) -->
  <section class="py-5 bg-light">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="section-title mb-0">Latest Reviews</h2>
        <a href="/restaurant-app/frontend/pages/reviews.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-chat-dots me-1"></i> All Reviews
        </a>
      </div>

      <div id="reviewsAlert" class="alert d-none" role="alert"></div>

      <div id="reviewsGrid" class="row g-3">
        <!-- review cards -->
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card-elev p-4 h-100">
            <h4 class="fw-bold mb-2">Find us</h4>
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-geo-alt text-danger"></i>
              <div>Jabbar Tower, 7th Floor, Gulshan-1, Dhaka 1212</div>
            </div>
            <div class="d-flex align-items-start gap-2 mt-2">
              <i class="bi bi-telephone text-danger"></i>
              <div><a class="link-quiet" href="tel:01799437172">01799-437172</a></div>
            </div>
            <div class="d-flex align-items-start gap-2 mt-2">
              <i class="bi bi-clock text-danger"></i>
              <div>Daily: 11:30 AM – 10:30 PM</div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card-elev p-4 h-100">
            <h4 class="fw-bold mb-2">Questions?</h4>
            <p class="mb-3 muted">Call us for party bookings, corporate events, বা বড় গ্রুপের জন্য।</p>
            <a href="/restaurant-app/frontend/pages/reservations.php" class="btn btn-danger">
              <i class="bi bi-calendar2-plus me-1"></i> Make a Reservation
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <?php include __DIR__ . "/frontend/partials/footer.html"; ?>

  <script>
    // ---------------------------
    // Helpers
    // ---------------------------
    const el = s => document.querySelector(s);
    const elAll = s => [...document.querySelectorAll(s)];
    function showAlert(box, type, msg){ box.className = `alert alert-${type}`; box.textContent = msg; box.classList.remove('d-none'); }
    function hideAlert(box){ box.classList.add('d-none'); }
    const bd = v => `৳${Number(v||0).toFixed(0)}`;
    const star = filled => `<i class="bi bi-star${filled?'-fill':''} ${filled?'star':'star-muted'}"></i>`;
    const esc = s => (s||'').replace(/[&<>"']/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[t]));

    // ---------------------------
    // Reviews KPI
    // ---------------------------
    async function loadAvgRating(){
      try{
        const r = await fetch('/restaurant-app/backend/public/index.php?r=reviews&a=summary');
        const d = await r.json().catch(()=>({}));
        const avg = d?.global?.avg ?? 0;
        el('#kpiAvg').textContent = Number(avg).toFixed(1);
      }catch(_){
        el('#kpiAvg').textContent = '—';
      }
    }

    // ---------------------------
    // Menu: load + render
    // ---------------------------
    let MENU_CACHE = [];
    let FILTER_CAT = '';
    let SEARCH_Q = '';
    let CURSOR = 0;

    function uniqueCategories(items){
      const set = new Set();
      items.forEach(i => { if (i.category) set.add(i.category); });
      return [...set];
    }

    function renderTabs(cats){
      const box = el('#menuTabs');
      // keep the first "All" button
      // remove previous cat buttons
      elAll('#menuTabs [data-cat]:not([data-cat=""])').forEach(b => b.remove());

      cats.forEach(c=>{
        const b = document.createElement('button');
        b.className = 'btn btn-sm btn-outline-secondary';
        b.textContent = c;
        b.setAttribute('data-cat', c);
        b.addEventListener('click', ()=>{
          FILTER_CAT = c;
          CURSOR = 0;
          renderGrid(true);
          // active style
          elAll('#menuTabs .btn').forEach(_b => _b.classList.remove('btn-danger'));
          b.classList.add('btn-danger');
        });
        box.appendChild(b);
      });

      // All button behavior
      const allBtn = box.querySelector('[data-cat=""]');
      allBtn.addEventListener('click', ()=>{
        FILTER_CAT = '';
        CURSOR = 0;
        renderGrid(true);
        elAll('#menuTabs .btn').forEach(_b => _b.classList.remove('btn-danger'));
        allBtn.classList.add('btn-danger');
      });
      // set default active
      allBtn.classList.add('btn-danger');
    }

    function filteredMenu(){
      let arr = [...MENU_CACHE].filter(x => x.status === 'available');
      if (FILTER_CAT) arr = arr.filter(x => (x.category||'') === FILTER_CAT);
      if (SEARCH_Q){
        const q = SEARCH_Q.toLowerCase();
        arr = arr.filter(x => (x.name||'').toLowerCase().includes(q) || (x.description||'').toLowerCase().includes(q));
      }
      return arr;
    }

    function renderGrid(reset=false){
      const items = filteredMenu();
      const grid = el('#menuGrid');
      const meta = el('#menuMeta');
      const btnMore = el('#btnLoadMore');

      if (reset){ grid.innerHTML=''; CURSOR = 0; }

      const chunk = items.slice(CURSOR, CURSOR + 8);
      if (CURSOR === 0 && chunk.length === 0){
        grid.innerHTML = `<div class="col-12 text-center muted py-4">No items found.</div>`;
      } else {
        chunk.forEach(it=>{
          const img = it.image ? `/restaurant-app/frontend/assets/images/${it.image}` : '';
          const col = document.createElement('div');
          col.className = 'col-12 col-sm-6 col-lg-3';
          col.innerHTML = `
            <div class="menu-card">
              ${img ? `<img class="menu-img" src="${img}" alt="${esc(it.name)}">` : ``}
              <div class="menu-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="fw-semibold">${esc(it.name)}</div>
                  <div class="price">${bd(it.price)}</div>
                </div>
                <div class="small muted">${esc(it.description || '')}</div>
                ${it.category ? `<div class="mt-1"><span class="chip">${esc(it.category)}</span></div>` : ''}
                <div class="d-grid mt-2">
                  <a href="/restaurant-app/frontend/pages/order.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-bag me-1"></i> Order
                  </a>
                </div>
              </div>
            </div>
          `;
          grid.appendChild(col);
        });
      }

      CURSOR += chunk.length;
      btnMore.classList.toggle('d-none', CURSOR >= items.length);

      meta.textContent = `${items.length} item${items.length!==1?'s':''} found`;
    }

    async function loadMenu(){
      const alert = el('#menuAlert'); hideAlert(alert);
      try{
        const r = await fetch('/restaurant-app/backend/public/index.php?r=menu&a=list&status=available&limit=200');
        const d = await r.json().catch(()=>({}));
        if (!r.ok) throw new Error(d?.error || 'Failed to load menu');
        MENU_CACHE = d.items || [];
        renderTabs(uniqueCategories(MENU_CACHE));
        renderGrid(true);
      }catch(err){
        showAlert(alert, 'danger', err.message || 'Unable to load menu');
      }
    }

    // Search bind
    el('#btnMenuSearch').addEventListener('click', ()=>{
      SEARCH_Q = el('#menuSearch').value.trim();
      renderGrid(true);
    });
    el('#menuSearch').addEventListener('keydown', (e)=>{ if(e.key==='Enter'){ e.preventDefault(); el('#btnMenuSearch').click(); } });
    el('#btnLoadMore').addEventListener('click', ()=> renderGrid(false));

    // ---------------------------
    // Latest reviews
    // ---------------------------
    async function loadLatestReviews(){
      const alert = el('#reviewsAlert'); hideAlert(alert);
      const grid = el('#reviewsGrid');
      try{
        const r = await fetch('/restaurant-app/backend/public/index.php?r=reviews&a=list&limit=6');
        const d = await r.json().catch(()=>({}));
        if (!r.ok) throw new Error(d?.error || 'Failed to load reviews');

        const items = d.items || [];
        if (!items.length){
          grid.innerHTML = `<div class="col-12 text-center muted py-4">No reviews yet. Be the first!</div>`;
          return;
        }

        grid.innerHTML = '';
        items.forEach(rv=>{
          const col = document.createElement('div');
          col.className = 'col-12 col-md-6 col-lg-4';
          col.innerHTML = `
            <div class="card-elev p-3 h-100">
              <div class="d-flex justify-content-between align-items-center">
                <div class="fw-semibold">${esc(rv.user?.name || 'Anonymous')}</div>
                <div>${[1,2,3,4,5].map(i => star(i<=rv.rating)).join(' ')}</div>
              </div>
              <div class="small muted">${rv.item?.name ? 'On: '+esc(rv.item.name) : 'Restaurant'}</div>
              ${rv.comment ? `<div class="mt-2">${esc(rv.comment)}</div>` : ''}
              <div class="small muted mt-2">${new Date(rv.created_at).toLocaleString()}</div>
            </div>
          `;
          grid.appendChild(col);
        });
      }catch(err){
        showAlert(alert, 'danger', err.message || 'Unable to load reviews');
      }
    }

    // ---------------------------
    // Init
    // ---------------------------
    window.addEventListener('load', async ()=>{
      await Promise.all([
        loadAvgRating(),
        loadMenu(),
        loadLatestReviews()
      ]);
    });
  </script>
</body>
</html>
