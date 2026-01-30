#!/bin/bash

BASE_URL="https://e5072e5d1402.ngrok.app"

echo "=============================================="
echo "   Sylius Headless Mollie Checkout Test"
echo "=============================================="
echo ""

# Step 1: Create cart
echo "[1/11] Creating cart..."
CART=$(curl -sk -X POST "${BASE_URL}/api/v2/shop/orders" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{}')
TOKEN=$(echo "$CART" | grep -o '"tokenValue":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
  echo "       ERROR: Failed to create cart"
  exit 1
fi
echo "       Token: $TOKEN"

# Step 2: Get product variant
echo "[2/11] Fetching product variant..."
VARIANT=$(curl -sk "${BASE_URL}/api/v2/shop/product-variants?itemsPerPage=1" \
  -H "Accept: application/json" | grep -o '"code":"[^"]*"' | head -1 | cut -d'"' -f4)
echo "       Variant: $VARIANT"

# Step 3: Add to cart
echo "[3/11] Adding item to cart..."
ADD_RESP=$(curl -sk -X POST "${BASE_URL}/api/v2/shop/orders/${TOKEN}/items" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"productVariant": "/api/v2/shop/product-variants/'"${VARIANT}"'", "quantity": 1}')

SHIPMENT_ID=$(echo "$ADD_RESP" | grep -o '"shipments":\[{"id":[0-9]*' | grep -o '[0-9]*$')
PAYMENT_ID=$(echo "$ADD_RESP" | grep -o '"payments":\[{"id":[0-9]*' | grep -o '[0-9]*$')
echo "       Shipment ID: $SHIPMENT_ID"
echo "       Payment ID: $PAYMENT_ID"

# Step 4: Set addresses
echo "[4/11] Setting billing and shipping address..."
curl -sk -X PUT "${BASE_URL}/api/v2/shop/orders/${TOKEN}" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "test@example.com",
    "billingAddress": {
      "firstName": "John",
      "lastName": "Doe",
      "countryCode": "US",
      "street": "123 Main St",
      "city": "New York",
      "postcode": "10001"
    },
    "shippingAddress": {
      "firstName": "John",
      "lastName": "Doe",
      "countryCode": "US",
      "street": "123 Main St",
      "city": "New York",
      "postcode": "10001"
    }
  }' > /dev/null
echo "       Done"

# Step 5: Get shipping methods
echo "[5/11] Fetching available shipping methods..."
SHIP_METHODS=$(curl -sk "${BASE_URL}/api/v2/shop/orders/${TOKEN}/shipments/${SHIPMENT_ID}/methods" \
  -H "Accept: application/json")
SHIP_METHOD=$(echo "$SHIP_METHODS" | grep -o '"code":"[^"]*"' | head -1 | cut -d'"' -f4)
echo "       Selected: $SHIP_METHOD"

# Step 6: Set shipping method
echo "[6/11] Setting shipping method..."
curl -sk -X PATCH "${BASE_URL}/api/v2/shop/orders/${TOKEN}/shipments/${SHIPMENT_ID}" \
  -H "Content-Type: application/merge-patch+json" \
  -H "Accept: application/json" \
  -d '{"shippingMethod": "/api/v2/shop/shipping-methods/'"${SHIP_METHOD}"'"}' > /dev/null
echo "       Done"

# Step 7: Get payment methods
echo "[7/11] Fetching available payment methods..."
PAY_METHODS=$(curl -sk "${BASE_URL}/api/v2/shop/orders/${TOKEN}/payments/${PAYMENT_ID}/methods" \
  -H "Accept: application/json")
echo "$PAY_METHODS" | grep -o '"code":"[^"]*"' | cut -d'"' -f4 | while read code; do
  echo "       - $code"
done

echo ""
echo "       Enter payment method code (e.g. mollie):"
read PM

# Step 8: Set payment method
echo "[8/11] Setting payment method..."
curl -sk -X PATCH "${BASE_URL}/api/v2/shop/orders/${TOKEN}/payments/${PAYMENT_ID}" \
  -H "Content-Type: application/merge-patch+json" \
  -H "Accept: application/json" \
  -d '{"paymentMethod": "/api/v2/shop/payment-methods/'"${PM}"'"}' > /dev/null
echo "       Done"

# Step 9: Complete checkout
echo "[9/11] Completing checkout..."
curl -sk -X PATCH "${BASE_URL}/api/v2/shop/orders/${TOKEN}/complete" \
  -H "Content-Type: application/merge-patch+json" \
  -H "Accept: application/json" \
  -d '{}' > /dev/null
echo "       Done"

# Step 10: Get Mollie methods
echo "[10/11] Fetching Mollie payment methods..."
MOLLIE_METHODS=$(curl -sk "${BASE_URL}/api/v2/shop/orders/${TOKEN}/mollie-methods" \
  -H "Accept: application/json")
echo "$MOLLIE_METHODS" | grep -o '"methodId":"[^"]*"' | cut -d'"' -f4 | while read method; do
  echo "       - $method"
done

echo ""
echo "       Enter Mollie method (e.g. ideal, creditcard, paypal):"
read MOLLIE_METHOD

# Step 11: Create Mollie payment
echo "[11/11] Creating Mollie payment..."
SELECT_RESP=$(curl -sk -X POST "${BASE_URL}/api/v2/shop/orders/${TOKEN}/mollie-methods" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"methodId": "'"${MOLLIE_METHOD}"'"}')

CHECKOUT_URL=$(echo "$SELECT_RESP" | grep -o '"checkoutUrl":"[^"]*"' | cut -d'"' -f4 | sed 's/\\//g')
MOLLIE_PAYMENT_ID=$(echo "$SELECT_RESP" | grep -o '"paymentId":"[^"]*"' | cut -d'"' -f4)

echo ""
echo "=============================================="
echo "   Checkout Complete!"
echo "=============================================="
echo ""
echo "   Order Token:      $TOKEN"
echo "   Payment ID:       $PAYMENT_ID"
echo "   Mollie Payment:   $MOLLIE_PAYMENT_ID"
echo ""
echo "   Open in browser to pay:"
echo "   $CHECKOUT_URL"
echo ""
echo "   After payment, check status:"
echo "   curl -s '${BASE_URL}/api/v2/shop/orders/${TOKEN}/mollie-status'"
echo ""
echo "=============================================="
