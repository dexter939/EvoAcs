/**
 * ACS Dashboard Real-time Updates
 * Auto-refresh statistics and charts every 30 seconds
 */

class DashboardRealtime {
    constructor() {
        this.refreshInterval = 30000; // 30 seconds
        this.charts = {};
        this.statsEndpoint = '/acs/dashboard/stats-api';
        this.init();
    }

    init() {
        this.setupCharts();
        this.startAutoRefresh();
        this.setupNotifications();
        console.log('✅ Dashboard Real-time initialized');
    }

    setupCharts() {
        // Get chart instances created in blade template
        this.charts.devices = Chart.getChart('devicesChart');
        this.charts.tasks = Chart.getChart('tasksChart');
        this.charts.diagnostics = Chart.getChart('diagnosticsChart');
        this.charts.firmware = Chart.getChart('firmwareChart');
    }

    async fetchStats() {
        try {
            const response = await fetch(this.statsEndpoint);
            if (!response.ok) throw new Error('Failed to fetch stats');
            return await response.json();
        } catch (error) {
            console.error('Error fetching stats:', error);
            this.showNotification('Errore aggiornamento dati', 'danger');
            return null;
        }
    }

    async refreshDashboard() {
        const stats = await this.fetchStats();
        if (!stats) return;

        this.updateStatCards(stats);
        this.updateCharts(stats);
        this.updateRecentDevices(stats.recent_devices);
        this.updateRecentTasks(stats.recent_tasks);
        
        // Update last refresh indicator
        this.updateLastRefreshTime();
    }

    updateStatCards(stats) {
        // Dispositivi Online
        this.animateNumber('.stat-devices-online', stats.devices.online);
        this.updateElement('.stat-devices-total', stats.devices.total);
        
        // Tasks Pending
        this.animateNumber('.stat-tasks-pending', stats.tasks.pending);
        
        // Firmware Deployments
        this.animateNumber('.stat-firmware-total', stats.firmware.total_deployments);
        
        // Tasks Completed
        this.animateNumber('.stat-tasks-completed', stats.tasks.completed);
        const completionRate = stats.tasks.total > 0 
            ? Math.round((stats.tasks.completed / stats.tasks.total) * 100) 
            : 0;
        this.updateElement('.stat-tasks-completion', completionRate + '%');
        
        // Other stats
        this.animateNumber('.stat-diagnostics-completed', stats.diagnostics.completed);
        this.updateElement('.stat-diagnostics-total', stats.diagnostics.total);
        this.animateNumber('.stat-profiles-active', stats.profiles_active);
        this.animateNumber('.stat-firmware-versions', stats.firmware_versions);
        this.animateNumber('.stat-parameters-count', stats.unique_parameters);
        
        // Protocol stats
        this.animateNumber('.stat-tr069-devices', stats.devices.tr069);
        this.animateNumber('.stat-tr369-devices', stats.devices.tr369);
        this.animateNumber('.stat-tr369-mqtt', stats.devices.tr369_mqtt);
        this.animateNumber('.stat-tr369-http', stats.devices.tr369_http);
    }

    updateCharts(stats) {
        // Update Devices Chart (Doughnut)
        if (this.charts.devices) {
            this.charts.devices.data.datasets[0].data = [
                stats.devices.online,
                stats.devices.offline,
                stats.devices.provisioning,
                stats.devices.error
            ];
            this.charts.devices.update('none'); // No animation for smooth update
        }

        // Update Tasks Chart (Bar)
        if (this.charts.tasks) {
            this.charts.tasks.data.datasets[0].data = [
                stats.tasks.pending,
                stats.tasks.processing,
                stats.tasks.completed,
                stats.tasks.failed
            ];
            this.charts.tasks.update('none');
        }

        // Update Diagnostics Chart (Polar Area)
        if (this.charts.diagnostics) {
            this.charts.diagnostics.data.datasets[0].data = [
                stats.diagnostics.by_type.ping,
                stats.diagnostics.by_type.traceroute,
                stats.diagnostics.by_type.download,
                stats.diagnostics.by_type.upload
            ];
            this.charts.diagnostics.update('none');
        }

        // Update Firmware Chart (Line)
        if (this.charts.firmware) {
            this.charts.firmware.data.datasets[0].data = [
                stats.firmware.scheduled,
                stats.firmware.downloading,
                stats.firmware.installing,
                stats.firmware.completed,
                stats.firmware.failed
            ];
            this.charts.firmware.update('none');
        }
    }

    updateRecentDevices(devices) {
        // Table update logic would go here
        // For now, we'll just indicate new data is available
        const badge = document.querySelector('.recent-devices-badge');
        if (badge) {
            badge.classList.add('pulse-animation');
            setTimeout(() => badge.classList.remove('pulse-animation'), 1000);
        }
    }

    updateRecentTasks(tasks) {
        // Similar to updateRecentDevices
        const badge = document.querySelector('.recent-tasks-badge');
        if (badge) {
            badge.classList.add('pulse-animation');
            setTimeout(() => badge.classList.remove('pulse-animation'), 1000);
        }
    }

    animateNumber(selector, newValue) {
        const element = document.querySelector(selector);
        if (!element) return;

        const currentValue = parseInt(element.textContent) || 0;
        if (currentValue === newValue) return;

        // Animate number change
        const duration = 500;
        const steps = 20;
        const stepValue = (newValue - currentValue) / steps;
        let step = 0;

        const interval = setInterval(() => {
            step++;
            const value = Math.round(currentValue + (stepValue * step));
            element.textContent = value;

            if (step >= steps) {
                clearInterval(interval);
                element.textContent = newValue;
                
                // Add highlight effect
                element.parentElement.classList.add('stat-updated');
                setTimeout(() => {
                    element.parentElement.classList.remove('stat-updated');
                }, 800);
            }
        }, duration / steps);
    }

    updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }

    updateLastRefreshTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('it-IT');
        
        let indicator = document.getElementById('last-refresh-indicator');
        if (!indicator) {
            // Create indicator if doesn't exist
            indicator = document.createElement('div');
            indicator.id = 'last-refresh-indicator';
            indicator.className = 'text-xs text-muted text-end mt-2';
            indicator.style.position = 'fixed';
            indicator.style.bottom = '20px';
            indicator.style.right = '20px';
            indicator.style.background = 'rgba(255,255,255,0.9)';
            indicator.style.padding = '8px 12px';
            indicator.style.borderRadius = '8px';
            indicator.style.boxShadow = '0 2px 6px rgba(0,0,0,0.1)';
            indicator.style.zIndex = '999';
            document.body.appendChild(indicator);
        }
        
        indicator.innerHTML = `<i class="fas fa-sync-alt text-success me-1"></i>Aggiornato: ${timeString}`;
        indicator.classList.add('fade-in');
        setTimeout(() => indicator.classList.remove('fade-in'), 500);
    }

    startAutoRefresh() {
        // Initial refresh after 5 seconds
        setTimeout(() => this.refreshDashboard(), 5000);
        
        // Then every 30 seconds
        setInterval(() => this.refreshDashboard(), this.refreshInterval);
        
        console.log(`🔄 Auto-refresh started (${this.refreshInterval/1000}s interval)`);
    }

    setupNotifications() {
        // Create notification container if doesn't exist
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const colors = {
            success: 'bg-gradient-success',
            danger: 'bg-gradient-danger',
            warning: 'bg-gradient-warning',
            info: 'bg-gradient-info'
        };

        const icons = {
            success: 'fa-check-circle',
            danger: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const toast = document.createElement('div');
        toast.className = `alert ${colors[type]} text-white alert-dismissible fade show mb-2`;
        toast.innerHTML = `
            <i class="fas ${icons[type]} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        `;

        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardRealtime = new DashboardRealtime();
});
