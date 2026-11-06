/* =============================================================
   Blogar — Blog Details (client)
   Comments + threaded replies + likes + bookmarks + share
   ============================================================= */
(() => {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];

  /* ---------------- Toast ---------------- */
  const toastEl = $('#toast');
  let toastTimer;
  const toast = (msg) => {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.hidden = false;
    requestAnimationFrame(() => toastEl.classList.add('show'));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toastEl.classList.remove('show');
      setTimeout(() => (toastEl.hidden = true), 300);
    }, 2400);
  };

  /* ---------------- Reading progress ---------------- */
  const bar = $('#readingBar');
  if (bar) {
    const onScroll = () => {
      const h = document.documentElement;
      const pct = (h.scrollTop / (h.scrollHeight - h.clientHeight)) * 100;
      bar.style.width = Math.min(100, Math.max(0, pct)) + '%';
    };
    document.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  /* ---------------- Char counter ---------------- */
  const input = $('#commentInput');
  const cnt = $('#cfCount');
  if (input && cnt) {
    input.addEventListener('input', () => (cnt.textContent = input.value.length));
  }

  /* ---------------- Post URLs (adjust if your backend paths differ) ---------------- */
  const COMMENT_URL = 'comments.php';
  const LIKE_URL = 'likes.php';
  const BOOKMARK_URL = 'includes/bookmark.inc.php';

  /* ---------------- Helpers ---------------- */
  const esc = (s) => s.replace(/[&<>"']/g, (m) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  }[m]));

  const buildCommentEl = ({ id, firstname, lastname, file_name, comment, time }, isReply = false) => {
    const name = esc(`${firstname} ${lastname}`.trim() || 'You');
    const wrap = document.createElement('article');
    wrap.className = 'comment is-new';
    wrap.dataset.id = id;
    wrap.innerHTML = `
      <img class="c-avatar" src="includes/${esc(file_name || 'default.png')}" alt="">
      <div class="c-body">
        <header class="c-head">
          <strong>${name}</strong>
          <time>· ${esc(time || 'Just now')}</time>
        </header>
        <p class="c-text">${esc(comment).replace(/\n/g, '<br>')}</p>
        // <div class="c-actions">
        //   <button type="button" class="c-btn like-mini"><i class="ri-heart-line"></i> Like</button>
        //   <button type="button" class="c-btn reply-toggle"><i class="ri-reply-line"></i> Reply</button>
        // </div>
        // <form class="reply-form" hidden>
        //   <textarea placeholder="Write a reply…" maxlength="1000" required></textarea>
        //   <div class="rf-actions">
        //     <button type="button" class="btn-ghost cancel-reply">Cancel</button>
        //     <button type="submit" class="btn-primary"><i class="ri-send-plane-2-line"></i> Reply</button>
        //   </div>
        // </form>
      </div>`;
    return wrap;
  };

  const bumpCount = (delta = 1) => {
    ['commentsCount'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.textContent = (parseInt(el.textContent, 10) || 0) + delta;
    });
  };

  /* ---------------- Submit top-level comment ---------------- */
  const form = $('#commentForm');
  const list = $('#commentsList');
  const empty = $('#emptyComments');

  const postComment = async (payload) => {
    const res = await fetch(COMMENT_URL, { method: 'POST', body: payload });
    let data;
    try { data = await res.json(); } catch { throw new Error('Bad server response'); }
    if (!res.ok || data.error) throw new Error(data.error || 'Failed');
    return data;
  };

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[type=submit]');
      const text = input.value.trim();
      if (!text) return toast('Write something first');
      btn.disabled = true;
      try {
        const data = await postComment(new FormData(form));
        if (empty) empty.remove();
        const el = buildCommentEl(data);
        list.prepend(el);
        input.value = '';
        cnt.textContent = 0;
        bumpCount(1);
        toast('Comment posted');
      } catch (err) {
        toast(err.message || 'Something went wrong');
      } finally {
        btn.disabled = false;
      }
    });
  }

  /* ---------------- Delegated: reply toggle / submit / like-mini ---------------- */
  if (list) {
    list.addEventListener('click', (e) => {
      const toggle = e.target.closest('.reply-toggle');
      const cancel = e.target.closest('.cancel-reply');
      const likeMini = e.target.closest('.like-mini');

      if (toggle) {
        const rf = toggle.closest('.c-body').querySelector(':scope > .reply-form');
        if (!rf) return;
        rf.hidden = !rf.hidden;
        if (!rf.hidden) rf.querySelector('textarea').focus();
      }
      if (cancel) {
        const rf = cancel.closest('.reply-form');
        rf.hidden = true;
        rf.querySelector('textarea').value = '';
      }
      if (likeMini) {
        likeMini.classList.toggle('active');
        const i = likeMini.querySelector('i');
        i.classList.toggle('ri-heart-line');
        i.classList.toggle('ri-heart-fill');
      }
    });

    list.addEventListener('submit', async (e) => {
      const rf = e.target.closest('.reply-form');
      if (!rf) return;
      e.preventDefault();
      const parent = rf.closest('.comment');
      const parentId = parent?.dataset.id;
      const ta = rf.querySelector('textarea');
      const text = ta.value.trim();
      const btn = rf.querySelector('button[type=submit]');
      const postId = $('.comments-section')?.dataset.postId;
      if (!text || !parentId || !postId) return;

      btn.disabled = true;
      try {
        const fd = new FormData();
        fd.append('post_id', postId);
        fd.append('parent_id', parentId);
        fd.append('comment', text);
        const data = await postComment(fd);

        let holder = parent.querySelector(':scope > .c-body > .c-replies');
        if (!holder) {
          holder = document.createElement('div');
          holder.className = 'c-replies';
          parent.querySelector(':scope > .c-body').appendChild(holder);
        }
        holder.appendChild(buildCommentEl(data, true));

        ta.value = '';
        rf.hidden = true;
        bumpCount(1);
        toast('Reply posted');
      } catch (err) {
        toast(err.message || 'Could not post reply');
      } finally {
        btn.disabled = false;
      }
    });
  }

  /* ---------------- Like / Bookmark (post-level) ---------------- */
  const syncCount = (n) => {
    const a = $('#likeCount'), b = $('#likeCountMobile');
    if (a) a.textContent = n; if (b) b.textContent = n;
  };

  $$('.like-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const postId = btn.dataset.postId;
      if (!postId) return;
      // Optimistic
      const liked = btn.classList.toggle('liked');
      $$('.like-btn').forEach(b => b.classList.toggle('liked', liked));
      $$('.like-btn i').forEach(i => {
        i.classList.toggle('ri-heart-line', !liked);
        i.classList.toggle('ri-heart-fill', liked);
      });
      const cur = parseInt($('#likeCount')?.textContent || '0', 10);
      syncCount(cur + (liked ? 1 : -1));
      try {
        const fd = new FormData(); fd.append('post_id', postId);
        const r = await fetch(LIKE_URL, { method: 'POST', body: fd });
        const d = await r.json().catch(() => ({}));
        if (typeof d.total === 'number') syncCount(d.total);
      } catch { /* keep optimistic */ }
    });
  });

  $$('.bookmark-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const postId = btn.dataset.postId;
      const saved = btn.classList.toggle('saved');
      $$('.bookmark-btn').forEach(b => b.classList.toggle('saved', saved));
      $$('.bookmark-btn i').forEach(i => {
        i.classList.toggle('ri-bookmark-line', !saved);
        i.classList.toggle('ri-bookmark-fill', saved);
      });
      toast(saved ? 'Saved to bookmarks' : 'Removed from bookmarks');
      try {
        const fd = new FormData(); fd.append('post_id', postId);
        await fetch(BOOKMARK_URL, { method: 'POST', body: fd });
      } catch { }
    });
  });

  /* ---------------- Share sheet ---------------- */
  const sheet = $('#shareSheet');
  const openShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({ title: document.title, url: location.href });
        return;
      } catch { /* fall through to sheet */ }
    }
    if (sheet) sheet.hidden = false;
  };
  const closeShare = () => { if (sheet) sheet.hidden = true; };

  $$('[data-share]').forEach(b => b.addEventListener('click', openShare));
  $('#shareClose')?.addEventListener('click', closeShare);
  sheet?.addEventListener('click', (e) => { if (e.target === sheet) closeShare(); });

  $$('.share-opt[data-net]').forEach(a => {
    a.addEventListener('click', () => {
      const url = encodeURIComponent(location.href);
      const title = encodeURIComponent(document.title);
      const map = {
        twitter: `https://twitter.com/intent/tweet?url=${url}&text=${title}`,
        facebook: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
        linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
        whatsapp: `https://wa.me/?text=${title}%20${url}`,
      };
      const link = map[a.dataset.net];
      if (link) window.open(link, '_blank', 'noopener,width=640,height=560');
    });
  });

  $('#copyLink')?.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(location.href);
      toast('Link copied');
      closeShare();
    } catch { toast('Copy failed'); }
  });

  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeShare(); });
})();
