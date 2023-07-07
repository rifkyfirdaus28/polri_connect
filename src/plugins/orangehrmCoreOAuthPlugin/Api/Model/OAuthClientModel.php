<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\OAuth\Api\Model;

use OpenApi\Annotations as OA;
use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\OAuthClient;

/**
 * @OA\Schema(
 *     schema="OAuth-OAuthClientModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="clientId", type="string"),
 *     @OA\Property(property="redirectUri", type="string"),
 *     @OA\Property(property="enabled", type="boolean"),
 *     @OA\Property(property="confidential", type="boolean")
 * )
 */
class OAuthClientModel implements Normalizable
{
    use ModelTrait;

    public function __construct(OAuthClient $oAuthClient)
    {
        $this->setEntity($oAuthClient);
        $this->setFilters(
            [
                'id',
                'name',
                'clientId',
                'redirectUri',
                ['isEnabled'],
                ['isConfidential'],
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                'name',
                'clientId',
                'redirectUri',
                'enabled',
                'confidential',
            ]
        );
    }
}
