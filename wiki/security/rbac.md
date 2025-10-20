# RBAC - Role-Based Access Control

Sistema di controllo accessi granulare basato su ruoli e permessi per gestione sicura multi-utente.

---

## 📋 Panoramica

Il sistema RBAC di ACS implementa un modello **Role → Permissions → Users** con:

- ✅ **5 ruoli predefiniti** (Administrator, Manager, Operator, Viewer, Support)
- ✅ **25+ permissions granulari** per feature-specific access control
- ✅ **Many-to-Many relationships** (user può avere multipli ruoli)
- ✅ **Permission inheritance** via role hierarchy
- ✅ **Security audit logging** per tutte le operazioni RBAC
- ✅ **UI management** per assegnazione ruoli e permissions

---

## 🎯 Architecture

### Entity Relationship

```
┌─────────┐         ┌──────────┐         ┌──────────────┐
│  Users  │◄───────►│user_role │◄───────►│    Roles     │
└─────────┘  N:M    └──────────┘   N:M   └──────┬───────┘
                                                 │
                                                 │ N:M
                                                 ▼
                                        ┌─────────────────┐
                                        │ role_permission │
                                        └────────┬────────┘
                                                 │
                                                 │ N:M
                                                 ▼
                                         ┌──────────────┐
                                         │ Permissions  │
                                         └──────────────┘
```

### Database Schema

```sql
-- Users Table
CREATE TABLE users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Roles Table
CREATE TABLE roles (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    level INTEGER,           -- Hierarchy level (1=highest)
    is_system BOOLEAN,       -- System role (cannot delete)
    is_active BOOLEAN,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Permissions Table
CREATE TABLE permissions (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255),
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    category VARCHAR(100),   -- alarms, devices, provisioning, etc.
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Pivot Tables
CREATE TABLE user_role (
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    role_id BIGINT REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

CREATE TABLE role_permission (
    role_id BIGINT REFERENCES roles(id) ON DELETE CASCADE,
    permission_id BIGINT REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);
```

---

## 👥 Ruoli Predefiniti

### 1. Super Administrator
```
Slug: super-admin
Level: 1 (highest)
Permissions: ALL
Description: Full system access
Is System: true

Use Case: System administrator, DevOps
```

**Capabilities**:
- ✅ Gestione utenti e ruoli
- ✅ Configurazione sistema
- ✅ Accesso a tutti i moduli
- ✅ Security audit logs
- ✅ Database operations

### 2. Manager
```
Slug: manager
Level: 2
Permissions: Most operations (exclude system config)
Description: Team lead / Department manager

Use Case: NOC manager, Regional manager
```

**Capabilities**:
- ✅ Device management
- ✅ Provisioning operations
- ✅ Firmware management
- ✅ Alarms management
- ✅ View reports & analytics
- ❌ System configuration
- ❌ User/role management

### 3. Operator
```
Slug: operator
Level: 3
Permissions: Operational tasks
Description: Day-to-day operations

Use Case: NOC operator, Field technician
```

**Capabilities**:
- ✅ Device configuration
- ✅ Alarm acknowledge/clear
- ✅ Run diagnostics
- ✅ View device details
- ❌ Firmware deployment
- ❌ Bulk operations
- ❌ Delete devices

### 4. Viewer
```
Slug: viewer
Level: 4
Permissions: Read-only access
Description: Monitoring and reporting

Use Case: Management, Audit, Reporting
```

**Capabilities**:
- ✅ View dashboards
- ✅ View device list
- ✅ View alarms
- ✅ Export reports
- ❌ Modify anything
- ❌ Execute operations

### 5. Support
```
Slug: support
Level: 5 (lowest)
Permissions: Minimal customer-facing
Description: Customer support team

Use Case: Help desk, Customer service
```

**Capabilities**:
- ✅ View customer devices (assigned only)
- ✅ View diagnostics results
- ✅ Create support tickets
- ❌ Modify configurations
- ❌ Access system settings

---

## 🔑 Permissions Catalog

### Alarms & Monitoring
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `alarms.view` | View Alarms | Access alarms dashboard, stats, SSE stream |
| `alarms.manage` | Manage Alarms | Acknowledge, clear, bulk operations |

### Devices Management
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `devices.view` | View Devices | List and view device details |
| `devices.create` | Create Devices | Register new devices manually |
| `devices.edit` | Edit Devices | Modify device parameters |
| `devices.delete` | Delete Devices | Remove devices from system |
| `devices.reboot` | Reboot Devices | Execute device reboot command |

### Provisioning
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `provisioning.view` | View Provisioning | Access provisioning dashboard |
| `provisioning.execute` | Execute Provisioning | Apply configuration profiles |
| `provisioning.bulk` | Bulk Provisioning | Mass configuration operations |
| `provisioning.schedule` | Schedule Provisioning | Create scheduled provisioning jobs |
| `provisioning.rollback` | Rollback Configuration | Restore previous configurations |

### Firmware Management
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `firmware.view` | View Firmware | List firmware images |
| `firmware.upload` | Upload Firmware | Upload new firmware files |
| `firmware.deploy` | Deploy Firmware | Execute firmware upgrades |
| `firmware.delete` | Delete Firmware | Remove firmware images |

### Users & Roles
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `users.view` | View Users | List system users |
| `users.manage` | Manage Users | Create, edit, delete users |
| `roles.manage` | Manage Roles | Assign roles and permissions |

### Diagnostics
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `diagnostics.view` | View Diagnostics | Access diagnostics results |
| `diagnostics.execute` | Execute Diagnostics | Run ping, traceroute, etc. |

### Configuration Profiles
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `profiles.view` | View Profiles | List configuration templates |
| `profiles.manage` | Manage Profiles | Create, edit, delete profiles |

### AI Assistant
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `ai.use` | Use AI Assistant | Access AI configuration features |

### Reports & Analytics
| Permission Slug | Name | Description |
|----------------|------|-------------|
| `reports.view` | View Reports | Access reports and analytics |
| `reports.export` | Export Reports | Download CSV, PDF exports |

---

## 🔒 Permission Checking

### Controller Level

```php
use App\Http\Middleware\CheckPermission;

class AlarmsController extends Controller
{
    // Via middleware in routes
    // Route::middleware(['permission:alarms.view'])
    
    public function index(Request $request)
    {
        // User already authorized by middleware
        return view('acs.alarms.index');
    }
    
    // Manual check in controller
    public function acknowledge($id)
    {
        if (!auth()->user()->hasPermission('alarms.manage')) {
            abort(403, 'Unauthorized');
        }
        
        // Proceed with operation
    }
}
```

### Route Level

```php
// routes/web.php

Route::middleware(['auth'])->prefix('acs')->group(function () {
    
    // Single permission
    Route::middleware(['permission:alarms.view'])->group(function () {
        Route::get('/alarms', [AlarmsController::class, 'index']);
    });
    
    // Multiple permissions (any)
    Route::middleware(['permission:devices.edit,devices.create'])->group(function () {
        Route::post('/devices', [DeviceController::class, 'store']);
    });
});
```

### Blade Template

```blade
@can('permission', 'alarms.manage')
    <button class="btn btn-primary" onclick="acknowledgeAlarm()">
        Acknowledge
    </button>
@endcan

@cannot('permission', 'alarms.manage')
    <span class="text-muted">
        <i class="fa fa-lock"></i> Requires alarms.manage permission
    </span>
@endcannot
```

### JavaScript/API Level

```javascript
// Frontend should check capabilities first
fetch('/acs/alarms/123/acknowledge', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': csrfToken,
    }
})
.then(response => {
    if (response.status === 403) {
        alert('You do not have permission to perform this action');
    }
    return response.json();
});
```

---

## 🛠️ RBAC Management UI

### Users Management

**Path**: `/acs/users`  
**Permission**: `users.manage`

**Features**:
- ✅ List all users with roles
- ✅ Create new user
- ✅ Edit user details
- ✅ Assign/remove roles
- ✅ Deactivate user
- ✅ Reset password

**UI Screenshot**:
```
┌─────────────────────────────────────────────────────────────┐
│ Users Management                              [+ New User]  │
├─────────────────────────────────────────────────────────────┤
│ Search: [________________]  Filter: [All Roles ▼]          │
├────┬──────────────┬────────────────────┬──────────┬─────────┤
│ ID │ Name         │ Email              │ Role     │ Actions │
├────┼──────────────┼────────────────────┼──────────┼─────────┤
│  1 │ Admin User   │ admin@acs.local    │ Admin    │ [Edit]  │
│  2 │ John Doe     │ john@company.local │ Operator │ [Edit]  │
│  3 │ Jane Smith   │ jane@company.local │ Viewer   │ [Edit]  │
└────┴──────────────┴────────────────────┴──────────┴─────────┘
```

### Roles Management

**Path**: `/acs/roles`  
**Permission**: `roles.manage`

**Features**:
- ✅ List all roles
- ✅ Create custom role
- ✅ Edit role details
- ✅ Assign permissions to role
- ✅ Delete custom role (system roles protected)

**Permission Assignment UI**:
```
┌────────────────────────────────────────────────────┐
│ Edit Role: Operator                                │
├────────────────────────────────────────────────────┤
│ Name: [Operator_______________]                    │
│ Description: [Day-to-day operations_____________]  │
│                                                    │
│ Permissions:                                       │
│ ┌────────────────────────────────────────────┐   │
│ │ Alarms & Monitoring                        │   │
│ │   ☑ alarms.view                            │   │
│ │   ☑ alarms.manage                          │   │
│ │                                            │   │
│ │ Devices Management                         │   │
│ │   ☑ devices.view                           │   │
│ │   ☑ devices.edit                           │   │
│ │   ☐ devices.delete                         │   │
│ │                                            │   │
│ │ Provisioning                               │   │
│ │   ☑ provisioning.view                      │   │
│ │   ☑ provisioning.execute                   │   │
│ │   ☐ provisioning.bulk                      │   │
│ └────────────────────────────────────────────┘   │
│                                                    │
│ [Cancel]                      [Save Changes]      │
└────────────────────────────────────────────────────┘
```

---

## 🔐 Security Features

### 1. Permission Middleware

**File**: `app/Http/Middleware/CheckPermission.php`

```php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->check()) {
            SecurityLog::logUnauthorizedAccess($request->path(), 'User not authenticated');
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->hasPermission($permission)) {
            SecurityLog::logUnauthorizedAccess(
                $request->path(),
                "User {$user->id} lacks permission: {$permission}"
            );
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
```

### 2. Security Audit Logging

Tutte le azioni RBAC sono tracciate:

```php
// User created
SecurityLog::create([
    'action' => 'user_created',
    'severity' => 'info',
    'user_id' => auth()->id(),
    'metadata' => [
        'new_user_id' => $user->id,
        'email' => $user->email,
        'role' => $role->name,
    ],
]);

// Permission denied
SecurityLog::create([
    'action' => 'unauthorized_access',
    'severity' => 'critical',
    'user_id' => auth()->id(),
    'metadata' => [
        'path' => $request->path(),
        'required_permission' => $permission,
    ],
]);

// Role assigned
SecurityLog::create([
    'action' => 'role_assigned',
    'severity' => 'info',
    'user_id' => auth()->id(),
    'metadata' => [
        'target_user_id' => $userId,
        'role_id' => $roleId,
        'role_name' => $role->name,
    ],
]);
```

### 3. Session Security

```php
// config/session.php
'lifetime' => 120,               // 2 hours
'expire_on_close' => true,
'encrypt' => true,
'same_site' => 'strict',
'secure' => env('SESSION_SECURE_COOKIE', true),  // HTTPS only
```

---

## 📊 Best Practices

### 1. Principle of Least Privilege

**DO**:
- ✅ Assegna SOLO le permissions necessarie per il ruolo
- ✅ Usa Viewer role per utenti che devono solo monitorare
- ✅ Crea custom roles per casi specifici

**DON'T**:
- ❌ Dare Administrator a tutti
- ❌ Creare "super users" con troppi permessi
- ❌ Riusare un singolo account per team

### 2. Role Hierarchy

Rispetta la gerarchia dei livelli:
```
Level 1 (Highest) → Super Administrator
Level 2           → Manager
Level 3           → Operator
Level 4           → Viewer
Level 5 (Lowest)  → Support
```

Un utente con ruolo level 2 **non può** modificare ruoli level 1.

### 3. Regular Audits

```sql
-- Review permission assignments quarterly
SELECT r.name as role, p.slug as permission, COUNT(ur.user_id) as user_count
FROM roles r
JOIN role_permission rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
LEFT JOIN user_role ur ON r.id = ur.role_id
GROUP BY r.name, p.slug
ORDER BY user_count DESC;

-- Find users with multiple roles
SELECT u.email, COUNT(ur.role_id) as role_count
FROM users u
JOIN user_role ur ON u.id = ur.user_id
GROUP BY u.email
HAVING COUNT(ur.role_id) > 1;
```

### 4. Testing RBAC

Sempre testare:
```php
// tests/Feature/RBACTest.php

public function test_viewer_cannot_acknowledge_alarms()
{
    $viewer = User::factory()->create();
    $viewer->assignRole(Role::where('slug', 'viewer')->first());
    
    $alarm = Alarm::factory()->create();
    
    $response = $this->actingAs($viewer)
        ->post("/acs/alarms/{$alarm->id}/acknowledge");
    
    $response->assertStatus(403);
}

public function test_operator_can_acknowledge_alarms()
{
    $operator = User::factory()->create();
    $operator->assignRole(Role::where('slug', 'operator')->first());
    
    $alarm = Alarm::factory()->create();
    
    $response = $this->actingAs($operator)
        ->post("/acs/alarms/{$alarm->id}/acknowledge");
    
    $response->assertStatus(200);
}
```

---

## 🔧 API Reference

### Check User Permission

```php
// In controller
if (auth()->user()->hasPermission('alarms.manage')) {
    // Authorized
}

// In service class
if ($user->hasAnyRole(['super-admin', 'manager'])) {
    // Authorized
}

// Check multiple permissions (any)
if ($user->hasAnyPermission(['devices.edit', 'devices.create'])) {
    // Authorized
}
```

### Assign Role to User

```php
$user = User::find($userId);
$role = Role::where('slug', 'operator')->first();

$user->assignRole($role);
// OR
$user->assignRole('operator');  // By slug
```

### Assign Permission to Role

```php
$role = Role::find($roleId);
$permission = Permission::where('slug', 'alarms.manage')->first();

$role->givePermissionTo($permission);
// OR
$role->givePermissionTo('alarms.manage');  // By slug
```

---

**Vedi Anche**:
- [RBAC Operations Guide](../../docs/ALARMS_RBAC_GUIDE.md)
- [RBAC Testing Guide](../../docs/ALARMS_RBAC_TESTING_GUIDE.md)
- [Security Hardening](hardening.md)

---

**Ultima Modifica**: Ottobre 2025  
**Versione**: 1.0
