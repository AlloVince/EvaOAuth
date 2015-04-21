<!doctype html>
<html class="no-js" lang="">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>EvaEngine OAuth Demo</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <!-- Place favicon.ico in the root directory -->

    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<p>1. Copy config.php to config.local.php </p>
<p>2. Fill config keys and secrets </p>
<p>3. Click below links to start OAuth authorize  </p>

<ul>
<?php
$config = include 'config.php';
foreach ($config as $key => $client) {
    printf("<li><a href='request.php?provider=%s'>%s</a></li>", $key, $key);
}
?>
    </ul>
</body>
</html>


