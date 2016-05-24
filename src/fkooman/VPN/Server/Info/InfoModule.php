<?php
/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
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

namespace fkooman\VPN\Server\Info;

use fkooman\Http\Request;
use fkooman\Rest\Service;
use fkooman\Rest\ServiceModuleInterface;
use fkooman\Http\JsonResponse;
use fkooman\Rest\Plugin\Authentication\Bearer\TokenInfo;
use fkooman\VPN\Server\Pools;
use fkooman\VPN\Server\Acl\AclInterface;

class InfoModule implements ServiceModuleInterface
{
    /** @var \fkooman\VPN\Server\Pools */
    private $pools;

    /** @var \fkooman\VPN\Server\AclInterface */
    private $acl;

    public function __construct(Pools $pools, AclInterface $acl)
    {
        $this->pools = $pools;
        $this->acl = $acl;
    }

    public function init(Service $service)
    {
        $service->get(
            '/info/server',
            function (Request $request, TokenInfo $tokenInfo) {
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                return $this->getInfo();
            }
        );

        $service->get(
            '/info/users/:userId',
            function ($userId, Request $request, TokenInfo $tokenInfo) {
                // XXX validate userId
                $tokenInfo->getScope()->requireScope(['admin', 'portal']);

                return $this->getUserInfo($userId);
            }
        );
    }

    private function getInfo()
    {
        $data = [];
        foreach ($this->pools as $pool) {
            $data[] = $pool->toArray();
        }
        $response = new JsonResponse();
        $response->setBody(['data' => $data]);

        return $response;
    }

    private function getUserInfo($userId)
    {
        $memberOf = $this->acl->getGroups($userId);
        $response = new JsonResponse();
        $response->setBody(
            [
                'data' => [
                    'memberOf' => $memberOf,
                ],
            ]
        );

        return $response;
    }
}
