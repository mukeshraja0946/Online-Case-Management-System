document.addEventListener('DOMContentLoaded', function () {
    const bellBtn = document.querySelector('.nav-icon-btn i.fa-bell')?.parentElement;
    if (!bellBtn) return;

    // Create container for bell and dropdown
    const wrapper = document.createElement('div');
    wrapper.className = 'notif-wrapper';
    bellBtn.parentNode.insertBefore(wrapper, bellBtn);
    wrapper.appendChild(bellBtn);

    // Create dropdown
    const dropdown = document.createElement('div');
    dropdown.className = 'notif-dropdown';
    dropdown.innerHTML = `
        <div class="notif-header">
            <span>Notifications</span>
            <a href="#" id="mark-all-read" style="font-size: 0.75rem; color: var(--primary-color);">Mark all as read</a>
        </div>
        <div class="notif-list">
            <div class="notif-empty">Loading...</div>
        </div>
    `;
    wrapper.appendChild(dropdown);

    const badge = document.createElement('div');
    badge.className = 'notification-badge';
    badge.style.display = 'none';
    bellBtn.appendChild(badge);

    // Toggle dropdown
    bellBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isVisible = dropdown.style.display === 'block';
        dropdown.style.display = isVisible ? 'none' : 'block';
        if (!isVisible) {
            fetchNotifications();
        }
    });

    document.addEventListener('click', function () {
        dropdown.style.display = 'none';
    });

    dropdown.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    function fetchNotifications() {
        fetch('../auth/fetch_notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data.error) return;

                updateBadge(data.unread_count);
                renderList(data.notifications);
            });
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.style.display = 'flex';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderList(notifications) {
        const list = dropdown.querySelector('.notif-list');
        if (notifications.length === 0) {
            list.innerHTML = '<div class="notif-empty">No notifications yet</div>';
            return;
        }

        list.innerHTML = '';
        notifications.forEach(n => {
            const item = document.createElement('div');
            item.className = `notif-item ${!n.is_read ? 'unread' : ''}`;
            item.innerHTML = `
                <div class="notif-msg">${n.message}</div>
                <div class="notif-time">${n.time}</div>
            `;
            item.addEventListener('click', () => markAsRead(n.id));
            list.appendChild(item);
        });
    }

    function markAsRead(id) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        if (id) formData.append('id', id);

        fetch('../auth/fetch_notifications.php', {
            method: 'POST',
            body: formData
        }).then(() => fetchNotifications());
    }

    document.getElementById('mark-all-read').addEventListener('click', function (e) {
        e.preventDefault();
        markAsRead(null);
    });

    // Initial fetch for badge
    fetchNotifications();
});
