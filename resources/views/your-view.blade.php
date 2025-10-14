<!DOCTYPE html>
<html>
<head>
    <title>Invoice</title>
</head>
<body>
    <h1>Invoice</h1>
    <p><strong>Customer Name:</strong> {{ $customer_name }}</p>
    <p><strong>Date:</strong> {{ $date }}</p>
    <table border="1" width="100%" cellpadding="5">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['quantity'] }}</td>
                    <td>{{ $item['price'] }}</td>
                    <td>{{ $item['quantity'] * $item['price'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <h3>Total: ${{ $total }}</h3>
</body>
</html>
