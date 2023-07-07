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


/**
 * Test class of Api/EmployeeService
 *
 * @group API
 */
use Orangehrm\Rest\Api\Leave\Entity\LeaveEntitlement;


class ApiLeaveEntitlementTest extends PHPUnit_Framework_TestCase
{

    public function testToArray(){


        $testArray = array(

            'id' => '1',
            'type' => 'Annual',
            'validFrom' => '2016-03-04',
            'validTo' => '2017-03-04',
            'days' => '14'
        );

        $employeeLeaveEntitlement = new LeaveEntitlement("1");
        $employeeLeaveEntitlement->setEntitlementType('Annual');
        $employeeLeaveEntitlement->setValidFrom('2016-03-04');
        $employeeLeaveEntitlement->setValidTo('2017-03-04');
        $employeeLeaveEntitlement->setDays('14');

        $this->assertEquals($testArray, $employeeLeaveEntitlement->toArray());

    }

}