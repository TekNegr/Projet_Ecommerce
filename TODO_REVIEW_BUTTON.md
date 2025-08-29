# TODO: Add Review Button to Orders Page

## Steps to Complete:

1. [ ] Add reviews relationship to Order model (app/Models/Order.php)
2. [ ] Add helper method to check if user has reviewed order (app/Models/Order.php)
3. [ ] Add review button to orders show page (resources/views/orders/show.blade.php)
4. [ ] Test the implementation

## Implementation Details:

### Order Model Updates:
- Add `reviews()` relationship method
- Add `hasUserReviewed()` method to check if current user has reviewed the order

### Orders Show Page Updates:
- Add review button that only shows for delivered orders
- Button should check if user hasn't already reviewed the order
- Button should link to review form using `route('reviews.show', $order)`
- Button should be styled consistently with other action buttons
