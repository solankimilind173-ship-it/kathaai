<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment successful</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    @include('emails.partials.header')
    <h1 style="color: #b45309;">Payment successful</h1>
    <p>Hi {{ $user->name }},</p>
    <p>Thank you for your payment. Your subscription to <strong>{{ $plan->name }}</strong> is now active.</p>

    <table width="100%" cellpadding="12" cellspacing="0" style="margin: 24px 0; border: 1px solid #eee; border-radius: 8px; background: #fafafa;">
        <tr>
            <td colspan="2" style="border-bottom: 1px solid #eee;"><strong style="color: #b45309;">Billing details</strong></td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee; width: 40%;">Plan</td>
            <td style="border-bottom: 1px solid #eee;">{{ $plan->name }}</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee;">Billing interval</td>
            <td style="border-bottom: 1px solid #eee;">{{ $interval === 'yearly' ? 'Yearly' : 'Monthly' }}</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid #eee;">Amount paid</td>
            <td style="border-bottom: 1px solid #eee;"><strong>{{ $amountFormatted }} {{ strtoupper($currency) }}</strong></td>
        </tr>
        @if($plan->monthly_credits)
        <tr>
            <td style="border-bottom: 1px solid #eee;">Credits per month</td>
            <td style="border-bottom: 1px solid #eee;">{{ $plan->monthly_credits }}</td>
        </tr>
        @endif
        @if($invoiceNumber)
        <tr>
            <td style="border-bottom: 1px solid #eee;">Invoice number</td>
            <td style="border-bottom: 1px solid #eee;">{{ $invoiceNumber }}</td>
        </tr>
        @endif
        <tr>
            <td>Date</td>
            <td>{{ now()->format('F j, Y \a\t g:i A') }}</td>
        </tr>
    </table>

    @if($invoicePdfUrl || $receiptUrl)
    <p>
        @if($invoicePdfUrl)
            <a href="{{ $invoicePdfUrl }}" style="display: inline-block; padding: 10px 20px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px; margin-right: 8px;">Download invoice (PDF)</a>
        @endif
        @if($receiptUrl)
            <a href="{{ $receiptUrl }}" style="display: inline-block; padding: 10px 20px; border: 1px solid #b45309; color: #b45309; text-decoration: none; border-radius: 6px;">View receipt</a>
        @endif
    </p>
    @endif

    <p>
        <a href="{{ url('/billing') }}" style="display: inline-block; padding: 10px 20px; background: #b45309; color: #fff; text-decoration: none; border-radius: 6px;">View billing &amp; usage</a>
    </p>
    <p style="margin-top: 24px; font-size: 12px; color: #666;">If you have any questions about this charge, please contact us or visit your billing page.</p>
    @include('emails.partials.footer')
</body>
</html>
