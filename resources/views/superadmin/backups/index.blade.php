@extends('superadmin.layouts.base')
@section('title', 'Backups')
@section('content')
<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle"><i class="ti ti-database"></i> System</div>
                    <h2 class="page-title">Backups</h2>
                    <div class="text-muted mt-1">System backup files.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow border-0 rounded-4">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Last Modified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($backups as $backup)
                            <tr>
                                <td><i class="ti ti-file-zip me-2 text-muted"></i>{{ $backup['name'] }}</td>
                                <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                <td class="text-muted">{{ \Carbon\Carbon::createFromTimestamp($backup['modified'])->format('M d, Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No backups found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endSection