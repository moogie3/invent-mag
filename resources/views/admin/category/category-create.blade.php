@extends('admin.layouts.base')

@section('title', 'Category')

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
                            Create Category
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
                                    action="{{ route('admin.category.store') }}">
                                    @csrf
                                    <fieldset class="form-fieldset container-xl">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">NAME</label>
                                            <input type="text" class="form-control" name="name" id="name"
                                                placeholder="Name" />
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">DESC</label>
                                            <input type="text" class="form-control" name="description" id="description"
                                                placeholder="Description" />
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
