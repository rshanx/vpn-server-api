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

use SURFnet\VPN\Server\OtpLog;

function showHelp(array $argv)
{
    return implode(
        PHP_EOL,
        [
            sprintf('SYNTAX: %s [--instance domain.tld]', $argv[0]),
            '',
            '--instance domain.tld      the instance to initialize',
            '',
        ]
    );
}

try {
    $instanceId = null;

    for ($i = 0; $i < $argc; ++$i) {
        if ('--help' == $argv[$i] || '-h' === $argv[$i]) {
            echo showHelp($argv);
            exit(0);
        }

        if ('--instance' === $argv[$i] || '-i' === $argv[$i]) {
            if (array_key_exists($i + 1, $argv)) {
                $instanceId = $argv[$i + 1];
                ++$i;
            }
        }
    }

    if (is_null($instanceId)) {
        throw new RuntimeException('instance must be specified, see --help');
    }

    $vpnDataDir = sprintf('%s/openvpn-data/%s', dirname(__DIR__), $instanceId);

    // create VPN directory if it does not yet exist
    if (!file_exists($vpnDataDir)) {
        if (false === @mkdir($vpnDataDir, 0711, true)) {
            throw new RuntimeException(sprintf('unable to create directory "%s"', $vpnDataDir));
        }
    }
    $db = new PDO(sprintf('sqlite://%s/otp.sqlite', $vpnDataDir));
    $otpLog = new OtpLog($db);
    $otpLog->initDatabase();
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage()).PHP_EOL;
    exit(1);
}
