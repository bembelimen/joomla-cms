describe('Test in backend that the redirect link form', () => {
  beforeEach(() => {
    cy.doAdministratorLogin();
    cy.visit('/administrator/index.php?option=com_redirect&view=link&layout=edit');
  });

  it('renders the destination URL as an editable field with a select button', () => {
    cy.get('#jform_new_url')
      .should('have.class', 'js-linkselect-field-input')
      .and('not.have.attr', 'readonly')
      .and('not.have.attr', 'disabled');

    cy.get('.js-linkselect-field-select').should('be.visible');
  });

  it('offers the registered link sources in the picker', () => {
    cy.get('.js-linkselect-field-select').click();

    cy.get('.joomla-link-picker-nav').within(() => {
      cy.get('[data-key="media"]').should('exist');
      cy.get('[data-key="article"]').should('exist');
      cy.get('[data-key="category"]').should('exist');
      cy.get('[data-key="contact"]').should('exist');
      cy.get('[data-key="menu"]').should('exist');
    });
  });

  it('can insert an article link', () => {
    cy.db_createArticle({ title: 'Test link picker article' }).then(() => {
      cy.reload();

      cy.get('.js-linkselect-field-select').click();
      cy.get('.joomla-link-picker-nav [data-key="article"]').click();

      cy.get('.joomla-link-picker-frame iframe').iframe().then(($body) => {
        cy.wrap($body).find('[data-content-select]').first().click();
      });

      cy.get('#jform_new_url').should('have.value').and('match', /option=com_content/);
    });
  });

  it('can insert a category link', () => {
    cy.get('.js-linkselect-field-select').click();
    cy.get('.joomla-link-picker-nav [data-key="category"]').click();

    cy.get('.joomla-link-picker-frame iframe').iframe().then(($body) => {
      cy.wrap($body).find('[data-content-select]').first().click();
    });

    cy.get('#jform_new_url').should('have.value').and('match', /option=com_content/);
  });

  it('still accepts a manually typed URL', () => {
    const oldUrl = `/test-typed-url-${Date.now()}`;

    cy.get('#jform_old_url').clear().type(oldUrl);
    cy.get('#jform_new_url').clear().type('https://example.org/target');

    cy.clickToolbarButton('Save & Close');

    cy.checkForSystemMessage('Link saved.');
    cy.contains('https://example.org/target');
  });
});
