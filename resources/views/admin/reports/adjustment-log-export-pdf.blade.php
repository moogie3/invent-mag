<!DOCTYPE html>
<html>
<head>
    <title>Adjustment Log</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Adjustment Log</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Product</th>
                <th>Type</th>
                <th>Quantity Before</th>
                <th>Quantity After</th>
                <th>Change</th>
                <th>Reason</th>
                <th>Adjusted By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($adjustments as $log)
                <tr>
                    <td>{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                    <td>{{ $log->product ? $log->product->name : 'Product Not Found' }}</td>
                    <td>{{ $log->adjustment_type }}</td>
                    <td>{{ $log->quantity_before }}</td>
                    <td>{{ $log->quantity_after }}</td>
                    @php
                        $change = $log->quantity_after - $log->quantity_before;
                    @endphp
                    <td>{{ $change }}</td>
                    <td>{{ $log->reason ?: 'N/A' }}</td>
                    <td>{{ $log->adjustedBy ? $log->adjustedBy->name : 'System' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No stock adjustments found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
