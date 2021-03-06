<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Order;
use App\OrdersLog;
use App\OrdersProduct;
use App\ReturnRequest;
use App\ExchangeRequest;
use App\Product;
use App\ProductsAttribute;
use Auth;
use Session;
class OrdersController extends Controller
{
    public function orders(){
        $orders = Order::with('orders_products')->where('user_id', Auth::user()->id)->orderBy('id', 'Desc')->get()->toArray();
        // dd($orders); die;
        return view('front.orders.orders')->with(compact('orders'));
    }

    public function orderDetails($id){
        $orderDetails= Order::with('orders_products')->where('id', $id)->first()->toArray();
        // dd($orderDetails); die;
        return view('front.orders.order_details')->with(compact('orderDetails'));
    }

    public function orderCancel($id, Request $request){
        if($request->post()){
            $data = $request->all();
            // echo "<pre>"; print_r($data); die;

            if(isset($data['reason']) && empty($data['reason'])){
                return redirect()->back();
            }
            
            // echo "<pre>"; print_r($data); die;

            // Get User ID from Order
            $user_id_auth = Auth::user()->id;

            // Get User ID from Order
            $user_id_order= Order::select('user_id')->where('id', $id)->first();

            // echo $user_id_auth;
            // echo "---";
            // echo $user_id_order->user_id; die;

            if($user_id_auth == $user_id_order->user_id){
                // echo $id; die;
                // Update Order Status to be Cancelled
                Order::where('id', $id)->update(['order_status'=> 'Cancelled']);

                //Update Order Log
                $log = new OrdersLog();
                $log->order_id = $id; 
                $log->order_status = "User Cancelled";
                $log->reason = $data['reason'];
                $log->updated_by = "User";
                $log->save();

                $message = "Order has been Cancelled";
                Session::flash('success_message', $message);
                return redirect()->back();   
            }else{
                $message = "Your Order Cancellation Request is not Valid";
                Session::flash('error_message', $message);
                return redirect('orders');
            } 
        }
    }

    // public function orderCancel($id){

    //     // Get User ID from Order
    //     $user_id_auth = Auth::user()->id;

    //     // Get User ID from Order
    //     $user_id_order= Order::select('user_id')->where('id', $id)->first();

    //     // echo $user_id_auth;
    //     // echo "---";
    //     // echo $user_id_order->user_id; die;
    //     if($user_id_auth == $user_id_order->user_id){
    //         // echo $id; die;
    //         // Update Order Status to be Cancelled
    //         Order::where('id', $id)->update(['order_status'=> 'Cancelled']);

    //         //Update Order Log
    //         $log = new OrdersLog();
    //         $log->order_id = $id; 
    //         $log->order_status = "Cancelled";
    //         $log->save();

    //         $message = "Order has been Cancelled";
    //         Session::flash('success_message', $message);
    //         return redirect()->back();   
    //     }else{
    //         $message = "Your Order Cancellation Request is not Valid";
    //         Session::flash('error_message', $message);
    //         return redirect('orders');
    //     }
    // }

    public function orderReturn($id, Request $request){
        if($request->isMethod('post')){
            $data= $request->all();
            // echo $id;
            // echo "<pre>"; print_r($data); die;

            // Get user ID from Auth
            $user_id_auth = Auth::user()->id;

            // Get User ID from Order
            $user_id_order = Order::select('user_id')->where('id', $id)->first();

            if($user_id_auth == $user_id_order->user_id){
                
                if($data['return_exchange'] == "Return"){

                    // Get Product Details 
                    $productArr = explode("--", $data['product_info']);
                    $product_code = $productArr[0];
                    $product_size = $productArr[1];

                    // Update Item Status 
                    OrdersProduct::where(['order_id' => $id, 'product_code' => $product_code, 'product_size' => $product_size])->update(['item_status'=> 'Return Intitiated']);

                    // Add Return Request
                    $return = new ReturnRequest;
                    $return->order_id = $id;
                    $return->user_id = $user_id_auth;
                    $return->product_size= $product_size;
                    $return->product_code = $product_code;
                    $return->return_reason = $data['return_reason'];
                    $return->return_status="Pending";
                    $return->comment= $data['comment'];
                    $return->save();
                    $message= "Return Request has been placed for the Ordered Product";
                    session::flash('success_message', $message);
                    return redirect()->back();
                    
                }else if($data['return_exchange'] == "Exchange"){
                    
                    // Get Product Details 
                    $productArr = explode("--", $data['product_info']);
                    $product_code = $productArr[0];
                    $product_size = $productArr[1];

                    // Update Item Status 
                    OrdersProduct::where(['order_id' => $id, 'product_code' => $product_code, 'product_size' => $product_size])->update(['item_status'=> 'Exchange Intitiated']);

                    // Add Exchange Request
                    $exchange = new ExchangeRequest;
                    $exchange->order_id = $id;
                    $exchange->user_id = $user_id_auth;
                    $exchange->product_size= $product_size;
                    $exchange->required_size= $data['required_size'];
                    $exchange->product_code = $product_code;
                    $exchange->exchange_reason = $data['return_reason'];
                    $exchange->exchange_status="Pending";
                    $exchange->comment= $data['comment'];
                    $exchange->save();
                    $message= "Exchange Request has been placed for the Ordered Product";
                    session::flash('success_message', $message);
                    return redirect()->back();

                }else{
                    $message = "Your Order Return/Exchange Request is not valid";
                    Session::flash('error_message', $message);
                    return redirect('orders');
                }
            }else{
                $message = "Your Order Return Request is not Valid";
                Session::flash('error_message', $message);
                return redirect('orders');
            }
        }
    }

    public function getProductSizes(Request $request){
        $data = $request->all();
        // echo "<pre>"; print_r($data); die;
        
        // Get Product Details
        $productArr = explode("--", $data['product_info']);
        $product_code = $productArr[0];
        $product_size= $productArr[1];
        
        // Fetch the products id and compares it to the attribute
        $productId = Product::select('id')->where('product_code', $product_code)->first();
        $product_id = $productId->id;

        // dd($product_id);
        $productSizes = ProductsAttribute::select('size')->where('product_id', $product_id)->where('size', '!=', $product_size)->where('stock', '>', 0)->get()->toArray();

        // echo "<pre>"; print_r($productSizes); die;
        // dd($productSizes); 

        // append this in the select box 
        $appendSizes = '<option value="">Select Required Size </option>';
        foreach($productSizes as $size){
            $appendSizes .= '<option value="'.$size['size'].'">'.$size['size'].'</option>';
        }
        return $appendSizes; 
    }
    
}
