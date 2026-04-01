<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Medication safety alert') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.5; color: #1a1a1a; max-width: 36rem;">
    <p>{{ __('Dear :name,', ['name' => $customerName]) }}</p>
    <p><strong>{{ $warningMessage }}</strong></p>
    <p>{{ __('Medication lot number:') }} <strong>{{ $medicationLotNumber }}</strong></p>
    <p>{{ $recommendationMessage }}</p>
    <p style="margin-top: 2rem; font-size: 0.875rem; color: #555;">{{ __('This message was sent because of a pharmacovigilance notice related to your purchase.') }}</p>
</body>
</html>
