<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Order;
use App\OrdersProduct;

class ordersExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // return Order::all();

        $ordersData= Order::select('id', 'user_id', 'name', 'address', 'city', 'state', 'country', 'mobile', 'email', 'order_status', 'payment_method', 'payment_gateway', 'grand_total')->orderBy('id', 'Desc')->get();

        // dd($ordersData); die;

        foreach($ordersData as $key => $value){
            // echo $value['id']; echo "<br>"; 
            $orderItems = OrdersProduct::select('id', 'product_code', 'product_name', 'product_color', 'product_size', 'product_price', 'product_qty')->where('order_id', $value['id'])->get();
            // $orderItems = json_decode(json_encode($orderItems));
            // echo "<pre>"; print_r($orderItems); die;

            $product_codes = "";
            $product_names = "";
            $product_colors = "";
            $product_sizes = "";
            $product_prices = "";
            $product_quantities = "";

            foreach ($orderItems as $item) {
                $product_codes .=       $item['product_code'].",";
                $product_names .=       $item['product_name'].",";
                $product_colors .=      $item['product_color'].",";
                $product_sizes .=       $item['product_size'].",";
                $product_prices .=      $item['product_price'].",";
                $product_quantities .=  $item['product_qty'].",";
                // echo "<pre>"; print_r($product_codes); die;
            }
            
            $ordersData[$key]['product_codes'] = $product_codes;
            $ordersData[$key]['product_names'] = $product_names;
            $ordersData[$key]['product_colors'] = $product_colors;
            $ordersData[$key]['product_sizes'] = $product_sizes;
            $ordersData[$key]['product_prices'] = $product_prices;
            $ordersData[$key]['product_quantities'] = $product_quantities;
            // $ordersData = json_decode(json_encode($ordersData));
            // echo "<pre>"; print_r($ordersData); die;
        }
        // die;
        return $ordersData;
    }

    public function headings(): array{
        return ['Id', 'User Id', 'Name', 'Address', 'City', 'State', 'Country', 'Mobile', 'Email', 'Order', 'Status', 'Payment Method', 'Payment Gateway', 'Grand Total', 'Product Colors', 'Product Prizes', 'Product Quantity'];
    }
}
