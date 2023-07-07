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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\Exception\RecordNotFoundException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Exception\DaoException;
use OrangeHRM\Entity\EmployeeImmigrationRecord;
use OrangeHRM\Pim\Api\Model\EmployeeImmigrationModel;
use OrangeHRM\Pim\Dto\EmployeeImmigrationRecordSearchFilterParams;
use OrangeHRM\Pim\Service\EmployeeImmigrationRecordService;

class EmployeeImmigrationRecordAPI extends Endpoint implements CrudEndpoint
{
    public const PARAMETER_NUMBER = 'number';
    public const PARAMETER_ISSUE_DATE = 'issuedDate';
    public const PARAMETER_EXPIRY_DATE = 'expiryDate';
    public const PARAMETER_TYPE = 'type';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_REVIEW_DATE = 'reviewDate';
    public const PARAMETER_COUNTRY_CODE = 'countryCode';
    public const PARAMETER_COMMENT = 'comment';

    public const FILTER_NUMBER = 'number';
    public const PARAM_RULE_DEFAULT_MAX_LENGTH = 30;
    public const PARAM_RULE_COMMENT_MAX_LENGTH = 250;
    public const PARAM_RULE_COUNTRY_MAX_LENGTH = 100;

    /**
     * @var EmployeeImmigrationRecordService|null
     */
    protected ?EmployeeImmigrationRecordService $employeeImmigrationRecordService = null;

    /**
     * @return EmployeeImmigrationRecordService
     */
    public function getEmployeeImmigrationRecordService(): EmployeeImmigrationRecordService
    {
        if (!$this->employeeImmigrationRecordService instanceof EmployeeImmigrationRecordService) {
            $this->employeeImmigrationRecordService = new EmployeeImmigrationRecordService();
        }
        return $this->employeeImmigrationRecordService;
    }

    /**
     * @inheritDoc
     * @throws DaoException
     */
    public function getAll(): EndpointCollectionResult
    {
        $employeeImmigrationRecordSearchParams = new EmployeeImmigrationRecordSearchFilterParams();
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );

        $this->setSortingAndPaginationParams($employeeImmigrationRecordSearchParams);
        $employeeImmigrationRecordSearchParams->setNumber(
            $this->getRequestParams()->getStringOrNull(
                RequestParams::PARAM_TYPE_QUERY,
                self::FILTER_NUMBER
            )
        );
        $employeeImmigrationRecordSearchParams->setEmpNumber($empNumber);
        $immigrationRecord = $this->getEmployeeImmigrationRecordService()->searchEmployeeImmigrationRecords(
            $employeeImmigrationRecordSearchParams
        );
        return new EndpointCollectionResult(
            EmployeeImmigrationModel::class,
            $immigrationRecord,
            new ParameterBag(
                [
                    CommonParams::PARAMETER_EMP_NUMBER => $empNumber,
                    CommonParams::PARAMETER_TOTAL => $this->getEmployeeImmigrationRecordService()->getSearchEmployeeImmigrationRecordsCount($employeeImmigrationRecordSearchParams)
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
            new ParamRule(
                CommonParams::PARAMETER_EMP_NUMBER,
                new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::FILTER_NUMBER,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_DEFAULT_MAX_LENGTH]),
                ),
            ),
            ...
            $this->getSortingAndPaginationParamsRules(EmployeeImmigrationRecordSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @inheritDoc
     */
    public function create(): EndpointResourceResult
    {
        $immigrationRecord = $this->saveEmployeeEmergencyImmigrations();
        return new EndpointResourceResult(
            EmployeeImmigrationModel::class,
            $immigrationRecord,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $immigrationRecord->getEmployee()->getEmpNumber()])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_EMP_NUMBER,
                new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
            ),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    private function getCommonBodyValidationRules(): array
    {
        return [
            new ParamRule(
                self::PARAMETER_NUMBER,
                new Rule(Rules::STRING_TYPE),
                new Rule(Rules::LENGTH, [null, self::PARAM_RULE_DEFAULT_MAX_LENGTH])
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_ISSUE_DATE,
                    new Rule(Rules::API_DATE),
                ),
            ),
            new ParamRule(
                self::PARAMETER_TYPE,
                new Rule(Rules::NUMBER),
                new Rule(Rules::BETWEEN, [0, 3]),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EXPIRY_DATE,
                    new Rule(Rules::API_DATE),
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_DEFAULT_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COUNTRY_CODE,
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_COUNTRY_MAX_LENGTH]),
                    new Rule(Rules::COUNTRY_CODE),
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_REVIEW_DATE,
                    new Rule(Rules::API_DATE),
                ),
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_COMMENT,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::PARAM_RULE_COMMENT_MAX_LENGTH])
                ),
                true
            ),
        ];
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResourceResult
    {
        $recordId = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            CommonParams::PARAMETER_IDS
        );
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $this->getEmployeeImmigrationRecordService()->deleteEmployeeImmigrationRecords($empNumber, $recordId);
        return new EndpointResourceResult(
            ArrayModel::class,
            $recordId,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_EMP_NUMBER,
                new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
            ),
            new ParamRule(
                CommonParams::PARAMETER_IDS,
                new Rule(Rules::ARRAY_TYPE)
            ),
        );
    }

    /**
     * @inheritDoc
     * @throws DaoException
     * @throws RecordNotFoundException
     */
    public function getOne(): EndpointResourceResult
    {
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $recordId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $immigrationRecord = $this->getEmployeeImmigrationRecordService()->getEmployeeImmigrationRecord(
            $empNumber,
            $recordId
        );
        $this->throwRecordNotFoundExceptionIfNotExist($immigrationRecord, EmployeeImmigrationRecord::class);

        return new EndpointResourceResult(
            EmployeeImmigrationModel::class,
            $immigrationRecord,
            new ParameterBag([CommonParams::PARAMETER_EMP_NUMBER => $empNumber])
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_EMP_NUMBER,
                new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
            ),
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::BETWEEN, [0, 100])
            ),
        );
    }

    /**
     * @inheritDoc
     * @throws DaoException
     */
    public function update(): EndpointResourceResult
    {
        $employeeImmigrationRecord = $this->saveEmployeeEmergencyImmigrations();
        return new EndpointResourceResult(
            EmployeeImmigrationModel::class,
            $employeeImmigrationRecord,
            new ParameterBag(
                [CommonParams::PARAMETER_EMP_NUMBER => $employeeImmigrationRecord->getEmployee()->getEmpNumber()]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_EMP_NUMBER,
                new Rule(Rules::IN_ACCESSIBLE_EMP_NUMBERS)
            ),
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::BETWEEN, [0, 100])
            ),
            ...$this->getCommonBodyValidationRules(),
        );
    }

    /**
     * @return EmployeeImmigrationRecord
     * @throws DaoException
     * @throws RecordNotFoundException
     */
    public function saveEmployeeEmergencyImmigrations(): EmployeeImmigrationRecord
    {
        $recordId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $empNumber = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            CommonParams::PARAMETER_EMP_NUMBER
        );
        $number = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NUMBER);
        $issuedDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ISSUE_DATE
        );
        $expiryDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_EXPIRY_DATE
        );
        $reviewDate = $this->getRequestParams()->getDateTimeOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_REVIEW_DATE
        );
        $type = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TYPE);
        $status = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_STATUS
        );
        $comment = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_COMMENT
        );
        $country = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_COUNTRY_CODE
        );
        if ($recordId) {
            $employeeImmigrationRecord = $this->getEmployeeImmigrationRecordService()->getEmployeeImmigrationRecord(
                $empNumber,
                $recordId
            );
            $this->throwRecordNotFoundExceptionIfNotExist($employeeImmigrationRecord, EmployeeImmigrationRecord::class);
        } else {
            $employeeImmigrationRecord = new EmployeeImmigrationRecord();
            $employeeImmigrationRecord->getDecorator()->setEmployeeByEmpNumber($empNumber);
        }
        $employeeImmigrationRecord->setNumber($number);
        $employeeImmigrationRecord->setIssuedDate($issuedDate);
        $employeeImmigrationRecord->setExpiryDate($expiryDate);
        $employeeImmigrationRecord->setReviewDate($reviewDate);
        $employeeImmigrationRecord->setType($type);
        $employeeImmigrationRecord->setStatus($status);
        $employeeImmigrationRecord->setComment($comment);
        $employeeImmigrationRecord->setCountryCode($country);
        return $this->getEmployeeImmigrationRecordService()->saveEmployeeImmigrationRecord($employeeImmigrationRecord);
    }
}
