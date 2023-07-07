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

namespace OrangeHRM\Pim\Api;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Pim\Dto\EmployeeSearchFilterParams;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;

class EmployeeCountAPI extends Endpoint implements CollectionEndpoint
{
    use EmployeeServiceTrait;

    public const FILTER_LOCATION_ID = 'locationId';

    public const PARAMETER_COUNT = 'count';

    public const META_PARAMETER_NAME = 'name';
    public const META_PARAMETER_NAME_OR_ID = 'nameOrId';
    public const META_PARAMETER_EMPLOYEE_ID = 'employeeId';
    public const META_PARAMETER_INCLUDE_EMPLOYEES = 'includeEmployees';
    public const META_PARAMETER_EMP_STATUS_ID = 'empStatusId';
    public const META_PARAMETER_JOB_TITLE_ID = 'jobTitleId';
    public const META_PARAMETER_SUBUNIT_IDS = 'subunitIds';
    public const META_PARAMETER_LOCATION_ID = 'locationId';

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $employeeSearchFilterParams = new EmployeeSearchFilterParams();
        $employeeSearchFilterParams->setIncludeEmployees(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_INCLUDE_EMPLOYEES
            )
        );
        $employeeSearchFilterParams->setName(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_NAME
            )
        );
        $employeeSearchFilterParams->setNameOrId(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_NAME_OR_ID
            )
        );
        $employeeSearchFilterParams->setEmployeeId(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_EMPLOYEE_ID
            )
        );
        $employeeSearchFilterParams->setEmpStatusId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_EMP_STATUS_ID
            )
        );
        $employeeSearchFilterParams->setJobTitleId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_JOB_TITLE_ID
            )
        );
        $employeeSearchFilterParams->setSubunitId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                EmployeeAPI::FILTER_SUBUNIT_ID
            )
        );
        $employeeSearchFilterParams->setLocationId(
            $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_LOCATION_ID
            )
        );

        $result = [self::PARAMETER_COUNT => $this->getEmployeeService()->getEmployeeCount($employeeSearchFilterParams)];
        return new EndpointResourceResult(
            ArrayModel::class,
            $result,
            new ParameterBag(
                [
                    self::META_PARAMETER_NAME => $employeeSearchFilterParams->getName(),
                    self::META_PARAMETER_NAME_OR_ID => $employeeSearchFilterParams->getNameOrId(),
                    self::META_PARAMETER_EMPLOYEE_ID => $employeeSearchFilterParams->getEmployeeId(),
                    self::META_PARAMETER_INCLUDE_EMPLOYEES => $employeeSearchFilterParams->getIncludeEmployees(),
                    self::META_PARAMETER_EMP_STATUS_ID => $employeeSearchFilterParams->getEmpStatusId(),
                    self::META_PARAMETER_JOB_TITLE_ID => $employeeSearchFilterParams->getJobTitleId(),
                    self::META_PARAMETER_SUBUNIT_IDS => $employeeSearchFilterParams->getSubunitIdChain(),
                    self::META_PARAMETER_LOCATION_ID => $employeeSearchFilterParams->getLocationId(),
                ]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_INCLUDE_EMPLOYEES,
                    new Rule(
                        Rules::IN,
                        [
                            array_merge(
                                array_keys(EmployeeSearchFilterParams::INCLUDE_EMPLOYEES_MAP),
                                array_values(EmployeeSearchFilterParams::INCLUDE_EMPLOYEES_MAP)
                            )
                        ]
                    )
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_NAME,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, EmployeeAPI::PARAM_RULE_FILTER_NAME_MAX_LENGTH]),
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_NAME_OR_ID,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, EmployeeAPI::PARAM_RULE_FILTER_NAME_OR_ID_MAX_LENGTH]),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_EMPLOYEE_ID,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, EmployeeAPI::PARAM_RULE_EMPLOYEE_ID_MAX_LENGTH]),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_EMP_STATUS_ID,
                    new Rule(Rules::POSITIVE),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_JOB_TITLE_ID,
                    new Rule(Rules::POSITIVE),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    EmployeeAPI::FILTER_SUBUNIT_ID,
                    new Rule(Rules::POSITIVE),
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_LOCATION_ID,
                    new Rule(Rules::POSITIVE),
                )
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
