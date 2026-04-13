(function() {
    // Only run if not on public/auth pages
    const path = window.location.pathname.toLowerCase();
    if (path.includes('login.php') || path.includes('logout.php') || path.includes('google_login.php') || path.includes('index.php')) {
        return;
    }

    let timeoutMinutes = 30;
    let warningThreshold = 2 * 60; // 2 minutes before
    let checkIntervalTime = 60 * 1000; // Check every minute
    let lastActivityTime = Date.now();
    let isWarningShown = false;
    let warningTimer = null;

    // Track local activity
    const updateActivity = () => {
        lastActivityTime = Date.now();
    };
    
    // Throttle events to limit execution frequency
    const throttle = (func, limit) => {
        let inThrottle;
        return function() {
            const args = arguments, context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        }
    }

    window.addEventListener('mousemove', throttle(updateActivity, 1000));
    window.addEventListener('keypress', throttle(updateActivity, 1000));
    window.addEventListener('click', throttle(updateActivity, 1000));
    window.addEventListener('scroll', throttle(updateActivity, 1000));

    // Determine config path dynamically 
    // Usually we are in `/student/`, `/admin/`, `/staff/` which are 1 level deep.
    // If we're deeper, we can infer by counting deeper directories, but a simpler generic path is relative to the root.
    // We find `OCMS/` or similar prefix
    let basePath = '';
    let match = window.location.pathname.match(/(.*\/)(admin|student|staff|auth)\//i);
    if (match) {
        basePath = match[1];
    } else {
        basePath = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    }
    
    // Safety check just in case basePath doesn't include the folder right
    // If not matching standard OCMS routes, use relative up one level (standard OCMS structure)
    let endpoint = (match ? basePath : '../') + 'config/session_heartbeat.php';

    // Inject CSS for Modal
    const style = document.createElement('style');
    style.innerHTML = `
        .session-modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);
            z-index: 99999; display: flex; align-items: center; justify-content: center;
        }
        .session-modal {
            background: white; padding: 30px; border-radius: 15px; width: 400px;
            text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            font-family: 'Outfit', sans-serif;
            border: 1px solid #e2e8f0;
        }
        .session-modal-icon {
            font-size: 3rem; color: #f59e0b; margin-bottom: 15px;
        }
        .session-modal h3 { margin-top: 0; font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
        .session-modal p { color: #64748b; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.5; }
        .session-modal #session-countdown { font-weight: 800; color: #ef4444; font-size: 1.1rem; }
        .session-btn {
            background: #2563eb; color: white; border: none; padding: 12px 24px; width: 100%;
            border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 1rem;
        }
        .session-btn:hover { background: #1d4ed8; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
    `;
    document.head.appendChild(style);

    const checkSession = () => {
        const formData = new FormData();
        const timeSinceLocalAction = Date.now() - lastActivityTime;
        
        // If user was active locally in the last check interval, send a heartbeat
        if (timeSinceLocalAction < checkIntervalTime * 1.5 && !isWarningShown) {
            formData.append('heartbeat', '1');
        }

        fetch(endpoint, { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                let logoutUrl = (match ? basePath : '../') + 'auth/logout.php';
                if (data.status === 'expired' || data.status === 'logged_out') {
                    window.location.href = logoutUrl;
                    return;
                }
                
                if (data.status === 'active') {
                    let totalSeconds = data.timeout_seconds;
                    let remaining = data.remaining_seconds;

                    // Automatically compute warning threshold based on total timeout limit
                    // Cap at 120s (2 mins), floor at 5s, typical is 1/4th of the timeout duration.
                    warningThreshold = Math.min(120, Math.max(5, Math.floor(totalSeconds / 4)));
                    
                    if (remaining <= warningThreshold && remaining > 0) {
                        showWarningModal(remaining, logoutUrl);
                    } else if (remaining <= 0) {
                        window.location.href = logoutUrl;
                    } else if (isWarningShown) {
                        hideWarningModal();
                    }
                }
            }).catch(console.error);
    };

    let modalEl = null;

    const showWarningModal = (remaining, logoutUrl) => {
        if (isWarningShown) return;
        isWarningShown = true;
        
        modalEl = document.createElement('div');
        modalEl.className = 'session-modal-overlay';
        modalEl.innerHTML = `
            <div class="session-modal">
                <div class="session-modal-icon">⏳</div>
                <h3>Session Timeout</h3>
                <p>You will be logged out due to inactivity in <br><span id="session-countdown">${remaining}</span> seconds.<br><br>Click continue to stay logged in.</p>
                <button class="session-btn" id="btn-session-continue">Continue</button>
            </div>
        `;
        document.body.appendChild(modalEl);

        document.getElementById('btn-session-continue').addEventListener('click', () => {
            hideWarningModal();
            updateActivity();
            const formData = new FormData();
            formData.append('heartbeat', '1');
            fetch(endpoint, { method: 'POST', body: formData }).then(checkSession);
        });

        // Countdown timer
        let r = remaining;
        warningTimer = setInterval(() => {
            r--;
            let countdownEl = document.getElementById('session-countdown');
            if(countdownEl) countdownEl.innerText = r;
            if(r <= 0) {
                window.location.href = logoutUrl;
            }
        }, 1000);
    };

    const hideWarningModal = () => {
        isWarningShown = false;
        if (modalEl) modalEl.remove();
        if (warningTimer) clearInterval(warningTimer);
    };

    // Initial check and start interval
    checkSession();
    setInterval(checkSession, checkIntervalTime);
})();
