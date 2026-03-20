/* frontend/assets/js/app.js */
'use strict';

/* ── APP BASE (matches APP_URL in init.php) ─────────────────────── */
const APP_BASE = '/sprintdesk';

/* ── THEME ──────────────────────────────────────────────────────── */
const Theme = {
    current: () => document.documentElement.getAttribute('data-theme') || 'light',
    apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        localStorage.setItem('sd_theme', t);
        fetch(APP_BASE + '/backend/api/set_theme.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ theme: t })
        }).catch(() => { });
    },
    toggle() { this.apply(this.current() === 'dark' ? 'light' : 'dark'); },
    init() {
        const saved = localStorage.getItem('sd_theme');
        if (saved) document.documentElement.setAttribute('data-theme', saved);
        document.querySelectorAll('.tf-theme-tog, .tf-theme-tog-btn').forEach(b =>
            b.addEventListener('click', () => Theme.toggle())
        );
    }
};

/* ── PAGE TRANSITIONS ────────────────────────────────────────────── */
const Curtain = {
    el: null,
    init() {
        this.el = document.getElementById('tf-curtain');
        if (!this.el) return;
        // intercept internal links
        document.body.addEventListener('click', e => {
            const a = e.target.closest('a[href]');
            if (!a) return;
            const h = a.getAttribute('href');
            if (!h || h.startsWith('#') || h.startsWith('javascript') || h.startsWith('mailto') || a.target === '_blank') return;
            e.preventDefault();
            this.el.classList.add('rising');
            setTimeout(() => { window.location.href = h; }, 380);
        });
        // fade in on load
        this.el.classList.add('falling');
        setTimeout(() => this.el.classList.remove('falling'), 10);
    }
};

/* ── TOAST ───────────────────────────────────────────────────────── */
const Toast = {
    box: null,
    init() {
        this.box = document.getElementById('tf-toasts');
        if (!this.box) {
            this.box = document.createElement('div');
            this.box.id = 'tf-toasts';
            document.body.appendChild(this.box);
        }
    },
    show(msg, type = 'ok', ms = 3400) {
        if (!this.box) this.init();
        const icons = { ok: '✓', err: '✕', info: 'ℹ' };
        const t = document.createElement('div');
        t.className = `tf-toast ${type}`;
        t.innerHTML = `<span>${icons[type] || '•'}</span><span>${msg}</span>`;
        this.box.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateX(20px)'; t.style.transition = 'all .25s'; setTimeout(() => t.remove(), 260); }, ms);
    }
};

/* ── MODAL ───────────────────────────────────────────────────────── */
const Modal = {
    open(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; }
    },
    close(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.remove('open'); document.body.style.overflow = ''; }
    }
};
document.addEventListener('click', e => {
    if (e.target.classList.contains('tf-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
    if (e.target.classList.contains('tf-modal-close')) {
        const ov = e.target.closest('.tf-overlay');
        if (ov) { ov.classList.remove('open'); document.body.style.overflow = ''; }
    }
});

/* ── KANBAN DRAG & DROP ──────────────────────────────────────────── */
const Kanban = {
    dragging: null,
    placeholder: null,
    init() {
        this.placeholder = document.createElement('div');
        this.placeholder.className = 'tf-kanban-placeholder';
        this.bindEvents();
    },
    bindEvents() {

        document.querySelectorAll('.tf-task-card').forEach(card => {
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', e => {
                this.dragging = card;
                card.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.dataset.id);
                
                // Set placeholder height same as card
                this.placeholder.style.height = card.offsetHeight + 'px';
                this.placeholder.style.marginBottom = window.getComputedStyle(card).marginBottom;
                
                // delay hiding original card
                setTimeout(() => card.style.display = 'none', 0);
            });

            card.addEventListener('dragend', () => {
                this.dragging.style.display = '';
                this.dragging.classList.remove('dragging');
                if (this.placeholder.parentElement) this.placeholder.remove();
                this.dragging = null;
            });
        });

        document.querySelectorAll('.tf-kcol').forEach(col => {
            col.addEventListener('dragover', e => {
                e.preventDefault();
                col.classList.add('drag-over');

                const list = col.querySelector('.tf-kcol-list');
                const afterElement = this.getDragAfterElement(list, e.clientY);
                if (afterElement == null) {
                    list.appendChild(this.placeholder);
                } else {
                    list.insertBefore(this.placeholder, afterElement);
                }
            });

            col.addEventListener('dragleave', e => {
                if (!col.contains(e.relatedTarget)) col.classList.remove('drag-over');
            });

            col.addEventListener('drop', e => {
                e.preventDefault();
                col.classList.remove('drag-over');
                if (!this.dragging) return;

                const status = col.dataset.status;
                const taskId = this.dragging.dataset.id;
                
                // Actual move
                this.placeholder.replaceWith(this.dragging);
                this.dragging.style.display = '';
                
                this.updateAllCounts();
                this.saveStatus(taskId, status);
            });
        });
    },
    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('.tf-task-card:not(.dragging)')];
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    },
    updateAllCounts() {
        document.querySelectorAll('.tf-kcol').forEach(col => {
            const cnt = col.querySelectorAll('.tf-task-card').length;
            const b = col.querySelector('.tf-kcol-cnt');
            if (b) b.textContent = cnt;
        });
    },
    saveStatus(id, status) {
        // Collect new positions in that column
        const col = document.querySelector(`.tf-kcol[data-status="${status}"]`);
        const positions = [...col.querySelectorAll('.tf-task-card')].map(c => c.dataset.id);

        fetch(APP_BASE + '/backend/api/update_task.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, status, positions })
        })
        .then(r => r.json())
        .then(d => { 
            if (d.ok) Toast.show('Kanban updated successfully'); 
            else Toast.show('Failed to sync changes', 'err'); 
        })
        .catch(() => Toast.show('Connection error', 'err'));
    }
};

/* ── SCROLL REVEAL ───────────────────────────────────────────────── */
const Reveal = {
    init() {
        const obs = new IntersectionObserver(entries => {
            entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('revealed'); obs.unobserve(e.target); } });
        }, { threshold: 0.1 });
        document.querySelectorAll('[data-reveal]').forEach(el => {
            el.style.cssText = 'opacity:0;transform:translateY(22px);transition:opacity .6s cubic-bezier(.4,0,.2,1),transform .6s cubic-bezier(.4,0,.2,1)';
            obs.observe(el);
        });
        document.querySelectorAll('.revealed').forEach(el => {
            el.style.opacity = '1'; el.style.transform = 'translateY(0)';
        });
    }
};
document.addEventListener('animationend', e => {
    if (e.target.style.cssText.includes('opacity:0')) { e.target.style.opacity = '1'; e.target.style.transform = 'translateY(0)'; }
});
const _origObserve = IntersectionObserver.prototype.observe;

/* ── CONFIRM DELETE ──────────────────────────────────────────────── */
function confirmDelete(msg, onConfirm) {
    if (confirm(msg || 'Are you sure?')) onConfirm();
}

/* ── CELEBRATION (CONFETTI) ───────────────────────────────────────── */
const Celebration = {
    trigger() {
        if (typeof confetti !== 'function') return;
        const duration = 2 * 1000;
        const animationEnd = Date.now() + duration;
        const defaults = { startVelocity: 35, spread: 360, ticks: 100, zIndex: 9999, scalar: 1.2 };

        const randomInRange = (min, max) => Math.random() * (max - min) + min;

        const interval = setInterval(function () {
            const timeLeft = animationEnd - Date.now();
            if (timeLeft <= 0) return clearInterval(interval);
            const particleCount = 60 * (timeLeft / duration);
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.1, 0.4), y: Math.random() - 0.2 } });
            confetti({ ...defaults, particleCount, origin: { x: randomInRange(0.6, 0.9), y: Math.random() - 0.2 } });
        }, 200);
    }
};

/* ── SEARCH & NOTIFICATIONS ──────────────────────────────────────── */
const App = {
    searchTimeout: null,
    bindScroll() {
        const line = document.getElementById('tf-scroll-line');
        if (!line) return;
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            line.style.width = scrolled + "%";
        });
    },
    bindMobile() {
        const toggle = document.getElementById('tf-mobile-toggle');
        const sidebar = document.getElementById('tf-sidebar');
        if (!toggle || !sidebar) return;
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    },
    celebrateLogin() {
        if (new URLSearchParams(window.location.search).get('login') === '1') {
            Celebration.trigger();
            // Clean URL
            const url = new URL(window.location);
            url.searchParams.delete('login');
            window.history.replaceState({}, document.title, url);
        }
    },
    init() {
        this.bindSearch();
        this.bindLocalSearch();
        this.bindNotifs();
        this.bindScroll();
        this.bindMobile();
        this.bindDesktopToggle();
        this.celebrateLogin();
        // Initial fetch
        this.fetchNotifs();
        // Poll every 3s for ultra real-time notifications
        setInterval(() => this.fetchNotifs(), 3000);
    },
    bindSearch() {
        const inp = document.getElementById('globalSearchInp');
        const res = document.getElementById('tf-search-results');
        if (!inp || !res) return;

        inp.addEventListener('input', () => {
            clearTimeout(this.searchTimeout);
            const q = inp.value.trim();
            if (q.length < 2) { res.style.display = 'none'; return; }

            this.searchTimeout = setTimeout(() => {
                fetch(`${APP_BASE}/backend/api/search.php?q=${encodeURIComponent(q)}`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d.ok || !d.results.length) {
                            res.innerHTML = '<div style="padding:14px;text-align:center;font-size:12px;color:var(--text3)">No results found.</div>';
                        } else {
                            const icons = { project: '📁', task: '📋', user: '👤' };
                            res.innerHTML = d.results.map(i => `
                                <div class="tf-search-item" onclick="window.location.href='${APP_BASE}/backend/${currentUserRole()}/pages/${i.type}s.php?id=${i.id}'">
                                    <div class="tf-si-icon">${icons[i.type] || '•'}</div>
                                    <div class="tf-si-info">
                                        <div class="tf-si-title">${this.escape(i.title)}</div>
                                        <div class="tf-si-sub">${i.type.toUpperCase()} • ${this.escape(i.subtitle)}</div>
                                    </div>
                                </div>
                            `).join('');
                        }
                        res.style.display = 'block';
                    });
            }, 300);
        });

        // Close search on click outside
        document.addEventListener('click', e => {
            if (!inp.contains(e.target) && !res.contains(e.target)) res.style.display = 'none';
        });
    },
    bindLocalSearch() {
        const inputs = document.querySelectorAll('.tf-live-search, #searchInp');
        inputs.forEach(inp => {
            inp.addEventListener('input', () => {
                const q = inp.value.toLowerCase();
                const target = inp.dataset.target;
                if (!target) return;
                document.querySelectorAll(target).forEach(item => {
                    item.style.display = item.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        });
    },
    bindNotifs() {
        const btn = document.getElementById('tf-notif-btn');
        const drp = document.getElementById('tf-notif-dropdown');
        if (!btn || !drp) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isVisible = drp.style.display === 'block';
            drp.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) this.fetchNotifs();
        });

        document.addEventListener('click', () => drp.style.display = 'none');
        drp.addEventListener('click', e => e.stopPropagation());
    },
    fetchNotifs() {
        fetch(`${APP_BASE}/backend/api/notifications.php`)
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    if (d.notifs && d.notifs.length > 0) {
                        const topId = d.notifs[0].id;
                        const lastId = localStorage.getItem('tf_last_notif_id');
                        if (lastId && topId > lastId) {
                            const newNotifs = d.notifs.filter(n => n.id > lastId);
                            newNotifs.slice(0, 3).forEach(n => Toast.show('🔔 <strong>' + this.escape(n.title) + '</strong><br>' + this.escape(n.message), 'info', 6000));
                        }
                        localStorage.setItem('tf_last_notif_id', topId);
                    }
                    const b = document.getElementById('tf-notif-badge');
                    if (b) {
                        b.innerText = d.unread > 9 ? '9+' : d.unread;
                        b.style.display = d.unread > 0 ? 'flex' : 'none';
                    }
                    const list = document.getElementById('tf-notif-list');
                    if (list) {
                        if (!d.notifs.length) {
                            list.innerHTML = '<div style="padding:30px;text-align:center;color:var(--text3);font-size:12px">No new notifications.</div>';
                        } else {
                            list.innerHTML = d.notifs.map(n => `
                                <div class="tf-notif-item ${n.is_read ? '' : 'tf-notif-unread'}" onclick="App.markRead(${n.id}, '${n.link}')">
                                    <div class="tf-ni-txt"><strong>${this.escape(n.title)}</strong><br>${this.escape(n.message)}</div>
                                    <div class="tf-ni-time">${n.created_at}</div>
                                </div>
                            `).join('');
                        }
                    }
                }
            });
    },
    markRead(id, link) {
        fetch(`${APP_BASE}/backend/api/notifications.php`, {
            method: 'POST',
            body: JSON.stringify({ id })
        }).then(() => { if (link) window.location.href = link; else this.fetchNotifs(); });
    },
    escape(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    },
    bindDesktopToggle() {
        const dToggle = document.getElementById('tf-desktop-toggle');
        if (!dToggle) return;
        dToggle.addEventListener('click', () => {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sd_sb_collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
        if (localStorage.getItem('sd_sb_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }
    }
};

function currentUserRole() {
    // Helper to get role from body attribute or global var
    return document.body.dataset.role || 'developer';
}

/* ── INIT ────────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    Theme.init();
    Curtain.init();
    Toast.init();
    App.init();
    if (document.querySelector('.tf-kanban')) Kanban.init();

    // Check for "celebrate" in URL to trigger celebration
    if (window.location.search.includes('celebrate=1')) {
        setTimeout(() => Celebration.trigger(), 500);
    }
});
