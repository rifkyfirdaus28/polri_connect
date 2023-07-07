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

describe('Admin - Job Category', function () {
  beforeEach(function () {
    cy.task('db:reset');
    cy.fixture('viewport').then(({HD}) => {
      cy.viewport(HD.width, HD.height);
    });
    cy.fixture('chars').as('strings');
    cy.intercept('GET', '**/api/v2/admin/job-categories*').as(
      'getJobCategories',
    );
    cy.intercept('POST', '**/api/v2/admin/job-categories').as(
      'postJobCategories',
    );
    cy.intercept('PUT', '**/api/v2/admin/job-categories/*').as(
      'updateJobCategories',
    );
    cy.intercept('DELETE', '**/api/v2/admin/job-categories').as(
      'deleteJobCategories',
    );
    cy.fixture('user').then(({admin}) => {
      this.user = admin;
    });
  });

  // Read
  describe('list job Categories', function () {
    it('job title list is loaded', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.getOXD('numRecords').contains('(9) Records Found');
    });
  });
  // Create
  describe('Add job Category', function () {
    it('add job category', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.wait('@getJobCategories');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').type(this.strings.chars50.text);
        cy.getOXD('button').contains('Save').click();
      });
      cy.wait('@postJobCategories');
      cy.toast('success', 'Successfully Saved');
    });
    it('Job Category form validations', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.wait('@getJobCategories');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name')
          .type(this.strings.chars100.text)
          .isInvalid('Should not exceed 50 characters');
        cy.getOXDInput('Name').setValue('').isInvalid('Required');
        cy.getOXDInput('Name')
          .type('Craft Workers')
          .isInvalid('Already exists');
      });
    });
    it('add a job category and click cancel', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory');
      cy.wait('@getJobCategories');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').type(this.strings.chars50.text);
        cy.getOXD('button').contains('Cancel').click();
      });
      cy.wait('@getJobCategories');
      cy.getOXD('pageTitle').contains('Job Categories');
    });
  });
  //Update
  describe('Update job Category', function () {
    it('Edit job category', function () {
      cy.loginTo(this.user, '/admin/saveJobCategory/1');
      cy.wait('@getJobCategories');
      cy.getOXD('form').within(() => {
        cy.getOXDInput('Name').clear().type(this.strings.chars50.text);
        cy.getOXD('button').contains('Save').click();
      });
      cy.wait('@updateJobCategories');
      cy.toast('success', 'Successfully Updated');
    });
  });
  //Delete
  describe('Delete job Category', function () {
    it('Delete a single job category', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.get(
        '.oxd-table-body > :nth-child(1) .oxd-table-cell-actions > :nth-child(1)',
      ).click();
      cy.getOXD('button').contains('Yes, Delete').click();
      cy.wait('@getJobCategories');
      cy.toast('success', 'Successfully Deleted');
    });
    it('Bulk Delete job categories', function () {
      cy.loginTo(this.user, '/admin/jobCategory');
      cy.wait('@getJobCategories');
      cy.get('.oxd-table-header .oxd-checkbox-input').click();
      cy.getOXD('button').contains('Delete Selected').click();
      cy.getOXD('button').contains('Yes, Delete').click();
      cy.wait('@getJobCategories');
      cy.toast('success', 'Successfully Deleted');
    });
  });
});
