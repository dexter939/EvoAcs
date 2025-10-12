# Production Deployment Checklist

Use this checklist to ensure a complete and secure production deployment of the ACS system.

## 🔧 Pre-Deployment Setup

### System Requirements
- [ ] Server meets minimum specs (8 cores, 16GB RAM, 500GB SSD)
- [ ] Operating system updated (Ubuntu 22.04 LTS or similar)
- [ ] Root/sudo access configured
- [ ] DNS records configured and propagated
- [ ] SSL certificates obtained (Let's Encrypt or commercial)

### Software Installation
- [ ] PHP 8.3+ installed with required extensions
- [ ] PostgreSQL 16+ installed and running
- [ ] Redis 7+ installed and running
- [ ] Nginx/Apache installed and configured
- [ ] Composer installed globally
- [ ] Git installed and configured
- [ ] Supervisor or Systemd available

---

## 📋 Application Setup

### Code Deployment
- [ ] Repository cloned to `/var/www/acs`
- [ ] Ownership set to `www-data:www-data`
- [ ] Composer dependencies installed (`--no-dev --optimize-autoloader`)
- [ ] Node dependencies installed (if applicable)
- [ ] Assets compiled (`npm run build`)

### Environment Configuration
- [ ] `.env` file created from `.env.example`
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated (`php artisan key:generate`)
- [ ] `APP_URL` set to production domain
- [ ] Database credentials configured
- [ ] Redis credentials configured
- [ ] MQTT broker configured
- [ ] WebSocket port configured (9000)
- [ ] **ACS_API_KEY changed** (32+ characters, secure random)

### Database Setup
- [ ] PostgreSQL database created
- [ ] Database user created with proper permissions
- [ ] Connection tested from application
- [ ] Migrations executed (`php artisan migrate --force`)
- [ ] Database indexes verified
- [ ] Backup strategy configured

---

## 🔐 Security Hardening

### Application Security
- [ ] All default passwords changed
- [ ] API keys rotated and secured
- [ ] Session encryption enabled (`SESSION_ENCRYPT=true`)
- [ ] Secure cookies enabled (`SESSION_SECURE_COOKIE=true`)
- [ ] CORS configured properly
- [ ] Trusted proxies configured (if using load balancer)
- [ ] Rate limiting enabled

### SSL/TLS Configuration
- [ ] MQTT broker SSL enabled (port 8883)
- [ ] WebSocket SSL enabled (`USP_WEBSOCKET_SSL=true`)
- [ ] HTTPS enabled for web interface
- [ ] SSL certificates valid and not expiring soon
- [ ] SSL grade A+ on SSL Labs test

### Firewall Rules
- [ ] UFW/iptables configured
- [ ] Port 80 (HTTP) open
- [ ] Port 443 (HTTPS) open
- [ ] Port 5432 (PostgreSQL) restricted to localhost
- [ ] Port 6379 (Redis) restricted to localhost
- [ ] Port 9000 (WebSocket) open with SSL
- [ ] Port 1883/8883 (MQTT) open
- [ ] SSH port changed from default (optional)
- [ ] Fail2ban configured for SSH/HTTP

---

## 🚀 Service Configuration

### Background Services

**Option A: Systemd (Recommended)**
- [ ] Copy service files to `/etc/systemd/system/`
  - [ ] `acs-mqtt.service`
  - [ ] `acs-websocket.service`
  - [ ] `acs-horizon.service`
- [ ] Services enabled (`systemctl enable acs-*`)
- [ ] Services started (`systemctl start acs-*`)
- [ ] Services verified (`systemctl status acs-*`)
- [ ] Auto-restart on failure configured

**Option B: Supervisor**
- [ ] Supervisor installed
- [ ] Config file copied to `/etc/supervisor/conf.d/acs.conf`
- [ ] Configuration reloaded (`supervisorctl reread && update`)
- [ ] Processes started (`supervisorctl start acs:*`)
- [ ] Processes verified (`supervisorctl status`)

### Web Server

**Nginx:**
- [ ] Configuration copied to `/etc/nginx/sites-available/acs`
- [ ] Symlink created in `/etc/nginx/sites-enabled/`
- [ ] Configuration tested (`nginx -t`)
- [ ] Server reloaded (`systemctl reload nginx`)
- [ ] Upstream servers configured (if load balancing)
- [ ] Rate limiting configured
- [ ] WebSocket proxy configured

**Apache (alternative):**
- [ ] VirtualHost configured
- [ ] Proxy modules enabled (proxy, proxy_http, proxy_wstunnel)
- [ ] SSL module enabled
- [ ] Configuration tested
- [ ] Server restarted

---

## 📊 Monitoring & Logging

### Application Monitoring
- [ ] Laravel logs configured
- [ ] Horizon dashboard accessible (`/horizon`)
- [ ] Error tracking configured (Sentry, Bugsnag, etc.)
- [ ] Metrics collection enabled (Prometheus, etc.)
- [ ] Uptime monitoring configured (Pingdom, UptimeRobot, etc.)

### System Monitoring
- [ ] Server metrics monitored (CPU, RAM, Disk)
- [ ] Process monitoring configured
- [ ] Database monitoring enabled
- [ ] Redis monitoring enabled
- [ ] MQTT broker monitoring enabled

### Log Management
- [ ] Application logs rotating (`/var/www/acs/storage/logs/`)
- [ ] Nginx logs rotating (`/var/log/nginx/`)
- [ ] System logs configured
- [ ] Log retention policy defined
- [ ] Centralized logging (optional: ELK, Graylog)

---

## 🧪 Testing & Validation

### Functional Testing
- [ ] Web dashboard accessible and loading
- [ ] API endpoints responding correctly
- [ ] TR-069 endpoint tested with device
- [ ] TR-369 USP endpoint tested
- [ ] MQTT transport verified
- [ ] WebSocket transport verified
- [ ] HTTP polling tested
- [ ] Authentication working (API keys)

### Performance Testing
- [ ] Load testing performed
- [ ] Response times acceptable (<500ms)
- [ ] Queue processing verified
- [ ] Database query performance optimized
- [ ] Caching working correctly
- [ ] WebSocket connections stable

### Security Testing
- [ ] SSL certificates valid
- [ ] Security headers present
- [ ] CORS working correctly
- [ ] SQL injection tests passed
- [ ] XSS protection verified
- [ ] CSRF protection enabled
- [ ] Rate limiting working

---

## 💾 Backup & Recovery

### Backup Configuration
- [ ] Database backup script configured
- [ ] Automated daily backups scheduled (cron)
- [ ] Backup retention policy defined
- [ ] Backup storage location secured
- [ ] Off-site backup configured (recommended)
- [ ] Application files backup configured

### Recovery Testing
- [ ] Database restore tested
- [ ] Application restore tested
- [ ] Disaster recovery plan documented
- [ ] RTO/RPO targets defined
- [ ] Recovery procedures documented

---

## 📈 Performance Optimization

### Application Optimization
- [ ] Configuration cached (`php artisan config:cache`)
- [ ] Routes cached (`php artisan route:cache`)
- [ ] Views cached (`php artisan view:cache`)
- [ ] Events cached (`php artisan event:cache`)
- [ ] Composer autoloader optimized
- [ ] OPcache enabled and configured

### Database Optimization
- [ ] Indexes created on frequently queried columns
- [ ] Query performance analyzed
- [ ] Connection pooling configured
- [ ] Slow query log enabled
- [ ] Database statistics updated

### Cache Configuration
- [ ] Redis cache driver configured
- [ ] Cache warming performed
- [ ] Cache hit rate monitored
- [ ] TTL values optimized

---

## 📝 Documentation

### Technical Documentation
- [ ] Deployment procedures documented
- [ ] Architecture diagram updated
- [ ] API documentation published
- [ ] Configuration guide available
- [ ] Troubleshooting guide created

### Operational Documentation
- [ ] Runbook created for common issues
- [ ] Escalation procedures defined
- [ ] On-call schedule defined (if applicable)
- [ ] Contact information documented
- [ ] Change management process defined

---

## 🎯 Go-Live Checklist

### Final Verification (T-1 hour)
- [ ] All services running and healthy
- [ ] Database migrations completed
- [ ] Cache warmed
- [ ] Health checks passing
- [ ] Monitoring dashboards showing green
- [ ] Team ready for go-live

### During Go-Live
- [ ] DNS switched to production
- [ ] Traffic monitoring
- [ ] Error rate monitoring
- [ ] Response time monitoring
- [ ] Queue processing monitoring

### Post-Deployment (T+1 hour)
- [ ] All functionality verified
- [ ] No critical errors in logs
- [ ] Performance metrics normal
- [ ] User acceptance testing passed
- [ ] Rollback plan ready (if needed)

---

## 🔄 Maintenance Tasks

### Daily
- [ ] Check service status
- [ ] Review error logs
- [ ] Monitor queue length
- [ ] Check disk space

### Weekly
- [ ] Review performance metrics
- [ ] Analyze slow queries
- [ ] Check backup status
- [ ] Update security patches

### Monthly
- [ ] Review and rotate logs
- [ ] Update dependencies
- [ ] Security audit
- [ ] Capacity planning review

---

## 🚨 Rollback Procedure

If deployment fails:

1. **Immediate Actions:**
   - [ ] Stop new deployment
   - [ ] Assess impact
   - [ ] Notify team

2. **Rollback Steps:**
   - [ ] Switch symlink to previous release
   - [ ] Restore database from backup (if needed)
   - [ ] Restart services
   - [ ] Clear caches
   - [ ] Verify functionality

3. **Post-Rollback:**
   - [ ] Document issue
   - [ ] Root cause analysis
   - [ ] Fix and retest
   - [ ] Plan new deployment

---

## ✅ Sign-Off

### Deployment Team
- [ ] Developer sign-off: _________________ Date: _______
- [ ] DevOps sign-off: _________________ Date: _______
- [ ] QA sign-off: _________________ Date: _______
- [ ] Security sign-off: _________________ Date: _______

### Production Approval
- [ ] Tech Lead approval: _________________ Date: _______
- [ ] Manager approval: _________________ Date: _______

### Notes:
_______________________________________________________
_______________________________________________________
_______________________________________________________

---

**Deployment Date:** _______________
**Deployment Time:** _______________
**Deployed Version:** _______________
**Deployed By:** _______________
