<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $employee->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 0 auto;
            background: white;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 12px;
            color: #666;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        .info-item {
            padding: 8px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .info-item label {
            font-weight: bold;
            display: block;
            font-size: 10px;
            color: #666;
            margin-bottom: 3px;
        }
        .info-item span {
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background: #f9f9f9;
        }
        .signature {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .signature-item {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid #333;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>PAYSLIP</h1>
            <p>{{ $employee->name }} - {{ $employee->employee_code }}</p>
            <p>Period: {{ $period->year }}/{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</p>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <label>Employee Name</label>
                <span>{{ $employee->name }}</span>
            </div>
            <div class="info-item">
                <label>Employee Code</label>
                <span>{{ $employee->employee_code }}</span>
            </div>
            <div class="info-item">
                <label>Department</label>
                <span>{{ $employee->department ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Position</label>
                <span>{{ $employee->position ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Bank Name</label>
                <span>{{ $employee->bank_name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>Bank Account</label>
                <span>{{ $employee->bank_account ?? '-' }}</span>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Salary</td>
                    <td class="text-right">{{ number_format($item->base_salary, 2) }}</td>
                </tr>
                @foreach($item->allowances ?? [] as $allowance)
                <tr>
                    <td>{{ $allowance['label'] }}</td>
                    <td class="text-right">{{ number_format($allowance['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td>Overtime</td>
                    <td class="text-right">{{ number_format($item->overtime_pay, 2) }}</td>
                </tr>
                @foreach($item->deductions ?? [] as $deduction)
                <tr>
                    <td>{{ $deduction['label'] }}</td>
                    <td class="text-right">-{{ number_format($deduction['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td>Gross Pay</td>
                    <td class="text-right">{{ number_format($item->gross_pay, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Net Pay</td>
                    <td class="text-right">{{ number_format($item->net_pay, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        <table>
            <thead>
                <tr>
                    <th>Working Days</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Overtime Hours</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">{{ $item->working_days }}</td>
                    <td class="text-center">{{ $item->present_days }}</td>
                    <td class="text-center">{{ $item->absent_days }}</td>
                    <td class="text-center">{{ $item->overtime_hours }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="signature">
            <div class="signature-item">
                <p>Employee Signature</p>
            </div>
            <div class="signature-item">
                <p>Authorized Signature</p>
            </div>
        </div>
        
        <div class="footer">
            <p>Generated on {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>