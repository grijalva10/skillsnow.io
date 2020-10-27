@extends('admin.layouts.master')
@section("title") Edit Slide - Dashboard
@endsection
@section('content')
<div class="page-header">
    <div class="page-header-content header-elements-md-inline">
        <div class="page-title d-flex">
            <h4><i class="icon-circle-right2 mr-2"></i>
                <span class="font-weight-bold mr-2">Editing Slide ID {{ $slide->id }} from {{ $slide->promo_slider->name }} </span>
            </h4>
            <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
        </div>
    </div>
</div>
<div class="content">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <legend class="font-weight-semibold text-uppercase font-size-sm">
                    <i class="icon-image2 mr-2"></i> Slide Details
                </legend>
                <form action="{{ route('admin.updateSlide') }}" method="POST" id="slideForm" class="mt-3" enctype="multipart/form-data">
                    <input type="hidden" class="form-control form-control-lg" name="id" value="{{ $slide->id }}" required>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Slide Name:</label>
                        <div class="col-lg-9">
                            <input type="text" class="form-control form-control-lg" name="name" placeholder="Slide Name" required value="{{ $slide->name }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-3 col-form-label">Slide Image:</label>
                        <div class="col-lg-9">
                            <img src="{{ substr(url("/"), 0, strrpos(url("/"), '/')) }}{{ $slide->image }}" alt="Image"
                            width="160" style="border-radius: 0.275rem;">
                            <img class="slider-preview-image hidden"/>
                            <div class="uploader">
                                <input type="file" class="form-control-uniform" name="image" accept="image/x-png,image/gif,image/jpeg" onchange="readURL(this);">
                            </div>
                        </div>
                    </div>
                    <div class="form-group row" id="restaurantURL">
                        <label class="col-lg-3 col-form-label">Link To:</label>
                        <div class="col-lg-9">
                            <select class="form-control form-control-lg select" name="url" id="urlInput">
                                <option></option>
                                @foreach($restaurants as $restaurant)
                                    <option value="stores/{{ $restaurant->slug }}">{{ $restaurant->name }}</option>
                                @endforeach
                            </select>
                            <div id="urlHelpBlockContainer" class="hidden">
                                <p class="help-block text-muted mb-0">Your URL will be: </p>
                                <span class="help-block" id="baseURL"></span><strong><span id="appendURL"></span></strong>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-md btn-primary" id="enterCustomURL" type="button">Enter Custom URL</button>
                    <div class="form-group row hidden" id="customURL">
                       <label class="col-lg-3 col-form-label">Custom URL:</label>
                        <div class="col-lg-9">
                            <input type="url" class="form-control form-control-lg" name="customUrl" id="customUrl" placeholder="Enter your custom URL">
                            <span class="help-text">Enter full URL with http:// or https://</span>
                        </div>
                    </div>
                    @csrf
                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">
                        UPDATE
                        <i class="icon-database-insert ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function readURL(input) {
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $('.slider-preview-image')
                    .removeClass('hidden')
                    .attr('src', e.target.result)
                    .width(120)
                    .height(120);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    $(function () {
        $("#showPassword").click(function (e) { 
            $("#passwordInput").attr("type", "text");
        });
        $('.select').select2({
             placeholder: "Select a store",
             allowClear: true
        });
    
       $('.form-control-uniform').uniform();
    
        $("#addSlide").click(function(event) {
            $("#slideForm").removeClass('hidden');
            $("#noSlidesContainer").remove();
            $(this).remove();
        });
    
        $("#urlInput").on("change paste keyup", function() {
            $("#urlHelpBlockContainer").removeClass('hidden');
            $("#appendURL").html($(this).val());
        });
    
         let URL = "{{ url("/") }}";
         URL = URL.substring(0, URL.lastIndexOf("/") + 1);
         $("#baseURL").html(URL);

         /*handle custom url click */ 
         $('#enterCustomURL').click(function(event) {
            $(this).hide();
            $('#customURL').removeClass('hidden');
            $('#restaurantURL').addClass('hidden');
            $('#urlInput').removeAttr('required');
            $('#customUrl').attr('required', 'required');
         });
    });
</script>
@endsection