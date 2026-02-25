<!DOCTYPE html>
<html>
<head>
    <title>Supplier List</title>
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
    <h1>Supplier List</h1>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Address</th>
                <th>Phone Number</th>
                <th>Location</th>
                <th>Payment Terms</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->code }}</td>
                    <td>{{ $supplier->name }}</td>
                    <td>{{ $supplier->address }}</td>
                    <td>{{ $supplier->phone_number }}</td>
                    <td>{{ $supplier->location }}</td>
                    <td>{{ $supplier->payment_terms }}</td>
                    <td>{{ $supplier->email }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
