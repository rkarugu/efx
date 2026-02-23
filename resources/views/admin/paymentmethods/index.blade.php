@extends('layouts.admin.admin')

@section('content')

    <!-- Main content -->
    <section class="content">
        <!-- Small boxes (Stat box) -->
        <div class="box box-primary">
            <div class="box-header with-border no-padding-h-b">
                <div class="box-header-flex">
                    <h3 class="box-title"> Payment Methods </h3>

                    @if(isset($permission[$pmodule.'___add']) || $permission == 'superadmin')
                        <div align="right"><a href="{!! route($model.'.create')!!}" class="btn btn-success">Add {!! $title !!}</a></div>
                    @endif
                </div>
            </div>

            <div class="box-body">
                @include('message')

                <div class="col-md-12 no-padding-h">
                    <table class="table table-bordered table-hover" id="">
                        <thead>
                        <tr>
                            <th width="10%">S.No.</th>
                            <th>Title</th>
                            <th>Payment Provider</th>
                            <th>Use For Payments</th>
                            <th>Use For Receipts</th>
                            <th>Use For POS</th>
                            <th>Use As Channel</th>
                            <th>GL Account</th>
                            <th>Branch</th>
                            <th width="20%" class="noneedtoshort">Action</th>

                            <!--th style = "width:10%" class = "noneedtoshort">Date</th-->

                        </tr>
                        </thead>
                        <tbody>

                        @foreach($lists as $list)

                            <tr>
                                <td>{{ $loop ->iteration }}</td>

                                <td>{!! $list->title !!}</td>
                                <td>{!! $list->provider?->name ?? '-' !!}</td>
                                <td> {{ $list->use_for_payments ? 'Yes' : 'No' }} </td>
                                <td> {{ $list->use_for_receipts ? 'Yes' : 'No' }} </td>
                                <td> {{ $list->use_in_pos ? 'Yes' : 'No' }} </td>
                                <td> {{ $list->use_as_channel ? 'Yes' : 'No' }} </td>
                                <td>{!! $list->paymentGlAccount?$list->paymentGlAccount->account_code:'' !!}</td>
                                <td>{{ $list->branch?->name ?? '' }}</td>

                                <td class="action_crud">

                                    @if(isset($permission[$pmodule.'___edit']) || $permission == 'superadmin')
                                        <span>
                                                    <a title="Edit" href="{{ route($model.'.edit', $list->slug) }}"><img src="{!! asset('assets/admin/images/edit.png') !!}" alt="">
                                                    </a>
                                                    </span>

                                    @endif

                                    {{--                                                   @if(isset($permission[$pmodule.'___delete']) || $permission == 'superadmin')--}}
                                    {{--                                                     @if($list->slug != 'mpesa')--}}

                                    {{--                                                    <span>--}}
                                    {{--                                                    <form title="Trash" action="{{ URL::route($model.'.destroy', $list->slug) }}" method="POST">--}}
                                    {{--                                                    <input type="hidden" name="_method" value="DELETE">--}}
                                    {{--                                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">--}}
                                    {{--                                                    <button  style="float:left"><i class="fa fa-trash" aria-hidden="true"></i>--}}
                                    {{--                                                    </button>--}}
                                    {{--                                                    </form>--}}
                                    {{--                                                    </span>--}}
                                    {{--                                                     @endif--}}


                                    {{--                                                     @endif--}}


                                </td>


                            </tr>

                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>


    </section>

@endsection
