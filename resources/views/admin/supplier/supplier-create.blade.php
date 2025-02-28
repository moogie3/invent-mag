@extends('admin.layouts.base')

@section('title', 'Supplier')

@section('content')
    <div class="page-wrapper">
        <div class="page-header">
            <div class="container-xl">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="page-pretitle">
                            Overview
                        </div>
                        <h2 class="page-title">
                            Create Supplier
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-body">
            <div class="container-xl">
                <div class="row row-deck row-cards">
                    <div class="col-md-12">
                        <div class="card card-primary">
                            <div class="card-body">

                                <form enctype="multipart/form-data" method="POST"
                                    action="{{ route('admin.supplier.store') }}">
                                    @csrf
                                    <fieldset class="form-fieldset container-xl">
                                        <div class="row">
                                            <div class="col-md-1 mb-3">
                                                <label class="form-label">CODE</label>
                                                <input type="text" class="form-control" name="code" id="code"
                                                    placeholder="Code" />
                                            </div>
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">PHONENUMBER</label>
                                                <input type="text" class="form-control" name="phone_number"
                                                    id="phone_number" placeholder="Phone number" />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">NAME</label>
                                                <input type="text" class="form-control" name="name" id="name"
                                                    placeholder="Name" />
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">ADDRESS</label>
                                                <input type="text" class="form-control" name="address" id="address"
                                                    placeholder="Address" />
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">LOCATION</label>
                                                <select class="form-control" name="location" id="location">
                                                    <option>Select location</option>
                                                    <option value="IN">IN</option>
                                                    <option value="OUT">OUT</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">PAYMENT TERMS</label>
                                                <input type="text" class="form-control" name="payment_terms"
                                                    id="payment_terms" placeholder="Payment terms" />
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary">Submit</button>
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
