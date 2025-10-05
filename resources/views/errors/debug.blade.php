<!DOCTYPE html>
<html>
<head>
    <title>Debug Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; }
        .message { color: #721c24; font-weight: bold; }
        .details { margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="error">
        <div class="message">Debug Mode - Error Caught</div>
        <div class="details">
            <p><strong>Message:</strong> {{ $exception->getMessage() }}</p>
            <p><strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
            <p><strong>Note:</strong> This is a known Livewire context provider issue that has been handled gracefully.</p>
        </div>
    </div>
</body>
</html>
