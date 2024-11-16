<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Product;
use App\Http\Requests\CheckoutRequest;
use Cart;
use App\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Mail;
use Illuminate\Http\Request;

class PhucTest extends TestCase
{

    /**
     * A basic test example.
     *
     * @return void
     */
    // Controller: CheckoutController
    // Bước chuẩn bị
    // Trước tiên, để chạy unit test, chúng ta cần chuẩn bị môi trường kiểm thử:

    // Sử dụng Laravel phpunit framework.
    // Dùng Mock để giả lập các lớp phụ thuộc như Cart, Mail, và Request.
    // Test cho index method
    // Phương thức index có các nhánh sau:

    // Kiểm tra nếu Cart có sản phẩm hay không (Cart::instance('default')->count() > 0).
    // Tính toán giá trị subtotal, discount, newSubtotal, tax, total.
    // Trả về view checkout hoặc redirect về trang cart.index nếu không có sản phẩm.

    // Kiểm thử khi giỏ hàng trống, ta kiểm tra nhánh redirect sang cart.index

    // Bao phủ quyết định có nghĩa là mỗi điều kiện quyết định 
    // (câu lệnh if, else, hoặc các biểu thức điều kiện) phải được kiểm tra với cả giá trị đúng (true)
    // và sai (false).

    // Câu lệnh if (Cart::instance('default')->count() > 0) kiểm tra xem giỏ hàng có sản phẩm hay không. 
    // Test này giả lập giỏ hàng trống (tức count() trả về 0). 
    // Điều này sẽ kiểm tra nhánh false của quyết định, dẫn đến việc redirect sang trang giỏ hàng (cart.index).
    public function testIndexWithEmptyCart()
    {
        // Mock dữ liệu giỏ hàng
        Cart::shouldReceive('instance->count')->once()->andReturn(0);

        // Gọi controller và phương thức
        $controller = new CheckoutController();
        $response = $controller->index();

        // Kiểm tra điều hướng đến trang cart.index khi giỏ hàng trống
        $this->assertEquals(route('cart.index'), $response->getTargetUrl());
        $this->assertEquals('You have nothing in your cart , please add some products first', session('error'));
    }

    // Kiểm thử khi có sản phẩm trong giỏ hàng. Kiểm tra các tính toán và đảm bảo rằng view checkout được trả về với các giá trị chính xác
    // Trong test này, Cart::instance('default')->count() trả về giá trị 2, có nghĩa là giỏ hàng không trống.
    // Điều này kiểm tra nhánh true của điều kiện if (Cart::instance('default')->count() > 0).
    // Điều này sẽ dẫn đến việc tính toán các giá trị subtotal, discount, tax, và total, đảm bảo rằng đoạn mã bên trong điều kiện này được kiểm tra.
    public function testIndexWithItemsInCart()
    {
        Cart::shouldReceive('instance->count')->once()->andReturn(2);
        Cart::shouldReceive('instance->subtotal')->once()->andReturn(1000);

        // Set up session discount
        session(['coupon' => ['discount' => 100]]);

        $controller = new CheckoutController();
        $response = $controller->index();

        // Kiểm tra nếu view được trả về có các biến phù hợp
        $this->assertEquals(view('checkout')->getName(), $response->getName());
        $this->assertEquals(900, $response->getData()['newSubtotal']);
        $this->assertEquals(189, $response->getData()['tax']);
        $this->assertEquals(1089, $response->getData()['total']);
    }

}
