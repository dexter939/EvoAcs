/**
 * ACS Dashboard Real-time Updates
 * Auto-refresh statistics and charts every 30 seconds
 */

class DashboardRealtime {
    constructor() {
        this.refreshInterval = 30000; // 30 seconds
        this.charts = {};
        this.statsEndpoint = '/acs/dashboard/stats-api'; // Existing API endpoint
        
        // Performance monitoring metrics
        this.metrics = {
            totalRequests: 0,
            successfulRequests: 0,
            failedRequests: 0,
            totalResponseTime: 0,
            minResponseTime: Infinity,
            maxResponseTime: 0,
            lastResponseTime: 0,
            responseTimes: [], // Keep last 10 response times
            errors: []
        };
        
        this.init();
    }

    init() {
        this.setupCharts();
        this.startAutoRefresh();
        this.setupNotifications();
        this.logPerformanceMetrics();
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
        const startTime = performance.now();
        this.metrics.totalRequests++;
        
        try {
            const response = await fetch(this.statsEndpoint);
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Track successful request metrics
            this.metrics.successfulRequests++;
            this.trackResponseTime(responseTime);
            
            // Log slow requests (> 1000ms)
            if (responseTime > 1000) {
                console.warn(`⚠️ Slow API response: ${responseTime}ms`);
            }
            
            return data;
        } catch (error) {
            const endTime = performance.now();
            const responseTime = Math.round(endTime - startTime);
            
            // Track failed request
            this.metrics.failedRequests++;
            this.metrics.errors.push({
                timestamp: new Date().toISOString(),
                error: error.message,
                responseTime
            });
            
            // Keep only last 10 errors
            if (this.metrics.errors.length > 10) {
                this.metrics.errors.shift();
            }
            
            console.error('❌ Error fetching stats:', error);
            this.showNotification('Errore aggiornamento dati', 'danger');
            return null;
        }
    }
    
    trackResponseTime(responseTime) {
        this.metrics.lastResponseTime = responseTime;
        this.metrics.totalResponseTime += responseTime;
        this.metrics.minResponseTime = Math.min(this.metrics.minResponseTime, responseTime);
        this.metrics.maxResponseTime = Math.max(this.metrics.maxResponseTime, responseTime);
        
        // Keep last 10 response times
        this.metrics.responseTimes.push(responseTime);
        if (this.metrics.responseTimes.length > 10) {
            this.metrics.responseTimes.shift();
        }
    }
    
    getAverageResponseTime() {
        if (this.metrics.successfulRequests === 0) return 0;
        return Math.round(this.metrics.totalResponseTime / this.metrics.successfulRequests);
    }
    
    getPerformanceReport() {
        const avgResponseTime = this.getAverageResponseTime();
        const successRate = this.metrics.totalRequests > 0
            ? ((this.metrics.successfulRequests / this.metrics.totalRequests) * 100).toFixed(2)
            : 0;
        
        return {
            totalRequests: this.metrics.totalRequests,
            successfulRequests: this.metrics.successfulRequests,
            failedRequests: this.metrics.failedRequests,
            successRate: `${successRate}%`,
            avgResponseTime: `${avgResponseTime}ms`,
            minResponseTime: this.metrics.minResponseTime === Infinity ? 'N/A' : `${this.metrics.minResponseTime}ms`,
            maxResponseTime: `${this.metrics.maxResponseTime}ms`,
            lastResponseTime: `${this.metrics.lastResponseTime}ms`,
            recentResponseTimes: this.metrics.responseTimes,
            recentErrors: this.metrics.errors
        };
    }
    
    logPerformanceMetrics() {
        // Log performance metrics every 5 minutes
        setInterval(() => {
            const report = this.getPerformanceReport();
            console.group('📊 Dashboard Performance Metrics');
            console.log('Total Requests:', report.totalRequests);
            console.log('Success Rate:', report.successRate);
            console.log('Avg Response Time:', report.avgResponseTime);
            console.log('Min/Max Response:', `${report.minResponseTime} / ${report.maxResponseTime}`);
            console.log('Last Response:', report.lastResponseTime);
            
            if (report.recentResponseTimes.length > 0) {
                console.log('Recent Response Times:', report.recentResponseTimes.join('ms, ') + 'ms');
            }
            
            if (report.recentErrors.length > 0) {
                console.warn('Recent Errors:', report.recentErrors);
            }
            
            console.groupEnd();
            
            // Alert if error rate is high (> 20%)
            const errorRate = this.metrics.totalRequests > 0
                ? (this.metrics.failedRequests / this.metrics.totalRequests) * 100
                : 0;
            
            if (errorRate > 20 && this.metrics.totalRequests >= 5) {
                console.error(`🚨 High error rate detected: ${errorRate.toFixed(1)}%`);
            }
        }, 300000); // Every 5 minutes
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
        const tbody = document.querySelector('.recent-devices-table tbody');
        if (!tbody || !devices) return;
        
        // Clear and rebuild table rows
        tbody.innerHTML = '';
        
        if (devices.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-sm text-muted py-4">
                        Nessun dispositivo recente
                    </td>
                </tr>
            `;
            return;
        }
        
        devices.forEach(device => {
            const statusColors = {
                online: 'success',
                offline: 'secondary',
                provisioning: 'warning',
                error: 'danger'
            };
            
            const statusColor = statusColors[device.status] || 'secondary';
            const lastInform = this.formatTimeAgo(device.last_inform);
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <div class="d-flex px-2 py-1">
                        <div>
                            <i class="fas fa-router text-primary me-2"></i>
                        </div>
                        <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm">${this.escapeHtml(device.serial_number)}</h6>
                            <p class="text-xs text-secondary mb-0">${this.escapeHtml(device.manufacturer || 'N/A')}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge badge-sm bg-gradient-${statusColor}">${this.escapeHtml(device.status)}</span>
                </td>
                <td class="align-middle text-center text-sm">
                    <span class="text-xs font-weight-bold">${lastInform}</span>
                </td>
                <td class="align-middle text-center">
                    <a href="/acs/devices/${device.id}" class="text-secondary font-weight-bold text-xs" title="Dettagli">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        // Add fade-in animation
        tbody.classList.add('fade-in');
        setTimeout(() => tbody.classList.remove('fade-in'), 500);
    }

    updateRecentTasks(tasks) {
        const listGroup = document.querySelector('.recent-tasks-list');
        if (!listGroup || !tasks) return;
        
        // Clear and rebuild list items
        listGroup.innerHTML = '';
        
        if (tasks.length === 0) {
            listGroup.innerHTML = `
                <li class="list-group-item border-0 text-center text-sm text-muted py-4">
                    Nessun task recente
                </li>
            `;
            return;
        }
        
        tasks.forEach(task => {
            const statusConfig = {
                completed: { color: 'success', icon: 'check' },
                failed: { color: 'danger', icon: 'times' },
                pending: { color: 'warning', icon: 'clock' },
                processing: { color: 'info', icon: 'spinner' }
            };
            
            const config = statusConfig[task.status] || statusConfig.pending;
            const taskType = task.task_type ? task.task_type.replace(/_/g, ' ') : 'Unknown';
            const deviceSerial = task.cpe_device?.serial_number || task.device_serial || 'N/A';
            
            const li = document.createElement('li');
            li.className = 'list-group-item border-0 d-flex justify-content-between ps-0 mb-2 border-radius-lg';
            li.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="icon icon-shape icon-sm me-3 bg-gradient-${config.color} shadow text-center">
                        <i class="fas fa-${config.icon} opacity-10"></i>
                    </div>
                    <div class="d-flex flex-column">
                        <h6 class="mb-1 text-dark text-sm">${this.capitalize(this.escapeHtml(taskType))}</h6>
                        <span class="text-xs">${this.escapeHtml(deviceSerial)}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center text-${config.color} text-gradient text-sm font-weight-bold">
                    ${this.capitalize(this.escapeHtml(task.status))}
                </div>
            `;
            listGroup.appendChild(li);
        });
        
        // Add fade-in animation
        listGroup.classList.add('fade-in');
        setTimeout(() => listGroup.classList.remove('fade-in'), 500);
    }
    
    // Helper methods
    formatTimeAgo(dateString) {
        if (!dateString) return 'N/A';
        
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Pochi secondi fa';
        if (seconds < 3600) return `${Math.floor(seconds / 60)} minuti fa`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)} ore fa`;
        return `${Math.floor(seconds / 86400)} giorni fa`;
    }
    
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
    
    capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
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
    
    // Expose global performance report method for debugging
    window.showDashboardMetrics = () => {
        const report = window.dashboardRealtime.getPerformanceReport();
        console.group('📊 Dashboard Performance Report');
        console.table({
            'Total Requests': report.totalRequests,
            'Successful': report.successfulRequests,
            'Failed': report.failedRequests,
            'Success Rate': report.successRate,
            'Avg Response': report.avgResponseTime,
            'Min Response': report.minResponseTime,
            'Max Response': report.maxResponseTime,
            'Last Response': report.lastResponseTime
        });
        
        if (report.recentResponseTimes.length > 0) {
            console.log('Recent Response Times:', report.recentResponseTimes);
        }
        
        if (report.recentErrors.length > 0) {
            console.warn('Recent Errors:', report.recentErrors);
        }
        
        console.groupEnd();
        console.log('💡 Tip: Call showDashboardMetrics() anytime to see current metrics');
    };
    
    console.log('💡 Type showDashboardMetrics() to view performance report');
});
