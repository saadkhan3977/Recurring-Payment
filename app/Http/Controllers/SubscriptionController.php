<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use App\Models\User;
use Auth;
class SubscriptionController extends Controller
{
    protected $stripe;
    public function __construct() 
    {
        $this->stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
    }
    public function createPlan()
    {
        // return $this->stripe->invoices->upcomingLines([
        //   'customer' => auth()->user()->stripe_id,
        //   'limit' => 5,
        // ]);
   
        // $user->subscription(Input::get('subscription'))->create(Input::get('stripeToken'), [
        //     'email' => $user->email,
        // ]);
    
        // $subscription = $cu->subscriptions->retrieve($user->stripe_subscription);
        // $subscription->trial_end = Carbon::now()->lastOfMonth()->timestamp;
        // $subscription->save();
    
        // $user->trial_ends_at = Carbon::now()->lastOfMonth();
        // $user->save();
        return view('plans.create');
    }
    public function storePlan(Request $request)
    {   
        $data = $request->except('_token');
        $data['slug'] = strtolower($data['name']);
        $price = $data['cost'] *100; 
        //create stripe product
        $stripeProduct = $this->stripe->products->create([
            'name' => $data['name'],
        ]);        
        //Stripe Plan Creation
        $stripePlanCreation = $this->stripe->plans->create([
            'amount' => $price,
            'currency' => 'USD',
            'interval' => $data['name'],//  it can be day,week,month or year
            'interval_count' => 5, 
            'product' => $stripeProduct->id,
        ]);
        $data['stripe_plan'] = $stripePlanCreation->id;
        Plan::create($data);
        echo 'plan has been created';
    }

    public function create(Request $request, Plan $plan)    {
        $plan = Plan::findOrFail($request->get('plan'));        
        $user = $request->user();
         $paymentMethod = $request->paymentMethod;

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($paymentMethod);
        $user->newSubscription('default', $plan->stripe_plan)
            ->create($paymentMethod, [
                'email' => $user->email,
            ]);
        
        return redirect()->route('home')->with('success', 'Your plan subscribed successfully');
    }


  


}
