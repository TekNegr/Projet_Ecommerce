# TODO: Seller Review Notification System

## Steps to Complete:

1. [x] Add notification method to Notification model for seller review alerts
2. [x] Update ReviewController to send notifications to sellers when a review is created
3. [x] Update seller order views to show review notifications
4. [x] Add functionality for sellers to answer reviews

## Implementation Details:

### Notification Model Updates:
- [x] Added `createForSellerReviewPosted()` method to notify sellers when a review is posted

### ReviewController Updates:
- [x] Updated store method to call notification method after review creation
- [x] Notify all sellers associated with the order using seller_ids array
- [x] Implemented answer method for sellers to respond to reviews

### Routes Updates:
- [x] Added route for review answer functionality

### Seller Views Updates:
- [x] Added reviews section to seller order show page
- [x] Added interface for sellers to answer reviews with form
- [x] Display existing answers if already responded

### Order Model Updates:
- [x] Already has seller_ids array field that contains all seller IDs
