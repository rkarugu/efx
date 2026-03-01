<?php
$logged_user_info = getLoggeduserProfile();
?>
<header class="main-header"> <!-- Logo -->
    <a href="{!! route('admin.dashboard') !!}" class="logo">
        <span class="logo-mini">
            <img src="{{ asset('assets/admin/images/logo.png') }}" alt="">
        </span>
        <span class="logo-lg" style="">
            <img src="{{ asset('assets/admin/images/logo.png') }}" alt="">
        </span>
    </a>
    <nav class="navbar navbar-static-top">
        <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
            <i class="fa fa-bars"></i>
            <span class="sr-only">Toggle navigation</span>
        </a>
        <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
{{--                <x-notifications></x-notifications>--}}
                <li id="theme-toggle-li">
                    <a href="#" id="theme-toggle-btn" title="Toggle Light/Dark Mode" onclick="return false;">
                        <i class="fas fa-sun" id="theme-toggle-icon"></i>
                    </a>
                </li>
                <li>
                    <a href="{!! route('users.get.change.profile.password') !!}" title="Change Password"><i class="fas fa-key"></i></a>
                </li>
                <li class="dropdown user user-menu">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                        <img src="{{ $logged_user_info->image && file_exists('uploads/users/thumb/' . $logged_user_info->image)
                            ? asset('uploads/users/thumb/' . $logged_user_info->image)
                            : asset('assets/userdefault.jpg') }}"
                            class="text user-image" alt="User Image">
                        <span class="hidden-xs">{!! ucfirst($logged_user_info->name) !!}</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li class="user-header">
                            @if ($logged_user_info->image && file_exists('uploads/users/thumb/' . $logged_user_info->image))
                                <img src="{{ asset('uploads/users/thumb/' . $logged_user_info->image) }}"
                                    class="text img-circle" alt="User Image">
                            @else
                                <img src="{{ asset('assets/userdefault.jpg') }}" alt="User">
                            @endif
                        </li>
                        <li class="user-footer">
                            <div class="pull-left">
                                <a title="My Profile" href="{!! route('admin.profile') !!}" class="btn btn-default btn-flat">
                                  <i class="fa fa-user" aria-hidden="true"></i>
                                </a>
                            </div>


                            <div class="pull-right">
                                <a title="Logout" href="{!! route('admin.logout') !!}" class="btn btn-default btn-flat">
                                  <i class="fa fa-sign-out" aria-hidden="true"></i>
                                </a>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
</header>
