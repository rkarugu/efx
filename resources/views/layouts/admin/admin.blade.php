<!DOCTYPE html>
<html>
@include('admin.includes.head')
<style type="text/css">
    .btn-success, .btn-primary, .btn-outline-primary {
        background-color: #00d4aa !important;
        border-color: #00d4aa !important;
        color: #000 !important;
        font-weight: 600 !important;
    }

    .btn-success:hover, .btn-primary:hover {
        background-color: #00b894 !important;
        border-color: #00b894 !important;
        color: #000 !important;
    }

    .btn-outline-primary:hover {
        color: #000 !important;
        background-color: #00b894 !important;
        border-color: #00b894 !important;
    }

    /**
    Flex Classes
     */
    .d-flex {
        display: flex !important;
    }

    .justify-content-between {
        justify-content: space-between !important;
    }

    .justify-content-end {
        justify-content: end !important;
    }

    .justify-content-center {
        justify-content: center !important;
    }

    .align-items-center {
        align-items: center !important;
    }

    .box-header-flex {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
    }

    .flex-wrap {
        flex-wrap: wrap !important;
    }

    .flex-column {
        flex-direction: column !important;
    }

    /**
    Additional utility classes
     */
    .action-button-div {
        display: inline-block !important;
    }

    .action-button-div a {
        margin-right: 6px;
    }

    .action-button-div > form {
        margin-right: 6px;
    }

    .session-message-container {
        margin-bottom: 10px;
    }

    .btn-icon {
        display: inline-block;
        margin-right: 5px;
    }

    .form-text {
        font-weight: 500 !important;
        color: rgba(0, 0, 0, 0.5);
    }

    .routes .nav-tab-active {
        color: #f00;
    }

    .routes .tab-content {
        display: none;
    }

    .routes .tab-content.active {
        display: block;
    }

    .modal-full {
        min-width: 80%;

    }

    .transparent-btn {
        background-color: transparent !important;
        border: none !important;
    }

    .modal-fullscreen {
        min-width: 90%;
    }

    .modal-full .modal-content {
        margin: 5vh 5vw 5vh 5vw;
        border-top: 2px solid #00d4aa;
    }

    .loader-overlay {
        position: absolute;
        width: 100%;
        height: 100%;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        display: none;
    }

    .loading-message {
        display: block;
        margin-left: 15px;
    }

    .custom-loader {
        border: 5px solid #f3f3f3;
        border-radius: 50%;
        border-top: 5px solidrgba(0, 255, 157, 0.7);
        border-bottom: 5px solidrgb(15, 254, 186);
        width: 50px;
        height: 50px;
        -webkit-animation: spin 2s linear infinite;
        animation: spin 2s linear infinite;
    }

    @-webkit-keyframes spin {
        0% {
            -webkit-transform: rotate(0deg);
        }
        100% {
            -webkit-transform: rotate(360deg);
        }
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }

    [v-cloak] {
        display: none;
    }

    .mt-20 {
        margin-top: 20px !important;
    }

    .mt-10 {
        margin-top: 10px !important;
    }

    .mt-5 {
        margin-top: 5px !important;
    }

    .ml-20 {
        margin-left: 20px !important;
    }

    .mr-20 {
        margin-right: 20px !important;
    }

    .ml-12 {
        margin-left: 12px !important;
    }

    .mr-12 {
        margin-right: 12px !important;
    }

    .mb-12 {
        margin-bottom: 12px !important;
    }

    .mb-20 {
        margin-bottom: 20px !important;
    }

    .flex-grow-1 {
        flex-grow: 1 !important;
    }

    .row.zero {
        margin: 0 !important;
        padding: 0 !important;
    }

    .box-title-tagline {
        font-size: 16px;
        margin-top: 4px;
    }

    .box-title {
        font-weight: 600 !important;
    }

    .control-label.required::after {
        content: '*';
        color: green;
    }

    .main-header .sidebar-toggle:before {
        content: "" !important;
    }

</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
@yield('uniquepagestyle')
@stack('styles')
<style type="text/css">
    .action_crud span {
        float: left;
        padding-left: 3px;
    }

    .error {
        color: red;
    }

    .no-padding-h {
        margin-top: 10px;
    }
</style>
<style>
    /* Style for notification container */
    #notification-container {
        position: fixed;
        top: 60px;
        right: 20px;
        max-width: 300px;
        z-index: 1000;
    }

    /* Style for individual notifications */
    .notification {
        background-color: #f8d7da;
        border-color:rgba(245, 198, 203, 0.93);
        color: #721c24;
        padding: 10px 15px;
        border: 1px solid transparent;
        border-radius: 4px;
        margin-bottom: 10px;
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Style for the close button */
    .notification .close-btn {
        cursor: pointer;
        padding: 2px 5px;
        border-radius: 50%;
        background-color: #f5c6cb;
        color:rgb(4, 1, 1);
        font-weight: bold;
        font-size: 12px;
        border: none;
        outline: none;
        margin-left: 10px;
    }
</style>
<body class="hold-transition skin-black sidebar-mini">
<div class="wrapper">
    @include('admin.includes.header')
    @include('admin.includes.sidebar')
    <div class="content-wrapper">
        <section class="content-header">
            @include('admin.includes.breadcum')
        </section>
        <section class="content">
            <button style="display: none;" id="notification-btn">Notification  sound btn</button>
            <ul id="notifications">
                <!-- Notifications will be appended here -->
            </ul>
        
            <!-- Notification container -->
            <div id="notification-container"></div>
            @yield('content')
            {{ $slot ?? '' }}
        </section>
    </div>
    @include('admin.includes.footer')
    <div class="control-sidebar-bg"></div>
</div>
@include('admin.includes.script')
@yield('uniquepagescript')
</body>
</html>
