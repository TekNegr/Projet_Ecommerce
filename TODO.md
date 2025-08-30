# Order System Refactoring - TODO List

## Phase 1: Order Model Updates ✓ COMPLETED
- [x] Remove OrderItem relationships and methods from Order model
- [x] Add cast for items array
- [x] Add helper methods for item array operations

## Phase 2: OrderController Updates ✓ COMPLETED
- [x] Modify store() method to store product data in items array
- [x] Remove OrderItem creation logic
- [x] Update stock management

## Phase 3: View Updates ✓ COMPLETED
- [x] Update orders/show.blade.php to work with item array
- [x] Update seller/orders/show.blade.php to work with item array
- [x] Remove shipping status functionality

## Phase 4: Seller OrderController Updates ✓ COMPLETED
- [x] Remove OrderItem-dependent functionality
- [x] Simplify seller order management

## Phase 5: Testing
- [x] Test order creation
- [x] Test order display
- [x] Test seller functionality

## Phase 6: AI Service Enhancements
- [ ] Add reasoning of why an order would be dissatisfying or not (if possible)
- [ ] Add sorting products via satisfaction
- [ ] Add in review a placeholder of predicted satisfaction
- [ ] Add coupons in case of dissatisfaction
- [ ] Document these changes
