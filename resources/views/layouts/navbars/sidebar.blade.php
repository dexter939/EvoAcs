<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
        <a class="align-items-center d-flex m-0 navbar-brand text-wrap" href="{{ route('acs.dashboard') }}">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-gradient-primary text-center me-2 d-flex align-items-center justify-content-center">
                <i class="fas fa-server text-white opacity-10"></i>
            </div>
            <span class="ms-2 font-weight-bold text-sm">ACS Management</span>
        </a>
    </div>
    <hr class="horizontal dark mt-0 mb-2">
    
    <div class="collapse navbar-collapse w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/dashboard') ? 'active' : '' }}" href="{{ route('acs.dashboard') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-chart-pie text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            
            <!-- Gestione Dispositivi -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Gestione CPE</h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/devices*') ? 'active' : '' }}" href="{{ route('acs.devices') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-router text-warning text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dispositivi CPE</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/provisioning*') ? 'active' : '' }}" href="{{ route('acs.provisioning') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-cogs text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Provisioning</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/firmware*') ? 'active' : '' }}" href="{{ route('acs.firmware') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-microchip text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Firmware</span>
                </a>
            </li>
            
            <!-- Sistema -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Sistema</h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/tasks*') ? 'active' : '' }}" href="{{ route('acs.tasks') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-tasks text-danger text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Task Queue</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link {{ Request::is('acs/profiles*') ? 'active' : '' }}" href="{{ route('acs.profiles') }}">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-file-code text-dark text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Profili Configurazione</span>
                </a>
            </li>
            
            <!-- TR-069 -->
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">TR-069</h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="/dashboard" target="_blank">
                    <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-terminal text-secondary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">API JSON</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="sidenav-footer mx-3 mt-auto mb-2">
        <div class="card card-background shadow-none card-background-mask-secondary" id="sidenavCard">
            <div class="full-background" style="background-image: url('/assets/img/curved-images/white-curved.jpeg')"></div>
            <div class="card-body text-start p-3 w-100">
                <div class="docs-info">
                    <h6 class="text-white up mb-0">TR-069 Server</h6>
                    <p class="text-xs font-weight-bold">Endpoint: /tr069</p>
                </div>
            </div>
        </div>
    </div>
</aside>
