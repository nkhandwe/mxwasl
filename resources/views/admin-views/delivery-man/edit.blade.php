@extends('layouts.admin.app')

@section('title','Update delivery-man')

@push('css_or_js')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/css/intlTelInput.css" integrity="sha512-gxWow8Mo6q6pLa1XH/CcH8JyiSDEtiwJV78E+D+QP0EVasFs8wKXq16G8CLD4CJ2SnonHr4Lm/yY2fSI2+cbmw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title text-break">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
                </span>
                <span>{{translate('messages.update')}} {{translate('messages.deliveryman')}}</span>
            </h1>
        </div>
        <!-- End Page Header -->
        <form action="{{route('admin.delivery-man.update',[$delivery_man['id']])}}" method="post" class="js-validate"
                enctype="multipart/form-data">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-title-icon"><i class="tio-user"></i></span>
                        <span>
                            {{translate('general_information')}}
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.first')}} {{translate('messages.name')}}</label>
                                        <input type="text" value="{{$delivery_man['f_name']}}" name="f_name"
                                                class="form-control" placeholder="{{translate('messages.first_name')}}"
                                                required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.last')}} {{translate('messages.name')}}</label>
                                        <input type="text" value="{{$delivery_man['l_name']}}" name="l_name"
                                                class="form-control" placeholder="{{translate('messages.last_name')}}"
                                                required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.email')}}</label>
                                        <input type="email" value="{{$delivery_man['email']}}" name="email" class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                                required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.deliveryman')}} {{translate('messages.type')}}</label>
                                        <select name="earning" class="form-control deliverymantype" required>
                                            <option value="1" {{$delivery_man->earning?'selected':''}}>{{translate('messages.freelancer')}}</option>
                                            <option value="0" {{$delivery_man->earning?'':'selected'}}>{{translate('messages.salary_based')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.zone')}}</label>
                                        <select name="zone_id" class="form-control">
                                        @foreach(\App\Models\Zone::all() as $zone)
                                            @if(isset(auth('admin')->user()->zone_id))
                                                @if(auth('admin')->user()->zone_id == $zone->id)
                                                    <option value="{{$zone->id}}" {{$zone->id == $delivery_man->zone_id?'selected':''}}>{{$zone->name}}</option>
                                                @endif
                                            @else
                                            <option value="{{$zone->id}}" {{$zone->id == $delivery_man->zone_id?'selected':''}}>{{$zone->name}}</option>
                                            @endif
                                        @endforeach
                                        </select>
                                    </div>
                                </div>
                                 <div class="col-sm-6 commissionData displaynone">
                                    <div  class="row">
                                        <div class="col-md-6">
                                             <div class="form-group mb-0">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('messages.Commission')}}</label>
                                           <input type="text" oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');" name="commission" class="form-control" placeholder="{{translate('messages.Commission')}}" value="{{$delivery_man['commission']}}"  required>
                                            </div>
                                        </div>
                                        <div class="col-md-6 ">
                                            <label>Commission Type</label>
                                            @if($delivery_man->commission_type == 'percent')
                                            <input type="checkbox" name="commission_type" value="percent" checked> Percent <br>
                                            <input type="checkbox" name="commission_type" value="fixed"> Fixed
                                            @else
                                            <input type="checkbox" name="commission_type" value="percent" > Percent <br>
                                            <input type="checkbox" name="commission_type" value="fixed" checked> Fixed
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="d-flex flex-column h-100">
                                <label>{{translate('messages.deliveryman')}} {{translate('messages.image')}} <small class="text-danger">* ( {{translate('messages.ratio')}} 1:1 )</small></label>
                                <center class="py-3 my-auto">
                                    <img class="img--100 rounded" id="viewer"
                                            src="{{asset('storage/app/public/delivery-man').'/'.$delivery_man['image']}}"
                                            onerror='this.src="{{asset('/public/assets/admin/img/admin.png')}}"'
                                            alt="delivery-man image"/>
                                </center>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                            accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                    <label class="custom-file-label" for="customFileEg1">{{translate('messages.choose')}} {{translate('messages.file')}}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="row g-3">
                                <div class="col-sm-6 col-lg-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.identity')}} {{translate('messages.type')}}</label>
                                        <select name="identity_type" class="form-control">
                                            <option
                                                value="passport" {{$delivery_man['identity_type']=='passport'?'selected':''}}>
                                                {{translate('messages.passport')}}
                                            </option>
                                            <option
                                                value="driving_license" {{$delivery_man['identity_type']=='driving_license'?'selected':''}}>
                                                {{translate('messages.driving')}} {{translate('messages.license')}}
                                            </option>
                                            <option value="nid" {{$delivery_man['identity_type']=='nid'?'selected':''}}>{{translate('messages.nid')}}
                                            </option>
                                            <option
                                                value="store_id" {{$delivery_man['identity_type']=='store_id'?'selected':''}}>
                                                {{translate('messages.store')}} {{translate('messages.id')}}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-12">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.identity')}} {{translate('messages.number')}}</label>
                                        <input type="text" name="identity_number" value="{{$delivery_man['identity_number']}}"
                                                class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} DH-23434-LS"
                                                required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="row g-3">
                                <div class="col-md-6 pb-0">
                                    <div class="row g-2">
                                        <div class="col-12 pb-0">
                                            <div class="form-group mb-0">
                                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.identity')}} {{translate('messages.images')}} : </label>
                                            </div>
                                        </div>
                                        @foreach(json_decode($delivery_man['identity_image'],true) as $img)
                                        <div class="col-6 spartan_item_wrapper size--sm">
                                            <img class="rounded border" src="{{asset('storage/app/public/delivery-man').'/'.$img}}">
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.update')}} {{translate('messages.identity')}} {{translate('messages.image')}}</label>
                                    <div>
                                        <div class="row g-2 mt-0" id="coba"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-header-icon">
                            <i class="tio-user"></i>
                        </span>
                        <span>
                            {{translate('messages.account')}} {{translate('messages.information')}}
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-4">
                            <div class="form-group mb-0">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.phone')}}</label>
                                <input type="text" id="phone" name="phone" value="{{$delivery_man['phone']}}" class="form-control"
                                        placeholder="{{ translate('messages.Ex:') }} 017********"
                                        required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="js-form-message form-group mb-0">
                                <label class="input-label" for="signupSrPassword">{{translate('messages.password')}}</label>

                                <div class="input-group input-group-merge">
                                    <input type="password" class="js-toggle-password form-control" name="password" id="signupSrPassword" placeholder="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" aria-label="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" 
                                    data-msg="Your password is invalid. Please try again."
                                    data-hs-toggle-password-options='{
                                    "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                    "defaultClass": "tio-hidden-outlined",
                                    "showClass": "tio-visible-outlined",
                                    "classChangeTarget": ".js-toggle-passowrd-show-icon-1"
                                    }'>
                                    <div class="js-toggle-password-target-1 input-group-append">
                                        <a class="input-group-text" href="javascript:;">
                                            <i class="js-toggle-passowrd-show-icon-1 tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="js-form-message form-group mb-0">
                                <label class="input-label" for="signupSrConfirmPassword">{{translate('messages.confirm_password')}}</label>
                                <div class="input-group input-group-merge">
                                <input type="password" class="js-toggle-password form-control" name="confirmPassword" id="signupSrConfirmPassword" placeholder="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" aria-label="{{translate('messages.password_length_placeholder',['length'=>'6+'])}}" 
                                        data-msg="Password does not match the confirm password."
                                        data-hs-toggle-password-options='{
                                        "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                        "defaultClass": "tio-hidden-outlined",
                                        "showClass": "tio-visible-outlined",
                                        "classChangeTarget": ".js-toggle-passowrd-show-icon-2"
                                        }'>
                                    <div class="js-toggle-password-target-2 input-group-append">
                                        <a class="input-group-text" href="javascript:;">
                                        <i class="js-toggle-passowrd-show-icon-2 tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn--container justify-content-end mt-3">
                <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
            </div>
        </form>
    </div>
<style type="text/css">
    .displaynone{
        display: none !important;
    }
</style>
@endsection

@push('script_2')
<script>
    $(document).on('ready', function () {
                  let deliverymantype=$('.deliverymantype').val();
            if (deliverymantype == 1) {
                $('.commissionData').removeClass('displaynone');
            }else{
                $('.commissionData').addClass('displaynone');
            }
            $('.deliverymantype').change(function(){
                let val=$(this).val();
                if (val == 1) {
                $('.commissionData').removeClass('displaynone');
            }else{
                $('.commissionData').addClass('displaynone');
            }
            });
            $("input:checkbox").on('click', function() {
              var $box = $(this);
              if ($box.is(":checked")) {
                var group = "input:checkbox[name='" + $box.attr("name") + "']";
                $(group).prop("checked", false);
                $box.prop("checked", true);
              } else {
                $box.prop("checked", false);
              }
            });
      // INITIALIZATION OF SHOW PASSWORD
      // =======================================================
      $('.js-toggle-password').each(function () {
        new HSTogglePassword(this).init()
      });


      // INITIALIZATION OF FORM VALIDATION
      // =======================================================
      $('.js-validate').each(function() {
        $.HSCore.components.HSValidation.init($(this), {
          rules: {
            confirmPassword: {
              equalTo: '#signupSrPassword'
            }
          }
        });
      });
    });
  </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput.min.js" integrity="sha512-QMUqEPmhXq1f3DnAVdXvu40C8nbTgxvBGvNruP6RFacy3zWKbNTmx7rdQVVM2gkd2auCWhlPYtcW2tHwzso4SA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/intlTelInput-jquery.min.js" integrity="sha512-hkmipUFWbNGcKnR0nayU95TV/6YhJ7J9YUAkx4WLoIgrVr7w1NYz28YkdNFMtPyPeX1FrQzbfs3gl+y94uZpSw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/js/utils.min.js" integrity="sha512-lv6g7RcY/5b9GMtFgw1qpTrznYu1U4Fm2z5PfDTG1puaaA+6F+aunX+GlMotukUFkxhDrvli/AgjAu128n2sXw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script> -->
    <link rel="shortcut icon" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/img/flags.png" type="image/x-icon">
    <link rel="shortcut icon" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.13/img/flags@2x.png" type="image/x-icon">
    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });

        @php($country=\App\Models\BusinessSetting::where('key','country')->first())
        var phone = $("#phone").intlTelInput({
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/8.4.6/js/utils.js",
            nationalMode: true,
            autoHideDialCode: true,
            autoPlaceholder: "ON",
            dropdownContainer: document.body,
            formatOnDisplay: true,
            hiddenInput: "phone",
            initialCountry: "{{$country?$country->value:auto}}",
            placeholderNumberType: "MOBILE",
            separateDialCode: true
        });
    </script>

    <script src="{{asset('public/assets/admin/js/spartan-multi-image-picker.js')}}"></script>
    <script type="text/javascript">
        $(function () {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'identity_image[]',
                maxCount: 5,
                rowHeight: '100px',
                groupClassName: 'col-6 spartan_item_wrapper size--sm',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{asset('public/assets/admin/img/400x400/img2.jpg')}}',
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function (index, file) {

                },
                onRenderedPreview: function (index) {

                },
                onRemoveRow: function (index) {

                },
                onExtensionErr: function (index, file) {
                    toastr.error('Please only input png or jpg type file', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function (index, file) {
                    toastr.error('File size too big', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });
    </script>
    <script>
        $('#reset_btn').click(function(){
            $('#viewer').attr('src','{{asset('storage/app/public/delivery-man')}}/{{$delivery_man['image']}}');
            $("#coba").empty().spartanMultiImagePicker({
                fieldName: 'identity_image[]',
                maxCount: 5,
                rowHeight: '120px',
                groupClassName: 'col-6 spartan_item_wrapper size--sm',
                maxFileSize: '',
                placeholderImage: {
                    image: '{{asset('public/assets/admin/img/400x400/img2.jpg')}}',
                    width: '100%'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function (index, file) {

                },
                onRenderedPreview: function (index) {

                },
                onRemoveRow: function (index) {

                },
                onExtensionErr: function (index, file) {
                    toastr.error('{{translate('messages.please_only_input_png_or_jpg_type_file')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function (index, file) {
                    toastr.error('{{translate('messages.file_size_too_big')}}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        })

    </script>
@endpush
