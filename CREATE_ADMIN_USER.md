# 🔐 Creare Utente Admin sul Deployment

## ⚠️ Problema

Hai provato ad accedere con `admin@acs.local` ma ricevi l'errore **"These credentials do not match our records"**.

**Motivo**: Il database di **produzione** (deployment) è separato dal database locale. L'utente admin deve essere creato nel database di produzione.

---

## ✅ Soluzione: Esegui il Seeder sul Deployment

### **Opzione 1: Via Console Replit (Consigliata)**

1. **Vai al tuo deployment su Replit**
   - Dashboard Replit → Deployments → Il tuo deployment attivo

2. **Apri la Console/Shell del deployment**
   - Cerca il pulsante "Console" o "Shell" nel deployment dashboard
   - Oppure vai alla tab "Tools" → "Console"

3. **Esegui questo comando**:
   ```bash
   php artisan tinker --execute="
   App\Models\User::firstOrCreate(
       ['email' => 'admin@acs.local'],
       [
           'name' => 'Admin',
           'password' => Hash::make('password'),
           'email_verified_at' => now()
       ]
   );
   echo 'Admin user created successfully';
   "
   ```

4. **Prova ad accedere di nuovo**
   - Email: `admin@acs.local`
   - Password: `password`

---

### **Opzione 2: Via Database Seeder (Alternativa)**

Se l'Opzione 1 non funziona, prova con il seeder:

1. **Apri la Console del deployment**

2. **Esegui il seeder**:
   ```bash
   php artisan db:seed --class=TestUserSeeder
   ```

   Oppure:
   ```bash
   php artisan tinker
   ```
   
   Poi nel prompt di tinker:
   ```php
   $user = App\Models\User::create([
       'name' => 'Admin',
       'email' => 'admin@acs.local',
       'password' => Hash::make('password'),
       'email_verified_at' => now()
   ]);
   echo "User created: " . $user->email;
   exit
   ```

---

### **Opzione 3: Via Replit Database UI**

Se hai accesso al Database UI di Replit per il deployment:

1. **Vai a Database → Production Database**

2. **Esegui questa query SQL**:
   ```sql
   INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
   VALUES (
       'Admin',
       'admin@acs.local',
       '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/.J7Q9X0Y7wQJMxaGm',
       NOW(),
       NOW(),
       NOW()
   );
   ```

   **Nota**: Questa password è pre-hashata per `password`

---

## 🔐 Credenziali di Accesso

```
Email:    admin@acs.local
Password: password
```

---

## ⚠️ IMPORTANTE: Cambia la Password Dopo il Primo Accesso

La password `password` è solo temporanea. Cambiala immediatamente dopo il primo login:

1. Vai su **Profilo** → **Impostazioni**
2. Cambia password
3. Usa una password sicura

---

## 🐛 Troubleshooting

### "User already exists"
Se ricevi questo errore, significa che l'utente esiste già. Prova:

```bash
php artisan tinker --execute="
\$user = App\Models\User::where('email', 'admin@acs.local')->first();
\$user->password = Hash::make('password');
\$user->email_verified_at = now();
\$user->save();
echo 'Password reset successfully';
"
```

### "Cannot connect to console"
Se non riesci ad accedere alla console del deployment:

1. Verifica che il deployment sia attivo
2. Prova a riavviare il deployment
3. Contatta il supporto Replit

### "Command not found"
Assicurati di essere nella directory corretta del progetto Laravel prima di eseguire i comandi.

---

## 📝 Note

- **Database Development vs Production**: Ricorda che il database locale (development) è SEPARATO dal database del deployment (production)
- **Modifiche locali**: Le modifiche fatte localmente (via tool) NON si riflettono sul deployment
- **Seeder aggiornato**: Ho modificato `DatabaseSeeder.php` per creare automaticamente l'utente admin, ma devi eseguirlo sul deployment

---

**Dopo aver creato l'utente, ricarica la pagina di login e prova di nuovo!** 🚀
