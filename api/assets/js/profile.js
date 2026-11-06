/**
 * profile.js — Blogar Profile Page
 * Handles: tabs, follow/unfollow, message modal, lightbox,
 *          people toggle, post menu, delete confirm, share/copy, likes
 */
(function () {
  'use strict';

  /* ── Tabs ─────────────────────────────────────────────── */
  const tabBtns   = document.querySelectorAll('.tab-btn');
  const tabPanels = document.querySelectorAll('.tab-panel');

  function activateTab(name) {
    tabBtns.forEach(b => b.classList.toggle('active', b.dataset.tab === name));
    tabPanels.forEach(p => {
      const on = p.id === 'tab-' + name;
      p.classList.toggle('active', on);
      p.hidden = !on;
    });
    try { history.replaceState(null, '', '#' + name); } catch (_) {}
  }
  tabBtns.forEach(b => b.addEventListener('click', () => activateTab(b.dataset.tab)));

  const initial = (location.hash || '').replace('#', '');
  if (initial && document.getElementById('tab-' + initial)) activateTab(initial);

  /* ── Follow / Unfollow ────────────────────────────────── */
  window.toggleFollow = function (btn) {
    if (typeof LOGGED_IN !== 'undefined' && !LOGGED_IN) {
      window.location.href = 'login.php';
      return;
    }
    const following = btn.classList.contains('action-following');

    fetch('follow.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'user_id=' + encodeURIComponent(PROFILE_ID),
    })
      .then(r => r.json().catch(() => ({ success: true }))) // fallback if server returns no JSON
      .then(data => {
        if (data && data.success === false) return;
        const countEl = document.getElementById('followerCount');
        let count = parseInt((countEl.textContent || '0').replace(/[^\d]/g, ''), 10) || 0;
        if (following) {
          btn.classList.replace('action-following', 'action-follow');
          btn.innerHTML = '<i class="ri-user-add-line"></i><span>Follow</span>';
          countEl.textContent = formatNum(Math.max(0, count - 1));
        } else {
          btn.classList.replace('action-follow', 'action-following');
          btn.innerHTML = '<i class="ri-user-follow-fill"></i><span>Following</span>';
          countEl.textContent = formatNum(count + 1);
        }
      })
      .catch(console.error);
  };

  function formatNum(n) {
    if (n >= 1e6) return (n / 1e6).toFixed(1) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return n.toLocaleString();
  }

  /* ── Message modal ────────────────────────────────────── */
  window.openMsg = function () {
    document.getElementById('msgOverlay').classList.add('show');
    document.getElementById('msgModal').classList.add('show');
    document.body.style.overflow = 'hidden';
  };
  window.closeMsg = function () {
    document.getElementById('msgOverlay').classList.remove('show');
    document.getElementById('msgModal').classList.remove('show');
    document.body.style.overflow = '';
  };

  /* ── Lightbox ─────────────────────────────────────────── */
  window.openLightbox = function (src) {
    const lb = document.getElementById('lightbox');
    document.getElementById('lbImg').src = src;
    lb.classList.add('show');
    document.body.style.overflow = 'hidden';
  };
  window.closeLightbox = function () {
    document.getElementById('lightbox').classList.remove('show');
    document.body.style.overflow = '';
  };

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { closeMsg(); closeLightbox(); }
  });

  /* ── See more / people toggle ─────────────────────────── */
  window.togglePeople = function () {
    const btn    = document.getElementById('seeMoreBtn');
    const hidden = document.querySelectorAll('.hidden-person');
    const open   = btn.classList.toggle('open');
    hidden.forEach(el => el.classList.toggle('show', open));
    btn.childNodes[0].textContent = open ? 'See less ' : 'See more ';
  };

  /* ── Post dropdown menu ───────────────────────────────── */
  window.toggleMenu = function (e, btn) {
    e.stopPropagation();
    const dropdown = btn.nextElementSibling;
    document.querySelectorAll('.post-dropdown.open').forEach(d => {
      if (d !== dropdown) d.classList.remove('open');
    });
    dropdown.classList.toggle('open');
  };
  document.addEventListener('click', () => {
    document.querySelectorAll('.post-dropdown.open').forEach(d => d.classList.remove('open'));
  });

  /* ── Delete confirm ───────────────────────────────────── */
  window.confirmDelete = function (postId) {
    if (typeof Swal !== 'undefined') {
      Swal.fire({
        icon: 'warning',
        title: 'Delete this post?',
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#eb2020',
        cancelButtonColor: '#6b7280',
      }).then(r => { if (r.isConfirmed) deletePost(postId); });
    } else if (confirm('Delete this post? This cannot be undone.')) {
      deletePost(postId);
    }
  };

  function deletePost(postId) {
    fetch('includes/delete_post.inc.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'post_id=' + encodeURIComponent(postId),
    })
      .then(r => r.json())
      .then(data => {
        if (!data || !data.success) return;
        const card = document.querySelector(`.post-card[data-id="${postId}"]`);
        if (!card) return;
        card.style.transition = 'opacity .3s, transform .3s';
        card.style.opacity = '0';
        card.style.transform = 'translateX(-10px)';
        setTimeout(() => card.remove(), 320);
        showToast('Post deleted');
      })
      .catch(console.error);
  }

  /* ── Share / copy link ────────────────────────────────── */
  window.sharePost = function () {
    const url   = window.location.href;
    const title = document.title;
    if (navigator.share) {
      navigator.share({ title, url }).catch(() => copy(url));
    } else copy(url);
  };

  function copy(text) {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(text).then(() => showToast('Link copied!')).catch(fallbackCopy);
    } else fallbackCopy();
    function fallbackCopy() {
      const ta = document.createElement('textarea');
      ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
      document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); showToast('Link copied!'); } catch (_) {}
      document.body.removeChild(ta);
    }
  }

  function showToast(msg) {
    const t = document.getElementById('copyToast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(showToast._id);
    showToast._id = setTimeout(() => t.classList.remove('show'), 2800);
  }
  window.showToast = showToast;

  /* ── Like handler ─────────────────────────────────────── */
  window.handleLike = function (btn) {
    if (typeof LOGGED_IN !== 'undefined' && !LOGGED_IN) { window.location.href = 'login.php'; return; }
    const postId  = btn.querySelector('.post-id').value;
    const icon    = btn.querySelector('i');
    const countEl = btn.querySelector('.like-count');
    const liked   = btn.classList.contains('liked');
    const url     = liked ? 'likes.php' : 'likes.php';

    btn.classList.add('pop');
    setTimeout(() => btn.classList.remove('pop'), 260);

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'post_id=' + encodeURIComponent(postId),
    })
      .then(r => r.json().catch(() => ({ success: true })))
      .then(data => {
        if (data && data.success === false) return;
        const cur = parseInt(countEl.textContent, 10) || 0;
        if (liked) {
          btn.classList.remove('liked');
          icon.className = 'ri-heart-line';
          countEl.textContent = Math.max(0, cur - 1);
        } else {
          btn.classList.add('liked');
          icon.className = 'ri-heart-fill';
          countEl.textContent = cur + 1;
        }
      })
      .catch(console.error);
  };
})();
