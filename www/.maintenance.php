<?php

/**
 * Maintenance error info script of EasyMinerCenter
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

header('HTTP/1.1 503 Service Unavailable');
header('Retry-After: 300'); // 5 minutes in seconds

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="robots" content="noindex" />
  <style>
    body { color: #333; background: white; width: 500px; margin: 100px auto }
    h1 { font: bold 47px/1.5 sans-serif; margin: .6em 0 }
    p { font: 21px/1.5 Georgia,serif; margin: 1.5em 0 }
  </style>

  <title>Site is temporarily down for maintenance</title>

</head>
<body>
  <h1>We're Sorry</h1>

  <p>EasyMiner is temporarily down for maintenance. Please try again in a few minutes.</p>

</body>
<?php

exit;
