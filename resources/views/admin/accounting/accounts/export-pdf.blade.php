<!DOCTYPE html>
<html>
<head>
    <title>Chart of Accounts</title>
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
    <h1>Chart of Accounts</h1>
    <table>
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Level</th>
            </tr>
        </thead>
        <tbody>
            @php
                $accountList = function ($accounts, $level = 0) use (&$accountList) {
                    foreach ($accounts as $account) {
                        echo '<tr>';
                        echo '<td>' . $account->code . '</td>';
                        echo '<td>' . str_repeat('-', $level) . ' ' . $account->name . '</td>';
                        echo '<td>' . $account->type . '</td>';
                        echo '<td>' . $account->level . '</td>';
                        echo '</tr>';
                
                        if ($account->children->isNotEmpty()) {
                            $accountList($account->children, $level + 1);
                        }
                    }
                };
            @endphp
            {{ $accountList($accounts) }}
        </tbody>
    </table>
</body>
</html>
