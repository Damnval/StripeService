<?php

namespace App\Services\Api;

Use Illuminate\Support\Facades\Response;

use Exception;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Token;

class StripeService
{
    private $apiKey;
    private $customer;

    /**
     * Set stripe api key
     * @throws string Exception message
     */
    public function __construct()
    {
       $this->apiKey = env('STRIPE_API_KEY');

       if (strlen($this->apiKey) < 1) {
            throw new Exception("No Api Key found.");
       }
        Stripe::setApiKey($this->apiKey);
    }

    /**
     * Register user to stripe
     * @param  object $request Input by user end
     * @return object Response from Stripe
     */
    public function createCustomer($request = null)
    {
        $arrayData = [
            "email" => $request->email
        ];
        try {
            $this->customer = Customer::create($arrayData);

        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->customer;
    }

    /**
     * Getting customer data from stripe
     * @param  integer $stripe_cust_id
     * @return object Customer Object from Stripe
     */
    public function retrieveCustomer($stripe_cust_id)
    {
        try {
            $this->customer = Customer::retrieve($stripe_cust_id);
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->customer;
    }

    /**
     * Setting default billing
     * @param  string $stripe_cust_id
     * @param  string $source_id
     * @return object Customer object from stripe
     */
    public function updateCustomer($stripe_cust_id, $source_id)
    {
        try {
            $this->customer = $this->retrieveCustomer($stripe_cust_id);
            $this->customer->default_source = $source_id;
            $this->customer->save();
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->customer;
    }

    /**
     * Get Customer
     * @return object Created customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Delete customer from stripe
     * @param  integer $stripe_cust_id
     * @return object Response from stripe
     */
    public function deleteCustomer($stripe_cust_id)
    {
        try {
            $this->customer = $this->retrieveCustomer($stripe_cust_id);
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->customer->delete();
    }

    /**
     * Get all customer cards
     * @param  integer $stripe_cust_id
     * @return object Array of card objects
     */
    public function getListCards($stripe_cust_id)
    {
        try {
            $cust = $this->retrieveCustomer($stripe_cust_id);
            return $cust->sources->all(['object' => 'card']);
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
    }

    /**
     * Creates a source as requirement to create card
     * @param  object $request Input by user end
     * @return object $token Response from Stripe
     */
    public function createCardToken($request)
    {
        try {
            $arrayData = [
                "number" => $request->input('card_number'),
                "exp_month" => $request->input('exp_month'),
                "exp_year" => $request->input('exp_year'),
                "cvc" => $request->input('cvc') ,
                "name" => $request->input('card_number') .  " " . $request->input('card_number')
            ];
            $token = Token::create(["card" => $arrayData]);
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->createCard($request->input('stripe_customer_id'), $token);
    }

    /**
     * Create card for customer
     * @param  integer $stripe_customer_id
     * @param  object $token
     * @return object Saved card from stripe
     */
    public function createCard($stripe_customer_id, $token)
    {
        try {
            $this->customer = $this->retrieveCustomer($stripe_customer_id);
            $billing = $this->customer->sources->create(["source" => $token->id]);
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $billing;
    }

    /**
     * Delete Card from stripe
     * @param  string $stripe_customer_id
     * @param  string $gateway_id
     * @return object Return from stripe
     */
    public function deleteCard($stripe_customer_id, $gateway_id)
    {
        try {
            $this->customer = $this->retrieveCustomer($stripe_customer_id);
            $this->customer->sources->retrieve($gateway_id)->delete();
        } catch (Exception $e) {
            error_log($e->getLine().' '.$e->getFileName().' '.$e->getCode());
        }
        return $this->customer;
    }

}
