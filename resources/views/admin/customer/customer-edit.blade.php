@extends('admin.layouts.base')

@section('title', 'Customer')

@section('content')
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">
                        Overview
                    </div>
                    <h2 class="page-title">
                        Edit Customer
                    </h2>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-12">
                    <div class="card card-primary">
                        <div class="card-body">
                            <form enctype="multipart/form-data" method="POST"
                                action="{{ route('admin.customer.update', $customers->id) }}">
                                @csrf
                                @method('PUT')
                                <fieldset class="form-fieldset container-xl">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">NAME</label>
                                        <input type="text" class="form-control" name="name" id="name" placeholder="Name"
                                            value="{{$customers->name}}"/>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">ADDRESS</label>
                                        <input type="text" class="form-control" name="address" id="address"
                                            placeholder="Address" value="{{$customers->address}}" />
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">PHONE NUMBER</label>
                                        <input type="text" class="form-control" name="phone_number" id="phone_number"
                                            placeholder="Phone number" value="{{$customers->phone_number}}" />
                                    </div>
                                    <div class="text-end">
                                        <button type="submit"
                                            class="btn btn-primary justify-content-right">Submit</button>
                                    </div>
                                </fieldset>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
