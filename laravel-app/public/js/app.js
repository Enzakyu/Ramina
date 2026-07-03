const RaminaHR = {
    // CSRF Token for all requests
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),

    // Notification system
    showNotification(message, type = 'success') {
        const alertHtml = `
            <div class="alert alert-${type} animate-slide-in" style="position:fixed; top:20px; right:20px; z-index:9999; box-shadow:0 10px 30px rgba(0,0,0,0.5);">
                ${message}
            </div>
        `;
        const div = document.createElement('div');
        div.innerHTML = alertHtml;
        document.body.appendChild(div.firstElementChild);
        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if(alert) {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }
        }, 3000);
    },

    // Format IDR Currency
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(amount);
    },

    // Format Date (e.g. 12 Oct 2026)
    formatDate(dateString) {
        if(!dateString) return '-';
        const d = new Date(dateString);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    },

    // Format Time (e.g. 09:30 AM)
    formatTime(dateString) {
        if(!dateString) return '-';
        const d = new Date(dateString);
        return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    },

    // Base AJAX Wrapper
    async ajax(url, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'X-CSRF-TOKEN': this.csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        if (data && method !== 'GET') {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Something went wrong');
            return result;
        } catch (error) {
            this.showNotification(error.message, 'danger');
            throw error;
        }
    },

    // Toggle Attendance (Check-in/out)
    async toggleAttendance(btn) {
        if(btn.disabled) return;
        btn.disabled = true;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<svg class="animate-spin" style="width:40px;height:40px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="4" stroke-dasharray="32" stroke-linecap="round"></circle></svg>';
        
        try {
            const res = await this.ajax('/attendance/toggle', 'POST');
            this.showNotification(res.message, 'success');
            setTimeout(() => window.location.reload(), 1000);
        } catch(e) {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    },

    // Clock
    initClock() {
        const el = document.getElementById('live-clock');
        if(!el) return;
        setInterval(() => {
            el.innerText = new Date().toLocaleTimeString('en-US', { hour12: false });
        }, 1000);
    },

    // Approve Leave
    async approveLeave(id) {
        if(!confirm('Approve this leave request?')) return;
        try {
            await this.ajax(`/admin/leave-approval/${id}/approve`, 'POST');
            this.showNotification('Leave approved successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } catch(e) {}
    },

    // Reject Leave
    async rejectLeave(id) {
        const reason = prompt('Reason for rejection:');
        if(reason === null) return;
        try {
            await this.ajax(`/admin/leave-approval/${id}/reject`, 'POST', { reason });
            this.showNotification('Leave rejected', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } catch(e) {}
    }
};

document.addEventListener('DOMContentLoaded', () => {
    RaminaHR.initClock();

    // Mobile Sidebar Toggle
    const toggleBtn = document.getElementById('mobile-toggle');
    if(toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('open');
        });
    }

    // Modal Handling
    document.querySelectorAll('[data-toggle="modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const target = document.querySelector(e.currentTarget.dataset.target);
            if(target) target.classList.add('show');
        });
    });
    
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.currentTarget.closest('.modal-backdrop').classList.remove('show');
        });
    });
});
