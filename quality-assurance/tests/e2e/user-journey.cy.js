describe('User Journey E2E Test', () => {
  beforeEach(() => {
    cy.visit('http://localhost:3000');
  });

  it('Complete purchase journey', () => {
    // 1. Search for product
    cy.get('[data-cy=search-input]').type('فلتر زيت');
    cy.get('[data-cy=search-button]').click();

    // 2. Select product
    cy.get('[data-cy=product-card]').first().click();

    // 3. Add to cart
    cy.get('[data-cy=add-to-cart]').click();
    cy.get('[data-cy=cart-count]').should('contain', '1');

    // 4. Go to cart
    cy.get('[data-cy=cart-icon]').click();
    cy.url().should('include', '/cart');

    // 5. Proceed to checkout
    cy.get('[data-cy=checkout-button]').click();

    // 6. Fill shipping info
    cy.get('[data-cy=shipping-name]').type('أحمد محمد');
    cy.get('[data-cy=shipping-phone]').type('+966501234567');
    cy.get('[data-cy=shipping-address]').type('شارع الملك فهد');
    cy.get('[data-cy=shipping-city]').select('الرياض');

    // 7. Select payment method
    cy.get('[data-cy=payment-mada]').click();

    // 8. Complete order
    cy.get('[data-cy=place-order]').click();

    // 9. Verify success
    cy.url().should('include', '/order-success');
    cy.get('[data-cy=order-number]').should('be.visible');
  });

  it('Voice search functionality', () => {
    // Check if voice search is available
    cy.get('[data-cy=voice-search]').should('be.visible');
    
    // Mock speech recognition
    cy.window().then((win) => {
      const SpeechRecognition = win.SpeechRecognition || win.webkitSpeechRecognition;
      if (SpeechRecognition) {
        cy.get('[data-cy=voice-search]').click();
        cy.get('[data-cy=voice-modal]').should('be.visible');
        // Simulate voice input
        cy.wait(2000);
        cy.get('[data-cy=voice-result]').should('contain', 'فلتر هواء');
      }
    });
  });

  it('AR product view', () => {
    // Navigate to product with AR
    cy.get('[data-cy=product-card]').first().click();
    
    // Check AR button
    cy.get('[data-cy=ar-view]').should('be.visible');
    cy.get('[data-cy=ar-view]').click();
    
    // Verify AR viewer opened
    cy.get('[data-cy=ar-viewer]').should('be.visible');
    cy.get('[data-cy=ar-controls]').should('be.visible');
  });
});
