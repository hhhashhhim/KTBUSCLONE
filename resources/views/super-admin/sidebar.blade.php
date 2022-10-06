<div class="main-sidebar sidebar-style-2">
    <aside id="sidebar-wrapper">
      <div class="sidebar-brand">
        <a href="/"> <img alt="image" src="{{ asset('assets/img/kt-logo.png') }}" class="header-logo" /> 
            {{-- <span class="logo-name">Otika</span> --}}
        </a>
      </div>
      <ul class="sidebar-menu">
        <li class="dropdown">
            <a href="#" class="menu-toggle nav-link has-dropdown"><i
                data-feather="briefcase"></i><span>Companies</span></a>
            <ul class="dropdown-menu">
              <li><a class="nav-link" href="{{ route('company.add') }}">Add Company</a></li>
              <li><a class="nav-link" href="{{ route('company') }}">All Companies</a></li>
            </ul>
          </li>
      </ul>
    </aside>
  </div>