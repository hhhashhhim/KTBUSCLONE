<!DOCTYPE html>
<html lang="en">


<!-- index.html  21 Nov 2019 03:44:50 GMT -->
<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Otika - Admin Dashboard Template</title>
  <!-- General CSS Files -->
  <link rel="stylesheet" href="assets/css/app.min.css">
  <!-- Template CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <!-- Custom style CSS -->
  <link rel="stylesheet" href="assets/css/custom.css">
  <link rel='shortcut icon' type='image/x-icon' href='assets/img/favicon.ico' />
  <style>
        /* buttons accordian  */
        .ab-button{
            display: inline-block;
            margin: 0 2px;
            width: 140px;
            height: 50px;
            background-color: #e7e7e7; 
            color: black;
            padding: 5px 5px 5px 5px;
            text-align: center;
            overflow: hidden;
            border-radius: 5px;
            position: relative;
        }
        .is-complete{
          background-color: #54ca68;
          color: white;
        }
        .ab-button.open{
            background-color: #6777ef!important;
            color: white!important;
        }
        .ab-button:hover{
            cursor: pointer;
            border: 1px solid #563535;
        }
        .ab-button-row {
            width: 100%; /* Set the width you want for the container */
            padding: 2px;
            height: 62px;
            overflow-x: auto; /* Enable horizontal scrolling */
            white-space: nowrap; /* Prevent the content from wrapping to the next line */
            border: 1px solid #ccc; /* Optional: add a border for visual clarity */
        }
        /* buttons add */
        .ab-button-add{
            display: inline-block;
            margin: 0 2px;
            width: 150px;
            height: 50px;
            background-color: green; 
            color: white;
            align-items: center;
            padding: 5px 5px 5px 5px;
            text-align: center;
            overflow: hidden;
            border-radius: 5px;
            box-shadow: 0 8px 16px 0 rgba(0, 0, 0, 0.01), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
            line-height: 2.8;
            font-weight: 600;
        }
        .ab-button-add:hover{
            cursor: pointer;
            border: 1px solid #563535;
        }
        .ab-data{
            margin: 20px 0;
        }
        .scroll-hide::-webkit-scrollbar {
          display: none; /* Hide scrollbar */
        }

        .scroll-hide {
          -ms-overflow-style: none; /* Internet Explorer and Edge */
          scrollbar-width: none; /* Firefox */
        }
        .cross-button{
          position: absolute;
          top: 0px;
          line-height: 0.9;
          right: 0;
          width: 20px;
          height: 20px;
          background-color: black;
          color: white;
          padding: 3px;
          z-index: 1;
        }
        
        
        /* Modal Css */
        * {
              box-sizing: border-box;
          }

          body {
              font-family: Arial, sans-serif;
          }

          .modal {
              display: none; /* Hidden by default */
              position: fixed; /* Stay in place */
              z-index: 1000; /* Sit on top */
              left: 0;
              top: 0;
              width: 100%; /* Full width */
              height: 100%; /* Full height */
              overflow: auto; /* Enable scroll if needed */
              background-color: rgba(0, 0, 0, 0.7); /* Black background with opacity */
              opacity: 0; /* Start invisible */
              transition: opacity 0.5s ease; /* Fade in and out */
          }

          .modal-content {
              position: relative;
              margin: 0;
              padding: 0;
              width: 100%;
              height: 100%;
              overflow: auto; /* Enable scroll for content */
              transform: translateY(-100%); /* Start off screen */
              transition: transform 0.5s ease; /* Slide in and out */
          }
          #tree-modal .modal-content {
            color: white;
            background-color: rgb(0 0 0 / 60%);
          }

          .modal.show {
              display: block; /* Show modal */
              opacity: 1; /* Fade in */
          }

          .modal-content.show {
              transform: translateY(0); /* Slide down to view */
          }

          .modal-body {
              padding: 20px;
          }

          .close {
              position: absolute;
              top: 10px;
              right: 20px;
              font-size: 28px;
              font-weight: bold;
              cursor: pointer;
              z-index: 1;
              cursor: pointer;
          }
          #tree-modal .close{
            color: white;
          }
          .close:hover{
            color: green;
          }
          .tree-node
          {
            text-align: center !important;
            margin: 70px;
          }
          .node-parent
          {
            display: flex;
            justify-content: space-around;
            border: 1px solid white;
          }
          #tree-section
          {
            width: fit-content;
          }
          .info{
            width: 200px;
            height: 110px;
            text-align: left;
            padding: 10px;
            position: relative;
          }

          .image-container{
            cursor: pointer;
          }

          .detail{
            display: none;
          }

          .info:hover .detail {
            display: block;
            position: absolute;
            background-color: white;
            min-width: 200px;
            color: black;
            padding: 10px;
            top: 110px;
            left: 0;
            width: 530px;
            z-index: 1;
          }
    </style>
</head>

<body>
  <div class="loader"></div>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <div class="navbar-bg"></div>
      <nav class="navbar navbar-expand-lg main-navbar sticky">
        <div class="form-inline mr-auto">
          <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg
									collapse-btn"> <i data-feather="align-justify"></i></a></li>
            <li><a href="#" class="nav-link nav-link-lg fullscreen-btn">
                <i data-feather="maximize"></i>
              </a></li>
            <li>
              <form class="form-inline mr-auto">
                <div class="search-element">
                  <input class="form-control" type="search" placeholder="Search" aria-label="Search" data-width="200">
                  <button class="btn" type="submit">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </form>
            </li>
          </ul>
        </div>
        <ul class="navbar-nav navbar-right">
          <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
              class="nav-link nav-link-lg message-toggle"><i data-feather="mail"></i>
              <span class="badge headerBadge1">
                6 </span> </a>
            <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">
              <div class="dropdown-header">
                Messages
                <div class="float-right">
                  <a href="#">Mark All As Read</a>
                </div>
              </div>
              <div class="dropdown-list-content dropdown-list-message">
                <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar
											text-white"> <img alt="image" src="assets/img/users/user-1.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">John
                      Deo</span>
                    <span class="time messege-text">Please check your mail !!</span>
                    <span class="time">2 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="assets/img/users/user-2.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Sarah
                      Smith</span> <span class="time messege-text">Request for leave
                      application</span>
                    <span class="time">5 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="assets/img/users/user-5.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Jacob
                      Ryan</span> <span class="time messege-text">Your payment invoice is
                      generated.</span> <span class="time">12 Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="assets/img/users/user-4.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Lina
                      Smith</span> <span class="time messege-text">hii John, I have upload
                      doc
                      related to task.</span> <span class="time">30
                      Min Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="assets/img/users/user-3.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Jalpa
                      Joshi</span> <span class="time messege-text">Please do as specify.
                      Let me
                      know if you have any query.</span> <span class="time">1
                      Days Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-avatar text-white">
                    <img alt="image" src="assets/img/users/user-2.png" class="rounded-circle">
                  </span> <span class="dropdown-item-desc"> <span class="message-user">Sarah
                      Smith</span> <span class="time messege-text">Client Requirements</span>
                    <span class="time">2 Days Ago</span>
                  </span>
                </a>
              </div>
              <div class="dropdown-footer text-center">
                <a href="#">View All <i class="fas fa-chevron-right"></i></a>
              </div>
            </div>
          </li>
          <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
              class="nav-link notification-toggle nav-link-lg"><i data-feather="bell" class="bell"></i>
            </a>
            <div class="dropdown-menu dropdown-list dropdown-menu-right pullDown">
              <div class="dropdown-header">
                Notifications
                <div class="float-right">
                  <a href="#">Mark All As Read</a>
                </div>
              </div>
              <div class="dropdown-list-content dropdown-list-icons">
                <a href="#" class="dropdown-item dropdown-item-unread"> <span
                    class="dropdown-item-icon bg-primary text-white"> <i class="fas
												fa-code"></i>
                  </span> <span class="dropdown-item-desc"> Template update is
                    available now! <span class="time">2 Min
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-info text-white"> <i class="far
												fa-user"></i>
                  </span> <span class="dropdown-item-desc"> <b>You</b> and <b>Dedik
                      Sugiharto</b> are now friends <span class="time">10 Hours
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-success text-white"> <i
                      class="fas
												fa-check"></i>
                  </span> <span class="dropdown-item-desc"> <b>Kusnaedi</b> has
                    moved task <b>Fix bug header</b> to <b>Done</b> <span class="time">12
                      Hours
                      Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-danger text-white"> <i
                      class="fas fa-exclamation-triangle"></i>
                  </span> <span class="dropdown-item-desc"> Low disk space. Let's
                    clean it! <span class="time">17 Hours Ago</span>
                  </span>
                </a> <a href="#" class="dropdown-item"> <span class="dropdown-item-icon bg-info text-white"> <i class="fas
												fa-bell"></i>
                  </span> <span class="dropdown-item-desc"> Welcome to Otika
                    template! <span class="time">Yesterday</span>
                  </span>
                </a>
              </div>
              <div class="dropdown-footer text-center">
                <a href="#">View All <i class="fas fa-chevron-right"></i></a>
              </div>
            </div>
          </li>
          <li class="dropdown"><a href="#" data-toggle="dropdown"
              class="nav-link dropdown-toggle nav-link-lg nav-link-user"> <img alt="image" src="assets/img/user.png"
                class="user-img-radious-style"> <span class="d-sm-none d-lg-inline-block"></span></a>
            <div class="dropdown-menu dropdown-menu-right pullDown">
              <div class="dropdown-title">Hello Sarah Smith</div>
              <a href="profile.html" class="dropdown-item has-icon"> <i class="far
										fa-user"></i> Profile
              </a> <a href="timeline.html" class="dropdown-item has-icon"> <i class="fas fa-bolt"></i>
                Activities
              </a> <a href="#" class="dropdown-item has-icon"> <i class="fas fa-cog"></i>
                Settings
              </a>
              <div class="dropdown-divider"></div>
              <a href="auth-login.html" class="dropdown-item has-icon text-danger"> <i class="fas fa-sign-out-alt"></i>
                Logout
              </a>
            </div>
          </li>
        </ul>
      </nav>
      <div class="main-sidebar sidebar-style-2">
        <aside id="sidebar-wrapper">
          <div class="sidebar-brand">
            <a href="index.html"> <img alt="image" src="assets/img/logo.png" class="header-logo" /> <span
                class="logo-name">Otika</span>
            </a>
          </div>
          <ul class="sidebar-menu">
            <li class="menu-header">Main</li>
            <li class="dropdown active">
              <a href="index.html" class="nav-link"><i data-feather="monitor"></i><span>Dashboard</span></a>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="briefcase"></i><span>Widgets</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="widget-chart.html">Chart Widgets</a></li>
                <li><a class="nav-link" href="widget-data.html">Data Widgets</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="command"></i><span>Apps</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="chat.html">Chat</a></li>
                <li><a class="nav-link" href="portfolio.html">Portfolio</a></li>
                <li><a class="nav-link" href="blog.html">Blog</a></li>
                <li><a class="nav-link" href="calendar.html">Calendar</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="mail"></i><span>Email</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="email-inbox.html">Inbox</a></li>
                <li><a class="nav-link" href="email-compose.html">Compose</a></li>
                <li><a class="nav-link" href="email-read.html">read</a></li>
              </ul>
            </li>
            <li class="menu-header">UI Elements</li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="copy"></i><span>Basic
                  Components</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="alert.html">Alert</a></li>
                <li><a class="nav-link" href="badge.html">Badge</a></li>
                <li><a class="nav-link" href="breadcrumb.html">Breadcrumb</a></li>
                <li><a class="nav-link" href="buttons.html">Buttons</a></li>
                <li><a class="nav-link" href="collapse.html">Collapse</a></li>
                <li><a class="nav-link" href="dropdown.html">Dropdown</a></li>
                <li><a class="nav-link" href="checkbox-and-radio.html">Checkbox &amp; Radios</a></li>
                <li><a class="nav-link" href="list-group.html">List Group</a></li>
                <li><a class="nav-link" href="media-object.html">Media Object</a></li>
                <li><a class="nav-link" href="navbar.html">Navbar</a></li>
                <li><a class="nav-link" href="pagination.html">Pagination</a></li>
                <li><a class="nav-link" href="popover.html">Popover</a></li>
                <li><a class="nav-link" href="progress.html">Progress</a></li>
                <li><a class="nav-link" href="tooltip.html">Tooltip</a></li>
                <li><a class="nav-link" href="flags.html">Flag</a></li>
                <li><a class="nav-link" href="typography.html">Typography</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="shopping-bag"></i><span>Advanced</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="avatar.html">Avatar</a></li>
                <li><a class="nav-link" href="card.html">Card</a></li>
                <li><a class="nav-link" href="modal.html">Modal</a></li>
                <li><a class="nav-link" href="sweet-alert.html">Sweet Alert</a></li>
                <li><a class="nav-link" href="toastr.html">Toastr</a></li>
                <li><a class="nav-link" href="empty-state.html">Empty State</a></li>
                <li><a class="nav-link" href="multiple-upload.html">Multiple Upload</a></li>
                <li><a class="nav-link" href="pricing.html">Pricing</a></li>
                <li><a class="nav-link" href="tabs.html">Tab</a></li>
              </ul>
            </li>
            <li><a class="nav-link" href="blank.html"><i data-feather="file"></i><span>Blank Page</span></a></li>
            <li class="menu-header">Otika</li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="layout"></i><span>Forms</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="basic-form.html">Basic Form</a></li>
                <li><a class="nav-link" href="forms-advanced-form.html">Advanced Form</a></li>
                <li><a class="nav-link" href="forms-editor.html">Editor</a></li>
                <li><a class="nav-link" href="forms-validation.html">Validation</a></li>
                <li><a class="nav-link" href="form-wizard.html">Form Wizard</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="grid"></i><span>Tables</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="basic-table.html">Basic Tables</a></li>
                <li><a class="nav-link" href="advance-table.html">Advanced Table</a></li>
                <li><a class="nav-link" href="datatables.html">Datatable</a></li>
                <li><a class="nav-link" href="export-table.html">Export Table</a></li>
                <li><a class="nav-link" href="editable-table.html">Editable Table</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="pie-chart"></i><span>Charts</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="chart-amchart.html">amChart</a></li>
                <li><a class="nav-link" href="chart-apexchart.html">apexchart</a></li>
                <li><a class="nav-link" href="chart-echart.html">eChart</a></li>
                <li><a class="nav-link" href="chart-chartjs.html">Chartjs</a></li>
                <li><a class="nav-link" href="chart-sparkline.html">Sparkline</a></li>
                <li><a class="nav-link" href="chart-morris.html">Morris</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="feather"></i><span>Icons</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="icon-font-awesome.html">Font Awesome</a></li>
                <li><a class="nav-link" href="icon-material.html">Material Design</a></li>
                <li><a class="nav-link" href="icon-ionicons.html">Ion Icons</a></li>
                <li><a class="nav-link" href="icon-feather.html">Feather Icons</a></li>
                <li><a class="nav-link" href="icon-weather-icon.html">Weather Icon</a></li>
              </ul>
            </li>
            <li class="menu-header">Media</li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="image"></i><span>Gallery</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="light-gallery.html">Light Gallery</a></li>
                <li><a href="gallery1.html">Gallery 2</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="flag"></i><span>Sliders</span></a>
              <ul class="dropdown-menu">
                <li><a href="carousel.html">Bootstrap Carousel.html</a></li>
                <li><a class="nav-link" href="owl-carousel.html">Owl Carousel</a></li>
              </ul>
            </li>
            <li><a class="nav-link" href="timeline.html"><i data-feather="sliders"></i><span>Timeline</span></a></li>
            <li class="menu-header">Maps</li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="map"></i><span>Google
                  Maps</span></a>
              <ul class="dropdown-menu">
                <li><a href="gmaps-advanced-route.html">Advanced Route</a></li>
                <li><a href="gmaps-draggable-marker.html">Draggable Marker</a></li>
                <li><a href="gmaps-geocoding.html">Geocoding</a></li>
                <li><a href="gmaps-geolocation.html">Geolocation</a></li>
                <li><a href="gmaps-marker.html">Marker</a></li>
                <li><a href="gmaps-multiple-marker.html">Multiple Marker</a></li>
                <li><a href="gmaps-route.html">Route</a></li>
                <li><a href="gmaps-simple.html">Simple</a></li>
              </ul>
            </li>
            <li><a class="nav-link" href="vector-map.html"><i data-feather="map-pin"></i><span>Vector
                  Map</span></a></li>
            <li class="menu-header">Pages</li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="user-check"></i><span>Auth</span></a>
              <ul class="dropdown-menu">
                <li><a href="auth-login.html">Login</a></li>
                <li><a href="auth-register.html">Register</a></li>
                <li><a href="auth-forgot-password.html">Forgot Password</a></li>
                <li><a href="auth-reset-password.html">Reset Password</a></li>
                <li><a href="subscribe.html">Subscribe</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="alert-triangle"></i><span>Errors</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="errors-503.html">503</a></li>
                <li><a class="nav-link" href="errors-403.html">403</a></li>
                <li><a class="nav-link" href="errors-404.html">404</a></li>
                <li><a class="nav-link" href="errors-500.html">500</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i data-feather="anchor"></i><span>Other
                  Pages</span></a>
              <ul class="dropdown-menu">
                <li><a class="nav-link" href="create-post.html">Create Post</a></li>
                <li><a class="nav-link" href="posts.html">Posts</a></li>
                <li><a class="nav-link" href="profile.html">Profile</a></li>
                <li><a class="nav-link" href="contact.html">Contact</a></li>
                <li><a class="nav-link" href="invoice.html">Invoice</a></li>
              </ul>
            </li>
            <li class="dropdown">
              <a href="#" class="menu-toggle nav-link has-dropdown"><i
                  data-feather="chevrons-down"></i><span>Multilevel</span></a>
              <ul class="dropdown-menu">
                <li><a href="#">Menu 1</a></li>
                <li class="dropdown">
                  <a href="#" class="has-dropdown">Menu 2</a>
                  <ul class="dropdown-menu">
                    <li><a href="#">Child Menu 1</a></li>
                    <li class="dropdown">
                      <a href="#" class="has-dropdown">Child Menu 2</a>
                      <ul class="dropdown-menu">
                        <li><a href="#">Child Menu 1</a></li>
                        <li><a href="#">Child Menu 2</a></li>
                      </ul>
                    </li>
                    <li><a href="#"> Child Menu 3</a></li>
                  </ul>
                </li>
              </ul>
            </li>
          </ul>
        </aside>
      </div>
      <!-- Main Content -->
      <div class="main-content">
        <section class="section">
          
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h4>
                    Chat Bot 
                    <button class="btn btn-dark modal-btn mr-2" data-trgt="#tree-modal" id="openModal">Tree Progress</button>
                    <button class="btn btn-danger" id="clear-all">Clear All</button>
                  </h4>
                  <div class="card-header-form">
                    <form>
                      <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search">
                        <div class="input-group-btn">
                          <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
                <div class="card-body">
                  <div class="ab-main-section">
                      <div class="ab-row-section">
                        <div class="ab-button-section mb-3" id="add-btn-0">
                          <div class="row mb-2">
                            <div class="col-md-12 p-0">
                              <label>First Message</label>
                              <textarea class="form-control first_message"></textarea>
                            </div>
                          </div>
                          <div class="row">
                            <div class="col-md-2 p-0">
                              <span class="ab-button-add" data-level="0">Add</span>
                            </div>
                            <div class="col-md-10 p-0">
                              <div class="ab-button-row scroll-hide">
                                  <!-- data -->
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <hr>
                      <div id="form-divs" class="ab-data-section py-1 px-4 bg-blue-grey text-white">
                      
                      </div>
                  </div>
                  <div class="d-flex justify-content-end mt-2">
                    <button class="btn btn-primary" id="submit">Submit</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
      <footer class="main-footer">
        <div class="footer-left">
          <a href="templateshub.net">Templateshub</a></a>
        </div>
        <div class="footer-right">
        </div>
      </footer>
    </div>
  </div>

  <!-- modal -->
  <div class="modal" id="tree-modal">
        <span class="close closeModal">&times;</span>
        <div class="modal-content scroll-hide">
            <div class="modal-body">
                <h1>Your Tree</h1>
                <div id="tree-section">
                  
                </div>
                 
            </div>
        </div>
    </div>

    <!-- media attachments -->
    <div id="mediaModal" class="modal">
      <span class="close closeModal">&times;</span>
      <div class="modal-content scroll-hide">
          <div class="modal-body">
              <h1>Your Media</h1>
              <div id="media-section" class="border">
              <div class="container-fluid">
                <div class="row p-3">
                    <div class="col-md-2 text-center">
                        <label class="image-container" for="radioOption1">
                            <img src="http://localhost/kt-api/assets/img/logo.png" alt="Image 1" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption1">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption2">
                            <img src="http://localhost/kt-dev/assets/img/kt-logo.png" alt="Image 2" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption2">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption3">
                            <img src="http://localhost/kt-api/assets/img/logo.png" alt="Image 1" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption3">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption4">
                            <img src="http://localhost/kt-dev/assets/img/kt-logo.png" alt="Image 2" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption4">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption5">
                            <img src="http://localhost/kt-api/assets/img/logo.png" alt="Image 1" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption5">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption6">
                            <img src="http://localhost/kt-dev/assets/img/kt-logo.png" alt="Image 2" class="gallery-image">
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption6">
                            </div>
                          </label>
                    </div>
                    <!-- Repeat for more images -->
                </div>
            </div>
              </div>
                
          </div>
      </div>
  </div>
    
  <!-- media attachments -->
    <div id="fileModal" class="modal">
      <span class="close closeModal">&times;</span>
      <div class="modal-content scroll-hide">
          <div class="modal-body">
              <h1>Your Files</h1>
              <div id="media-section" class="border">
              <div class="container-fluid">
                <div class="row p-3">
                    <div class="col-md-2 text-center">
                        <label class="image-container" for="radioOption1">
                            <h6>logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption1">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption2">
                            <h6>kt-logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption2">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption3">
                            <h6>logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption3">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption4">
                            <h6>kt-logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption4">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption5">
                            <h6>logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="logo.png" id="radioOption5">
                            </div>
                          </label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="image-container"  for="radioOption6">
                            <h6>kt-logo.png</h6>
                            <div class="radio-container mt-2">
                                <input type="radio" class="radio-file" name="fileRadio" value="kt-logo.png" id="radioOption6">
                            </div>
                          </label>
                    </div>
                    <!-- Repeat for more images -->
                </div>
            </div>
              </div>
                
          </div>
      </div>
  </div>
  <!-- clone data -->
  <div id="clone-data" class="d-none">
    <span class="ab-button"></span>
    <div class="col-md-12 clone-message">
      <label class="text-white">Message</label>
      <textarea class="form-control message"></textarea>
    </div>
    <div class="form" data-level="">
      <h6 class="heading">
          
      </h6>
      <div class="row">
          <div class="form-group col-md-3">
              <label class="text-white">Command</label>
              <input type="text" class="form-control command">
          </div>
          <div class="form-group col-md-3">
              <label class="text-white">Type</label>
              <select name="" class="form-control msg-type">
                  <option value="0">Selcted</option>
                  <option value="text">Text</option>
                  <option value="media">Media</option>
                  <option value="file">File</option>
                  <option value="sub menu">Submenu</option>
                  <option value="forwarder">Forwarder</option>
                  <option value="live chat">Live Chat</option>
                  <option value="live agent">Live Agent</option>
                  <option value="api call">Api Call</option>
              </select>
          </div>
      </div>
    </div>
    <div class="ab-button-section mb-3" id="add-btn-clone-0">
        <div class="row">
          <div class="col-md-2 p-0">
            <span class="ab-button-add" data-level="0">Add</span>
          </div>
          <div class="col-md-10 p-0">
            <div class="ab-button-row scroll-hide">
                <!-- data -->
            </div>
          </div>
        </div>
    </div>
    <div id="on-change-text-field">
          <div class="form-group col-md-3">
            <label class="text-white">Text</label>
            <input type="text" class="form-control text">
          </div>
    </div>
    <div id="on-change-media-field">
          <div class="form-group col-md-3">
            <label class="text-white">Media</label>
            <div class="form-control">
              <a href="#">Not Selected</a>
            </div>
            <input type="hidden" value="" class="form-control media">
          </div>
    </div>
    <div id="on-change-file-field">
          <div class="form-group col-md-3">
            <label class="text-white">File</label>
            <div class="form-control">
              <a href="#">Not Selected</a>
            </div>
            <input type="hidden" value="" class="form-control file">
          </div>
    </div>
    <div id="on-change-forwarder-field">
      <div class="form-group col-md-3">
        <label class="text-white">Forwarder</label>
        <select class="form-control forwarder">
          
        </select>
      </div>
      <div class="form-group col-md-3">
        <label class="text-white">Forwarder Text</label>
        <input type="text" class="form-control forwarder-text">
      </div>
    </div>
    <div id="on-change-liveagent-field">
      <div class="form-group col-md-3">
        <label class="text-white">Agent from Department</label>
        <select class="form-control department">
          
        </select>
      </div>
    </div>
    <div id="on-change-apicall-field">
      <div class="form-group col-md-3">
          <label class="text-white">Your Api</label>
          <input type="text" class="form-control api">
      </div>
      <div class="form-group col-md-3">
          <label class="text-white">Message For Api</label>
          <input type="text" class="form-control api-message">
      </div>

    </div>
  </div>
  <!-- General JS Scripts -->
  <script src="assets/js/app.min.js"></script>
  <!-- JS Libraies -->
  <script src="assets/bundles/apexcharts/apexcharts.min.js"></script>
  <!-- Page Specific JS File -->
  <script src="assets/js/page/index.js"></script>
  <!-- Template JS File -->
  <script src="assets/js/scripts.js"></script>
  <!-- Custom JS File -->
  <script src="assets/js/custom.js"></script>
  
</body>

<script>
    
    let forwarderDropdown = [{id:1,name:'forw 1'},{id:2,name:'forw 2'}];
    let departmentDropdown = [{id:1,name:'dept 1'},{id:2,name:'dept 2'}];
    let editData = @json($data ?? 'addition');

    if(editData != 'addition')
    {
      localStorage.setItem('aiTree', JSON.stringify(editData));
    }


    function forwarderOptions(selected = null) {
        const defaultOption = '<option value="">Select an option</option>';
        return defaultOption + forwarderDropdown.map(option => `<option ${selected == option.id ? 'selected' : ''} value="${option.id}">${option.name}</option>`).join('');
    }
    function departmentOptions(selected = null) {
        const defaultOption = '<option value="">Select an option</option>';
        return defaultOption + departmentDropdown.map(option => `<option ${selected == option.id ? 'selected' : ''} value="${option.id}">${option.name}</option>`).join('');
    }
    $("#on-change-forwarder-field .forwarder").html(forwarderOptions());
    $("#on-change-liveagent-field .department").html(departmentOptions());
    
    renderHtmlByObject();
    updateTreeChart();
    function validateNode(formData)
    { 
      if(formData.reply_type == "sub menu")
      {
        if(formData.message == null)
        {
          return false; 
        }
      }
      if(formData.command == null)
      {
        return false; 
      }
      if(formData.reply_type == null || formData.reply_type == 0)
      {
        return false; 
      }
      if(formData.reply_type == "text")
      {
        if(formData.text == null)
        {
          return false; 
        }
      }
      if(formData.reply_type == "media")
      {
        if(formData.media == null)
        {
          return false; 
        }
      }
      if(formData.reply_type == "file")
      {
        if(formData.file == null)
        {
          return false; 
        }
      }
      if(formData.reply_type == "forwarder")
      {
        if(formData.forwarder == null || formData.forwarder == 0)
        {
          return false; 
        }
        if(formData.forwarder_text == null)
        {
          return false; 
        }
      }
      if(formData.reply_type == "live agent")
      {
        if(formData.department == null || formData.department == 0)
        {
          return false; 
        }
      }
      if(formData.reply_type == "api call")
      {
        if(formData.api == null)
        {
          return false; 
        }
        if(formData.api_message == null)
        {
          return false; 
        }
      }
      return true;
    }
    function updateTreeChart() {
      var data = JSON.parse(localStorage.getItem('aiTree')) || {};
      function traverseAndRender(obj) {
          for (const key in obj) {
              const item = obj[key];
              // Check if the item is an object with a 'name' property
              if (typeof item === 'object' && item !== null && key != "form_data") {
                  const isComplete = validateNode(item.form_data);
                  const htmlParent = `<div class="node-parent" id="${'trp-'+item.parents}"></div>`;
                  const htmlNode = `<div class="tree-node" id="${'trn-'+item.level}">
                                      <div class="info ${isComplete ? 'bg-blue-grey':'bg-danger'}" data-level="${item.level}">
                                        <div class="form-value-row">
                                          <span>Level: </span>
                                          <span>${item.level}</span>
                                        </div>
                                        <div class="form-value-row">
                                          <span>Command: </span>
                                          <span>${item.form_data.command}</span>
                                        </div>
                                        <div class="form-value-row">
                                          <span>Type: </span>
                                          <span>${item.form_data.reply_type}</span>
                                        </div>
                                        <div class="detail">
                                          ${item.form_data.message ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Message</b></div>
                                            <div>${item.form_data.message}</div>
                                          </div>` : ''}
                                          ${item.form_data.text ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Text</b></div>
                                            <div>${item.form_data.text}</div>
                                          </div>` : ''}
                                          ${item.form_data.media ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Media</b></div>
                                            <div>${item.form_data.media}</div>
                                          </div>` : ''}
                                          ${item.form_data.file ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>File</b></div>
                                            <div>${item.form_data.file}</div>
                                          </div>` : ''}
                                          ${item.form_data.forwarder ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Forwarder</b></div>
                                            <div>${item.form_data.forwarder}</div>
                                          </div>` : ''}
                                          ${item.form_data.forwarder_text ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Forwarder Text</b></div>
                                            <div>${item.form_data.forwarder_text}</div>
                                          </div>` : ''}
                                          ${item.form_data.department ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Live Agent</b></div>
                                            <div>${item.form_data.department}</div>
                                          </div>` : ''}
                                          ${item.form_data.api ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Api</b></div>
                                            <div>${item.form_data.api}</div>
                                          </div>` : ''}
                                          ${item.form_data.api_message ? `<div class="border p-1 my-2 form-value-row">
                                            <div><b>Api Text</b></div>
                                            <div>${item.form_data.api_message}</div>
                                          </div>` : ''}
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button class="btn btn-sm btn-dark direct-add">View</button>
                                          </div>
                                      </div>
                                    </div>`;

                  if (item.parent == 0) {
                      const childExists = $('#tree-section').children().length;
                      if(childExists == 0)
                      {
                        $('#tree-section').append(htmlParent);
                      }
                      $('#trp-'+item.parents).append(htmlNode);
                  } else {
                      const childExists = $('#trn-'+item.parents).children().length;
                      if(childExists == 1)
                      {
                        $('#trn-'+item.parents).append(htmlParent);
                      }
                      $('#trp-'+item.parents).append(htmlNode);
                  }

                  // Create a unique ID for the current item and set it on the node element
                  // const itemId = `${item.level}`;
                  // $nodeElement.attr('id', 'trp-'+itemId);

                  // Recursively call the function for nested objects
                  traverseAndRender(item);
              }
          }
      }

      // Clear the tree-section div before rendering
      $('#tree-section').empty();
      
      // Render the HTML into the document
      traverseAndRender(data);
    }


    function updateTreeDataArray(level,form_data = null)
    {
      
      // Parse `aiTree` from localStorage and ensure it's an object
      let myData = JSON.parse(localStorage.getItem('aiTree')) || {};

      // Example input: `btnIds` array
      const btnIds = level.toString().split('-').map(Number); // Example: [2, 3, 2, 6, 4, 3]

      // Recursive function to build the nested structure
      function buildNestedObject(obj, keys, currentLevel) {
          let current = obj;

          // Keep track of the upperLevel
          let upperLevel = 0;
          let upperLevels = 0;

          keys.forEach((key, index) => {
              // Define latestData object with parent reference
              const latestData = {
                  form_data: form_data ? form_data : {
                    level: null,
                    message: null,
                    command: null,
                    reply_type: null,
                    text: null,
                    media: null,
                    file: null,
                    forwarder: null,
                    forwarder_text: null,
                    department: null,
                    api: null,
                    api_message: null,
                  },
                  level: currentLevel,
                  parent: upperLevel, // Set parent to upperLevel
                  parents: upperLevels == 0 ? 0 : upperLevels // Set parent to upperLevel
              };

              // If it's the last key, assign the latestData
              if (index === keys.length - 1) {
                  current[key] = {
                                  ...current[key], // Merge existing data
                                  ...latestData // Merge latestData (only overrides the keys in latestData)
                                };;
              } else {
                  // If the key does not exist, create an empty object
                  current[key] = current[key] || {};
                  current = current[key];
              }

              // Update upperLevel for the next iteration
              upperLevel = key; // Set upperLevel to the current level
              upperLevels = upperLevels == 0 ? key : upperLevels+'-'+key; // Set upperLevel to the current level
          });

          return obj
      }

      // Build the nested object with `btnIds` array
      myData = buildNestedObject(myData, btnIds, level);
      // Save the updated structure back to localStorage
      localStorage.setItem('aiTree', JSON.stringify(myData));
      updateTreeChart();

    }
    // after deleting some element it will reorder the nested object and it's value like parent/parents
    function reorderObjectKeys(obj) {
      var obj = JSON.parse(localStorage.getItem('aiTree')) || {};
      if (typeof obj !== 'object' || obj === null) {
          return obj;
      }

      const stack = [{ current: obj, path: [] }];
      const result = {};

      while (stack.length > 0) {
          const { current, path } = stack.pop();
          const keys = Object.keys(current)
              .map(Number) // Convert keys to numbers for sorting
              .sort((a, b) => a - b);

          let newKey = 1;
          keys.forEach((key) => {
              const newPath = path.concat(newKey);
              const newLevel = path.concat(newKey);
              const newParent = newLevel.length >= 2 ? newLevel[newLevel.length - 2] : 0;
              const newParents = newLevel.slice(0, newLevel.length - 1);
              
              let target = result;

              // Navigate to the correct location in the result object using the path
              for (let i = 0; i < newPath.length - 1; i++) {
                  target = target[newPath[i]] = target[newPath[i]] || {};
              }

               // If the current[key] is an object and not null, set the level property and push to the stack
              if (typeof current[key] === 'object' && current[key] !== null && !Array.isArray(current[key])) {
                  target[newPath[newPath.length - 1]] = {
                      ...current[key],
                      level: newLevel.join('-'),
                      parent: newParent,
                      parents:  newParents.length > 0 ? newParents.join("-") : 0,
                  };
                  stack.push({ current: current[key], path: newPath });
              } else {
                  // If it's not an object, directly assign the value
                  target[newPath[newPath.length - 1]] = current[key];
              }

              newKey++;
          });
      }
      localStorage.setItem('aiTree', JSON.stringify(result));
    }

    // this will make html again if object changes
    function renderHtmlByObject(){
      var obj = JSON.parse(localStorage.getItem('aiTree')) || {};
      
      var firstMessage = $(".first_message").val()??"";
      $(".ab-row-section").html("");
      $("#form-divs").html("");
      if(obj === null || Object.keys(obj).length === 0)
      {
        var buttonRowData = `<div class="ab-button-section mb-3" id="add-btn-0">
                              <div class="row mb-2">
                                <div class="col-md-12 p-0">
                                  <label>First Message</label>
                                  <textarea class="form-control first_message">${firstMessage}</textarea>
                                </div>
                              </div>
                              <div class="row">
                                <div class="col-md-2 p-0">
                                  <span class="ab-button-add" data-level="0">Add</span>
                                </div>
                                <div class="col-md-10 p-0">
                                  <div class="ab-button-row scroll-hide">
                                      <!-- data -->
                                  </div>
                                </div>
                              </div>
                            </div>`;
        $(".ab-row-section").html(buttonRowData);
        return true;
      }
      
      function render(obj){
        
        for (const key in obj) {
          const item = obj[key];
          // Check if the item is an object with a 'name' property
          if (typeof item === 'object' && item !== null && key != "form_data") {
              const isComplete = validateNode(item.form_data);
              //////////////////////////////////////////////////this is for button section/////////////////////////////////////////////////////
              var htmlNode = `<span class="ab-button ${isComplete ? 'is-complete':''}" data-tgt="#data-${item.level}" data-level="${item.level}" id="btn-${item.level}">
                            <div>${item.level} ${item.form_data.command ? `<div>Command: ${item.form_data.command}</div>` : ''}</div>
                            <span class="cross-button"> ×</span>
                          </span>
                          `;
        
              
              if (($("#add-btn-"+item.parents).length) == 0) {
                var htmlRow = `<div class="ab-button-section mb-3" id="add-btn-${item.parents}">`
                                if(item.parents == 0)
                                {
                                  htmlRow +=`<div class="row mb-2">
                                              <div class="col-md-12 p-0">
                                                <label>First Message</label>
                                                <textarea class="form-control first_message">${firstMessage}</textarea>
                                              </div>
                                            </div>`;
                                }

                htmlRow +=  `<div class="row">
                                    <div class="col-md-2 p-0">
                                      <span class="ab-button-add" data-level="${item.parents}">Add</span>
                                    </div>
                                    <div class="col-md-10 p-0">
                                      <div class="ab-button-row scroll-hide">
                                          <!-- data -->
                                      </div>
                                    </div>
                                  </div>
                                </div>`;
                  
                  $(".ab-row-section").append(htmlRow);
              }
              $("#add-btn-"+item.parents+" .ab-button-row").append(htmlNode);
              //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
              
              //////////////////////////////////////////////////this is for data section////////////////////////////////////////////////////////
              var htmlDataDiv = `<div id="data-${item.level}" data-level="${item.level}" class="ab-data" style="display: none;">
                                  <div class="form" data-level="">
                                    <h6 class="heading">Entry Value For Level: ${item.level}</h6>
                                    <div class="row">`
                                    if(item.form_data.reply_type == 'sub menu')
                                        {
                                          htmlDataDiv += `<div class="col-md-12 clone-message">
                                                            <label class="text-white">Message</label>
                                                            <textarea class="form-control message">${item.form_data.message??'' }</textarea>
                                                          </div>`;
                                        }
                                        
              htmlDataDiv +=             `<div class="form-group col-md-3">
                                            <label class="text-white">Command</label>
                                            <input type="text" class="form-control command" value="${item.form_data.command??'' }">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label class="text-white">Type</label>
                                            <select name="" class="form-control msg-type">
                                                <option value="0">Selcted</option>
                                                <option ${item.form_data.reply_type == 'text' ? 'selected' : ''} value="text">Text</option>
                                                <option ${item.form_data.reply_type == 'media' ? 'selected' : ''} value="media">Media</option>
                                                <option ${item.form_data.reply_type == 'file' ? 'selected' : ''} value="file">File</option>
                                                <option ${item.form_data.reply_type == 'sub menu' ? 'selected' : ''} value="sub menu">Submenu</option>
                                                <option ${item.form_data.reply_type == 'forwarder' ? 'selected' : ''} value="forwarder">Forwarder</option>
                                                <option ${item.form_data.reply_type == 'live chat' ? 'selected' : ''} value="live chat">Live Chat</option>
                                                <option ${item.form_data.reply_type == 'live agent' ? 'selected' : ''} value="live agent">Live Agent</option>
                                                <option ${item.form_data.reply_type == 'api call' ? 'selected' : ''} value="api call">Api Call</option>
                                            </select>
                                        </div>`;
                                        if(item.form_data.reply_type == 'text')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3">
                                                            <label class="text-white">Text</label>
                                                            <input type="text" class="form-control text"  value="${item.form_data.text??''}">
                                                          </div>`;
                                        }
                                        if(item.form_data.reply_type == 'media')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3" id="file-${item.level}">
                                                            <label class="text-white">Media</label>
                                                            <div class="form-control">
                                                              <a href="#">${item.form_data.media}</a>
                                                            </div>
                                                            <input type="hidden" value="${item.form_data.media}" class="form-control media">
                                                          </div>`;
                                        }
                                        if(item.form_data.reply_type == 'file')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3" id="file-${item.level}">
                                                          <label class="text-white">File</label>
                                                          <div class="form-control">
                                                            <a href="#">${item.form_data.file}</a>
                                                          </div>
                                                          <input type="hidden" value="${item.form_data.file}" class="form-control file">
                                                        </div>`;
                                        }
                                        if(item.form_data.reply_type == 'forwarder')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3">
                                                            <label class="text-white">Forwarder</label>
                                                            <select class="form-control forwarder">
                                                              ${forwarderOptions(item.form_data.forwarder)}
                                                            </select>
                                                          </div>
                                                          <div class="form-group col-md-3">
                                                            <label class="text-white">Forwarder Text</label>
                                                            <input type="text" class="form-control forwarder-text" value="${item.form_data.forwarder_text??''}">
                                                          </div>`;
                                        }
                                        if(item.form_data.reply_type == 'live agent')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3">
                                                            <label class="text-white">Agent from Department</label>
                                                            <select class="form-control department">
                                                              ${departmentOptions(item.form_data.department)}
                                                            </select>
                                                          </div>`;
                                        }
                                        if(item.form_data.reply_type == 'api call')
                                        {
                                          htmlDataDiv += `<div class="form-group col-md-3">
                                                              <label class="text-white">Your Api</label>
                                                              <input type="text" class="form-control api" value="${item.form_data.api??''}">
                                                          </div>
                                                          <div class="form-group col-md-3">
                                                              <label class="text-white">Message For Api</label>
                                                              <input type="text" class="form-control api-message" value="${item.form_data.api_message??''}">
                                                          </div>`;
                                        }
              htmlDataDiv +=        `</div>
                                    </div>
                                  </div>`;
              $("#form-divs").append(htmlDataDiv);
              //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
              render(item);
          }
        }
      }
      render(obj);
      $(".ab-button-section").hide();
      $('#add-btn-0').show();
    }


    
      $(document).on('click', '.ab-button-add', function() {
          let level = $(this).data('level');
          let buttonCount = $(this).closest(".ab-button-section").find(".ab-button-row").children().length + 1;
          // Clone the button from #clone-data
          let button = $('#clone-data .ab-button').clone();
          // Update the button's text and id
          // Check if level is zero
          if (level === 0) {
              var targetId = buttonCount;
              // If level is zero, set the text and id without the level prefix
              button
                  .html(`<div>${buttonCount}</div>`) // Only show the button count
                  .attr('data-tgt', "#data-" + targetId) // Use buttonCount as ID
                  .attr('data-level',buttonCount) // Remove data-level attribute
                  .attr('id',"btn-"+buttonCount); // Remove data-level attribute
          } else {
              var targetId = level + "-" + buttonCount;
              // If level is not zero, set the text and id with the level prefix
              button
                  .html(`<div>${targetId}</div>`)
                  .attr('data-tgt', "#data-" + targetId)
                  .attr('data-level', level+"-"+buttonCount) // Set the data-level attribute
                  .attr('id', "btn-"+level+"-"+buttonCount); // Set the data-level attribute
          }
          var closeSpan = $('<span>')
            .text(' ×') // Add cross symbol
            .addClass('cross-button'); // Add a class for styling (optional)
            
          button.append(closeSpan);
          // $('#clone-data .ab-button-section').attr('id',"row-" + parseInt(level + 1));
          // $('#clone-data .form').attr('data-level',"row-" + parseInt(level + 1));
          // Append the cloned button to the next .ab-button-row
          $(this).closest(".ab-button-section").find(".ab-button-row").append(button);


          // now need to add div according to button
          let formDiv = $("#clone-data .form").clone();
          // Create a new div with the same ID as the button
          let newDiv = $('<div></div>')
              .attr('id', "data-"+targetId) // Set the same ID as the button
              .attr('data-level', targetId) // Set the same ID as the button
              .html(formDiv) // Set the text inside the new div
              .css('display', 'none') // Set the display to none
              .addClass('ab-data'); // Add the class 'ab-data'
          
          // Append the new div to the desired container (for example, the same button section)
          $(this).closest(".ab-main-section").find(".ab-data-section").append(newDiv);
          $("#data-"+targetId + " .form .heading").html("Entry Value For Level: "+targetId);
          $('.ab-button-row').scrollLeft(4000);

          updateTreeDataArray(targetId);
      });
      
      $(document).on('click', '.ab-button', function() {
          let dataId = $(this).data('tgt'); // Get the target ID from the clicked button
          let level = $(this).data('level'); // Get the target ID from the clicked button
          const btnIds = level.toString().split('-').map(Number);
          
          // this to add open class to it
          $(".ab-button").removeClass('open');
          let btnStr = null;
          $.each(btnIds, function(index, btnId) {
              if (btnStr === null) {
                  btnStr += btnId;
              }
              else
              {
                btnStr += "-"+btnId;
              }
              $('#btn-' + btnStr).addClass('open');

          });

          $(".ab-button-section").hide();
          $('#add-btn-0').show();
          $('#add-btn-clone-0').show();
          let btnSectionStr = null;
          $.each(btnIds, function(index, btnId) {
              if (btnSectionStr === null) {
                  btnSectionStr += btnId;
              }
              else
              {
                btnSectionStr += "-"+btnId;
              }
              $('#add-btn-' + btnSectionStr).show();

          });
          // Hide all ab-data elements
          $('.ab-data').hide(); // Change to hide() to hide the elements
          // Show the specific ab-data element corresponding to the clicked button
          $(dataId).show();
      });
      // on type menu change
      fileValue = "";
      $(document).on('change', '.msg-type', function() {
          let parent = $(this).closest(".ab-data").data('level');
         
          let selectedValue = $(this).val()

          $("#data-"+parent+" .clone-message").remove();
          $(this).closest('.row').children().slice(2).remove();
          // text
          if(selectedValue == "text")
          {
              let rowSection = $('#clone-data #on-change-text-field').children().clone();
              $(this).closest('.row').append(rowSection);
          }
          // media
          if(selectedValue == "media")
          {
              let rowSection = $('#clone-data #on-change-media-field').children().clone();
              rowSection.attr("id","file-"+parent)
              fileValue = parent;
              $(this).closest('.row').append(rowSection);
              $("#mediaModal").addClass('show');
              $("#mediaModal .modal-content").addClass('show');
          }
          // file
          if(selectedValue == "file")
          {
              let rowSection = $('#clone-data #on-change-file-field').children().clone();
              rowSection.attr("id","file-"+parent)
              fileValue = parent;
              $(this).closest('.row').append(rowSection);
              $("#fileModal").addClass('show');
              $("#fileModal .modal-content").addClass('show');
          }
          // file
          if(selectedValue == "forwarder")
          {
              let rowSection = $('#clone-data #on-change-forwarder-field').children().clone();
              $(this).closest('.row').append(rowSection);
          }
          // live chat
          if(selectedValue == "live agent")
          {
              let rowSection = $('#clone-data #on-change-liveagent-field').children().clone();
              $(this).closest('.row').append(rowSection);
          }
          // live chat
          if(selectedValue == "api call")
          {
              let rowSection = $('#clone-data #on-change-apicall-field').children().clone();
              $(this).closest('.row').append(rowSection);
          }
          // sub menu
          if(selectedValue == "sub menu")
          {
              let rowSection = $('#clone-data .ab-button-section').clone();
              rowSection.attr('id',"add-btn-"+parent);
              $(".ab-row-section").append(rowSection);
              $("#add-btn-"+parent+" .ab-button-add").attr("data-level",parent);
              
              let formMessage = $('#clone-data .clone-message').clone();
              $("#data-"+parent+" .row").prepend(formMessage);
          }
          else
          {
              $("#add-btn-"+parent).remove();
          }
      });
      $(document).on('click', '.image-container', function() {
          const radioValue = $(this).find("input[name='fileRadio']:checked").val();
          $("#file-"+fileValue+" a").text(radioValue);
          $("#file-"+fileValue+" input").val(radioValue);
          $("input[name='fileRadio']").prop("checked", false);
          $('.modal').removeClass("show");
          $('.modal .modal-content').removeClass("show");
          getFormDataAndUpdate(fileValue);
      });
      $(document).on('click', '#submit', function() {
        var first_message = $(".first_message").val();
        var data = JSON.parse(localStorage.getItem('aiTree')) || {};
        var submitData = {first_message: first_message, data: data};

        if(first_message == "" || first_message == null)
        {
          alert("Please enter first message");
          return;
        }
        if(($(".is-complete").length + 1) != $(".ab-button").length || $(".ab-button").length == 1)
        {
          alert("Some data is missing. please check tree progress");
          return;
        }
        $(".first_message").val("");
        localStorage.setItem('aiTree',JSON.stringify({}));
        renderHtmlByObject();
        updateTreeChart();
        console.log(submitData);
      });
      $(document).on('click', '#clear-all', function() {
        $(".first_message").val("");
        localStorage.setItem('aiTree',JSON.stringify({}));
        renderHtmlByObject();
        updateTreeChart();
         
      });
      $(document).on('click', '.direct-add', function() {
          const dataLevel = $(this).closest(".info").data('level');
          const btnIds = dataLevel.toString().split('-').map(Number);
          
          // this to add open class to it
          $(".ab-button").removeClass('open');
          let btnStr = null;
          $.each(btnIds, function(index, btnId) {
              if (btnStr === null) {
                  btnStr += btnId;
              }
              else
              {
                btnStr += "-"+btnId;
              }
              console.log('#btn-' + btnStr);
              $('#btn-' + btnStr).addClass('open');

          });

          $(".ab-button-section").hide();
          $('#add-btn-0').show();
          $('#add-btn-clone-0').show();
          let btnSectionStr = null;
          $.each(btnIds, function(index, btnId) {
              if (btnSectionStr === null) {
                  btnSectionStr += btnId;
              }
              else
              {
                btnSectionStr += "-"+btnId;
              }
              $('#add-btn-' + btnSectionStr).show();

          });

          $('.ab-data').hide();
          $("#data-"+dataLevel).show();

          $(this).closest('.modal').removeClass("show");
          $(this).closest('.modal .modal-content').removeClass("show");
          // alert(dataLevel);
      });

      // Trigger the function on change or keypress
      $(document).on('input', '.ab-data input, .ab-data textarea, .ab-data select', function() {
          var dataLevel = $(this).closest('.ab-data').data('level');
          
          getFormDataAndUpdate(dataLevel);
      });
      $(document).on('click', '.cross-button', function(e) {
        let myData = JSON.parse(localStorage.getItem('aiTree')) || {};
        const level = $(this).closest(".ab-button").data('level');
        const keys = level.toString().split('-').map(Number);

        unsetNestedKey(myData, keys);
        localStorage.setItem('aiTree', JSON.stringify(myData));
        reorderObjectKeys();
        renderHtmlByObject();
        updateTreeChart();
        // alert(level);
        e.stopPropagation();
      });
      function getFormDataAndUpdate(dataLevel)
      {
        // Find the current `.ab-data` section by `data-level`
        var $abDataSection = $('.ab-data').filter(function() {
            return $(this).data('level') === dataLevel;
        });

        // Create an object to store all values
        var form_data = {
            level: dataLevel,
            message: $abDataSection.find('.message').val() ? $abDataSection.find('.message').val() : null,
            command: $abDataSection.find('.command').val() ? $abDataSection.find('.command').val() : null,
            reply_type: $abDataSection.find('.msg-type').val() ? $abDataSection.find('.msg-type').val() : null,
            text: $abDataSection.find('.text').val() ? $abDataSection.find('.text').val() : null,
            media: $abDataSection.find('.media').val() ? $abDataSection.find('.media').val() : null,
            file: $abDataSection.find('.file').val() ? $abDataSection.find('.file').val() : null,
            forwarder: $abDataSection.find('.forwarder').val() ? $abDataSection.find('.forwarder').val() : null,
            forwarder_text: $abDataSection.find('.forwarder-text').val() ? $abDataSection.find('.forwarder-text').val() : null,
            department: $abDataSection.find('.department').val() ? $abDataSection.find('.department').val() : null,
            api: $abDataSection.find('.api').val() ? $abDataSection.find('.api').val() : null,
            api_message: $abDataSection.find('.api-message').val() ? $abDataSection.find('.api-message').val() : null
        };

        if(form_data.command)
        {
          $("#btn-"+dataLevel+" div").html('').append(`${dataLevel}<div>Command: ${form_data.command}</div>`);
        }
        else
        {          
          $("#btn-"+dataLevel+" div").html('').append(`${dataLevel}`);
        }
        var isComplete = validateNode(form_data);
        if(isComplete)
        {
          $("#btn-"+dataLevel).addClass("is-complete")
        }
        else
        {
          $("#btn-"+dataLevel).removeClass("is-complete")
        }
        // Log the collected data or pass it to any other function
        updateTreeDataArray(dataLevel, form_data);
        // alert(JSON.stringify(formData)); // For testing, displays the object as a string
      }
      function unsetNestedKey(obj, keys) {
        if (!obj || !Array.isArray(keys) || keys.length === 0) {
            return;
        }

        let current = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            // Navigate through the object using the keys
            if (current[keys[i]] !== undefined) {
                current = current[keys[i]];
            } else {
                // Exit if the path doesn't exist
                return;
            }
        }

        // Delete the last key in the path
        delete current[keys[keys.length - 1]];
    }
</script>

  <!-- modal js -->
<script>
  $(document).on('click', '.modal-btn', function() {
      const trgt = $(this).data('trgt');
      $(trgt).addClass("show");
      $(trgt+" .modal-content").addClass("show");
  });
  $(document).on('click', '.closeModal', function() {
    $(this).closest('.modal').removeClass("show");
    $(this).closest('.modal .modal-content').removeClass("show");
  });

  // Listen for escape key press
  document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") {
        $('.modal').removeClass("show");
        $('.modal .modal-content').removeClass("show");
      }
  });

</script>
</html>