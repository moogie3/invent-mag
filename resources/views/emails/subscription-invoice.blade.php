<x-mail::message>
# Payment Confirmed

Hi {{ $tenantName }},

Your payment has been successfully processed. Here are the details of your subscription invoice:

<x-mail::table>
| Detail | Info |
|:-------|:-----|
| **Invoice Number** | {{ $order->order_number }} |
| **Date** | {{ $order->paid_at->format('F d, Y - H:i') }} |
| **Plan** | {{ $planName }} |
| **Amount (USD)** | ${{ number_format($priceUsd, 0) }} |
| **Amount Charged (IDR)** | Rp {{ number_format($priceIdr, 0, ',', '.') }} |
| **Status** | Paid |
</x-mail::table>

Your account has been upgraded to the **{{ $planName }}** plan. You now have access to all features included in this plan.

<x-mail::button :url="url('/admin/settings/plan')">
View Your Plan
</x-mail::button>

If you have any questions about your subscription, please contact our support team.

Thanks,<br>
{{ config('app.name') }}

<x-mail::subcopy>
This is an automated receipt for your records. No action is required.
</x-mail::subcopy>
</x-mail::message>
