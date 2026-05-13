<!DOCTYPE html>
<html>
<head>
    <title>{{ __('messages.hr_attendance') }} - {{ $period->year }}/{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: center; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .sticky-col { font-weight: bold; text-align: left; }
    </style>
</head>
<body>
    <h2 style="text-align:center;">{{ __('messages.hr_attendance') }} - {{ $period->year }}/{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}</h2>
    <table>
        <thead>
            <tr>
                <th class="sticky-col">{{ __('messages.hr_employee') }}</th>
                @for($day = 1; $day <= $daysInMonth; $day++)
                    <th>{{ $day }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $employee)
                <tr>
                    <td class="sticky-col">{{ $employee->name }}</td>
                    @for($day = 1; $day <= $daysInMonth; $day++)
                        @php
                            $date = sprintf('%04d-%02d-%02d', $period->year, $period->month, $day);
                            $record = $records->get($employee->id)?->firstWhere('date', $date);
                        @endphp
                        <td>
                            @if($record)
                                @switch($record->status)
                                    @case('present') P @break
                                    @case('absent') A @break
                                    @case('sick') S @break
                                    @case('leave') L @break
                                    @case('holiday') H @break
                                    @case('half_day') ½ @break
                                    @default -
                                @endswitch
                            @else
                                -
                            @endif
                        </td>
                    @endfor
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
