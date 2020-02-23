<?php

/**
 * index.php
 * Copyright (c) 2020 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

echo '<!DOCTYPE HTML>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="8; url=./public/">
    <script type="text/javascript">
    setTimeout(function() {
        window.location.href = "./public/";
        }, 8000);
    </script>
    <title>Firefly III</title>
    <style>
    p {font-family:Arial,sans-serif;font-size:18px;color:#222;text-align:center;}
</style>
</head>
<body>
<p>
    <strong style="color:red;">Danger!</strong> This directory should not be open to the public!
</p>
<p>
    <span style="font-family:monospace;">/public/</span> should be the document root of your web server.
</p>
<p>
    Leaving your web server configured like this is a <span style="color:red;">huge</span> security risk.
</p>
<p>
Please <a href="https://github.com/firefly-iii/help/wiki/Configure-your-webserver-correctly">read more on the Github help pages</a>.
</p>
</body>
</html>
';
