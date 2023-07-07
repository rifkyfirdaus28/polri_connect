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

namespace OrangeHRM\Leave\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\ModelTrait;
use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\LeaveEntitlement;

/**
 * @OA\Schema(
 *     schema="Leave-LeaveEntitlementModel",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(
 *         property="employee",
 *         type="object",
 *         @OA\Property(property="empNumber", type="integer"),
 *         @OA\Property(property="firstName", type="string"),
 *         @OA\Property(property="lastName", type="string"),
 *         @OA\Property(property="middleName", type="string"),
 *         @OA\Property(property="employeeId", type="string"),
 *         @OA\Property(property="terminationId", type="integer"),
 *     ),
 *     @OA\Property(property="entitlement", type="integer"),
 *     @OA\Property(property="daysUsed", type="integer"),
 *     @OA\Property(
 *         property="leaveType",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *         @OA\Property(property="deleted", type="boolean")
 *     ),
 *     @OA\Property(property="fromDate", type="number"),
 *     @OA\Property(property="toDate", type="number"),
 *     @OA\Property(property="creditedDate", type="number"),
 *     @OA\Property(
 *         property="entitlementType",
 *         type="object",
 *         @OA\Property(property="id", type="integer"),
 *         @OA\Property(property="name", type="string"),
 *     ),
 *     @OA\Property(property="deleted", type="boolean"),
 *     @OA\Property(property="deletable", type="boolean")
 * )
 */
class LeaveEntitlementModel implements Normalizable
{
    use ModelTrait;

    /**
     * @param LeaveEntitlement $leaveEntitlement
     */
    public function __construct(LeaveEntitlement $leaveEntitlement)
    {
        $this->setEntity($leaveEntitlement);
        $this->setFilters(
            [
                'id',
                ['getEmployee', 'getEmpNumber'],
                ['getEmployee', 'getLastName'],
                ['getEmployee', 'getFirstName'],
                ['getEmployee', 'getMiddleName'],
                ['getEmployee', 'getEmployeeId'],
                ['getEmployee', 'getEmployeeTerminationRecord', 'getId'],
                'noOfDays',
                'daysUsed',
                ['getLeaveType', 'getId'],
                ['getLeaveType', 'getName'],
                ['getLeaveType', 'isDeleted'],
                ['getDecorator', 'getFromDate'],
                ['getDecorator', 'getToDate'],
                ['getDecorator', 'getCreditedDate'],
                ['getEntitlementType', 'getId'],
                ['getEntitlementType', 'getName'],
                ['isDeleted'],
                ['getDecorator', 'isDeletable']
            ]
        );
        $this->setAttributeNames(
            [
                'id',
                ['employee', 'empNumber'],
                ['employee', 'lastName'],
                ['employee', 'firstName'],
                ['employee', 'middleName'],
                ['employee', 'employeeId'],
                ['employee', 'terminationId'],
                'entitlement',
                'daysUsed',
                ['leaveType', 'id'],
                ['leaveType', 'name'],
                ['leaveType', 'deleted'],
                'fromDate',
                'toDate',
                'creditedDate',
                ['entitlementType', 'id'],
                ['entitlementType', 'name'],
                'deleted',
                'deletable',
            ]
        );
    }
}
