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
use Orangehrm\Rest\Api\Leave\Entity\Leave;


class ApiLeaveTest extends PHPUnit_Framework_TestCase
{

    /**
     * Set up method
     */
    protected function setUp()
    {

    }

    public function testToArray(){



        $testArray = array(

            'date' => '2016-05-02',
            'status' => 'Pending',
            'durationString' => '13:00-18:00',
            'duration' => '8.0',
            'comments' => ''
        );

        $leave = new Leave();
        $leave->setDate('2016-05-02');
        $leave->setLeaveType('Annual');
        $leave->setComments('');
        $leave->setDuration('8.0');
        $leave->setStatus('Pending');
        $leave->setDurationString('13:00-18:00');



        $this->assertEquals($testArray, $leave->toArray());

    }

}