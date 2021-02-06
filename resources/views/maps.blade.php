<!DOCTYPE html>
<html lang="en">


<body class="animsition">
    <div class="page-wrapper">
        <!-- HEADER MOBILE-->
        <!-- PAGE CONTAINER-->
        <div class="page-container">
            <!-- HEADER DESKTOP-->
            <!-- HEADER DESKTOP-->

            <!-- MAIN CONTENT-->
            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        @foreach($maps as $key => $map)
                        <div class="row">
                            <div class="col-md-12">
                                    <div style="max-width: 1002px; max-height: 668px; cursor: pointer; display:block;position:relative">
                                        <img src="https://wow.zamimg.com/images/wow/maps/eses/original/{{$key}}.jpg" alt="">
                                        @foreach($map['values']['coords'] as $coord)
                                            <div style="left:{{$coord[0]}}%;top:{{$coord[1]}}%;position:absolute;width:1px;height:1px;font-size:1px;z-index:5">
                                                <img src="{{asset('resources/images/pin-yellow.png')}}" style="position:relative;width:11px;height:11px;display:block">
                                            </div>
                                        @endforeach
                                </div>
                            </div>
                            <hr>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- END MAIN CONTENT-->
            <!-- END PAGE CONTAINER-->
        </div>

    </div>

</body>

</html>
<!-- end document-->
