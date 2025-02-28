@extends('admin.layouts.base')

@section('title', 'Daily Sales')

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
                            Create Daily Sales
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
                                <form enctype="multipart/form-data" method="POST" action="{{ route('admin.ds.store') }}">
                                    @csrf
                                    <fieldset class="form-fieldset container-xl">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">DATE</label>
                                            <input type="date" class="form-control" name="date" id="date"
                                                placeholder="Date" />
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">TOTAL</label>
                                            <input type="text" class="form-control" name="total" id="total"
                                                placeholder="Total" />
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
