<?php
/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace fkooman\VPN\Server;

class Firewall
{
    private $ipVersion;
    private $externalIf;
    private $useNat;
    private $inputPorts;
    private $ranges;

    public function __construct($ipVersion = 4, $externalIf = 'eth0', $useNat = true)
    {
        $this->ipVersion = $ipVersion;
        $this->externalIf = $externalIf;
        $this->useNat = $useNat;
        $this->inputPorts = [];
        $this->ranges = [];
    }

    private function getNat()
    {
        return [
            '*nat',
            ':PREROUTING ACCEPT [0:0]',
            ':OUTPUT ACCEPT [0:0]',
            ':POSTROUTING ACCEPT [0:0]',
            sprintf('-A POSTROUTING -o %s -j MASQUERADE', $this->externalIf),
            'COMMIT',
        ];
    }

    private function getFilter()
    {
        $filter = [
            '*filter',
            ':INPUT ACCEPT [0:0]',
            ':FORWARD ACCEPT [0:0]',
            ':OUTPUT ACCEPT [0:0]',
            '-A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT',
            sprintf('-A INPUT -p %s -j ACCEPT', 4 === $this->ipVersion ? 'icmp' : 'ipv6-icmp'),
            '-A INPUT -i lo -j ACCEPT',
        ];

        foreach ($this->inputPorts as $inputPort) {
            list($proto, $port) = explode('/', $inputPort);
            $proto = strtolower($proto);
            $filter[] = sprintf('-A INPUT -m state --state NEW -m %s -p %s --dport %d -j ACCEPT', $proto, $proto, $port);
        }

        $filter[] = sprintf('-A INPUT -j REJECT --reject-with %s', 4 === $this->ipVersion ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');

        $filter = array_merge($filter, $this->getForward());

        $filter[] = sprintf('-A FORWARD -j REJECT --reject-with %s', 4 === $this->ipVersion ? 'icmp-host-prohibited' : 'icmp6-adm-prohibited');
        $filter[] = 'COMMIT';

        return $filter;
    }

    private function getForward()
    {
        $forward = [
            '-N vpn',
            '-A FORWARD -m state --state ESTABLISHED,RELATED -j ACCEPT',
            sprintf('-A FORWARD -i tun+ -o %s -j vpn', $this->externalIf),
            sprintf('-A vpn -p %s -j ACCEPT', 4 === $this->ipVersion ? 'icmp' : 'ipv6-icmp'),
            '-A vpn -m udp -p udp --dport 53 -j ACCEPT',
            '-A vpn -m tcp -p tcp --dport 53 -j ACCEPT',
        ];

        foreach ($this->ranges as $r) {
            $forward = array_merge($forward, $r);
        }

        return $forward;
    }

    public function addInputPorts(array $inputPorts)
    {
        $this->inputPorts = $inputPorts;
    }

    public function addRange($srcNet)
    {
        $range = [];
        $range[] = sprintf('-A vpn -s %s -j ACCEPT', $srcNet);

        $this->ranges[] = $range;
    }

    public function getFirewall()
    {
        $firewall = [];

        if ($this->useNat) {
            $firewall = array_merge($firewall, $this->getNat());
        }
        $firewall = array_merge($firewall, $this->getFilter());

        return implode(PHP_EOL, $firewall).PHP_EOL;
    }
}
