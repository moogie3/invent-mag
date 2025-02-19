@extends('admin.layouts.base')

@section('title', 'Category')

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
                        Category
                    </h2>
                </div>
                <div class="col-auto ms-auto">
                    <div class="btn-list">
                        <a href="{{ route('admin.category.create') }}" class="btn btn-primary d-none d-sm-inline-block">
                            <i class="ti ti-plus fs-4"></i>
                            Create Category
                        </a>
                    </div>
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
                        <div class="table-responsive" id="table-default">
                            <table class="table">
                                <thead>
                                    <tr class="dark">
                                        <th>no</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th style="width:180px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($categories as $category)
                                        <tr>
                                            <td>{{ $category->id }}</td>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $category->description }}</td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button href="{{ route('admin.category.edit', $category->id) }}" class="btn btn-secondary"><i class="ti ti-edit"></i></button>
                                                    <form method="POST" action="{{ route('admin.category.destroy', $category->id) }}">
                                                        @method('delete')
                                                        @csrf
                                                        <button type="submit" class="btn btn-danger"><i
                                                                class="ti ti-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
