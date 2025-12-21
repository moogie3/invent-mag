<!DOCTYPE html>
<html>
<head>
    <title>Product List</title>
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
    <h1>Product List</h1>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Category</th>
                <th>Supplier</th>
                <th>Warehouse</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Price</th>
                <th>Selling Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $product)
                <tr>
                    <td>{{ $product->code }}</td>
                    <td>{{ $product->name }}</td>
                    <td>{{ $product->category->name }}</td>
                    <td>{{ $product->supplier->name }}</td>
                    <td>{{ $product->warehouse->name }}</td>
                    <td>{{ $product->unit->name }}</td>
                    <td>{{ $product->stock_quantity }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($product->price) }}</td>
                    <td>{{ \App\Helpers\CurrencyHelper::format($product->selling_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
