function openDeleteModal(id) {
  deleteId = id;
  document.getElementById("deleteId").value = id;
  document.getElementById("deleteModal").style.display = "flex";
}

function closeDeleteModal() {
  document.getElementById("deleteModal").style.display = "none";
  deleteId = null;
}

function confirmLogout() {
  Swal.fire({
    title: "Are you sure?",
    text: "You will be logged out of your account.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Yes, log me out",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      {
        Swal.fire({
          title: "Logging out...",
          text: "Please wait",
          icon: "info",
          timer: 1500,
          showConfirmButton: false,
          willClose: () => {
            window.location.href = "login.php";
          }
        });
      }

    }
  });
}


function toggleBlogMenu(event, button) {

  event.preventDefault();
  event.stopPropagation();

  const dropdown = button.nextElementSibling;

  // close all dropdowns first
  document.querySelectorAll(".blog-dropdown").forEach(menu => {
    if (menu !== dropdown) {
      menu.classList.remove("active");
    }
  });

  dropdown.classList.toggle("active");
}

// close menu when clicking outside
document.addEventListener("click", () => {
  document.querySelectorAll(".blog-dropdown").forEach(menu => {
    menu.classList.remove("active");
  });
});


function toggleSidebar() { document.getElementById("sidebar").classList.toggle("active"); }



document.querySelectorAll(".like-btn").forEach(btn => {
  btn.addEventListener("click", function () {
    let postId = this.dataset.post;

    fetch("like.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: "post_id=" + postId
    })
      .then(res => res.json())
      .then(data => {
        document.getElementById("like-count-" + postId).innerText = data.count;

        // optional UI change
        if (data.status === "liked") {
          this.classList.add("liked");
        } else {
          this.classList.remove("liked");
        }
      });
  });
});



/* ============================================================
   BLOGAR SIDEBAR — interaction layer
   ============================================================ */
(() => {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebarOverlay");
  const openBtn = document.getElementById("openBtn");
  // const closeBtn  = document.getElementById("closeBtn");
  const collapseBtn = document.getElementById("collapseBtn");
  const logoutBtn = document.getElementById("logoutBtn");
  const navItems = document.querySelectorAll(".nav-item");
  const searchInput = document.querySelector(".search input");

  const MQ_DESKTOP = window.matchMedia("(min-width: 1025px)");

  /* -------- Mobile drawer -------- */
  const openSidebar = () => {
    sidebar.classList.add("show");
    overlay.classList.add("show");
    document.body.style.overflow = "hidden";
  };
  const closeSidebar = () => {
    sidebar.classList.remove("show");
    overlay.classList.remove("show");
    document.body.style.overflow = "";
  };
  openBtn?.addEventListener("click", openSidebar);
  closeBtn?.addEventListener("click", closeSidebar);
  overlay?.addEventListener("click", closeSidebar);

  /* close on escape */
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeSidebar();
    // ⌘K / Ctrl+K focuses search
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === "k") {
      e.preventDefault(); searchInput?.focus();
    }
  });

  /* -------- Desktop collapse (persisted) -------- */
  const COLLAPSE_KEY = "blogar:sidebar:collapsed";
  if (localStorage.getItem(COLLAPSE_KEY) === "1") sidebar.classList.add("collapsed");

  collapseBtn?.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
    localStorage.setItem(COLLAPSE_KEY, sidebar.classList.contains("collapsed") ? "1" : "0");
  });

  /* -------- Active item state -------- */
  navItems.forEach((item) => {
    item.addEventListener("click", (e) => {
      // demo only — prevent navigation for "#" links
      if (item.getAttribute("href") === "#") e.preventDefault();
      navItems.forEach((n) => n.classList.remove("active"));
      item.classList.add("active");
      if (!MQ_DESKTOP.matches) closeSidebar();
    });
  });

  /* -------- Reset state when resizing across breakpoint -------- */
  MQ_DESKTOP.addEventListener("change", (e) => {
    if (e.matches) {
      sidebar.classList.remove("show");
      overlay.classList.remove("show");
      document.body.style.overflow = "";
    }
  });

  /* -------- Live search filter -------- */
  searchInput?.addEventListener("input", (e) => {
    const q = e.target.value.trim().toLowerCase();
    navItems.forEach((item) => {
      const text = item.querySelector(".nav-text")?.textContent.toLowerCase() ?? "";
      item.style.display = !q || text.includes(q) ? "" : "none";
    });
  });
})();




/* ═══════════════════════════════════════════════════════════
   Blogar Dashboard — script.js
   • Scroll-reveal (AOS-lite)
   • Animated number counters
   • Animated donut (canvas, no library)
   ═══════════════════════════════════════════════════════════ */

/* ── Easing ───────────────────────────────────────────────── */
const easeOut3 = t => 1 - Math.pow(1 - t, 3);
const easeInOut = t => t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;

/* ── Scroll Reveal ────────────────────────────────────────── */
function initReveal() {
  const elements = document.querySelectorAll('[data-aos]');

  const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting) return;
      const el = entry.target;
      const delay = parseFloat(el.dataset.aosDelay || 0);
      setTimeout(() => {
        el.classList.add('aos-in');
        // kick counters inside this element
        el.querySelectorAll('[data-count]').forEach((num, i) => {
          animateCount(num, parseInt(num.dataset.count, 10), 900, i * 80);
        });
      }, delay);
      observer.unobserve(el);
    });
  }, { threshold: 0.12 });

  elements.forEach(el => observer.observe(el));
}

/* ── Number Counter ───────────────────────────────────────── */
function animateCount(el, target, duration = 900, delay = 0) {
  setTimeout(() => {
    const start = performance.now();
    const run = now => {
      const t = Math.min((now - start) / duration, 1);
      el.textContent = Math.round(easeOut3(t) * target);
      if (t < 1) requestAnimationFrame(run);
      else el.textContent = target;
    };
    requestAnimationFrame(run);
  }, delay);
}

/* ── Donut Chart ──────────────────────────────────────────── */
const DONUT_DATA = [
  { value: 45, color: '#d0d0d0' }, // Reader Distribution
  { value: 35, color: '#e53935' }, // Top Performing Article
  { value: 20, color: '#7b1f1f' }, // Reading Trends
];

function initDonut() {
  const canvas = document.getElementById('donutChart');
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  const dpr = window.devicePixelRatio || 1;
  const SIZE = 180;
  canvas.width = SIZE * dpr;
  canvas.height = SIZE * dpr;
  canvas.style.width = SIZE + 'px';
  canvas.style.height = SIZE + 'px';
  ctx.scale(dpr, dpr);

  const cx = SIZE / 2, cy = SIZE / 2;
  const outerR = SIZE / 2 - 10;
  const innerR = outerR * 0.60;
  const total = DONUT_DATA.reduce((s, d) => s + d.value, 0);
  const gap = 0.025; // radians gap between segments

  /* Animate progress 0 → 1 */
  let progress = 0;
  const duration = 1100;
  const startTime = performance.now();

  function draw(p) {
    ctx.clearRect(0, 0, SIZE, SIZE);

    let startAngle = -Math.PI / 2;

    DONUT_DATA.forEach(seg => {
      const slice = (seg.value / total) * Math.PI * 2 * p;
      const endAngle = startAngle + slice - (p > 0.02 ? gap : 0);

      if (slice <= 0) return;

      ctx.beginPath();
      ctx.arc(cx, cy, outerR, startAngle, endAngle);
      ctx.arc(cx, cy, innerR, endAngle, startAngle, true);
      ctx.closePath();
      ctx.fillStyle = seg.color;
      ctx.fill();

      startAngle = endAngle + (p > 0.02 ? gap : 0);
    });
  }

  /* Check if canvas is in viewport before starting */
  const observer = new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting) return;
    observer.disconnect();

    const t0 = performance.now();
    const tick = now => {
      const p = Math.min(easeInOut((now - t0) / duration), 1);
      draw(p);
      if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);

    /* also trigger the donut center counter */
    const numEl = document.querySelector('.donut-num');
    if (numEl) animateCount(numEl, parseInt(numEl.dataset.count, 10), 900, 300);

  }, { threshold: 0.2 });

  observer.observe(canvas);
}

/* ── Like Button Toggle ───────────────────────────────────── */
function initActions() {
  document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function () {
      this.classList.toggle('action-btn--active');
      // pulse
      this.animate([
        { transform: 'scale(1)' },
        { transform: 'scale(1.22)' },
        { transform: 'scale(1)' }
      ], { duration: 260, easing: 'ease-out' });
    });
  });
}

/* ── Init ─────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  initReveal();
  initDonut();
  initActions();
});