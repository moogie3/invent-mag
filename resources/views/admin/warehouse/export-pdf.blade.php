<!DOCTYPE html>
<html>
<head>
    <title>Warehouse List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Warehouse List</h1>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Address</th>
                <th>Description</th>
                <th>Is Main</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warehouses as $warehouse)
                <tr>
                    <td>{{ $warehouse->name }}</td>
                    <td>{{ $warehouse->address }}</td>
                    <td>{{ $warehouse->description }}</td>
                    <td>{{ $warehouse->is_main ? 'Yes' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
