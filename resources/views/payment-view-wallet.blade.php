@php($currency=\App\Models\BusinessSetting::where(['key'=>'currency'])->first()->value)

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <title>
        @yield('title')
    </title>
    <!-- SEO Meta Tags-->
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <!-- Viewport-->
    <meta name="_token" content="{{csrf_token()}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon and Touch Icons-->
    <link rel="shortcut icon" href="favicon.ico">
    <!-- Font -->
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">

    <style>
        .stripe-button-el {
            display: none !important;
        }

        .razorpay-payment-button {
            display: none !important;
        }
    </style>
    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js">
    </script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
    {{--stripe--}}
    <script src="https://polyfill.io/v3/polyfill.min.js?version=3.52.1&features=fetch"></script>
    <script src="https://js.stripe.com/v3/"></script>
    {{--stripe--}}
</head>
<!-- Body-->

<body class="toolbar-enabled">
    <!-- Page Content-->
    <div class="container pb-5 mb-2 mb-md-4">
        <div class="row">
            <div class="col-md-12 mb-5 pt-5">
                <center class="">
                    <h1>Payment method</h1>
                </center>
            </div>
            @php($order=\App\Models\Order::find(session('order_id')))
            <section class="col-lg-12">
                <div class="checkout_details mt-3">
                    <div class="row">
                        @php($config=\App\CentralLogics\Helpers::get_business_settings('razor_pay'))
                        @if($config['status'])
                        <div class="col-md-6 mb-4" style="cursor: pointer">
                            <div class="card">
                                <div class="card-body pt-1" style="height: 70px">
                                    @php($config=\App\CentralLogics\Helpers::get_business_settings('razor_pay'))
                                    <form
                                        action="{{route('wallet.payment-razor')}}?customer_id={{$data['customer_id']}}&amount={{$data['amount']}}&name={{$data['name']}}&email={{$data['email']}}&callback={{$data['callback']}}"
                                        method="POST">
                                        @csrf
                                        <!-- Note that the amount is in paise = 50 INR -->
                                        <!--amount need to be in paisa-->
                                        <script src="https://checkout.razorpay.com/v1/checkout.js"
                                            data-key="{{ Config::get('razor.razor_key') }}"
                                            data-amount="{{$data['amount']*100}}"
                                            data-buttontext="Add {{$data['amount']}} {{\App\CentralLogics\Helpers::currency_code()}}"
                                            data-name="{{\App\Models\BusinessSetting::where(['key'=>'business_name'])->first()->value}}"
                                            data-description="Add to wallet"
                                            data-image="{{asset('storage/app/public/business/'.\App\Models\BusinessSetting::where(['key'=>'logo'])->first()->value)}}"
                                            data-prefill.name="{{$data['name']}}"
                                            data-prefill.email="{{$data['email']}}" data-theme.color="#ff7529">
                                        </script>
                                    </form>
                                    <button class="btn btn-block click-if-alone" type="button"
                                        onclick="{{\App\CentralLogics\Helpers::currency_code()=='INR'?"
                                        $('.razorpay-payment-button').click()":"toastr.error('Your currency is not
                                        supported by Razor Pay.')"}}">
                                        <img width="100" src="{{asset('public/assets/admin/img/razorpay.png')}}" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif

                        @php($config=\App\CentralLogics\Helpers::get_business_settings('paytm'))
                        @if(isset($config) && $config['status'])
                        <div class="col-md-6 mb-4" style="cursor: pointer">
                            <div class="card">
                                <div class="card-body" style="height: 100px">
                                    <a class="btn btn-block click-if-alone"
                                        href="{{route('wallet.paytm-payment')}}?customer_id={{$data['customer_id']}}&amount={{$data['amount']}}&name={{$data['name']}}&email={{$data['email']}}&callback={{$data['callback']}}">
                                        <img style="max-width: 150px; margin-top: -10px"
                                            src="{{asset('public/assets/admin/img/paytm.png')}}" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- JS Front -->
    <script src="{{asset('public/assets/admin')}}/js/custom.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/bootstrap.min.js"></script>

    {!! Toastr::message() !!}



    <script>
        setTimeout(function () {
        $('.razorpay-payment-button').hide();
    }, 10)
    </script>
    <script>
        function click_if_alone() {
        let total = $('.checkout_details .click-if-alone').length;
        if (Number.parseInt(total) == 1) {
            $('.click-if-alone')[0].click()
            $('.checkout_details').html('<div class="text-center"><h1>{{translate('messages.Redirecting_to_the_payment_page')}}......</h1></div>');
        }
    }
    @if(!Session::has('toastr::messages'))
        click_if_alone();
    @endif
    </script>
</body>

</html>