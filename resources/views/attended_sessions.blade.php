<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attended Sessions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Attended Sessions</h1>
    <table>
        <thead>
            <tr>
                <th>Patient Name</th>
                <th>Therapist Name</th>
                <th>Treatment Name</th>
                <th>Attended Sessions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sessionList as $session)
                <tr>
                    <td>{{ $session['patient_name'] }}</td>
                    <td>{{ $session['therapist_name'] }}</td>
                    <td>{{ $session['treatment_name'] }}</td>
                    <td>{{ $session['attended_session'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
