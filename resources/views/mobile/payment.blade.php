<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kainat Travels payment</title>
    <style>body{font:18px system-ui,sans-serif;background:#f5f7fb;color:#10223a;margin:0;padding:24px}main{max-width:440px;margin:12vh auto;background:white;padding:28px;border-radius:20px}h1{font-size:24px}p{line-height:1.5}button{background:#173a6b;color:white;border:0;border-radius:10px;padding:14px 20px;font:inherit;cursor:pointer;width:100%}</style>
</head>
<body><main>
    <h1>Kainat Travels</h1>
    @if($sandbox)<p><strong>Sandbox payment — test transactions only</strong></p>@endif
    <p>{{ $message }}</p>
    @isset($form)
        <form method="post" action="{{ $form['action'] }}" autocomplete="off">
            @foreach($form['fields'] as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <button type="submit">Continue to payment</button>
        </form>
    @endisset
</main></body>
</html>
