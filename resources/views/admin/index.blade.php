<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Kainat Travels</title>
    <meta name="csrf-token" id="token" content="{{ csrf_token() }}">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="{{ asset('assets/css/app.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/iziToast.min.css') }}">
    <!-- Custom style CSS -->
    <link rel='shortcut icon' type='image/x-icon' href='{{ asset('assets/img/fav-logo.png') }}'/>
    <link rel="stylesheet" href="{{ asset('assets/bundles/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-timepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/bundles/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">
    <style>
        html{
            scroll-behavior: smooth !important;
        }
        .select2-container{
            width: 100%!important;
        }
    </style>
</head>

<body>
<div class="loader"></div>
<div id="app">
    @if (Auth::check())

        <main-app :user="{{ \App\Models\User::with('role','company')->find(Auth::id()) }}" app_url="{{ config('app.url') }}">
        </main-app>
    @else
        {{-- <test-app :user="false" app_url="{{ config('app.url') }}"></test-app> --}}
        <main-app :user="false" app_url="{{ config('app.url') }}"></main-app>
    @endif
</div>
<!-- General JS Scripts -->
<script src="{{ asset('assets/js/app.min.js') }}"></script>
<!-- JS Libraies -->
<script src="{{ asset('assets/bundles/apexcharts/apexcharts.min.js') }}"></script>
<!-- Page Specific JS File -->
<script src="{{ asset('assets/js/page/index.js') }}"></script>
<!-- Template JS File -->
<script src="{{ asset('assets/js/scripts.js') }}"></script>
<!-- Custom JS File -->
<script src="{{ asset('assets/js/custom.js') }}"></script>
<script src="{{ asset('assets/js/iziToast.min.js') }}"></script>
<script src="{{ asset('assets/js/select2.full.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap-timepicker.min.js') }}"></script>
<script src="{{ asset('assets/js/jquery.inputmask.bundle.min.js') }}"></script>
<script src="{{ asset('assets/bundles/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/bundles/datatables/DataTables-1.10.16/js/dataTables.bootstrap4.min.js') }}"></script>

{{-- Vue App JS --}}
<script>
    window.Laravel = {csrfToken: '{{ csrf_token() }}'}
</script>
<script src="{{ asset('js/app.js') }}"></script>
<script>
    $(document).ready(function () {
        $(document).on('show.bs.modal', '.modal', function () {
            $(this).appendTo('body');
        });
    })
</script>
<script src="{{ asset('assets/bundles/sweetalert/sweetalert.min.js') }}"></script>


</body>

</html>
