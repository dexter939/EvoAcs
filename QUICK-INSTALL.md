# 🚀 EvoAcs - Installazione Rapida

Sistema carrier-grade per gestione CPE con supporto TR-069 e TR-369 USP.

## ⚡ Installazione in 1 Minuto

```bash
# Download e installazione automatica
wget https://raw.githubusercontent.com/dexter939/EvoAcs/main/install.sh
chmod +x install.sh
sudo ./install.sh
```

Lo script installerà automaticamente:
- ✅ PHP 8.3 + estensioni
- ✅ PostgreSQL 16
- ✅ Redis 7.0
- ✅ Nginx
- ✅ Supervisor
- ✅ Prosody XMPP Server
- ✅ Configurazione completa del sistema

## 🔐 Credenziali Default

Dopo l'installazione:
- **URL**: `http://your-server-ip`
- **Email**: `admin@acs.local`
- **Password**: `password`

⚠️ **Cambia la password al primo accesso!**

## 🎯 Sistemi Supportati

- Ubuntu 22.04+ / Debian 11+
- CentOS/RHEL 8+
- Rocky Linux / AlmaLinux 8+

## 📖 Documentazione Completa

Per installazione manuale e troubleshooting dettagliato, consulta [README-INSTALLATION.md](README-INSTALLATION.md)

## 🔧 Personalizzazione

Prima di eseguire lo script, puoi impostare:

```bash
# Dominio personalizzato
export DOMAIN='acs.yourdomain.com'

# Password database personalizzata
export DB_PASSWORD='your-secure-password'

# Repository personalizzato (se hai un fork)
export REPO_URL='https://github.com/your-fork/EvoAcs.git'

# Poi esegui l'installazione
sudo ./install.sh
```

## 📊 Verifica Installazione

```bash
# Controlla stato servizi
sudo systemctl status nginx
sudo systemctl status postgresql
sudo systemctl status redis
sudo supervisorctl status

# Visualizza log applicazione
tail -f /opt/acs/storage/logs/laravel.log
```

## 🆘 Supporto

- **Repository**: https://github.com/dexter939/EvoAcs
- **Issues**: https://github.com/dexter939/EvoAcs/issues
- **Documentazione**: [README-INSTALLATION.md](README-INSTALLATION.md)

## 📝 Note Importanti

1. Lo script richiede accesso root o sudo
2. Assicurati che le porte 80, 443, 7547, 5222 siano aperte
3. L'installazione richiede circa 5-10 minuti
4. Una connessione internet stabile è necessaria

---

**EvoAcs** - Powered by Laravel 11 • TR-069 • TR-369 USP
