<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Cart;
use App\Models\Order;

class StripeController extends Controller
{
    public function stripe(Request $request)
    {
        $cart = Cart::where('user_id', auth()->id())->first();

        // Ensure the cart exists
        if (!$cart) {
            return redirect()->route('cart.index')->with('error', 'You need a cart to place an order.');
        }

        // Validate the request data
        $validated = $request->validate([
            'shipping_first_name' => 'required',
            'shipping_last_name' => 'required',
            'shipping_address' => 'required',
            'shipping_post_code' => 'required',
            'shipping_city' => 'required',
            'shipping_district' => 'required',
            'shipping_phone' => 'required',
            'billing_first_name' => 'nullable',
            'billing_last_name' => 'nullable',
            'billing_address' => 'nullable',
            'billing_post_code' => 'nullable',
            'billing_city' => 'nullable',
            'billing_district' => 'nullable',
            'billing_phone' => 'nullable',
        ]);

        // Set user_id and cart_id in the validated data
        $validated['user_id'] = auth()->id();
        $validated['cart_id'] = $cart->id;
        $validated['total'] = $cart->total;

        // Check if billing address is null and copy from shipping address if necessary
        if (is_null($validated['billing_address'])) {
            $validated['billing_first_name'] = $validated['shipping_first_name'];
            $validated['billing_last_name'] = $validated['shipping_last_name'];
            $validated['billing_address'] = $validated['shipping_address'];
            $validated['billing_post_code'] = $validated['shipping_post_code'];
            $validated['billing_city'] = $validated['shipping_city'];
            $validated['billing_district'] = $validated['shipping_district'];
            $validated['billing_phone'] = $validated['shipping_phone'];
        }

        $order = Order::create($validated);

        $lineitems = [];
        $products = $cart ? $cart->products : collect();
        foreach ($products  as $product){
            $lineitems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $product->name,
                        ],
                        'unit_amount' => $product->pivot->price * 100,
                    ],
                    'quantity' => $product->pivot->quantity,
                ];
        }

        $stripe = new \Stripe\StripeClient(config('stripe.stripe_sk'));
        $response = $stripe->checkout->sessions->create([
            'line_items' => $lineitems,
            'mode' => 'payment',
            'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cancel'),
        ]);

        if (isset($response->id) && $response->id != '') {
            session()->put('order_id', $order->id);
            return redirect($response->url);
        } else {
            return redirect()->route('cancel');
        }
    }

    public function paynow(Request $request, Order $order)
    {
        // get the cart of the order
        $cart = Cart::find($order->cart_id);

        
        $lineitems = [];
        $products = $cart ? $cart->products : collect();
        foreach ($products  as $product){
            $lineitems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $product->name,
                        ],
                        'unit_amount' => $product->pivot->price * 100,
                    ],
                    'quantity' => $product->pivot->quantity,
                ];
        }

        $stripe = new \Stripe\StripeClient(config('stripe.stripe_sk'));
        $response = $stripe->checkout->sessions->create([
            'line_items' => $lineitems,
            'mode' => 'payment',
            'success_url' => route('success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('cancel'),
        ]);

        if (isset($response->id) && $response->id != '') {
            session()->put('order_id', $order->id);
            return redirect($response->url);
        } else {
            return redirect()->route('cancel');
        }
    

    }

    public function success(Request $request)
    {
        if (isset($request->session_id)) {
            $stripe = new \Stripe\StripeClient(config('stripe.stripe_sk'));
            $response = $stripe->checkout->sessions->retrieve($request->session_id);

            // Retrieve the order ID from the session
            $order_id = session()->get('order_id');

            // Find the order and update payment_status
            if ($order_id) {
                $order = Order::find($order_id);
                if ($order) {
                    $order->update(['payment_status' => 'Paid']);
                    // get the cart of the order
                    $cart = Cart::find($order->cart_id);
                    // make the cart is_paid to true
                    $cart->update(['is_paid' => true]);
                }
            }

            return redirect()->route('home')->with('Payment_success', 'Payment Successful! Order confirmed');
        } else {
            return redirect()->route('cancel');
        }
    }

    public function cancel()
    {
        return redirect()->route('home')->with('Payment_cancel', 'Payment Cancelled');
    }
}
