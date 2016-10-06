#!/usr/bin/php
<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once sprintf('%s/vendor/autoload.php', dirname(__DIR__));

use SURFnet\VPN\Server\Api\OtpLog;
use SURFnet\VPN\Common\CliParser;

try {
    $p = new CliParser(
        'Peform cleaning up of expired OTP keys',
        [
            'instance' => ['the instance', true, true],
        ]
    );

    $opt = $p->parse($argv);
    if ($opt->e('help')) {
        echo $p->help();
        exit(0);
    }

    $dataDir = sprintf('%s/data/%s', dirname(__DIR__), $opt->v('instance'));
    $db = new PDO(sprintf('sqlite://%s/users/otp.sqlite', $dataDir));
    $otpLog = new OtpLog($db);
    // remove all OTP key entries that are older than 5 minutes
    $otpLog->housekeeping(strtotime('now -5 minutes'));
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}