// ═══════════════════════════════════════════════
// GanjiSmart – Partner Experience Engine
// ═══════════════════════════════════════════════

document.addEventListener('DOMContentLoaded', () => {
    // ── Register Service Worker (PWA) ──
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js')
            .then(() => console.log('GanjiSmart: Brain Linked (PWA)'))
            .catch(err => console.error('Brain Link Failure:', err));
    }

    // ── Stat Animations ──
    document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseFloat(el.getAttribute('data-counter'));
        let count = 0;
        const inc = target / 50;
        const timer = setInterval(() => {
            count += inc;
            if (count >= target) {
                count = target;
                clearInterval(timer);
            }
            el.textContent = count.toLocaleString('en-US', { 
                style: 'currency', 
                currency: 'USD',
                maximumFractionDigits: 0 
            });
        }, 20);
    });

    // ── Mobile Menu Toggle ──
    const menuBtn = document.getElementById('menu-trigger');
    const sidebar = document.getElementById('sidebar');
    if (menuBtn && sidebar) {
        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
});

// ── Notification System ──
let notifTimer;
function showNotif(type, emoji, label, message) {
    const stack = document.getElementById('notif-stack');
    if (!stack) return;

    const id = 'notif-' + Date.now();
    const div = document.createElement('div');
    div.className = 'notif-popup';
    div.id = id;
    
    // type: success, warning, info, danger
    div.innerHTML = `
        <div class="notif-card ${type}">
            <div class="notif-head">
                <span class="notif-emoji">${emoji}</span>
                <span class="notif-label ${type}">${label}</span>
                <button class="notif-close" onclick="dismissNotif('${id}')">×</button>
            </div>
            <div class="notif-msg">${message}</div>
            <div class="notif-time">Just now · GanjiSmart</div>
        </div>
    `;
    stack.appendChild(div);
    setTimeout(() => dismissNotif(id), 6000);
}

function dismissNotif(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.style.animation = 'slideOutR 0.4s ease forwards';
    setTimeout(() => el.remove(), 400);
}

// ── Trade Actions ──
function recycleCapital(id) {
    const price = prompt('Enter the current exit price to recycle capital:');
    if (!price) return;
    
    fetch('api/trade_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=exit&id=${id}&exit_price=${price}`
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showNotif('success', '💰', 'Capital Recycled', 'Success Chief, capital is back in your pool.');
            setTimeout(() => location.reload(), 1500);
        }
    });
}
