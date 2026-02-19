<html>
    <title>Stock Take Sheet</title>

    <head>
        <style>
            html{
                margin:4px;
                padding:0;
            }
            .headr {font-size: 16px;}
            .makeborderbottom{           
            }
            th{
                padding:2px 4px;
                text-align:left;
            }
            td{               
                padding:2px 4px;
            }
            table *{
                font-size: 10px;
            }
            .page-break {
                page-break-after: always;
            }
          
        </style>

    </head>

    <body>
        
        @foreach ($bins as $bin)
            <div class="page-break">
                <b class="headr">
                    <?= strtoupper('STORE: '.@$freeze->getAssociateLocationDetail->location_name)?> 
                    <span style="margin-left:20px">
                        <?= strtoupper('BIN LOCATION: '.@$bin->title)?> 
                        </span>
                </b>
                <br>
                <b class="headr">
                    <span style="margin-left:20px">
                        <?= strtoupper('Stock Take Sheet '  )?> 
                        </span>
                </b>                              
                        <?php
                            $toLines= 0;
                        ?>
                    <main>
                        @foreach ($categories as $category)
                            @php
                                // Use pre-grouped data for O(1) lookup instead of O(n) filtering
                                $categoryItems = $itemsByBinAndCategory[$bin->uom_id][$category->id] ?? [];
                            @endphp
                            @if (count($categoryItems) > 0)
                            <h5 style="margin: 0px;">{{$category->category_description}}</h5>
                            <table  width="100%" cellspacing="0" border="1">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th colspan="7" style="text-align: center;">Dates</th>
                                    </tr>
                                    <tr>
                                        <th width="8%">CODE</th>
                                        <th width="52%">DESCRIPTION</th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                        <th width="8%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                            </tr>
                            @foreach ($categoryItems as $item)
                                <tr>
                                    <td>{{$item->stock_id_code}}</td>
                                    <td>{{$item->title}}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            @endforeach
                                </tbody>
                            </table>
                                
                            @endif
                    @endforeach
                    
                        <table  width="100%" cellspacing="0" border="1" style="border-top: 0px solid">
                            
                            <tr>
                                <td colspan="7"  style="padding-left:10px">TOTAl LINES : {{ $toLines }}</td>                     
                            </tr>
                            <tr>
                                <td colspan="7"  style="padding-left:10px;height: 70px">SIGNED BY : STORE ASSISTANT</td>                     
                            </tr>
                            <tr>
                                <td colspan="7"  style="padding-left:10px;height: 70px">SIGNED BY : SUPERVISOR</td>                     
                            </tr>
                            <tr>
                                <td colspan="7"  style="padding-left:10px;height: 70px">SIGNED BY : STOCK TAKE OBSERVER</td>                     
                            </tr>
                            
                        </table>
                        </main>
            </div>

        @endforeach


    </body>

</html>


