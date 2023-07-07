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
 * Boston, MA 02110-1301, USA
 */

namespace OrangeHRM\Buzz\Api\Model;

use OpenApi\Annotations as OA;
use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\BuzzLikeOnShare;

/**
 * @OA\Schema(
 *     schema="Buzz-BuzzLikeOnShareModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="likedAtDate", type="string"),
 *     @OA\Property(property="likedAtTime", type="string"),
 *     @OA\Property(
 *         property="share",
 *         type="object",
 *         @OA\Property(property="id", type="integer")
 *     ),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="terminationId", type="integer")
 *     )
 * )
 */
class BuzzLikeOnShareModel implements Normalizable
{
    use ModelTrait;

    public function __construct(BuzzLikeOnShare $buzzLikeOnShare)
    {
        $this->setEntity($buzzLikeOnShare);
        $this->setFilters(
            [
                'id',
                ['getDecorator', 'getLikedAtDate'],
                ['getDecorator', 'getLikedAtTime'],
                ['getShare', 'getId'],
                ['getEmployee', 'getEmpNumber'],
                ['getEmployee', 'getLastName'],
                ['getEmployee', 'getFirstName'],
                ['getEmployee', 'getMiddleName'],
                ['getEmployee', 'getEmployeeId'],
                ['getEmployee', 'getEmployeeTerminationRecord', 'getId'],
            ]
        );

        $this->setAttributeNames(
            [
                'id',
                'date',
                'time',
                ['share', 'id'],
                ['employee', 'empNumber'],
                ['employee', 'lastName'],
                ['employee', 'firstName'],
                ['employee', 'middleName'],
                ['employee', 'employeeId'],
                ['employee', 'terminationId'],
            ]
        );
    }
}
